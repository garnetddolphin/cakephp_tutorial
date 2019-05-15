<?php
namespace App\View\Helper;

use Cake\View\Helper;
use Cake\View\View;
// 画像を返すimage()で使用
use Cake\Http\Exception\NotFoundException;
/**
 * Image helper
 */
class ImageHelper extends Helper
{
	/**
	 * Default configuration.
	 *
	 * @var array
	 */
	protected $_defaultConfig = [];

	// return path
	function Path($file){
		$path = WWW_ROOT . "Applications/XAMPP/htdocs/cms/upload_img/" . $file;
		return $path;
	}

	// 画像を返す
	function image($file){
		$image = ROOT . "Applications/XAMPP/htdocs/cms/upload_img/" . $file;
		if(!file_exists($image)){
			throw new NotFoundException();
		}
		return new CakeResponse(array('type' => 'image/png', 'body' => readFile($image)));
	}
}
