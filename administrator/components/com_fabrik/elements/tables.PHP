<?php
/**
* @package Joomla
* @subpackage Fabrik
* @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();

//required for menus
require_once( str_replace(DS.'administrator', '', JPATH_BASE).DS.'components'.DS.'com_fabrik'.DS.'helpers'.DS.'html.php' );
/**
 * Renders a repeating drop down list of tables
 *
 * @author 		Rob Clayburn 
 * @package 	Joomla
 * @subpackage		Fabrik
 * @since		1.5
 */

class JElementTables extends JElement
{
	/**
	* Element name
	*
	* @access	protected
	* @var		string
	*/
	var	$_name = 'Tables';
	
	var $_array_counter = null;
	
	function fetchElement( $name, $value, &$node, $control_name )
	{
		FabrikHelperHTML::script( 'tables.js', 'administrator/components/com_fabrik/elements/', true );
		$connectionDd = $node->attributes( 'observe', '' );
		$db			= & JFactory::getDBO();
		$document =& JFactory::getDocument();
		$repeat = false;
		if(isset($this->_parent->_group)){
			$group = $this->_parent->_group;
			$repeat = $this->_parent->_xml[$group]->attributes('repeat');
		}
		if (strstr( $name, "[]" )) {
			$name = trim($name, "[]");
			$fullname = $control_name.'['.$name."][]";
		} else {
			$fullname = $control_name.'['.$name.']';
		}
		$script = '';
		if ($connectionDd == '') {
			//we are not monitoring a connection drop down so load in all tables
			$query = "SHOW TABLES";
			
			$db->setQuery( $query );
			$list = $db->loadResultArray();
			foreach($list as $l){
				$rows[] = JHTML::_('select.option', $l, $l);
			}
		} else {
			$rows = array( JHTML::_('select.option', '', JText::_( 'SELECT A CONNECTION FIRST')));	
		}

		$id = (is_null( $this->_array_counter )) ?  $control_name.$name :  $control_name.$name . '-' . $this->_array_counter;
		
		if ($connectionDd != '') {
			$script .= "window.addEvent('domready', function(){\n";
			$script .= "tableElements = \$H();\n";
			$script .= 	"tableElements.set('$id', new tablesElement( '$id', {
				'livesite':'".COM_FABRIK_LIVESITE."',
				'conn':'params" .$connectionDd . "',
				'value':'$value',
				'repeat':'$repeat'
			}));\n";
			$script .="});\n";
		}
	
		if ($script != '') {
			$document->addScriptDeclaration($script);
		}
		FabrikHelperHTML::cleanMootools();
		$str = JHTML::_('select.genericlist',  $rows, $fullname, 'class="repeattable inputbox"', 'value', 'text', $value, $id );
		$str .= "<img style='margin-left:10px;display:none' id='".$id."_loader' src='components/com_fabrik/images/ajax-loader.gif' alt='" . JText::_('LOADING'). "' />"; 
		return $str;
	}

}