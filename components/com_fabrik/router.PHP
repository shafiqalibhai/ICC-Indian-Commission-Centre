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
	 * if using file extensions sef and htaccess :
	 * you need to edit yout .htaccess file to:
	 * 
	 * RewriteCond %{REQUEST_URI} (/|\.csv|\.php|\.html|\.htm|\.feed|\.pdf|\.raw|/[^.]*)$  [NC]
	 * 
	 * otherwise the csv exporter will give you a 404 error
	 * 
	 */

function fabrikBuildRoute(&$query){
	$segments = array();
	
	$menu = &JSite::getMenu();
	//echo "query itemid = " . $query['Itemid'] . "<br>";
	$menuItem = &$menu->getItem( @$query['Itemid'] );
	//$segments[] = 'test?';

	if(isset($query['c'])) {
		//$segments[] = $query['c'];//remove from sef url
		unset($query['c']);
	};	
	
	if(isset($query['task'])) {
		$segments[] = $query['task'];
		unset($query['task']);
	};	

	if(isset($query['view'])) {
		$segments[] = $query['view'];
		unset($query['view']);
	};
	
	if(isset($query['id'])) {
		$segments[] = $query['id'];
		unset($query['id']);
	};
	
	if(isset($query['layout'])) {
		$segments[] = $query['layout'];
		unset($query['layout']);
	};
	
	if(isset($query['fabrik'])) {
		$segments[] = $query['fabrik'];
		unset($query['fabrik']);
	};

	if(isset($query['tableid'])) {
		$segments[] = $query['tableid'];
		unset($query['tableid']);
	};
	
	if(isset($query['rowid'])) {
		$segments[] = $query['rowid'];
		unset($query['rowid']);
	};
	
	if(isset($query['fabrik_cursor'])) {
		//$segments[] = $query['fabrik_cursor'];//remove from sef url
		unset($query['fabrik_cursor']);
	};	
	
	if(isset($query['fabrik_total'])) {
		//$segments[] = $query['fabrik_total']; //remove from sef url
		unset($query['fabrik_total']);
	};
	
	if(isset($query['calculations'])) {
		$segments[] = $query['calculations'];
		unset($query['calculations']);
	};
	
	if(isset($query['filetype'])){
		$segments[] = $query['filetype'];
		unset($query['filetype']);
	}
	if(isset($query['format'])){
		$segments[] = $query['format'];
		//don't unset as with sef urls and extensions on - if we unset it
		//the url's prefix is set to .html
		//unset($query['format']); 
	}
	
	if(isset($query['type'])){
		$segments[] = $query['type'];
		unset($query['type']);
	}
	
		//test	
	if(isset($query['fabriklayout'])) {
		$segments[] = $query['fabriklayout'];
		unset($query['fabriklayout']);
	};
	
	return $segments;	
}

function fabrikParseRoute($segments)
{
	//vars are what Joomla then uses for its $_REQUEST array
	$vars = array();
	//Get the active menu item
	$menu =& JSite::getMenu();
	$item =& $menu->getActive();

	// Count route segments
	$count = count($segments);
	switch($segments[0]){ //view (controller not passed into segments)
		case 'form':
		case 'details':
			$vars['task'] = 'view';
			$vars['fabrik'] = $segments[1];
			$vars['tableid'] = $segments[2];
			$vars['rowid'] = @$segments[3];
			//test
			$vars['view'] = $segments[0];
			break;
		case 'table':
			$vars['view'] = $segments[0];
			$vars['tableid'] = $segments[1];
			$vars['format'] = $segments[2];
			//$vars['type'] = $segments[3];
			break;
		case 'import':
			$vars['view'] = 'import';
			$vars['tableid'] = $segments[1];
			$vars['filetype'] = $segments[2];
			break;
		case 'visualization':
			$vars['id'] = $segments[1];
			$vars['format'] = @$segments[2];
			break;
	}
	return $vars;
}
?>