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

$File: index.php - language file$

Revision date: 02/20/2007

**********************************************
Get the latest version of JCal Pro at:
http://dev.anything-digital.com//
**********************************************
*/

/** ensure this file is being included by a parent file */
defined( '_JEXEC' ) or die( 'Direct Access to this location is not allowed.' );

// New language structure
$lang_info = array (
	'name' => 'Thai'
	,'nativename' => 'Thai' // Language name in native language. E.g: 'Français' for 'French'
	,'locale' => array('th','thai') // Standard locale alternatives for a specific language. For reference, go to: http://www.php.net/manual/en/function.setlocale.php
	,'charset' => 'utf8' // For reference, go to : http://www.w3.org/International/O-charset-lang.html
	,'direction' => 'ltr' // 'ltr' for Left to Right. 'rtl' for Right to Left languages such as Arabic.
	,'author' => 'Mohamed Moujami (Simo)' 
	,'author_email' => 'simoami@hotmail.com' 
	,'author_url' => 'http://www.MarocTour.com'
	,'transdate' => '05/15/2005' // Translate by Chaisilp Panawiwatn => chaisilp@hotmail.com
);

$lang_general = array (
	'yes' => 'ãªè'
	,'no' => 'äÁèãªè'
	,'back' => '¡ÅÑº'
	,'continue' => '·ÓµèÍ'
	,'close' => '»Ô´'
	,'errors' => '¼Ô´¾ÅÒ´'
	,'info' => 'ÃÒÂÅÐàÍÕÂ´'
	,'day' => 'ÇÑ¹'
	,'days' => 'ÇÑ¹'
	,'month' => 'à´×Í¹'
	,'months' => 'à´×Í¹'
	,'year' => '»Õ'
	,'years' => '»Õ'
	,'hour' => 'ªÑèÇâÁ§'
	,'hours' => 'ªÑèÇâÁ§'
	,'minute' => '¹Ò·Õ'
	,'minutes' => '¹Ò·Õ'
	,'everyday' => '·Ø¡æÇÑ¹'
	,'everymonth' => '·Ø¡æà´×Í¹'
	,'everyyear' => '·Ø¡æ»Õ'
	,'active' => 'áÊ´§'
	,'not_active' => 'äÁèáÊ´§'
	,'today' => 'ÇÑ¹¹Õé'
	,'signature' => 'Powered by %s'
	,'expand' => '¢ÂÒÂ'
	,'collapse' => '»Ô´'
);

// Date formats, For reference, go to : http://www.php.net/manual/en/function.strftime.php
$lang_date_format = array (
	'full_date' => 'ÇÑ¹%A·Õè %d %B %Y' // e.g. Wednesday, June 05, 2002
	,'full_date_time_24hour' => 'ÇÑ¹%A·Õè %d %B %Y àÇÅÒ %H:%M' // e.g. Wednesday, June 05, 2002 At 21:05
	,'full_date_time_12hour' => 'ÇÑ¹%A·Õè %d %B %Y àÇÅÒ %I:%M %p' // e.g. Wednesday, June 05, 2002 At 9:05 pm
	,'day_month_year' => '%d-%b-%Y' // e.g 10-Sep-2004
	,'local_date' => '%c' // Preferred date and time representation for current language 
	,'mini_date' => '%a %d %b %Y' 
	,'month_year' => '%B %Y'
	,'day_of_week' => array('Í','¨','Í','¾','¾','È','Ê')
	,'months' => array('Á¡ÃÒ¤Á','¡ØÁÀÒ¾Ñ¹¸ì','ÁÕ¹Ò¤Á','àÁÉÒÂ¹','¾ÄÉÀÒ¤Á','ÁÔ¶Ø¹ÒÂ¹','¡Ã¡®Ò¤Á','ÊÔ§ËÒ¤Á','¡Ñ¹ÂÒÂ¹','µØÅÒ¤Á','¾ÄÈ¨Ô¡ÒÂ¹','¸Ñ¹ÇÒ¤Á')
);

$lang_system = array (
	'system_caption' => '¢éÍ¤ÇÒÁÃÐºº'
	,'page_access_denied' => '¤Ø³äÁèÁÕÊÔ·¸Ôìã¹¡ÒÃà¢éÒãªé§Ò¹¹Õé'
	,'page_requires_login' => '¤Ø³µéÍ§à¢éÒÊÙèÃÐºº¡èÍ¹'
	,'operation_denied' => '¤Ø³äÁèÁÕÊÔ·¸Ôìã¹¡ÒÃà¢éÒãªé§Ò¹¹Õé'
	,'section_disabled' => 'section »Ô´ !'
	,'non_exist_cat' => 'ÂÑ§äÁèÁÕËÁÇ´¡Ô¨¡ÃÃÁ¹Õé !'
	,'non_exist_event' => 'ÂÑ§äÁèÁÕ¡Ô¨¡ÃÃÁ¹Õé !'
	,'param_missing' => '¢éÍÁÙÅäÁè¶Ù¡µéÍ§'
	,'no_events' => 'äÁèÁÕÃÒÂ¡ÒÃ¡Ô¨¡ÃÃÁ'
	,'config_string' => '¤Ø³ãªé \'%s\' º¹ %s, %s áÅÐ %s.'
	,'no_table' => 'äÁèÁÕµÒÃÒ§ \'%s\' !'
	,'no_anonymous_group' => 'µÒÃÒ§ %s äÁèµÃ§¡Ñº¡ÅØèÁ \'Anonymous\' !'
	,'calendar_locked' => '¢ÍÍÀÑÂ »Ô´ãªé§Ò¹ªÑèÇ¤ÃÒÇ !'
	,'new_upgrade' => 'ÃÐººµÃÇ¨¾ºàÇÍÃìªÑè¹ãËÁè ¡´»ØèÁ "Continue" à¾×èÍ·Ó¡ÒÃÍÑ¾à¡Ã´'
	,'no_profile' => '¾º¢éÍ¼Ô´¾ÅÒ´¢³ÐàÃÕÂ¡¢éÍÁÙÅ»ÃÐÇÑµÔ¢Í§¤Ø³'
	,'unknown_component' => 'äÁèÃÙé¨Ñ¡¤ÍÁâ¾à¹é¹·ì'
// Mail messages
	,'new_event_subject' => 'Â×¹ÂÑ¹ÃÒÂ¡ÒÃ¡Ô¨¡ÃÃÁ : %s'
	,'event_notification_failed' => '¾º¢éÍ¼Ô´¾ÅÒ´ã¹¡ÒÃÊè§ÍÕàÁÅì !'
);

// Message body for new event email notification
$lang_system['event_notification_body'] = <<<EOT
ÃÒÂ¡ÒÃ¡Ô¨¡ÃÃÁä´é¡ÓË¹´¢Öé¹ã¹ {CALENDAR_NAME}
áÅÐµéÍ§¡ÒÃ¡ÒÃÂ×¹ÂÑ¹ :

ËÑÇ¢éÍ: "{TITLE}"
ÇÑ¹·Õè: "{DATE}"
ªèÇ§àÇÅÒ: "{DURATION}"

¤Ø³ÊÒÁÒÃ¶¨Ñ´¡ÒÃ¡Ô¨¡ÃÃÁ¹Õéâ´Â¡ÒÃ¤ÅÔê¡ÅÔê§¤ì´éÒ¹ÅèÒ§ ËÃ×Í copy ä»à»Ô´ã¹ºÃÒÇà«ÍÃìä´é

{LINK}

(ËÁÒÂàËµØ ¤Ø³µéÍ§à¢éÒÊÙèÃÐºº¡èÍ¹¨Ö§¨Ð´Óà¹Ô¹¡ÒÃä´é)

¢ÍáÊ´§¤ÇÒÁ¹Ñº¶×Í

¼ÙéºÃÔËÒÃ¨Ñ´¡ÒÃ {CALENDAR_NAME}

EOT;

// Admin menu entries
$lang_admin_menu = array (
	'login' => 'à¢éÒÊÙèÃÐºº'
	,'register' => 'Å§·ÐàºÕÂ¹'
  ,'logout' => 'ÍÍ¡¨Ò¡ÃÐºº <span style="color:#FF9922">[<span style="color:#606F79">%s</span>]</span>'
  ,'user_profile' => '»ÃÐÇÑµÔÊèÇ¹µÑÇ'
	,'admin_events' => '¡Ô¨¡ÃÃÁ'
  ,'admin_categories' => 'ËÁÇ´'
  ,'admin_groups' => '¡ÅØèÁ'
  ,'admin_users' => '¼Ùéãªé§Ò¹'
  ,'admin_settings' => 'µÑé§¤èÒ'
);

// Main menu entries
$lang_main_menu = array (
	'add_event' => 'à¾ÔèÁ¡Ô¨¡ÃÃÁ'
	,'cal_view' => 'ÃÒÂà´×Í¹'
  ,'flat_view' => 'µÒÁÅÓ´Ñº'
  ,'weekly_view' => 'ÃÒÂÊÑ»´ÒËì'
  ,'daily_view' => 'ÃÒÂÇÑ¹'
  ,'yearly_view' => 'ÃÒÂ»Õ'
  ,'categories_view' => 'ËÁÇ´¡Ô¨¡ÃÃÁ'
  ,'search_view' => '¤é¹ËÒ'
);

// ======================================================
// Add Event view
// ======================================================

