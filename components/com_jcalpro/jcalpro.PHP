<?php
/*
 **********************************************
 JCal Pro v1.5.3
 Copyright (c) 2006-2007 Anything-Digital.com
 **********************************************
 JCal Pro is a fork of the existing Extcalendar component for Joomla!
 (com_extcal_0_9_2_RC4.zip from mamboguru.com).
 Extcal (http://sourceforge.net/projects/extcal) was renamed
 and adapted to become a Mambo/Joomla! component by
 Matthew Friedman, and further modified by David McKinnis
 (mamboguru.com) to repair some security holes.

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 This header must not be removed. Additional contributions/changes
 may be added to this header as long as no information is deleted.
 **********************************************

 $Id: jcalpro.php 325 2008-07-12 14:28:56Z shumisha $

 **********************************************
 Get the latest version of JCal Pro at:
 http://dev.anything-digital.com//
 **********************************************
 */

/** ensure this file is being included by a parent file */
defined( '_JEXEC' ) or die( 'Direct Access to this location is not allowed.' );

//global $mainframe;
//include_once( JPATH_COMPONENT_ADMINISTRATOR . DS . 'install.jcalpro.php' );
require_once( JPATH_BASE."/components/com_jcalpro/config.inc.php" );
//require_once( $CONFIG_EXT['LIB_DIR']."mail.inc.php" );  // replaced by J1.5 API mail class

// If using pop-up event view, and request is for an event view
// disable main menu and search calendar blocks
$show_main_menu = TRUE;
if( $CONFIG_EXT['popup_event_mode'] && $extmode == "view" ) {
  $show_main_menu = FALSE;
  $CONFIG_EXT['search_view'] = FALSE;
}

if(empty($extmode)) {
  $caldefault = $CONFIG_EXT['default_view'];
  if ($caldefault == 0) $extmode = "day";
  if ($caldefault == 1) $extmode = "week";
  if ($caldefault == 2) $extmode = "cal";
  if ($caldefault == 3) $extmode = "flyer";
  if ($caldefault == 4) $extmode = "cats";
}

switch($extmode) {
  case 'addevent' :
  case 'eventform' :
    if (require_priv('add')) {
      //pageheader($lang_main_menu['add_event']);
      //print_add_event_form($date);
    }
    break;

  case 'event' :
    include( $CONFIG_EXT['FS_PATH'].'admin_events.php' );
    break;

  case 'view' :
    pageheader($lang_event_view['section_title']);
    if(!empty($event_id)) print_event_view($event_id);
    else print_monthly_view();
    break;

  case 'day':
    pageheader($lang_main_menu['daily_view']);
    print_daily_view($date);
    break;

  case 'week':
    pageheader($lang_main_menu['weekly_view']);
    print_weekly_view($date);
    break;

  case 'cats' :
    pageheader($lang_main_menu['categories_view']);
    print_categories_view();
    break;

  case 'cat' :
    if(!empty($cat_id)) {
      $cat_info = get_cat_info($cat_id);
      pageheader(sprintf($lang_cat_events_view['section_title'], $cat_info['cat_name']));
      print_cat_content($cat_id);
    } else {
      pageheader($lang_main_menu['categories_view']);
      print_categories_view();
    }
    break;

  case 'flyer' :
  case 'flat' :
    pageheader($lang_main_menu['flat_view']);
    print_flat_view($date);
    break;

  case 'month' :
  case 'cal' :
    pageheader($lang_main_menu['cal_view']);
    print_monthly_view($date);
    break;

  case 'extcal_search' :
    pageheader($lang_event_search_data['section_title']);
    include( $CONFIG_EXT['FS_PATH'].'cal_search.php' );
    break;

  default:
    $extmode = '';
    pageheader($lang_main_menu['cal_view']);
    print_monthly_view($date);
    break;
}

// footer
pagefooter();

// Functions

function print_event_view($extid)	{
  // function to display details on a specific event
  global $CONFIG_EXT, $lang_system, $lang_general, $lang_add_event_view;
  $database = &JFactory::getDBO();
  $date_stamp = (isset($_GET['recurdate']) && !empty($_GET['recurdate']))?$_GET['recurdate']:false;

  $event = new ExtCal_Event();
  if (!$event->loadEvent($extid,$date_stamp)) {
    $sef_href = JRoute::_( $CONFIG_EXT['calendar_calling_page'] );
    theme_redirect_dialog($lang_system['system_caption'], $lang_system['non_exist_event'], $lang_general['back'], $sef_href);
  } else {
    if ( !has_priv ( 'category' . $event->catId ) || !$event->published ) {
      $sef_href = JRoute::_( $CONFIG_EXT['calendar_calling_page'] );
      theme_redirect_dialog($lang_system['system_caption'], $lang_system['non_exist_event'], $lang_general['back'], $sef_href);
    } else {
      // additional field processing
      $event->title = format_text($event->title,false,$CONFIG_EXT['capitalize_event_titles']);
      $event->description = process_content(format_text($event->description,true,false));
      theme_view_event($event,$CONFIG_EXT['popup_event_mode']);
    }
    if ($CONFIG_EXT['search_view']) extcal_search();
  }
}

