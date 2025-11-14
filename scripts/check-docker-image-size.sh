#!/bin/bash
# Docker image size analysis commands

analyze_image() {
    local IMAGE_NAME="$1"
    
    echo "=== Analyzing Image: $IMAGE_NAME ==="
    echo "================================================"
    
    echo -e "\n=== Image Size Summary ==="
    docker images --format "table {{.Repository}}\t{{.Tag}}\t{{.Size}}" | grep -E "^REPOSITORY|^${IMAGE_NAME%:*}"
    
    echo -e "\n=== Layer Breakdown (with sizes) ==="
    docker history "$IMAGE_NAME" --format "table {{.Size}}\t{{.CreatedBy}}" | head -20
    
    echo -e "\n=== Layers Over 1MB ==="
    docker history "$IMAGE_NAME" | grep -E "GB|MB" | grep -v " 0B"
    
    echo -e "\n=== Total Size (from inspect) ==="
    SIZE_BYTES=$(docker inspect "$IMAGE_NAME" --format='{{.Size}}' 2>/dev/null)
    if [ -n "$SIZE_BYTES" ]; then
        SIZE_GB=$(echo "scale=2; $SIZE_BYTES / 1073741824" | bc)
        echo "Size: $SIZE_BYTES bytes ($SIZE_GB GB)"
    else
        echo "Error: Could not get size for $IMAGE_NAME"
    fi
    
    echo -e "\n=== Detailed Layer Analysis ==="
    docker history "$IMAGE_NAME" --no-trunc --format "table {{.CreatedBy}}\t{{.Size}}" | grep -E "COPY|RUN" | head -10
}

# Define the three images to analyze
IMAGES=(
    "ghcr.io/bgrins/vwa_classifieds_web:latest"    # web from docker-compose.yml
    "ghcr.io/bgrins/vwa_classifieds_db:latest"     # db from docker-compose.yml
    "jykoh/classifieds:latest"                      # web from docker-compose.original.yml
)

# Analyze each image
for image in "${IMAGES[@]}"; do
    echo -e "\n\n"
    analyze_image "$image"
    echo -e "\n========================================\n"
done

# Summary comparison
echo -e "\n=== SIZE COMPARISON SUMMARY ==="
echo "================================================"
docker images --format "table {{.Repository}}\t{{.Tag}}\t{{.Size}}" | grep -E "^REPOSITORY|ghcr.io/bgrins/vwa_classifieds|jykoh/classifieds"