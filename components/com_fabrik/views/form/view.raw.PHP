<?php
/**
* @package Joomla
* @subpackage Fabrik
* @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.view');

require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'json.php' );

class fabrikViewForm extends JView
{
	
	var $_template 	= null;
	var $_errors 	= null;
	var $_data 		= null;
	var $_rowId 	= null;
	var $_params 	= null;

	var $_id 			= null;
	
	function setId($id)
	{
		$this->_id = $id;
	}
	
	function _getGroupProperties( &$groupModel )
	{
		$group 			= new stdClass(  );
		$model		= &$this->getModel();
		$groupTable 	=& $groupModel->getGroup();
		$groupParams 	=& $groupModel->getParams();
		$group->canRepeat = $groupParams->get( 'repeat_group_button', '0' );
		$addJs 			= str_replace( '"', "'",  $groupParams->get( 'repeat_group_js_add' ) );
		$group->addJs 	= str_replace( array("\n", "\r"), "",  $addJs );
		$delJs 			= str_replace('"', "'",  $groupParams->get( 'repeat_group_js_delete' ) );
		$group->delJs 	= str_replace( array("\n", "\r"), "",  $delJs );
		$showGroup 		= $groupParams->def( 'repeat_group_show_first', '1' );
		if ($showGroup == 0) {
			$groupTable->css .= ";display:none;";
		}
		$rubbish = array ("<br />", "<br>");
		$group->css 	= trim( str_replace( $rubbish, "", $groupTable->css ) );
		$group->id 		= $groupTable->id;
		$group->title 	= $groupTable->label;
		$group->name	= $groupTable->name;
		if ($groupModel->canRepeat() && $model->_editable) {
			$group->displaystate = '1';
		} else {
			$group->displaystate = '0';
		}
		return $group;
	}
	
	
	function display( $tpl = null )
	{
		global $mainframe, $_SESSION;
		$w =& new FabrikWorker();
		$config		=& JFactory::getConfig();
		$model		=& $this->getModel();
		$document =& JFactory::getDocument();
		FabrikHelperHTML::mootools();

		//Get the active menu item
		$usersConfig = &JComponentHelper::getParams( 'com_fabrik' );

		if (!isset( $this->_id )) {
			$model->setId( $usersConfig->get( 'fabrik', JRequest::getInt( 'fabrik' ) ) );
		} else {
			//when in a package the id is set from the package view
			$model->setId( $this->_id );
		}

		$form 	=& $model->getForm();
		$model->render();

		$tableModel =& $model->_table;
		$table = is_object( $tableModel ) ? $tableModel->getTable() : null;
		if (!$model->canPublish()) {
			if (!$model->_admin) {
				echo JText::_( 'SORRY THIS FORM IS NOT PUBLISHED' );
				return false;
			}
		}

		$access = $model->checkAccessFromTableSettings( );
		if ($access == 0) {
			return JError::raiseWarning( 500, JText::_( 'ALERTNOTAUTH' ) );
		}
		$model->_editable = ($access == 1 && $model->_editable == '1') ? false : true;
		if (JRequest::getVar('view') == 'details') {
			$model->_editable = false;
		}
		if (is_object( $tableModel )) {
			$joins =& $tableModel->getJoins( );
			$model->getJoinGroupIds( $joins );
		}

		$params =& $model->getParams();
		$params->def( 'icons', $mainframe->getCfg( 'icons' ) );
		$pop =  (JRequest::getVar('tmpl') == 'component') ? 1 : 0;
		$params->set( 'popup', $pop );

		$view = JRequest::getVar( 'view', 'form' );
		if ($view == 'details') {
			$model->_editable = false;
		}

		$groups =& $model->getGroupsHiarachy();
		
		$JSONarray = array();
		
		foreach ($groups as $gkey => $groupModel) {
			$groupTable 	=& $groupModel->getGroup();
			$group 				= new stdClass();//$this->getGroupProperties( $groupModel );
			$groupParams 	=& $groupModel->getParams();
			$aElements 		= array();
			//check if group is acutally a table join

			$repeatGroup = 1;
			$foreignKey = null;

			if ($groupModel->canRepeat()) {
				if ($groupModel->isJoin()) {

					$joinModel =& $groupModel->getJoinModel();
					$joinTable =& $joinModel->getJoin();

					$foreignKey  = '';
					if (is_object( $joinTable )) {
						$foreignKey = $joinTable->table_join_key;
						//need to duplicate this perhaps per the number of times
						//that a repeat group occurs in the default data?
						if (array_key_exists( $joinTable->id, $model->_data['join'] )) {
							$elementModels =& $groupModel->getPublishedElements();
							reset( $elementModels );
							$tmpElement = current( $elementModels );
							$smallerElHTMLName = $tmpElement->getFullName( false, true, false );
							$repeatGroup = count( $model->_data['join'][$joinTable->id][$smallerElHTMLName] );
						} else {
							//$$$ rob test!!!
							if (!$groupParams->get('repeat_group_show_first')) {
								continue;
							}
						}
					}
				} else {
					// repeat groups which arent joins
					$elementModels =& $groupModel->getPublishedElements();
					foreach ($elementModels as $tmpElement) {
						$smallerElHTMLName = $tmpElement->getFullName( false, true, false );
						if (array_key_exists( $smallerElHTMLName."_raw", $model->_data )){
							$d = $model->_data[$smallerElHTMLName."_raw"];
						} else {
							$d = @$model->_data[$smallerElHTMLName];
						}
						if (is_string($d) && strstr( $d, $tmpElement->_groupSplitter )) {
							$d = explode( $tmpElement->_groupSplitter, $d );
						}
						$c = count($d);
						if ( $c > $repeatGroup ) { $repeatGroup = $c;}
					}
				}
			}
			
			$groupModel->_repeatTotal = $repeatGroup;
			
			$aSubGroups = array();
			for ($c = 0; $c < $repeatGroup; $c++) {
				$aSubGroupElements = array();
				$elCount = 0;
				$elementModels =& $groupModel->getPublishedElements();
				foreach ($elementModels as $elementModel) {
					if (!$model->_editable) {
						$elementModel->_inDetailedView = true;
					}
					//force reload?
					$elementModel->_HTMLids = null;
					$elementHTMLId 	= $elementModel->getHTMLId( $c );
					$JSONarray[$elementHTMLId] 	=  $elementModel->getDefaultValue( $model->_data, $c );
				}
				
			}
		}

		$data = array( "id"=>$model->_id, 'model'=>'table', "errors"=> $model->_arErrors, "data" => $JSONarray, 'post'=>$_REQUEST );
		echo FastJSON::encode( $data );	
	}
	
}
?>