function print_daily_view($date = '')	{
  // function to display daily events
  global $CONFIG_EXT, $today, $lang_daily_event_view, $lang_system;
  global $lang_general, $lang_date_format, $todayclr, $mainframe;
  global $option, $Itemid_Querystring;
  $database = &JFactory::getDBO();

  $mosConfig_live_site = JURI::base();

  if ($CONFIG_EXT['daily_view'] || has_priv('add')) {
    // Check date. if no date is passed as argument, then we pick today
    if (empty($date)) {
      $day = $today['day'];
      $month = $today['month'];
      $year = $today['year'];
    } else {
      $day = $date['day'];
      $month = $date['month'];
      $year = $date['year'];
    }

    // check if "show past events" is enabled, else force the date to today's date
    if(mktime(0,0,0,$month,$day,$year) < mktime(0,0,0,$today['month'],$today['day'],$today['year']) && !$CONFIG_EXT['archive']) {
      $day = $today['day'];
      $month = $today['month'];
      $year = $today['year'];
    }
    $we = mktime(0,0,0,$month,$day,$year);
    $we = JHTML::date( JCalToUTC( $we), '%w');
    $we++;

    $nextday = mktime(0,0,0,$month,$day + 1,$year);
    $nextday = JHTML::date( JCalToUTC( $nextday), '%d');
    $nextmonth = mktime(0,0,0,$month,$day + 1,$year);
    $nextmonth = JHTML::date( JCalToUTC( $nextmonth), '%m');
    $nextyear = mktime(0,0,0,$month,$day + 1,$year);
    $nextyear = JHTML::date( JCalToUTC( $nextyear), '%Y');

    $previousday = mktime(0,0,0,$month,$day - 1,$year);
    $previousday = JHTML::date( JCalToUTC( $previousday), '%d');
    $previousmonth = mktime(0,0,0,$month,$day - 1,$year);
    $previousmonth = JHTML::date( JCalToUTC( $previousmonth), '%m');
    $previousyear = mktime(0,0,0,$month,$day - 1,$year);
    $previousyear = JHTML::date( JCalToUTC( $previousyear), '%Y');

    starttable('100%', $lang_daily_event_view['section_title'], 3);
    echo "<tr class='tablec'>";

    $date_stamp = mktime(0,0,0,$month,$day,$year);
    $events = get_events($date_stamp,$CONFIG_EXT['show_recurrent_events'],$CONFIG_EXT['show_overlapping_recurrences_dailyview']);

    $previous_day_date = JHTML::date( JCalToUTC( mktime(0,0,0,$previousmonth,$previousday,$previousyear)), '%Y-%m-%d');
    $next_day_date = JHTML::date( JCalToUTC(mktime(0,0,0,$nextmonth,$nextday,$nextyear)), '%Y-%m-%d');

    // link to previous day
    echo "<td class='previousday' nowrap='nowrap' width='33%'>";
    if ((mktime(0,0,0,$previousmonth,$previousday,$previousyear) >= mktime(0,0,0,$today['month'],1,$today['year'])) || $CONFIG_EXT["archive"])
    {
      $sef_href = JRoute::_( $CONFIG_EXT['calendar_calling_page']."&extmode=day&date=".$previous_day_date );
      //echo "<a href=\"$sef_href\"><img class='miniarrow' src='$mosConfig_live_site/components/com_jcalpro/images/mini_arrowleft.gif' border='0' alt=\"".$lang_daily_event_view['previous_day']."\" /></a>";
      echo "<a href=\"$sef_href\">" . $lang_daily_event_view['previous_day'] . "</a>";
    }

    $bgcolor = ($day == $today['day'] && $month == $today['month'] && $year == $today['year']);
    // Current day cell
    echo "</td><td class='currentday' style='$bgcolor' nowrap='nowrap'>";
    $date = JHTML::date( JCalToUTC( mktime(0,0,0,(int)$month,(int)$day,(int)$year)), $lang_date_format['full_date']);
    echo $date."</td>";

    // link to next day
    echo "<td class='nextday' nowrap='nowrap' width='33%'>";
    $sef_href = JRoute::_( $CONFIG_EXT['calendar_calling_page']."&extmode=day&date=".$next_day_date );
    echo "<a href=\"$sef_href\">" . $lang_daily_event_view['next_day'] . "</a>";

    //echo "<a href=\"$sef_href\"><img class='miniarrow' src='$mosConfig_live_site/components/com_jcalpro/images/mini_arrowright.gif' border='0' alt='".$lang_daily_event_view['next_day']."' /></a></td>";
    echo "</td></tr>\n";

    if(!$events) {
    		// display no events on selected day message
      echo "
      <!-- BEGIN message_row -->
      <tr class='tableb'>
      <td class='tableb' colspan='3'>
      <p class='cal_message'>{$lang_daily_event_view['no_events']}</p>

      </td>
      </tr>
      <!-- END message_row -->
";

    } else {
      // print results of query
      $event = new ExtCal_Event();
      while (list(,$event_row) = each($events)) {
        $event->loadEvent($event_row[0]);
        if ( isset($event_row[3]) ) $event->recurStartDay = $event_row[3];
        if ( isset($event_row[4]) ) $event->recurEndDay = $event_row[4];
        // popup or link ?
        if ($CONFIG_EXT['popup_event_mode']) {
          $non_sef_href = "index2.php?option=" . $option . $Itemid_Querystring ."&extmode=view&extid=".$event->extid.($event->isRecurrent()?"&recurdate=".$event->recurStartDay:'');
          $link = "href=\"javascript:;\" onclick=\"MM_openBrWindow('$non_sef_href','Calendar','toolbar=no,location=no,";
          $link .= "status=no,menubar=no,scrollbars=yes,resizable=yes',".$CONFIG_EXT['popup_event_width'].",".$CONFIG_EXT['popup_event_height'].",false)\"";
        } else {
          $sef_href = JRoute::_( $CONFIG_EXT['calendar_calling_page']."&extmode=view&extid=".$event->extid.($event->isRecurrent()?"&recurdate=".$event->recurStartDay:'') );
          $link = "href=\"$sef_href\"";
        }

        if ( $CONFIG_EXT['show_event_times_in_daily_view'] ) {
          if ( $event->isRecurrent() ) $event_date_string = ( $event->recurStartDay == $date_stamp ) ? ' ('.mf_get_timerange($event).')' : '';
          else $event_date_string = (  mf_convert_to_timestamp($event->start_date, 'dateonly') == $date_stamp ) ? ' ('.mf_get_timerange($event).')' : '';
        } else {
          $event_date_string = '';
        }

        echo "<tr><td colspan='3'>\n<table width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
        echo "<tr><td width='6' bgcolor='".$event->color."'><img src='$mosConfig_live_site/components/com_jcalpro/images/spacer.gif' width='6' height='1' alt='' /></td>\n";
        echo "<td class='tableb' width='100%'><div class='eventdesc'><a $link class='eventtitle'>".format_text(sub_string($event->title,$CONFIG_EXT['daily_view_max_chars'],'...'),false,$CONFIG_EXT['capitalize_event_titles']).$event_date_string."</a>\n";
        echo "</div></td></tr></table></td></tr>\n";

      }

    }
    display_cat_legend (3);
    endtable();

    if ($CONFIG_EXT['search_view']) extcal_search();
  } else {
    $sef_href = JRoute::_( $CONFIG_EXT['calendar_calling_page'] );
    theme_redirect_dialog($lang_daily_event_view['section_title'], $lang_system['section_disabled'], $lang_general['back'], $sef_href);
  }
}

