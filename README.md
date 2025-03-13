# Intervention Image Cache

A simple image caching package for Laravel using Intervention Image v3.

## Installation

You can install the package via composer:

```bash
composer require marceli-to/intervention-image-cache
```

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

Where `coords` is a comma-separated string in the format `x,y,width,height`.

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
    protected $coords = null;
    
    public function __construct($max_width = null, $max_height = null, $coords = null)
    {
        $this->max_width = $max_width;
        $this->max_height = $max_height;
        $this->coords = $coords;
    }
    
    public function apply(ImageInterface $image): ImageInterface
    {
        // Apply cropping if coordinates are provided
        if ($this->coords) {
            // Parse coordinates (x,y,width,height)
            $coordsArray = explode(',', $this->coords);
            
            // Ensure we have all 4 coordinates
            if (count($coordsArray) === 4) {
                $x = (int) trim($coordsArray[0]);
                $y = (int) trim($coordsArray[1]);
                $width = (int) trim($coordsArray[2]);
                $height = (int) trim($coordsArray[3]);
                
                // Apply crop
                $image = $image->crop($width, $height, $x, $y);
            }
        }
        
        // Apply other transformations...
        
        return $image;
    }
}
```

## License

The MIT License (MIT).
