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
 * Renders a list of elements found in a fabrik table
 *
 * @package 	Joomla
 * @subpackage	Articles
 * @since		1.5
 */
class JElementTablefields extends JElement
{
	/**
	 * Element name
	 * @access	protected
	 * @var		string
	 */
	var	$_name = 'Tablefields';
	
	var $_array_counter = null;
	
	/** @var array objects resulting from this elements queries - keyed on idetifying hash */
	var $results = null;
	

	function fetchElement($name, $value, &$node, $control_name)
	{
		if (is_null( $this->results )) {
			$this->results = array();
		}
		
		$db =& JFactory::getDBO();
		$controller = JRequest::getVar('c');
		$aEls 		= array();
		$onlytablefields = (int)$node->attributes( 'onlytablefields', 1 );
		
		$onlytablefields = ($onlytablefields === 1) ? "show_in_table_summary = 1" : "";
		$bits = array();

		//test
		$id = (is_null( $this->_array_counter )) ?  $control_name.$name :  $control_name.$name . '-' . $this->_array_counter;
		//end
		
		switch($controller)
		{
			case 'element':
				//@TODO this seems like we could refractor it to use the formModel class as per the table and form switches below?
				$connectionDd = $node->attributes( 'connection', '' );
				if ($connectionDd == '') {
					$oGroup = & JModel::getInstance( 'Group', 'FabrikModel' );
					if ($this->_parent->get('group_id') != '') {
						$bits[] = "e.group_id = " . $this->_parent->get('group_id');
					}
					if ($onlytablefields != '') {
						$bits[] .= " AND $onlytablefields ";
					}
					$hash = "$controller.".implode('.', $bits);
					
					if (array_key_exists( $hash, $this->results )) {
						$res = $this->results[$hash];
					} else {
						$query = "SELECT t.id AS table_id, t.db_table_name AS table_name, e.id, `e`.`name`, e.label FROM #__fabrik_elements AS e "
						. " LEFT JOIN #__fabrik_formgroup AS fg ON e.group_id = fg.group_id"
						. " LEFT JOIN #__fabrik_forms AS f ON f.id = fg.form_id"
						. " LEFT JOIN #__fabrik_tables AS t ON f.id = t.form_id";
						if (!empty( $bits )) {
							$query .= " WHERE " . implode(' AND ', $bits);
						}
	
						$query .= " ORDER BY e.label";
	
						$db->setQuery( $query );
						$res = $db->loadObjectList();
					
						$db->setQuery( "SELECT group_id FROM #__fabrik_joins WHERE table_id = '{$res[0]->table_id}'" );
						$gids = $db->loadResultArray();
						if (!empty( $gids )) {
							if ($this->_parent->get('group_id') != '') {
								$gids[] = $this->_parent->get('group_id');
							}
							$query = "SELECT t.id AS table_id, t.db_table_name AS table_name, e.id, `e`.`name`, e.label FROM #__fabrik_elements AS e "
							. " LEFT JOIN #__fabrik_formgroup AS fg ON e.group_id = fg.group_id"
							. " LEFT JOIN #__fabrik_forms AS f ON f.id = fg.form_id"
							. " LEFT JOIN #__fabrik_tables AS t ON f.id = t.form_id"
							. " WHERE e.group_id IN (" . implode(',', $gids) . ")";
							if ($onlytablefields != '') {
								$query .= " AND $onlytablefields ";
							}
							$query .=" ORDER BY e.label";
							$db->setQuery( $query );
							
							$res = $db->loadObjectList();
						}
						$this->results[$hash] =& $res;
					}
				}else{

					//****************************//
					$group = $this->_parent->_group;
					$repeat = $this->_parent->_xml[$group]->attributes('repeat');
					$tableDd = $node->attributes( 'table', '' );
					FabrikHelperHTML::script( 'tablefields.js', 'administrator/components/com_fabrik/elements/', true );
					$script = "window.addEvent('domready', function(){\n";
					$script .= 	"new tablefieldsElement( '$id', {
					'livesite':'".COM_FABRIK_LIVESITE."',
					'conn':'params" .$connectionDd . "',
					'table':'params". $tableDd . "',
					'value':'$value',
					'repeat':'$repeat'
				});\n";
				$script .="});\n";
				$document =& JFactory::getDocument();
				$document->addScriptDeclaration($script);
				$rows = array( JHTML::_('select.option', '', JText::_( 'SELECT A CONNECTION FIRST')));
				$o = new stdClass();
				$o->table_name = '';
				$o->name = '';
				$o->id = '';
				$o->label = JText::_( 'SELECT A TABLE FIRST');
				$res[] = $o;
				//****************************//
				}
				break;
			case 'table':
				$id = $this->_parent->get('id', false);
				if ($id === false) {
					$id = JRequest::getVar('cid', array(0));
					if (is_array( $id )) {
						$id = $id[0];
					}
				}
				$tableModel =& JModel::getInstance( 'table', 'FabrikModel' );
				$tableModel->setId( $id );
				$formModel =& $tableModel->getForm();
				$valfield = $node->attributes( 'valueformat', 'tableelement' ) == 'tableelement' ? 'name' : 'id';
				$onlytablefields = (int)$node->attributes( 'onlytablefields', 1 );
				$res = $formModel->getElementOptions( false, $valfield, $onlytablefields );
				break;
			case 'form':
				$id = $this->_parent->get('id');
				$id = JRequest::getVar( 'cid', array(0) );
				if (is_array( $id )) {
					$id = $id[0];
				}
				$formModel =& JModel::getInstance( 'form', 'FabrikModel' );
				$formModel->setId( $id );
				$valfield = $node->attributes( 'valueformat', 'tableelement' ) == 'tableelement' ? 'name' : 'id';
				$onlytablefields = (int)$node->attributes( 'onlytablefields', 1 );
				$res = $formModel->getElementOptions( false, $valfield, $onlytablefields );
				break;
			default:
				return JText::_( 'THE TABLEFIELDS ELEMENT IS ONLY USABLE BY TABLES AND ELEMENTS' );
				break;
		}
		$return = '';
		if (is_array($res)) {
			if ($controller == 'element') {
				foreach ($res as $o) {
					$s = new stdClass();
					$s->value= $node->attributes( 'valueformat', 'tableelement' ) == 'tableelement' ? $o->table_name.'.'.$o->name : $o->id;
					$s->text = FabrikString::getShortDdLabel( $o->label );
					$aEls[] = $s;
				}
			} else {
				foreach ($res as &$o) {
					$o->text = FabrikString::getShortDdLabel( $o->text );
				}
				$aEls = $res;
			}
			$aEls[] = JHTML::_( 'select.option', '', '-' );
			$return = JHTML::_( 'select.genericlist',  $aEls, $control_name.'['.$name.']', 'class="inputbox" size="1" ', 'value', 'text', $value );
			$return .= "<img style='margin-left:10px;display:none' id='".$control_name.$name."_loader' src='components/com_fabrik/images/ajax-loader.gif' alt='" . JText::_('LOADING'). "' />";
		}
		return $return;
	}
}