function print_weekly_view($date = '')	{
  // function to display weekly events
  global $CONFIG_EXT, $today, $lang_weekly_event_view, $lang_system, $mainframe;
  global $lang_general, $lang_date_format, $todayclr;
  global $option, $Itemid_Querystring;
  $database = &JFactory::getDBO();

  $mosConfig_live_site = JURI::base();

  if ($CONFIG_EXT['weekly_view'] || has_priv('add')) {
    // Check date. if no date is passed as argument, then we pick today
    if (empty($date)) {

    		$day = $today['day'];
    		$month = $today['month'];
    		$year = $today['year'];
    } else {
    		$day = $date['day'];
    		$month = $date['month'];
    		$year = $date['year'];
    }

    $current_weeknumber = get_week_number($today['day'], $today['month'], $today['year']);
    // Calculationg the week number
    $weeknumber = get_week_number($day, $month, $year);

    // check if "show past events" is enabled, else force the date to today's date
    // if(($weeknumber < $current_weeknumber && $year <= $today['year'] ) && !$CONFIG_EXT['archive']) {
    if(mktime(0,0,0,$month,$day,$year) < mktime(0,0,0,$today['month'],$today['day'],$today['year']) && !$CONFIG_EXT['archive']) {
      $day = $today['day'];
      $month = $today['month'];
      $year = $today['year'];
      $weeknumber = $current_weeknumber;
    }

    $week_bound = array();
    $week_bound = get_week_bounds($day,$month,$year);

    $fdy = $week_bound['first_day']['year'];
    $fdm = $week_bound['first_day']['month'];
    $fdd = $week_bound['first_day']['day'];

    $ldy = $week_bound['last_day']['year'];
    $ldm = $week_bound['last_day']['month'];
    $ldd = $week_bound['last_day']['day'];

    $period = sprintf($lang_weekly_event_view['week_period'],JHTML::date( JCalToUTC( mktime(0,0,0,$fdm,$fdd,$fdy)), $lang_date_format['mini_date']),
    JHTML::date( JCalToUTC( mktime(0,0,0,$ldm,$ldd,$ldy)), $lang_date_format['mini_date']));

    // header (with links)
    starttable('100%', $lang_weekly_event_view['section_title'], 3, '', $period);
    echo "<tr class='tablec'>";

    echo "<td class='previousweek' nowrap='nowrap' width='33%'>";
    // previous and next week links

    // Calculationg the week number that contains the first day of current month and year
    //$currentweek = get_week_number($today['day'], $today['month'], $today['year']);

    //if ($CONFIG_EXT['archive'] || ($weeknumber > $current_weeknumber || $year > $today['year'] ) )
    if ($CONFIG_EXT['archive'] || mktime(0,0,0,$fdm,$fdd,$fdy) >= mktime(0,0,0,$today['month'], $today['day'],$today['year'])) {
      $time_stamp = mktime(0,0,0,$fdm,$fdd-7,$fdy) >= mktime(0,0,0,$today['month'], $today['day'],$today['year'])? mktime(0,0,0,$fdm,$fdd-7,$fdy):mktime(0,0,0,$today['month'], $today['day'],$today['year']);
      $sef_href = JRoute::_( $CONFIG_EXT['calendar_calling_page']."&extmode=week&date=".date("Y-m-d", $time_stamp) );
      echo "<a href=\"$sef_href\">";
      //echo "<img class='miniarrow' src='$mosConfig_live_site/components/com_jcalpro/images/mini_arrowleft.gif' border='0' alt='".$lang_weekly_event_view['previous_week']."' /></a>";
      //echo "<a href=\"$sef_href\">";

      echo $lang_weekly_event_view['previous_week'];
      echo "</a> ";
    }
    // Current week cell
    $bgcolor = ($weeknumber == $current_weeknumber);
    echo "</td><td class='currentweek' style='$bgcolor' nowrap='nowrap'>";
    echo sprintf($lang_weekly_event_view['selected_week'], $weeknumber). "</td>";

    // link to next week
    echo "<td class='nextweek' nowrap='nowrap' width='33%'>";
    $sef_href = JRoute::_( $CONFIG_EXT['calendar_calling_page']."&extmode=week&date=".date("Y-m-d", mktime(0,0,0,$ldm,$ldd+1,$ldy)) );
    echo "<a href=\"$sef_href\">";
    echo $lang_weekly_event_view['next_week'];
    echo "</a>";
    echo "</td></tr>\n";
    //echo "<a href=\"$sef_href\">";

    //echo "<img class='miniarrow' src='$mosConfig_live_site/components/com_jcalpro/images/mini_arrowright.gif' border='0' alt='".$lang_weekly_event_view['next_week']."' />";
    //echo "</a></td>";
    //echo "</tr>\n";


    while ($fdy.$fdm.$fdd <= $ldy.$ldm.$ldd ) {

      $day_pattern = sprintf("%04d%02d%02d",$fdy,$fdm,$fdd); // day pattern: 20041231 for 'December 31, 2004'
      $query = "SELECT extid FROM ".$CONFIG_EXT['TABLE_EVENTS']." AS e LEFT JOIN ".$CONFIG_EXT['TABLE_CATEGORIES']." AS c ON ";
      $query .= "e.cat=c.cat_id WHERE (DATE_FORMAT(e.start_date,'%Y%m%d') <= $day_pattern AND DATE_FORMAT(e.end_date,'%Y%m%d') >= $day_pattern) AND c.published = '1' AND approved='1' ORDER BY start_date,title ASC";
      $result = extcal_db_query($query);

      $date_stamp = mktime(0,0,0,$fdm,$fdd,$fdy);
      $events = get_events($date_stamp,$CONFIG_EXT['show_recurrent_events'],$CONFIG_EXT['show_overlapping_recurrences_weeklyview']);

      $previousweekday = 0;
      // Initialize the event object
      $event = new ExtCal_Event();
      while (list(,$event_row) = each($events)) {
        $event->loadEvent($event_row[0]);
        if ( isset($event_row[3]) ) $event->recurStartDay = $event_row[3];
        if ( isset($event_row[4]) ) $event->recurEndDay = $event_row[4];

        $weekday = date("w",mktime(0,0,0,$fdm,$fdd,$fdy));
        $weekday++;

        $date_stamp = mktime(0,0,0,$fdm,$fdd,$fdy); // added to pass date to recurring events
        $date = JHTML::date( JCalToUTC( $date_stamp), $lang_date_format['full_date'] );

        if($previousweekday != $weekday ) {
          echo "<tr class='tableh2'><td colspan='3' class='tableh2'><a id=\"w".$weekday."\" name=\"w".$weekday."\"></a>$date</td></tr>";
        }
        $previousweekday = $weekday;

        if ($CONFIG_EXT['popup_event_mode']) {
          $non_sef_href = "index2.php?option=" . $option . $Itemid_Querystring ."&extmode=view&extid=".$event->extid.($event->isRecurrent()?"&recurdate=".$event->recurStartDay:'');
          $link = "href=\"javascript:;\" onclick=\"MM_openBrWindow('$non_sef_href','Calendar','toolbar=no,location=no,";
          $link .= "status=no,menubar=no,scrollbars=yes,resizable=yes',".$CONFIG_EXT['popup_event_width'].",".$CONFIG_EXT['popup_event_height'].",false)\"";
        } else {
          $sef_href = JRoute::_( $CONFIG_EXT['calendar_calling_page']."&extmode=view&extid=".$event->extid.($event->isRecurrent()?"&recurdate=".$event->recurStartDay:'') );
          $link = "href=\"$sef_href\"";
        }

        if ( $CONFIG_EXT['show_event_times_in_weekly_view'] ) {
          if ( $event->isRecurrent() ) $event_date_string = ( $event->recurStartDay == $date_stamp ) ? ' ('.mf_get_timerange($event).')' : '';
          else $event_date_string = (  mf_convert_to_timestamp($event->start_date, 'dateonly') == $date_stamp ) ? ' ('.mf_get_timerange($event).')' : '';
        } else {
          $event_date_string = '';
        }

        echo "<tr><td colspan='3'>\n<table width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
        echo "<tr><td width='6' bgcolor='".$event->color."'><img src='$mosConfig_live_site/components/com_jcalpro/images/spacer.gif' width='6' height='1' alt='' /></td>\n";
        echo "<td class='tableb' width='100%'><div class='eventdesc'><a $link class='eventtitle'>".format_text(sub_string($event->title,$CONFIG_EXT['weekly_view_max_chars'],'...'),false,$CONFIG_EXT['capitalize_event_titles']).$event_date_string."</a>\n";

        echo "</div></td>\n";

        echo "</tr></table></td></tr>\n";

      }


      $fdy = date("Y", mktime(0,0,0,$fdm,$fdd+1,$fdy));
      if (date("m", mktime(0,0,0,$fdm,$fdd+1,$fdy))==$fdm) {
        $fdd = date("d", mktime(0,0,0,$fdm,$fdd+1,$fdy));
      } else {
        $fdm = date("m", mktime(0,0,0,$fdm,$fdd+1,$fdy));
        $fdd = date("d", mktime(0,0,0,$fdm,1,$fdy));
      }
    }
    if(!$weekday) {
      // display no events on selected day message
      echo "
      <!-- BEGIN message_row -->
      <tr class='tableb'>
      <td align='center' class='tableb' colspan='3'>
      <br /><br />
      <strong>{$lang_weekly_event_view['no_events']}</strong>
      <br /><br /><br />
      </td>
      </tr>
      <!-- END message_row -->
";

    }
    display_cat_legend (3);
    endtable();

    if ($CONFIG_EXT['search_view'])	extcal_search();
  } else {
    $sef_href = JRoute::_( $CONFIG_EXT['calendar_calling_page'] );
    theme_redirect_dialog($lang_weekly_event_view['section_title'], $lang_system['section_disabled'], $lang_general['back'], $sef_href);
  }
}

