<?php
/**
* @package Joomla
* @subpackage Fabrik
* @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/


defined( '_JEXEC' ) or die( 'Restricted access' );

if (version_compare( phpversion(), '5.0.0', '<')) {
    echo 'Sorry you are using ' .  phpversion() . ". You need to have PHP5 installed to run Fabrik\n";
    return;
}

global $_SESSION;

// Set the table directory
JTable::addIncludePath( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_fabrik'.DS.'tables' );

$controllerName = JRequest::getCmd( 'c', 'home' );

define( "COM_FABRIK_BASE", str_replace(DS.'administrator', '', JPATH_BASE) );
define( "COM_FABRIK_FRONTEND", str_replace( DS.'administrator', '', JPATH_BASE).DS.'components'.DS.'com_fabrik' );
define( "COM_FABRIK_LIVESITE", str_replace( 'administrator', '', JURI::base() ) );
//just until joomla uses mootools 1.2
jimport( 'joomla.html.editor' );
require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'editor.php' );
//end mootools 1.2 

//add the helpers directory

require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'params.php' );
require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'string.php' );
require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'html.php' );
require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'parent.php' );
require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'cache.php' );

//load front end language file as well
$lang =& JFactory::getLanguage();
$lang->load( 'com_fabrik', COM_FABRIK_BASE );


$task = JRequest::getCmd('task');
//echo $task;

require_once( JPATH_COMPONENT.DS.'controllers'.DS.$controllerName.'.php' );
require_once( COM_FABRIK_FRONTEND.DS.'models'.DS.'parent.php' );


$config = array();
if ($controllerName == 'table' || $controllerName == 'form') {
	$config['view_path'] =  COM_FABRIK_FRONTEND . DS . 'views' ;
}

$controllerName = 'FabrikController'.$controllerName;

$fbConfig =& JComponentHelper::getParams( 'com_fabrik' );
if (!$fbConfig->get('use_wip') && $controllerName === 'FabrikControllerpackage'){
  global $mainframe;
  $mainframe->enqueueMessage( JText::_('PACKAGE_WIP'), 'notice' );
  return;
}

 JHTML::stylesheet( 'headings.css', 'administrator/components/com_fabrik/' );
// Create the controller
$controller = new $controllerName( $config );


// Perform the Request task
$controller->execute( $task );

// Redirect if set by the controller
$controller->redirect();

?>