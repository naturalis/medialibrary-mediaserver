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
			$this->_serveDownload($path)
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



}
