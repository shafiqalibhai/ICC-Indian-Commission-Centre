<?php
/**
 * @package Joomla
 * @subpackage Fabrik
 * @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

/**
 * compat with php < 5.1
 */
if ( !function_exists('htmlspecialchars_decode') )
{
	function htmlspecialchars_decode($text)
	{
		return strtr($text, array_flip(get_html_translation_table(HTML_SPECIALCHARS)));
	}
}

if (!function_exists('array_combine'))
{
	function array_combine($arr1,$arr2) {
		$out = array();
		foreach ($arr1 as $key1 => $value1) {
			$out[$value1] = $arr2[$key1];
		}
		return $out;
	}
}



/*
 * generic tools that all models use
 * This code used to be in models/parent.php
 */

class FabrikWorker {

	var $finalformat = null;

	/** @var string image file extensions */
	var $_image_extensions_eregi = 'bmp|gif|jpg|jpeg|png';

		/** @var string audio file extensions */
	var $_audio_extensions_eregi = 'mp3';
	
	static protected $_audio_mime_types = array (
		'mp3' => 'audio/x-mpeg'
	);

	/**
	 * returns true if $file has an image extension type
	 * @param string filename
	 * @return bool
	 */
	function isImageExtension( $file ) {
		$path_parts = pathinfo($file);
		return eregi(self::$_image_extensions_eregi, $path_parts['extension']);
	}

	/**
	 * returns true if $file has an image extension type
	 * @param string filename
	 * @return bool
	 */
	function isAudioExtension( $file ) {
		$path_parts = pathinfo($file);
		return eregi(self::$_audio_extensions_eregi, $path_parts['extension']);
	}
	
	function getAudioMimeType($file) {
		$path_parts = pathinfo($file);
		if (array_key_exists($path_parts['extension'], self::$_audio_mime_types)) {
			return self::$_audio_mime_types[$path_parts['extension']];
		}
		return false;
	}
	
	/**
	 * format a string to datetime
	 *
	 * http://fr.php.net/strftime
	 * (use as strptime)
	 *
	 * @param string $date
	 * @param string $format
	 * @return array date info
	 */
	
	function strToDateTime($date, $format) {

		$weekdays = array (
		'Sun' => '0',
		'Mon' => '1',
		'Tue' => '2',
		'Wed' => '3',
		'Thu' => '4',
		'Fri' => '5',
		'Sat' => '6'
		);
		$months = array (
		'Jan' => '01',
		'Feb' => '02',
		'Mar' => '03',
		'Apr' => '04',
		'May' => '05',
		'Jun' => '06',
		'Jul' => '07',
		'Aug' => '08',
		'Sep' => '09',
		'Oct' => '10',
		'Nov' => '11',
		'Dec' => '12'
		);
		if (!($date = FabrikWorker::str2Time( $date, $format ))) {
			return;
		}
		$months 				= array( JText::_('January'), JText::_('February'), JText::_('March'), JText::_('April'), JText::_('May'), JText::_('June'), JText::_('July'), JText::_('August'), JText::_('September'), JText::_('October'), JText::_('November'), JText::_('December') );
		$shortMonths 		= array( JText::_('Jan'), JText::_('Feb'), JText::_('Mar'), JText::_('Apr'), JText::_('May'), JText::_('Jun'), JText::_('Jul'), JText::_('Aug'), JText::_('Sept'), JText::_('Oct'), JText::_('Nov'), JText::_('Dec') );
	
		//$$$ rob set day default to 1, so that if you have a date format string of %m-%Y the day is set to the first day of the month
		// and not the last day of the previous month (which is what a 0 here would represent)
		$dateTime = array('sec' => 0, 'min' => 0, 'hour' => 0, 'day' => 1, 'mon' => 0, 'year' => 0, 'timestamp' => 0);
		foreach ($date as $key => $val) {
			switch($key) {
				
				case 'd':
				case 'j': $dateTime['day'] = intval($val); break;
				case 'D': $dateTime['day'] = intval($weekdays[$val]); break;

				case 'm':
				case 'n': $dateTime['mon'] = intval($val); break;
				case 'b': 
					$dateTime['mon'] = $shortMonths[$val] + 1;break;
					break;

				case 'Y': $dateTime['year'] = intval($val); break;
				case 'y': $dateTime['year'] = intval($val)+2000; break;

				case 'G':
				case 'g':
				case 'H':
				case 'h': $dateTime['hour'] = intval($val); break;

				case 'M': $dateTime['min'] = intval($val); break;
				case 'i': $dateTime['min'] = intval($val); break;

				case 's': $dateTime['sec'] = intval($val); break;
			}

		}
		$dateTime['timestamp'] = mktime($dateTime['hour'], $dateTime['min'], $dateTime['sec'], $dateTime['mon'], $dateTime['day'], $dateTime['year']);
		return $dateTime;
	}
	//@TODO: use JDate:_strftime() to translate correctly

