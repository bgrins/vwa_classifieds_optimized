#!/usr/bin/env python3
import os
import csv
from pathlib import Path

def get_file_info(filepath):
    """Get file metadata including size."""
    info = {
        'path': filepath,
        'size_bytes': os.path.getsize(filepath),
        'extension': filepath.suffix.lower()
    }
    
    return info

def main():
    base_dir = Path('osclass-v8.1.2/oc-content/uploads')
    
    if not base_dir.exists():
        print(f"Directory {base_dir} not found!")
        return
    
    print("Scanning for PNG files...")
    
    # Find all PNG files only
    image_files = []
    for ext in ['*.png', '*.PNG']:
        image_files.extend(base_dir.rglob(ext))
    
    total_files = len(image_files)
    print(f"Found {total_files:,} image files. Processing...")
    
    # Open CSV file for writing
    output_file = 'image_metadata.csv'
    with open(output_file, 'w', newline='') as csvfile:
        fieldnames = ['original_path', 'original_size_bytes',
                      'avif_size_bytes', 'avif_compression_ratio']
        writer = csv.DictWriter(csvfile, fieldnames=fieldnames)
        writer.writeheader()
        
        processed = 0
        for img_path in image_files:
            # Get the base filename without extension
            base_name = img_path.stem
            parent_dir = img_path.parent
            
            # Get info for the original image
            original_info = get_file_info(img_path)
            
            # Look for corresponding AVIF file
            avif_path = parent_dir / f"{base_name}.avif"
            
            avif_info = get_file_info(avif_path) if avif_path.exists() else None
            
            # Create row for CSV
            # Remove osclass-v8.1.2/ prefix from path
            path_str = str(img_path)
            if path_str.startswith('osclass-v8.1.2/'):
                path_str = path_str[15:]  # Remove 'osclass-v8.1.2/' prefix
            
            row = {
                'original_path': path_str,
                'original_size_bytes': original_info['size_bytes'],
                'avif_size_bytes': avif_info['size_bytes'] if avif_info else None,
                'avif_compression_ratio': round(original_info['size_bytes'] / avif_info['size_bytes'], 2) if avif_info else None
            }
            
            writer.writerow(row)
            
            processed += 1
            if processed % 1000 == 0:
                print(f"Processed {processed:,}/{total_files:,} files ({processed/total_files*100:.1f}%)")
        
    print(f"\nCompleted! Written {processed:,} rows to {output_file}")

if __name__ == "__main__":
    main()