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

require_once( COM_FABRIK_FRONTEND.DS.'models'.DS.'plugin.php' );

class FabrikModelFormPlugin extends FabrikModelPlugin
{

	/**
	 * run right at the beginning of the form processing
	 *
	 * @return bol
	 */

	function onBeforeProcess()
	{
		return true;
	}

	/**
	 * run if form validation fails
	 *
	 * @return bol
	 */

	function onError()
	{
			
	}

	/**
	 * run before table calculations are applied
	 *
	 * @return bol
	 */

	function onBeforeCalculations()
	{
		return true;
	}

	/**
	 * run right at the end of the form processing
	 * form needs to be set to record in database for this to hook to be called
	 *
	 * @return bol
	 */

	function onAfterProcess()
	{
		return true;
	}

	/**
	 * alter the returned plugin manager's result
	 *
	 * @param string $method
	 * @return bol
	 */

	function customProcessResult( $method )
	{
		return true;
	}

	/**
	 * sets up any bottom html
	 *
	 */

	function getBottomContent()
	{
			
	}

	/**
	 * get any html that needs to be written into the bottom of the form
	 *
	 * @return string html
	 */

	function getBottomContent_result()
	{
		return "";
	}

	/**
	 * sets up any top html
	 *
	 */

	function getTopContent()
	{
			
	}

	/**
	 * get any html that needs to be written into the top of the form
	 *
	 * @return string html
	 */

	function getTopContent_result()
	{
		return "";
	}

	/**
	 * show a new for entering the form actions options
	 */

	function renderAdminSettings( $elementId, &$row, &$params, $lists, $c ){
		$params->_counter_override = $this->_counter;
		$display =  ($this->_adminVisible) ? "display:block" : "display:none";
		$return = '<div class="page-' . $elementId . ' elementSettings" style="' . $display . '">
 		'. $params->render('params', '_default', false, $c)
		.'</div>
 		';
		$return = str_replace("\r", "", $return);
	  return $return;
	  //dont do here as if we json enocde it as we do in admin form view things go wrong
		//return  addslashes(str_replace("\n", "", $return));
	}

	/**
	 * convert the posted form data to the data to be shown in the email
	 * e.g. radio buttons swap their values for the value's label
	 *
	 * HACKED from the form view
	 *
	 * @return array email data
	 */

	function getEmailData()
	{
		$model		=& $this->formModel;
		$model->getPostMethod();
		//$$$rob dont render the form - theres no need and it gives a warning about an unfound rowid
		//$model->render();
		$tableModel =& $model->_table;
		$table = is_object( $tableModel ) ? $tableModel->getTable() : null;

		$model->_editable = false;
		if (is_object( $tableModel )) {
			$joins =& $tableModel->getJoins( );
			$model->getJoinGroupIds( $joins );
		}

		$params =& $model->getParams();

		$emailData = array();

		// $$$ hugh - temp foreach fix
		$groups = $model->getGroupsHiarachy();

		foreach ($groups as $gkey => $groupModel) {
			$groupParams 	=& $groupModel->getParams();
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
						//if (array_key_exists( $joinTable->id, $model->_data['join'] )) {
						if (array_key_exists( $joinTable->id, $model->_formDataWithTableName['join'] )) {

							$elementModels =& $groupModel->getPublishedElements();
							reset( $elementModels );
							$tmpElement = current( $elementModels );
							$smallerElHTMLName = $tmpElement->getFullName( false, true, false );
							//$repeatGroup = count( $model->_data['join'][$joinTable->id][$smallerElHTMLName] );
							$repeatGroup = count( $model->_formDataWithTableName['join'][$joinTable->id][$smallerElHTMLName] );
						} else {
							if (!$groupParams->get( 'repeat_group_show_first' )) {
								continue;
							}
						}
					}
				} else {
					// repeat groups which arent joins
					$elementModels =& $groupModel->getPublishedElements();
					foreach ($elementModels as $tmpElement) {
						$smallerElHTMLName = $tmpElement->getFullName( false, true, false );
						if (array_key_exists( $smallerElHTMLName."_raw", $model->_formDataWithTableName )){
							$d = $model->_formDataWithTableName[$smallerElHTMLName."_raw"];
						} else {
							$d = @$model->_formDataWithTableName[$smallerElHTMLName];
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
			$group =& $groupModel->getGroup();
			$aSubGroups = array();
			for ($c = 0; $c < $repeatGroup; $c++) {
				$aSubGroupElements = array();
				$elementModels =& $groupModel->getPublishedElements();

				foreach ($elementModels as $elementModel) {
					//force reload?
					$elementModel->defaults = null;
					$elementModel->_repeatGroupTotal = $repeatGroup - 1;
					$element =& $elementModel->getElement();

					$k			= $elementModel->getFullName( false, true, false );
					$key 				= $elementModel->getFullName( true, true, false );
					//used for working out if the element should behave as if it was
					//in a new form (joined grouped) even when editing a record
					$elementModel->_inRepeatGroup = $groupModel->canRepeat();
					$elementModel->_inJoin 				= $groupModel->isJoin();
					$elementModel->_editable 	= false;
						
					if( $elementModel->_inJoin) {
						if ($elementModel->_inRepeatGroup) {
							$emailData[$k."_raw"] = $model->_formDataWithTableName['join'][$group->join_id][$k][$c];
						} else {
							$emailData[$k."_raw"] = $model->_formDataWithTableName['join'][$group->join_id][$k];
						}
					} else {
						$emailData[$k."_raw"]= $model->_formDataWithTableName[$key];
					}
					$emailData[$k]= $elementModel->getEmailValue($emailData[$k."_raw"], $model->_formDataWithTableName, $c);
				}
			}
		}
		return $emailData;
	}

}
?>