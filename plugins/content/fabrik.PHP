<?php
/**
 * @package		Joomla
 * @subpackage	Fabik
 * @copyright	Copyright (C) 2005 - 2008 Pollen 8 Design Ltd. All rights reserved.
 * @license		GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined( '_JEXEC' ) or die();

jimport( 'joomla.plugin.plugin' );

/**
 * Fabrik content plugin - renders forms and tables
 *
 * @package		Joomla
 * @subpackage	Content
 * @since 		1.5
 */

class plgContentFabrik extends JPlugin
{

	/**
	 * Constructor
	 *
	 * For php4 compatability we must not use the __constructor as a constructor for plugins
	 * because func_get_args ( void ) returns a copy of all passed arguments NOT references.
	 * This causes problems with cross-referencing necessary for the observer design pattern.
	 *
	 * @param object $subject The object to observe
	 * @param object $params  The object that holds the plugin parameters
	 * @since 1.5
	 */

	function plgContentFabrik( &$subject, $params = null)
	{
		parent::__construct( $subject, $params );
	}

	/**
	 * Example prepare content method
	 *
	 * Method is called by the view
	 *
	 * @param 	object		The article object.  Note $article->text is also available
	 * @param 	object		The article params
	 * @param 	int			The 'page' number
	 */

	function onPrepareContent( &$article, &$params, $limitstart=0 )
	{
		//load fabrik language
		$lang =& JFactory::getLanguage();
		$lang->load( 'com_fabrik' );

		// Get plugin info
		$plugin =& JPluginHelper::getPlugin( 'content', 'fabrik' );
		// $$$ hugh had to rename this, it was stomping on com_content and friends $params
		// $$$ which is passed by reference to us!
		$fparams = new JParameter( $plugin->params );

		// simple performance check to determine whether bot should process further
		$botRegex = ($fparams->get( 'Botregex' ) != '') ? $fparams->get( 'Botregex' ) : 'fabrik';

		if (JString::strpos( $article->text, $botRegex) === false) {
			return true;
		}
		$regex = "/{" .$botRegex ."\s*.*?}/i";
		$article->text = preg_replace_callback( $regex, array($this, 'replace'), $article->text );
	}

	/**
	 * the functon called from the preg_replace_callback - replace the {} with the correct html
	 *
	 * @param string plugin match
	 * @return unknown
	 */

	function replace( $match )
	{
		$match = $match[0];
		$match = trim( $match, "{" );
		$match = trim( $match, "}" );
		$match = explode( " ", $match );
		array_shift( $match );
		$user = JFactory::getUser();
		$usersConfig 	=& JComponentHelper::getParams( 'com_fabrik' );
		$unused = array();
		$element = false; // special case if we are wanting to write in an element's data
		$repeatcounter = 0;
		foreach ($match as $m) {
			$m = explode("=", $m);
			switch ($m[0])
			{
				case 'view':
					$viewName = strtolower($m[1]);
					break;
				case 'id':
					$id = $m[1];
					break;
				case 'layout':
					$layout = $m[1];
					JRequest::setVar('layout', $layout);
					break;
				case 'row':
					$row = $m[1];
					if ($row == -1) {
						$row = $user->get('id');
					}
					$usersConfig->set('rowid', $row);
					break;
				case 'element':
					//{fabrik view=element table=3 rowid=364 element=fielddatatwo}
					$element = $m[1];
					break;
				case 'rowid':
					$rowid = $m[1];
					break;
				case 'table':
					$tableid = $m[1];
					break;
				case 'repeatcounter':
					$repeatcounter = $m[1];
					break;
				/*case 'subview':
					$subview = $m[1];
					//for viz to define subview
					JRequest::setVar('subview', $subview);
					break;*/
				default:
					$unused[$m[0]] = $m[1];//these are later set as jrequest vars if presenet in table view
			}
		}


		if ($element) {
			//special case for rendering element data
			$controller = $this->_getController( 'table', $tableid );
			$model 			=& $this->_getModel( $controller, 'table', $tableid );
			$model->setId( $tableid );
			$formModel =& $model->getForm();
			$groups =& $formModel->getGroupsHiarachy();
			foreach ($groups as $groupModel) {
				$elements =& $groupModel->getMyElements();
				foreach ($elements as &$elementModel) {
					if ($element == $elementModel->getFullName(true, true, false)) {
						$activeEl = $elementModel;
						continue 2;
					}
				}
			}
			$row 		=& $model->getRow( $rowid );
			$name 	= $activeEl->getFullName( true, true, false );
			$defaultdata = array( $name => $row->$element );
			$activeEl->_editable 	= false;
			return $activeEl->render( $defaultdata, $repeatcounter );
		}
		if (!isset( $viewName )) {
			return;
		}

		$this->generalIncludes( $viewName );

		$document 	=& JFactory::getDocument();
		$viewType		= $document->getType();
		$controller = $this->_getController( $viewName, $id );
		$view 			=& $this->_getView( $controller, $viewName, $id );
		$model 			=& $this->_getModel( $controller, $viewName, $id );

		
		$origid = JRequest::getVar( 'id' );
		$origView = JRequest::getVar( 'view' );
		JRequest::setVar( 'id', $id );
		JRequest::setVar( 'view', $viewName );
		// $$$ hugh - at least make the $origid available for certain corner cases, like ...
		// http://fabrikar.com/forums/showthread.php?p=42960#post42960
		JRequest::setVar( 'origid', $origid, 'GET', false);


		if (!JError::isError( $model )) {
			$model->setAdmin( false );
			$view->setModel( $model, true );
		}

		// Display the view
		$view->assign( 'error', $controller->getError() );
		$view->_isMambot = true;

		// do some view specific code
		switch ($viewName) {
			case 'form':
			case 'details':
				$view->setId( $id );
				$model->_postMethod = 'ajax';
				$model->_id = $id;
				break;
			case 'table':
				$view->setId( $id );
				$model->setId($id);
				$formModel =& $model->getForm();
				//apply filters set in mambot
				foreach ($unused as $k=>$v) {
					if ($formModel->hasElement( $k )) {
						JRequest::setVar( $k, $v );
					}
				}
				break;
			case 'visualization':
				foreach ($unused as $k=>$v) {
					JRequest::setVar( $k, $v );
				}
				break;
		}
		//hack for gallery viz as it may not use the default view
		$controller->_isMambot = true;
		$result = $controller->display();
		JRequest::setVar( 'id', $origid );
		JRequest::setVar( 'view', $origView );
		return $result;
	}

