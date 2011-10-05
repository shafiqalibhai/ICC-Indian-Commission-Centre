<?php
/**
 * @package Joomla
 * @subpackage Fabrik
 * @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.model');
jimport('joomla.filesystem.file');

class FabrikModelPluginmanager extends JModel{

	/** @var array plugins */
	var $_plugIns = array();
	var $_loading = null;
	var $_group = null;
	var $_errs = array();
	var $_runPlugins = 0;

	var $_paths = array();

	/** @var array element lists */
	var $_elementLists = array();

	/** @var array containing out put from run plugins */
	var $_data = array();

	/**
	 * constructor
	 */

	function __construct()
	{
		parent::__construct();
	}


	/**
	 * get a html drop down list of the elment types with this objs element type selected as default
	 * @param string default selected option
	 * @param string html name for drop down
	 * @param string extra info for drop down
	 * @return string html element type list
	 */

	function getElementTypeDd( $default, $name='plugin', $extra='class="inputbox elementtype"  size="1"', $defaultlabel='' )
	{
		$hash = $default.$name.$extra.$defaultlabel;
		if (!array_key_exists($hash, $this->_elementLists)){
			if ($defaultlabel == '') {
				$defaultlabel = JText::_( 'PLEASE SELECT' );
			}
			$a = array( JHTML::_('select.option', '', $defaultlabel ) );
			$elementstypes = $this->_getList();
			$elementstypes = array_merge( $a, $elementstypes );
			$this->_elementLists[$hash] = JHTML::_('select.genericlist',  $elementstypes, $name, $extra , 'value', 'text', $default );
		}
		return $this->_elementLists[$hash];
	}

	function canUse()
	{
		return true;
	}

	/**
	 * get an unordered list of plugins
	 * @param string plugin group
	 * @param string ul id
	 */

	function getList( $group, $id )
	{
		$str = "<ul id='$id'>";
		$elementstypes = $this->_getList();
		foreach ($elementstypes as $plugin) {
			$str .= "<li>" . $plugin->text . "</li>";
		}
		$str .= "</ul>";
		return $str;
	}

	/**
	 * get a list of plugin ids/names for usin in a drop down list
	 * if no group set defaults to element list
	 * @return array plugin list
	 */

	function _getList()
	{
		$db =& JFactory::getDBO();
		if (is_null( $this->_group )) {
			$this->_group = 'element';
		}
		$db->setQuery( "SELECT name AS value, label AS text FROM #__fabrik_plugins WHERE type='$this->_group' AND state ='1' ORDER BY text" );
		$elementstypes = $db->loadObjectList();
		return $elementstypes;
	}

	/**
	 * get a certain group of plugins
	 * @param string plugin group to load
	 * @return array plugins
	 */

	function &getPlugInGroup( $group )
	{
		if (array_key_exists( $group, $this->_plugIns ))
		{
			return $this->_plugIns[$group];
		} else {
			return $this->loadPlugInGroup( $group );
		}
	}

	/**
	 * add to the document head all element js files
	 * used in calendar to ensure all element js files are loaded from unserialized form
	 */

	function loadJS()
	{
		$document =& JFactory::getDocument();
		$files = JFolder::files( JPATH_SITE . '/components/com_fabrik/plugins/element',  'javascript.js$', true, true  );
		foreach ($files as $f) {
			$f =  str_replace("\\", "/", str_replace( JPATH_SITE, '', $f));
			$file = basename( $f );
			$folder = dirname( $f );
			$folder = FabrikString::ltrimword( $folder, '/' ) .'/';
			FabrikHelperHTML::script( $file, $folder, true );
		}
	}

	/**
	 *@param string plugin type - element/form/table/validationrule supported
	 *loads ABSTRACT version of a plugin group
	 */

	function &loadPlugInGroup( $group )
	{
		$db =& JFactory::getDBO();
		$this->_plugIns[$group] = array();
		$this->_group = $group;
		$db->setQuery( "SELECT * FROM #__fabrik_plugins WHERE type = '$group'" );
		$plugIns = $db->loadObjectList();
		$n = count( $plugIns );
		for ($i = 0; $i < $n; $i++) {
			$plugIn = $plugIns[$i];
			$this->_loadPlugin( $group, $plugIn );
		}
		return $this->_plugIns[$group];
	}

	/**
	 * @param string plugin name e.g. fabrikfield
	 * @param string plugin type element/ form or table
	 */

	function getPlugIn( $className, $group )
	{
		if (array_key_exists( $group, $this->_plugIns ) && array_key_exists( $className, $this->_plugIns[$group] )) {
			return $this->_plugIns[$group][$className];
		} else {
			return $this->loadPlugIn( $className, $group );
		}
	}

	/**
	 * @param string plugin name e.g. fabrikfield
	 * @param string plugin type element/ form or table
	 */

	function &loadPlugIn( $className, $group )
	{
		$db =& JFactory::getDBO();
		$db->setQuery( "SELECT * FROM #__fabrik_plugins WHERE type = '$group' AND name = '$className'" );
		$plugIn = $db->loadObject();
		if (!$this->_loadPlugin( $group, $plugIn )) {
			return JError::raiseError( 500, JText::sprintf("DID NOT FIND PLUGIN %s TO LOAD", $className));
		}
		return $this->_plugIns[$group][$className];
	}

	/**
	 * load all the forms plugins
	 *
	 * @param object form model
	 * @return array of group objects with plugin objects loaded in group->elements
	 */

	function getFormPlugins( &$form )
	{
		if (!isset($this->formplugins)) {
			$this->formplugins = array();
		}
		if (!array_key_exists( $form->_id, $this->formplugins)) {
			$this->formplugins[$form->_id] = array();
			$groupIds = $form->getGroupIds();
			if (empty( $groupIds )) { //new form
				return array();
			}
			$db =& JFactory::getDBO();
			$query = "SELECT *, e.name AS name, e.id AS id, e.state AS state, e.label AS label FROM #__fabrik_elements AS e\n".
			"INNER JOIN #__fabrik_plugins AS p \n".
			"ON p.name = e.plugin \n".
			"WHERE group_id IN (" . implode(',', $groupIds) . ") AND p.type = 'element'\n".
			"ORDER BY group_id, ordering";
			$db->setQuery( $query );
			$elements = $db->loadObjectList();

			$groupModels = $form->getGroups();

			$group = 'element';
			foreach ($elements as $element) {
			  
				$this->_loadPaths( $group, $element->plugin );
				$pluginModel =& JModel::getInstance( $element->plugin, 'FabrikModel' );

				$pluginModel->_type = $group;

				$pluginModel->_xmlPath = COM_FABRIK_FRONTEND.DS.'plugins'.DS.$group.DS.$element->plugin.DS.$element->plugin.'.xml';;

				$pluginModel->setId( $element->id );
				$groupModel =& $groupModels[$element->group_id];
				$pluginModel->setContext( $groupModel, $form, $form->_table );
				$pluginModel->bindToElement( $element );
					
				$pluginModel->_formId = $form->_id;
					
				$groupModel->elements[$pluginModel->_id] = $pluginModel;

			}

			foreach ($groupModels as $groupid => $g) {
				$this->formplugins[$form->_id][$groupid] = $g;
			}
		}
		return $this->formplugins[$form->_id];
	}

	/**
	 * @param string name of plugin group to load
	 * @param array list of default element lists
	 * @param array list of default and plugin element lists
	 */

	function loadLists( $group, $lists, &$elementModel )
	{
		if (empty( $this->_plugIns )) {
			$this->loadPlugInGroup( $group );
		}
		foreach ($this->_plugIns[$group] as $plugIn) {
			if (method_exists( $plugIn->object, 'getAdminLists' ))
			{
				$lists = $plugIn->object->getAdminLists( $lists, $elementModel, $plugIn->params );
			}
		}
		return $lists;
	}

	/**
	 * @param string group name (currently only 'element' is supported)
	 * @param object database row of plugin info
	 * @return bol true if loaded ok
	 */

	function _loadPlugin( $group, &$row )
	{
		if (!is_object( $row )) {
			return false;
		}
		$folder     = $row->type;
		$element    = $row->name;
		$published  = $row->state;
		$params     = $row->params;
		$p 					= COM_FABRIK_FRONTEND.DS.'plugins'.DS. $folder . DS . $element .DS;
		$path 			= $p . $element . '.php';
		$xmlPath 		= $p . $element . '.xml';
		$lang				= $p . "language";
		JModel::getInstance( 'Element', 'FabrikModel' );
		JModel::getInstance( 'Plugin', 'FabrikModel' );
		JModel::getInstance( 'Visualization', 'FabrikModel' );

		$this->_loadPaths( $group, $element );
		$plugIn = & JModel::getInstance( $element, 'FabrikModel' );

		if (JError::isError( $plugIn )) {
			JError::handleMessage( $plugIn );
		}
		$plugIn->_type = $folder;
		$plugIn->_pluginLabel = $row->label;
		$plugIn->_xmlPath = $xmlPath;
		$this->_plugIns[$group][$element] 	=& $plugIn;
		$plugIn->_loading = null;
		return true;
	}

	/**
	 * check and load in required plugin paths
	 *
	 * @param string plugin $group
	 * @param string plugin name
	 * @return unknown
	 */

	function _loadPaths( $group, $element )
	{
		if (!array_key_exists("$group.$element", $this->_paths)) {
			//@TODO hmm still loads in x times per table element - guess we have n instanaces of the pluginmanager going on?
			$p 					= COM_FABRIK_FRONTEND.DS.'plugins'.DS. $group . DS . $element .DS;
			$path 			= $p . $element . '.php';
			$xmlPath 		= $p . $element . '.xml';
			if (!JFile::exists( $path )) {
				$path = COM_FABRIK_FRONTEND.DS.'plugins'.DS.$group.DS.$element.DS.'models'.DS. $element . '.php';
				if (!file_exists( $path )) {
					return JError::raiseWarning( E_NOTICE, "cant load $group:$element - missng files $path" );
				}
			}
			if (!JFile::exists( $xmlPath )) {
				return JError::raiseWarning( E_NOTICE, "cant load $group:$element - missng files $xmlPath" );
			}
			$cPaths = JModel::addIncludePath( $p );

			$cPaths = JModel::addIncludePath( COM_FABRIK_FRONTEND.DS.'plugins'.DS.$group.DS.$element );

			//for viz & MVC plugins
			$cPaths = JModel::addIncludePath( COM_FABRIK_FRONTEND.DS.'plugins'.DS.$group.DS.$element.DS.'models' );

			// Load common language files
			$lang =& JFactory::getLanguage();
			$langfile = 'com_fabrik.plg.'. $group . '.'.$element;
			$lang->load( $langfile );
			$lang->load( $langfile, COM_FABRIK_BASE );
			$this->_paths["$group.$element"] = 1;
		}
	}

	/**
	 * run form & element plugins - yeah!
	 * @param string method to check and call - corresponds to stage of form processing
	 * @param object model calling the plugin form/table
	 * @param string plugin type to call form/table
	 * @return bol false if error found and processed, otherwise true
	 */

	function runPlugins( $method, &$oRequest, $type = 'form' )
	{
		$params =& $oRequest->getParams();
		$this->getPlugInGroup( $type );
		$return = true;
		$usedPlugins 	= $params->get('plugin', "", "_default", "array");
		$usedLocations 	= $params->get('plugin_locations', "", "_default",  "array");
		$usedEvents 	= $params->get('plugin_events', "", "_default",  "array");
		$this->_data = array();

		if ($type != 'table') {

			if(method_exists($oRequest, 'getGroupsHiarachy')){
				$groups =& $oRequest->getGroupsHiarachy();
				foreach ($groups as $groupModel) {
					$elementModels =& $groupModel->getPublishedElements();
					foreach ($elementModels as $elementModel ) {
						if (method_exists( $elementModel, $method)) {
							$elementModel->$method( $oRequest );
						}
					}
				}
			}
		}
		$c = 0;
		$runPlugins = 0;

		foreach ($usedPlugins as $usedPlugin) {
			if ($usedPlugin != '') {
				$oPlugin = $this->_plugIns[$type][$usedPlugin];
				//testing this if statement as onLoad was being called on form email plugin when no method availbale
				if (method_exists( $oPlugin, $method )) {
					$modelTable = $oRequest->getTable();
					//below moved to plugin model->setParams();

					$oPlugin->renderOrder = $c;
					$pluginParams =& $oPlugin->setParams( $params, $usedLocations, $usedEvents );
					//end
					if ($oPlugin->canUse( $oRequest, $usedLocations[$c], $usedEvents[$c]) &&  method_exists( $oPlugin, $method )) {

						$numargs = func_num_args();
						$pluginArgs = array();
						if($numargs > 3){
							$arg_list = func_get_args();
							for ($i = 3; $i < $numargs; $i++) {
								$pluginArgs[] =& $arg_list[$i];
							}
						}
						
						$ok = $oPlugin->$method( $pluginParams, $oRequest, $pluginArgs );
						if ($ok === false) {
							$this->_errs[] = $oPlugin->_err;
							return false;
						} else {
							$thisreturn = $oPlugin->customProcessResult( $method );
							if (!$thisreturn && $return) {
								$return = false;
							}
							$m = $method.'_result';
							if (method_exists( $oPlugin, $m )) {
								$this->_data[] = $oPlugin->$m( $c );
							}
						}
						$runPlugins ++;
					}
				}
				$c ++;
			}
		}
		$this->_runPlugins = $runPlugins;
		return $return;
	}
}
?>