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
require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'json.php' );

class FabrikModelPlugin extends FabrikModel
{

	/** @var bol determines if the admin settings are visible or hidden when rendered */
	var $_adminVisible = false;

	/** @var string path to xml file **/
	var $_xmlPath = null;

	/** @var object params **/
	var $_params  = null;

	var $attribs = null;

	/** @var string error **/
	var $_err = null;

	var $_id = null;

	var $_row = null;

	/** @var int order that the plugin is rendered */
	var $renderOrder = null;
	
	/** @var object params for this given plugin **/
	var $pluginParams;
	
	var $_counter;

	/**
	 * constructor
	 */

	function __construct()
	{
		parent::__construct();
	}

	function setId($id)
	{
		$this->_id = $id;
	}

	/**
	 * sets the instances admin state
	 * @param bol admin state
	 */

	function setAdmin( $admin )
	{
		$this->_admin = $admin;
	}

	function setParams( &$params, &$usedLocations, &$usedEvents)
	{
		//get some blank parameter properties
		$this->pluginParams = new fabrikParams( '', $this->_xmlPath, 'fabrikplugin' );

		$p =& $this->pluginParams->getParams('params', '_default','array', $this->renderOrder);
		$tmpAttribs = '';
		//build a temp attributes string to pass to the parameters object
		foreach ($p as $e) {
			$name = $e[5];
			$pluginElOpts = $params->get($name, "", "_default", "array");
			$val = (array_key_exists( $this->renderOrder, $pluginElOpts )) ? $pluginElOpts[$this->renderOrder] : '';
			$tmpAttribs .= $name . "=" . $val . "\n";
		}
		//redo the parmas with the exploded data
		$this->pluginParams = new fabrikParams( $tmpAttribs, $this->_xmlPath, 'fabrikplugin');
		//for table
		if (!array_key_exists( $this->renderOrder, $usedLocations )) {
			$usedLocations[$this->renderOrder] = '';
		}
		if (!array_key_exists( $this->renderOrder, $usedEvents )) {
			$usedEvents[$this->renderOrder] = '';
		}
		return $this->pluginParams;
	}

	/**
	 * write out the admin form for customising the plugin
	 *
	 * @param object $row
	 */

	function renderAdminSettings()
	{
		/* can be overwritten by action plugin */
		$params =& $this->getParams();
		?>
<div id="page-<?php echo $this->_name;?>" class="pluginSettings"
	style="display: none">
<table>
<?php echo $params->render();?>
</table>
</div>
<?php
	}

	/**
	 * load params
	 */

	function &getParams()
	{
		if (is_null( $this->_params )) {
			return $this->_loadParams();
		}else{
			return $this->_params;
		}
	}

	function &_loadParams()
	{
		if (is_null($this->attribs)) {
			$row =& $this->getRow();
			$a =& $row->attribs;
		} else {
			$a =& $this->attribs;
		}
		if (!isset( $this->_params )) {
			$this->_params = &new fabrikParams( $a, $this->_xmlPath, 'component' );
		}
		return $this->_params;
	}

	function getRow()
	{
		if (is_null( $this->_row )){
			$this->_row =& $this->getTable( $this->_type );
			$this->_row->load( $this->_id );
		}
		return $this->_row;
	}

	/**
	 * determine if we use the plugin or not
	 * both location and event criteria have to be match
	 * @param object calling the plugin table/form
	 * @param string location to trigger plugin on
	 * @param string event to trigger plugin on
	 * @return bol true if we should run the plugin otherwise false
	 */

	function canUse( &$oRequest, $location, $event )
	{
		$ok = false;
		switch($location){
			case 'front':
				if(!$oRequest->_admin){
					$ok = true;
				}
				break;
			case 'back':
				if($oRequest->_admin){
					$ok = true;
				}
				break;
			case 'both':
				$ok = true;
				break;
		}
		if($ok){
			switch( $event ){
				case 'new':
					if( $oRequest->_rowId != 0 ){
						$ok = false;
					}
					break;
				case 'edit':
					if( $oRequest->_rowId == 0 ){
						$ok = false;
					}
					break;
			}
		}
		return $ok;
	}

