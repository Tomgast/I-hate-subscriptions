#!/bin/bash

# Build script for Plesk deployment
echo "Starting build process..."

# Install dependencies
npm ci

# Build the Next.js app
npm run build

# Check if build was successful
if [ -d ".next" ]; then
    echo "Next.js build successful - ready for Node.js deployment on Plesk"
    echo "Make sure Plesk is configured to run: npm start"
else
    echo "Build failed - no .next directory found"
    exit 1
fi

echo "Build complete!"
