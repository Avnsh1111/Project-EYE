#!/bin/bash

# Start Queue Worker for Background Processing
# This script starts the Laravel queue worker for processing images in the background

echo "🚀 Starting Avinash-EYE Queue Worker..."
echo ""
echo "This worker will process uploaded images in the background."
echo "Keep this running to enable instant upload feature."
echo ""
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

