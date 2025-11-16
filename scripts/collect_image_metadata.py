#!/usr/bin/env python3
import os
import csv
import sys
from pathlib import Path

def build_original_size_map(reference_dir):
    """Collect all PNG and JPEG file sizes from reference directory."""
    print(f"Collecting original PNG and JPEG file sizes from {reference_dir}...")

    size_map = {}

    # Find all PNG files
    png_files = list(reference_dir.rglob('*.png'))
    for png_path in png_files:
        relative_path = png_path.relative_to(reference_dir)
        size_map[str(relative_path)] = os.path.getsize(png_path)

    # Find all JPEG files
    for pattern in ['*.jpg', '*.jpeg']:
        jpeg_files = list(reference_dir.rglob(pattern))
        for jpeg_path in jpeg_files:
            relative_path = jpeg_path.relative_to(reference_dir)
            size_map[str(relative_path)] = os.path.getsize(jpeg_path)

    print(f"Found {len(size_map):,} image files in reference directory")
    return size_map

def main():
    if len(sys.argv) != 3:
        print("Usage: ./collect_image_metadata.py <input_directory> <reference_directory>")
        print("Example: ./collect_image_metadata.py myapp_q40/oc-content/uploads myapp_upstream/oc-content/uploads")
        sys.exit(1)

    input_dir = Path(sys.argv[1])
    reference_dir = Path(sys.argv[2])

    if not input_dir.exists():
        print(f"Directory {input_dir} not found!")
        sys.exit(1)

    if not reference_dir.exists():
        print(f"Reference directory {reference_dir} not found!")
        sys.exit(1)

    # Build map of original file sizes
    original_size_map = build_original_size_map(reference_dir)

    print(f"Scanning for AVIF files in {input_dir}...")

    # Find all AVIF files
    avif_files = list(input_dir.rglob('*.avif'))

    total_files = len(avif_files)
    print(f"Found {total_files:,} AVIF files. Processing...")

    # Determine output filename based on compressed directory name
    # Get the top-level directory name (e.g., myapp_q40 from myapp_q40/oc-content/uploads)
    compressed_dir_name = input_dir.parts[0] if len(input_dir.parts) > 0 else input_dir.name

    # Create misc directory if it doesn't exist
    misc_dir = Path('misc')
    misc_dir.mkdir(exist_ok=True)

    output_file = misc_dir / f'image_metadata_{compressed_dir_name}.csv'

    with open(output_file, 'w', newline='') as csvfile:
        fieldnames = ['original_path', 'original_size_bytes',
                      'avif_size_bytes', 'avif_compression_ratio']
        writer = csv.DictWriter(csvfile, fieldnames=fieldnames)
        writer.writeheader()

        processed = 0
        skipped = 0

        for avif_path in avif_files:
            # Get the base filename without extension
            base_name = avif_path.stem

            # Construct the relative path for the original image
            # Remove input_dir prefix to get relative path
            relative_avif = avif_path.relative_to(input_dir)

            # Try to find the original file (could be PNG or JPEG)
            original_size = None
            original_ext = None
            for ext in ['.png', '.jpg', '.jpeg']:
                relative_original = str(relative_avif.parent / f"{base_name}{ext}")
                if relative_original in original_size_map:
                    original_size = original_size_map[relative_original]
                    original_ext = ext
                    break

            if original_size is None:
                skipped += 1
                continue

            # Get AVIF size from local file
            avif_size = os.path.getsize(avif_path)

            # Create row for CSV
            row = {
                'original_path': f"oc-content/uploads/{str(relative_avif.parent / f'{base_name}{original_ext}')}",
                'original_size_bytes': original_size,
                'avif_size_bytes': avif_size,
                'avif_compression_ratio': round(original_size / avif_size, 2) if avif_size > 0 else None
            }

            writer.writerow(row)

            processed += 1
            if processed % 1000 == 0:
                print(f"Processed {processed:,}/{total_files:,} files ({processed/total_files*100:.1f}%), skipped: {skipped}")

    print(f"\nCompleted! Written {processed:,} rows to {output_file}")
    if skipped > 0:
        print(f"Skipped {skipped:,} files (original PNG not found in container)")

if __name__ == "__main__":
    main()