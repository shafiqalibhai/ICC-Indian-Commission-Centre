<?php

/**
* Add an action button to the table to copy rows
* @package Joomla
* @subpackage Fabrik
* @author Rob Clayburn
* @copyright (C) Rob Clayburn
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

//require the abstract plugin class
require_once( COM_FABRIK_FRONTEND.DS.'models'.DS.'plugin-table.php' );
require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'html.php' );
require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'parent.php' );

class FabrikModelCopy extends FabrikModelTablePlugin {
 	
	var $_counter = null;
	
	/**
	* Constructor
	*/

	function __construct()
	{
		parent::__construct();
	}
	
	function button()
	{
		return "copy records";
	}
	
	function button_result( $c )
	{
		return "<input type=\"button\" name=\"copy\" value=\"".JText::_('COPY') . "\" class=\"button tableplugin\"/>";
	}
	
	function canUse()
	{
		return true;
	}
	
	/**
	 * determine if the table plugin is a button and can be activated only when rows are selected
	 *
	 * @return bol
	 */
	
	function canSelectRows()
	{
		return true;
	}
	
	/**
	 * do the plugin action
	 *
	 */
	function process(&$model)
	{
		$ids	= JRequest::getVar( 'ids', array(), 'method', 'array' );
		$table =& $model->getTable();
		
		$formModel =& $model->getForm();
		$origPost = JRequest::get('post');
		$_POST = array();
		foreach ($ids as $id) {
			$row = $model->getRow($id);
			$row->Copy = '1';
			foreach ($row as $key=>$val) {
				JRequest::setVar( $key, $val, 'post' );
			}
			$formModel->setFormData();
			$formModel->processToDB();
		}
		$_POST = array();
		JRequest::set( $origPost, 'post', true );
		return JText::sprintf( 'ROWS COPIED', count($ids));
	}
	
	/**
	 * load the javascript class that manages interaction with the form element
	 * should only be called once
	 * @return string javascript class file
	 */

	function loadJavascriptClass()
	{
		FabrikHelperHTML::script( 'javascript.js', 'components/com_fabrik/plugins/table/copy/', false );
	}

	/**
	 * return tehe javascript to create an instance of the class defined in formJavascriptClass
	 * @param string table's form id to contain plugin
	 * @return string javascript to create instance. Instance name must be 'el'
	 */

	function loadJavascriptInstance( $form_id )
	{
		$opts = new stdClass();
		$opts->renderOrder = $this->renderOrder;
		$opts = FastJSON::encode($opts);
		return "new fbTableCopy('$form_id', $opts)" ;
	}
	
	/**
	 * show a new for entering the form actions options 
	 */
 	
 	function renderAdminSettings( $elementId, &$row, &$params, $lists, $c )
 	{
 		$params->_counter_override = $this->_counter;
 		$display =  ($this->_adminVisible) ? "display:block" : "display:none";
 		$return = '<div class="page-' . $elementId . ' elementSettings" style="' . $display . '">
 		' . $params->render('params', '_default', false, $c) . 
 		'</div>
 		';
    $return = str_replace("\r", "", $return);
	  return $return;
	  //dont do here as if we json enocde it as we do in admin form view things go wrong
		//return  addslashes(str_replace("\n", "", $return));
 	}
	
}
?>