function print_monthly_view($date = '')	{
  // function to display monthly events
  global $CONFIG_EXT, $today, $lang_monthly_event_view, $lang_system, $THEME_DIR;
  global $lang_general, $lang_date_format, $event_icons, $template_monthly_view, $todayclr, $cat_id;
  global $lang_event_search_data;
  $database =& JFactory::getDBO();

  if ($CONFIG_EXT['monthly_view'] || has_priv('add')) {
    // Check date. if no date is passed as argument, then we pick today
    if (empty($date)) {
      $day = $today['day'];
      $month = $today['month'];
      $year = $today['year'];
    } else {
      $day = $date['day'];
      $month = $date['month'];
      $year = $date['year'];
    }

    // check if "show past events" is enabled, else force the date to today's date
    if(mktime(0,0,0,$month,$day,$year) < mktime(0,0,0,$today['month'],1,$today['year']) && !$CONFIG_EXT['archive']) {
      $day = $today['day'];
      $month = $today['month'];
      $year = $today['year'];
    }

    // insert date into an array an pass it to the theme monthly view
    $target_date = array(
    	'day' => $day,
    	'month' => $month,
    	'year' => $year
    );
    // Build the category filter for the url
    $cat_filter = '';
    if(isset($cat_id) && is_numeric($cat_id)) $cat_filter .= "&cat_id=".$cat_id;
    // number of days in asked month
    $nr = date("t",mktime(12,0,0,$month,1,$year));

    $previous_month_date = date("Y-m-d", mktime(0,0,0,$month-1,1,$year));
    $next_month_date = date("Y-m-d", mktime(0,0,0,$month+1,1,$year));

    $sef_href = JRoute::_( $CONFIG_EXT['calendar_calling_page']."&extmode=cal&date=".$previous_month_date.$cat_filter );
    $info_data['previous_month_url'] = $sef_href;
    $sef_href = JRoute::_( $CONFIG_EXT['calendar_calling_page']."&extmode=cal&date=".$next_month_date.$cat_filter );
    $info_data['next_month_url'] = $sef_href;

    $info_data['current_month_color'] = ($month == $today['month'] && $year == $today['year']);

    if ($CONFIG_EXT['archive'] || ($month != date("n") || $year != date("Y"))) { 
      $info_data['show_past_months'] = true;
    } else {
      $info_data['show_past_months'] = false;
    }

    // get the weekdays
    for ($i=0;$i<=6;$i++) {
      $array_index = $CONFIG_EXT['day_start']?($i+1)%7:$i;

      if ($array_index) { 
        $css_class = "weekdaytopclr"; // weekdays
      } else {
        $css_class = "sundaytopclr"; // sunday
      }
      $info_data['weekdays'][$i]['name'] = $lang_date_format['day_of_week'][$array_index];
      $info_data['weekdays'][$i]['class'] = $css_class;
    }

     
    $event_stack = array();

    // 'existing' days in month
    for ($i=1;$i<=$nr;$i++) {
      $date_stamp = mktime( 0,0,0,$month,$i,$year);  // we must use local time, not UTC 
      $gmt = gmmktime( 0,0,0,$month,$i,$year);
      $events = get_events( $date_stamp,$CONFIG_EXT['show_recurrent_events'],$CONFIG_EXT['show_overlapping_recurrences_monthlyview']);
      //$events = sort_events($events, $event_stack, $date_stamp);

      //$event_stack[$i]['events'] = $events;
      $event_stack[$i]['week_number'] = (int) get_week_number($i, $month, $year);

      // Initialize the event object
      //$event = new ExtCal_Event();

      while (list(,$event_info) = each($events)) {
        // Initialize the event object RP: moved from outside the while loop as we need a new event created each time
        $event = new ExtCal_Event();
        $event->loadEvent($event_info[0]);
        // when the loadEvent is run it loads the values based only on the extid; if there are overlapping
        // recurrences, loadEvent doesn't distinguish and makes them all the same, with "RecurStartDay" and
        // "RecurEndDay" properties set to be those of the most recent recurrence. So now we have to correct
        // the RecurStartDay and RecurEndDay to match this recurrence's particular start and end.
        // NOTE: the values are TIMESTAMPS, date only, no time.
        if ( isset($event_info[3]) ) $event->recurStartDay = $event_info[3];
        if ( isset($event_info[4]) ) $event->recurEndDay = $event_info[4];
        $event_style = $event->get_style($date_stamp,$event_info[1],$event_info[2]);
        $event_icon = $event_icons[$event_style];
        $title = format_text(sub_string($event->title,$CONFIG_EXT['cal_view_max_chars'],'...'),false,$CONFIG_EXT['capitalize_event_titles']);

        $test->hey = $event;

        // HELP, waarom doet ie het nou verkeerd? raadsel...

        $event_stack[$i]['events'][] = array(
      				'title' => $title,
      				'style' => $event_style,
      				'icon' => $event_icon,
      				'color' => $event->color,
      				'extid' => $event->extid,
      				'eventdata' => $test->hey
        );
      }
    }

    theme_monthly_view($target_date, $event_stack, $info_data);

    if ($CONFIG_EXT['search_view']) extcal_search();

  } else {
    $sef_href = JRoute::_( $CONFIG_EXT['calendar_calling_page'] );
    theme_redirect_dialog($lang_weekly_event_view['section_title'], $lang_system['section_disabled'], $lang_general['back'], $sef_href);
  }
}

