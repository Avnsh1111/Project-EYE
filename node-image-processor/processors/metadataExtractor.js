/**
 * Metadata Extraction Module
 * Fast EXIF and image metadata extraction
 */

const exifr = require('exifr');
const sharp = require('sharp');
const fs = require('fs').promises;
const path = require('path');

/**
 * Extract comprehensive metadata from image
 */
async function extractMetadata(imagePath) {
  try {
    const stats = await fs.stat(imagePath);
    const image = sharp(imagePath);
    const metadata = await image.metadata();
    
    // Extract EXIF data
    const exifData = await exifr.parse(imagePath, {
      pick: [
        'Make', 'Model', 'DateTimeOriginal', 'GPSLatitude', 'GPSLongitude',
        'ExposureTime', 'FNumber', 'ISO', 'FocalLength', 'LensModel',
        'Orientation', 'ColorSpace', 'WhiteBalance'
      ]
    });

    // Calculate file size
    const fileSize = stats.size;
    const fileSizeMB = (fileSize / (1024 * 1024)).toFixed(2);

    // Extract dimensions
    const width = metadata.width || null;
    const height = metadata.height || null;
    const format = metadata.format || null;

    // Extract date taken or use file modification time
    let dateTaken = null;
    if (exifData?.DateTimeOriginal) {
      dateTaken = new Date(exifData.DateTimeOriginal).toISOString();
    } else if (metadata.exif?.DateTimeOriginal) {
      dateTaken = new Date(metadata.exif.DateTimeOriginal).toISOString();
    } else {
      dateTaken = stats.mtime.toISOString();
    }

    // GPS coordinates
    const gps = {};
    if (exifData?.GPSLatitude && exifData?.GPSLongitude) {
      gps.latitude = exifData.GPSLatitude;
      gps.longitude = exifData.GPSLongitude;
    }

    // Camera information
    const camera = {};
    if (exifData?.Make) camera.make = exifData.Make;
    if (exifData?.Model) camera.model = exifData.Model;
    if (exifData?.LensModel) camera.lens = exifData.LensModel;

    // Exposure settings
    const exposure = {};
    if (exifData?.ExposureTime) exposure.exposure_time = exifData.ExposureTime;
    if (exifData?.FNumber) exposure.f_number = exifData.FNumber;
    if (exifData?.ISO) exposure.iso = exifData.ISO;
    if (exifData?.FocalLength) exposure.focal_length = exifData.FocalLength;

    return {
      filename: path.basename(imagePath),
      file_size: fileSize,
      file_size_mb: parseFloat(fileSizeMB),
      width: width,
      height: height,
      format: format,
      date_taken: dateTaken,
      gps: Object.keys(gps).length > 0 ? gps : null,
      camera: Object.keys(camera).length > 0 ? camera : null,
      exposure: Object.keys(exposure).length > 0 ? exposure : null,
      orientation: exifData?.Orientation || metadata.orientation || null,
      color_space: exifData?.ColorSpace || metadata.space || null,
      white_balance: exifData?.WhiteBalance || null,
      extracted_at: new Date().toISOString()
    };
  } catch (error) {
    console.error(`Error extracting metadata from ${imagePath}:`, error);
    throw error;
  }
}

module.exports = {
  extractMetadata
};

