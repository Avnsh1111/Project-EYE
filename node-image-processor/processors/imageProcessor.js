/**
 * Image Processing Module
 * Handles image optimization, resizing, and AI analysis coordination
 */

const sharp = require('sharp');
const path = require('path');
const fs = require('fs').promises;
const axios = require('axios');
const { extractMetadata } = require('./metadataExtractor');
const { default: PQueue } = require('p-queue');

/**
 * Process a single image
 */
async function processImage(imagePath, options = {}) {
  const {
    pythonAiUrl = 'http://python-ai:8000',
    sharedPath = '/app/shared',
    optimize = true,
    generateThumbnail = true,
    originalFilename = null
  } = options;

  try {
    // Use original filename if provided, otherwise use temp filename
    const filename = originalFilename || path.basename(imagePath);
    const ext = path.extname(filename).toLowerCase();
    const nameWithoutExt = path.basename(filename, ext);
    
    // Extract metadata first (fast)
    const metadata = await extractMetadata(imagePath);
    
    // Determine final path - if already in shared path, use it, otherwise move
    let finalPath;
    if (imagePath.startsWith(sharedPath)) {
      finalPath = imagePath;
    } else {
      finalPath = path.join(sharedPath, filename);
      // Ensure directory exists
      await fs.mkdir(path.dirname(finalPath), { recursive: true });
      await fs.rename(imagePath, finalPath);
    }

    // Process image in parallel: optimization and thumbnail generation
    const [optimizedPath, thumbnailPath] = await Promise.all([
      optimize ? optimizeImage(finalPath, sharedPath) : Promise.resolve(null),
      generateThumbnail ? generateThumbnailImage(finalPath, sharedPath) : Promise.resolve(null)
    ]);

    // Call Python AI service for analysis
    let aiAnalysis = null;
    try {
      const aiResponse = await axios.post(`${pythonAiUrl}/analyze`, {
        image_path: finalPath,
        ollama_enabled: true,
        face_detection_enabled: true
      }, {
        timeout: 300000 // 5 minutes
      });
      
      aiAnalysis = aiResponse.data;
    } catch (error) {
      console.warn(`AI analysis failed for ${filename}:`, error.message);
      // Continue without AI analysis
    }

    return {
      success: true,
      filename: filename,
      path: finalPath,
      optimized_path: optimizedPath,
      thumbnail_path: thumbnailPath,
      metadata: metadata,
      ai_analysis: aiAnalysis,
      processed_at: new Date().toISOString()
    };
  } catch (error) {
    console.error(`Error processing image ${imagePath}:`, error);
    throw error;
  }
}

/**
 * Process multiple images in parallel
 */
async function processBatch(imageData, options = {}) {
  const {
    pythonAiUrl = 'http://python-ai:8000',
    sharedPath = '/app/shared',
    concurrency = 2
  } = options;

  // Handle both array of paths (legacy) and array of objects with path/originalname
  const normalizedData = imageData.map(item => {
    if (typeof item === 'string') {
      return { path: item, originalname: null };
    }
    return item;
  });

  const queue = new PQueue({ concurrency });
  
  const results = await Promise.allSettled(
    normalizedData.map(({ path: imagePath, originalname }) =>
      queue.add(() => processImage(imagePath, { 
        pythonAiUrl, 
        sharedPath,
        originalFilename: originalname
      }))
    )
  );

  return results.map((result, index) => {
    if (result.status === 'fulfilled') {
      return result.value;
    } else {
      return {
        success: false,
        filename: normalizedData[index].originalname || path.basename(normalizedData[index].path),
        error: result.reason?.message || 'Unknown error'
      };
    }
  });
}

/**
 * Optimize image (compress, strip metadata)
 */
async function optimizeImage(imagePath, outputDir) {
  try {
    const filename = path.basename(imagePath);
    const ext = path.extname(filename).toLowerCase();
    const nameWithoutExt = path.basename(filename, ext);
    const optimizedPath = path.join(outputDir, `${nameWithoutExt}_optimized${ext}`);

    await sharp(imagePath)
      .jpeg({ quality: 85, progressive: true })
      .png({ quality: 85, compressionLevel: 9 })
      .webp({ quality: 85 })
      .toFile(optimizedPath);

    return optimizedPath;
  } catch (error) {
    console.warn(`Image optimization failed:`, error.message);
    return null;
  }
}

/**
 * Generate thumbnail
 */
async function generateThumbnailImage(imagePath, outputDir) {
  try {
    const filename = path.basename(imagePath);
    const ext = path.extname(filename).toLowerCase();
    const nameWithoutExt = path.basename(filename, ext);
    const thumbnailPath = path.join(outputDir, `${nameWithoutExt}_thumb${ext}`);

    await sharp(imagePath)
      .resize(300, 300, {
        fit: 'inside',
        withoutEnlargement: true
      })
      .jpeg({ quality: 80 })
      .png({ quality: 80 })
      .webp({ quality: 80 })
      .toFile(thumbnailPath);

    return thumbnailPath;
  } catch (error) {
    console.warn(`Thumbnail generation failed:`, error.message);
    return null;
  }
}

module.exports = {
  processImage,
  processBatch,
  optimizeImage,
  generateThumbnailImage
};

