<?php
/**
 * @package Joomla
 * @subpackage Fabrik
 * @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!

//run when a student finally accepts the project

defined('_JEXEC') or die();
$db = JFactory::getDBO();
$post = JRequest::get('post');
$accepted = JRequest::getVar('eadtu_project_applicant___student_accepted');
$accepted = $accepted[0];
$studentid = JRequest::getVar('eadtu_project_applicant___student_id');

$sql = "SELECT id, name, email FROM jos_users 
where id = $studentid";
$db->setQuery($sql);
$student = $db->loadObject();

$rowid = JRequest::getVar('rowid');
$db->setQuery("select title, p.owner_id, u.name, u.email from eadtu_project_applicant as a 
left join eadtu_project as p on a.project_id = p.id 
left join #__users as u on u.id = p.owner_id where a.id = $rowid");
$project = $db->loadObject();

$projectName = $project->title;


global $mainframe;

$MailFrom	= $mainframe->getCfg('mailfrom');
$FromName	= $mainframe->getCfg('fromname');
$SiteName	= $mainframe->getCfg('sitename');
$subject = ($accepted == 1) ? "$SiteName: $projectName application accepted by student" : "$SiteName: $projectName application declined by student";
$acpt = ($accepted == 1) ? "accepted" : "declined";

$message = "Dear %s,<br /> 
<p>With this mail we want to notify you that %s has %s the project '%s'. </p>";

$thismsg = sprintf($message, $project->name, $student->name, $acpt, $projectName);
if($accepted == 1){
	$thismsg .= "<p>Your internship student and yourselves may now continue to use the site 
	to help you manage your internship.</p>";
}
$thismsg .= "<p><a href='http://matchmaking.eadtu.nl'>http://matchmaking.eadtu.nl</a></p>";
$res = JUtility::sendMail( $MailFrom, $FromName, $student->email, $subject, $thismsg, true );

?>