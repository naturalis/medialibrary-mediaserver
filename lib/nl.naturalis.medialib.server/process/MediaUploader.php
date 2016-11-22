<?php

namespace nl\naturalis\medialib\server\process;

use nl\naturalis\medialib\util\FileUtil;
use nl\naturalis\medialib\util\context\Context;
use nl\naturalis\medialib\util\Config;
use nl\naturalis\medialib\publisher\harvest\StagingAreaManager;
use nl\naturalis\medialib\publisher\offload\TarAreaManager;
use nl\naturalis\medialib\publisher\harvest\MediaFileIndexer;
use nl\naturalis\medialib\publisher\masters\MasterFileCreator;
use nl\naturalis\medialib\publisher\db\dao\BaseDAO;
use nl\naturalis\medialib\publisher\web\WebFileCreator;
use Monolog\Logger;

/**
 * A {@code MediaUploader} drives single media files through the
 * harvesting chain, just like the Harvester, MasterPublisher and
 * WebPublisher together do this for batches of media files.
 * Although it is called a MediaUploader, it really just drives
 * a single media file through the harvesting chain. Whether the
 * file got there through an HTTP file upload or through some
 * other mechanism is immaterial for the MediaUploader; it just
 * starts from the full path of the file.
 * 
 * 
 * @author ayco_holleman
 *
 */
class MediaUploader {
	
	/**
	 * 
	 * @var Context
	 */
	private $_context;
	
	/**
	 * 
	 * @var Logger
	 */
	private $_logger;


	/**
	 * Instantiate a new MediaUploader.
	 * 
	 * @param string $publisherIniFile The configuration file used to
	 * drive the media file through the harvesting chain. This must be
	 * a configuration file modelled after config.ini.tpl in the
	 * publisher application, NOT the configuration file of the
	 * server application!
	 * 
	 * @param string $logFile The log file to write to
	 */
	public function __construct($publisherIniFile, $logFile)
	{
		$this->_context = new Context(new Config($publisherIniFile));
		$this->_context->initializeLogging($logFile);
		$this->_logger = $this->_context->getLogger(__CLASS__);
	}


	/**
	 * Process a single media file by driving it through the harvesting chain.
	 * 
	 * @param string $path The full path to the file.
	 */
	public function upload($path)
	{
		
		$start = time();		
		$this->_context->setProperty('start', $start);
		
		$this->_logger->addInfo("Processing media file: " . basename($path));
		
		$stagingAreaManager = new StagingAreaManager($this->_context);
		$stagingAreaManager->createStagingArea();
		
		// Choose random backup group for the uploaded media file. See config.help
		// in media publisher root directory for the meaning of backup groups.
		$backupGroup = rand(0, (int) $this->_context->getConfig()->numBackupGroups - 1);
		
		$mediaFileIndexer = new MediaFileIndexer($this->_context);
		$mediaFileIndexer->setPhase1Directory($stagingAreaManager->getPhase1Directory());
		$mediaFileIndexer->setPhase2Directory($stagingAreaManager->getPhase2Directory());
		// Media entering the media library this way are always assumed
		// to be new, i.e. if a media file with the same name already
		// exists, it will not be overwritten. Instead an exception is
		// thrown. If you want to resubmit a media file, you must first
		// delete the old version.
		$mediaId = $mediaFileIndexer->index($path, $backupGroup, false);
		
		$baseDao = new BaseDAO($this->_context);
		$media = $baseDao->getMediaById($mediaId);
		
		$masterFileCreator = new MasterFileCreator($this->_context);
		$masterFileCreator->createMasterFile($media);
		
		// We must now load the media record again, because the MasterFileCreator
		// will have set the full path to the master file it created, and the
		// WebFileCreator needs this to create the low-res images. If the need
		// arises we can optimize this by having MasterFileCreator::processFile()
		// and MasterFileCreator::createMasterFile() return the full path to the
		// master file.
		$media = $baseDao->getMediaById($mediaId);
		
		$webFileCreator = new WebFileCreator($this->_context);
		$webFileCreator->createWebFile($media);
		
		$this->_logger->addInfo("Media file successfully processed: " . basename($path));
	}

}