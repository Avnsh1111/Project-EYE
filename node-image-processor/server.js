/**
 * High-Performance Parallel Image Processing Service
 * Processes multiple images concurrently for faster throughput
 */

const express = require('express');
const multer = require('multer');
const path = require('path');
const fs = require('fs').promises;
const { processImage, processBatch } = require('./processors/imageProcessor');
const { extractMetadata } = require('./processors/metadataExtractor');
const { default: PQueue } = require('p-queue');

const app = express();
const PORT = process.env.PORT || 3000;
const MAX_CONCURRENT = parseInt(process.env.MAX_CONCURRENT || '2');
const PYTHON_AI_URL = process.env.PYTHON_AI_URL || 'http://python-ai:8000';
const SHARED_IMAGES_PATH = process.env.SHARED_IMAGES_PATH || '/app/shared';

// Create processing queue with concurrency limit
const processingQueue = new PQueue({ concurrency: MAX_CONCURRENT });

// Middleware
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Configure multer for file uploads
const storage = multer.diskStorage({
  destination: async (req, file, cb) => {
    const uploadDir = path.join(SHARED_IMAGES_PATH, 'temp');
    try {
      await fs.mkdir(uploadDir, { recursive: true });
      cb(null, uploadDir);
    } catch (error) {
      cb(error);
    }
  },
  filename: (req, file, cb) => {
    const uniqueSuffix = Date.now() + '-' + Math.round(Math.random() * 1E9);
    cb(null, uniqueSuffix + path.extname(file.originalname));
  }
});

const upload = multer({
  storage: storage,
  limits: {
    fileSize: 50 * 1024 * 1024, // 50MB
  },
  fileFilter: (req, file, cb) => {
    const allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp'];
    if (allowedMimes.includes(file.mimetype)) {
      cb(null, true);
    } else {
      cb(new Error('Invalid file type. Only images are allowed.'));
    }
  }
});

// Health check endpoint
app.get('/health', (req, res) => {
  res.json({
    status: 'healthy',
    service: 'image-processor',
    version: '1.0.0',
    queue: {
      size: processingQueue.size,
      pending: processingQueue.pending,
      concurrency: MAX_CONCURRENT
    }
  });
});

// Process single image
app.post('/process', upload.single('image'), async (req, res) => {
    try {
        if (!req.file) {
            return res.status(400).json({ error: 'No image file provided' });
        }

        const originalFilename = req.file.originalname || path.basename(req.file.path);

        const result = await processingQueue.add(async () => {
            return await processImage(req.file.path, {
                pythonAiUrl: PYTHON_AI_URL,
                sharedPath: SHARED_IMAGES_PATH,
                originalFilename: originalFilename
            });
        });

        // Clean up temp file only if it was moved
        if (result.path && result.path !== req.file.path) {
            await fs.unlink(req.file.path).catch(() => {});
        }

        res.json(result);
    } catch (error) {
        console.error('Error processing image:', error);
        
        // Clean up temp file on error
        if (req.file?.path) {
            await fs.unlink(req.file.path).catch(() => {});
        }

        res.status(500).json({
            error: 'Failed to process image',
            message: error.message
        });
    }
});

// Process multiple images in parallel
// Use upload.any() to accept any field name (including images[])
app.post('/process/batch', upload.any(), async (req, res) => {
  try {
    // Filter only image files (multer.any() accepts all files)
    const imageFiles = req.files.filter(file => 
      file.mimetype && file.mimetype.startsWith('image/')
    );
    
    if (!imageFiles || imageFiles.length === 0) {
      return res.status(400).json({ error: 'No image files provided' });
    }

    console.log(`Processing batch of ${imageFiles.length} images...`);

    // Map files to include original filenames
    const fileData = imageFiles.map(f => ({
      path: f.path,
      originalname: f.originalname || path.basename(f.path)
    }));

    const results = await processBatch(
      fileData,
      {
        pythonAiUrl: PYTHON_AI_URL,
        sharedPath: SHARED_IMAGES_PATH,
        concurrency: MAX_CONCURRENT
      }
    );

    // Clean up temp files
    await Promise.all(
      imageFiles.map(f => fs.unlink(f.path).catch(() => {}))
    );

    res.json({
      success: true,
      processed: results.length,
      results: results
    });
  } catch (error) {
    console.error('Error processing batch:', error);
    
    // Clean up temp files on error
    if (req.files) {
      const imageFiles = req.files.filter(file => 
        file.mimetype && file.mimetype.startsWith('image/')
      );
      await Promise.all(
        imageFiles.map(f => fs.unlink(f.path).catch(() => {}))
      );
    }

    res.status(500).json({
      error: 'Failed to process batch',
      message: error.message
    });
  }
});

// Extract metadata only (fast, no AI processing)
app.post('/metadata', upload.single('image'), async (req, res) => {
  try {
    if (!req.file) {
      return res.status(400).json({ error: 'No image file provided' });
    }

    const metadata = await extractMetadata(req.file.path);

    // Clean up temp file
    await fs.unlink(req.file.path).catch(() => {});

    res.json(metadata);
  } catch (error) {
    console.error('Error extracting metadata:', error);
    
    if (req.file?.path) {
      await fs.unlink(req.file.path).catch(() => {});
    }

    res.status(500).json({
      error: 'Failed to extract metadata',
      message: error.message
    });
  }
});

// Extract metadata for batch
// Use upload.any() to accept any field name (including images[])
app.post('/metadata/batch', upload.any(), async (req, res) => {
  try {
    // Filter only image files (multer.any() accepts all files)
    const imageFiles = req.files.filter(file => 
      file.mimetype && file.mimetype.startsWith('image/')
    );
    
    if (!imageFiles || imageFiles.length === 0) {
      return res.status(400).json({ error: 'No image files provided' });
    }

    const metadataResults = await Promise.all(
      imageFiles.map(async (file) => {
        try {
          const metadata = await extractMetadata(file.path);
          return { filename: file.originalname, ...metadata };
        } catch (error) {
          return { filename: file.originalname, error: error.message };
        }
      })
    );

    // Clean up temp files
    await Promise.all(
      imageFiles.map(f => fs.unlink(f.path).catch(() => {}))
    );

    res.json({
      success: true,
      processed: metadataResults.length,
      results: metadataResults
    });
  } catch (error) {
    console.error('Error extracting batch metadata:', error);
    
    if (req.files) {
      const imageFiles = req.files.filter(file => 
        file.mimetype && file.mimetype.startsWith('image/')
      );
      await Promise.all(
        imageFiles.map(f => fs.unlink(f.path).catch(() => {}))
      );
    }

    res.status(500).json({
      error: 'Failed to extract batch metadata',
      message: error.message
    });
  }
});

// Error handling middleware
app.use((error, req, res, next) => {
  console.error('Unhandled error:', error);
  res.status(500).json({
    error: 'Internal server error',
    message: error.message
  });
});

// Start server
app.listen(PORT, '0.0.0.0', () => {
  console.log(`🚀 Image Processor Service running on port ${PORT}`);
  console.log(`📊 Max concurrent processing: ${MAX_CONCURRENT}`);
  console.log(`🔗 Python AI URL: ${PYTHON_AI_URL}`);
  console.log(`📁 Shared images path: ${SHARED_IMAGES_PATH}`);
});