	/**
	 * get the model
	 * @param object controller
	 * @param string $viewName
	 * @param int id
	 */

	function _getModel( &$controller, $viewName, $id )
	{
		if ($viewName == 'visualization') {
			$viewName = $this->_getPluginVizName( $id );
		}
		if ($viewName == 'details') {
			$viewName = 'form';
		}
		return $controller->getModel( $viewName );
	}

	/**
	 * get a view
	 * @param object controller
	 * @param string $viewName
	 * @param int id
	 */

	function _getView( &$controller, $viewName, $id )
	{
		$document =& JFactory::getDocument();
		$viewType	= $document->getType();
		if ($viewName == 'visualization') {
			$viewName = $this->_getPluginVizName( $id );
		}
		if ($viewName == 'details') {
			$viewName = 'form';
		}
		$view = &$controller->getView( $viewName, $viewType );
		return $view;
	}

	/**
	 * get the viz plugin name
	 *
	 * @param int $id
	 * @return string viz plugin name
	 */

	function _getPluginVizName( $id )
	{
		if (!isset($this->pluginVizName)) {
			$this->pluginVizName = array();
		}
		if (!array_key_exists($id, $this->pluginVizName)) {
			$db =& JFactory::getDBO();
			$db->setQuery( 'SELECT plugin FROM #__fabrik_visualizations WHERE id = '.$id );
			$this->pluginVizName[$id] = $db->loadResult();
		}
		return $this->pluginVizName[$id];
	}

	/**
	 * get the controller
	 *
	 * @param string $viewName
	 * @param int $id
	 * @return object controller
	 */

	function _getController( $viewName, $id )
	{
	  switch ($viewName) {
	    case 'visualization':
				$name = $this->_getPluginVizName( $id );
				$path = COM_FABRIK_FRONTEND.DS.'plugins'.DS.'visualization'.DS.$name.DS.'controllers'.DS.$name.'.php';
				if (file_exists($path)) {
					require_once $path;
				}
				$controllerName = 'FabrikControllerVisualization'.$name;
				$controller = new $controllerName();
				$controller->addViewPath( COM_FABRIK_FRONTEND.DS.'plugins'.DS.'visualization'.DS.$name.DS.'views' );
				$controller->addViewPath(COM_FABRIK_FRONTEND.DS.'views');
				//add the model path
				$modelpaths = JModel::addIncludePath( COM_FABRIK_FRONTEND.DS.'plugins'.DS.'visualization'.DS.$name.DS.'models' );
				$modelpaths = JModel::addIncludePath( COM_FABRIK_FRONTEND.DS.'models' );
				break;
	    case 'form':
	      $controller = new FabrikControllerForm();
				break;
	    case 'table':
	      $controller = new FabrikControllerTable();
				break;
			case 'pacakge':
	      $controller = new FabrikControllerPackage();
				break;
	    default:
				$controller = new FabrikController();
				break;
	  }
		return $controller;
	}

	/**
	 * load the required fabrik files
	 *
	 * @param string $view
	 */

	function generalIncludes( $view )
	{
		if (!defined('COM_FABRIK_BASE') )
		{
			define( "COM_FABRIK_BASE",  JPATH_BASE );
			define( "COM_FABRIK_FRONTEND",  JPATH_BASE.DS.'components'.DS.'com_fabrik' );
			define( "COM_FABRIK_LIVESITE",  JURI::base() );
		}
		require_once( COM_FABRIK_FRONTEND.DS.'controller.php' );
		require_once( COM_FABRIK_FRONTEND.DS.'controllers/form.php' );
		require_once( COM_FABRIK_FRONTEND.DS.'controllers/package.php' );
		require_once( COM_FABRIK_FRONTEND.DS.'controllers/table.php' );
		require_once( COM_FABRIK_FRONTEND.DS.'models'.DS.'parent.php' );
		JTable::addIncludePath( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_fabrik'.DS.'tables' );
		JModel::addIncludePath( COM_FABRIK_FRONTEND.DS.'models' );
		if ($view == 'details') {
			$view = 'form';
		}
		require_once( COM_FABRIK_FRONTEND.DS.'views'.DS.$view.DS.'view.html.php' );
	}

}