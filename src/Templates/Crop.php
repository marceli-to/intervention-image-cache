<?php

namespace MarceliTo\InterventionImageCache\Templates;

use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\ModifierInterface;

class Crop implements ModifierInterface
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
        $this->max_width = $max_width;
        $this->max_height = $max_height;
        $this->coords = $coords;
    }
    
    /**
     * Apply filter to image
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        // First apply cropping if coordinates are provided
        if ($this->coords) {
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
