<?php
/**
* @version		$Id: helper.php 10616 2008-08-06 11:06:39Z hackwar $
* @package		Joomla
* @copyright	Copyright (C) 2005 - 2008 Open Source Matters. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* Joomla! is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

class modJForceHelper
{
	var $_pid 			= null;
	var $_controller 	= null;
	
	function getMenu(&$params)
	{	

		$menu = array();
		
		# DASHBOARD #
		$menu[0]->link = JRoute::_('index.php?option=com_jforce&view=dashboard');
		$menu[0]->text = JText::_('Dashboard');
		
		# SALES #
		$menu[1]->link = JRoute::_('index.php?option=com_jforce&c=sales&view=potential');
		$menu[1]->text = JText::_('Sales');
		
		# PROJECTS #
		$menu[2]->link = JRoute::_('index.php?option=com_jforce&c=project');
		$menu[2]->text = JText::_('Projects');
		
		# ACCOUNTING #
		$menu[3]->link = JRoute::_('index.php?option=com_jforce&c=accounting&view=quote');
		$menu[3]->text = JText::_('Accounting');
		
		# SUPPORT #
		$menu[4]->link = JRoute::_('index.php?option=com_jforce&c=support&view=ticket');
		$menu[4]->text = JText::_('Support');
		
		# People #
		$menu[5]->link = JRoute::_('index.php?option=com_jforce&c=people&view=company');
		$menu[5]->text = JText::_('People');
		
		# MESSAGES #
		$menu[6]->link = JRoute::_('index.php?option=com_jforce&c=message&view=message');
		$menu[6]->text = JText::_('Messages');
		
		return $menu;
	}

	function getChildMenu() {
		
		$childMenu = array();	
		$childMenu[0] = 0;
		
		$controller = JRequest::getVar('c', 'project');
		$this->_controller = $controller;
		
		$pid = JRequest::getVar('pid', 0, '', 'int');
		$this->_pid = (int)$pid;
		$views = array();
		
		if($this->_controller == 'sales'):
			$childMenu[0] = 1;
			$views = array('lead','potential','campaign');
		endif;
		
		if($this->_controller == 'project'):
			if($this->_pid):
				$childMenu[0] = 2;		
				$views = array('project','milestone','checklist','discussion','document','calendar','ticket','quote','invoice','timetracker','person');
			endif;
		endif;
	
		if($this->_controller == 'accounting'):
			$childMenu[0] = 3;
			$views = array('quote','invoice');	
		endif;
		
		if($this->_controller == 'support'):
			$childMenu[0] = 4;
			$views = array('ticket');
		endif;
		
		// SUBMENU ITEMS
		for($i=0;$i<count($views);$i++):
			$j = $i+1;
			$view = $views[$i];
			if($pid):
				$childMenu[$j]->link = JRoute::_('index.php?option=com_jforce&c='.$this->_controller.'&view='.$view.'&pid='.$this->_pid);
			else:
				$childMenu[$j]->link = JRoute::_('index.php?option=com_jforce&c='.$this->_controller.'&view='.$view);				
			endif;
			
			if($view == 'project') $view = 'overview';
			
			$childMenu[$j]->text = ucwords($view);
		
		endfor;
		
	return $childMenu;
	
}

	function getProjectName() {
		$database = JFactory::getDBO();
		
		$pid = JRequest::getVar('pid', 0, '', 'int');
		$this->_pid = (int)$pid;
		
		$name = null;
		
		if($this->_pid):
			$query = "SELECT name FROM #__jf_projects WHERE id = '$this->_pid'";
			$database->setQuery($query);
			$name = $database->loadResult();
		endif;
		
		return $name;
	}
}
