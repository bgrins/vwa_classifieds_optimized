#!/bin/bash
# Prepare myapp directory for Docker build

SCRIPT_START=$(date +%s)
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Preparing myapp directory for build..."

# Backup existing myapp if it exists
if [ -d "myapp" ]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Backing up existing myapp directory to myapp.backup..."
    rm -rf myapp.backup
    mv myapp myapp.backup
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Backup complete"
fi

# Start the upstream container
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Starting upstream container..."
CONTAINER_START=$(date +%s)
docker run -d --rm --name classifieds jykoh/classifieds:latest
CONTAINER_END=$(date +%s)
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Container started ($(($CONTAINER_END - $CONTAINER_START))s)"

# Wait for container to be ready
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Waiting for container to be ready..."
sleep 5

# Copy files out of container
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Copying files out of container (very slow, ~70GB)..."
COPY_START=$(date +%s)
docker cp classifieds:/usr/src/myapp ./myapp
COPY_END=$(date +%s)
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Copy complete ($(($COPY_END - $COPY_START))s)"

# Stop and remove the container
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Stopping container..."
docker stop classifieds
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Container stopped and removed"

# Create oc-temp if it doesn't exist
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Creating oc-temp directory..."
mkdir -p myapp/oc-temp

# Set permissions (will be preserved by Docker COPY)
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Setting permissions..."
chmod 777 myapp/oc-content
chmod 777 myapp/oc-content/uploads
chmod 777 myapp/oc-content/downloads
chmod 777 myapp/oc-temp

# Empty log files instead of removing them
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Emptying log files..."
echo "" > myapp/oc-content/explain_queries.log
echo "" > myapp/oc-content/queries.log

# Check the size
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Checking uploads directory size..."
du -sh myapp/oc-content/uploads

# Count images
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Counting images..."
echo "PNG files: $(find myapp/oc-content/uploads \( -name "*.png" \) -type f | wc -l)"
echo "JPEG files: $(find myapp/oc-content/uploads \( -name "*.jpg" -o -name "*.jpeg" \) -type f | wc -l)"

SCRIPT_END=$(date +%s)
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Preparation complete! Total time: $(($SCRIPT_END - $SCRIPT_START))s"
echo "Ready to run conversion script."