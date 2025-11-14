#!/bin/bash

# Build and push multi-platform images for linux/amd64 and linux/arm64
# Make sure you're logged into ghcr.io first: docker login ghcr.io

echo "Building multi-platform images..."

# Build web container for multiple platforms
echo "Building web container..."
docker buildx build \
  --platform linux/amd64,linux/arm64 \
  -t ghcr.io/bgrins/vwa_classifieds_web:latest \
  -t ghcr.io/bgrins/vwa_classifieds_web:1 \
  --push \
  .

# Build db container for multiple platforms
echo "Building database container..."
docker buildx build \
  --platform linux/amd64,linux/arm64 \
  -t ghcr.io/bgrins/vwa_classifieds_db:latest \
  -t ghcr.io/bgrins/vwa_classifieds_db:1 \
  --push \
  ./mysql-baked

echo "Multi-platform build complete!"