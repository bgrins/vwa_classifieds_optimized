#!/bin/bash

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Converting images to AVIF..."

find ./myapp/oc-content/uploads -maxdepth 1 -type d | grep -E '/[0-9]+$' | while read -r dir; do
    find "$dir" -type f \( -name "*.jpg" -o -name "*.jpeg" -o -name "*.png" \)
done | while read -r file; do
    output_avif="${file%.*}.avif"
    if [ ! -f "$output_avif" ]; then
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] Converting: $file -> $output_avif"
        vips copy "$file" "$output_avif"
        rm "$file"
    else
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] Skipping: $output_avif (already exists)"
    fi
done