$lang_add_event_view = array(
	'section_title' => 'à¾ÔèÁ¡Ô¨¡ÃÃÁ'
	,'edit_event' => 'á¡éä¢¡Ô¨¡ÃÃÁ [id%d] \'%s\''
	,'update_event_button' => '»ÃÑº»ÃØ§¡Ô¨¡ÃÃÁ'

// Event details
	,'event_details_label' => 'ÃÒÂÅÐàÍÕÂ´'
	,'event_title' => 'ËÑÇ¢éÍ¡Ô¨¡ÃÃÁ'
	,'event_desc' => 'ÃÒÂÅÐàÍÕÂ´'
	,'event_cat' => 'ËÁÇ´'
	,'choose_cat' => 'àÅ×Í¡'
	,'event_date' => 'ªèÇ§àÇÅÒ'
	,'day_label' => 'ÇÑ¹'
	,'month_label' => 'à´×Í¹'
	,'year_label' => '»Õ'
	,'start_date_label' => 'ÇÑ¹·ÕèàÃÔèÁµé¹'
	,'start_time_label' => 'àÇÅÒ'
	,'end_date_label' => 'ÃÐÂÐàÇÅÒ'
	,'all_day_label' => 'µÅÍ´ÇÑ¹'
// Contact details
	,'contact_details_label' => 'ÃÒÂÅÐàÍÕÂ´¼ÙéµÔ´µèÍ'
	,'contact_info' => 'ÃÒÂÅÐàÍÕÂ´¡ÒÃµÔ´µèÍ'
	,'contact_email' => 'ÍÕàÁÅì'
	,'contact_url' => 'àÇçºä«µì'
// Repeat events
	,'repeat_event_label' => '¡Ô¨¡ÃÃÁµèÍà¹×èÍ§'
	,'repeat_method_label' => 'ÇÔ¸Õ¡ÒÃáÊ´§'
	,'repeat_none' => 'äÁèµéÍ§áÊ´§¡Ô¨¡ÃÃÁµèÍà¹×èÍ§'
	,'repeat_every' => 'áÊ´§«éÓ·Ø¡æ'
	,'repeat_days' => 'ÇÑ¹'
	,'repeat_weeks' => 'ÊÑ»´ÒËì'
	,'repeat_months' => 'à´×Í¹'
	,'repeat_years' => '»Õ'
	,'repeat_end_date_label' => 'áÊ´§ÇÑ¹ÊÔé¹ÊØ´¡Ô¨¡ÃÃÁ'
	,'repeat_end_date_none' => 'äÁè¡ÓË¹´ÇÑ¹ÊÔé¹ÊØ´¡Ô¨¡ÃÃÁ'
	,'repeat_end_date_count' => 'ÊÔé¹ÊØ´ËÅÑ§¨Ò¡ %s ¡Ô¨¡ÃÃÁ'
	,'repeat_end_date_until' => 'áÊ´§«éÓ¨¹¶Ö§ÇÑ¹·Õè'
// Other details
	,'other_details_label' => 'ÃÒÂÅÐàÍÕÂ´Í×è¹æ'
	,'picture_file' => 'ä¿ÅìÃÙ»ÀÒ¾'
	,'file_upload_info' => '(äÁèà¡Ô¹ %d Kb - à©¾ÒÐ : %s )' 
	,'del_picture' => 'ÅºÀÒ¾»Ñ¨¨ØºÑ¹ ?'
// Administrative options
	,'admin_options_label' => 'ÊèÇ¹¢Í§¼Ùé´ÙáÅÃÐºº'
	,'auto_appr_event' => 'Â×¹ÂÑ¹¡Ô¨¡ÃÃÁ'

// Error messages
	,'no_title' => 'ÂÑ§äÁèä´éÃÐºØËÑÇ¢éÍ¡Ô¨¡ÃÃÁ !'
	,'no_desc' => 'ÂÑ§äÁèä´éÃÐºØÃÒÂÅÐàÍÕÂ´ !'
	,'no_cat' => 'ÂÑ§äÁèä´éàÅ×Í¡ËÁÇ´¡Ô¨¡ÃÃÁ !'
	,'date_invalid' => 'ÃÐºØÇÑ¹·ÕèäÁè¶Ù¡µéÍ§ !'
	,'end_days_invalid' => 'ÃÐºØ¤èÒÇÑ¹äÁè¶Ù¡µéÍ§ !'
	,'end_hours_invalid' => 'ÃÐºØ¤èÒªÑèÇâÁ§äÁè¶Ù¡µéÍ§ !'
	,'end_minutes_invalid' => 'ÃÐºØ¤èÒ¹Ò·ÕäÁè¶Ù¡µéÍ§ !'
	,'non_valid_extension' => 'ÃÙ»áººä¿ÅìÃÙ»ÀÒ¾äÁè¶Ù¡µéÍ§ ! (à©¾ÒÐ: %s à·èÒ¹Ñé¹)'
	,'file_too_large' => '¢¹Ò´ÃÙ»ÀÒ¾ãË­èà¡Ô¹ %d Kb !'
	,'move_image_failed' => 'ÃÐººäÁèÊÒÁÒÃ¶·Ó¡ÒÃÍÑ¾âËÅ´ÃÙ»ÀÒ¾ä´é ¡ÃØ³ÒµÃÇ¨ÊÍº¢¹Ò´ÃÙ»ÀÒ¾ ËÃ×Íá¨é§¼Ùé´ÙáÅÃÐºº'
	,'non_valid_dimensions' => '¤ÇÒÁ¡ÇéÒ§ËÃ×Í¤ÇÒÁÊÙ§ãË­èà¡Ô¹ %s ¾Ô¡à«Å !'
	,'recur_val_1_invalid' => 'ÃÐºØ¤èÒäÁè¶Ù¡µéÍ§ µéÍ§ÃÐºØ¤èÒ·ÕèÁÒ¡¡ÇèÒ \'0\' !'
	,'recur_end_count_invalid' => 'ÃÐºØ¤èÒäÁè¶Ù¡µéÍ§ µéÍ§ÃÐºØ¤èÒ·ÕèÁÒ¡¡ÇèÒ \'0\' !'
	,'recur_end_until_invalid' => 'ÇÑ¹·Õèã¹¡Ô¨¡ÃÃÁµèÍà¹×èÍ§µéÍ§ÁÒ¡¡ÇèÒÇÑ¹·ÕèàÃÔèÁµé¹¡Ô¨¡ÃÃÁ !'
	,'no_recur_end_date' => 'A recurring event should have an end-date or a number of occurences'
// Misc. messages
	,'submit_event_pending' => 'ä´éÃÑº¡Ô¨¡ÃÃÁ¢Í§·èÒ¹ áµè¨ÐÂÑ§äÁèáÊ´§ã¹»¯Ô·Ô¹¨¹¡ÇèÒ¨Ðä´éÃÑº¡ÒÃÍ¹ØÁÑµÔ¨Ò¡¼Ùé´ÙáÅÃÐºº!'
	,'submit_event_approved' => '¡Ô¨¡ÃÃÁ¢Í§¤Ø³ä´éÃÑº¡ÒÃÍ¹ØÁÑµÔáÅéÇ ¢Íº¤Ø³·Õèà¾ÔèÁ¡Ô¨¡ÃÃÁ!'
	,'event_repeat_msg' => '¡Ô¨¡ÃÃÁ¹Õéä´é¡ÓË¹´ãËéà»ç¹¡Ô¨¡ÃÃÁµèÍà¹×èÍ§'
	,'event_no_repeat_msg' => '¡Ô¨¡ÃÃÁ¹ÕéäÁèÊÒÁÒÃ¶µèÍà¹×èÍ§ä´é'
);

// ======================================================
// daily view
// ======================================================

$lang_daily_event_view = array(
	'section_title' => 'áÊ´§áººÃÒÂÇÑ¹'
	,'next_day' => 'ÇÑ¹¶Ñ´ä»'
	,'previous_day' => 'ÇÑ¹¡èÍ¹Ë¹éÒ'
	,'no_events' => 'äÁèÁÕ¡Ô¨¡ÃÃÁã¹ÇÑ¹¹Õé'
);

// ======================================================
// weekly view
// ======================================================

$lang_weekly_event_view = array(
	'section_title' => 'áÊ´§áººÃÒÂÊÑ»´ÒËì'
	,'week_period' => '%s - %s'
	,'next_week' => 'ÊÑ»´ÒËì¶Ñ´ä»'
	,'previous_week' => 'ÊÑ»´ÒËì·ÕèáÅéÇ'
	,'selected_week' => 'ÊÑ»´ÒËì·Õè %d'
	,'no_events' => 'äÁèÁÕ¡Ô¨¡ÃÃÁã¹ÊÑ»´ÒËì¹Õé'
);

// ======================================================
// monthly view
// ======================================================

$lang_monthly_event_view = array(
	'section_title' => 'áÊ´§áººÃÒÂà´×Í¹'
	,'next_month' => 'à´×Í¹¶Ñ´ä»'
	,'previous_month' => 'à´×Í¹·ÕèáÅéÇ'
);

// ======================================================
// flat view
// ======================================================

