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
require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'params.php' );
require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'string.php' );
require_once( COM_FABRIK_BASE.DS.'administrator'.DS.'components'.DS.'com_fabrik'.DS.'helpers'.DS.'menu.php' );
require_once( COM_FABRIK_BASE.DS.'administrator'.DS.'components'.DS.'com_fabrik'.DS.'helpers'.DS.'adminhtml.php' );
require_once( COM_FABRIK_BASE.DS.'administrator'.DS.'components'.DS.'com_fabrik'.DS.'helpers'.DS.'fabrik.php' );

/**
 * @package		Joomla
 * @subpackage	Fabrik
 */

class FabrikControllerForm extends JController
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
		$this->registerTask( 'menulinkForm', 'save' );
		$this->registerTask( 'unpublish',	'publish' );
		$this->registerTask( 'go2menu', 'save' );
		$this->registerTask( 'go2menuitem', 'save' );

		//editing an existing record in admin
		//$this->registerTask( '', 'form' );
	}

	/**
	 * process submitted form
	 */

	function processForm()
	{
		$model =& JModel::getInstance( 'Form', 'FabrikModel' );
		$model->setId( JRequest::getInt( 'form_id', 0 ) );
		$model->getForm();
		$model->_rowId = JRequest::getVar( 'rowid', '' );
		$model->setAdmin( true );
		$post	= JRequest::get( 'post' );
		$document =& JFactory::getDocument();
		JRequest::setVar('view', 'Form' );
		$viewType	= $document->getType();
		$viewName	= JRequest::getCmd( 'view', $this->_name );
		$viewLayout	= JRequest::getCmd( 'layout', 'default' );
		$view = & $this->getView( $viewName, $viewType, '');
		$view->setModel($model, true);
		$view->_admin = true;
		if (!$model->validate()) {
			$view->display();
		} else {
			$model->process();
			$link = "index.php?option=com_fabrik&c=table&view=viewTable&task=viewTable&cid=" . JRequest::getVar( 'tableid');
			$msg = JText::_( 'RECORD SAVED' );
			$this->setRedirect( $link, $msg );
		}
	}

	/*
	 * view the form
	 */
	
	function form()
	{
		JRequest::setVar( 'view', 'Form' );
		$this->_form();
	}

	function details()
	{
		JRequest::setVar( 'view', 'details' );
		$this->_form();
	}

	function _form()
	{
		$document =& JFactory::getDocument();
		$model = JModel::getInstance( 'Form', 'FabrikModel');
		$model->setAdmin( true );
		$model->render( true );
		$viewType	= $document->getType();
		$viewName	= JRequest::getCmd( 'view', $this->_name );

		$viewLayout	= JRequest::getCmd( 'layout', 'default' );
		$view = & $this->getView( 'form', $viewType, '' );
		$view->setModel( $model, true );
		$view->_admin = true;

		// Set the layout
		$view->setLayout( $viewLayout );

		//todo check for cached version
		//JRequest::setVar( 'layout', 'admin' );
		$view->display( );
	}

	/**
	 * Edit a form
	 */

	function edit()
	{
		$user	  = &JFactory::getUser();
		$db =& JFactory::getDBO();
		$lists 	= array();
		$row =& JTable::getInstance( 'form', 'Table' );
		if ($this->_task == 'edit') {
			$cid	= JRequest::getVar( 'cid', array(0), 'method', 'array' );
			$cid	= array((int) $cid[0]);
		} else {
			$cid	= array( 0 );
		}

		$row->load( $cid[0] );
			
		if ($cid) {
			$row->checkout( $user->get( 'id' ) );
		}
		$model = JModel::getInstance( 'Form', 'FabrikModel' );
		$model->setId( $cid[0] );
		$model->getTable();
		$groupModels =& $model->getGroupsHiarachy();
		$possible_email_receipt_fields[] = JHTML::_('select.option','', 'n/a');
		foreach ($groupModels as $groupModel) {
			$groupModel->_form =& $model;
			$elementModels =& $groupModel->getMyElements();
			foreach ($elementModels as $elementModel) {
				if ($elementModel->isReceiptElement()) {
					$element =& $elementModel->getElement();
					$possible_email_receipt_fields[] = JHTML::_('select.option', $element->name, $element->label);
				}
			}
		}
		
		$db->setQuery( "SELECT id AS value, CONCAT_WS(': ', name,  email) AS text FROM #__users" );
		$users 			= $db->loadObjectList();
		$action_id 	= (isset( $row->action_id )) ? intval( $row->action_id ) : 0;
		$lists['userslist']	= JHTML::_('select.genericlist', $users, 'action_id', 'class="inputbox" size="1"', 'value', 'text', $action_id );

		// get params definitions
		$params =& new fabrikParams( $row->attribs, JPATH_COMPONENT.DS.'xml'.DS.'form.xml' );
		require_once( JPATH_COMPONENT.DS.'views'.DS.'form.php' );


		// get a list of used groups
		$sql = "SELECT  #__fabrik_formgroup.group_id AS value,
			#__fabrik_groups.name AS text
			FROM #__fabrik_formgroup 
			LEFT JOIN #__fabrik_groups 
			ON #__fabrik_formgroup.group_id = #__fabrik_groups.id
			WHERE  #__fabrik_formgroup.form_id = '".$cid[0]."' 
			AND #__fabrik_groups.name <> ''
			ORDER BY  #__fabrik_formgroup.ordering";
		$db->setQuery( $sql );
		$current_groups = $db->loadObjectList( );
		$lists['current_groups'] 	= $current_groups;
		$lists['current_grouplist'] = JHTML::_('select.genericlist',  $current_groups, 'current_groups', "class=\"inputbox\" style=\"width:100%;\" size=\"10\" ", 'value', 'text', '/' );
		// get a list of available groups - need to make the sql only return groups not already listed in mos_fabrik_fromgroup for $id

		//$$$ only unused groups can be assigned now - simplifies a load of stuff for us!
		$db->setQuery( "SELECT DISTINCT(group_id) FROM #__fabrik_formgroup" );
		$usedgroups = $db->loadResultArray();
		$db->setQuery( "SELECT id AS value, name AS text FROM #__fabrik_groups WHERE id NOT IN(".implode(",", $usedgroups) .") ORDER BY `text`" );
		$groups 			= $db->loadObjectList( );

		$lists['groups'] 	= $groups;
		$lists['grouplist']	= JHTML::_('select.genericlist', $groups, 'groups', "class=\"inputbox\" size=\"10\" style=\"width:100%;\" ", 'value', 'text', null );
		if ($cid[0] != 0) {
			$row->_database_name = $model->getTableName();
			$row->_connection_id = $model->_table->_table->connection_id;
		} else {
			//this is a new form so fill in some default values
			$row->error 		= JText::_( 'SOME OF THE FORM DATA IS MISSING' );
			$row->submit_button_label 	= JText::_( 'SUBMIT' );
			$row->_database_name 		= '';
			$row->_connection_id 		= '';
			$menus = array( );
		}
		//get the view only templates
		$templates = JFolder::folders( COM_FABRIK_FRONTEND. DS."views".DS."form".DS."tmpl" );
		foreach ($templates as $file) {
			$oTemplates[] = JHTML::_('select.option', $file );
		}
		$viewTemplate = ($row->view_only_template == '') ? "default" : $row->view_only_template;
		$lists['viewOnlyTemplates'] = JHTML::_('select.genericlist',  $oTemplates, 'view_only_template', 'class="inputbox"', 'value', 'text', $viewTemplate );

		//get the form templates
		$formTemplate = ($row->form_template == '') ? "default" : $row->form_template;
		$lists['formTemplates'] = JHTML::_('select.genericlist', $oTemplates, 'form_template', 'class="inputbox"', 'value', 'text', $formTemplate );

		//not used?
		//$params = new fabrikParams( $row->attribs, JPATH_COMPONENT.DS.'xml'.DS.'form.xml');

		$pluginManager = JModel::getInstance( 'Pluginmanager', 'FabrikModel' );
		$pluginManager->getPlugInGroup( 'form' );

		// Create the form
		$form = new JParameter( '', JPATH_COMPONENT.DS.'models'.DS.'form.xml' );

		$form->bind( $row );
		if ($cid[0] == 0 || $form->get('publish_down') == '' || $form->get('publish_down') ==  $db->getNullDate()) {
			$form->set('publish_down', JText::_('Never'));
		} else {
			$form->set('publish_down', JHTML::_('date', $row->publish_down, '%Y-%m-%d %H:%M:%S'));
		}

		$form->set( 'created', JHTML::_( 'date', $row->created, '%Y-%m-%d %H:%M:%S' ) );
		$form->set( 'publish_up', JHTML::_( 'date', $row->publish_up, '%Y-%m-%d %H:%M:%S' ) );

		$form->loadINI( $row->attribs );
		FabrikViewForm::edit( $row, $pluginManager, $lists, $params, $form );
	}

	/**
	 * cancel editing
	 */

	function cancel()
	{
		JRequest::checkToken() or die( 'Invalid Token' );
		$row 		=& JTable::getInstance('form', 'Table');
		$id 		= JRequest::getInt( 'id', 0, 'post' );
		$row->load($id);
		$row->checkin();
		$this->setRedirect( 'index.php?option=com_fabrik&c=form' );
	}

	/**
	 * Save a connection
	 */

	function save()
	{
		// Check for request forgeries
		JRequest::checkToken() or die( 'Invalid Token' );

		jimport('joomla.utilities.date');

		$db =& JFactory::getDBO();
		$user	  = &JFactory::getUser();
		$formModel =& JModel::getInstance( 'Form', 'FabrikModel' );
		$formModel->setId( JRequest::getInt('id') );
		$formModel->getForm();

		$row =& JTable::getInstance( 'form', 'Table' );

		$post	= JRequest::get( 'post' );

		if (!$row->bind( $post )) {
			return JError::raiseWarning( 500, $row->getError() );
		}

		$filter	= new JFilterInput( null, null, 1, 1 );
		$intro = JRequest::getVar( 'intro', '', 'post', 'string', JREQUEST_ALLOWRAW );
		$row->intro = $filter->clean( $intro );

		$details	= JRequest::getVar( 'details', array(), 'post', 'array' );
		$row->bind( $details );

		FabrikHelper::prepareSaveDate( $row->publish_down );
		FabrikHelper::prepareSaveDate( $row->created );
		FabrikHelper::prepareSaveDate( $row->publish_up );
		
		// save params

		$params = new fabrikParams( $row->attribs, JPATH_COMPONENT.DS.'model'.DS.'form.xml' );
		$row->attribs = $params->updateAttribsFromParams( JRequest::getVar( 'params', array(), 'post', 'array' ) );

		if ($row->id != 0) {
			$datenow =& JFactory::getDate();
			$row->modified 		= $datenow->toMySQL();
			$row->modified_by 	= $user->get('id');
		}

		if (!$row->store()) {
			return JError::raiseWarning( 500, $row->getError() );
		}
		$row->checkin();
		$formModel->_id = $row->id;
		$formModel->_form =& $row;
		$formModel->saveFormGroups();

		$task = JRequest::getCmd( 'task' );

		switch ($task)
		{
			case 'apply':
				$link = 'index.php?option=com_fabrik&c=form&task=edit&cid[]='. $row->id ;
				break;

			case 'save':
			default:
				$link = 'index.php?option=com_fabrik&c=form';
				break;
		}
		$cache = & JFactory::getCache( 'com_fabrik' );
		$cache->clean();
		$this->setRedirect( $link, JText::_( 'FORM SAVED' ) );
	}

	/**
	 * Publish a form
	 */

	function publish()
	{
		// Check for request forgeries
		JRequest::checkToken() or die( 'Invalid Token' );

		$this->setRedirect( 'index.php?option=com_fabrik&c=form' );

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

		$query = 'UPDATE #__fabrik_forms'
		. ' SET state = ' . (int) $publish
		. ' WHERE id IN ( '. $cids.'  )'
		. ' AND ( checked_out = 0 OR ( checked_out = ' .(int) $user->get('id'). ' ) )'
		;
		$db->setQuery( $query );
		if (!$db->query()) {
			return JError::raiseWarning( 500, $row->getError() );
		}
		$this->setMessage( JText::sprintf( $publish ? 'Items published' : 'Items unpublished', $n ) );
	}

	/**
	 * Display the list of forms
	 */

	function display()
	{
		global $mainframe;
		$db =& JFactory::getDBO();
		$user =& JFactory::getUser();
		// get the total number of records
		$context			= 'com_fabrik.form.list.';
		$filter_order		= $mainframe->getUserStateFromRequest( $context.'filter_order',	'filter_order',	'f.label',	'cmd' );
		$filter_order_Dir	= $mainframe->getUserStateFromRequest( $context.'filter_order_Dir',	'filter_order_Dir',	'',	'word' );
		$limit				= $mainframe->getUserStateFromRequest( $context.'limit', 'limit', $mainframe->getCfg('list_limit'), 'int' );
		$limitstart 		= $mainframe->getUserStateFromRequest( $context.'limitstart', 'limitstart', 0, 'int' );
		$filter_form 		= $mainframe->getUserStateFromRequest( $context."filter_form", 'filter_form', '' );

		// table ordering
		$lists['order_Dir']	= $filter_order_Dir;
		$lists['order']		= $filter_order;

		$where = array();
		if ($filter_form != '') {
			$where[] = " f.label LIKE '%$filter_form%' ";
		}

		if ($user->gid <= 24) {
			$where[] = " f.private = '0'";
		}
		$where		= count( $where ) ? ' WHERE ' . implode( ' AND ', $where ) : '';
		$orderby	= ' ORDER BY '. $filter_order .' '. $filter_order_Dir;

		$db->setQuery( "SELECT count(*) FROM #__fabrik_forms $where" );
		$total = $db->loadResult();

		jimport( 'joomla.html.pagination' );
		$pageNav = new JPagination( $total, $limitstart, $limit );

		$sql = "SELECT *, u.name AS editor, f.id AS id, t.id as _table_id, f.state AS state
		, f.label AS label, f.attribs AS attribs FROM #__fabrik_forms AS f" .
			"\n LEFT JOIN #__users AS u ON u.id = f.checked_out " .
			"\n LEFT JOIN #__fabrik_tables as t ON f.id = t.form_id" .
			"\n $where $orderby";
		$db->setQuery( $sql, $pageNav->limitstart, $pageNav->limit );
		$rows = $db->loadObjectList();
		if ($db->getErrorMsg() != '') {
			JError::raiseError(500, $db->getErrorMsg());
		}

		$lists['filter_form'] =  '<input type="text" value="' . $filter_form . '" name="filter_form" onblur="document.adminForm.submit( );" />';
		require_once( JPATH_COMPONENT.DS.'views'.DS.'form.php' );
		FabrikViewForm::show( $rows, $pageNav, $lists );
	}

	/**
	 * copy a Form
	 */

	function copy()
	{
		// Check for request forgeries
		JRequest::checkToken() or die( 'Invalid Token' );

		$this->setRedirect( 'index.php?option=com_fabrik&c=form' );

		$cid		= JRequest::getVar( 'cid', null, 'post', 'array' );
		$db			=& JFactory::getDBO();
		$rule		=& JTable::getInstance( 'form', 'Table' );
		$user		= &JFactory::getUser();
		$n			= count( $cid );

		if ($n > 0)
		{
			foreach ($cid as $id)
			{
				if ($rule->load( (int)$id ))
				{
					$rule->id				= 0;
					if (!$rule->store()) {
						return JError::raiseWarning( $rule->getError() );
					}
				}
				else {
					return JError::raiseWarning( 500, $rule->getError() );
				}
			}
		}

		else {
			return JError::raiseWarning( 500, JText::_( 'NO ITEMS SELECTED' ) );
		}
		$this->setMessage( JText::sprintf( 'Items copied', $n ) );
	}

	/**
	 * delete form
	 */

	function remove()
	{
		// Check for request forgeries
		JRequest::checkToken() or die( 'Invalid Token' );

		$this->setRedirect( 'index.php?option=com_fabrik&c=form' );

		// Initialize variables
		$db		=& JFactory::getDBO();
		$cid	= JRequest::getVar( 'cid', array(), 'post', 'array' );
		$n		= count( $cid );
		JArrayHelper::toInteger( $cid );

		if ($n)
		{
			$query = 'DELETE FROM #__fabrik_forms'
			. ' WHERE id = ' . implode( ' OR id = ', $cid )
			;
			$db->setQuery( $query );
			if (!$db->query()) {
				JError::raiseWarning( 500, $db->getError() );
			}
		}
		// added by CyberFabrik aka CyberTiger 08 Sep 2008 - start
		// This extra piece of code will also remove "group to form" mappings...
		// Just deleting the form is not enough..We also need to dissasociate the 
		// form from any of the groups that have been added to the form...
		//
		   $query = 'DELETE FROM #__fabrik_formgroup'
		   . ' WHERE form_id = ' . implode( ' OR form_id = ', $cid )
		   ;
		   $db->setQuery( $query );
		   if (!$db->query()) {
		      JError::raiseWarning( 500, $db->getError() );
		   }
		// added by CyberFabrik aka CyberTiger 08 Sep 2008 - end
		$this->setMessage( JText::sprintf( 'Items removed', $n ) );

	}

	/**
	 * called when form groups saved and record in database is true.
	 * Will either call methods to create or alter existing database table
	 * @return boolean false if not saved
	 */

	function updatedatabase()
	{
		// Check for request forgeries
		JRequest::checkToken() or die( 'Invalid Token' );
		$this->setRedirect( 'index.php?option=com_fabrik&c=form' );
		$db =& JFactory::getDBO();
		$cid		= JRequest::getVar( 'cid', null, 'post', 'array' );
		$formId = $cid[0];
		$model =& JModel::getInstance( 'Form', 'FabrikModel' );
		$model->setId( $formId );
		$form =& $model->getForm();

		//use this in case there is not table view linked to the form
		if ($form->record_in_database == 1) {
			//there is a table view linked to the form so lets load it
			$tableModel =& $model->getTableModel();
			$tableModel->loadFromFormId( $form->id );
			$dbExisits = $tableModel->databaseTableExists( );
			if (!$dbExisits) {
				$tableModel->createDBTable( $model );
			} else {
				$tableModel->ammendTable( $model );
			}
		}
		$this->setMessage( JText::_( 'DATABASE UPDATED' ) );
	}
}
?>