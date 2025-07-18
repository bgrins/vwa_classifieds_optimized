#!/bin/bash
# Prepare myapp directory for Docker build

echo "Preparing myapp directory for build..."

echo "Copying files out of main image (very slow, 70GB)..."
docker cp classifieds:/usr/src/myapp ./myapp

# Create oc-temp if it doesn't exist
mkdir -p myapp/oc-temp

# Set permissions (will be preserved by Docker COPY)
chmod 777 myapp/oc-content
chmod 777 myapp/oc-content/uploads
chmod 777 myapp/oc-content/downloads
chmod 777 myapp/oc-temp

# Empty log files instead of removing them
echo "" > myapp/oc-content/explain_queries.log
echo "" > myapp/oc-content/queries.log

echo "Permissions set. Ready to build Docker image."