$lang_flat_event_view = array(
	'section_title' => 'áÊ´§áººµÒÁÅÓ´Ñº'
	,'week_period' => '%s - %s'
	,'next_month' => 'à´×Í¹¶Ñ´ä»'
	,'previous_month' => 'à´×Í¹·ÕèáÅéÇ'
	,'contact_info' => 'ÃÒÂÅÐàÍÕÂ´¡ÒÃµÔ´µèÍ'
	,'contact_email' => 'ÍÕàÁÅì'
	,'contact_url' => 'àÇçºä«µì'
	,'no_events' => 'äÁèÁÕ¡Ô¨¡ÃÃÁÊÓËÃÑºà´×Í¹¹Õé'
);

// ======================================================
// Event view
// ======================================================

$lang_event_view = array(
	'section_title' => 'áÊ´§ÃÒÂ¡ÒÃ¡Ô¨¡ÃÃÁ'
	,'display_event' => '¡Ô¨¡ÃÃÁ: \'%s\''
	,'cat_name' => 'ËÁÇ´'
	,'event_start_date' => 'àÃÔèÁÇÑ¹·Õè'
	,'event_end_date' => '¶Ö§ÇÑ¹·Õè'
	,'event_duration' => 'ªèÇ§àÇÅÒ'
	,'contact_info' => 'ÃÒÂÅÐàÍÕÂ´¡ÒÃµÔ´µèÍ'
	,'contact_email' => 'ÍÕàÁÅì'
	,'contact_url' => 'àÇçºä«µì'
	,'no_event' => 'äÁèÁÕÃÒÂ¡ÒÃ¡Ô¨¡ÃÃÁ'
	,'stats_string' => 'ÃÇÁ·Ñé§ÊÔé¹ <strong>%d</strong> ¡Ô¨¡ÃÃÁ'
	,'edit_event' => 'á¡éä¢¡Ô¨¡ÃÃÁ'
	,'delete_event' => 'Åº¡Ô¨¡ÃÃÁ'
	,'delete_confirm' => 'µéÍ§¡ÒÃÅº¡Ô¨¡ÃÃÁËÃ×ÍäÁè ?'
	
);

// ======================================================
// Categories view
// ======================================================

$lang_cats_view = array(
	'section_title' => 'áÊ´§ËÁÇ´¡Ô¨¡ÃÃÁ'
	,'cat_name' => 'ª×èÍËÁÇ´'
	,'total_events' => '¡Ô¨¡ÃÃÁ·Ñé§ËÁ´'
	,'upcoming_events' => '¡Ô¨¡ÃÃÁ·Õè¨ÐÁÒ¶Ö§'
	,'no_cats' => 'äÁèÁÕËÁÇ´¡Ô¨¡ÃÃÁ'
	,'stats_string' => 'ÁÕ¨Ó¹Ç¹ <strong>%d</strong> ¡Ô¨¡ÃÃÁ ã¹ <strong>%d</strong> ËÁÇ´'
);

// ======================================================
// Category Events view
// ======================================================

$lang_cat_events_view = array(
	'section_title' => '¡Ô¨¡ÃÃÁÀÒÂãµé \'%s\''
	,'event_name' => 'ª×èÍ¡Ô¨¡ÃÃÁ'
	,'event_date' => 'ÇÑ¹·Õè'
	,'no_events' => 'äÁèÁÕ¡Ô¨¡ÃÃÁã¹ËÁÇ´¹Õé'
	,'stats_string' => 'ÃÇÁ·Ñé§ÊÔé¹ <strong>%d</strong> ¡Ô¨¡ÃÃÁ'
	,'stats_string1' => '<strong>%d</strong> ¡Ô¨¡ÃÃÁ ã¹ <strong>%d</strong> Ë¹éÒ'
);

// ======================================================
// cal_search.php
// ======================================================

$lang_event_search_data = array(
	'section_title' => '¤é¹ËÒ¡Ô¨¡ÃÃÁ',
	'search_results' => '¼Å¡ÒÃ¤é¹ËÒ',
	'category_label' => 'ËÁÇ´',
	'date_label' => 'ÇÑ¹·Õè',
	'no_events' => 'äÁèÁÕ¡Ô¨¡ÃÃÁã¹ËÁÇ´¹Õé',
	'search_caption' => 'ÃÐºØ¤Ó·Õè¤é¹ËÒ...',
	'search_again' => '¤é¹ËÒÍÕ¡¤ÃÑé§',
	'search_button' => '¤é¹ËÒ',
// Misc.
	'no_results' => 'äÁè¾ºÃÒÂ¡ÒÃ·Õè¤é¹ËÒ',	
// Stats
	'stats_string1' => '¤é¹¾º <strong>%d</strong> ¡Ô¨¡ÃÃÁ',
	'stats_string2' => '<strong>%d</strong> ¡Ô¨¡ÃÃÁ ã¹ <strong>%d</strong> Ë¹éÒ'
);

// ======================================================
// profile.php
// ======================================================

if (defined('PROFILE_PHP')) 

$lang_user_profile_data = array(
	'section_title' => '»ÃÐÇÑµÔÊèÇ¹µÑÇ',
	'edit_profile' => 'á¡éä¢»ÃÐÇÑµÔÊèÇ¹µÑÇ',
	'update_profile' => '»ÃÑº»ÃØ§»ÃÐÇÑµÔÊèÇ¹µÑÇ',
	'actions_label' => 'Actions',
// Account Info
	'account_info_label' => 'ÃÒÂÅÐàÍÕÂ´ºÑ­ªÕ¼Ùéãªé§Ò¹',
	'user_name' => 'ª×èÍ¼Ùéãªé§Ò¹',
	'user_pass' => 'ÃËÑÊ¼èÒ¹',
	'user_pass_confirm' => 'Â×¹ÂÑ¹ÃËÑÊ¼èÒ¹',
	'user_email' => 'ÍÕàÁÅì¼Ùéãªé§Ò¹',
	'group_label' => 'ÊÁÒªÔ¡¡ÅØèÁ',
// Other Details
	'other_details_label' => 'ÃÒÂÅÐàÍÕÂ´Í×è¹æ',
	'first_name' => 'ª×èÍ',
	'last_name' => '¹ÒÁÊ¡ØÅ',
	'full_name' => 'ª×èÍàµçÁ',
	'user_website' => 'àÇçºä«µì',
	'user_location' => '·ÕèÍÂÙè',
	'user_occupation' => 'ÍÒªÕ¾',
// Misc.
	'select_language' => 'àÅ×Í¡ÀÒÉÒ',
	'edit_profile_success' => '»ÃÑº»ÃØ§»ÃÐÇÑµÔÊèÇ¹µÑÇàÃÕÂºÃéÍÂáÅéÇ',
	'update_pass_info' => 'ËÒ¡äÁèµéÍ§¡ÒÃá¡éä¢ÃËÑÊ¼èÒ¹ ãËéàÇé¹ÇèÒ§äÇé',
// Error messages
	'invalid_password' => '¡ÃØ³ÒãÊèÃËÑÊ¼èÒ¹à»ç¹µÑÇÍÑ¡ÉÃáÅÐµÑÇàÅ¢ 4 ¶Ö§ 16 µÑÇÍÑ¡ÉÃ !',
	'password_is_username' => 'ÃËÑÊ¼èÒ¹µéÍ§äÁè«éÓ¡Ñºª×èÍ¼Ùéãªé§Ò¹ !',
	'password_not_match' =>'ÃËÑÊ¼èÒ¹äÁèµÃ§¡Ñ¹¡Ñº \'Â×¹ÂÑ¹ÃËÑÊ¼èÒ¹\'',
	'invalid_email' => 'µéÍ§ÃÐºØÍÕàÁÅìãËé¶Ù¡µéÍ§ !',
	'email_exists' => 'ÍÕàÁÅì¹ÕéÁÕ¼Ùéãªé§Ò¹ÍÂÙèáÅéÇ ¡ÃØ³ÒàÅ×Í¡ÍÕàÁÅìãËÁè !',
	'no_email' => 'µéÍ§ÃÐºØÍÕàÁÅì !',
	'invalid_email' => 'µéÍ§ÃÐºØÍÕàÁÅìãËé¶Ù¡µéÍ§ !',
	'no_password' => 'µéÍ§ÃÐºØÃËÑÊ¼èÒ¹ !'
);

// ======================================================
// register.php
// ======================================================

if (defined('USER_REGISTRATION_PHP')) 

