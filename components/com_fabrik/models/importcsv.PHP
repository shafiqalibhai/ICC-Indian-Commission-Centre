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

require_once(COM_FABRIK_FRONTEND.DS.'models'.DS.'import.php');

class FabrikModelImportcsv extends FabrikModelImport{

	var $headings = null;
	var $data = null;

	/** @var array list of new headings found in csv file when importing */
	var $newHeadings = array();

	/** @var array list of matched headings found in csv file when importing */
	var $matchedHeadings = array();

	/** @var array of table's join objects */
	var $joins = null;
	
	/** @var object table object to import into */
	var $table = null;
	
	/**
	 * checks uploaded file, and uploads it
	 */

	function checkUpload()
	{
		if (!(bool)ini_get('file_uploads')) {
			JError::raiseWarning(500, JText::_("The installer can't continue before file uploads are enabled. Please use the install from directory method.")) ;
			return false;
		}
		$userfile = JRequest::getVar( 'userfile', null, 'files' );
		if (!$userfile) {
			JError::raiseWarning(500, JText::_( 'No file selected'));
			return false;
		}
		jimport('joomla.filesystem.file');

		$to = JPath::clean( COM_FABRIK_BASE . DS .'media'.DS.$userfile['name'] );

		$resultdir = JFile::upload( $userfile['tmp_name'], $to );
		if ($resultdir == false) {
			JError::raiseWarning(500, JText::_('Upload Error'));
			return false;
		}
		return true;
	}

	/**
	 * read the CSV file, store results in $this->headings and $this->data
	 */

	function readCSV( $userfile_name ){
		$baseDir 			= JPath::clean( COM_FABRIK_BASE . DS .'media' );
		$file_handle 		= fopen($baseDir . DS . $userfile_name, "r");
		$this->headings 	= array();
		$this->data 		= array();
		$field_delimiter 	= JRequest::getVar( 'field_delimiter', ',' );
		$text_delimiter 	= stripslashes( JRequest::getVar( 'text_delimiter', '"' ) );

		$csv				 = & new csv_bv($baseDir . '/' . $userfile_name, $field_delimiter, $text_delimiter, '\\');

		$csv->SkipEmptyRows(TRUE); // Will skip empty rows. TRUE by default. (Shown here for example only).
		$csv->TrimFields(TRUE); // Remove leading and trailing \s and \t. TRUE by default.

		while ($arr_data = $csv->NextLine()) {
			if (empty( $this->headings )) {
				$this->headings =  $arr_data;
			} else {
				foreach ($arr_data as &$d) {
					//strip any none uft-8 characters from the import data 
					//if we don't do this then the site's session is destroyed and you are logged out
					$d = iconv("utf-8", "utf-8//IGNORE", $d);
				}
				$this->data[] = $arr_data;
			}
		}
		
		fclose( $file_handle );
		$session =& JFactory::getSession();
		$session->set( 'com_fabrik.csvdata', $this->data );
		$session->set( 'com_fabrik.matchedHeadings', $this->matchedHeadings );
	}
	
	/**
	 * get the table model
	 * @return object table model
	 */
	function getTableModel()
	{
	  if(!isset($this->table)) {
	    $this->table = JModel::getInstance('table', 'FabrikModel');
	    //tableid JRequest::getVar( 'fabrik_table' )
	    $this->table->setId( JRequest::getVar( 'tableid' ) );
	  }
	  return $this->table;
	}

	function findExistingElements()
	{
	  $tableModel =& $this->getTableModel();
		$tableModel->getFormGroupElementData();
		$pluginManager =& JModel::getInstance( 'Pluginmanager', 'FabrikModel' );
		$pluginManager->getPlugInGroup( 'table' );
		$aUsedElements = array();
		$formModel =& $tableModel->getForm();
		$tableParams =& $tableModel->getParams();
		$mode = $tableParams->get( 'csvfullname' );
		$intKey = 0;
		$groups =& $formModel->getGroupsHiarachy();
		
		
		$elementMap = array();
		foreach ($this->headings as $heading) {
			$found = false;
			foreach ($groups as $groupModel) {

				$elementModels =& $groupModel->getMyElements();
				foreach ($elementModels as $elementModel) {
					$element =& $elementModel->getElement();


					switch($mode){
						case 0:
							$name = $element->name;
							break;
						case 1:
							$name = $elementModel->getFullName( false, false, false );
							break;
						case 2:
							$name = $element->label;
							break;
					}

					if (strtolower( $heading ) == strtolower( $name )) {
						/** heading found in table */
						$this->matchedHeadings[] = $element->name;
						$this->aUsedElements[strtolower( $heading )] =& $elementModel;
						$elementMap[$intKey] = clone($elementModel);
						$found = true;
						break ; //break out of the group foreach
					}

					if (strtolower( $heading ) == strtolower( $name )."_raw") {
						/** heading found in table */
						$this->matchedHeadings[] = $element->name ."_raw";
						$this->aUsedElements[strtolower( $heading ) ."_raw"] =& $elementModel;
						$found = true;
						$elementMap[$intKey] = clone($elementModel);
						break ; //break out of the group foreach
					}
					
				}
			}
			//moved after repeat group otherwise elements in second group are never found
			if (!$found && !in_array($heading, $this->newHeadings)) {
				$this->newHeadings[] = $heading;
			}
			
			$intKey ++;
		}
			foreach($elementMap as $key => $elementModel){
				$element =& $elementModel->getElement();
				$elementModel->prepareCSVData( $this->data, $key );
			}
	}
	
