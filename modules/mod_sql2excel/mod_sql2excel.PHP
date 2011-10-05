<?php
/*
 * @package Joomla 1.5
 * @copyright Copyright (C) 2005 Open Source Matters. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 *
 * @module SQL 2 Excel
 * @copyright Copyright (C) Joomla-R-Us, http://Joomla-R-Us.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 *
 * Module  : SQL 2 Excel
 * Joomla-R-Us.com
 */
 
defined('_JEXEC') or die('Restricted access');// no direct access

$user 		=& JFactory::getUser();
$aid 		= $user->get('aid', 0);	
$db 		=& JFactory::getDBO();
$menu 		=& JSite::getMenu();
$document	=& JFactory::getDocument();


// Module Parameters
$display_sections 	= $params->get( 'display_sections', '' );
$hide_sections 		= $params->get( 'hide_sections', '' );
$display_categories 	= $params->get( 'display_categories', '' );
$hide_categories 	= $params->get( 'hide_categories', '' );
$maxlen 		= $params->get( 'maxlen', '40' );
$sort_by 		= $params->get( 'sort_by', 'date' );
$sort_order 		= $params->get( 'sort_order', 'DESC' );
$number_to_show		= $params->get( 'number_to_show', '5' );
$link_to		= $params->get( 'link_to', 'download' );
$listclass		= $params->get( 'listclass', '' );
$showdate		= $params->get( 'showdate', '0' );
$dateformat		= $params->get( 'dateformat', 'm/d/y' );
$showdownloads		= $params->get( 'showdownloads', '0' );
$preview		= $params->get( 'preview', '0' );


// SQL, QUERY
$wheres[] = " a.id = b.wb_id ";
$wheres[] = " b.ws_id = c.id ";
$wheres[] = " c.published=1 ";
$wheres[] = " a.section=d.id ";
$wheres[] = " a.category=e.id ";
$wheres[] = " e.published=1 ";


if ($aid !== null) {
	$wheres[] = "a.access <= " . (int) $aid;
	$wheres[] = "c.access <= " . (int) $aid;
	$wheres[] = "d.access <= " . (int) $aid;
	$wheres[] = "e.access <= " . (int) $aid;
}


if ( $display_sections != '' ) {
	$section_ids_where = " AND a.section IN (".$display_sections.")";
} else {
	$section_ids_where = '';
}

if ( $hide_sections != '' ) {
	$section_ids_not_where = " AND a.section NOT IN (".$hide_sections.")";
} else {
	$section_ids_not_where = '';
}

if ( $display_categories != '' ) {
	$category_ids_where = " AND a.category IN (".$display_categories.")";
} else {
	$category_ids_where = '';
}

if ( $hide_categories != '' ) {
	$category_ids_not_where = " AND a.category NOT IN (".$hide_categories.")";
} else {
	$category_ids_not_where = '';
}

	

$query = " SELECT a.id, a.link_title, a.count, UNIX_TIMESTAMP(a.created) as utime, count(*) as ws_cnt " .
	 " FROM #__sql2excel_workbooks a, #__sql2excel_worksheet2book b, #__sql2excel_worksheets c, #__sql2excel_sections d, #__sql2excel_categories e " .
	 " WHERE " . implode( " AND ", $wheres ) .
	 $section_ids_where . $section_ids_not_where .
	 $category_ids_where . $category_ids_not_where .
	 " GROUP BY a.id, a.link_title" .
	 " ORDER BY " . $sort_by . " " . $sort_order .
	 " LIMIT 0," . $number_to_show;

	

$db->setQuery( $query );
$workbooks = $db->loadObjectList();

$output = '<div id="sql2excel-module">';
if (!empty($workbooks)) {
	if ( trim($listclass) != '' ) {
		$output .= '<ul class="' . trim($listclass) . '">';
	} else {
		$output .= '<ul>';
	}
	foreach ($workbooks as $value) {
 		$dltitle = $value->link_title;
	        if ( strlen($dltitle) > $maxlen )  {
	         	$dltitle = substr($dltitle,0,$maxlen) . '...';
	        }
	        $target = '';	        	        
	        if ( $preview == 'inline' ) {
	        	$url = 'index.php?option=com_sql2excel&controller=preview&task=popup&id='.$value->id;
	        } elseif ( $preview == 'inlinewt' )  {
	        	$url = 'index.php?option=com_sql2excel&view=pre&id='.$value->id;
	        } elseif ( $preview == 'popup' )  {
	        	$url = 'index.php?option=com_sql2excel&controller=preview&task=popup&id='.$value->id;
	        	$target = ' target="_NEW" ';
	        } elseif ( $preview == 'popupwt' )  {
	        	$url = 'index.php?option=com_sql2excel&view=pre&id='.$value->id;
	        	$target = ' target="_NEW" ';	        
	        } else {
	        	$url = 'index.php?option=com_sql2excel&controller=download&task=dl&id='.$value->id;
	        }
			        
	        $linkDownloadB = '<a href="'. JRoute::_($url).'"' . $target . '>';				
	        $linkDownloadE ='</a>';
		if ( $showdate ) {
			$linkDownloadE .=' <span class="small">[' . date($dateformat, $value->utime) . ']</span>';
		} 				
		if ( $showdownloads ) {
			$linkDownloadE .=' <span class="small">[' . $value->count . ']</span>';
		} 	        
	        $output .= '<li>' . $linkDownloadB . $dltitle . $linkDownloadE . '</li>';
	}
	$output .= '</ul>';	
}
$output .= '</div>';


require(JModuleHelper::getLayoutPath('mod_sql2excel'));
?>