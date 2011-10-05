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
$post = JRequest::get('post');


$blogid = JRequest::getVar('eadtu_blog_comments___project_blog_id');
$db->setQuery("select project_id from eadtu_project_blog where id = $blogid");
$projectId = $db->loadResult();

$sql = "SELECT u.id, u.name, u.email, p.title, p.owner_id FROM `eadtu_project` as p 
left join jos_users as u on u.id = p.owner_id
where p.id = $projectId";
$db->setQuery($sql);
$owner = $db->loadObject();
$sql = "SELECT u.id, u.name, u.email, p.title, p.owner_id FROM `eadtu_project` as p 
left join eadtu_project_applicant as a
on a.project_id = p.id
left join jos_users as u on u.id = a.student_id
where p.id = $projectId
and company_accepted = 1 and student_accepted = 1";

$db->setQuery($sql);
$students = $db->loadObjectList();

$user = JFactory::getUser();


global $mainframe;

$MailFrom	= $mainframe->getCfg('mailfrom');
$FromName	= $mainframe->getCfg('fromname');
$SiteName	= $mainframe->getCfg('sitename');
$subject = "$SiteName: new blog comment posted";

$projectid = JRequest::getVar('eadtu_project___id');
$url = COM_FABRIK_LIVESITE .JRoute::_('index.php?option=com_fabrik&c=form&view=details&Itemid=112&fabrik=2&rowid='.$projectid.'&tableid=2');
$message = "Dear %s,<br /> 
<p>With this mail we want to notify you that a new blog comment has been written and is available here. 
<a href='$url'>http://matchmaking.eadtu.nl</a><p>";
if( $owner->id != $user->get('id')){
	$thismsg = sprintf($message, $owner->name);
	$res = JUtility::sendMail( $MailFrom, $FromName, $owner->email, $subject, $thismsg, true );
}

foreach($students as $r){
	if( $r->id != $user->get('id')){
		$thismsg = sprintf($message, $r->name);
		$res = JUtility::sendMail( $MailFrom, $FromName, $r->email, $subject, $thismsg, true );
	}
}
?>