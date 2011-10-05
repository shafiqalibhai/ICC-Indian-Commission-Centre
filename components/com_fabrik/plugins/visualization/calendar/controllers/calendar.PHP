<?php
/**
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

/**
 * Contact Component Controller
 *
 * @static
 * @package		Joomla
 * @subpackage	Contact
 * @since 1.5
 */
class FabrikControllerVisualizationcalendar extends JController
{
	/**
	 * Display the view
	 */
	function display()
	{
		$document =& JFactory::getDocument();

		//$viewName	= JRequest::getVar( 'view', 'form', 'default', 'cmd' );
		$viewName = 'calendar';
	
		$viewType	= $document->getType();

		// Set the default view name from the Request
		$view = &$this->getView( $viewName, $viewType );
		
		//create a form view as well to render the add event form.
		$view->_formView = &$this->getView( 'Form', $viewType );
		
		$formModel =& $this->getModel( 'Form' );
		$view->_formView->setModel( $formModel, true );
			
		// Push a model into the view
		$model	= &$this->getModel( $viewName );

		if (!JError::isError( $model )) {
			$model->setAdmin( false );
			$view->setModel( $model, true );
		}
		// Display the view
		$view->assign( 'error', $this->getError() );
		return $view->display();
	}
	
	function deleteEvent()
	{
		$model	= &$this->getModel( 'calendar' );
		$model->deleteEvent();
		$this->getEvents();
	}
	
	function getEvents()
	{
		$viewName = 'calendar';
		global $mainframe;
		$usersConfig = &JComponentHelper::getParams( 'com_fabrik' );
		$model	= &$this->getModel( $viewName );
		$id = JRequest::getInt('id', $usersConfig->get( 'visualizationid', JRequest::getInt('visualizationid', 0) ), 'get' );
		$model->setId( $id );
		echo $model->getEvents();
	}
	
	function chooseaddevent()
	{
		$document =& JFactory::getDocument();
		$viewName = 'calendar';
	
		$viewType	= $document->getType();
		$formModel =& $this->getModel( 'Form' );
		
		// Push a model into the view
		$model	= &$this->getModel( $viewName );
		
		// Set the default view name from the Request
		$view = &$this->getView( $viewName, $viewType );
		$view->setModel( $formModel );
		$view->setModel( $model, true );
		$view->chooseaddevent();
	}
	
	function addEvForm()
	{
		$tableid = JRequest::getInt('tableid');
		$viewName = 'calendar';
		$usersConfig = &JComponentHelper::getParams( 'com_fabrik' );
		$model	= &$this->getModel( $viewName );
		$model->setId( JRequest::getVar('id', $usersConfig->get( 'visualizationid', JRequest::getInt('visualizationid', 0) ) ));
		//$eventTables =& $model->getEventTables();
		$model->setupEvents();
		if (array_key_exists($tableid, $model->_events)){
			$datefield = $model->_events[$tableid][0]['startdate'];
		} else {
			$config =& JFactory::getConfig();
			$prefix = $config->getValue('config.dbprefix');
			$datefield = $prefix.'fabrik_calendar_events___start_date';
		}
		$rowid = JRequest::getInt('rowid');
		$tableModel =& JModel::getInstance( 'Table', 'FabrikModel' );
		$tableModel->setAdmin( false );
		$tableModel->setId($tableid);
		$table =& $tableModel->getTable();
		JRequest::setVar('view', 'form');
		JRequest::setVar('fabrik', $table->form_id);
		JRequest::setVar('tmpl', 'component');
		JRequest::setVar('_postMethod', 'ajax');
		$link = 'index.php?option=com_fabrik&view=form&fabrik='.$table->form_id.'&rowid='.$rowid.'&tmpl=component&_postMethod=ajax';
		$link .= '&jos_fabrik_calendar_events___visualization_id=' . JRequest::getInt('jos_fabrik_calendar_events___visualization_id');
		$link .= "&$datefield=".JRequest::getVar('start_date');
		$this->setRedirect($link);
	}
}
?>