	function makeTableFromCSV()
	{
		$user 			=& JFactory::getUser();
			
		$dropData		= JRequest::getInt( 'drop_data', 0, 'post' );
		$overWrite	= JRequest::getInt( 'overwrite', 0, 'post' );
		$tableModel	=& $this->getTableModel();
		$tableModel->getTable();
		$formModel 	=& $tableModel->getForm();

		if ($dropData) {
			$tableModel->truncate();
		}

		$table 				=& $tableModel->getTable();
		$tableParams 	=& $tableModel->getParams();
		$csvFullName 	=  $tableParams->get( 'csvfullname', 0 );

		$key = str_replace( array('`', $table->db_table_name, "." ), "", $table->db_primary_key );

		//get a list of exisitng primary key vals
		$db =& $tableModel->getDb();
		$db->setQuery( "SELECT $table->db_primary_key FROM $table->db_table_name" );
		$aExistingKeys = $db->loadResultArray();

		$ignore = array( 'fabrik_cursor', 'fabrik_total', '__details_link' );
		$addedCount = 0;
		$updatedCount = 0;
		$joins =& $this->getJoins();
		$joindata = array();
		foreach ($this->data as $data) {
			$aRow = array();
			$pkVal = null;
			for ($i=0; $i < count( $this->matchedHeadings ); $i++) {
				$heading = $this->matchedHeadings[$i];
				
				switch ($csvFullName) {
					case 0:
						break;
					case 1:
						$heading = array_pop( explode( ".", $heading ) );
						break;
					case 2:
						break;
				}
				if (in_array( $heading, $ignore )) {
					continue;
				}
			 //could be that you have two elements (short names) with the 
			 // same name (if trying to import joined data, in this case I'm 
			 //presuming that the master table's pkval is the first one you come to 
				if ($heading == $key && !isset($pkVal) ) {
					$pkVal = $data[$i];
				} else {
					$aRow[$heading] = $data[$i];
				}
			}
			
			$tableModel->getFormGroupElementData();
			$aRow = $tableModel->removeTableNameFromSaveData( $aRow );
			
			//take any _raw values and replace their real elements with their data
			foreach ($aRow as $k=>$val) {
				if (substr( $k, strlen($k)-4, strlen($k) ) == "_raw") {
					$noneraw = substr($k, 0, strlen($k)-4);
					if (array_key_exists( $noneraw, $aRow )) {
						$aRow[$noneraw] = $val;
						unset($aRow[$k]);
					}
				}
			}
			
			
			if ($overWrite && in_array( $pkVal, $aExistingKeys )) {
			  $formModel->_rowId = $pkVal;
			  $updatedCount ++;
			} else {
			  $formModel->_rowId = 0;
			  $addedCount ++;
			}
			if (empty( $joins )) {
			  $formModel->_formData = $aRow;
			  $formModel->processToDB();
			} else {
			  //merge multi line csv into one entry & defer till we've passed everything
			  $joindata = $this->_fakeJoinData( $joindata, $aRow, $pkVal );
			}
		}
		
		if (!empty( $joins )) {
		  $this->insertJoinedData( $joindata );
		}
		$elementsCreated = count($this->newHeadings);
		if ($elementsCreated == 0) {
			$msg = JText::sprintf("%s CSV records added and %s records updated", $addedCount, $updatedCount);
		} else {
			$msg = JText::sprintf("%s new elements added, %s CSV records added and %s records updated", $elementsCreated, $addedCount, $updatedCount);
		}
		return $msg;
	}
	
