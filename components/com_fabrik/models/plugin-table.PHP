<?php

/**
* @package Joomla
* @subpackage Fabrik
* @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport( 'joomla.application.component.model' );

require_once( JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'models'.DS.'plugin.php' );

class FabrikModelTablePlugin extends FabrikModelPlugin
{
	/** determines if the plugin requires mocha to be loaded */
	var $useMocha = false;
	/**
	 * constructor
	 */

	function __construct()
	{
		parent::__construct();
		$usersConfig = &JComponentHelper::getParams( 'com_fabrik' );
		$id = JRequest::getVar( 'tableid', $usersConfig->get( 'tableid' ) );
		$this->setId($id);
	}
	
	function canUse()
	{
		return true;
	}
	
	/**
	 * @param $form_id string name of form js class
	 */
	
  function loadJavascriptInstance( $form_id )
	{

	}
	
	/**
	 * onGetData method
	 *
	 * @param object calling the plugin table/form
	 * @return bol currently ignored
	 */
	function onLoadData( &$params, &$oRequest )
	{
		return true;
	}
	
		/**
	 * onFiltersGot method - run after the table has created filters
	 *
	 * @param object calling the plugin table/form
	 * @return bol currently ignored
	 */
	
	function onFiltersGot( &$params, &$oRequest )
	{
	  return true;
	}
	
	function requiresMocha()
	{
		return $this->useMocha;
	}
 	
}	
?>