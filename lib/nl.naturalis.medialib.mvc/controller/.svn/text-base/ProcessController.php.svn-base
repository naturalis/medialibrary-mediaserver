<?php

namespace nl\naturalis\medialib\server\controller;

use nl\naturalis\medialib\server\AbstractController;
use nl\naturalis\medialib\util\context\Context;
use nl\naturalis\medialib\util\FileUtil;
use nl\naturalis\medialib\server\process\MediaUploader;
use nl\naturalis\medialib\util\Config;
use nl\naturalis\medialib\publisher\db\dao\BaseDAO;


/**
 * The UploadController is a web service that handles media file
 * uploads.
 *
 * @author ayco_holleman
 */
class ProcessController extends AbstractController {
	
	/**
	 * The name of the request param for the uploaded file
	 * @var string
	 */
	const FILE_PARAM = 'file';


	public function deleteAction()
	{
		header('Content-Type:text/plain');
		$this->_logger->addInfo('Request: ' . $this->_dispatcher->getPath());
		$regno = $this->_dispatcher->getParam('id');
		if($regno === null) {
			$this->_echoError('ERROR: Missing request parameter "id"');
			exit();
		}
		try {
			// NB This is somewhat peculiar: we use a MediaServer Context,
			// created from the MediaServer's ini file to instantiate a DAO
			// that lives in the publisher package. However the DAO only uses
			// the connection settings from the ini file, and these _must_ be
			// the same as those in the ini file used by the various publisher
			// processes (Harvester, MasterPublisher, etc.): the media server
			// serves from the same database that the publisher processes
			// populate. That's why we can take this shortcut. In the
			// uploadAction() method we DO create a separate Context, because
			// we need other settings pertaining to the publishing process as
			// well.
			$dao = new BaseDAO($this->_context);
			$media = $dao->getMediaByRegno($regno);
			if($media === false) {
				$this->_echoError("ERROR: no such id: $regno");
				exit();
			}
			FileUtil::unlink($media->source_file, true);
			FileUtil::unlink($media->master_file, true);
			FileUtil::unlink("{$media->www_dir}/{$media->www_file}", true);
			FileUtil::unlink("{$media->www_dir}/large/{$media->www_file}", true);
			FileUtil::unlink("{$media->www_dir}/medium/{$media->www_file}", true);
			FileUtil::unlink("{$media->www_dir}/small/{$media->www_file}", true);
			$dao->backup($media);
			$dao->deleteMedia($media->id);
			$this->_echoInfo('OK');
			exit();
		}
		catch(\Exception $e) {
			$this->_echoError("ERROR: {$e->getMessage()}");
			exit();
		}
	}


	public function uploadAction()
	{
		header('Content-Type:text/plain');
		$this->_logger->addInfo('Request: ' . $this->_dispatcher->getPath());
		if(!isset($_FILES) || !isset($_FILES[self::FILE_PARAM])) {
			$fileParam = self::FILE_PARAM;
			$this->_echoError("ERROR: Bad request (not a file upload, or file not uploaded using request parameter \"$fileParam\")");
			exit();
		}
		
		$fileName = $_FILES[self::FILE_PARAM]['name'];
		
		if($_FILES[self::FILE_PARAM]['error'] !== UPLOAD_ERR_OK) {
			$this->_echoError("ERROR: Error while uploading file $fileName");
			exit();
		}
		
		$tempFile = $_FILES[self::FILE_PARAM]['tmp_name'];
		$fileName = dirname($tempFile) . DIRECTORY_SEPARATOR . $fileName;
		if(!move_uploaded_file($tempFile, $fileName)) {
			$this->_echoError("ERROR: Could not move uploaded file to $fileName");
			exit();
		}
		
		$producer = $this->_dispatcher->getParam('producer');
		if($producer === null) {
			$this->_echoError('ERROR: Missing request parameter "producer"');
			exit();
		}
		
		$owner = $this->_dispatcher->getParam('owner');
		if($owner === null) {
			$this->_echoError('ERROR: Missing request parameter "owner"');
			exit();
		}
		
		$iniFile = FileUtil::createPath(APPLICATION_PATH, 'conf', 'process', "{$producer}.ini");
		if(!is_file($iniFile)) {
			$this->_echoError("ERROR: Could not find configuration file for specified producer: {$iniFile}");
			exit();
		}
		
		try {
			$config = new Config($iniFile);
			if($config->producer !== $producer) {
				$warning = "The producer specified in the request ({$producer}) does not match the producer ";
				$warning .= "in the configuration file used for this media upload ({$config->producer}). ";
				$warning .= "Configuration file: {$iniFile}. The media file will be assigned to producer {$producer}.";
				$this->_logger->addWarning($warning);
				$config->producer = $producer;
			}
			if($config->owner !== $owner) {
				$warning = "The owner specified in the request ({$owner}) does not match the owner ";
				$warning .= "in the configuration file used for this media upload ({$config->owner}). ";
				$warning .= "Configuration file: {$iniFile}. The producer for the media file will be {$owner}.";
				$this->_logger->addWarning($warning);
				$config->owner = $owner;
			}
			$publisherContext = new Context($config);
			
			$mediaUploader = new MediaUploader($iniFile, $this->_context->getLogFile());
			$mediaUploader->upload($fileName);
		}
		catch(\Exception $e) {
			$this->_echoError("ERROR: {$e->getMessage()}");
			exit();
		}
		
		$this->_echoInfo('OK');
		exit();
	}

}