	/**
	 * once we have itterated over all of the csv file and recreated
	 * the join data, we can finally allow the table's form to proces it 
	 * @param $joindata
	 */
	
	private function insertJoinedData( $joindata )
	{
	  $table =& $this->getTableModel();
	  $form =& $table->getForm();
	  foreach ($joindata as $data) {
	     $form->_formData = $data;
			 $form->processToDB();
	  }
	}
	
	/**
	 * as each csv row is in a single line we need to fake the join data before
	 * sending it of to be processed by the form model
	 * Look at the table model and get all table joins
	 * then insert data into the row
	 * NOTE: will probably only work for a 1:1 join result
	 * 
	 * @param array merged join data
	 * @param array $aRow
	 * @param mixed primary key value
	 * @return array updated join data
	 */
	
	private function _fakeJoinData( $joindata, $aRow, $pkVal )
	{
	  $origData = $aRow;
	  $overWrite	= JRequest::getInt( 'overwrite', 0, 'post' );
		$joins =& $this->getJoins();
		if (!empty( $joins )) {
		  
		  //a new record that will need to be inserted
		  if(!array_key_exists( $pkVal, $joindata )) {
		    $joindata[$pkVal] = array();
		  }
		  
		  foreach ($aRow as $k=>$v) {
		    if (!array_key_exists( $k, $joindata[$pkVal] )) {
		      $joindata[$pkVal][$k] = $v;
		    }
		  }
		  if (!array_key_exists( 'join', $joindata[$pkVal])) {
		    $joindata[$pkVal]['join'] = array();
		  }
		  
		  foreach ($joins as $join) {
		    
			  $keys =& $this->getJoinPkRecords( $join );
			  if ($overWrite && in_array( $pkVal, $keys )) { // not sure 2nd test is right here
				  $origData[$join->table_key] = $pkVal;
				  $updatedCount ++;
				} else {
				  $origData[$join->table_key] = 0;
				  $addedCount ++;
				}
		    $origData[$join->table_join_key] = $pkVal;
		    foreach ($origData as $key => $val) {
		      $joindata[$pkVal]['join'][$join->id][$join->table_join . '___' . $key][] = $val;
		    }
		  }
		}
	  return $joindata;
	}
	
	/**
	 * 
	 * @param object $join
	 * @return unknown_type
	 */
	
	function getJoinPkRecords( $join )
	{
	  $tableModel =& $this->getTableModel();
    $formModel =& $tableModel->getForm();
	  if (!isset( $this->joinpkids )) {
	    $this->joinpkids = array();
	  }
	  if (!array_key_exists( $join->id, $this->joinpkids )) {
	    $db =& $tableModel->getDb();
		  $db->setQuery("SELECT $join->table_key FROM $join->table_join");
		  $this->joinpkids[$join->id] = $db->loadResultArray();
	  }
	  return $this->joinpkids[$join->id];
	}
	
	/**
	 * 
	 * @return unknown_type
	 */
	
	function getJoins()
	{
	  if (!isset( $this->joins )) {
		  $tableModel =& $this->getTableModel();
			//move the join table data into their own array space
			$this->joins 	=& $tableModel->getJoins();
			foreach ($this->joins as $j => $join) {
			  if ($this->joins[$j]->element_id != 0) {
			    unset($this->joins[$j]);
			  }
			}
	  }
	  return $this->joins;
	}
	
	function _makeError()
	{
		$str =  JText::_('SORRY THE FOLLOWNG FIELDS IN THE CSV FILE ARE NOT FOUND IN THE TABLE') . "<ul>";
		foreach ($this->newHeadings as $heading) {
			$str .= "<li>$heading</li>";
		}
		$str .= "<ul>";
		return $str;
	}
}

/********************** */

