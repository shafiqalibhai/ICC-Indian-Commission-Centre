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
JModel::addIncludePath( COM_FABRIK_FRONTEND.DS.'models' );

require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'params.php');

/**
 * @package		Joomla
 * @subpackage	Fabrik
 */

class FabrikControllerCron extends JController
{

	/**
	 * Constructor
	 */
	function __construct( $config = array() )
	{
		parent::__construct( $config );
		// Register Extra tasks
		$this->registerTask( 'add',			'edit' );
		$this->registerTask( 'apply',		'save' );
		$this->registerTask( 'unpublish',	'publish' );
	}
	
	/**
	 * run a cron job
	 */

	function run()
	{
		$db =& JFactory::getDBO();
		$cid	= JRequest::getVar('cid', array(0), 'method', 'array');
		$query = "SELECT id, plugin FROM #__fabrik_cron WHERE id IN (" . implode(',', $cid).")";
		$db->setQuery($query);
		$rows = $db->loadObjectList();
		$pluginManager	 	=& JModel::getInstance( 'Pluginmanager', 'FabrikModel' );
		$tableModel =& JModel::getInstance( 'table', 'FabrikModel' );
		$c = 0;
	foreach ($rows as $row) {
			//load in the plugin
			$plugin =& $pluginManager->getPlugIn( $row->plugin, 'cron' );
			$plugin->setId($row->id);
			$params =& $plugin->getParams();
			
			$thisTableModel = clone($tableModel);
			$thisTableModel->setId( $params->get('table'));
			$table =& $tableModel->getTable();
			$total 						= $thisTableModel->getTotalRecords();
			$nav =& $thisTableModel->getPagination( $total, 0, $total );
			$data  = $thisTableModel->getData();
			// $$$ hugh - added table model param, in case plugin wants to do further table processing
			$c = $c + $plugin->process($data, $thisTableModel);
		}
		$this->setRedirect('index.php?option=com_fabrik&c=cron', $c . "records updated");
	}
	/**
	 * Edit a cron job
	 */

	function edit()
	{
		
		$user	  = &JFactory::getUser();
		$db =& JFactory::getDBO();
		$row =& JTable::getInstance( 'Cron', 'Table' );
		if ($this->_task == 'edit') {
			$cid	= JRequest::getVar('cid', array(0), 'method', 'array');
			$cid	= array((int) $cid[0]);
		} else {
			$cid	= array( 0 );
		}

		$row->load( $cid[0] );
		if ($cid) {
			$row->checkout( $user->get( 'id' ) );
		}

		$model		= &$this->getModel( 'Cron' );
		
		// get params definitions
		
		$lists = array();
		
		$units = array(
		  JHTML::_('select.option', 'minute', JText::_( 'MINUTE' )),
			JHTML::_('select.option', 'hour', JText::_( 'HOUR' )),
			JHTML::_('select.option', 'day', JText::_( 'DAY' )),
			JHTML::_('select.option', 'week', JText::_( 'WEEK' )),
			JHTML::_('select.option', 'month', JText::_( 'MONTH' )),
			JHTML::_('select.option', 'year', JText::_( 'YEAR' )),
		);
		$lists['unit'] = JHTML::_( 'select.genericlist',  $units, 'unit', 'class="inputbox"', 'value', 'text', $row->unit );
		
		//build list of visualization plugins
		$pluginManager	 	=& JModel::getInstance( 'Pluginmanager', 'FabrikModel' );
		$pluginManager->getPlugInGroup( 'cron' );
		
		$lists['plugins'] = $pluginManager->getElementTypeDd( $row->plugin, 'plugin', 'class="inputbox"' );
		
		// Create the form
		$form = new JParameter('', JPATH_COMPONENT.DS.'models'.DS.'cron.xml' );
		$form->loadINI( $row->attribs );
		
		require_once( JPATH_COMPONENT.DS.'views'.DS.'cron.php' ); 
		FabrikViewCron::edit( $row, $form, $lists, $pluginManager );
	}
	