$lang_user_registration_data = array(
	'section_title' => 'Å§·ÐàºÕÂ¹¼Ùéãªé§Ò¹',
// Step 1: Terms & Conditions
	'terms_caption' => 'Terms and Conditions',
	'terms_intro' => 'In order to proceed, you must agree to the following:',
	'terms_message' => 'Please take a moment to review these rules detailed below. If you agree with them and wish to proceed with the registration, simply click the "I agree" button below. To cancel this registration, simply hit the \'back\' button on your browser.<br /><br />Please remember that we are not responsible for any events posted by users of this calendar application. We do not vouch for or warrant the accuracy, completeness or usefulness of any posted event, and are not responsible for the contents of any event.<br /><br />The messages express the views of the author of the event, not necessarily the views of this calendar application. Any user who feels that a posted event is objectionable is encouraged to contact us immediately by email. We have the ability to remove objectionable content and we will make every effort to do so, within a reasonable time frame, if we determine that removal is necessary.<br /><br />You agree, through your use of this service, that you will not use this calendar application to post any material which is knowingly false and/or defamatory, inaccurate, abusive, vulgar, hateful, harassing, obscene, profane, sexually oriented, threatening, invasive of a person\'s privacy, or otherwise violative of any law.<br /><br />You agree not to post any copyrighted material unless the copyright is owned by you or by %s.',
	'terms_button' => 'ÂÍÁÃÑº',
	
// Account Info
	'account_info_label' => 'ÃÒÂÅÐàÍÕÂ´ºÑ­ªÕ¼Ùéãªé§Ò¹',
	'user_name' => 'ª×èÍ¼Ùéãªé§Ò¹',
	'user_pass' => 'ÃËÑÊ¼èÒ¹',
	'user_pass_confirm' => 'Â×¹ÂÑ¹ÃËÑÊ¼èÒ¹',
	'user_email' => 'ÍÕàÁÅì',
// Other Details
	'other_details_label' => 'ÃÒÂÅÐàÍÕÂ´Í×è¹æ',
	'first_name' => 'ª×èÍ',
	'last_name' => '¹ÒÁÊ¡ØÅ',
	'user_website' => 'àÇçºä«µì',
	'user_location' => '·ÕèÍÂÙè',
	'user_occupation' => 'ÍÒªÕ¾',
	'register_button' => 'Å§·ÐàºÕÂ¹',

// Stats
	'stats_string1' => '<strong>%d</strong> ¼Ùéãªé§Ò¹',
	'stats_string2' => '<strong>%d</strong> ¼Ùéãªé§Ò¹ ¨Ó¹Ç¹ <strong>%d</strong> Ë¹éÒ',
// Misc.
	'reg_nomail_success' => '¢Íº¤Ø³',
	'reg_mail_success' => 'ÃÒÂÅÐàÍÕÂ´¡ÒÃÂ×¹ÂÑ¹¡ÒÃÅ§·ÐàºÕÂ¹ä´éÊè§ãËé·Ò§ÍÕàÁÅì·Õè¤Ø³ÃÐºØáÅéÇ',
	'reg_activation_success' => '¤Ø³ä´éÅ§·ÐàºÕÂ¹àÃÕÂºÃéÍÂáÅéÇ ¤Ø³ÊÒÁÒÃ¶à¢éÒÊÙèÃÐºº´éÇÂª×èÍáÅÐÃËÑÊ¼èÒ¹¢Í§¤Ø³',
// Mail messages
	'reg_confirm_subject' => 'Â×¹ÂÑ¹¡ÒÃÅ§·ÐàºÕÂ¹ %s',
	
// Error messages
	'no_username' => 'µéÍ§ÃÐºØª×èÍ¼Ùéãªé§Ò¹ !',
	'invalid_username' => 'â»Ã´ÃÐºØª×èÍà»ç¹à©¾ÒÐµÑÇÍÑ¡ÉÃËÃ×ÍµÑÇàÅ¢ ¨Ó¹Ç¹ 4 ¶Ö§ 30 µÑÇÍÑ¡ÉÃ !',
	'username_exists' => 'èÁÕª×èÍ¼Ùéãªé§Ò¹¹ÕéÍÂÙèáÅéÇ ¡ÃØ³ÒÃÐºØª×èÍÍ×è¹ !',
	'no_password' => 'µéÍ§ÃÐºØÃËÑÊ¼èÒ¹ !',
	'invalid_password' => '¡ÃØ³ÒãÊèÃËÑÊ¼èÒ¹à»ç¹µÑÇÍÑ¡ÉÃáÅÐµÑÇàÅ¢ 4 ¶Ö§ 16 µÑÇÍÑ¡ÉÃ !',
	'password_is_username' => 'ÃËÑÊ¼èÒ¹µéÍ§äÁè«éÓ¡Ñºª×èÍ¼Ùéãªé§Ò¹ !',
	'password_not_match' =>'ÃËÑÊ¼èÒ¹äÁèµÃ§¡Ñ¹¡Ñº \'Â×¹ÂÑ¹ÃËÑÊ¼èÒ¹\'',
	'no_email' => 'µéÍ§ÃÐºØÍÕàÁÅì !',
	'invalid_email' => 'µéÍ§ÃÐºØÍÕàÁÅìãËé¶Ù¡µéÍ§ !',
	'email_exists' => 'ÍÕàÁÅì¹ÕéÁÕ¼Ùéãªé§Ò¹ÍÂÙèáÅéÇ ¡ÃØ³ÒàÅ×Í¡ÍÕàÁÅìãËÁè !',
	'delete_user_failed' => 'äÁèÊÒÁÒÃ¶ÅººÑ­ªÕ¼Ùéãªé§Ò¹¹Õé',
	'no_users' => 'äÁèÁÕÃÒÂ¡ÒÃ¼Ùéãªé§Ò¹ !',
	'already_logged' => '¤Ø³à¢éÒÊÙèÃÐººáÅéÇ !',
	'registration_not_allowed' => 'ÃÐ§Ñº¡ÒÃãªé§Ò¹ÃÐººÅ§·ÐàºÕÂ¹ !',
	'reg_email_failed' => '¾º¢éÍ¼Ô´¾ÅÒ´ã¹¡ÒÃÊè§ÍÕàÁÅì !',
	'reg_activation_failed' => '¾º¢éÍ¼Ô´¾ÅÒ´ã¹¡ÒÃ·ÓÃÒÂ¡ÒÃ¹Õé !'
);
// Message body for email activation
$lang_user_registration_data['reg_confirm_body'] = <<<EOT
Â×¹ÂÑ¹¡ÒÃÅ§·ÐàºÕÂ¹ {CALENDAR_NAME}

ª×èÍ¼Ùéãªé§Ò¹ : "{USERNAME}"
ÃËÑÊ¼èÒ¹ : "{PASSWORD}"

à¾×èÍÂ×¹ÂÑ¹¡ÒÃÅ§·ÐàºÕÂ¹¤Ø³µéÍ§¤ÅÔê¡ÅÔê§¤ì¢éÒ§ÅèÒ§ ËÃ×Í copy ä»à»Ô´ã¹ºÃÒÇà«ÍÃìä´é

{REG_LINK}

¢ÍáÊ´§¤ÇÒÁ¹Ñº¶×Í

¼ÙéºÃÔËÒÃ¨Ñ´¡ÒÃ {CALENDAR_NAME}

EOT;

// ======================================================
// theme.php
// ======================================================

// To Be Done

// ======================================================
// functions.inc.php
// ======================================================

// To Be Done

// ======================================================
// dblib.php
// ======================================================

// To Be Done

// ======================================================
// admin_events.php
// ======================================================

if (defined('ADMIN_EVENTS_PHP')) 

