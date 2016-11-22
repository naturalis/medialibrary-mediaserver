<?php

namespace nl\naturalis\medialib\server;

use nl\naturalis\medialib\server\db\dao\MediaServerDAO;
use nl\naturalis\medialib\util\context\Context;
use nl\naturalis\medialib\util\DateTimeUtil;
use nl\naturalis\medialib\util\logging\LoggerFactory;
use nl\naturalis\medialib\util\logging\LogConfig;
use nl\naturalis\medialib\util\Config;
use Monolog\Logger;
use \Exception;


/**
 * @author ayco_holleman
 */
class MediaServer {
	
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
	 *
	 * @var MediaServerDAO
	 */
	private $_dao;


	public function __construct()
	{
		if(!defined('APPLICATION_PATH')) {
			throw new Exception('APPLICATION_PATH not defined.');
		}
		$iniFile = APPLICATION_PATH . DIRECTORY_SEPARATOR . 'static.ini';
		$this->_context = new Context(new Config($iniFile));
		// MediaServer logger must never log to stdout, since that
		// might interfere with the header() calls when serving the
		// media. So override any setting from the ini file:
		$this->_context->getConfig()->stdout = false;
		$this->_context->initializeLogging($this->getLogFileFullPath());
		$this->_logger = $this->_context->getLogger(__CLASS__);
		$this->_dao = new MediaServerDAO($this->_context);
	}


	protected function getLogFileFullPath()
	{
		$dir = rtrim($this->_context->getConfig()->logging->directory, DIRECTORY_SEPARATOR);
		if(!is_dir($dir)) {
			throw new \Exception("Logging directory does not exist: $dir");
		}
		$ds = DIRECTORY_SEPARATOR;
		$date = date('Ymd');
		$process = substr(__CLASS__, strrpos(__CLASS__, '\\') + 1);
		return "{$dir}{$ds}{$date}.{$process}.log";
	}


	public function handleRequest()
	{
		$debug = $this->_context->getConfig()->getBoolean('debug') || (isset($_GET['debug']) && strtolower($_GET['debug']) === 'true');
		$this->_context->setProperty('debug', $debug);
		
		try {
			$path = $_SERVER['REQUEST_URI'];
			$this->_logger->addInfo('Request: ' . $path);
			$baseUrl = $this->_context->getConfig()->baseUrl;
			$dispatcher = new RequestDispatcher($path, $baseUrl);
			$controller = $dispatcher->getController();
			if($controller === null) {
				$this->showWelcomePage();
				return;
			}
			$controller->setContext($this->_context);
			$controller->setRequestDispatcher($dispatcher);
			$method = $dispatcher->getActionMethod();
			$controller->$method();
		}
		catch(Exception $e) {
			if($debug) {
				header('Content-Type:text/plain');
				echo "\n" . $e->getTraceAsString();
				echo "\n" . basename($e->getFile()) . ' (' . $e->getLine() . '): ' . $e->getMessage();
			}
			else {
				header('X-Error-Message: ' . $e->getMessage(), true, 500);
			}
			$this->_logger->addDebug("\n" . $e->getTraceAsString());
			$this->_logger->addError($_SERVER['REQUEST_URI'] . ' - ' . basename($e->getFile()) . ' (' . $e->getLine() . '): ' . $e->getMessage());
			exit();
		}
	}


	protected function showWelcomePage()
	{
		$baseUrl = trim($this->_context->getConfig()->baseUrl, '/');
		if(strlen($baseUrl) !== 0) {
			$baseUrl = '/' . $baseUrl;
		}
		$_REQUEST['BASE_URL'] = $baseUrl;
		if(isset($_POST) && is_array($_POST) && count($_POST) !== 0) {
			$_REQUEST['RESULT_SET'] = $this->_dao->searchMedia($_POST);
			$_REQUEST['RESULT_SET_SIZE'] = $this->_dao->countMedia($_POST);
			$_REQUEST['PRODUCER_ARRAY'] = explode(';', $_POST['producerArray']);
		}
		else {
			$_REQUEST['RESULT_SET'] = null;
			$_REQUEST['RESULT_SET_SIZE'] = 0;
			$_REQUEST['PRODUCER_ARRAY'] = $this->_dao->getProducers();
		}
		include APPLICATION_PATH . DIRECTORY_SEPARATOR . 'welcome.php';
	}

}