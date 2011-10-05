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
require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'html.php' );
require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'cache.php' );
//just until joomla uses mootools 1.2
jimport( 'joomla.html.editor' );
require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'editor.php' );
//end mootools 1.2

/**
 * Fabrik Table Controller
 *
 * @static
 * @package		Joomla
 * @subpackage	Fabrik
 * @since 1.5
 */
class FabrikControllerTable extends JController
{
	/**
	 * Display the view
	 */
	function display()
	{
		//menu links use fabriklayout parameters rather than layout
		$flayout = JRequest::getVar( 'fabriklayout' );
		if ($flayout != '') {
			JRequest::setVar( 'layout', $flayout );
		}

		$document =& JFactory::getDocument();

		$viewName	= JRequest::getVar( 'view', 'table', 'default', 'cmd' );
		$modelName = $viewName;

		$viewType	= $document->getType();
		// Set the default view name from the Request
		$view = &$this->getView( $viewName, $viewType );

		// Push a model into the view
		$model	= &$this->getModel( $modelName );
		if (!JError::isError( $model ) && is_object( $model)) {
			if (method_exists('setAdmin', $model)) {
				$model->setAdmin( false );
			}
			$view->setModel( $model, true );
		}

		// Display the view
		$view->assign( 'error', $this->getError() );
		return $view->display();
	}
	
	/**
	 * delete rows from table
	 */

	function delete()
	{
		// Check for request forgeries
		JRequest::checkToken() or die( 'Invalid Token' );
		global $mainframe;
		$model	= &$this->getModel( 'table' );
		$ids = JRequest::getVar('ids', array(), 'request', 'array');
		$model->deleteRows( $ids );
		if ( JRequest::getVar('format') == 'raw') {
			JRequest::setVar( 'view', 'table' );
			$this->display();
		} else {
			//@TODO: test this
			$ref = JRequest::getVar( 'fabrik_referrer', "index.php", 'post' );
			$mainframe->redirect( $ref, count($ids) . " " . JText::_( 'RECORDS DELETED' ) );
		}
	}
	
	/**
	 * empty a table of records and reset its key to 0
	 */
	
	function doempty()
	{
		$model	= &$this->getModel( 'table' );
		$model->truncate();
		$this->display();
	}
	

	/**
	 * run a table plugin
	 */

	function doPlugin()
	{
		$cid	= JRequest::getVar( 'cid', array(0), 'method', 'array' );
		if(is_array($cid)){$cid = $cid[0];}
		$model	= &$this->getModel( 'table' );
		$model->setId( JRequest::getInt( 'tableid', $cid) );
		$msg = $model->processPlugin();
		if (JRequest::getVar('format') == 'raw') {
			JRequest::setVar( 'view', 'table' );
			$this->display();
		} else {
			$this->setRedirect( 'index.php?'.JRequest::getVar( 'pageURL' ) );
			$this->setMessage( $msg );
		}
	}

}
?>