$lang_event_admin_data = array(
	'section_title' => '¨Ñ´¡ÒÃÃÒÂ¡ÒÃ¡Ô¨¡ÃÃÁ',
	'events_to_approve' => '¨Ñ´¡ÒÃÃÒÂ¡ÒÃ¡Ô¨¡ÃÃÁ: Â×¹ÂÑ¹¡Ô¨¡ÃÃÁ',
	'upcoming_events' => '¨Ñ´¡ÒÃÃÒÂ¡ÒÃ¡Ô¨¡ÃÃÁ: ¡Ô¨¡ÃÃÁ·Õè¨ÐÁÒ¶Ö§',
	'past_events' => '¨Ñ´¡ÒÃÃÒÂ¡ÒÃ¡Ô¨¡ÃÃÁ: ¡Ô¨¡ÃÃÁ·Õè¼èÒ¹ÁÒ',
	'add_event' => 'à¾ÔèÁ¡Ô¨¡ÃÃÁ',
	'edit_event' => 'á¡éä¢¡Ô¨¡ÃÃÁ',
	'view_event' => 'áÊ´§¡Ô¨¡ÃÃÁ',
	'approve_event' => 'Â×¹ÂÑ¹¡Ô¨¡ÃÃÁ',
	'update_event' => '»ÃÑº»ÃØ§¡Ô¨¡ÃÃÁ',
	'delete_event' => 'Åº¡Ô¨¡ÃÃÁ',
	'events_label' => '¡Ô¨¡ÃÃÁ',
	'auto_approve' => 'Â×¹ÂÑ¹ÍÑµâ¹ÁÑµÔ',
	'date_label' => 'ÇÑ¹·Õè',
	'actions_label' => 'Actions',
	'events_filter_label' => '¡ÃÍ§ÃÒÂ¡ÒÃ¡Ô¨¡ÃÃÁ',
	'events_filter_options' => array('áÊ´§¡Ô¨¡ÃÃÁ·Ñé§ËÁ´','à©¾ÒÐ¡Ô¨¡ÃÃÁ·ÕèÂÑ§äÁèÂ×¹ÂÑ¹','áÊ´§¡Ô¨¡ÃÃÁ·Õè¨ÐÁÒ¶Ö§','à©¾ÒÐ¡Ô¨¡ÃÃÁ·Õè¼èÒ¹ÁÒ'),
	'picture_attached' => 'á¹ºÃÙ»ÀÒ¾',
// View Event
	'view_event_name' => '¡Ô¨¡ÃÃÁ: \'%s\'',
	'event_start_date' => 'ÇÑÕ¹·Õè',
	'event_end_date' => '¨¹¶Ö§',
	'event_duration' => 'ªèÇ§àÇÅÒ',
	'contact_info' => 'ÃÒÂÅÐàÍÕÂ´¡ÒÃµÔ´µèÍ',
	'contact_email' => 'ÍÕàÁÅì',
	'contact_url' => 'àÇçºä«µì',
// General Info
// Event form
	'edit_event_title' => '¡Ô¨¡ÃÃÁ: \'%s\'',
	'cat_name' => 'ËÁÇ´¡Ô¨¡ÃÃÁ',
	'event_start_date' => 'ÇÑ¹·Õè',
	'event_end_date' => '¨¹¶Ö§',
	'contact_info' => 'ÃÒÂÅÐàÍÕÂ´¡ÒÃµÔ´µèÍ',
	'contact_email' => 'ÍÕàÁÅì',
	'contact_url' => 'àÇçºä«µì',
	'no_event' => 'äÁèÁÕÃÒÂ¡ÒÃ¡Ô¨¡ÃÃÁ',
	'stats_string' => '·Ñé§ËÁ´ <strong>%d</strong> ¡Ô¨¡ÃÃÁ',
// Stats
	'stats_string1' => '<strong>%d</strong> ÃÒÂ¡ÒÃ',
	'stats_string2' => '·Ñé§ËÁ´: <strong>%d</strong> ÃÒÂ¡ÒÃ ¨Ó¹Ç¹ <strong>%d</strong> Ë¹éÒ',
// Misc.
	'add_event_success' => 'à¾ÔèÁ¡Ô¨¡ÃÃÁãËÁèàÃÕÂºÃéÍÂáÅéÇ',
	'edit_event_success' => 'á¡éä¢ÃÒÂ¡ÒÃ¡Ô¨¡ÃÃÁàÃÕÂºÃéÍÂáÅéÇ',
	'approve_event_success' => '»ÃÑº»ÃØ§ÃÒÂ¡ÒÃ¡Ô¨¡ÃÃÁàÃÕÂºÃéÍÂáÅéÇ',
	'delete_confirm' => 'µéÍ§¡ÒÃÅºÃÒÂ¡ÒÃ¡Ô¨¡ÃÃÁËÃ×ÍäÁè ?',
	'delete_event_success' => 'ÅºÃÒÂ¡ÒÃ¡Ô¨¡ÃÃÁáÅéÇ',
	'active_label' => 'áÊ´§',
	'not_active_label' => 'äÁèáÊ´§',
// Error messages
	'no_event_name' => 'µéÍ§ÃÐºØª×èÍ¡Ô¨¡ÃÃÁ !',
	'no_event_desc' => 'µéÍ§ÃÐºØÃÒÂÅÐàÍÕÂ´ !',
	'no_cat' => 'µéÍ§àÅ×Í¡ËÁÇ´¡Ô¨¡ÃÃÁ !',
	'no_day' => 'µéÍ§ÃÐºØÇÑ¹ !',
	'no_month' => 'µéÍ§ÃÐºØà´×Í¹ !',
	'no_year' => 'µéÍ§ÃÐºØ»Õ !',
	'non_valid_date' => 'ÃÐºØÇÑ¹ãËé¶Ù¡µéÍ§ !',
	'end_days_invalid' => 'ªèÍ§ \'ÇÑ¹\' ã¹ÊèÇ¹¢Í§ \'ÃÐÂÐàÇÅÒ\' µéÍ§à»ç¹µÑÇàÅ¢à·èÒ¹Ñé¹ !',
	'end_hours_invalid' => 'ªèÍ§ \'ªÑèÇâÁ§\' ã¹ÊèÇ¹¢Í§ \'ÃÐÂÐàÇÅÒ\' µéÍ§à»ç¹µÑÇàÅ¢à·èÒ¹Ñé¹ !',
	'end_minutes_invalid' => 'ªèÍ§ \'¹Ò·Õ\' ã¹ÊèÇ¹¢Í§ \'ÃÐÂÐàÇÅÒ\' µéÍ§à»ç¹µÑÇàÅ¢à·èÒ¹Ñé¹ !',
	'file_too_large' => '¢¹Ò´ÀÒ¾ãË­èà¡Ô¹ %d Kb !',
	'non_valid_extension' => 'ÃÙ»áººä¿ÅìÃÙ»ÀÒ¾äÁè¶Ù¡µéÍ§ !',
	'delete_event_failed' => 'äÁèÊÒÁÒÃ¶ÅºÃÒÂ¡ÒÃ¡Ô¨¡ÃÃÁ¹Õéä´é',
	'approve_event_failed' => 'ÂÑ§äÁèÁÕ¡ÒÃÂ×¹ÂÑ¹¡Ô¨¡ÃÃÁ¹Õé',
	'no_events' => 'äÁèÁÕÃÒÂ¡ÒÃ¡Ô¨¡ÃÃÁ !',
	'move_image_failed' => 'äÁèÊÒÁÒÃ¶ÍÑ¾âËÅ´ÃÙ»ÀÒ¾ä´é !',
	'non_valid_dimensions' => '¢¹Ò´ÀÒ¾¡ÇéÒ§ ËÃ×ÍÊÙ§à¡Ô¹ %s ¾Ô¡à«Å !',
	'recur_val_1_invalid' => '¤èÒ·ÕèÃÐºØã¹ÊèÇ¹ \'áÊ´§«éÓ·Ø¡æ\' µéÍ§ÁÒ¡¡ÇèÒ \'0\' !',
	'recur_end_count_invalid' => '¤èÒ·ÕèÃÐºØã¹ÊèÇ¹ \'ÊÔé¹ÊØ´ËÅÑ§¨Ò¡\' µéÍ§ÁÒ¡¡ÇèÒ \'0\' !',
	'recur_end_until_invalid' => 'ÇÑ¹·Õè·ÕèÃÐºØã¹ÊèÇ¹ \'áÊ´§«éÓ¨¹¶Ö§ÇÑ¹·Õè\' µéÍ§ÁÒ¡¡ÇèÒÇÑ¹àÃÔèÁµé¹ !'
);

// ======================================================
// admin_categories.php
// ======================================================

if (defined('ADMIN_CATS_PHP')) 

$lang_cat_admin_data = array(
	'section_title' => '¨Ñ´¡ÒÃËÁÇ´¡Ô¨¡ÃÃÁ',
	'add_cat' => 'à¾ÔèÁËÁÇ´¡Ô¨¡ÃÃÁ',
	'edit_cat' => 'á¡éä¢ËÁÇ´¡Ô¨¡ÃÃÁ',
	'update_cat' => '»ÃÑº»ÃØ§ËÁÇ´¡Ô¨¡ÃÃÁ',
	'delete_cat' => 'ÅºËÁÇ´¡Ô¨¡ÃÃÁ',
	'events_label' => '¡Ô¨¡ÃÃÁ',
	'visibility' => 'áÊ´§',
	'actions_label' => 'Actions',
	'users_label' => '¼Ùéãªé§Ò¹',
	'admins_label' => 'Admins',
// General Info
	'general_info_label' => 'ÃÒÂÅÐàÍÕÂ´·ÑèÇä»',
	'cat_name' => 'ª×èÍËÁÇ´',
	'cat_desc' => 'ÃÒÂÅÐàÍÕÂ´',
	'cat_color' => 'ÊÕ',
	'pick_color' => 'àÅ×Í¡ÊÕ!',
	'status_label' => 'Ê¶Ò¹Ð',
// Administrative Options
	'admin_label' => 'ÊèÇ¹¢Í§¼Ùé´ÙáÅÃÐºº',
	'auto_admin_appr' => 'Â×¹ÂÑ¹¡Ô¨¡ÃÃÁ¢Í§¼Ùé´ÙáÅÃÐººâ´ÂÍÑµâ¹ÁÑµÔ',
	'auto_user_appr' => 'Â×¹ÂÑ¹¡Ô¨¡ÃÃÁ¢Í§¼Ùéãªé§Ò¹â´ÂÍÑµâ¹ÁÑµÔ',
// Stats
	'stats_string1' => '<strong>%d</strong> ËÁÇ´',
	'stats_string2' => 'áÊ´§: <strong>%d</strong>&nbsp;&nbsp;&nbsp;Total: <strong>%d</strong>&nbsp;&nbsp;&nbsp;on <strong>%d</strong> page(s)',
// Misc.
	'add_cat_success' => 'à¾ÔèÁËÁÇ´¡Ô¨¡ÃÃÁàÃÕÂºÃéÍÂáÅéÇ',
	'edit_cat_success' => '»ÃÑº»ÃØ§ËÁÇ´¡Ô¨¡ÃÃÁàÃÕÂºÃéÍÂáÅéÇ',
	'delete_confirm' => 'µéÍ§¡ÒÃÅºËÁÇ´¡Ô¨¡ÃÃÁ¹ÕéËÃ×ÍäÁè ?',
	'delete_cat_success' => 'ÅºËÁÇ´¡Ô¨¡ÃÃÁàÃÕÂºÃéÍÂáÅéÇ',
	'active_label' => 'áÊ´§',
	'not_active_label' => 'äÁèáÊ´§',
// Error messages
	'no_cat_name' => 'µéÍ§ÃÐºØª×èÍËÁÇ´¡Ô¨¡ÃÃÁ !',
	'no_cat_desc' => 'µéÍ§ÃÐºØÃÒÂÅÐàÍÕÂ´ËÁÇ´¡Ô¨¡ÃÃÁ !',
	'no_color' => 'µéÍ§ÃÐºØÊÕÊÓËÃÑºËÁÇ´¡Ô¨¡ÃÃÁ !',
	'delete_cat_failed' => 'äÁèÊÒÁÒÃ¶ÅºËÁÇ´¡Ô¨¡ÃÃÁ¹Õéä´é',
	'no_cats' => 'äÁèÁÕËÁÇ´¡Ô¨¡ÃÃÁ !',
	'cat_has_events' => 'äÁèÊÒÁÒÃ¶Åºä´é à¾ÃÒÐËÁÇ´¡Ô¨¡ÃÃÁ¹ÕéÁÕÃÒÂ¡ÒÃÍÂÙè %d ¡Ô¨¡ÃÃÁ!<br>µéÍ§ÅºÃÒÂ¡ÒÃ¡Ô¨¡ÃÃÁÍÍ¡¡èÍ¹ ¨Ö§¨ÐÅºËÁÇ´¡Ô¨¡ÃÃÁä´é!'

);
// ======================================================
// admin_users.php
// ======================================================

