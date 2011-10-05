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

$accepted = JRequest::getVar('eadtu_project_applicant___company_accepted');
$accepted = $accepted[0];
$studentid = JRequest::getVar('eadtu_project_applicant___student_id');

$sql = "SELECT id, name, email FROM jos_users 
where id = $studentid";
$db->setQuery($sql);
$student = $db->loadObject();

$rowid = JRequest::getVar('rowid');
$db->setQuery("select title from eadtu_project_applicant as a left join eadtu_project as p on a.project_id = p.id where id = $rowid");
$projectName = $db->loadResult();

$user = JFactory::getUser();


global $mainframe;

$MailFrom	= $mainframe->getCfg('mailfrom');
$FromName	= $mainframe->getCfg('fromname');
$SiteName	= $mainframe->getCfg('sitename');
$subject = ($accepted == 1) ? "$SiteName: $projectName application accepted" : "$SiteName: $projectName application declined";
$acpt = ($accepted == 1) ? "accepted" : "declined";

$projectid = JRequest::getVar('eadtu_project___id');
$message = "Dear %s,<br /> 
<p>With this mail we want to notify you that you have been %s for the project '%s'. </p>";

	$thismsg = sprintf($message, $owner->name, $acpt, $projectName);
	if($accepted == 1){
		$thismsg .= "<p>Please log into the site and accept or decline the offer. 
		If accepted you and the placement organisation can continue to use the site 
		to help you manage your internship.</p>";
	}
	$thismsg .= "<p><a href='http://matchmaking.eadtu.nl'>http://matchmaking.eadtu.nl</a></p>";
	
	echo "$accepted: $MailFrom, $FromName, $student->email, $subject, $thismsg, true";
	$res = JUtility::sendMail( $MailFrom, $FromName, $student->email, $subject, $thismsg, true );

?>