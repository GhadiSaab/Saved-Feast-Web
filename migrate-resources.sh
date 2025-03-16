#!/bin/bash
# Script to migrate FrontRessources to standard Laravel resources structure

echo "Starting migration of FrontRessources to standard resources directory..."

# Create directories if they don't exist
mkdir -p resources/views
mkdir -p resources/js
mkdir -p resources/sass
mkdir -p resources/images
mkdir -p public/images
mkdir -p public/fonts
mkdir -p public/css

# Copy views
echo "Copying views..."
cp -r FrontRessources/views/* resources/views/

# Copy JavaScript files
echo "Copying JavaScript files..."
cp -r FrontRessources/js/* resources/js/

# Copy SASS files
echo "Copying SASS files..."
cp -r FrontRessources/sass/* resources/sass/

# Copy images to both resources and public
echo "Copying images..."
if [ -d "FrontRessources/images" ]; then
  cp -r FrontRessources/images/* resources/images/
  cp -r FrontRessources/images/* public/images/
fi

# Copy fonts to public directory
echo "Copying fonts..."
if [ -d "FrontRessources/fonts" ]; then
  cp -r FrontRessources/fonts/* public/fonts/
fi

# Copy any CSS files directly to public
echo "Copying CSS files..."
if [ -d "FrontRessources/css" ]; then
  cp -r FrontRessources/css/* public/css/
fi

echo "Migration completed successfully!"