	function customProcessResult()
	{
		return true;
	}

	/**
	 * ajax function to return a string of table drop down options
	 * based on cid variable in query string
	 *
	 */
	function ajax_tables()
	{
		$db =& JFactory::getDBO();
		$cid = JRequest::getVar( 'cid', -1 );
		$showFabrikTables = JRequest::getVar( 'showf', false);
		if ($showFabrikTables) {
			$sql = "SELECT id, label FROM #__fabrik_tables WHERE connection_id = '$cid'";
			$db->setQuery( $sql );
			$rows = $db->loadObjectList();
			$default = new stdClass;
			$default->id = '';
			$default->label = JText::_('PLEASE SELECT');
			array_unshift($rows, $default);
		} else {
			$cnn = JModel::getInstance('Connection', 'FabrikModel');
			$cnn->setId( $cid );
			$db =& $cnn->getDb();
			$db->setQuery("SHOW TABLES");
			$rows = $db->loadResultArray();
			array_unshift($rows, '');
		}
		echo FastJSON::encode($rows);
	}

	function ajax_fields()
	{
		$tid = JRequest::getVar( 't' );
		$keyType = JRequest::getVar( 'k', 1 );
		$showAll = JRequest::getVar( 'showall', false );//if true show all fields if false show fabrik elements

		//only used if showall = false, includes validations as separate entries
		$incCalculations = JRequest::getVar('calcs', false);
		$arr = array();
		if ($showAll) { //show all db columns
			$cid = JRequest::getVar( 'cid', -1 );
			$cnn = JModel::getInstance('Connection', 'FabrikModel');
			$cnn->setId( $cid );
			$db =& $cnn->getDb();
			if ($tid != '') {
				$db->setQuery("DESCRIBE `$tid`");

				$rows = $db->loadObjectList();
				if (is_array( $rows )) {
					foreach ($rows as $r) {
						$c = new stdClass();
						$c->value = $r->Field;
						$c->label = $r->Field;
						$arr[] = $c; //dont use =&
					}
				}
			}
		} else {
			//show fabrik elements in the table
			//$keyType 1 = $element->id;
			//$keyType 2 = tablename___elementname
			$model =& JModel::getInstance( 'Table', 'FabrikModel' );
			$model->setId( $tid );
			$table =& $model->getTable();
			$groups = $model->getFormGroupElementData();
			$arr = array();
			foreach ($groups as $g=>$val) {
				$elementModels =& $groups[$g]->getMyElements();
				foreach ($elementModels as $e => $eVal) {
					$element =& $eVal->getElement();
					if ($keyType == 1) {
						$v = $element->id;
					} else {
						$v = $eVal->getFullName( false );
					}
					$c = new stdClass();
					$c->value = $v;
					$label = FabrikString::getShortDdLabel( $element->label );
					$c->label = $label; 
					$arr[] = $c; //dont use =&
					if ($incCalculations) {
						$params =& $eVal->getParams();
						if ($params->get( 'sum_on', 0 )) {
							$c = new stdClass();
							$c->value = 'sum___'.$v;
							$c->label = JText::_('SUM') . ": " .$label ;
							$arr[] = $c; //dont use =&
						}
						if ($params->get( 'avg_on', 0 )) {
							$c = new stdClass();
							$c->value = 'avg___'.$v;
							$c->label = JText::_('AVERAGE') . ": " .$label;
							$arr[] = $c; //dont use =&
						}
						if ($params->get( 'median_on', 0 )) {
							$c = new stdClass();
							$c->value = 'med___'.$v;
							$c->label = JText::_('MEDIAN') . ": " .$label;
							$arr[] = $c; //dont use =&
						}
						if ($params->get( 'count_on', 0 )) {
							$c = new stdClass();
							$c->value = 'cnt___'.$v;
							$c->label = JText::_('COUNT') . ": " .$label;
							$arr[] = $c; //dont use =&
						}
					}
				}
			}
		}
		echo FastJSON::encode($arr);
	}
}
?>