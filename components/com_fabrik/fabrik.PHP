<?php
/**
 * @package Joomla
 * @subpackage Fabrik
 * @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport( 'joomla.application.component.helper' );

define( "COM_FABRIK_BASE",  JPATH_BASE );
define( "COM_FABRIK_FRONTEND",  JPATH_BASE.DS.'components'.DS.'com_fabrik' );
define( "COM_FABRIK_LIVESITE",  JURI::base() );

/** php 4.? compat */

if (version_compare( phpversion(), '5.0.0', '<')) {
    echo 'Sorry you are using ' .  phpversion() . ". You need to have PHP5 installed to run Fabrik\n";
    return;
}

require_once( JPATH_COMPONENT.DS.'controller.php' );
require_once( JPATH_COMPONENT.DS.'models'.DS.'parent.php' );

JModel::addIncludePath( COM_FABRIK_BASE.DS.'components'.DS.'com_fabrik'.DS.'models' );

$controllerName = JRequest::getVar('controller');
//check for a plugin controller

//call a plugin controller via the url :
// &c=visualization.calendar

$isplugin = false;

if (strpos($controllerName, '.') != false)
{
	list($type, $name) = explode('.', $controllerName);
	$path = JPATH_COMPONENT.DS.'plugins'.DS.$type.DS.$name.DS.'controllers'.DS.$name.'.php';
	if (file_exists($path)) {
		require_once $path;
		$isplugin = true;
		$controller = $type.$name;
	} else {
		$controller = '';
	}
	
} else {
	// its not a plugin
	// map controller to view - load if exists
	
	//$$$ROB was a simple $controller = view, which was giving an error when trying to save a popup 
	//form to the calendar viz
	//May simply be the best idea to remove main contoller and have different controllers for each view
	
	//hack for package
	if (JRequest::getWord('view') == 'package' || JRequest::getWord('view') == 'table') {
		$controller = JRequest::getWord('view');
	} else {
		$controller = $controllerName;
	}
	
	//if($controller != JRequest::getWord('view')) {
		$path = JPATH_COMPONENT.DS.'controllers'.DS.$controller.'.php';
		if (file_exists($path)) {
			require_once $path;
		} else {
			$controller = '';
		}
	//}
}
// Create the controller
$classname	= 'FabrikController'.ucfirst($controller);
$task = JRequest::getVar( 'task', null, 'default', 'cmd' );

$controller = new $classname();

if ($isplugin) {
	//add in plugin view
	$controller->addViewPath( JPATH_COMPONENT.DS.'plugins'.DS.$type.DS.$name.DS.'views' );
	//add the model path
	$modelpaths = JModel::addIncludePath( JPATH_COMPONENT.DS.'plugins'.DS.$type.DS.$name.DS.'models' );
}

// Perform the Request task
$controller->execute( $task );

// Redirect if set by the controller
$controller->redirect();

?>