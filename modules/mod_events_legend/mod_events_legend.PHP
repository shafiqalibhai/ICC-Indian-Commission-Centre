<?php
/**
 * Module Events Legend for Joomla 1.0.x
 *
 * @version     $Id: mod_events_legend.php 843 2007-07-12 07:06:00Z geraint $
 * @package     Events
 * @subpackage  Module Events Legend
 * @copyright   Copyright (C) 2006 JEvents Project Group
 * @licence     http://www.gnu.org/copyleft/gpl.html
 * @link        http://joomlacode.org/gf/project/jevents
 */

defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );


global $mosConfig_offset;
global $mosConfig_lang;

// CHECK EVENTS COMPONENT 
$file = mosMainFrame::getBasePath() . 'components/com_events/includes/modutils.php';
if (file_exists($file) ) {
	include_once($file);
} else {
    die ("Events Calendar\n<br />This module needs the Events component. <br />Please configure it first, if it is already installed.");   
}

// get configuration object
$cfg = & EventsConfig::getInstance();

// CHECK LANGUAGE
if (!defined( '_CAL_LANG_INCLUDED' )) {
    if (file_exists("components/com_events/language/".$mosConfig_lang.".php") ) { 
        include_once("components/com_events/language/".$mosConfig_lang.".php");
    } else { 
        include_once("components/com_events/language/english.php");
    }
}

if (!defined("_CAL_LEGEND_ALL_CATEGORIES")) define("_CAL_LEGEND_ALL_CATEGORIES","all categories");
if (!defined("_CAL_LEGEND_ALL_CATEGORIES_DESC")) define("_CAL_LEGEND_ALL_CATEGORIES_DESC","all categories desc");

$params = mosParseParams( $module->params );

$content = displayCalendarLegend($params);
global $_VERSION;
if (floatval($_VERSION->getShortVersion())>=1.5)  echo $content;

class catLegend {
   function catLegend($id, $name, $color, $description)
	{
		$this->id=$id;
		$this->name=$name;
		$this->color=$color;
		$this->description=$description;		
	}
}
function displayCalendarLegend($params){

	// since this is meant to be a comprehensive legend look for catids from menu first:
	global $mainframe, $option, $my, $Itemid, $database;
	global $mosConfig_offset;
	// Parameters - This module should only be displayed alongside a com_events calendar component!!!
	if ($option!="com_events") return;

	$year	= intval( mosGetParam( $_REQUEST, 'year',	strftime( '%Y', time() + ( $mosConfig_offset*60*60 )) ));
	$month	= intval( mosGetParam( $_REQUEST, 'month',	strftime( '%m', time() + ( $mosConfig_offset*60*60 )) ));
	$day	= intval( mosGetParam( $_REQUEST, 'day',	strftime( '%d', time() + ( $mosConfig_offset*60*60 )) ));

	$catidList = "";

	include_once(mosMainFrame::getBasePath()."/administrator/components/com_events/colorMap.php");

	
	// I can't rely on
	// $menu = $mainframe->get( 'menu' );
	// $params = new mosParameters( $menu->params );
	// so I get the paramaters from the database directly
	if ($Itemid>0){
		$query = "SELECT id, params"
		. "\n FROM #__menu WHERE"
		. "\n link = 'index.php?option=com_events'"
		. "\n AND published = 1"
		. "\n AND access <= $my->gid"
		. "\n AND id = $Itemid"
		. "\n ORDER BY access ASC";
		$database->setQuery($query);
		$database->loadObject($idParam);
		if (isset($idParam) && intval($idParam->id) == $Itemid){
			$test = new mosParameters( $idParam->params);
			$c=0;
			$catids = array();
			while ($nextCatId = $test->get( "catid$c", null )){
				if (!in_array($nextCatId,$catids)){
					$catids[]=$nextCatId;
					$catidList .= (strlen($catidList)>0?",":"").$nextCatId;
				}
				$c++;
			}
		}
		$catidsOut = str_replace(",","|",$catidList);
	}
	
	// I should only show legend for items that **can** be shown in calendar so must filter based on GET/POST
	$catidsIn = urldecode(mosGetParam( $_REQUEST, 'catids', "NONE" ));
	if ($catidsIn!="NONE") $catidsGP = explode("|",$catidsIn);
	else $catidsGP = array();

	$sql = "SELECT cat.id, cat.name, cat.description, cat.access, evcat.color"
	. "\n FROM #__events_categories as evcat, #__categories as cat"
	. "\n WHERE  evcat.id=cat.id"
	. "\n AND cat.access <= $my->gid"
	. "\n AND cat.published = 1";
	if (strlen($catidList)>0) $sql .= " AND evcat.id IN ($catidList)";
	$sql .= " ORDER BY cat.ordering ASC";
	$database->setQuery($sql);
	$allrows = $database->loadObjectList();

	$allcats = new catLegend("0", _CAL_LEGEND_ALL_CATEGORIES,"lightgray",_CAL_LEGEND_ALL_CATEGORIES_DESC);
	
	$availableCatsIds="";
	foreach ($allrows as $row){
		$availableCatsIds.=(strlen($availableCatsIds)>0?"|":"").$row->id;
	}
	
	array_push($allrows,$allcats);
	if (count($allrows)==0) $content="";
	else {
		if ($Itemid<999999) $itm = "&amp;Itemid=$Itemid";

		global $task;
		$tsk="";
		if ($task=="view_month" || $task=="view_week" ||  $task=="view_day" || $task=="view_year"|| $task=="view_cat"){
			$tsk="&amp;task=$task&amp;year=$year&amp;month=$month&amp;day=$day";
		}
		$content = "<div class=\"event_legend_container\"><ul class=\"event_legend_list\">";
		foreach ($allrows as $row) {
			// do not show legend for categories exluded via GET/POST
			if ($row->id>0 && count($catidsGP) && !in_array($row->id, $catidsGP)) continue;
			$st1="background-color:".$row->color.";color:".mapColor($row->color);			
			$cat = $row->id>0?"&amp;catids=$row->id":"&amp;catids=$availableCatsIds";
			$content .= "<li style='list-style:none;margin-top:5px;'>"
			."<div class='event_legend_name' style='".$st1."'>"
			//."$row->name ($row->id)</div>"
			."<a href='".sefRelToAbs("index.php?option=com_events$cat$itm$tsk")."' title='".$row->name."' style='".$st1."'>"
			."$row->name</a></div>";
			if (strlen($row->description)>0) {
				$content .="<div class='event_legend_desc'>$row->description</div>";
			}
			$content .="</li>";
		}
		$content .= "</ul></div>";
	}
	if (isset($params->show_admin) && $params->show_admin && isset($year) && isset($month) && isset($day) && isset($Itemid)) {
		global $mainframe;
		include_once(mosMainFrame::getBasePath()."/components/com_events/events.html.php");
		ob_start();
	    HTML_events::viewNavAdminPanel( $year, $month, $day, $option, $Itemid );
		$content .= ob_get_contents();
		ob_end_clean();
	}
    return $content;
}
?>