/**
 * This class will parse a csv file in either standard or MS Excel format.
 * Two methods are provided to either process a line at a time or return the whole csv file as an array.
 *
 * It can deal with:
 * - Line breaks within quoted fields
 * - Character seperator (usually a comma or semicolon) in quoted fields
 * - Can leave or remove leading and trailing \s or \t
 * - Can leave or skip empty rows.
 * - Windows and Unix line breaks dealt with automatically. Care must be taken with Macintosh format.
 *
 * Also, the escape character is automatically removed.
 *
 * NOTICE:
 * - Quote character can be escaped by itself or by using an escape character, within a quoted field (i.e. "" or \" will work)
 *
 * USAGE:
 *
 * include_once 'class.csv_bv.php';
 *
 * $csv = & new csv_bv('test.csv', ';', '"' , '\\');
 * $csv->SkipEmptyRows(TRUE); // Will skip empty rows. TRUE by default. (Shown here for example only).
 * $csv->TrimFields(TRUE); // Remove leading and trailing \s and \t. TRUE by default.
 *
 * while ($arr_data = $csv->NextLine()){
 *
 *         echo "<br><br>Processing line ". $csv->RowCount() . "<br>";
 *         echo implode(' , ', $arr_data);
 *
 * }
 *
 * echo "<br><br>Number of returned rows: ".$csv->RowCount();
 * echo "<br><br>Number of skipped rows: ".$csv->SkippedRowCount();
 *
 * ----
 * OR using the csv2array function.
 * ----
 *
 * include_once 'class.csv_bv.php';
 *
 * $csv = & new csv_bv('test.csv', ';', '"' , '\\');
 * $csv->SkipEmptyRows(TRUE); // Will skip empty rows. TRUE by default. (Shown here for example only).
 * $csv->TrimFields(TRUE); // Remove leading and trailing \s and \t. TRUE by default.
 *
 * $_arr = $csv->csv2Array();
 *
 * echo "<br><br>Number of returned rows: ".$csv->RowCount();
 * echo "<br><br>Number of skipped rows: ".$csv->SkippedRowCount();
 *
 *
 * WARNING:
 * - Macintosh line breaks need to be dealt with carefully. See the PHP help files for the function 'fgetcsv'
 *
 * The coding standards used in this file can be found here: http://www.dagbladet.no/development/phpcodingstandard/
 *
 *    All commets and suggestions are welcomed.
 *
 * SUPPORT: Visit http://vhd.com.au/forum/
 *
 * CHANGELOG:
 *
 * - Fixed skipping of last row if the last row did not have a new line. Thanks to Florian Bruch and Henry Flurry. (2006_05_15)
 * - Changed the class name to csv_bv for consistency. (2006_05_15)
 * - Fixed small problem where line breaks at the end of file returned a warning (2005_10_28)
 *
 * @author Ben Vautier <classes@vhd.com.au>
 * @copyright (c) 2006
 * @license BSD
 * @version 1.2 (2006_05_15)
 */


class csv_bv
{
	/**
	 * Seperator character
	 * @var char
	 * @access private
	 */
	var $mFldSeperator;

	/**
	 * Enclose character
	 * @var char
	 * @access private
	 */
	var $mFldEnclosure;

	/**
	 * Escape character
	 * @var char
	 * @access private
	 */
	var $mFldEscapor;

	/**
	 * Length of the largest row in bytes.Default is 4096
	 * @var int
	 * @access private
	 */
	var $mRowSize;

	/**
	 * Holds the file pointer
	 * @var resource
	 * @access private
	 */
	var $mHandle;

	/**
	 * Counts the number of rows that have been returned
	 * @var int
	 * @access private
	 */
	var $mRowCount;

	/**
	 * Counts the number of empty rows that have been skipped
	 * @var int
	 * @access private
	 */
	var $mSkippedRowCount;

	/**
	 * Determines whether empty rows should be skipped or not.
	 * By default empty rows are returned.
	 * @var boolean
	 * @access private
	 */
	var $mSkipEmptyRows;

	/**
	 * Specifies whether the fields leading and trailing \s and \t should be removed
	 * By default it is TRUE.
	 * @var boolean
	 * @access private
	 */
	var $mTrimFields;

	/**
	 * Constructor
	 *
	 * Only used to initialise variables.
	 *
	 * @param str $file - file path
	 * @param str $seperator - Only one character is allowed (optional)
	 * @param str $enclose - Only one character is allowed (optional)
	 * @param str $escape - Only one character is allowed (optional)
	 * @access public
	 */
	Function csv_bv($file, $seperator = ',', $enclose = '"', $escape = ''){

		$this->mFldSeperator = $seperator;
		$this->mFldEnclosure = $enclose;
		$this->mFldEscapor = $escape;

		$this->mSkipEmptyRows = TRUE;
		$this->mTrimFields =  TRUE;
		$this->htmlentity = true;
		$this->mRowCount = 0;
		$this->mSkippedRowCount = 0;

		$this->mRowSize = 4096;

		// Open file
		$this->mHandle = @fopen($file, "r") or trigger_error('Unable to open csv file', E_USER_ERROR);
	}

