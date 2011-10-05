<?php
/**
* @package Joomla
* @subpackage Fabrik
* @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.model');
/**
 * @package fabrik
 * @Copyright (C) Rob Clayburn
 * @version $Revision: 1.3 $
 */
 
require_once( JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'models'.DS.'plugin.php' );

class FabrikModelValidationRule extends FabrikModelPlugin
{

	var $_pluginName = null;
	
	var $_counter = null;
	
	var $_pluginParams = null;
	
	var $_rule = null;
	/**
	 * constructor
	 */

	function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * validate the elements data against the rule
	 * @param string data to check
	 * @param object element
	 * @param int plugin sequence ref
	 * @return bol true if validation passes, false if fails
	 */
	
	function validate( $data, &$element, $c )
	{
		return true;
	}

	/**
	 * looks at the validation condition & evaulates it
	 * if evaulation is true then the validation rule is applied
	 *@return bol apply validation
	 */
	
	function shouldValidate( $data, $c )
	{
		$params =& $this->getParams();
		$post	= JRequest::get( 'post' );
		$v = $params->get($this->_pluginName .'-validation_condition', '', '_default','array', $c);
		
		if (!array_key_exists( $c, $v )) {
			return true;
		}
		$condition = $v[$c];
		if ($condition == '') {
			return true;
		}
		
		$w = new FabrikWorker();
		$condition = trim( $w->parseMessageForPlaceHolder( $condition, $post ) );
		// $$$ hugh - this screws things up if it's more than one line of code.
		/*
		if (substr( strtolower( $condition ), 0, 6 ) !== 'return') {
			$condition = "return $condition";
		}
		*/
		
		$res = @eval( $condition );
		if (is_null( $res )) {
			return true;
		}
		return $res;
	}

	
	function renderAdminSettings( $elementId, &$row, &$params, $c )
	{
 		$params->_counter_override = $this->_counter;
 		$display =  ($this->_adminVisible) ? "display:block" : "display:none";
 		$return = '<div class="page-' . $elementId . ' elementSettings" style="' . $display . '">'
 		. $params->render('params', '_default', false, $c);
		$return .= '</div>';
 		$return = str_replace("\r", "", $return);
	  return $return;
	  //dont do here as if we json enocde it as we do in admin form view things go wrong
		//return  addslashes(str_replace("\n", "", $return));
 	}
 	
 	function getPluginParams()
	{
		if (!isset( $this->_pluginParams )) {
			$cache = & JFactory::getCache();
			//$this->_pluginParams = $cache->call( array( &$this, '_loadPluginParams') );
			$this->_pluginParams = $this->_loadPluginParams();
		}
		return $this->_pluginParams;
	}
	
	function _loadPluginParams()
	{
		if (isset( $this->_xmlPath )) {
			$rule =& $this->getValidationRule();
			$pluginParams = &new fabrikParams( $rule->attribs, $this->_xmlPath, 'fabrikplugin' );
			$pluginParams->bind( $rule );
			return $pluginParams;
		}
		return false;
	}
	
	function &getValidationRule()
	{
		if (!$this->_rule) {
			JTable::addIncludePath( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_fabrik'.DS.'tables' );
			$row = JTable::getInstance( 'Validationrule', 'Table' );
			$row->load( $this->_id );
			$this->_rule = $row;
		}
		return $this->_rule;
	}
	
	
	/**
	 * get the warning message
	 *
	 * @return string
	 */
	
	function getMessage( $c )
	{
		//$params = null
		$params =& $this->_loadParams();
		$v = $params->get($this->_pluginName .'-message', '_default', 'array', $c);
		return $v[$c];
	}

}
?>
