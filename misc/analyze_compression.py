#!/usr/bin/env python3
import csv
import sys
import os
from collections import defaultdict
import statistics

def format_bytes(size):
    """Convert bytes to human readable format"""
    for unit in ['B', 'KB', 'MB', 'GB']:
        if size < 1024.0:
            return f"{size:.2f} {unit}"
        size /= 1024.0
    return f"{size:.2f} TB"

def load_data(csv_file):
    """Load and parse the CSV data"""
    data = []
    with open(csv_file, 'r') as f:
        reader = csv.DictReader(f)
        for row in reader:
            # Convert numeric fields
            row['original_size_bytes'] = int(row['original_size_bytes'])
            row['avif_size_bytes'] = int(row['avif_size_bytes']) if row['avif_size_bytes'] else None
            row['avif_compression_ratio'] = float(row['avif_compression_ratio']) if row['avif_compression_ratio'] else None
            data.append(row)
    return data

def print_summary_stats(data):
    """Print overall summary statistics"""
    print("=== COMPRESSION SUMMARY ===\n")
    
    total_original = sum(row['original_size_bytes'] for row in data)
    total_avif = sum(row['avif_size_bytes'] for row in data if row['avif_size_bytes'])
    
    avif_count = sum(1 for row in data if row['avif_size_bytes'])
    
    print(f"Total PNG files analyzed: {len(data):,}")
    print(f"Files with AVIF: {avif_count:,} ({avif_count/len(data)*100:.1f}%)")
    print()
    
    print(f"Total original size: {format_bytes(total_original)}")
    if avif_count > 0:
        print(f"Total AVIF size: {format_bytes(total_avif)}")
        print(f"AVIF space saved: {format_bytes(total_original - total_avif)} ({(total_original - total_avif)/total_original*100:.1f}%)")
    print()

def print_compression_stats(data):
    """Print compression ratio statistics"""
    print("=== COMPRESSION RATIO STATISTICS ===\n")
    
    avif_ratios = [row['avif_compression_ratio'] for row in data if row['avif_compression_ratio']]
    
    if avif_ratios:
        print("AVIF Compression Ratios:")
        print(f"  Mean: {statistics.mean(avif_ratios):.2f}x")
        print(f"  Median: {statistics.median(avif_ratios):.2f}x")
        print(f"  Min: {min(avif_ratios):.2f}x")
        print(f"  Max: {max(avif_ratios):.2f}x")
        print(f"  Std Dev: {statistics.stdev(avif_ratios):.2f}")
        print()

def print_size_distribution(data):
    """Print file size distribution"""
    print("=== ORIGINAL FILE SIZE DISTRIBUTION ===\n")
    
    size_buckets = defaultdict(int)
    size_ranges = [
        (0, 10 * 1024, "0-10 KB"),
        (10 * 1024, 50 * 1024, "10-50 KB"),
        (50 * 1024, 100 * 1024, "50-100 KB"),
        (100 * 1024, 500 * 1024, "100-500 KB"),
        (500 * 1024, 1024 * 1024, "500KB-1MB"),
        (1024 * 1024, 5 * 1024 * 1024, "1-5 MB"),
        (5 * 1024 * 1024, float('inf'), ">5 MB")
    ]
    
    for row in data:
        size = row['original_size_bytes']
        for min_size, max_size, label in size_ranges:
            if min_size <= size < max_size:
                size_buckets[label] += 1
                break
    
    for _, _, label in size_ranges:
        count = size_buckets[label]
        if count > 0:
            print(f"{label:>12}: {count:8,} files ({count/len(data)*100:5.1f}%)")
    print()

def print_compressed_size_distributions(data):
    """Print file size distribution for compressed formats"""
    formats = [
        ('AVIF', 'avif_size_bytes')
    ]
    
    size_ranges = [
        (0, 10 * 1024, "0-10 KB"),
        (10 * 1024, 50 * 1024, "10-50 KB"),
        (50 * 1024, 100 * 1024, "50-100 KB"),
        (100 * 1024, 500 * 1024, "100-500 KB"),
        (500 * 1024, 1024 * 1024, "500KB-1MB"),
        (1024 * 1024, 5 * 1024 * 1024, "1-5 MB"),
        (5 * 1024 * 1024, float('inf'), ">5 MB")
    ]
    
    for format_name, size_field in formats:
        print(f"=== {format_name} FILE SIZE DISTRIBUTION ===\n")
        
        size_buckets = defaultdict(int)
        total_files = 0
        
        for row in data:
            size = row.get(size_field)
            if size:
                total_files += 1
                for min_size, max_size, label in size_ranges:
                    if min_size <= size < max_size:
                        size_buckets[label] += 1
                        break
        
        if total_files > 0:
            for _, _, label in size_ranges:
                count = size_buckets[label]
                if count > 0:
                    print(f"{label:>12}: {count:8,} files ({count/total_files*100:5.1f}%)")
        else:
            print(f"No {format_name} files found")
        print()