	function charset_decode_utf_8 ($string) {
		/* Only do the slow convert if there are 8-bit characters */
		/* avoid using 0xA0 (\240) in ereg ranges. RH73 does not like that */
		if (! ereg("[\200-\237]", $string) and ! ereg("[\241-\377]", $string))
		return $string;

		// decode three byte unicode characters
		$string = preg_replace("/([\340-\357])([\200-\277])([\200-\277])/e",
    "'&#'.((ord('\\1')-224)*4096 + (ord('\\2')-128)*64 + (ord('\\3')-128)).';'",   
		$string);

		// decode two byte unicode characters
		$string = preg_replace("/([\300-\337])([\200-\277])/e",
    "'&#'.((ord('\\1')-192)*64+(ord('\\2')-128)).';'",
		$string);

		return $string;
	}


	/**
	 * csv::NextLine() returns an array of fields from the next csv line.
	 *
	 * The position of the file pointer is stored in PHP internals.
	 *
	 * Empty rows can be skipped
	 * Leading and trailing \s and \t can be removed from each field
	 *
	 * @access public
	 * @return array of fields
	 */
	Function NextLine(){

		if (feof($this->mHandle)){
			return False;
		}

		$arr_row = fgetcsv ($this->mHandle, $this->mRowSize, $this->mFldSeperator, $this->mFldEnclosure);

		$this->mRowCount++;

		//-------------------------
		// Skip empty rows if asked to
		if ($this->mSkipEmptyRows){


			if ($arr_row[0] === ''  && count($arr_row) === 1){

				$this->mRowCount--;
				$this->mSkippedRowCount++;

				$arr_row = $this->NextLine();

				// This is to avoid a warning when empty lines are found at the bvery end of a file.
				if (!is_array($arr_row)){ // This will only happen if we are at the end of a file.
					return FALSE;
				}
			}
		}

		//-------------------------
		// Remove leading and trailing spaces \s and \t
		if ($this->mTrimFields && is_array($arr_row)){
			array_walk($arr_row, array($this, 'ArrayTrim'));
		}

		//-------------------------
		// Remove escape character if it is not empty and different from the enclose character
		// otherwise fgetcsv removes it automatically and we don't have to worry about it.
		if ($this->mFldEscapor !== '' && $this->mFldEscapor !== $this->mFldEnclosure && is_array($arr_row)){
			array_walk($arr_row, array($this, 'ArrayRemoveEscapor'));
		}

		//-------------------------
		// Remove leading and trailing spaces \s and \t
		if ($this->htmlentity && is_array($arr_row)){

			array_walk($arr_row, array($this, 'charset_decode_utf_8'));
			//array_walk($arr_row, array($this, 'htmlentity'));
		}

		return $arr_row;
	}

	/**
	 * csv::Csv2Array will return the whole csv file as 2D array
	 *
	 * @access public
	 */
	Function Csv2Array(){

		$arr_csv = array();

		while ($arr_row = $this->NextLine()){
			$arr_csv[] = $arr_row;
		}

		return $arr_csv;
	}

	/**
	 * csv::ArrayTrim will remove \s and \t from an array
	 *
	 * It is called from array_walk.
	 * @access private
	 */
	Function ArrayTrim(&$item, $key){
		$item = trim($item, " \t"); // space and tab
	}

	/**
	 * csv::ArrayRemoveEscapor will escape the enclose character
	 *
	 * It is called from array_walk.
	 * @access private
	 */
	Function ArrayRemoveEscapor(&$item, $key){
		$item = str_replace($this->mFldEscapor.$this->mFldEnclosure, $this->mFldEnclosure, $item);
	}

	function htmlentity(&$item, $key)
	{
		$item = htmlentities($item);
	}

	/**
	 * csv::RowCount return the current row count
	 *
	 * @access public
	 * @return int
	 */
	Function RowCount(){
		return $this->mRowCount;
	}

	/**
	 * csv::RowCount return the current skipped row count
	 *
	 * @access public
	 * @return int
	 */
	Function SkippedRowCount(){
		return $this->mSkippedRowCount;
	}

	/**
	 * csv::SkipEmptyRows, sets whether empty rows should be skipped or not
	 *
	 * @access public
	 * @param bool $bool
	 * @return void
	 */
	Function SkipEmptyRows($bool = TRUE){
		$this->mSkipEmptyRows = $bool;
	}

	/**
	 * csv::TrimFields, sets whether fields should have their \s and \t removed.
	 *
	 * @access public
	 * @param bool $bool
	 * @return void
	 */
	Function TrimFields($bool = TRUE){
		$this->mTrimFields = $bool;
	}

}

/************************/


?>