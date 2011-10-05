<?php
/**
 * @package Joomla
 * @subpackage Fabrik
 * @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();
$db = JFactory::getDBO();

$to = JRequest::getVar('eadtu_messages___recipient_id');


$sql = "SELECT id, name, email FROM jos_users where id = $to";
$db->setQuery($sql);
$to = $db->loadObject();
$user = JFactory::getUser();

global $mainframe;

$MailFrom	= $mainframe->getCfg('mailfrom');
$FromName	= $mainframe->getCfg('fromname');
$SiteName	= $mainframe->getCfg('sitename');

$subject = "$SiteName: new private message";

$projectid = JRequest::getVar('eadtu_project___id');
$url = COM_FABRIK_LIVESITE .JRoute::_('index.php?option=com_fabrik&c=form&view=details&Itemid=112&fabrik=2&rowid='.$projectid.'&tableid=2');

$message = "Dear %s,<br /> 
<p>With this mail we want to notify you that a new private message has been 
written and is available here. 
<a href='$url'>http://matchmaking.eadtu.nl</a><p>";

$thismsg = sprintf($message, $to->name);
$res = JUtility::sendMail( $MailFrom, $FromName, $to->email, $subject, $thismsg, true );


?>