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

class FabrikViewTable extends JView{

	function display()
	{
		global $mainframe;
		$post = JRequest::get( 'post' );
		$model			= &$this->getModel();
		if ($mainframe->_name == 'administrator') {
			$model->_admin = true;
		}
		$model->setId( JRequest::getInt( 'tableid' ) );
		$table 	=& $model->getTable();
		$total 	= $model->getTotalRecords();
		$nav 		=& $model->getPagination( $total, 0, $total );

		//bodge the models as fields to only render the ones selected in popup win
		$asfields =& $model->getAsFields();
		$newfields = array();
		$model->_temp_db_key_addded = false;
		$selectedFields = JRequest::getVar( 'fields' );
		foreach ($selectedFields as $name => $val) {
			foreach ($asfields as $f) {
				if (strstr($f, $name) && $val == 1) {
					$newfields[] = $f;
				}
			}
		}
		// $$$ hugh - pretty sure this needs to be [0] not [1]
		$model->asfields[0] =& $newfields;
		$model->render();

		$headings = $this->getHeadings();
		if (empty( $headings )) {
			$url = JRequest::getVar( 'HTTP_REFERER', '', 'server' );
			$mainframe->redirect( $url, JText::_( 'No data to export' ) );
			return;
		}
		$document       = JFactory::getDocument();
		$document->setMimeEncoding( 'application/zip' );

		// Set the response to indicate a file download
		JResponse::setHeader( 'Content-Type', 'application/zip' );
		JResponse::setHeader( 'Content-Disposition', 'attachment; filename="' . $table->label . '-export.csv"' );

		$str = implode( $headings, ",") . "\n";
		$incRaw = JRequest::getVar('incraw', true);
		$data =& $model->getData();
    
		$exportFormat = $model->getParams()->get('csvfullname');
	  $shortkey = FabrikString::shortColName($table->db_primary_key);
		foreach ($data as $group) {
			foreach ($group as $row) {
					
				$a = JArrayHelper::fromObject( $row );
				if ($exportFormat == 1) {
				  unset($a[$shortkey]);
				}
				if (!$incRaw) {
					foreach($a as $key=>$val){
						if (substr($key, strlen($key)-4, strlen($key)) == "_raw" ) {
							unset($a[$key]);
						}
					}
				}
				if (JRequest::getVar( 'inccalcs' ) == 1) {
					array_unshift($a, ' ');
				}
				$str .= implode( ",", array_map( array( $this, "_quote" ), array_values( $a ) ) );
				$str .= "\n";
			}
		}

		$this->_addCalculations($a, $str);
		
		JResponse::setBody( $str );
		echo JResponse::toString( false );
	}

	/**
	 * add calculations
	 *
	 * @param array of field elements $a
	 * @param string to out put as csv file $str
	 */
	
	function _addCalculations($a, &$str)
	{
		$model			= &$this->getModel();
		if (JRequest::getVar( 'inccalcs' ) == 1) {
				
			$calkeys = array( 'sums', 'avgs', 'medians', 'count' );
			foreach ($calkeys as $calkey) {
				$aCalcs[$calkey] = array_fill( 0, count($a)+1, ' ' );
				$aCalcs[$calkey][0] = $calkey;
				$calcs = $model->_aRunCalculations;
				foreach ($calcs[$calkey] as $key => $cal) {
					$x = 0;
					$found = false;
					foreach ($a as $akey=>$aval) {
						if ($akey == substr($key, 0, strlen($key)-4)&& $x != 0) {
							$found = true;
							break;
						}
						$x ++;
					}
					if ($found) {
						$aCalcs[$calkey][$x] = $cal['calc']->value;
					}
				}
				$str .= implode( ",", array_map( array( $this, "_quote" ), array_values( $aCalcs[$calkey] ) ) );
				$str .= "\n";
			}
		}
	}

	function _quote($n) {
		return '"'.str_replace('"', '""', $n).'"';
	}

	function getHeadings()
	{
		$model			= &$this->getModel();
		$table 	    =& $model->getTable();
		$params 		=& $model->getParams();
		$hformat 		= $params->get( 'csvfullname' );
		$data 			=& $model->getData();
		$headings 	= array();
		$g =& current($data);
		if (empty($g)) {
			return $g;
		}
		$selectedFields = JRequest::getVar( 'fields' );
		$r =& current($g);
		$formModel =& $model->getForm();
		$groups =& $formModel->getGroupsHiarachy();
		$h = array();
		if (!is_object( $r )) {
			return new stdClass();
		}
		$incRaw = JRequest::getVar('incraw', true);

	  $shortkey = FabrikString::shortColName($table->db_primary_key);

		foreach ($r as $heading=>$value) {
			$found = false;
			foreach ($groups as $groupModel) {
				$elementModels =& $groupModel->getPublishedElements();
				foreach ($elementModels as $elementModel) {
					$element =& $elementModel->getElement();
					$fullname = $elementModel->getFullName( false, true, false );
					
					if ($fullname == $heading || $fullname . "_raw" == $heading){
						$found = true;
						switch ($hformat) {
							default:
							case '0':
								$n = $element->name;
								break;
							case '1':
								$n = $elementModel->getFullName( false, false, false );
								break;
							case '2':
								$n = $element->label;
								break;
						}
						if($fullname . "_raw" == $heading) {
							$n .= "_raw";
						}
						if (!(substr($n, strlen($n)-4, strlen($n)) == "_raw" && !$incRaw)) {
							$h[] = $n;
						}
					}
				}
			}

			if (!$found) {
				if (!(substr($heading, strlen( $heading )-4, strlen( $heading )) == "_raw" && !$incRaw)) {
				  //stop fabrik_internal_id getting added to tables when exported wiht fullelname key
				  if ($hformat != 1 && $heading != $shortkey) {
					  $h[] = $heading;
				  }
				}
			}
		}
		if (JRequest::getVar( 'inccalcs' ) == 1) {
			array_unshift($h, JText::_('Calculation'));
		}
		return $h;
	}
}
?>