if (defined('ADMIN_USERS_PHP')) 

$lang_user_admin_data = array(
	'section_title' => '¡ÒÃ¨Ñ´¡ÒÃ¼Ùéãªé§Ò¹',
	'add_user' => 'à¾ÔèÁ¼Ùéãªé§Ò¹ãËÁè',
	'edit_user' => 'á¡éä¢ÃÒÂÅÐàÍÕÂ´¼Ùéãªé§Ò¹',
	'update_user' => '»ÃÑº»ÃØ§ºÑ­ªÕ¼Ùéãªé§Ò¹',
	'delete_user' => 'ÅººÑ­ªÕ¼Ùéãªé§Ò¹',
	'last_access' => 'à¢éÒ¤ÃÑé§ÊØ´·éÒÂ',
	'actions_label' => 'Actions',
	'active_label' => 'áÊ´§',
	'not_active_label' => 'äÁèáÊ´§',
// Account Info
	'account_info_label' => 'ÃÒÂÅÐàÍÕÂ´ºÑ­ªÕ',
	'user_name' => 'ª×èÍ¼Ùéãªé§Ò¹',
	'user_pass' => 'ÃËÑÊ¼èÒ¹',
	'user_pass_confirm' => 'Â×¹ÂÑ¹ÃËÑÊ¼èÒ¹',
	'user_email' => 'ÍÕàÁÅì',
	'group_label' => '¡ÅØèÁ¼Ùéãªé§Ò¹',
	'status_label' => 'Ê¶Ò¹Ð',
// Other Details
	'other_details_label' => 'ÃÒÂÅÐàÍÕÂ´Í×è¹æ',
	'first_name' => 'ª×èÍ',
	'last_name' => '¹ÒÁÊ¡ØÅ',
	'user_website' => 'àÇçºä«µì',
	'user_location' => '·ÕèÍÂÙè',
	'user_occupation' => 'ÍÒªÕ¾',
// Stats
	'stats_string1' => '<strong>%d</strong> ¼Ùéãªé§Ò¹',
	'stats_string2' => '<strong>%d</strong> ¼Ùéãªé§Ò¹ ¨Ó¹Ç¹ <strong>%d</strong> Ë¹éÒ',
// Misc.
	'select_group' => 'àÅ×Í¡ÃÒÂ¡ÒÃ...',
	'add_user_success' => 'à¾ÔèÁºÑ­ªÕ¼Ùéãªé§Ò¹àÃÕÂºÃéÍÂáÅéÇ',
	'edit_user_success' => '»ÃÑº»ÃØ§ºÑ­ªÕ¼Ùéãªé§Ò¹àÃÕÂºÃéÍÂáÅéÇ',
	'delete_confirm' => 'µéÍ§¡ÒÃÅººÑ­ªÕ¼Ùéãªé§Ò¹¹ÕéËÃ×ÍäÁè ?',
	'delete_user_success' => 'ÅººÑ­ªÕ¼Ùéãªé§Ò¹àÃÕÂºÃéÍÂáÅéÇ',
	'update_pass_info' => 'ËÒ¡äÁèµéÍ§¡ÒÃá¡éä¢ÃËÑÊ¼èÒ¹ ãËéàÇé¹ÇèÒ§äÇé',
	'access_never' => 'äÁèà¤Â',
// Error messages
	'no_username' => 'µéÍ§ÃÐºØª×èÍ¼Ùéãªé§Ò¹ !',
	'invalid_username' => 'â»Ã´ÃÐºØª×èÍà»ç¹à©¾ÒÐµÑÇÍÑ¡ÉÃËÃ×ÍµÑÇàÅ¢ ¨Ó¹Ç¹ 4 ¶Ö§ 30 µÑÇÍÑ¡ÉÃ !',
	'invalid_password' => '¡ÃØ³ÒãÊèÃËÑÊ¼èÒ¹à»ç¹µÑÇÍÑ¡ÉÃáÅÐµÑÇàÅ¢ 4 ¶Ö§ 16 µÑÇÍÑ¡ÉÃ !',
	'password_is_username' => 'ÃËÑÊ¼èÒ¹µéÍ§äÁè«éÓ¡Ñºª×èÍ¼Ùéãªé§Ò¹ !',
	'password_not_match' =>'ÃËÑÊ¼èÒ¹äÁèµÃ§¡Ñ¹¡Ñº \'Â×¹ÂÑ¹ÃËÑÊ¼èÒ¹\'',
	'invalid_email' => 'µéÍ§ÃÐºØÍÕàÁÅìãËé¶Ù¡µéÍ§ !',
	'email_exists' => 'ÍÕàÁÅì¹ÕéÁÕ¼Ùéãªé§Ò¹ÍÂÙèáÅéÇ ¡ÃØ³ÒàÅ×Í¡ÍÕàÁÅìãËÁè !',
	'username_exists' => 'ª×èÍ¼Ùéãªé§Ò¹¹ÕéÁÕ¼Ùéãªé§Ò¹ÍÂÙèáÅéÇ ¡ÃØ³ÒàÅ×Í¡ª×èÍ¼Ùéãªé§Ò¹ãËÁè !',
	'no_email' => 'µéÍ§ÃÐºØÍÕàÁÅì !',
	'invalid_email' => 'µéÍ§ÃÐºØÍÕàÁÅìãËé¶Ù¡µéÍ§ !',
	'no_password' => 'µéÍ§ÃÐºØÃËÑÊ¼èÒ¹ !',
	'no_group' => 'àÅ×Í¡¡ÅØèÁÊÓËÃÑº¼Ùéãªé§Ò¹¹Õé !',
	'delete_user_failed' => 'äÁèÊÒÁÒÃ¶Åº¼Ùéãªé§Ò¹¹Õéä´é',
	'no_users' => 'äÁèÁÕºÑ­ªÕ¼Ùéãªé§Ò¹ !'

);

// ======================================================
// admin_groups.php
// ======================================================

if (defined('ADMIN_GROUPS_PHP')) 