function print_flat_view($date = '')	{
  // function to display monthly events in a flat view mode
  global $CONFIG_EXT, $today, $lang_flat_event_view, $lang_system, $THEME_DIR;
  global $lang_general, $lang_date_format, $todayclr, $mainframe;
  global $option, $Itemid_Querystring;
  $database = &JFactory::getDBO();

  $mosConfig_live_site = JURI::base();

  if ($CONFIG_EXT['flyer_view'] || has_priv('add'))
  {
    // Check date. if no date is passed as argument, then we pick today
    if (empty($date))
    {
      $day = $today['day'];
      $month = $today['month'];
      $year = $today['year'];
    }
    else
    {
      $day = $date['day'];
      $month = $date['month'];
      $year = $date['year'];
    }

    // check if "show past events" is enabled, else force the date to today's date
    if(mktime(0,0,0,$month,$day,$year) < mktime(0,0,0,$today['month'],1,$today['year']) && !$CONFIG_EXT['archive']) {
      $day = $today['day'];
      $month = $today['month'];
      $year = $today['year'];
    }

    // previous month
    $pm = $month;
    if ($month == "1") $pm = "12"; else  $pm--;
    // previous year
    $py = $year;
    if ($pm == "12") $py--;

    // next month
    $nm = $month;
    if ($month == "12") $nm = "1"; else $nm++;
    // next year
    $ny = $year;
    if ($nm == 1) $ny++;

    $firstday = JHTML::date ( JCalToUTC( mktime(12,0,0,$month,1,$year)), '%w');
    $firstday++;
    // nr of days in askedmonth
    $nr = date("t",mktime(12,0,0,$month,1,$year));

    $today_date = JHTML::date( JCalToUTC( mktime(0,0,0,$today['month'],$today['day'],$today['year'])), $lang_date_format['full_date']);
    starttable('100%', $lang_flat_event_view['section_title'], 3, '', $today_date);
    echo "<tr class='tablec'>";
    echo "<td class='previousmonth' nowrap='nowrap' width='33%'>&nbsp;";

    $previous_month_date = date("Y-m-d", mktime(0,0,0,$pm,1,$py));
    $next_month_date = date("Y-m-d", mktime(0,0,0,$nm,1,$ny));

    if ($month != date("n") || $year != date("Y"))
    {
      // date for previous month
      $date = JHTML::date( JCalToUTC( mktime(0,0,0,$pm,1,$py)), $lang_date_format['month_year']);
      $sef_href = JRoute::_( $CONFIG_EXT['calendar_calling_page']."&extmode=flat&date=".$previous_month_date );
      //echo "<a href=\"$sef_href\" onmouseover=\"showOnBar('".$date."');return true;\" onmouseout=\"showOnBar('');return true;\">";
      //echo "<img class='miniarrow' src='$mosConfig_live_site/components/com_jcalpro/images/mini_arrowleft.gif' border='0' alt='$date' /></a>";
      echo "<a href=\"$sef_href\" onmouseover=\"showOnBar('".$date."');return true;\" onmouseout=\"showOnBar('');return true;\">";

      echo $date;
      echo "</a>";
    }
    elseif ($CONFIG_EXT['archive'] == '1')
    {
      // date for previous month
      $date = JHTML::date( JCalToUTC( mktime(0,0,0,$pm,1,$py)), $lang_date_format['month_year']);
      $sef_href = JRoute::_( $CONFIG_EXT['calendar_calling_page']."&extmode=flat&date=".$previous_month_date );
      //echo "<a href=\"$sef_href\" onmouseover=\"showOnBar('".$date."');return true;\" onmouseout=\"showOnBar('');return true;\">";
      //echo "<img class='miniarrow' src='$mosConfig_live_site/components/com_jcalpro/images/mini_arrowleft.gif' border='0' alt='$date' /></a>";
      echo "<a href=\"$sef_href\" onmouseover=\"showOnBar('".$date."');return true;\" onmouseout=\"showOnBar('');return true;\">";

      echo $date;
      echo "</a>";
    }
    // date for current month
    $date = JHTML::date( JCalToUTC( mktime(0,0,0,$month,1,$year)), $lang_date_format['month_year']);
    $bgcolor = ($month == $today['month'] && $year == $today['year']);
    echo "</td><td class='currentmonth' style='$bgcolor' nowrap='nowrap'>";
    echo $date."</td>";

    // date for next month
    $date = JHTML::date(  JCalToUTC( mktime(0,0,0,$nm,1,$ny)), $lang_date_format['month_year']);
    echo "<td class='nextmonth' nowrap='nowrap' width='33%'>";
    $sef_href = JRoute::_( $CONFIG_EXT['calendar_calling_page']."&extmode=flat&date=".$next_month_date );
    echo "<a href=\"$sef_href\" onmouseover=\"showOnBar('".$date."');return true;\" onmouseout=\"showOnBar('');return true;\">";
    echo $date;
    echo "</a>";

    //echo "<a href=\"$sef_href\" onmouseover=\"showOnBar('".$date."');return true;\" onmouseout=\"showOnBar('');return true;\">";

    //echo "<img class='miniarrow' src='$mosConfig_live_site/components/com_jcalpro/images/mini_arrowright.gif' border='0' alt='$date' />";
    //echo "</a>&nbsp;</td>";
    //echo "</tr>\n";
    echo "</td></tr>\n";

    for ($i=1;$i<=$nr;$i++)
    {
      $date_stamp = mktime(0,0,0,$month,$i,$year);
      $events = get_events($date_stamp,$CONFIG_EXT['show_recurrent_events'],$CONFIG_EXT['show_overlapping_recurrences_flatview']);

      // if result, let's go for that day !
      if ($events){

        $date = JHTML::date( JCalToUTC( mktime(0,0,0,$month,$i,$year)), $lang_date_format['full_date']);
        echo "<tr class='tableh2'><td colspan='3' class='tableh2'><a id=\"w$i\" name=\"w$i\"></a>".$date."</td></tr>";

        // Initialize the event object
        $event = new ExtCal_Event();
        while (list(,$event_info) = each($events))
        {
          $event->loadEvent($event_info[0]);
          if ( isset($event_info[3]) ) $event->recurStartDay = $event_info[3];
          if ( isset($event_info[4]) ) $event->recurEndDay = $event_info[4];

          if ( $CONFIG_EXT['show_event_times_in_flat_view'] ) {
            if ( $event->isRecurrent() ) $event_date_string = ( $event->recurStartDay == $date_stamp ) ? ' ('.mf_get_timerange($event).')' : '';
            else $event_date_string = (  mf_convert_to_timestamp($event->start_date, 'dateonly') == $date_stamp ) ? ' ('.mf_get_timerange($event).')' : '';
          } else {
            $event_date_string = '';
          }

          echo "<tr><td colspan='3'>\n<table width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
          echo "<tr><td width='6' bgcolor='".$event->color."'><img src='$mosConfig_live_site/components/com_jcalpro/images/spacer.gif' width='6' height='1' alt='' /></td>\n";
          if ($CONFIG_EXT['popup_event_mode'])
          {
            $non_sef_href = "index2.php?option=" . $option . $Itemid_Querystring ."&extmode=view&extid=".$event->extid.($event->isRecurrent()?"&recurdate=".$event->recurStartDay:'');
            echo "<td class='tableb' width='100%'><div class='eventdesc'><a href=\"javascript:;\" onclick=\"MM_openBrWindow('".$non_sef_href."','Calendar','toolbar=no,location=no,";
            echo "status=no,menubar=no,scrollbars=yes,resizable=yes',".$CONFIG_EXT['popup_event_width'].",".$CONFIG_EXT['popup_event_height'].",false)\" class=\"eventtitle\" >";
          } else {
            $sef_href = JRoute::_( $CONFIG_EXT['calendar_calling_page']."&extmode=view&extid=".$event->extid.($event->isRecurrent()?"&recurdate=".$event->recurStartDay:'') );
            echo "<td class='tableb' width='100%'><div class='eventdesc'><a href=\"$sef_href\" class='eventtitle'>";
          }
          echo format_text($event->title,false,$CONFIG_EXT['capitalize_event_titles']).$event_date_string."</a><br />\n";

          // picture
          if ($CONFIG_EXT["flyer_show_picture"])
          {
            if ($event->picture) echo "<img src=\"".$CONFIG_EXT['calendar_url'].'/upload/'.$event->picture."\" align=\"right\" alt=\"\" /><br />";
          }
          // title
          // description
          echo process_content(format_text(sub_string($event->description,$CONFIG_EXT['flyer_view_max_chars'],'...'),true,false))."<br />\n";
          echo "<img src='$mosConfig_live_site/components/com_jcalpro/images/spacer.gif' width='1' height='4' alt='' /><br />\n";
          // contact
          if ($event->contact) echo "<strong>".$lang_flat_event_view['contact_info']." :</strong> ".stripslashes($event->contact)." \n";
          // email
          if ($event->email) echo "<strong>".$lang_flat_event_view['contact_email']." :</strong> $event->email\n";
          // url
          if ($event->url) echo "<strong>Url:</strong> <a href=".$event->url.">".$event->url."</a>\n";
          echo "<img src='$mosConfig_live_site/components/com_jcalpro/images/spacer.gif' width='1' height='8' alt='' /><br />\n";
          echo "</div></td></tr></table></td></tr>\n";
        }
      }
    }
    display_cat_legend (3);
    endtable();
    if ($CONFIG_EXT['search_view']) extcal_search();
  } else {
    $sef_href = JRoute::_( $CONFIG_EXT['calendar_calling_page'] );
    theme_redirect_dialog($lang_flat_event_view['section_title'], $lang_system['section_disabled'], $lang_general['back'], $sef_href);
  }
}

