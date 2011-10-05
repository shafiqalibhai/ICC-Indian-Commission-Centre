<?php
/**
 * @version
 * @package Joomla
 * @subpackage Fabrik
 * @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );
if (!defined('COM_FABRIK_BASE')) {
	define( "COM_FABRIK_BASE",  JPATH_BASE );
	define( "COM_FABRIK_FRONTEND",  JPATH_BASE.DS.'components'.DS.'com_fabrik' );
	define( "COM_FABRIK_LIVESITE",  JURI::base() );
}
JModel::addIncludePath( COM_FABRIK_FRONTEND.DS.'models' );

global $mainframe;
//load front end language file as well
$lang =& JFactory::getLanguage();
$lang->load( 'com_fabrik' );

require_once('components/com_fabrik/controller.php');
require_once('components/com_fabrik/controllers/table.php');
require_once('components/com_fabrik/views/table/view.html.php');
require_once('components/com_fabrik/views/package/view.html.php');
require_once( COM_FABRIK_FRONTEND.DS.'models'.DS.'parent.php' );


JModel::addIncludePath( COM_FABRIK_FRONTEND.DS.'models' );
JTable::addIncludePath( COM_FABRIK_BASE.DS.'administrator'.DS.'components'.DS.'com_fabrik'.DS.'tables' );
$document =& JFactory::getDocument();


require_once('components/com_fabrik/controllers/package.php');
require_once('components/com_fabrik/views/form/view.html.php');

$tableId			= intval( $params->get( 'table_id', 1 ) );
$useajax			= intval( $params->get('useajax', 0));
$random 			= intval( $params->get('radomizerecords', 0) );
$limit				= intval( $params->get( 'limit', 0));
$origLayout 	= JRequest::getVar( 'layout' );
$layout				=  $params->get('fabriklayout', '');
if($layout != '') {
	JRequest::setVar('layout', $layout);
}
if($limit !== 0){
	
	$mainframe->setUserState('com_fabrik.table.'.$tableId.'.list.limitLength', $limit);
	JRequest::setVar('limit', $limit);
}
/*this all works fine for a table
 * going to try to load a package so u can access the form and table
 */
if (!$useajax) { 
	$tableId				= intval( $params->get( 'table_id', 1 ) );
	$moduleclass_sfx 	= $params->get( 'moduleclass_sfx', '' );
	
	
	$viewName = 'table';
	$viewType	= $document->getType();
	$controller = new FabrikControllerTable();
	
	// Set the default view name from the Request
	$view = &$controller->getView( $viewName, $viewType );
	
	// Push a model into the view
	$model	= &$controller->getModel( $viewName );
	$model->_randomRecords = $random;
	//$model->_postMethod = 'ajax';
	if (!JError::isError( $model )) {
		$model->setAdmin( false );
		$view->setModel( $model, true );
	}
	$view->_isMambot = true;
	// Display the view
	$view->assign( 'error', $controller->getError() );
	$view->setId( $tableId );
	echo $view->display();
} else {
	
	$moduleclass_sfx 	= $params->get( 'moduleclass_sfx', '' );
	
	$document =& JFactory::getDocument();
	
	$viewName	= 'Package';
	
	$viewType	= $document->getType();
	
	$controller =& new FabrikControllerPackage();
	
	// Set the default view name from the Request
	$view = &$controller->getView( $viewName, $viewType );
	
	//if the view is a package create and assign the table and form views
	$tableView = &$controller->getView( 'Table', $viewType );
	$tableModel =& $controller->getModel( 'Table' );
	$tableModel->_randomRecords = $random;
	$tableView->setModel( $tableModel, true );
	$view->_tableView =& $tableView;
	
	$view->_formView = &$controller->getView( 'Form', $viewType );
	$formModel =& $controller->getModel( 'Form' );
	
	$view->_formView->setModel( $formModel, true );
	
	// Push a model into the view
	$model	= &$controller->getModel( $viewName );
	$package =& $model->getPackage();
	$package->tables = $tableId;
	$package->template = 'module';
	
	if (!JError::isError( $model )) {
		$model->setAdmin( false );
		$view->setModel( $model, true );
	}
	$view->_isMambot = true;
	// Display the view
	$view->assign( 'error', $this->getError() );
	
	//force the module layout for the package
	
	//push some data into the model
	$divid = "fabrikModule_table_$tableId";
	echo "<div id='$divid'>";
	echo $view->display();
	echo "</div>";
	
	FabrikHelperHTML::script( 'tablemodule.js', 'modules/mod_fabrik_table/', true );
	$fbConfig =& JComponentHelper::getParams( 'com_fabrik' );
	$opts = new stdClass();
	$opts->mooversion = ($fbConfig->get( 'usefabrik_mootools', false )) ? 1.2 : 1.1;
	$opts = FastJSON::encode($opts);
	$script  = "var oFabrikTableModule = new fabrikTableModule('$divid', $opts);\n";
	$script .= "oPackage.addBlock('$divid', oFabrikTableModule);\n";
	$document->addScriptDeclaration($script);
}

JRequest::setVar('layout', $origLayout);
?>