$lang_group_admin_data = array(
	'section_title' => '¡ÅØèÁ¼Ùéãªé§Ò¹',
	'add_group' => 'à¾ÔèÁ¡ÅØèÁãËÁè',
	'edit_group' => 'á¡éä¢¡ÅØèÁ',
	'update_group' => '»ÃÑº»ÃØ§ÃÒÂÅÐàÍÕÂ´¡ÅØèÁ',
	'delete_group' => 'Åº¡ÅØèÁ',
	'view_group' => '´Ù¡ÅØèÁ',
	'users_label' => 'ÊÁÒªÔ¡',
	'actions_label' => 'Actions',
// General Info
	'general_info_label' => 'ÃÒÂÅÐàÍÕÂ´·ÑèÇä»',
	'group_name' => 'ª×èÍ¡ÅØèÁ',
	'group_desc' => 'ÃÒÂÅÐàÍÕÂ´',
// Group Access Level
	'access_level_label' => 'ÃÐ´Ñº¡ÒÃà¢éÒ¶Ö§',
	'Administrator' => 'à©¾ÒÐ¼Ùé´ÙáÅÃÐºº ¨Ö§¨ÐÊÒÁÒÃ¶·ÓÃÒÂ¡ÒÃä´é',
	'can_manage_accounts' => '¡ÅØèÁ¼Ùéãªé§Ò¹¹Õé ÊÒÁÒÃ¶¨Ñ´¡ÒÃºÑ­ªÕ¼Ùéãªé§Ò¹ä´é',
	'can_change_settings' => '¡ÅØèÁ¼Ùéãªé§Ò¹¹Õé ÊÒÁÒÃ¶á¡éä¢»¯Ô·Ô¹ä´é',
	'can_manage_cats' => '¡ÅØèÁ¼Ùéãªé§Ò¹¹Õé ÊÒÁÒÃ¶¨Ñ´¡ÒÃËÁÇ´¡Ô¨¡ÃÃÁä´é',
	'upl_need_approval' => '¡ÒÃÊè§ÃÒÂ¡ÒÃ¡Ô¨¡ÃÃÁµéÍ§ÁÕ¡ÒÃÂ×¹ÂÑ¹¨Ò¡¼Ùé´ÙáÅÃÐºº',
// Stats
	'stats_string1' => '<strong>%d</strong> ¡ÅØèÁ',
	'stats_string2' => '·Ñé§ËÁ´: <strong>%d</strong> ¡ÅØèÁ ¨Ó¹Ç¹ <strong>%d</strong> Ë¹éÒ',
	'stats_string3' => '·Ñé§ËÁ´: <strong>%d</strong> ¼Ùéãªé§Ò¹ ¨Ó¹Ç¹ <strong>%d</strong> Ë¹éÒ',
// View Group Members
	'group_members_string' => 'ÊÁÒªÔ¡¡ÅØèÁ \'%s\' ',
	'username_label' => 'ª×èÍ¼Ùéãªé§Ò¹',
	'firstname_label' => 'ª×èÍ',
	'lastname_label' => '¹ÒÁÊ¡ØÅ',
	'email_label' => 'ÍÕàÁÅì',
	'last_access_label' => 'à¢éÒ¤ÃÑé§ÊØ´·éÒÂ',
	'edit_user' => 'á¡éä¢¼Ùéãªé§Ò¹',
	'delete_user' => 'Åº¼Ùéãªé§Ò¹',
// Misc.
	'add_group_success' => 'à¾ÔèÁ¡ÅØèÁàÃÕÂºÃéÍÂáÅéÇ',
	'edit_group_success' => '»ÃÑº»ÃØ§¡ÅØèÁàÃÕÂºÃéÍÂáÅéÇ',
	'delete_confirm' => 'µéÍ§¡ÒÃÅº¡ÅØèÁ¹ÕéËÃ×ÍäÁè ?',
	'delete_user_confirm' => 'µéÍ§¡ÒÃÅº¼Ùéãªé§Ò¹¹ÕéËÃ×ÍäÁè ?',
	'delete_group_success' => 'Åº¡ÅØèÁàÃÕÂºÃéÍÂáÅéÇ',
	'no_users_string' => 'äÁèÁÕ¼Ùéãªé§Ò¹ã¹¡ÅØèÁ¹Õé',
// Error messages
	'no_group_name' => 'µéÍ§ÃÐºØª×èÍ¡ÅØèÁ !',
	'no_group_desc' => 'µéÍ§ÃÐºØÃÒÂÅÐàÍÕÂ´¡ÅØèÁ !',
	'delete_group_failed' => 'äÁèÊÒÁÒÃ¶Åº¡ÅØèÁ¹Õéä´é',
	'no_groups' => 'äÁèÁÕ¡ÅØèÁãËéáÊ´§ !',
	'group_has_users' => 'äÁèÊÒÁÒÃ¶Åºä´é à¾ÃÒÐ¡ÅØèÁ¹ÕéÁÕ¼Ùéãªé§Ò¹ÍÂÙè %d ¼Ùéãªé§Ò¹!<br>µéÍ§Åº¼Ùéãªé§Ò¹ÍÍ¡¡èÍ¹ ¨Ö§¨ÐÅº¡ÅØèÁä´é!'
);

// ======================================================
// admin_settings.php / admin_settings_template.php / 
// admin_settings_updates.php
// ======================================================

