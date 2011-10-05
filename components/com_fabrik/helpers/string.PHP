<?php
/**
 * @package Joomla
 * @subpackage Fabrik
 * @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();


class FabrikString extends JString{

	/**
	 * UTF-8 aware - replace the first word
	 *
	 * @static
	 * @access public
	 * @param string the string to be trimmed
	 * @param string the word to trim
	 * @return string the trimmed string
	 */
	function ltrimword( $str, $word = FALSE )
	{
		$pos = strpos($str,$word);
		if ($pos === 0) { // true ? then exectue!
			$str = JString::substr($str, strlen($word));
		}
		return $str;
	}


	function rtrimword( &$str, $word = false)
	{
		$l = strlen($word);
		$end = substr($str, -$l);
		if($end === $word){
			return substr($str, 0, strlen($str)-$l);
		}else{
			return $str;
		}
	}
	/**
	 * formats a string to return a safe db col name - eg
	 * table.field is returned as `table`.field`
	 * table is return as `table`
	 *
	 * @param mixed $col col name to format
	 */

	function safeColName( &$col )
	{
		//first test for tablename___elementname or `tablename___elementname`
		if (strstr($col, '___')){
			$col = trim($col, '`');
			$col = str_replace('___', '`.`', $col);
			$col = "`$col`";
		}
		if (!strstr( $col, "`" )) {
			if (strstr( $col, "." )) {
				$col = explode(".", $col);
				$col = "`" . $col[0] . "`.`" . $col[1] . "`";
			}	else {
				$col = "`" . $col . "`";
			}
		}
		return $col;
	}

	/** inverse of safeColName takes `table`.`field` and returns table___field*/

	function safeColNameToArrayKey( $col ){
		$col = str_replace( array( "`.`", "." ) , "___", $col);
		$col = str_replace("`", "", $col);
		return $col;
	}

	/** takes tablename.element or tablename___elementname (with or without quotes) and returns elementname */
	function shortColName( $col ){
		if (strstr( $col, '.')){
			$bits = explode( '.', $col );
			$col = array_pop($bits);
		}else	if (strstr( $col, '___')){
			$bits = explode( '___', $col );
			$col = array_pop($bits);
		}
		$col = str_replace("`", "", $col);
		return $col;
	}
	
	/**
	 * check if the given email address is correctly formatted
	 * @param string email address to check
	 * @return bol ok / not ok
	 */
	function check_email_address( $email ){
		/* First, we check that there's one symbol, and that the lengths are right*/
		if ( !ereg("[^@]{1,64}@[^@]{1,255}", $email ) ) {
			/* Email invalid because wrong number of characters in one section, or wrong number of symbols.*/
			return false;
		}
		/* Split it into sections to make life easier*/
		$email_array = explode( "@", $email );
		$local_array = explode( ".", $email_array[0] );
		for ( $i = 0; $i < sizeof($local_array); $i++ ) {
			if ( !ereg( "^(([A-Za-z0-9!#$%&'*+/=?^_`{|}~-][A-Za-z0-9!#$%&'*+/=?^_`{|}~\.-]{0,63})|(\"[^(\\|\")]{0,62}\"))$", $local_array[0] ) ) {
				return false;
			}
		}
		/* Check if domain is IP. If not, it should be valid domain name */
		if ( !ereg( "^\[?[0-9\.]+\]?$", $email_array[1] ) ) {
			$domain_array = explode( ".", $email_array[1] );
			if ( sizeof( $domain_array ) < 2 ) {
				/* Not enough parts to domain */
				return false;
			}
			for ( $i = 0; $i < sizeof( $domain_array ); $i++ ) {
				if ( !ereg( "^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|([A-Za-z0-9]+))$", $domain_array[$i])) {
					return false;
				}
			}
		}
		return true;
	}
	

	function getShortDdLabel($label){
		$label = strip_tags($label);
		preg_replace('/<[a-z][a-z0-9]*[^<>]*>/', '', $label);
		if(strlen($label) > 50){
			$label = substr($label, 0, 47).'...';
		}
		$label = trim($label);
		return $label;
	}
}
?>