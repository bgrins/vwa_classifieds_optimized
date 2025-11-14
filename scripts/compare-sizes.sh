#!/bin/bash

# Usage: ./compare-sizes.sh [input_dir] [quality]
# Default: input_dir=myapp, quality=30

INPUT_DIR=${1:-myapp}
QUALITY=${2:-30}

echo "================================================================"
echo "Comparing sizes: New AVIF (avifenc q$QUALITY) vs Original PNG"
echo "================================================================"
echo "Input directory: $INPUT_DIR"
echo ""

# Clean and create sample directory
SAMPLE_DIR="misc/compression_samples_avifenc_q${QUALITY}_$(basename $INPUT_DIR)"
rm -rf "$SAMPLE_DIR"
mkdir -p "$SAMPLE_DIR"

# Get a sample of converted files
SAMPLE_SIZE=200
SAMPLE_FILES=$(find $INPUT_DIR/oc-content/uploads -name "*.avif" -type f | head -$SAMPLE_SIZE)

if [ -z "$SAMPLE_FILES" ]; then
    echo "No AVIF files found yet!"
    exit 1
fi

echo "Sample size: $SAMPLE_SIZE files (only copying *_original.* files)"
echo ""
printf "%-60s %12s %12s %10s\n" "File" "AVIF (q$QUALITY)" "Original PNG" "Savings"
echo "----------------------------------------------------------------"

TOTAL_AVIF=0
TOTAL_PNG=0

for avif_file in $SAMPLE_FILES; do
    # Get AVIF file size
    AVIF_SIZE=$(stat -f%z "$avif_file" 2>/dev/null)

    # Get path relative to input dir and convert to PNG name
    REL_PATH="${avif_file#$INPUT_DIR/}"
    PNG_PATH="${REL_PATH%.avif}.png"

    # Get PNG size from container
    PNG_SIZE=$(docker exec classifieds stat -c%s "/usr/src/myapp/$PNG_PATH" 2>/dev/null)

    if [ -n "$AVIF_SIZE" ] && [ -n "$PNG_SIZE" ] && [ "$PNG_SIZE" -gt 0 ]; then
        TOTAL_AVIF=$((TOTAL_AVIF + AVIF_SIZE))
        TOTAL_PNG=$((TOTAL_PNG + PNG_SIZE))

        # Calculate compression ratio
        SAVINGS=$(awk "BEGIN {printf \"%.1f\", ((1 - ($AVIF_SIZE / $PNG_SIZE)) * 100)}")

        # Format sizes
        AVIF_KB=$(awk "BEGIN {printf \"%.1f KB\", $AVIF_SIZE / 1024}")
        PNG_KB=$(awk "BEGIN {printf \"%.1f KB\", $PNG_SIZE / 1024}")

        # Get just the filename for display
        BASENAME=$(basename "$avif_file")
        DIRNAME=$(dirname "$avif_file" | xargs basename)
        DISPLAY_NAME="$DIRNAME/$BASENAME"

        printf "%-60s %12s %12s %9s%%\n" "$DISPLAY_NAME" "$AVIF_KB" "$PNG_KB" "$SAVINGS"

        # Copy only _original.* files to samples directory
        if [[ "$BASENAME" == *"_original.avif" ]]; then
            REL_PATH_CLEAN="${avif_file#$INPUT_DIR/oc-content/uploads/}"
            DIR_PART=$(dirname "$REL_PATH_CLEAN")
            BASE_PART=$(basename "$REL_PATH_CLEAN")
            FLAT_NAME="${DIR_PART}_${BASE_PART}"
            PNG_FLAT_NAME="${DIR_PART}_${BASE_PART%.avif}.png"

            cp "$avif_file" "$SAMPLE_DIR/$FLAT_NAME"
            docker cp "classifieds:/usr/src/myapp/$PNG_PATH" "$SAMPLE_DIR/$PNG_FLAT_NAME" 2>/dev/null
        fi
    fi
done

echo "----------------------------------------------------------------"

if [ $TOTAL_PNG -gt 0 ]; then
    # Calculate total savings
    TOTAL_SAVINGS=$(awk "BEGIN {printf \"%.1f\", ((1 - ($TOTAL_AVIF / $TOTAL_PNG)) * 100)}")
    TOTAL_AVIF_MB=$(awk "BEGIN {printf \"%.2f MB\", $TOTAL_AVIF / 1024 / 1024}")
    TOTAL_PNG_MB=$(awk "BEGIN {printf \"%.2f MB\", $TOTAL_PNG / 1024 / 1024}")

    echo ""
    echo "Total for sample:"
    echo "  AVIF (q$QUALITY):  $TOTAL_AVIF_MB"
    echo "  Original PNG:      $TOTAL_PNG_MB"
    echo "  Space savings:     $TOTAL_SAVINGS%"
    echo ""
    echo "================================================================"
    echo "Sample files copied to ./$SAMPLE_DIR/ for manual review"
    echo "================================================================"
    echo "  AVIF files (avifenc -y 420 -q $QUALITY -s 0) and PNG files side-by-side"
    echo ""
    echo "File counts:"
    echo "  AVIF files: $(ls $SAMPLE_DIR/*.avif 2>/dev/null | wc -l | tr -d ' ')"
    echo "  PNG files:  $(ls $SAMPLE_DIR/*.png 2>/dev/null | wc -l | tr -d ' ')"
fi
