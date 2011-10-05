<?php
/**
 * @version
 * @package Joomla
 * @subpackage Fabrik
 * @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

/**
 * @package		Joomla
 * @subpackage	Fabrik
 * @license		GNU/GPL, see LICENSE.php
 */

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.application.component.controller' );
jimport( 'joomla.application.component.model' );

require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'params.php' );
require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'json.php' );
require_once( COM_FABRIK_BASE.DS.'administrator'.DS.'components'.DS.'com_fabrik'.DS.'helpers'.DS.'menu.php' );
require_once( COM_FABRIK_BASE.DS.'administrator'.DS.'components'.DS.'com_fabrik'.DS.'helpers'.DS.'adminhtml.php' );
require_once( COM_FABRIK_BASE.DS.'administrator'.DS.'components'.DS.'com_fabrik'.DS.'helpers'.DS.'fabrik.php' );
JModel::addIncludePath( COM_FABRIK_FRONTEND.DS.'models' );

require_once( JPATH_COMPONENT.DS.'views'.DS.'import.php' );
/**
 * @package		Joomla
 * @subpackage	Fabrik
 */

class FabrikControllerImport extends JController
{

	/**
	 * Constructor
	 */
	function __construct( $config = array() )
	{
		parent::__construct( $config );

	}

	function cancel()
	{
		$this->setRedirect( 'index.php?option=com_fabrik&c=table' );
	}

	function makeTableFromCSV()
	{
		
		$session 		=& JFactory::getSession();
		$model			=& $this->getModel( 'Importcsv' );
		$user 			=& JFactory::getUser();
			
		$dropData		= JRequest::getInt( 'drop_data', 0, 'post' );
		$overWrite	= JRequest::getInt( 'overwrite', 0, 'post' );
		$tableModel	=& $this->getModel( 'Table' );
		$tableModel->setId( JRequest::getVar( 'fabrik_table' ) );
		$tableModel->getTable();
		$formModel 	=& $tableModel->getForm();
		$groupId 		= current(array_keys( $formModel->getGroupsHiarachy() ));

		$newElements 	= JRequest::getVar( 'createElements', array() );
		$plugins 			= JRequest::getVar( 'plugin' );
		$matchedHeadings = $session->get( 'com_fabrik.matchedHeadings' );
		$c = 0;
		$elementModel =& $this->getModel('element');
		$element =& $elementModel->getElement();
		$elementsCreated = 0;

		foreach ($newElements as $elname => $add) {
			if ($add) {
				$element->name = JFilterInput::clean($elname);
				$element->label 	= strtolower($elname);
				$element->plugin = $plugins[$c];

				$element->group_id 			= $groupId;
				$element->eval 					= 0;
				$element->state 				= 1;
				$element->width 				= 255;
				$element->created 			= date( 'Y-m-d' );
				$element->created_by 		= $user->get('id');
				$element->created_by_alias 	= $user->get('username');
				$element->checked_out 	= 0;
				$element->show_in_table_summary = 1;
				$element->ordering 			= 0;
				$matchedHeadings[] = $element->name;
				$element->store();
				$where = " group_id = '" . $element->group_id . "'";
				$element->move( 1, $where );
				$elementModel->addToDBTable();
				$elementsCreated ++;
			}
			$c ++;
		}

		if ($dropData) {
			$tableModel->truncate();
		}

		$table =& $tableModel->getTable();
		$tableParams =& $tableModel->getParams();
		$csvFullName =  $tableParams->get( 'csvfullname', 0) ;

		$key = str_replace( array('`', $table->db_table_name, "." ), "", $table->db_primary_key );

		//get a list of exisitng primary key vals
		$db =& $tableModel->getDb();
		$db->setQuery( "SELECT $table->db_primary_key FROM $table->db_table_name" );
		$aExistingKeys = $db->loadResultArray();

		$csvdata = $session->get( 'com_fabrik.csvdata' );
		$ignore = array('fabrik_cursor', 'fabrik_total', '__details_link' );
		$addedCount = 0;
		$updatedCount = 0;
		foreach ($csvdata as $data) {
			$aRow = array();
			for ($i=0; $i < count( $matchedHeadings ); $i++) {
				$heading = $matchedHeadings[$i];
				switch( $csvFullName ){
					case 0:
						break;
					case 1:
						$heading = array_pop( explode( ".", $heading ) );
						break;
				}
				if (in_array( $heading, $ignore )) {
					continue;
				}
				
				if ($heading == $key ) {
					$pkVal = $data[$i];
				} else {
					$aRow[$heading] = $data[$i];
				}
			}
			$aRow = $tableModel->removeTableNameFromSaveData( $aRow );

			if ($overWrite && in_array( $pkVal, $aExistingKeys )) {
				$tableModel->storeRow( $aRow, $pkVal );
				$updatedCount ++;
			} else {
				$tableModel->storeRow( $aRow, 0 );
				$addedCount ++;
			}
		}
		$this->setRedirect('index.php?option=com_fabrik&c=table', JText::sprintf("%s new elements added, %s CSV records added and %s records updated", $elementsCreated, $addedCount, $updatedCount));
	}

	function doimport()
	{
		$model				= &$this->getModel( 'Importcsv' );
		$tableModel		=& $this->getModel( 'Table' );
		$tableModel->setId( JRequest::getVar( 'tableid' ) );
		$this->table 	=& $tableModel->getTable();
		if (!$model->checkUpload()) {
			$this->display();
		}
		$userfile = JRequest::getVar( 'userfile', null, 'files' );
		$model->readCSV( $userfile['name'] );

		$model->findExistingElements( $tableModel );
		
		$this->newHeadings 			=& $model->newHeadings;
		$this->headings 				=& $model->headings;
		$this->data 						= $model->data;
		$this->matchedHeadings 	=& $model->matchedHeadings;
		
		$session =& JFactory::getSession();
		$session->set( 'com_fabrik.csvdata', $this->data );
		$session->set( 'com_fabrik.matchedHeadings', $this->matchedHeadings );
		
		if (!empty($model->newHeadings))
		{
			$pluginManager =& $this->getModel( 'pluginmanager' );
			$elementTypes =$pluginManager->getElementTypeDd( 'fabrikfield', 'plugin[]' );
			FabrikViewImport::csvChooseElementTypes( $elementTypes );
		} else {
			JRequest::setVar( 'fabrik_table', $this->table->id );
			$this->makeTableFromCSV();
		}
	}

	function display()
	{
		$this->tableid = JRequest::getVar( 'tableid', 0 );
		$tableModel =& JModel::getInstance( 'Table', 'FabrikModel' );
		$tableModel->setId( $this->tableid );
		$this->table =& $tableModel->getTable();
		FabrikViewImport::import();
			
	}
}
?>