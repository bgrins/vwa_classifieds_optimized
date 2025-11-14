#!/bin/bash

# Usage: ./convert-avifenc.sh [quality] [input_dir] [parallel_jobs]
# Default: quality=30, input_dir=myapp, parallel_jobs=auto (cores-2)

QUALITY=${1:-30}
INPUT_DIR=${2:-myapp}
NUM_CORES=$(sysctl -n hw.ncpu 2>/dev/null || nproc 2>/dev/null || echo 4)
PARALLEL_JOBS=${3:-$((NUM_CORES - 2))}

# Ensure at least 1 job
if [ $PARALLEL_JOBS -lt 1 ]; then
    PARALLEL_JOBS=1
fi

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Converting images to AVIF with avifenc (yuv 420 q $QUALITY s 0)..."
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Input directory: $INPUT_DIR"
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Parallel jobs: $PARALLEL_JOBS (detected $NUM_CORES cores)"

if [ ! -d "$INPUT_DIR/oc-content/uploads" ]; then
    echo "ERROR: Directory $INPUT_DIR/oc-content/uploads not found!"
    exit 1
fi

SCRIPT_START=$(date +%s)

# Function to convert a single file
convert_file() {
    local file="$1"
    local quality="$2"
    local output_avif="${file%.*}.avif"

    if [ ! -f "$output_avif" ]; then
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] Converting: $file -> $output_avif"
        avifenc -y 420 -q $quality -s 0 "$file" "$output_avif" 2>&1
        if [ $? -eq 0 ]; then
            rm "$file"
        else
            echo "[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: Failed to convert $file"
            return 1
        fi
    fi
}

# Export function and variables for xargs
export -f convert_file
export QUALITY

# Find all files and process in parallel
find ./$INPUT_DIR/oc-content/uploads -maxdepth 1 -type d | grep -E '/[0-9]+$' | while read -r dir; do
    find "$dir" -type f \( -name "*.jpg" -o -name "*.jpeg" -o -name "*.png" \)
done | xargs -P $PARALLEL_JOBS -I {} bash -c 'convert_file "$@"' _ {} "$QUALITY"

SCRIPT_END=$(date +%s)
TOTAL_TIME=$((SCRIPT_END - SCRIPT_START))
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Conversion complete! Total time: ${TOTAL_TIME}s"