  /**
   * cancel editing
   */

  function cancel()
  {
    JRequest::checkToken() or die( 'Invalid Token' );
  	$row 		=& JTable::getInstance('cron', 'Table');
  	$id 		= JRequest::getInt( 'id', 0, 'post' );
  	$row->load($id);
  	$row->checkin();
  	$this->setRedirect( 'index.php?option=com_fabrik&c=cron' );
  }
  
	/**
	 * Save cron
	 */

	function save()
	{
		// Check for request forgeries
		JRequest::checkToken() or die( 'Invalid Token' );
		jimport('joomla.utilities.date');
		$user =& JFactory::getUser();
		$db =& JFactory::getDBO();
		
		$row =& JTable::getInstance('cron', 'Table');
		
		$post	= JRequest::get( 'post' );
		if (!$row->bind( $post )) {
			return JError::raiseWarning( 500, $row->getError() );
		} 

		$now = new JDate();
		
		if ($row->id == 0) {
			$row->created = $now->toMySQL();
			$row->created_by = $user->get('id');
			$row->created_by_alias = $user->get('username');
		} else {
		
			$row->modified = $now->toMySQL();
			$row->modified_by = $user->get('id');
		}
		//set the lastrun date to be that shown in the date selections
		echo JRequest::getVar('lastrun');

		// 	save params
		$params = new fabrikParams($row->attribs, JPATH_COMPONENT.DS.'xml'.DS.'cron.xml');
		$row->attribs = $params->updateAttribsFromParams( JRequest::getVar( 'params', array(), 'post', 'array' ) );
		
		
		if (!$row->store( )) {
			return JError::raiseWarning( 500, $row->getError() );
		} 
		$row->checkin( );
		$task = JRequest::getCmd( 'task' );
	
		switch ($task)
		{
			case 'apply':
				$link = 'index.php?option=com_fabrik&c=cron&task=edit&cid[]='. $row->id ;
				break;

			case 'save':
			default:
				$link = 'index.php?option=com_fabrik&c=cron';
				break;
		}
		$this->setRedirect( $link, JText::_( 'CRON SAVED' ) );
	}
	
	/**
	 * Publish a cron
	 */

	function publish()
	{
		// Check for request forgeries
		JRequest::checkToken() or die( 'Invalid Token' );
		
		$this->setRedirect( 'index.php?option=com_fabrik&c=cron' );

		// Initialize variables
		$db			=& JFactory::getDBO();
		$user		=& JFactory::getUser();
		$cid		= JRequest::getVar( 'cid', array(), 'post', 'array' );
		$task		= JRequest::getCmd( 'task' );
		$publish	= ($task == 'publish');
		$n			= count( $cid );

		if (empty( $cid )) {
			return JError::raiseWarning( 500, JText::_( 'NO ITEMS SELECTED' ) );
		}

		JArrayHelper::toInteger( $cid );
		$cids = implode( ',', $cid );

		$query = 'UPDATE #__fabrik_cron'
		. ' SET state = ' . (int) $publish
		. ' WHERE id IN ( '. $cids.'  )'
		. ' AND ( checked_out = 0 OR ( checked_out = ' .(int) $user->get('id'). ' ) )'
		;
		$db->setQuery( $query );
		if (!$db->query()) {
			return JError::raiseWarning( 500, $row->getError() );
		}
		$this->setMessage( JText::sprintf( $publish ? 'ITEMS PUBLISHED' : 'ITEMS UPUBLISHED', $n ) );
	}

	/**
	 * Display the list of cron jobs
	 */

