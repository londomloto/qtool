<?php
namespace Londo;

class Grabber {

	static $basepath = '';

	public static function config ($config = array()) {
		if (isset($config['basepath'])) {
			self::$basepath = $config['basepath'];
		}
	}
	
	public static function grab($url) {
		
	}

	public static function path($query = null) {
		$list = array();
		if ( ! empty($query)) {
			$file = self::$basepath.'/misc/path.db';
			if (file_exists($file)) {
				$handle = fopen($file, 'r');
				while( ! feof($handle)) {
					$line = fgets($handle);
					$list[] = $line;
				}
				fclose($handle);
			}
		}
		return $list;
	}

	public static function browser($enabled = true) {
		return $enabled ? ' -U "Mozilla/5.0 (Windows NT 10.0; WOW64; rv:44.0) Gecko/20100101 Firefox/44.0" ' : '';
	}

	public static function proxy($enabled = true, $index = 1) {

		if ( ! $enabled)
			return '';

		$file = self::$basepath.'/misc/proxy.db';
		$list = array();

		if (file_exists($file)) {
			$handle = fopen($file, 'r');
			while( ! feof($handle)) {
				$line = trim(fgets($handle));
				if (preg_match('/([\d.]+)\s+([\d]+)/', $line, $matches)) {
					$list[] = ' -e use_proxy='.($enabled ? 'yes' : 'no').' -e http_proxy='.$matches[1].':'.$matches[2].' ';
				}
			}
			fclose($handle);
		}
		
		if (count($list) > 0) {
			if (isset($list[$index - 1])) {
				return $list[$index - 1];
			}
			return $list[array_rand($list, 1)];
		}

		return '';
	}

	public static function crop($file, $width = 184, $height = 240) {
		
		if (file_exists($file)) {

			set_time_limit(0);

			// ini_set('memory_limit', '500MB');	
			if ($info = @getimagesize($file)) {

				$mime = $info['mime'];
				
				$orig_width  = $info[0];
				$orig_height = $info[1];

				if ($width >= $orig_width && $height >= $orig_height) {
					$width  = $orig_width;
					$height = $orig_height;
				}

				$offset_x = 0;
				$offset_y = 0;

				$orig_ratio = $orig_width / $orig_height;
				$crop_ratio = $orig_width < $orig_height ? ($width / $height) : ($height / $width);

				if ($orig_ratio < $crop_ratio) {
					$temp        = $orig_height;
					$orig_height = $orig_width / $crop_ratio;
					$offset_y    = ($temp - $orig_height) / 2;
				} else if ($orig_ratio > $crop_ratio) {
					$temp       = $orig_width;
					$orig_width = $orig_height / $crop_ratio;
					$offset_x   = ($temp - $orig_width) / 2;
				}

				$ratio_x  = $width / $orig_width;
				$ratio_y  = $height / $orig_height;

				if ($ratio_x * $orig_height < $height) {
					$crop_width  = $width;
					$crop_height = ceil($ratio_x * $orig_height);
				} else {
					$crop_width  = ceil($ratio_y * $orig_width);
					$crop_height = $height;
				}

				$target  = imagecreatetruecolor($crop_width, $crop_height);
				$source  = imagecreatefromjpeg($file);
				$quality = 100;

				imagecopyresampled(
					$target, 
					$source, 
					0, 
					0, 
					$offset_x, 
					$offset_y, 
					$crop_width, 
					$crop_height, 
					$orig_width, 
					$orig_height
				);

				ob_start();
				imagejpeg($target, null, $quality);
				$content = ob_get_contents();
				ob_end_clean();

				imagedestroy($target);
				imagedestroy($source);

				header('Pragma: public');
				header('Cache-Control: max-age=86400');
				header('Expires: '. gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));
				header('Content-Type: '.$mime);
				header('Content-Length: '.strlen($content));
				
				echo $content;
				exit();

			}

		}

	}

	public static function try_caching($etag, $lastmodified, $mime = '') {
		$etag_str = $etag;

		header("Last-Modified: $lastmodified");
		header("ETag: \"{$etag}\"");

		$if_none_match     = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? stripslashes($_SERVER['HTTP_IF_NONE_MATCH']) : FALSE;
		$if_modified_since = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? stripslashes($_SERVER['HTTP_IF_MODIFIED_SINCE']) : FALSE;

		if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== FALSE) {
			if ( ! in_array($mime, array('image/x-png'))) {
				$etag = $etag.'-gzip';
				$if_none_match = strtolower(str_replace(array(
					'"',
					'-gzip'
				) , '', $if_none_match)) . '-gzip';
			}
		}

		if ( ! $if_modified_since && ! $if_none_match) return;
		if ($if_none_match && $if_none_match != $etag && $if_none_match != '"' . $etag . '"') return;
		if ($if_modified_since && $if_modified_since != $lastmodified) return;

		header('HTTP/1.1 304 Not Modified');
		exit();
	}

}