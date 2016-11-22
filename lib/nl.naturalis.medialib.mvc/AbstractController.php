<?php

namespace nl\naturalis\medialib\server;

use nl\naturalis\medialib\server\db\dao\MediaServerDAO;
use nl\naturalis\medialib\util\context\Context;
use Monolog\Logger;

/**
 *
 * @author ayco_holleman
 *        
 */
abstract class AbstractController {
	
	/**
	 * 
	 * @var Context
	 */
	protected $_context;
	
	/**
	 *
	 * @var MediaServerDAO
	 */
	protected $_dao;
	
	/**
	 * 
	 * @var Logger
	 */
	protected $_logger;
	
	/**
	 *
	 * @var RequestDispatcher
	 */
	protected $_dispatcher;


	public function setContext(Context $context)
	{
		$this->_context = $context;
		$this->_dao = new MediaServerDAO($context);
		$this->_logger = $context->getLogger(get_called_class());
	}


	public function setRequestDispatcher(RequestDispatcher $dispatcher)
	{
		$this->_dispatcher = $dispatcher;
	}


	/**
	 * Whether or not this controller consists of just one action. Subclasses
	 * of AbstractController can override this method by returning true, and
	 * thus declare themselves to be a single action controller. For a single
	 * action controller the following applies:
	 * <ul>
	 * <li>
	 * The URL for this action is assumed NOT to have a path segment for the
	 * action; the request dispatching mechanism automatically dispatches to
	 * the one and only action in the controller. In other words, the next
	 * path segment after the controller segment is assumed to be the name of
	 * the first path parameter.
	 * </li>
	 * <li>
	 * The method implementing the one and only action is assumed to be
	 * indexAction(). The request dispatching mechanism will always call this
	 * method for single action controllers.
	 * </li>
	 * </ul>
	 * Subclasses of AbstractController can override this method by returning
	 * true, and thus declare themselves to be a single action controller.
	 * @return boolean
	 */
	public function isSingleActionController()
	{
		return false;
	}


	/**
	 * Send an error message to the requestor and log the error.
	 */
	protected function _echoError($message)
	{
		$this->_logger->addError($message);
		if(!$this->_context->getConfig()->getBoolean('logging.stdout')) {
			echo $message;
		}
	}


	/**
	 * Send an warning message to the requestor and log the warning.
	 */
	protected function _echoWarning($message)
	{
		$this->_logger->addWarning($message);
		if(!$this->_context->getConfig()->getBoolean('logging.stdout')) {
			echo $message;
		}
	}


	/**
	 * Send an informational message to the requestor and log the message.
	 */
	protected function _echoInfo($message)
	{
		$this->_logger->addInfo($message);
		if(!$this->_context->getConfig()->getBoolean('logging.stdout')) {
			echo $message;
		}
	}

}