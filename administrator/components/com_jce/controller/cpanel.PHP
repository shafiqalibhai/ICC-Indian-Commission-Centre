<?php
/**
* @version		$Id: admin.jce.php 7320 2007-05-03 19:02:52Z jinx $
* @package		Joomla
* @subpackage	Plugins
* @copyright	Copyright (C) 2005 - 2007 Open Source Matters. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* Joomla! is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

// Controller
require_once( JPATH_COMPONENT .DS. 'cpanel' .DS. 'controller.php' );

// Create the controller
$controller	= new CpanelController( array(
	'base_path' 	=>  JPATH_COMPONENT .DS. 'cpanel' ,
	'template_path' =>  JPATH_COMPONENT .DS. 'cpanel' .DS. 'views' .DS. 'tmpl'
) );

$controller->execute( JRequest::getCmd( 'task' ) );
$controller->redirect();
?>