def print_top_savings(data, n=10):
    """Print files with top absolute space savings"""
    print(f"=== BEST {n} FILES BY ABSOLUTE SPACE SAVINGS ===\n")
    
    savings = []
    for row in data:
        if row['avif_size_bytes']:
            avif_saved = row['original_size_bytes'] - row['avif_size_bytes']
            savings.append(('AVIF', row['original_path'], row['original_size_bytes'], avif_saved, row['avif_compression_ratio']))
    
    savings.sort(key=lambda x: x[3], reverse=True)
    
    for format_type, path, orig_size, saved, ratio in savings[:n]:
        filename = os.path.basename(path)
        print(f"{format_type}: {filename} - {format_bytes(orig_size)} → saved {format_bytes(saved)} ({ratio:.2f}x compression)")
    print()

def print_top_compression_ratios(data, n=10):
    """Print files with highest compression ratios"""
    print(f"=== BEST {n} FILES BY COMPRESSION RATIO ===\n")
    
    ratios = []
    for row in data:
        if row['avif_compression_ratio']:
            ratios.append(('AVIF', row['original_path'], row['original_size_bytes'], 
                          row['avif_size_bytes'], row['avif_compression_ratio']))
    
    ratios.sort(key=lambda x: x[4], reverse=True)
    
    for format_type, path, orig_size, comp_size, ratio in ratios[:n]:
        filename = os.path.basename(path)
        print(f"{format_type}: {filename} - {format_bytes(orig_size)} → {format_bytes(comp_size)} ({ratio:.2f}x compression)")
    print()

def print_bottom_compression_ratios(data, n=10):
    """Print files with lowest compression ratios"""
    print(f"=== WORST {n} FILES BY COMPRESSION RATIO ===\n")
    
    ratios = []
    for row in data:
        if row['avif_compression_ratio']:
            ratios.append(('AVIF', row['original_path'], row['original_size_bytes'], 
                          row['avif_size_bytes'], row['avif_compression_ratio']))
    
    ratios.sort(key=lambda x: x[4])  # Sort ascending for bottom ratios
    
    for format_type, path, orig_size, comp_size, ratio in ratios[:n]:
        filename = os.path.basename(path)
        print(f"{format_type}: {filename} - {format_bytes(orig_size)} → {format_bytes(comp_size)} ({ratio:.2f}x compression)")
    print()

def print_histogram(data, bins=20):
    """Print ASCII histogram of compression ratios"""
    print("=== COMPRESSION RATIO HISTOGRAM ===\n")
    
    avif_ratios = [row['avif_compression_ratio'] for row in data if row['avif_compression_ratio']]
    
    if not avif_ratios:
        print("No AVIF data to display")
        return
    
    min_ratio = min(avif_ratios)
    max_ratio = max(avif_ratios)
    bin_width = (max_ratio - min_ratio) / bins
    
    histogram = defaultdict(int)
    
    for ratio in avif_ratios:
        bin_idx = int((ratio - min_ratio) / bin_width)
        bin_idx = min(bin_idx, bins - 1)
        histogram[bin_idx] += 1
    
    max_count = max(histogram.values())
    bar_width = 40
    
    print("AVIF Distribution:")
    
    for i in range(bins):
        bin_start = min_ratio + i * bin_width
        bin_end = bin_start + bin_width
        count = histogram[i]
        bar_length = int(count / max_count * bar_width) if max_count > 0 else 0
        bar = '█' * bar_length
        
        # Format the range label
        if i == bins - 1 and bin_end < max_ratio:
            # Last bin might contain values up to max_ratio
            print(f"{bin_start:4.1f}-{max_ratio:4.1f}x: {bar:<{bar_width}} {count:6,}")
        else:
            print(f"{bin_start:4.1f}-{bin_end:4.1f}x: {bar:<{bar_width}} {count:6,}")
    
    print(f"Total files: {len(avif_ratios):,}")
    print()

def main():
    csv_file = 'misc/image_metadata.csv'
    if not os.path.exists(csv_file):
        print(f"Error: File '{csv_file}' not found")
        sys.exit(1)
    
    print(f"Loading data from {csv_file}...")
    data = load_data(csv_file)
    print(f"Loaded {len(data):,} records\n")
    
    print_summary_stats(data)
    print_compression_stats(data)
    print_size_distribution(data)
    print_compressed_size_distributions(data)
    print_top_savings(data)
    print_top_compression_ratios(data)
    print_bottom_compression_ratios(data)
    print_histogram(data)

if __name__ == "__main__":
    main()