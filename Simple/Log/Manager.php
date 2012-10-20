<?php

/**
* @author Eric Schabell <eric@schabell.com>
* @copyright Copyright 2005, GPL
* @package PMS
*/


/**
* Manager class
*
* Manager manages! It is an abstract class, therefore you must (re)implement 
* all methods in all classes extending from Manager!
* @access public
*
* @package PMS
* @subpackage Manager
* @abstract
*/
abstract class Manager 
{
	/**
	* @var string Type of manager.
	*/
	private $typeManager;

	/** 
	* @var string Error message.
	*/
	private $errorMsg;
	
	/**
	* Constructor - initialize the manager by setting the type of manager.
	* @access public
	*
	* @param string The type of manager being created.
	* @return Manager Manager object.
	*/
	public function __construct( $manager )
	{
		$this->typeManager = $manager;
	}


	/**
	* getErrorMsg - retrieves the error message.
	* @access public
	*
	* @return string The error message.
	*/
	public function getErrorMsg()
	{
		return $this->errorMsg;
	}


	/**
	* getTypeManager - returns the type of Manager this is.
    * @access public
	* 
	* @return string The type of manager this is.
	*/
	public function getTypeManager()
	{
		return $this->typeManager;
	}

	/**
	* setErrorMsg - fills error with message.
	* @access public
	*
	* @param string The message to be put into error message.
	*/
	public function setErrorMsg( $message )
	{
		$this->errorMsg = $message;
	}

}

?>