	function str2Time($date, $format)
	{
		static $finalformat;
		
		// $$$ - hugh : urldecode (useful when ajax calls, may need better fix)
		// as per http://fabrikar.com/forums/showthread.php?p=43314#post43314
		$date = urldecode($date);
		
		//strip any textual date representations from the string
		
		$days = array('%A', '%a');
		foreach ($days as $day) {
			if (strstr( $format, $day )) {
				$format = str_replace( $day, '', $format );
				$date =  FabrikWorker::_stripDay( $date,  $day == '%a' ? true : false );
			}
		}
		$months = array('%B', '%b', '%h');
		foreach ($months as $month) {
			if (strstr( $format, $month )) {
				$format = str_replace( $month, '%m', $format );
				$date =  FabrikWorker::_monthToInt( $date, $month == '%B' ? false : true );
			}
		}
		
		//@TODO: some of these arent right for strftime
		$this->finalformat = $format;
		$search = array('%d', '%D', '%j', // day
                    '%m', '%b', // month
                    '%Y', '%y', // year
                    '%g', '%H', '%h', // hour
                    '%i', '%s', '%M');

		$replace = array('(\d{2})', '(\w{3})', '(\d{1,2})', //day
                     '(\d{2})', '(\w{3})', // month
                     '(\d{4})', '(\d{2})', // year
                     '(\d{1,2})', '(\d{2})', '(\d{2})', // hour
                     '(\d{2})', '(\d{2})', '(\d{2})');


		$pattern = str_replace( $search, $replace, $format );
		if (!preg_match( "#$pattern#", $date, $matches )) {
			// lets allow for partial date formats - eg just the date and ignore the time
			$format = explode( "%", $format );
			if (empty( $format )) {
				//no format left to test so return false
				return false;
			}
			array_pop( $format );
			$format = trim( implode( '%', $format ) );
			$this->finalformat = $format;
			return FabrikWorker::str2Time( $date, $format );
		}
		$dp = $matches;
		if (!preg_match_all( '#%(\w)#', $format, $matches )) {
			return false;
		}
		$id = $matches['1'];

		if(count($dp) != count($id)+1) {
			return false;
		}
		$ret = array();
		for ($i=0, $j=count($id); $i<$j; $i++) {
			$ret[$id[$i]] = $dp[$i+1];
		}
		return $ret;
	}

	function getFinalDateFormat()
	{
		return $this->finalformat;
	}

