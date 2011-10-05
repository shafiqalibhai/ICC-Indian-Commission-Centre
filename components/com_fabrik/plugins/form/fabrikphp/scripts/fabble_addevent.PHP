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
$user =& JFactory::getUser();
$userid = (int)$user->get('id');
$eventid = JRequest::getInt('jos_fabble_events___id');
$db->setQuery("insert into #__fabble_person_events (`user_id`, `event_id`) VALUES ($userid, $eventid)");
$db->query();

//add forum topic
		$host 		= 'localhost';
		$username 		= 'insight_insj15';
		$password 	= 'SLenr3Ubs4Fz';
		$database	= 'insight_insvb';
		$prefix 	= 'vb_';
		$driver 	= 'mysqli';
		$debug 		= false;

		$options	= array ( 'driver' => $driver, 'host' => $host, 'user' => $username, 'password' => $password, 'database' => $database, 'prefix' => $prefix );

$vbdb =& JDatabase::getInstance( $options );

//create thread
//not yet used? prefixid 	firstpostid 	lastpostid 	lastpost pollid dateline 	views 	iconid 	notes 		sticky 	votenum 	votetotal 	attach 	similar 	taglist
$title = $vbdb->Quote(JRequest::getVar('jos_fabble_events___label'));
$forumid = 2;
$open = 1;
$postusername = $vbdb->Quote($user->get('username'));
$vbdb->setQuery("select userid from #__user where username = $postusername");
//echo $vbdb->getQuery();
$userid = (int)$vbdb->loadResult();
$query = "INSERT INTO #__thread (`title`, `forumid`, `open`, `postusername`, `postuserid`, `lastposter`, `visible`)"
. " VALUES ($title, $forumid, $open, $postusername, $userid, $postusername, 1)";
$vbdb->setQuery($query);
//echo $vbdb->getQuery();
$vbdb->query(); 
$threadid = (int)$vbdb->insertid();

$query = "INSERT INTO #__post (`threadid`, `parentid`, `username`, `userid`, `title`, `dateline`, `pagetext`, `allowsmilie`, `showsignature`, `visible` )".
" VALUES($threadid, 0, $postusername, $userid, $title, '', '', 1, 0, 1) ";
$vbdb->setQuery($query);
//echo $vbdb->getQuery();
$vbdb->query(); 

//add a gallery
$userid = (int)$user->get('id');
$db->setQuery("insert into #__fabrik_plg_gallery (`owner`, `label`, `preview_image`, `meeting_id`) values ($userid, $title, '/images/stories/noimage-large.png', $eventid)");
$db->query();
//echo $db->getQuery();
?>