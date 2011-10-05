<?php
/**
* @package Joomla
* @subpackage Fabrik
* @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/


// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();

/**
 * Renders a SQL element
 *
 * @author 		rob clayburn
 * @package 	fabrikar
 * @subpackage		Parameter
 * @since		1.5
 */

class JElementConnections extends JElement
{
	/**
	* Element name
	*
	* @access	protected
	* @var		string
	*/
	var	$_name = 'Connections';

	function fetchElement($name, $value, &$node, $control_name)
	{
		$db			= & JFactory::getDBO();
		if ($value == '') {
			$db->setQuery("SELECT id FROM #__fabrik_connections WHERE `default` = 1");
			$value = $db->loadResult();
		}
		$db->setQuery("SELECT id AS value, description AS text FROM #__fabrik_connections WHERE state = '1'");
		$cnns = array_merge( array(JHTML::_('select.option', '-1', JText::_( 'PLEASE SELECT' ) )), $db->loadObjectList() );	
		$js = "onchange=\"" . $node->attributes('js') . "\"";
		$return = JHTML::_('select.genericlist', $cnns , ''.$control_name.'['.$name.']', 'class="inputbox" ' . $js, 'value', 'text', $value, $control_name.$name);
		$return .= "<img style='margin-left:10px;display:none' id='".$control_name.$name."_loader' src='components/com_fabrik/images/ajax-loader.gif' alt='" . JText::_('LOADING'). "' />";
		return $return;
	}
}
?>