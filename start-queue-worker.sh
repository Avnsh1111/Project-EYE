#!/bin/bash

# Start Queue Worker for Background Processing
# This script starts the Laravel queue worker for processing images in the background

echo "🚀 Starting Avinash-EYE Queue Worker..."
echo ""
echo "This worker processes all images in the background:"
echo "  • Single image uploads → Individual processing"
echo "  • Batch uploads (2+ images) → Node.js parallel processing"
echo ""
echo "Node.js service handles parallelization for batch uploads,"
echo "but jobs are still dispatched via queue for background processing."
echo ""
echo "Keep this running to process all uploaded images."
echo "Press Ctrl+C to stop"
echo ""

docker-compose exec laravel-app php artisan queue:work \
  --queue=image-processing \
  --tries=3 \
  --timeout=300 \
  --sleep=3 \
  --verbose

# If docker-compose is not available, try docker exec
if [ $? -ne 0 ]; then
    echo "Trying with docker exec..."
    docker exec -it avinash-eye-laravel-app php artisan queue:work \
      --queue=image-processing \
      --tries=3 \
      --timeout=300 \
      --sleep=3 \
      --verbose
fi

