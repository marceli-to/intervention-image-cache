<?php

namespace MarceliTo\InterventionImageCache;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;

class ImageCache
{
	protected $manager;
	protected $cachePath;
	protected $lifetime;

	public function __construct()
	{
		$this->manager = new ImageManager(new Driver());
		$this->cachePath = storage_path(config('image-cache.cache_path', 'app/public/cache/images'));
		$this->lifetime = config('image-cache.lifetime', 43200); // Default to 30 days if not set
		
		// Ensure cache directory exists
		if (!File::exists($this->cachePath)) {
			File::makeDirectory($this->cachePath, 0755, true);
		}
	}

	/**
	 * Get or create a cached image
	 *
	 * @param string $template
	 * @param string $filename
	 * @param array $params
	 * @return string
	 */
	public function getCachedImage($template, $filename, $params = [])
	{
		$cacheKey = $this->generateCacheKey($template, $filename, $params);
		$cachedPath = $this->cachePath . '/' . $cacheKey;
		
		// Check if cached file exists and is not expired
		if (File::exists($cachedPath) && (time() - File::lastModified($cachedPath) < $this->lifetime * 60)) {
			return $cachedPath;
		}
		
		// Find the original image
		$originalPath = $this->findOriginalImage($filename);
		
		if (!$originalPath) {
			return null;
		}
		
		// Apply template
		$image = $this->applyTemplate($template, $originalPath, $params);
		
		// Save to cache
		$image->save($cachedPath);
		
		return $cachedPath;
	}
	
	/**
	 * Apply a template to an image
	 *
	 * @param string $template
	 * @param string $path
	 * @param array $params
	 * @return ImageInterface
	 */
	protected function applyTemplate($template, $path, $params = [])
	{
		$image = $this->manager->read($path);
		
		$templateClass = config("image-cache.templates.{$template}");
		
		if (class_exists($templateClass)) {
			$filter = new $templateClass(
				$params['maxWidth'] ?? null,
				$params['maxHeight'] ?? null,
				$params['coords'] ?? null
			);
			
			return $filter->apply($image);
		}
		
		return $image;
	}
	
	/**
	 * Find the original image in the configured paths
	 *
	 * @param string $filename
	 * @return string|null
	 */
	protected function findOriginalImage($filename)
	{
		$paths = config('image-cache.paths', []);
		
		foreach ($paths as $path) {
			$filePath = $path . '/' . $filename;
			if (File::exists($filePath)) {
				return $filePath;
			}
		}
		
		return null;
	}
	
	/**
	 * Generate a unique cache key for the image
	 *
	 * @param string $template
	 * @param string $filename
	 * @param array $params
	 * @return string
	 */
	protected function generateCacheKey($template, $filename, $params = [])
	{
		$key = $template . '_' . $filename;
		
		if (!empty($params)) {
			$key .= '_' . md5(serialize($params));
		}
		
		return $key;
	}

	/**
	 * Clear all cached images
	 *
	 * @return bool
	 */
	public function clearCache()
	{
		if (File::exists($this->cachePath)) {
			$files = File::allFiles($this->cachePath);
			foreach ($files as $file) {
				File::delete($file->getPathname());
			}
			return true;
		}
		return false;
	}

	/**
	 * Clear cached images for a specific template
	 *
	 * @param string $template
	 * @return bool
	 */
	public function clearTemplateCache($template)
	{
		if (File::exists($this->cachePath)) {
			$files = File::allFiles($this->cachePath);
			foreach ($files as $file) {
				$filename = $file->getFilename();
				if (strpos($filename, $template . '_') === 0) {
					File::delete($file->getPathname());
				}
			}
			return true;
		}
		return false;
	}
}