	function display()
	{
		global $mainframe;
		
		$db =& JFactory::getDBO();
		
		//check if the cron plugin is installed
		$db->setQuery( "SELECT count(id) FROM #__plugins WHERE folder = 'system' AND element = 'fabrikcron'");
		$res = $db->loadResult();
		if ($res === 0) {
			return JError::raiseWarning( 500, 'You must have the fabrik cron system plugin installed for this to work' );
		}
		$user =& JFactory::getUser();
		$context			= 'com_fabrik.cron.list.';
		$limit				= $mainframe->getUserStateFromRequest( $context.'limit', 'limit', $mainframe->getCfg('list_limit'), 'int' );
		$limitstart 		= $mainframe->getUserStateFromRequest( $context.'limitstart', 'limitstart', 0, 'int' );
		$filter_order		= $mainframe->getUserStateFromRequest( $context.'filter_order',		'filter_order',		'label',	'cmd' );
		$filter_order_Dir	= $mainframe->getUserStateFromRequest( $context.'filter_order_Dir',	'filter_order_Dir',	'',			'word' );
		
		$lists = array ( );
		$where = array();

		$where		= count( $where ) ? ' WHERE ' . implode( ' AND ', $where ) : '';
		$orderby	= ' ORDER BY '. $filter_order .' '. $filter_order_Dir;
		
		// get the total number of records
		$db->setQuery( "SELECT COUNT(id) FROM #__fabrik_cron ". $where );
		$total = $db->loadResult();
		
		jimport( 'joomla.html.pagination' );
		$pageNav = new JPagination( $total, $limitstart, $limit );
		
		$sql = "SELECT *, u.name AS editor, c.id AS id FROM #__fabrik_cron AS c ".
			"\n LEFT JOIN #__users AS u ON u.id = c.checked_out" .
			"\n $where $orderby";
		$db->setQuery( $sql, $pageNav->limitstart, $pageNav->limit );
		
		$rows = $db->loadObjectList( );

		$arElcount = array();
		
		// table ordering
		$lists['order_Dir']	= $filter_order_Dir;
		$lists['order']		= $filter_order;
		
		require_once( JPATH_COMPONENT.DS.'views'.DS.'cron.php' );
		FabrikViewCron::show( $rows, $pageNav, $lists );
	}
	
	/**
	 * copy 
	 * @param int connection id
	 */
	 
	function copy( )
	{
		// Check for request forgeries
		JRequest::checkToken() or die( 'Invalid Token' );
		
		$this->setRedirect( 'index.php?option=com_fabrik&c=cron' );

		$cid		= JRequest::getVar( 'cid', null, 'post', 'array' );
		$db			=& JFactory::getDBO();
		$row		=& JTable::getInstance( 'cron', 'Table' );
		$user		= &JFactory::getUser();
		$n			= count( $cid );

		if ($n > 0)
		{
			foreach ($cid as $id)
			{
				if ($row->load( (int)$id ))
				{
					$row->id				= 0;
					$row->label	= 'Copy of ' . $row->label;
					if (!$row->store()) {
						return JError::raiseWarning( $row->getError() );
					}
				}
				else {
					return JError::raiseWarning( 500, $row->getError() );
				}
			}
		}
		else {
			return JError::raiseWarning( 500, JText::_( 'NO ITEMS SELECTED' ) );
		}
		$this->setMessage( JText::sprintf( 'ITEMS COPIED', $n ) );
	}
	
	/**
	 * delete cron
	 */
	 
	function remove( )
	{
		
		// Check for request forgeries
		JRequest::checkToken() or die( 'Invalid Token' );
		
		$this->setRedirect( 'index.php?option=com_fabrik&c=cron' );

		// Initialize variables
		$db		=& JFactory::getDBO();
		$cid	= JRequest::getVar( 'cid', array(), 'post', 'array' );
		$n		= count( $cid );
		JArrayHelper::toInteger( $cid );
		if ($n)
		{
			$query = 'DELETE FROM #__fabrik_cron'
			. ' WHERE id = ' . implode( ' OR id = ', $cid )
			;
			$db->setQuery( $query );
			if (!$db->query()) {
				JError::raiseWarning( 500, $db->getError() );
			}
		}
		$this->setMessage( JText::sprintf( 'ITEMS REMOVED', $n ) );
	}
}
?>