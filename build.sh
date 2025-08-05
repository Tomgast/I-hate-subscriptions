#!/bin/bash

# Build script for Plesk deployment
echo "Starting build process..."

# Install dependencies
npm ci

# Build the Next.js app for static export
npm run build

# Copy static files to public directory for Plesk
if [ -d "out" ]; then
    echo "Static export successful - files ready for Plesk"
    # Plesk should serve files from the 'out' directory
else
    echo "Build failed - no output directory found"
    exit 1
fi

echo "Build complete!"
