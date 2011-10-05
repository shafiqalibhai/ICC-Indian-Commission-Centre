<?php
/**
 * @package Joomla
 * @subpackage Fabrik
 * @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

$post = JRequest::get('post');
$db = JFactory::getDBO();
$userid = JRequest::getVar('eadtu_project_applicant___student_id');
jimport('joomla.utilities.simplecrypt');
jimport('joomla.utilities.utility');
$crypt = new JSimpleCrypt();

$projectid = $crypt->decrypt($post['fabrik_vars']['querystring']['eadtu_project_applicant___project_id']);

$db->setQuery("SELECT owner_id, title, u.name as owner_name, u.email AS user_email FROM eadtu_project AS p LEFT JOIN #__users AS u
ON p.owner_id = u.id WHERE p.id = $projectid");
$project = $db->loadObject();
$contactid = $project->owner_id;

//send email to org to notify them of a new applicant

global $mainframe;

$MailFrom	= $mainframe->getCfg('mailfrom');
$FromName	= $mainframe->getCfg('fromname');
$SiteName	= $mainframe->getCfg('sitename');
$subject = "$SiteName: new applicant";

$projectid = JRequest::getVar('eadtu_project___id');
$url = COM_FABRIK_LIVESITE .JRoute::_('index.php?option=com_fabrik&c=form&view=details&Itemid=112&fabrik=2&rowid='.$projectid.'&tableid=2');
$message = "Dear %s,<br /> 
<p>With this mail we want to notify you that a new applicant has applied for your project, '%s', and is available here. 
<a href='$url'>http://matchmaking.eadtu.nl</a><p>";

$thismsg = sprintf($message, $project->owner_name, $project->title);
$res = JUtility::sendMail( $MailFrom, $FromName, $project->user_email, $subject, $thismsg, true );


//see if the user already knows the organisation
$sql ="select count(*) from eadtu_contacts where user_id = $userid and contact_id = $contactid ";
$db->setQuery($sql);
$c = $db->loadResult();
if($c == 0){
	//add in oragnisation into the students contact list
	$sql = "INSERT INTO eadtu_contacts (`user_id`, `contact_id`) VALUES ('$userid', '$contactid');";
	$db->setQuery($sql);
	$db->query();
}

//see if the user already knows the organisation
$sql ="select count(*) from eadtu_contacts where user_id = $contactid and contact_id = $userid ";
$db->setQuery($sql);
$c = $db->loadResult();
if($c == 0){
	//add STUDENT into the ORG	 contact list
	$sql = "INSERT INTO eadtu_contacts (`user_id`, `contact_id`) VALUES ('$contactid', '$userid');";
	$db->setQuery($sql);
	$db->query();
}

?>