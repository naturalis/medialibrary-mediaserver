<?php

namespace nl\naturalis\medialib\server\controller;

use \Exception;
use nl\naturalis\medialib\server\AbstractController;
use nl\naturalis\medialib\util\context\Context;
use nl\naturalis\medialib\util\FileUtil;
use Monolog\Logger;


/**
 * The FileController is responsible for serving media based on their
 * registration number, which, by design, is equal to their file name
 * minus the file extension.
 *
 * @author ayco_holleman
 */
class FileController extends AbstractController {
	const REGNO_PARAM = 'id';
	const FORMAT_PARAM = 'format';
	const FORMAT_SMALL = 'small';
	const FORMAT_MEDIUM = 'medium';
	const FORMAT_LARGE = 'large';
	const FORMAT_MASTER = 'master';

	public function isSingleActionController()
	{
		return true;
	}


	public function indexAction()
	{
		$regno = $this->_dispatcher->getParam(self::REGNO_PARAM);
		if($regno === null) {
			throw new Exception("Missing parameter: " . self::REGNO_PARAM);
		}
		$format = $this->_dispatcher->getParam(self::FORMAT_PARAM, self::FORMAT_MEDIUM);

		$media = $this->_dao->getMedia($regno);
		if($media === false) {
			throw new Exception(sprintf('No media found for %s "%s"', self::REGNO_PARAM, $regno));
		}
		if((int) $media->www_ok === 0) {
			throw new Exception('Requested resource not ready to be served yet');
		}

		$path = $this->_getLocationOnServer($media, $format);
		if(!is_file($path)) {
			$this->_logger->addError($_SERVER['REQUEST_URI'] . " -  resource not found at expected location: $path");
			header('HTTP/1.0 404 Not Found');
			exit();
		}

		$this->_logger->addDebug("Serving \"$path\"");
		if ($format == self::FORMAT_MASTER) {
			$this->_serveDownload($path);
		}
		elseif($this->_isImage($path)) {
			$this->_serveImage($path);
		}
		else {
			$this->_serveFile($path);
		}
	}


	private function _serveImage($path)
	{
		$img = @imagecreatefromjpeg($path);
		if($img === false) {
			$errInfo = error_get_last();
			throw new Exception($errInfo['message']);
		}
		header('Content-Type:image/jpeg');
		imagejpeg($img);
		exit();
	}


	private function _serveFile($path)
	{
		$contentType = $this->_getContentType($path);
		if ($contentType == 'video/mp4') {
			$this->_serveMp4($path);
		}
		header('Content-Type: ' . $contentType);
		header('Content-Length: ' . filesize($path));
		if(@readfile($path) === false) {
			$errInfo = error_get_last();
			throw new Exception($errInfo['message']);
		}
		exit();
	}

	private function _serveDownload($path)
	{
		header("X-Sendfile: $path");
		header("Content-type: application/octet-stream");
		header('Content-Disposition: attachment; filename="' . basename($path) . '"');
		exit();
	}


	private function _getLocationOnServer($media, $format)
	{
		if($this->_isImage($media->www_file)) {
			if ($format == self::FORMAT_MASTER ) {
					$location = "{$media->master_file}";
			}else {
					$location = "{$media->www_dir}/{$format}/{$media->www_file}";
			}
		}
		else {
			$location = "{$media->www_dir}/{$media->www_file}";
		}
		return $location;
	}


	private function _isImage($path)
	{
		$ext = FileUtil::getExtension($path, true);
		return $ext === 'jpg' || $ext === 'jpeg';
	}


	private function _getContentType($fileName)
	{
		$ext = FileUtil::getExtension($fileName, true);
		switch ($ext) {
			case 'jpg' :
			case 'jpeg' :
				return 'image/jpeg';
			case 'pdf' :
				return 'application/pdf';
			case 'mp4' :
				return 'video/mp4';
			case 'mp3' :
				return 'audio/mpeg';
				/////////////////////////////////////////
			// expand this list as the need arises //
			/////////////////////////////////////////
		}
		throw new Exception("Error serving $fileName. Unknown file type: $ext");
	}

	/**
	 * Adapted from http://www.thomthom.net/blog/2007/09/php-resumable-download-server/
	 * 
	 * @param string $path
	 */
	private function _serveMp4 ($path) {
		
		// Only use for mp4, as this is causing problems in Safari
		if ($this->_getContentType($path) != 'video/mp4') {
			return false;
		}
		
		$fp = @fopen($path, 'rb');
		
		if(!$fp) {
			$errInfo = error_get_last();
			throw new Exception($errInfo['message']);
		}

		// Original method starts here
		$size   = filesize($path); // File size
		$length = $size;           // Content length
		$start  = 0;               // Start byte
		$end    = $size - 1;       // End byte
		
		header('Content-type: video/mp4');
		header("Accept-Ranges: 0-$length");
		
		if (isset($_SERVER['HTTP_RANGE'])) {
		
		    $c_start = $start;
		    $c_end   = $end;
		
		    list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
		    if (strpos($range, ',') !== false) {
		        header('HTTP/1.1 416 Requested Range Not Satisfiable');
		        header("Content-Range: bytes $start-$end/$size");
		        exit;
		    }
		    if ($range == '-') {
		        $c_start = $size - substr($range, 1);
		    }else{
		        $range  = explode('-', $range);
		        $c_start = $range[0];
		        $c_end   = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $size;
		    }
		    $c_end = ($c_end > $end) ? $end : $c_end;
		    if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size) {
		        header('HTTP/1.1 416 Requested Range Not Satisfiable');
		        header("Content-Range: bytes $start-$end/$size");
		        exit;
		    }
		    $start  = $c_start;
		    $end    = $c_end;
		    $length = $end - $start + 1;
		    fseek($fp, $start);
		    header('HTTP/1.1 206 Partial Content');
		}
		
		header("Content-Range: bytes $start-$end/$size");
		header("Content-Length: ".$length);
		
		$buffer = 1024 * 8;
		while(!feof($fp) && ($p = ftell($fp)) <= $end) {
		
		    if ($p + $buffer > $end) {
		        $buffer = $end - $p + 1;
		    }
		    set_time_limit(0);
		    echo fread($fp, $buffer);
		    flush();
		}
		
		fclose($fp);
		exit();
	}

}