function print_categories_view()	{
  // function to display a list of event categories
  global $CONFIG_EXT, $today, $lang_cats_view, $lang_system;
  global $lang_general, $lang_date_format;
  $database = &JFactory::getDBO();

  if ($CONFIG_EXT['cats_view'] || has_priv('add')) //  enabled or not ?
  {
    $query = "SELECT * FROM " . $CONFIG_EXT['TABLE_CATEGORIES'] . " WHERE published = '1' ORDER BY cat_name";
    $results = extcal_db_query($query);
    $rows = extcal_db_num_rows($results);

    if (!$rows) {
      $sef_href = JRoute::_( $CONFIG_EXT['calendar_calling_page'] );
      theme_redirect_dialog($lang_system['system_caption'], $lang_cats_view['no_cats'], $lang_general['back'], $sef_href);
    } else {

      $total_cats = 0;
      $all_events = 0;
      $count = 0;
      $cat_rows = '';
      while ($row = extcal_db_fetch_object($results))
      {
        if ( has_priv ( 'category' . $row->cat_id ) )
        {
          if($CONFIG_EXT['archive']) {
            $query = "SELECT * FROM ".$CONFIG_EXT['TABLE_EVENTS'] . " WHERE cat = '$row->cat_id' AND approved = 1 AND published = 1 ORDER BY title";
          } else {
            $day_pattern = sprintf("%04d%02d%02d",$today['year'],$today['month'],1); // day pattern: 20041231 for 'December 31, 2004'
            $query = "SELECT * FROM ".$CONFIG_EXT['TABLE_EVENTS'] . " AS e WHERE (DATE_FORMAT(e.start_date,'%Y%m%d') >= $day_pattern) AND cat = '$row->cat_id' AND approved = 1 AND published = 1 ORDER BY title";
          }
          $result1 = extcal_db_query($query);
          $total_events = extcal_db_num_rows($result1);
          $day_pattern = sprintf("%04d%02d%02d",$today['year'],$today['month'],$today['day']); // day pattern: 20041231 for 'December 31, 2004'
          $query = "SELECT * FROM ".$CONFIG_EXT['TABLE_EVENTS'] . " AS e WHERE (DATE_FORMAT(e.start_date,'%Y%m%d') > $day_pattern OR DATE_FORMAT(e.recur_until,'%Y%m%d') >= $day_pattern) AND cat = $row->cat_id AND approved = 1 and published = 1";
          $result2 = extcal_db_query($query);
          $upcoming_events = extcal_db_num_rows($result2);

          $cat_rows[$count]['total_events'] = $total_events;
          $cat_rows[$count]['upcoming_events'] = $upcoming_events;
          $cat_rows[$count]['color'] = $row->color;
          $sef_href = JRoute::_( $CONFIG_EXT['calendar_calling_page']."&extmode=cat&cat_id=".$row->cat_id );
          $cat_rows[$count]['link'] = $sef_href;
          $cat_rows[$count]['cat_id'] = $row->cat_id;
          $cat_rows[$count]['cat_name'] = stripslashes($row->cat_name);
          $cat_rows[$count]['description'] = stripslashes($row->description);

          $all_events += $total_events;
          $total_cats ++;
          $count ++;

          extcal_db_free_result($result1);
          extcal_db_free_result($result2);
        }
      }
      $stat_string = sprintf($lang_cats_view['stats_string'], $all_events, $total_cats);

      theme_cats_list($cat_rows, $stat_string);

      extcal_db_free_result($results);
    }
    if ($CONFIG_EXT['search_view']) extcal_search();
  } else {
    $sef_href = JRoute::_( $CONFIG_EXT['calendar_calling_page'] );
    theme_redirect_dialog($lang_cats_view['section_title'], $lang_system['section_disabled'], $lang_general['back'], $sef_href);
  }
}

