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

$where = array();
$eduLevel = JRequest::getVar('eadtu_project___education_level_id');
if($eduLevel != ''){
	$where[] = "education_level_id = $eduLevel";
}
$eduSector = JRequest::getVar('eadtu_project___education_type_id');
if($eduSector != ''){
	$where[] = "education_type_id = $eduSector";
}


$eduCourse = JRequest::getVar('eadtu_project___sub_education_type_id');
if($eduCourse != ''){
	$where[] = "sub_education_type_id = $eduCourse";
}
$where[] = " (student_accepted =0 or student_accepted is NULL)";

$sql = "select u.name, u.email from eadtu_student as s ";
$sql .= " inner join #__users as u on u.id = s.user_id";
$sql .= " LEFT join eadtu_project_applicant as a on a.student_id = u.id";
if(!empty($where)){
	$sql .= " WHERE " .implode(" AND ", $where);
}
$db->setQuery($sql);
$res = $db->loadObjectList();
global $mainframe;

$MailFrom	= $mainframe->getCfg('mailfrom');
$FromName	= $mainframe->getCfg('fromname');
$SiteName	= $mainframe->getCfg('sitename');
$subject = "$SiteName: new project posted";

$projectid = JRequest::getVar('eadtu_project___id');
$url = COM_FABRIK_LIVESITE .JRoute::_('index.php?option=com_fabrik&c=form&view=details&Itemid=112&fabrik=2&rowid='.$projectid.'&tableid=2');
$message = "Dear Subscriber,<br /> 
<p>With this mail we want to notify you that a new project is available that fits your profile. 
Check <a href='$url'>http://matchmaking.eadtu.nl</a> for more info<p>";
foreach($res as $r){
	$res = JUtility::sendMail( $MailFrom, $FromName, $r->email, $subject, $message, true );
}

?>