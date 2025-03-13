<?php

namespace MarceliTo\InterventionImageCache\Templates;

use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\ModifierInterface;

class Small implements ModifierInterface
{
	/**
	 * Maximum width for small landscape images
	 */    
	protected $max_width = 800;    

	/**
	 * Maximum height for small portrait images
	 */    
	protected $max_height = 450;
	
	/**
	 * Constructor with optional parameters
	 */
	public function __construct($max_width = null, $max_height = null)
	{
		// Ensure max dimensions are positive
		$this->max_width = $max_width > 0 ? $max_width : 800;
		$this->max_height = $max_height > 0 ? $max_height : 450;
	}
	
	/**
	 * Apply filter to image
	 */
	public function apply(ImageInterface $image): ImageInterface
	{
		// Get width and height
		$width  = $image->width();
		$height = $image->height();

		// Calculate aspect ratios
		$width_ratio = $width / $this->max_width;
		$height_ratio = $height / $this->max_height;

		// Determine which dimension needs to be constrained
		if ($width_ratio > 1 || $height_ratio > 1) {
			$resize_ratio = max($width_ratio, $height_ratio);
			$new_width = round($width / $resize_ratio);
			$new_height = round($height / $resize_ratio);
			
			return $image->resize($new_width, $new_height);
		}
		
		return $image;
	}
}
