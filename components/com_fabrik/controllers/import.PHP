<?php
/**
 * Fabrik Import Controller
 * @package Joomla
 * @subpackage Fabrik
 * @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport( 'joomla.application.component.controller' );

require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'params.php' );
require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'string.php' );
require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'html.php' );
require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'cache.php' );
//just until joomla uses mootools 1.2
jimport( 'joomla.html.editor' );
require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'editor.php' );
//end mootools 1.2


class FabrikControllerImport extends JController
{
	/**
	 * Display the view
	 */
	function display()
	{
		$this->tableid = JRequest::getVar( 'tableid', 0 );
		$tableModel =& $this->getModel( 'table' );
		$tableModel->setId( $this->tableid );
		$this->table =& $tableModel->getTable();
		$document =& JFactory::getDocument();
		$viewName	= JRequest::getVar( 'view', 'form', 'default', 'cmd' );
		$viewType	= $document->getType();
		// Set the default view name from the Request
		$view = &$this->getView( $viewName, $viewType );
		$view->display();
	}

	function doimport()
	{
		$model				= &$this->getModel( 'Importcsv' );
		/*$tableModel		=& $this->getModel( 'Table' );
		$tableModel->setId( JRequest::getVar( 'tableid' ) );
		$this->table 	=& $tableModel->getTable();*/
		if (!$model->checkUpload()) {
			$this->display();
		}
		$userfile = JRequest::getVar( 'userfile', null, 'files' );
		$model->readCSV( $userfile['name'] );

		$model->findExistingElements();
    $tableModel =& $model->getTableModel();
    $table =& $tableModel->getTable();
		/*$this->newHeadings 			=& $model->newHeadings;
		$this->headings 				=& $model->headings;
		$this->data 						= $model->data;
		$this->matchedHeadings 	=& $model->matchedHeadings;*/
		$document =& JFactory::getDocument();
		$viewName	= JRequest::getVar( 'view', 'form', 'default', 'cmd' );
		$viewType	= $document->getType();
		// Set the default view name from the Request
		$view = &$this->getView( $viewName, $viewType );
		if (!empty( $model->newHeadings ))
		{
			//as opposed to admin you can't alter table structure with a CSV import
			//from the front end
			JError::raiseNotice( 500, $model->_makeError() );
			$this->setRedirect( "index.php?option=com_fabrik&c=import&view=import&fietype=csv&tableid=" . $table->id );
		} else {
			JRequest::setVar( 'fabrik_table', $table->id );
			$msg = $model->makeTableFromCSV();
			$this->setRedirect( 'index.php?option=com_fabrik&view=table&tableid='.$table->id, $msg );
		}
	}


	
}
?>