	/**
	 * removed day of week name from string
	 *
	 * @access protected
	 * @param string $day The string date
	 * @param bol abbreviated day?
	 * @return string date
	 */
	function _stripDay($date, $abrv = false)
	{
		if ($abrv) {
			$date = str_replace( JText::_('SUN'), '', $date );
			$date = str_replace( JText::_('MON'), '', $date );
			$date = str_replace( JText::_('TUE'), '', $date );
			$date = str_replace( JText::_('WED'), '', $date );
			$date = str_replace( JText::_('THU'), '', $date );
			$date = str_replace( JText::_('FRI'), '', $date );
			$date = str_replace( JText::_('SAT'), '', $date );
		}else{
			$date = str_replace( JText::_('SUNDAY'), '', $date );
			$date = str_replace( JText::_('MONDAY'), '', $date );
			$date = str_replace( JText::_('TUESDAY'), '', $date );
			$date = str_replace( JText::_('WEDNESDAY'), '', $date );
			$date = str_replace( JText::_('THURSDAY'), '', $date );
			$date = str_replace( JText::_('FRIDAY'), '', $date );
			$date = str_replace( JText::_('SATURDAY'), '', $date );
		}
		return $date;
	}
	
	function _monthToInt( $date, $abrv = false )
	{
		if ($abrv) {
			$date = str_replace( JText::_('JANUARY_SHORT'), '01', $date );
			$date = str_replace( JText::_('FEBRUARY_SHORT'), '02', $date );
			$date = str_replace( JText::_('MARCH_SHORT'), '03', $date );
			$date = str_replace( JText::_('APRIL_SHORT'), '04', $date );
			$date = str_replace( JText::_('MAY_SHORT'), '05', $date );
			$date = str_replace( JText::_('JUNE_SHORT'), '06', $date );
			$date = str_replace( JText::_('JULY_SHORT'), '07', $date );
			$date = str_replace( JText::_('AUGUST_SHORT'), '08', $date );
			$date = str_replace( JText::_('SEPTEMBER_SHORT'), '09', $date );
			$date = str_replace( JText::_('OCTOBER_SHORT'), 10, $date );
			$date = str_replace( JText::_('NOVEMBER_SHORT'), 11, $date );
			$date = str_replace( JText::_('DECEMBER_SHORT'), 12, $date );
		}else{
			$date = str_replace( JText::_('JANUARY'), '01', $date );
			$date = str_replace( JText::_('FEBRUARY'), '02', $date );
			$date = str_replace( JText::_('MARCH'), '03', $date );
			$date = str_replace( JText::_('APRIL'), '04', $date );
			$date = str_replace( JText::_('MAY'), '05', $date );
			$date = str_replace( JText::_('JUNE'), '06', $date );
			$date = str_replace( JText::_('JULY'), '07', $date );
			$date = str_replace( JText::_('AUGUST'), '08', $date );
			$date = str_replace( JText::_('SEPTEMBER'), '09', $date );
			$date = str_replace( JText::_('OCTOBER'), 10, $date );
			$date = str_replace( JText::_('NOVEMBER'), 11, $date );
			$date = str_replace( JText::_('DECEMBER'), 12, $date );
		}
		return $date;

	}

	function isReserved( $str )
	{
		$_reservedWords = array("fabrik_frommodule", "act","task", "view", "layout", "option", "form_id", "submit", "ul_max_file_size", "ul_file_types", "ul_directory", "tableid", 'rowid', 'itemid', 'fabrik', 'adddropdownvalue', 'adddropdownlabel', 'ul_end_dir');
		if(in_array( strtolower( $str ), $_reservedWords)) {
			return true;
		}
		return false;
	}

	/**
	 * takes a field name and quotes it
	 * - e.g.
	 * "table.element" becomes "`table`.`element`
	 * "element" becomes `element`
	 * "table___element" becomes "`table___element`"
	 *
	 * @param string
	 * @return string
	 */

	function getDbSafeName( $str )
	{
		if( strstr( $str, '`' )) {
			return $str;
		}
		if (strstr( $str, '.' )) {
			$safeKey = explode('.', $str);
			$safeKey = "`" . $safeKey[0] . "`.`" . $safeKey[1] . "`";
		} else {
			$safeKey = "`$str`";
		}
		return $safeKey;
	}

	/**
	 * takes a quoted db field name and returns fabrik safe name
	 * - e.g.
	 * "`table`.`element`" becomes "table___element"
	 *
	 * @param string
	 * @return string
	 */

