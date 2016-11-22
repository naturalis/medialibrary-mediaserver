<?php

namespace nl\naturalis\medialib\server;

/**
 * @author ayco_holleman
 */
class RequestDispatcher {
	private $_path = null;
	private $_baseUrl = null;
	private $_controllerName = null;
	/**
	 * @var AbstractController
	 */
	private $_controller = null;
	private $_actionName = null;
	private $_actionMethod = null;
	private $_pathParamNames = array();
	private $_pathParams = array();


	/**
	 * Create a new {@code RequestDispatcher} for the specified path. You
	 * would ordinarily specify $_SERVER['REQUEST_URI'] as the path. Note
	 * that $path is not supposed to be a complete URL. It is the part
	 * that starts with the forward slash after the host/port specification.
	 * If the path is supposed to start with a base URL segment, you must
	 * pass the base URL as the second argument to the constructor.
	 * 
	 * @param string $path        	
	 */
	public function __construct($path, $baseUrl = null)
	{
		// Chop off query string from path
		$pos = strpos($path, '?');
		$this->_path = $pos === false ? $path : substr($path, 0, $pos);
		$this->_baseUrl = trim($baseUrl, '/');
		$this->_decompose();
	}


	/**
	 * Get the path for which this {@code URLDecomposer} was created.
	 *
	 * @return string
	 */
	public function getPath()
	{
		return $this->_path;
	}


	/**
	 * Get the base URL, if any. If there is a base URL, it is the first segment
	 * of the path, without forward slashes.
	 *
	 * @return string
	 */
	public function getBaseUrl()
	{
		return $this->_baseUrl;
	}


	/**
	 * Get the path segment that denotes the controller to dispatch the request to.
	 *
	 * @return string
	 */
	public function getControllerName()
	{
		return $this->_controllerName;
	}


	/**
	 * Get the object that implements the controller.
	 *
	 * @return AbstractController
	 */
	public function getController()
	{
		return $this->_controller;
	}


	/**
	 * Get the path segment that denotes the action.
	 *
	 * @return string
	 */
	public function getActionName()
	{
		return $this->_actionName;
	}


	/**
	 * Get the name of the method that implements the action.
	 *
	 * @return string
	 */
	public function getActionMethod()
	{
		return $this->_actionMethod;
	}


	/**
	 * Get the value of the specified path parameter.
	 * 
	 * @param string $name The name of the path parameter
	 * @param string $default The value returned if there is no path parameter
	 * 			by that name
	 * @return string
	 */
	public function getPathParam($name, $default = null)
	{
		if(isset($this->_pathParams[$name]) && strlen($this->_pathParams[$name]) !== 0) {
			return $this->_pathParams[$name];
		}
		return $default;
	}


	/**
	 * Get the value of the specified parameter, be it a request parameter
	 * or a path parameter.
	 * 
	 * @param string $name The name of the parameter
	 * @param string $default The value returned if there is no parameter
	 * 			by that name
	 * @return string
	 */
	public function getParam($name, $default = null)
	{
		if(isset($this->_pathParams[$name]) && strlen($this->_pathParams[$name]) !== 0) {
			return $this->_pathParams[$name];
		}
		if(isset($_REQUEST[$name]) && strlen($_REQUEST[$name]) !== 0) {
			return $_REQUEST[$name];
		}
		return $default;
	}


	/**
	 * Get the names of the path parameters. The names are sorted
	 * according to the order in which they occor in the URL.
	 * @return array
	 */
	public function getPathParamNames()
	{
		return $this->_pathParamNames;
	}


	/**
	 * Get path parameters as an associative array.
	 * @return array
	 */
	public function getPathParams()
	{
		return $this->_pathParams;
	}


	private function _decompose()
	{
		
		// Normalize the path somewhat and slice it into path segments
		$path = trim(str_replace('//', '/', $this->_path), '/ ');
		$pathSegments = explode('/', $path);
		
		$i = count($pathSegments);
		
		// If last path segment is empty, remove it
		if($i > 0) {
			if(strlen(trim($pathSegments[$i - 1])) === 0) {
				--$i;
				array_pop($pathSegments);
			}
		}
		
		if($i === 0) {
			return;
		}
		
		// If there is a base URL, chop it off
		if($this->_baseUrl !== null && $this->_baseUrl !== '') {
			if($pathSegments[0] !== $this->_baseUrl) {
				throw new \Exception("Configuration error: invalid value for baseUrl: {$this->_baseUrl}");
			}
			array_shift($pathSegments);
		}
		
		if(count($pathSegments) === 0) {
			// No controller specified; path only contains base URL.
			return;
		}
		
		// Extract the controller from the path
		$this->_controllerName = $pathSegments[0];
		$controllerClass = $this->getControllerClass($this->_controllerName);
		if(!class_exists($controllerClass, true)) {
			throw new \Exception("Invalid controller: \"{$this->_controllerName}\"");
		}
		$this->_controller = new $controllerClass();
		
		if($this->_controller->isSingleActionController()) {
			$this->_actionName = 'index';
			$this->_actionMethod = $this->getActionMethodName($this->_actionName);
			if(!method_exists($controllerClass, $this->_actionMethod)) {
				throw new Exception("Invalid action: \"{$this->_actionName}\"");
			}
		}
		
		// Move past the controller segment
		array_shift($pathSegments);
		if(count($pathSegments) === 0) {
			if($this->_controller->isSingleActionController()) {
				return;
			}
			throw new Exception("Missing action in path \"{$this->_path}\"");
		}
		
		if(!$this->_controller->isSingleActionController()) {
			$this->_actionName = $pathSegments[0];
			$this->_actionMethod = $this->getActionMethodName($this->_actionName);
			if(!method_exists($controllerClass, $this->_actionMethod)) {
				throw new Exception("Invalid action: \"{$this->_actionName}\"");
			}
			// Move past the action segment
			array_shift($pathSegments);
		}
		
		// Add remaining segments as key-value pairs to $_pathParams array.
		for($i = 0; $i < count($pathSegments); $i += 2) {
			$key = rawurldecode($pathSegments[$i]);
			$value = isset($pathSegments[$i + 1]) ? rawurldecode($pathSegments[$i + 1]) : null;
			$this->_pathParamNames[] = $key;
			$this->_pathParams[$key] = $value;
		}
	}


	protected function getControllerClass($controllerName)
	{
		$controllerName[0] = strtoupper($controllerName[0]);
		return 'nl\naturalis\medialib\server\controller\\' . $controllerName . 'Controller';
	}


	protected function getActionMethodName($actionName)
	{
		return strtolower($actionName) . 'Action';
	}

}