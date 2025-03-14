# Intervention Image Cache

A simple image caching package for Laravel using Intervention Image v3.

## Installation

You can install the package via composer:

```bash
composer require marceli-to/intervention-image-cache
```

## Intervention Image v3 Compatibility

This package is built for Intervention Image v3, which has a significantly different API compared to v2. If you're upgrading from a package that used Intervention Image v2, please note these key differences:

- The v3 API uses interfaces like `ImageInterface` and `ModifierInterface`
- Image manipulation methods return a new image instance rather than modifying the original
- The driver system has changed (GD is used by default in this package)

For more details on Intervention Image v3, please refer to the [official documentation](https://image.intervention.io/v3).

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=image-cache-config
```

This will create a `config/image-cache.php` file where you can configure:

- Cache path
- Cache lifetime
- Image search paths
- Available templates
- Route configuration

## Usage

### Basic Usage

```php
use MarceliTo\InterventionImageCache\Facades\ImageCache;

// Get a cached image
$path = ImageCache::getCachedImage('large', 'image.jpg');

// Display the image in a view
<img src="{{ asset('storage/cache/images/' . basename($path)) }}" alt="Image">
```

### In a Controller

```php
use MarceliTo\InterventionImageCache\Facades\ImageCache;

class ImageController extends Controller
{
    public function show($template, $filename)
    {
        $path = ImageCache::getCachedImage($template, $filename);
        
        if (!$path) {
            abort(404);
        }
        
        return response()->file($path);
    }
}
```

### In Views

The package automatically registers the necessary routes, so you can use it directly in your views:

```html
<img src="/img/thumbnail/image.jpg" alt="Image">

<!-- With custom dimensions -->
<img src="/img/large/image.jpg/1200/800" alt="Image">

<!-- With cropping (x,y,width,height) -->
<img src="/img/crop/image.jpg/800/600/100,150,500,300" alt="Image">
```

The URL format is:
```
/img/{template}/{filename}/{maxWidth?}/{maxHeight?}/{coords?}
```

Where:
- `template`: One of the templates defined in your config (e.g., 'large', 'small', 'thumbnail', 'crop')
- `filename`: The name of the image file to process
- `maxWidth`: (Optional) Maximum width for the output image
- `maxHeight`: (Optional) Maximum height for the output image
- `coords`: (Optional) Comma-separated string in the format `x,y,width,height` for cropping

**Important notes about cropping coordinates:**
- All four values must be numeric
- x and y coordinates must be non-negative (values less than 0 will be set to 0)
- Width and height must be positive (values less than or equal to 0 will skip cropping)
- Coordinates that exceed image dimensions will be adjusted automatically
- If all coordinates are 0 (0,0,0,0), cropping will be skipped

### Custom Controller

If you prefer to use your own controller, you can disable the automatic route registration in the config file:

```php
// config/image-cache.php
'register_routes' => false,
```

Then create your own route and controller:

```php
// routes/web.php
Route::get('/img/{template}/{filename}/{maxW?}/{maxH?}/{coords?}', [ImageController::class, 'getResponse']);

// App\Http\Controllers\ImageController.php
public function getResponse($template, $filename, $maxW = null, $maxH = null, $coords = null)
{
    $params = [];
    
    if ($maxW) {
        $params['maxWidth'] = (int) $maxW;
    }
    
    if ($maxH) {
        $params['maxHeight'] = (int) $maxH;
    }
    
    if ($coords) {
        $params['coords'] = $coords;
    }
    
    $path = ImageCache::getCachedImage($template, $filename, $params);
    
    if (!$path || !file_exists($path)) {
        abort(404);
    }
    
    $mime = mime_content_type($path);
    $content = file_get_contents($path);
    
    return response($content)
        ->header('Content-Type', $mime)
        ->header('Cache-Control', 'public, max-age=31536000');
}
```

### Programmatic Usage

You can also use the package programmatically:

```php
use MarceliTo\InterventionImageCache\Facades\ImageCache;

// Get a cached image
$path = ImageCache::getCachedImage('large', 'image.jpg', [
    'maxWidth' => 1200,
    'maxHeight' => 800
]);

// Get a cached image with cropping
$path = ImageCache::getCachedImage('crop', 'image.jpg', [
    'maxWidth' => 800,
    'maxHeight' => 600,
    'coords' => '100,150,500,300'  // Format: x,y,width,height
]);
```

### Clearing the Cache

You can clear the cache using the provided Artisan command:

```bash
# Clear all cached images
php artisan image:clear-cache

# Clear cached images for a specific template
php artisan image:clear-cache large
```

Or programmatically:

```php
use MarceliTo\InterventionImageCache\Facades\ImageCache;

// Clear all cached images
ImageCache::clearCache();

// Clear cached images for a specific template
ImageCache::clearTemplateCache('large');
```

## Custom Templates

You can create your own templates by:

1. Creating a class that implements `Intervention\Image\Interfaces\ModifierInterface`
2. Registering it in the `templates` array in the config file

Example:

```php
<?php

namespace App\ImageTemplates;

use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\ModifierInterface;

class Custom implements ModifierInterface
{
    public function apply(ImageInterface $image): ImageInterface
    {
        return $image->resize(500, 500);
    }
}
```

Then in your config:

```php
'templates' => [
    // ...
    'custom' => \App\ImageTemplates\Custom::class,
],
```

### Using Cropping in Custom Templates

You can implement cropping in your custom templates by following this pattern:

```php
<?php

namespace App\ImageTemplates;

use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\ModifierInterface;

class CustomCrop implements ModifierInterface
{
    /**
     * Coordinates for cropping (x,y,width,height)
     */
    protected $coords = null;
    
    /**
     * Maximum width for resizing after crop
     */    
    protected $max_width = null;
    
    /**
     * Maximum height for resizing after crop
     */    
    protected $max_height = null;
    
    /**
     * Constructor with optional parameters
     */
    public function __construct($max_width = null, $max_height = null, $coords = null)
    {
        // Ensure max dimensions are positive
        $this->max_width = $max_width > 0 ? $max_width : null;
        $this->max_height = $max_height > 0 ? $max_height : null;
        $this->coords = $coords;
    }
    
    /**
     * Apply filter to image
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        // First apply cropping if coordinates are provided
        if ($this->coords && is_string($this->coords)) {
            $image = $this->applyCropping($image);
        }
        
        // Then resize if needed
        if ($this->max_width || $this->max_height) {
            $image = $this->applyResize($image);
        }
        
        return $image;
    }
    
    /**
     * Apply cropping based on coordinates
     * 
     * @param ImageInterface $image
     * @return ImageInterface
     */
    protected function applyCropping(ImageInterface $image): ImageInterface
    {
        // Parse coordinates (x,y,width,height)
        $coordsArray = explode(',', $this->coords);
        
        // Ensure we have all 4 coordinates and they're numeric
        if (count($coordsArray) === 4 && array_reduce($coordsArray, fn($carry, $item) => $carry && is_numeric(trim($item)), true)) {
            $x = max(0, (int) trim($coordsArray[0]));
            $y = max(0, (int) trim($coordsArray[1]));
            $width = (int) trim($coordsArray[2]);
            $height = (int) trim($coordsArray[3]);
            
            // Skip cropping if width or height is 0/negative, or if all coordinates are 0
            if ($width <= 0 || $height <= 0 || ($x === 0 && $y === 0 && $width === 0 && $height === 0)) {
                return $image;
            }
            
            // Ensure coordinates don't exceed image dimensions
            $imageWidth = $image->width();
            $imageHeight = $image->height();
            
            if ($x >= $imageWidth || $y >= $imageHeight) {
                return $image;
            }
            
            // Adjust width/height if they exceed image boundaries
            $width = min($width, $imageWidth - $x);
            $height = min($height, $imageHeight - $y);
            
            // Apply crop
            return $image->crop($width, $height, $x, $y);
        }
        
        return $image;
    }
    
    /**
     * Apply resize after cropping if needed
     * 
     * @param ImageInterface $image
     * @return ImageInterface
     */
    protected function applyResize(ImageInterface $image): ImageInterface
    {
        $width = $image->width();
        $height = $image->height();
        
        // Calculate aspect ratios if both dimensions are provided
        if ($this->max_width && $this->max_height) {
            $width_ratio = $width / $this->max_width;
            $height_ratio = $height / $this->max_height;
            
            if ($width_ratio > 1 || $height_ratio > 1) {
                $resize_ratio = max($width_ratio, $height_ratio);
                $new_width = round($width / $resize_ratio);
                $new_height = round($height / $resize_ratio);
                
                return $image->resize($new_width, $new_height);
            }
        }
        // Handle single dimension constraints
        elseif ($this->max_width && $width > $this->max_width) {
            $ratio = $width / $this->max_width;
            return $image->resize(
                $this->max_width,
                round($height / $ratio)
            );
        }
        elseif ($this->max_height && $height > $this->max_height) {
            $ratio = $height / $this->max_height;
            return $image->resize(
                round($width / $ratio),
                $this->max_height
            );
        }
        
        return $image;
    }
}
```

## Testing

The package includes a comprehensive test suite. To run the tests:

```bash
composer test
```

Or you can run PHPUnit directly:

```bash
./vendor/bin/phpunit
```

The test suite includes:
- Unit tests for template classes
- Unit tests for the ImageCache class
- Feature tests for the HTTP endpoints

## License

The MIT License (MIT).
