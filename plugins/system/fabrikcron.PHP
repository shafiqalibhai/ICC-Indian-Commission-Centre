<?php
/**
 * @package		Joomla
 * @subpackage fabrik
 * @copyright	Copyright (C) 2005 - 2008 Pollen 8 Design Ltd. All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 */

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.plugin.plugin' );

/**
 * Joomla! Fabrik cron job plugin
 *
 * @author		Rob Clayburn <rob@pollen-8.co.uk>
 * @package		Joomla
 * @subpackage	fabrik
 */
class plgSystemFabrikcron extends JPlugin
{

	/**
	 * Constructor
	 *
	 * For php4 compatability we must not use the __constructor as a constructor for plugins
	 * because func_get_args ( void ) returns a copy of all passed arguments NOT references.
	 * This causes problems with cross-referencing necessary for the observer design pattern.
	 *
	 * @access	protected
	 * @param	object $subject The object to observe
	 * @param 	array  $config  An array that holds the plugin configuration
	 * @since	1.0
	 */
	function plgSystemFabrikcron(& $subject, $config)
	{
		parent::__construct($subject, $config);

	}

	function onAfterRender()
	{

		if (!defined('COM_FABRIK_BASE')) {
			define( "COM_FABRIK_BASE",  JPATH_SITE );
			define( "COM_FABRIK_FRONTEND",  JPATH_SITE.DS.'components'.DS.'com_fabrik' );
			define( "COM_FABRIK_LIVESITE",  JURI::base() );
		}
		jimport( 'joomla.application.component.model' );
		require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'params.php' );
		require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'string.php' );
		require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'html.php' );
		require_once( COM_FABRIK_FRONTEND.DS.'models'.DS.'parent.php' );
		JTable::addIncludePath(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_fabrik'.DS.'tables');
		JModel::addIncludePath( JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'models' );
		//get all active tasks
		$db =& JFactory::getDBO();
		$now = JRequest::getVar('fabrikcron_run', false);
		if (!$now) {
			$db->setQuery("SELECT id, plugin FROM #__fabrik_cron WHERE state = '1' \n".
			"AND (INTERVAL 1 `unit`  + `lastrun` ) < NOW()");
	
			$nextrun = "CASE WHEN unit = 'minute' THEN DATE_ADD( lastrun, INTERVAL frequency HOUR )\n"
			."WHEN unit = 'hour' THEN DATE_ADD( lastrun, INTERVAL frequency MINUTE )\n"
			."WHEN unit = 'day' THEN DATE_ADD( lastrun, INTERVAL frequency DAY )\n"
			."WHEN unit = 'week' THEN DATE_ADD( lastrun, INTERVAL frequency WEEK )\n"
			."WHEN unit = 'month' THEN DATE_ADD( lastrun, INTERVAL frequency MONTH )\n"
			."WHEN unit = 'year' THEN DATE_ADD( lastrun, INTERVAL frequency YEAR ) END";
	
			$query = "SELECT id, plugin, lastrun, unit, frequency, $nextrun AS nextrun FROM jos_fabrik_cron\n"
			."WHERE state = '1' AND $nextrun < NOW()";
		} else {
			$query = "SELECT id, plugin FROM #__fabrik_cron WHERE state = '1'";
		}
		$db->setQuery( $query );
		$rows = $db->loadObjectList();
		
		if (empty( $rows )) {
			return;
		}
	
		$pluginManager	 	=& JModel::getInstance( 'Pluginmanager', 'FabrikModel' );
		$tableModel =& JModel::getInstance( 'table', 'FabrikModel' );
		//depreciated - always false now - use view to decide if we need to show elements
		//$tableModel->_onlyTableData = false;
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
					//depreciated - always false now - use view to decide if we need to show elements
		//$thisTableModel->_onlyTableData = false;
			$data  = $thisTableModel->getData();
			$plugin->process($data);
			
			$now =& JFactory::getDate();
			$now = $now->toUnix();
			$new =& JFactory::getDate($row->nextrun);
			$tmp =  $new->toUnix();
				
			switch ($row->unit) {
				case 'hour':
					$inc = 60 * 60;
					break;
				default:
				case 'day':
					$inc = 60 * 60 * 24;
					break;
			}
			//don't use NOW() as the last run date as this could mean that the cron 
			//jobs aren't run as frequently as specifed
			//if the lastrun date was set in admin to ages ago, then incrementally increase the
			//last run date until it is less than now
			while ($tmp + ($inc * $row->frequency) < $now) {
				$tmp = $tmp + ($inc * $row->frequency);
			}

			//mark them as being run
			$nextrun =& JFactory::getDate($tmp);
			$db->setQuery('update #__fabrik_cron SET lastrun = "'.$nextrun->toMySQL() .'" WHERE id = '.$row->id);
			$db->query();
		}

	}

}