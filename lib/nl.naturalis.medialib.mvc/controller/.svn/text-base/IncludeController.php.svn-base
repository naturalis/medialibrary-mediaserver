<?php

namespace nl\naturalis\medialib\server\controller;

use nl\naturalis\medialib\server\AbstractController;
use nl\naturalis\medialib\util\context\Context;
use Monolog\Logger;


/**
 * <p>
 * The IncludeController let's you execute any PHP script under the media
 * server's application directory as though it were a stand-alone script.
 * This is not trivial since the media server's dispatch mechanism works
 * by routing ALL requests to index.php (see .htaccess). This ordinarily
 * your script would never execute under the media server's base URL. If
 * you want to execute a PHP script directly, use a URL like this:
 * </p>
 * <p>
 * <code>
 * http://medialib_host/include/path/to/the/script.php
 * </code>
 * </p>
 * <p>
 * You specify the path relative to application directory (the directory
 * containing index.php).
 * </p>
 * <p>
 * You can also include path parameters in the URL, e.g.
 * <br/>
 * <code>
 * http://medialib_host/include/path/to/the/script.php/param1/value1/param2/value2
 * </code>
 * </p>
 * 
 * @author ayco_holleman
 */
class IncludeController extends AbstractController {
	
	public function isSingleActionController()
	{
		return true;
	}


	public function indexAction()
	{
		$path = APPLICATION_PATH . DIRECTORY_SEPARATOR;
		foreach($this->_dispatcher->getPathParamNames() as $name) {
			$path .= DIRECTORY_SEPARATOR . $name;
			$value = $this->_dispatcher->getPathParam($name);
			if($value !== null) {
				$path .= DIRECTORY_SEPARATOR . $value;
			}
		}
		include $path;
	}

}