	function getFabrikSafeName( $str )
	{
		if (strstr( $str, '.' )) {
			$str = explode('.', $str);
			$str = $str[0] . "___" . $str[1];
		}
		$str = str_replace("`", "", $str);
		return $str;
	}

	/**
	 * iterates through string to replace every
	 * {placeholder} with posted data
	 * @param string text to parse
	 * @param array data to search for placeholders (default $_POST)
	 */

	function parseMessageForPlaceHolder( $msg, $searchData = null )
	{
		if ($msg == '' || is_array($msg)) {
			return $msg;
		}
		if (is_object($searchData)) {
			$searchData = JArrayHelper::fromObject($searchData);
		}
		$post	= JRequest::get( 'post' );
		$this->_searchData =(is_null( $searchData ) ) ?  $post :  array_merge( $post, $searchData );
		$msg = FabrikWorker::_replaceWithUserData( $msg );
		$msg = FabrikWorker::_replaceWithGlobals( $msg );
		$msg = preg_replace( "/{}/", "", $msg );
		/* replace {element name} with form data */
		$msg = preg_replace_callback( "/{[^}\s]+}/i", array( $this, '_replaceWithFormData'), $msg );
		return $msg;
	}

	function getACLGroups( $val, $cond = '>=' )
	{
		//@TODO: cachce this to call it once per page
		$db =& JFactory::getDBO();
		$sql = "SELECT name FROM #__core_acl_aro_groups WHERE id $cond '$val' AND id < 28";
		$db->setQuery( $sql );
		$res = $db->loadResultArray();
		return $res;
	}

	function setACL( $action, $task, $coponent, $userGroup, $a=null, $b=null, $c=null )
	{
		$acl =& JFactory::getACL();
		$acl->addACL( $action, $task, $coponent, $userGroup, $a, $b, $c );
	}

	/**
	 * determine if you can use a thing
	 * uses JACL if available
	 * @param object access id
	 * @param string access object identifier
	 * @param string acl direction (inclusive or exclusive - not tested for JACL integration )
	 */

	function getACL( $a, $key, $cond = '>=' )
	{

		$user	= &JFactory::getUser();
		$acl	=& JFactory::getACL();
		//everyone

		if ($a == 0) {
			return true;
		}
		
		if ($a == '29' || $a == '' || $a == '0') {
			if ($cond == '>=') {
				return true;
			} else {
				return false;
			}
		}
		if (defined( '_JACL' )) {
			$inacl =  in_array( $a, explode( ',', $user->get( 'jaclplus', '0' ) ) );
			if ($cond == '<='){
				return !$inacl;
			} else {
				return $inacl;
			}
		} else {
			$groupNames =& FabrikWorker::getACLGroups( $a, $cond );
			foreach ($groupNames as $name) {
				FabrikWorker::setACL( 'action', $key, 'fabrik', $name, 'components', null );
			}
			//if not logged in set the usertype to ROOT (ie public front end)
			$utype = ($user->get('usertype') == '') ? 'ROOT' : $user->get('usertype');

			return $acl->acl_check( 'action', $key, 'fabrik', $utype, 'components', null );
		}
	}

	/**
	 * PRIVATE:
	 * called from parseMessageForPlaceHolder to iterate through string to replace
	 * {placeholder} with user ($my) data
	 * @param string message to parse
	 * @return string parsed message
	 */

	function _replaceWithUserData( $msg )
	{
		$user  = &JFactory::getUser();
		if (is_object( $user )) {
			foreach ($user as $key=>$val) {
				if (substr( $key, 0, 1 ) != '_') {
					if (!is_object( $val ) && !is_array( $val )) {
						$msg = str_replace( '{$my->' . $key . '}', $val, $msg );
						$msg = str_replace( '{$my-&gt;' . $key . '}', $val, $msg );
					}
				}
			}
		}
		return $msg;
	}


	/**
	 * PRIVATE:
	 * called from parseMessageForPlaceHolder to iterate through string to replace
	 * {placeholder} with global data
	 * @param string message to parse
	 * @return string parsed message
	 */