function print_cat_content($cat_id) {
  // function to display events under a specific category
  global $CONFIG_EXT, $today, $lang_cat_events_view, $lang_system, $next_recurrence_stamp;
  global $lang_general, $lang_date_format;
  global $option, $Itemid_Querystring;
  $database = &JFactory::getDBO();

  if ( ( $CONFIG_EXT['cats_view'] || has_priv('add') ) AND ( has_priv ( 'category' . $cat_id ) ) ) //  enabled or not ?
  {
    $cat_info = get_cat_info($cat_id);
    if (!$cat_info) {
      $sef_href = JRoute::_( $CONFIG_EXT['calendar_calling_page'] . "&extmode=cats" );
      theme_redirect_dialog($lang_system['system_caption'], $lang_system['non_exist_cat'], $lang_general['back'], $sef_href);
    } else
    {

      $total_cats = 0;
      $count = 0;
      $event_rows = '';

      if($CONFIG_EXT['archive']) {
        $query = "SELECT * FROM ".$CONFIG_EXT['TABLE_EVENTS'] . " WHERE cat = '$cat_id' AND approved = 1 AND published = 1 ORDER BY title";
        $result = extcal_db_query($query);
      } else {

        // still have to add support for show past event
        $day_pattern = sprintf("%04d%02d%02d",$today['year'],$today['month'],$today['day']); // day pattern: 20041231 for 'December 31, 2004'
        $query = "SELECT * FROM ".$CONFIG_EXT['TABLE_EVENTS'] . " AS e WHERE (DATE_FORMAT(e.start_date,'%Y%m%d') >= $day_pattern OR DATE_FORMAT(e.end_date,'%Y%m%d') >= $day_pattern OR DATE_FORMAT(e.recur_until,'%Y%m%d') >= $day_pattern) AND cat = '$cat_id' AND approved = 1 AND published = 1 ORDER BY title";
        $result = extcal_db_query($query);
      }
      $total_events = extcal_db_num_rows($result);

      while ($row = extcal_db_fetch_object($result))
      {
        $month = ($row->month)?$lang_date_format['months'][$row->month-1]:$lang_general['everymonth'];
        $year = ($row->year)?$row->year:$lang_general['everyyear'];
        $event_rows[$count]['date'] = mf_process_category_date($row);
        $event_rows[$count]['start_date'] = $row->start_date;
        $event_rows[$count]['next_recurrence_stamp'] = $next_recurrence_stamp;
        if ($CONFIG_EXT['popup_event_mode']) {
          $non_sef_href = "index2.php?option=" . $option . $Itemid_Querystring ."&extmode=view&extid=".$row->extid.(!empty($row->recur_type) ? "&recurdate=$next_recurrence_stamp" : '');
          $event_rows[$count]['link'] = "href='javascript:;' onClick=\"MM_openBrWindow('$non_sef_href','eventview','toolbar=no,status=no,resizable=yes,scrollbars=yes',".$CONFIG_EXT['popup_event_width'].",".$CONFIG_EXT['popup_event_height'].",false)\"";
        } else {
          $sef_href = JRoute::_( $CONFIG_EXT['calendar_calling_page']."&extmode=view&extid=".$row->extid.(!empty($row->recur_type) ? "&recurdate=$next_recurrence_stamp" : '') );
          $event_rows[$count]['link'] = "href='$sef_href'";
        }
        $event_rows[$count]['extid'] = $row->extid;
        $event_rows[$count]['title'] = format_text(sub_string($row->title,$CONFIG_EXT['cats_view_max_chars'],'...'),false,$CONFIG_EXT['capitalize_event_titles']);
        // $event_rows[$count]['description'] = process_content(format_text($row->description, true,false));

        $count ++;
      }
      $stats['total_events'] = (int)$total_events;

      // Now we sort the merged array by order specified in Settings:
      function sortCategoryEvents( $a, $b ) {
        global $CONFIG_EXT;
        switch ( $CONFIG_EXT['sort_category_view_by'] ) {
          case 'title_asc':
            return strcmp(strtolower($a['title']), strtolower ($b['title']));
            break;
          case 'title_desc':
            return strcmp(strtolower($b['title']), strtolower ($a['title']));
            break;
          case 'date_desc':
            if ( $a['next_recurrence_stamp'] == $b['next_recurrence_stamp'] ) {
              // if same day, sort by time:
              if ( mf_convert_to_timestamp($a['start_date'],'timeonly') == mf_convert_to_timestamp($b['start_date'],'timeonly') ) return 0;
              else return ( mf_convert_to_timestamp($a['start_date'],'timeonly') > mf_convert_to_timestamp($b['start_date'],'timeonly') ) ? -1 : 1;
            } else return ( $a['next_recurrence_stamp'] > $b['next_recurrence_stamp'] ) ? -1 : 1;
            break;
          case 'date_asc':
          default:
            if ( $b['next_recurrence_stamp'] == $a['next_recurrence_stamp'] ) {
              // if same day, sort by time:
              if ( mf_convert_to_timestamp($b['start_date'],'timeonly') == mf_convert_to_timestamp($a['start_date'],'timeonly') ) return 0;
              else return ( mf_convert_to_timestamp($b['start_date'],'timeonly') > mf_convert_to_timestamp($a['start_date'],'timeonly') ) ? -1 : 1;
            } else return ( $b['next_recurrence_stamp'] > $a['next_recurrence_stamp'] ) ? -1 : 1;
            break;
        }
      }
      if ( is_array($event_rows) ) usort( $event_rows, "sortCategoryEvents" );

      theme_cat_events_list($event_rows, $cat_info, $stats);
      extcal_db_free_result($result);

    }

    if ($CONFIG_EXT['search_view']) extcal_search();

  } else {
    $sef_href = JRoute::_( $CONFIG_EXT['calendar_calling_page'] );
    theme_redirect_dialog($lang_cats_view['section_title'], $lang_system['section_disabled'], $lang_general['back'], $sef_href);
  }

}

?>