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
	 * Coordinates for cropping (x,y,width,height)
	 */
	protected $coords = null;
	
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
		
		if ($coords) {
			$this->coords = $coords;
		}
	}
	
	/**
	 * Apply filter to image
	 */
	public function apply(ImageInterface $image): ImageInterface
	{
		// Apply cropping if coordinates are provided
		if ($this->coords) {
			$image = $this->applyCropping($image);
		}
		
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
		
		// Ensure we have all 4 coordinates
		if (count($coordsArray) === 4) {
			$x = (int) trim($coordsArray[0]);
			$y = (int) trim($coordsArray[1]);
			$width = (int) trim($coordsArray[2]);
			$height = (int) trim($coordsArray[3]);
			
			// Apply crop
			return $image->crop($width, $height, $x, $y);
		}
		
		return $image;
	}
}