	function _replaceWithGlobals( $msg )
	{
		global $Itemid;
		$config		=& JFactory::getConfig();
		$msg = str_replace( '{$mosConfig_absolute_path}', JPATH_SITE, $msg );
		$msg = str_replace( '{$mosConfig_live_site}', JURI::base(), $msg );
		$msg = str_replace( '{$mosConfig_offset}', $config->getValue( 'offset' ), $msg );
		$msg = str_replace( '{$Itemid}', $Itemid, $msg );
		$msg = str_replace( '{$mosConfig_sitename}', $config->getValue( 'sitename' ), $msg );
		$msg = str_replace( '{$mosConfig_mailfrom}',$config->getValue( 'mailfrom' ), $msg );
		//
		$session =& JFactory::getSession();
		$token = $session->get( 'session.token' );
		$msg = str_replace( '{session.token}', $token, $msg );
		return $msg;
	}

	/**
	 * PRVIATE:
	 * called from parseMessageForPlaceHolder to iterate through string to replace
	 * {placeholder} with posted data
	 * @param string placeholder e.g. {placeholder}
	 * @return string posted data that corresponds with placeholder
	 */

	function _replaceWithFormData( $matches )
	{
		$match = $matches[0];
		/* strip the {} */
		$match = substr( $match, 1, strlen($match) - 2 );
		// $$$ hugh - NOOOOOOO!!  Screws up where people actually have mixed case element names
		//$match = strtolower( $match );
		$match = preg_replace( "/ /", "_", $match );
		if ( !strstr( $match, "." ) ) {
			/* for some reason array_key_exists wasnt working for nested arrays?? */
			$aKeys = array_keys( $this->_searchData );
			/* remove the table prefix from the post key */
			$aPrefixFields = array();
			for ($i=0; $i<count( $aKeys ); $i++) {
				$aKeyParts = explode( '___', $aKeys[$i]);

				if (count( $aKeyParts ) == 2) {
					$tablePrefix = array_shift($aKeyParts);
					$field = array_pop($aKeyParts);
					$aPrefixFields[$field] = $tablePrefix;
				}
			}
			if (array_key_exists( $match, $aPrefixFields )) {
				$match =  $aPrefixFields[$match] . '___' . $match;
			}

			//test to see if the made match is in the post key arrays

			if( in_array( $match, $aKeys ) ) {
				/* get the post data */
				$match = $this->_searchData[ $match ];
				if( is_array( $match ) ){
					$match = implode( ',', $match);
				}
			} else {
				$match = "";
			}
		} else {
			/* could be looking for URL field type eg for $_POST[url][link] the match text will be url.link */
			$aMatch = explode( ".", $match );
			$aPost = $this->_searchData;
			foreach ($aMatch as $sPossibleArrayKey) {
				if (is_array( $aPost )) {
					if (!isset( $aPost[$sPossibleArrayKey] )) {
						$match = "";
						return $match;
					} else {
						$aPost = $aPost[$sPossibleArrayKey];
					}
				}
			}
			$match = $aPost;
			return $match;
		}
		return $match;
	}
}

class FArrayHelper extends JArrayHelper
{
	/**
	 * Utility function to map an array to a stdClass object.
	 *
	 * @static
	 * @param	array	$array		The array to map.
	 * @param	string	$calss 		Name of the class to create
	 * @param bol recurse into each value and set any arrays to objects
	 * @return	object	The object mapped from the given array
	 * @since	1.5
	 */
	function toObject(&$array, $class = 'stdClass', $recurse = true)
	{
		$obj = null;
		if (is_array($array))
		{
			$obj = new $class();
			foreach ($array as $k => $v)
			{
				if (is_array($v) && $recurse) {
					$obj->$k = JArrayHelper::toObject($v, $class);
				} else {
					$obj->$k = $v;
				}
			}
		}
		return $obj;
	}
}
?>