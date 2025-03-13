<?php

namespace MarceliTo\InterventionImageCache\Templates;

use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\ModifierInterface;

class Large implements ModifierInterface
{
	/**
	 * Maximum width for large landscape images
	 */    
	protected $max_width = 1600;    

	/**
	 * Maximum height for large portrait images
	 */    
	protected $max_height = 900;
	
	/**
	 * Constructor with optional parameters
	 */
	public function __construct($max_width = null, $max_height = null, $coords = null)
	{
		if ($max_width) {
			$this->max_width = $max_width;
		}
		
		if ($max_height) {
			$this->max_height = $max_height;
		}
	}
	
	/**
	 * Apply filter to image
	 */
	public function apply(ImageInterface $image): ImageInterface
	{
		// Get width and height
		$width  = $image->width();
		$height = $image->height();

		// Resize landscape image
		if ($width > $height && $width >= $this->max_width)
		{
			return $image->resize($this->max_width, null);
		}
		else if ($height >= $this->max_height)
		{
			return $image->resize(null, $this->max_height);
		}
		
		return $image;
	}
}
