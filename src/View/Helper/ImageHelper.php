<?php
namespace App\View\Helper;

use Cake\View\Helper;
use Cake\View\View;
// 画像を返すimage()で使用
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
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

	// URL直打ちで見れない(Webrootから見えない)パスに
	// 保存された画像のフルパスを返す。
	// function Return_Path($userid , $file){
	function Return_Path($userid , $file){
		$path = "/Applications/XAMPP/htdocs/ImageFolder/" . $userid . "/". $file;
		return $path;
	}

	// 画像を<img>タグでインラインイメージとして表示するために
	// base64でエンコードして返す
	function Base64_Image($path){
    $base64_image = base64_encode(file_get_contents($path));
    return $base64_image;
  }
}
