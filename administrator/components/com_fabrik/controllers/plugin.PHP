<?php
/**
* @version 
* @package Joomla
* @subpackage Fabrik
* @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.application.component.controller' );
jimport( 'joomla.application.component.model' );
jimport('joomla.client.helper');

require_once( JPATH_COMPONENT.DS.'views'.DS.'plugin.php' );
require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'params.php' );
require_once( COM_FABRIK_BASE.DS.'administrator'.DS.'components'.DS.'com_fabrik'.DS.'helpers'.DS.'menu.php' );
JModel::addIncludePath(COM_FABRIK_FRONTEND.DS.'models' );

/**
 * @plugin		Joomla
 * @subplugin	Fabrik
 */

class FabrikControllerPlugin extends JController
{

	/**
	 * Constructor
	 */
	function __construct( $config = array() )
	{
		parent::__construct( $config );
		// Register Extra tasks
		
	}
	function uninstallPlugin()
	{
		// Check for request forgeries
		JRequest::checkToken() or die( 'Invalid Token' );
		global $mainframe;
		$model = $this->getModel( 'PluginInstaller' );
		$result = $model->uninstall();
		if ($result) {
			$mainframe->enqueueMessage(JText::_('UNINSTALL SUCESSFUL'), 'message');
		} else {
			JError::raiseWarning(0, JText::_( 'UNINSTALL UNSUCESSFUL' ));
		}
		$this->display();
	}

	/**
	 * actually do the install
	 */

	function doimportPlugin()
	{
		global $mainframe;
		jimport( 'domit.xml_domit_lite_include' );
		$model = $this->getModel( 'PluginInstaller' );
		$result = $model->install();
		if( $result ){
			$mainframe->enqueueMessage(JText::_('INSTALLATION SUCESSFUL'), 'message');
		}else{
			JError::raiseWarning(0, JText::_( 'INSTALLATION UNSUCESSFUL' ));
		}
		$this->display();
	}


	/**
	 * Display the list of plugins
	 */

	function display()
	{
		global $mainframe;
		$db =& JFactory::getDBO();
		$lists 			= array();
		$where 			= array();
		$context		= 'com_fabrik.plugin.list.';
		$filter_order		= $mainframe->getUserStateFromRequest( $context.'filter_order',		'filter_order',		"id",	'cmd' );
		$filter_order_Dir	= $mainframe->getUserStateFromRequest( $context.'filter_order_Dir',	'filter_order_Dir',	'',			'word' );
		
		// table ordering
		$lists['order_Dir']	= $filter_order_Dir;
		$lists['order']		= $filter_order;
		
		$filter_PluginInType 	= $mainframe->getUserStateFromRequest( $context."filter_PluginInType", 'filter_PluginInType', '' );
		
		if ( $filter_PluginInType != '' ) {
			$where[] 	= " type  = '$filter_PluginInType'";
		}
		$where		= count( $where ) ? ' WHERE ' . implode( ' AND ', $where ) : '';
		$orderby	= ' ORDER BY '. $filter_order .' '. $filter_order_Dir;
		
		// get the total number of records
		$db->setQuery( "SELECT count(*) FROM #__fabrik_plugins $where" );
		$total = $db->loadResult();
		echo $db->getErrorMsg();

		$limit		= $mainframe->getUserStateFromRequest( $context.'limit', 'limit', $mainframe->getCfg('list_limit'), 'int' );
		$limitstart = $mainframe->getUserStateFromRequest( $context.'limitstart', 'limitstart', 0, 'int' );
		
		jimport('joomla.html.pagination');
		$pageNav = new JPagination( $total, $limitstart, $limit );
		
		$sql = "SELECT * FROM #__fabrik_plugins $where $orderby ";
		$db->setQuery( $sql, $pageNav->limitstart, $pageNav->limit );
		$rows = $db->loadObjectList( );
		
		$aTypes[] 		= JHTML::_('select.option', "", JText::_( 'PLEASE SELECT' ) );
		$aTypes[] 		= JHTML::_('select.option', "element", 'element' );
		$aTypes[] 		= JHTML::_('select.option', "form", 'form action' );
		$aTypes[] 		= JHTML::_('select.option', "table", 'table' );
		$aTypes[] 		= JHTML::_('select.option', "validationrule", 'validation rules' );
		$aTypes[] 		= JHTML::_('select.option', "visualization", 'visualizations' );
		$lists['type'] 	= JHTML::_('select.genericlist', $aTypes, 'filter_PluginInType', 'class="inputbox"  onchange="document.adminForm.submit( );"', 'value', 'text', $filter_PluginInType );
		
		require_once(JPATH_COMPONENT.DS.'views'.DS.'plugin.php');
		FabrikViewPlugin::show( $rows, $pageNav, $lists );

	}

	/**
	 * delete plugin
	 * DEPRECIATED USE UNISTALL INSTEAD
	 */
	 
	function remove( )
	{
		$this->uninstallPlugin();
	}
}
?>