$lang_settings_data = array(
	'section_title' => 'µÑé§¤èÒ»¯Ô·Ô¹'
// Links
	,'admin_links_text' => 'àÅ×Í¡ÃÒÂ¡ÒÃ'
	,'admin_links' => array('µÑé§¤èÒËÅÑ¡','¡ÒÃµÑé§¤èÒà·Áà¾Åµ','»ÃÑº»ÃØ§ÊÔ¹¤éÒ')
// General Settings
	,'general_settings_label' => '¡ÒÃµÑé§¤èÒ·ÑèÇä»'
	,'calendar_name' => 'ª×èÍ»¯Ô·Ô¹'
	,'calendar_description' => 'ÃÒÂÅÐàÕÍÕÂ´'
	,'calendar_admin_email' => 'ÍÕàÁÅì¢Í§¼ÙéºÃÔËÒÃ»¯Ô·Ô¹¡Ô¨¡ÃÃÁ'
	,'cookie_name' => 'ª×èÍ¤Øê¡¡Õé'
	,'cookie_path' => '¾Ò¸·Õèà¡çº¤Øê¡¡Õé'
	,'debug_mode' => 'áÊ´§âËÁ´á¡éä¢'
	,'calendar_status' => 'áÊ´§»¯Ô·Ô¹áººÊÒ¸ÒÃ³Ð'
// Environment Settings
	,'env_settings_label' => '¡ÒÃµÑé§¤èÒÊÀÒ¾áÇ´ÅéÍÁ'
	,'lang' => 'ÀÒÉÒ'
		,'lang_name' => 'ÀÒÉÒ'
		,'lang_native_name' => 'ª×èÍ¾×é¹àÁ×Í§'
		,'lang_trans_date' => 'á»ÅàÁ×èÍ'
		,'lang_author_name' => '¼ÙéÊÃéÒ§'
		,'lang_author_email' => 'ÍÕàÁÅì'
		,'lang_author_url' => 'àÇçºä«µì'
	,'charset' => 'ÃËÑÊÍÑ¡¢ÃÐ'
	,'theme' => '¸ÕÁ'
		,'theme_name' => 'ª×èÍ¸ÕÁ'
		,'theme_date_made' => 'ÊÃéÒ§àÁ×èÍ'
		,'theme_author_name' => '¼ÙéÊÃéÒ§'
		,'theme_author_email' => 'ÍÕàÁÅì'
		,'theme_author_url' => 'àÇçºä«µì'
	,'timezone' => '¤èÒàËÅ×èÍÁàÇÅÒ'
	,'time_format' => 'ÃÙ»áºº¡ÒÃáÊ´§àÇÅÒ'
		,'24hours' => '24 ªÑèÇâÁ§'
		,'12hours' => '12 ªÑèÇâÁ§'
	,'auto_daylight_saving' => 'µÑé§¤èÒ Daylight Saving Time (DST) áººÍÑµâ¹ÁÑµÔ'
	,'main_table_width' => '¤ÇÒÁ¡ÇéÒ§¢Í§µÒÃÒ§ËÅÑ¡ (¾Ô¡à«Å ËÃ×Í %)'
	,'day_start' => 'ÇÑ¹àÃÔèÁµé¹ÊÑ»´ÒËì'
	,'default_view' => 'áÊ´§áºº»¡µÔ'
	,'search_view' => 'áÊ´§¡ÒÃ¤é¹ËÒ'
	,'archive' => 'áÊ´§¡Ô¨¡ÃÃÁ·Õè¼èÒ¹ÁÒ'
	,'events_per_page' => '¨Ó¹Ç¹¡Ô¨¡ÃÃÁµèÍË¹éÒ'
	,'sort_order' => '¡ÒÃàÃÕÂ§ÅÓ´Ñº'
		,'sort_order_title_a' => 'àÃÕÂ§µÒÁËÑÇ¢éÍ ¡-Î'
		,'sort_order_title_d' => 'àÃÕÂ§µÒÁËÑÇ¢éÍ Î-¡'
		,'sort_order_date_a' => 'àÃÕÂ§µÒÁÇÑ¹·Õè¹éÍÂä»ËÒÁÒ¡'
		,'sort_order_date_d' => 'àÃÕÂ§µÒÁÇÑ¹·ÕèÁÒ¡ÁÒËÒ¹éÍÂ'
	,'show_recurrent_events' => 'áÊ´§¡Ô¨¡ÃÃÁ·Õèà¡Ô´«éÓ'
	,'multi_day_events' => '¡Ô¨¡ÃÃÁáººËÅÒÂÇÑ¹'
		,'multi_day_events_all' => 'áÊ´§·Ø¡ªèÇ§¡Ô¨¡ÃÃÁ'
		,'multi_day_events_bounds' => 'áÊ´§ÇÑ¹àÃÔèÁµé¹ & áÅÐÇÑ¹ÊÔ¹ÊØ´¡Ô¨¡ÃÃÁ'
		,'multi_day_events_start' => 'áÊ´§à©¾ÒÐÇÑ¹àÃÔèÁµé¹¡Ô¨¡ÃÃÁ'
	// User Settings
	,'user_settings_label' => '¡ÒÃµÑé§¤èÒ¼Ùéãªé§Ò¹'
	,'allow_user_registration' => 'Í¹Ø­ÒµãËé¼ÙéãªéÅ§·ÐàºÕÂ¹'
	,'reg_duplicate_emails' => 'ËéÒÁÁÕÍÕàÁÅì«éÓ¡Ñ¹'
	,'reg_email_verify' => 'ãªé¡ÒÃÂ×¹ÂÑ¹Å§·ÐàºÕÂ¹'
// Event View
	,'event_view_label' => 'áÊ´§¡Ô¨¡ÃÃÁ'
	,'popup_event_mode' => 'áÊ´§áººË¹éÒµèÒ§»êÍºÍÑ¾'
	,'popup_event_width' => '¤ÇÒÁ¡ÇéÒ§¢Í§Ë¹éÒµèÒ§»êÍºÍÑ¾'
	,'popup_event_height' => '¤ÇÒÁÊÙ§¢Í§Ë¹éÒµèÒ§»êÍºÍÑ¾'
// Add Event View
	,'add_event_view_label' => 'à¾ÔèÁ¡Ô¨¡ÃÃÁ'
	,'add_event_view' => 'áÊ´§'
	,'addevent_allow_html' => 'ãªé <b>BB Code</b> ã¹ÊèÇ¹ÃÒÂÅÐàÍÕÂ´'
	,'addevent_allow_contact' => 'áÊ´§ÃÒÂÅÐàÍÕÂ´¡ÒÃµÔ´µèÍ'
	,'addevent_allow_email' => 'áÊ´§ÍÕàÁÅì'
	,'addevent_allow_url' => 'áÊ´§àÇçºä«µì'
	,'addevent_allow_picture' => 'áÊ´§ÃÙ»ÀÒ¾'
	,'new_post_notification' => 'ãªé¡ÒÃÂ×¹ÂÑ¹¡ÒÃà¾ÔèÁ¡Ô¨¡ÃÃÁãËÁè'
// Calendar View
	,'calendar_view_label' => 'áÊ´§áºº»¯Ô·Ô¹'
	,'monthly_view' => 'áÊ´§áººÃÒÂà´×Í¹'
	,'cal_view_show_week' => 'áÊ´§àÅ¢ÊÑ»´ÒËì'
	,'cal_view_max_chars' => '¨Ó¹Ç¹µÑÇÍÑ¡ÉÃÊÙ§ÊØ´'
// Flyer View
	,'flyer_view_label' => 'Flyer View'
	,'flyer_view' => 'áÊ´§'
	,'flyer_show_picture' => 'áÊ´§ÃÙ»ÀÒ¾ã¹ Flyer View'
	,'flyer_view_max_chars' => '¨Ó¹Ç¹µÑÇÍÑ¡ÉÃÊÙ§ÊØ´'
// Weekly View
	,'weekly_view_label' => 'áÊ´§áººÃÒÂÊÑ»´ÒËì'
	,'weekly_view' => 'áÊ´§'
	,'weekly_view_max_chars' => '¨Ó¹Ç¹µÑÇÍÑ¡ÉÃÊÙ§ÊØ´'
// Daily View
	,'daily_view_label' => 'áÊ´§áººÃÒÂÇÑ¹'
	,'daily_view' => 'áÊ´§'
	,'daily_view_max_chars' => '¨Ó¹Ç¹µÑÇÍÑ¡ÉÃÊÙ§ÊØ´'
// Categories View
	,'categories_view_label' => 'áÊ´§áººËÁÇ´'
	,'cats_view' => 'áÊ´§'
	,'cats_view_max_chars' => '¨Ó¹Ç¹µÑÇÍÑ¡ÉÃÊÙ§ÊØ´'
// Mini Calendar
	,'mini_cal_label' => '»¯Ô·Ô¹¨ÔëÇ'
	,'mini_cal_def_picture' => 'ÃÙ»ÀÒ¾»¡µÔ'
	,'mini_cal_display_picture' => 'áÊ´§ÃÙ»ÀÒ¾'
	,'mini_cal_diplay_options' => array('äÁèáÊ´§','ÀÒ¾»¡µÔ', 'ÀÒ¾ÃÒÂÇÑ¹','ÀÒ¾ÃÒÂÊÑ»´ÒËì','ÀÒ¾ÊØèÁ')
// Mail Settings
	,'mail_settings_label' => 'µÑé§¤èÒÍÕàÁÅì'
	,'mail_method' => 'ÃÙ»áºº¡ÒÃÊè§àÁÅì'
	,'mail_smtp_host' => 'SMTP Hosts (áÂ¡´éÇÂà¤Ã×èÍ§ËÁÒÂ ;)'
	,'mail_smtp_auth' => ' SMTP Authentication'
	,'mail_smtp_username' => 'SMTP Username'
	,'mail_smtp_password' => 'SMTP Password'

// Picture Settings
	,'picture_settings_label' => 'µÑé§¤èéÒÃÙ»ÀÒ¾'
	,'max_upl_dim' => '¢¹Ò´¡ÇéÒ§ÊØ´ËÃ×ÍÊÙ§ÊØ´ÊÓËÃÑºÃÙ»ÀÒ¾·Õè¨ÐÍÑ¾âËÅ´'
	,'max_upl_size' => '¢¹Ò´ÊÙ§ÊØ´ÊÓËÃÑºÃÙ»ÀÒ¾·Õè¨ÐÍÑ¾âËÅ´ (äºµì)'
	,'picture_chmod' => 'âËÁ´»¡µÔÊÓËÃÑºÃÙ»ÀÒ¾ (CHMOD)'
	,'allowed_file_extensions' => '»ÃÐàÀ·ä¿Åì¢Í§ÃÙ»ÀÒ¾·Õè¨ÐÍÑ¾âËÅ´'
// Form Buttons
	,'update_config' => 'ºÑ¹·Ö¡¡ÒÃµÑé§¤èÒãËÁè'
	,'restore_config' => 'ãªéé¤èÒà´ÔÁ¢Í§ÃÐºº'
// Misc.
	,'update_settings_success' => '¡ÒÃµÑé§¤èÒàÃÕÂºÃéÍÂáÅéÇ'
	,'restore_default_confirm' => 'µéÍ§¡ÒÃãªé¤èÒà´ÔÁ¢Í§ÃÐººËÃ×ÍäÁè ?'
// Template Configuration
	,'template_type' => '»ÃÐàÀ·à·Áà¾Åµ'
	,'template_header' => 'á¡éä¢ Header'
	,'template_footer' => 'á¡éä¢ Footer'
	,'template_status_default' => 'ãªéà·Áà¾Åµ»¡µÔ'
	,'template_status_custom' => 'ãªéà·Áà¾Åµ:'
	,'template_custom' => '¡ÓË¹´àÍ§'

	,'info_meta' => 'ÃÒÂÅÐàÍÕÂ´àÁµéÒ'
	,'info_status' => 'Ê¶Ò¹Ð'
	,'info_status_default' => 'äÁèáÊ´§º·¤ÇÒÁ'
	,'info_status_custom' => 'áÊ´§º·¤ÇÒÁ:'
	,'info_custom' => 'ÃÒÂÅÐàÍÕÂ´'

	,'dynamic_tags' => 'ä´¹ÒÁÔ¤á·ç¡'

// Product Updates
	,'updates_check_text' => '¡ÃØ³ÒÃÍ«Ñ¡¤ÃÙè ¡ÓÅÑ§·ÓÃÒÂ¡ÒÃ...'
	,'updates_no_response' => '·ÓÃÒÂ¡ÒÃäÁèä´é ¡ÃØ³ÒÅÍ§ãËÁèÍÕ¡¤ÃÑé§'
	,'avail_updates' => 'ÍÑ¾à´·ÅèÒÊØ´'
	,'updates_download_zip' => '´ÒÇ¹ìâËÅ´ ZIP á¾ç¤à¡ç¨ (.zip)'
	,'updates_download_tgz' => '´ÒÇ¹ìâËÅ´ TGZ á¾ç¤à¡ç¨ (.tar.gz)'
	,'updates_released_label' => 'ÇÑ¹·Õè»ÃÑº»ÃØ§: %s'
	,'updates_no_update' => '¤Ø³¡ÓÅÑ§ãªé§Ò¹àÇÍÃìªÑè¹ÅèÒÊØ´ äÁè¨Óà»ç¹µéÍ§ÍÑ¾à´·'
);

// ======================================================
// cal_mini.inc.php
// ======================================================

$lang_mini_cal = array(
	'def_pic' => 'ÀÒ¾»¡µÔ'
	,'daily_pic' => 'ÀÒ¾»ÃÐ¨ÓÇÑ¹·Õè (%s)'
	,'weekly_pic' => 'ÀÒ¾»ÃÐ¨ÓÊÑ»´ÒËì·Õè (%s)'
	,'rand_pic' => 'ÀÒ¾ÊØèÁ (%s)'
	,'post_event' => 'à¾ÔèÁ¡Ô¨¡ÃÃÁãËÁè'
	,'num_events' => '%d ¡Ô¨¡ÃÃÁ'
	,'selected_week' => 'ÊÑ»´ÒËì·Õè %d'
);

// ======================================================
// extcalendar.php
// ======================================================

// To Be Done

// ======================================================
// config.inc.php
// ======================================================

// To Be Done

// ======================================================
// install.php
// ======================================================

// To Be Done

// ======================================================
// login.php
// ======================================================

if (defined('LOGIN_PHP')) 

$lang_login_data = array(
	'section_title' => 'à¢éÒÊÙèÃÐºº'
// General Settings
	,'login_intro' => 'ãÊèª×èÍ¼Ùéãªé§Ò¹ áÅÐÃËÑÊ¼èÒ¹à¾×èÍà¢éÒÊÙèÃÐºº'
	,'username' => 'ª×èÍ¼Ùéãªé§Ò¹'
	,'password' => 'ÃËÑÊ¼èÒ¹'
	,'remember_me' => '¨Ó¢éÍÁÙÅ¡ÒÃÅçÍ¡ÍÔ¹'
	,'login_button' => 'à¢éÒÊÙèÃÐºº'
// Errors
	,'invalid_login' => '¡ÃØ³ÒÅÍ§ãËÁèÍÕ¡¤ÃÑé§ !'
	,'no_username' => 'µéÍ§ÃÐºØª×èÍ¼Ùéãªé§Ò¹ !'
	,'already_logged' => '¤Ø³à¢éÒÊÙèÃÐººáÅéÇ !'
);

// ======================================================
// logout.php
// ======================================================

// To Be Done


?>