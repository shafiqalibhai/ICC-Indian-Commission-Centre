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
 * Fabrik From Controller
 *
 * @static
 * @package		Joomla
 * @subpackage	Fabrik
 * @since 1.5
 */
class FabrikControllerForm extends JController 
{
  
  var $_isMambot = false;
  
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

		$viewName	= JRequest::getVar( 'view', 'form', 'default', 'cmd' );
		$modelName = $viewName;
		if ($viewName == 'emailform') {
			$modelName = 'form';
		}
		
		if ($viewName == 'details') {
			//huh why was this here? - stopped detailed view from ever ever being loaded
			//JRequest::setVar( 'view', 'form' ); 
			$viewName = 'form';
			$modelName = 'form';
		}

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
		$cachable = false;
		if(($viewName = 'form' || $viewName = 'details') ){// && && JRequest::getVar('rowid') == 0
			$cachable = true;
		}

		if ($viewType != 'feed' && !$this->_isMambot) {
			$cache =& JFactory::getCache('com_fabrik', 'view');
			$cache->get($view, 'display');
		} else {
			return $view->display();
		}
	}
	
		/**
	 * process the form
	 */

	function processForm()
	{
		global $mainframe;
		@set_time_limit(300);
		$document =& JFactory::getDocument();
		$viewName	= JRequest::getVar( 'view', 'form', 'default', 'cmd' );
		$viewType	= $document->getType();
		$view 		= &$this->getView( $viewName, $viewType );
		$model		= &$this->getModel( 'form' );

		if (!JError::isError( $model )) {
			$view->setModel( $model, true );
		}
		$model->getPostMethod();
		$model->setId( JRequest::getInt( 'form_id', 0 ) );

		$model->getForm();
		$model->_rowId = JRequest::getVar( 'rowid', '' );
		$model->setAdmin( false );
		// Check for request forgeries
		$fbConfig =& JComponentHelper::getParams( 'com_fabrik' );
		if ($fbConfig->get( 'spoofcheck_on_formsubmission', true )) {
			JRequest::checkToken() or die( 'Invalid Token' );
		}
		if (JRequest::getVar('fabrik_ignorevalidation', 0) != 1) { //put in when saving page of form
			if (!$model->validate()) {
				$view->display();
				return;
			}
		}
		//reset errors as validate() now returns ok validations as empty arrays
		$model->_arErrors = array(); 
		$defaultAction = $model->process();
		//check if any plugin has created a new validation error

		if (!empty( $model->_arErrors )) {
			$pluginManager 	=& $model->getPluginManager();
			$pluginManager->runPlugins( 'onError', $model );
			$view->display();
			return;
		}

		//one of the plugins returned false stopping the default redirect
		// action from taking place
		if (!$defaultAction) {
			return;
		}
		$tableModel				=& $model->getTableModel();
		$tableModel->_table = null;

		if (JRequest::getVar( 'format' ) == 'raw') {
			JRequest::setVar( 'view', 'table' );
			$this->display();
			return;
		} else {
				
			if ($this->_admin) {
				if ($return == 1) {
					$page = "index.php?option=com_fabrik&task=showForm&cid=$cid&tableid=".$model->_table->id."&rowid=";
				} else {
					$page = "index.php?option=com_fabrik&c=table&task=viewTable&cid[]=".$model->_table->id;
				}
				$mainframe->redirect( $page, JText::_('RECORD ADDED/UPDATED') );
			} else {
				$ref = JRequest::getVar( 'fabrik_referrer', "index.php", 'post' );
				global $Itemid;
				if ($ref == '') {
					$ref = "index.php?option=com_fabrik&Itemid=$Itemid";
				}
				$mainframe->redirect( JRoute::_($ref), JText::_( 'RECORD ADDED/UPDATED' ) );
			}
		}
	}

	/**
	 * validate via ajax
	 *
	 */

	function ajax_validate()
	{
		//$activeElement = JRequest::getVar( '_activeElement_id' );
		$model	= &$this->getModel( 'form' );
		$model->setId( JRequest::getInt( 'form_id', 0 ) );
		$model->getForm();
		$model->_rowId = JRequest::getVar( 'rowid', '' );
		$model->validate();
		
		$data = array( 'modified' => $model->_modifiedValidationData );
		/*if (array_key_exists( $activeElement, $model->_arErrors )) {
			$data['errors'] = $model->_arErrors[$activeElement];
		} else {*/
			//validating entire group when navigating form pages
			$data['errors'] = $model->_arErrors;
		//}
		echo FastJSON::encode($data);
	}
	

	/**
	 * save a form's page to the session table
	 */

	function savepage()
	{
		$model		=& $this->getModel( 'Formsession' );
		$model->savePage();
	}

	/**
	 * clear down any temp db records or cookies
	 * containing partially filled in form data
	 */

	function removeSession()
	{
		$sessionModel =& $this->getModel( 'formsession' );
		$sessionModel->setFormId( JRequest::getInt( 'form_id', 0 ) );
		$sessionModel->setRowId( JRequest::getVar( 'rowid', '' ) );
		$sessionModel->remove();
		$this->display();
	}

}
?>