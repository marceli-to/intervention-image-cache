<?php

namespace MarceliTo\InterventionImageCache\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use MarceliTo\InterventionImageCache\Facades\ImageCache;

class ImageController extends Controller
{
	/**
	 * Get the image response
	 *
	 * @param string $template
	 * @param string $filename
	 * @param int|null $maxW
	 * @param int|null $maxH
	 * @param string|null $coords
	 * @return \Illuminate\Http\Response
	 */
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
}
