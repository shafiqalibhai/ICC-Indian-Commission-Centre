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
//jimport( 'joomla.application.component.helper' );
JModel::addIncludePath( COM_FABRIK_FRONTEND.DS.'models' );

require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'params.php' );
require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'html.php' );
require_once( COM_FABRIK_BASE.DS.'administrator'.DS.'components'.DS.'com_fabrik'.DS.'helpers'.DS.'adminhtml.php' );

/**
 * @package		Joomla
 * @subpackage	Fabrik
 */

class FabrikControllerElement extends JController
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
    $this->registerTask( 'removeFromTableview', 'addToTable' );
    $this->registerTask( 'addToTableView', 'addToTable' );
    $this->registerTask( 'orderDownElement', 'reorder' );
    $this->registerTask( 'orderUpElement', 'reorder' );
  }

  function reorder()
  {

    // Check for request forgeries
    JRequest::checkToken() or die( 'Invalid Token' );

    $this->setRedirect( 'index.php?option=com_fabrik&c=element' );

    $task		= JRequest::getCmd( 'task' );

    $direction 	= ($task == 'orderUpElement') ? -1 : 1;

    // Initialize variables
    $db		= & JFactory::getDBO();
    $cid	= JRequest::getVar( 'cid', array(), 'post', 'array' );
    if (isset( $cid[0] ))
    {
      $row = & JTable::getInstance('element', 'Table');
      $row->load( (int) $cid[0] );
      $where = " group_id = '" . $row->group_id . "'";
      $row->move($direction, $where );

    }
    $this->setMessage( JText::_( 'ITEMS REORDERED' ) );
  }

  /**
   * used when top save order button pressed
   *
   * @return unknown
   */
  function saveOrder()
  {

    // Check for request forgeries
    JRequest::checkToken() or die( 'Invalid Token' );

    // Initialize variables
    $db			= & JFactory::getDBO();

    $cid			= JRequest::getVar( 'cid', array(0), 'post', 'array' );
    $order		= JRequest::getVar( 'order', array (0), 'post', 'array' );
    $redirect	= JRequest::getVar( 'redirect', 0, 'post', 'int' );
    $rettask	= JRequest::getVar( 'returntask', '', 'post', 'cmd' );
    $total		= count($cid);
    $conditions	= array ();

    JArrayHelper::toInteger($cid, array(0));
    JArrayHelper::toInteger($order, array(0));

    // Instantiate an article table object
    $row = & JTable::getInstance('element', 'Table');

    // Update the ordering for items in the cid array
    for ($i = 0; $i < $total; $i ++)
    {
      $row->load( (int) $cid[$i] );
      if ($row->ordering != $order[$i]) {
        $row->ordering = $order[$i];
        if (!$row->store()) {
          JError::raiseError( 500, $db->getErrorMsg() );
          return false;
        }
        // remember to updateOrder this group
        $condition = 'group_id = '.(int) $row->group_id;
        $found = false;
        foreach ($conditions as $cond)
        if ($cond[1] == $condition) {
          $found = true;
          break;
        }
        if (!$found)
        $conditions[] = array ($row->id, $condition);
      }
    }

    // execute updateOrder for each group
    foreach ($conditions as $cond)
    {
      $row->load($cond[0]);
      $row->reorder($cond[1]);
    }

    $cache = & JFactory::getCache('com_fabrik');
    $cache->clean();

    $this->setRedirect( 'index.php?option=com_fabrik&c=element', JText::_('NEW ORDERING SAVED') );
  }

  /**
   * add/remove from table view
   * @param mixed array/int elements to add/remove to table
   * @param bol add = true/remove = false;
   */

  function addToTable()
  {

    // Check for request forgeries
    JRequest::checkToken() or die( 'Invalid Token' );

    $this->setRedirect( 'index.php?option=com_fabrik&c=element' );

    // Initialize variables
    $db			=& JFactory::getDBO();
    $user		=& JFactory::getUser();
    $cid		= JRequest::getVar( 'cid', array(), 'post', 'array' );
    $task		= JRequest::getCmd( 'task' );
    $publish	= ($task == 'addToTableView');
    $n			= count( $cid );

    if (empty( $cid )) {
      return JError::raiseWarning( 500, JText::_( 'NO ITEMS SELECTED' ) );
    }

    JArrayHelper::toInteger( $cid );
    $cids = implode( ',', $cid );

    $query = 'UPDATE #__fabrik_elements'
    . ' SET show_in_table_summary = ' . (int) $publish
    . ' WHERE id IN ( '. $cids.'  )'
    . ' AND ( checked_out = 0 OR ( checked_out = ' .(int) $user->get('id'). ' ) )'
    ;
    $db->setQuery( $query );
    if (!$db->query()) {
      return JError::raiseWarning( 500, $row->getError() );
    }
    $this->setMessage( JText::sprintf( $publish ? 'Items added to table view' : 'Items removed from table view', $n ) );
  }


  /**
   * Edit an element
   */

  function edit()
  {
    global $mainframe;
    $user		=& JFactory::getUser();
    $db 		=& JFactory::getDBO();
    $acl 		=& JFactory::getACL();
    $model	=& JModel::getInstance( 'element', 'FabrikModel' );
    if ($this->_task == 'edit') {
      $cid	= JRequest::getVar( 'cid', array(0), 'method', 'array' );
      $cid	= array((int) $cid[0]);
    } else {
      $cid	= array( 0 );
    }

    $model->setId( $cid[0] );
    $row =& $model->getElement();
    	
    if ($cid) {
      $row->checkout( $user->get( 'id' ) );
    }

    // get params definitions
    $params =& $model->getParams();
    require_once( JPATH_COMPONENT.DS.'views'.DS.'element.php' );

    $pluginManager =& JModel::getInstance( 'Pluginmanager', 'FabrikModel' );

    $db->setQuery( "SELECT count(*) FROM #__fabrik_groups" );
    $total 			= $db->loadResult( );
    if ($total == 0) {
      $mainframe->redirect( "index.php?option=com_fabrik&c=group&task=new", JText::_('PLEASE CREATE A GROUP BEFORE CREATING AN ELEMENT' ) );
    } else {
      $lists = array();

      if ($cid[0] != '0') {
        // set the publsih default to 1
        $row->state = '1';

        $aEls = array();
        $aGroups = array();

        $db->setQuery("SELECT form_id FROM #__fabrik_formgroup AS fg\n".
				"WHERE fg.group_id = $row->group_id");
        $formrow = $db->loadObject();

        if (is_null( $formrow )) {
          $aEls[] = $aGroups[] = JText::_('GROUP MUST BE IN A FORM');
        }

        $formModel = JModel::getInstance( 'form', 'FabrikModel' );
        $formModel->setId( $formrow->form_id );

        //get available element types
        $groups =& $formModel->getGroupsHiarachy();

        foreach ($groups as $groupModel) {
          $group =& $groupModel->getGroup();
          $o = new stdClass();
          $o->label = $group->name;
          $o->value = "fabrik_trigger_group_group".$group->id;
          $aGroups[] = $o;
          $elementModels =& $groupModel->getMyElements();
          foreach ($elementModels as $elementModel) {
            $o = new stdClass();
            $element =& $elementModel->getElement();
            $o->label = FabrikString::getShortDdLabel( $element->label );
            $o->value = "fabrik_trigger_element_".$elementModel->getFullName( false, true, false );
            $aEls[] = $o;
          }
        }
        asort( $aEls );
        $o = new StdClass();
        $o->groups = $aGroups;
        $o->elements = array_values($aEls);

        $lists['elements'] = $o;
      } else {
        $lists['elements'] = array( JText::_( 'AVAILABLE ONCE SAVED' ));
      }

      $pluginManager->getPlugInGroup( 'validationrule' );
      $pluginManager->loadPlugInGroup( 'element' );

      $j =& new JRegistry();
      $lists['jsActions'] 		= $model->getJSActions();
      //merge the js attribs back into the array
      foreach ($lists['jsActions'] as $js) {
        $j->loadINI( $js->attribs );
        $a = $j->toArray();
        foreach ($a as $k=>$v) {
          $js->$k = $v;
        }
        unset( $js->attribs );
      }

      $no_html	=  JRequest::getBool( 'no_html', 0 );

      // Create the form
      $form = new JParameter( '', JPATH_COMPONENT.DS.'models'.DS.'element.xml' );
      $form->bind( $row );
      $form->loadINI( $row->attribs );

      if ($row->parent_id == 0 || $row->parent_id == '') {
        $lists['parent'] = 0;
      } else {
        $sql = "SELECT * FROM #__fabrik_elements WHERE id = '$row->parent_id'";
        $db->setQuery( $sql );
        $parent = $db->loadObject();
        $lists['parent'] = $parent;
      }
      if (is_null( $row->parent_id )) {
        $row->parent_id = 0;
      }
      if ($no_html != 1) {
        FabrikViewElement::edit( $row, $pluginManager, $lists, $params, $form );
      }
    }
  }

  /**
   * when you go from a child to parent element, check in child before redirect
   */

  function parentredirect()
  {
    $id 						= JRequest::getInt( 'id', 0, 'post' );
    $pluginManager	=& JModel::getInstance( 'Pluginmanager', 'FabrikModel' );
    $className 			= JRequest::getVar( 'plugin', 'fabrikfield', 'post' );
    $elementModel 	= $pluginManager->getPlugIn( $className, 'element' );
    $elementModel->setId( $id );
    $row =& $elementModel->getElement();
    $row->checkin( );
    $to = JRequest::getInt('redirectto');
    $this->_task = 'edit';
    JRequest::setVar('cid', array($to));
    $this->edit();
  }

  /**
   * cancel editing
   */

  function cancel()
  {
    JRequest::checkToken() or die( 'Invalid Token' );
    $row 		=& JTable::getInstance('element', 'Table');
    $id 		= JRequest::getInt( 'id', 0, 'post' );
    $row->load($id);
    $row->checkin();
    $this->setRedirect( 'index.php?option=com_fabrik&c=element' );
  }

  /**
   * make the redirect link when you save the form
   * also sets the controller redirct to that link
   *
   * @access private
   * @param string $task
   * @param int $id
   */

  function _setSaveRedirect($task, $id)
  {
    switch ($task)
    {
      case 'apply':
        $link = 'index.php?option=com_fabrik&c=element&task=edit&cid[]='. $id ;
        break;

      case 'save':
      default:
        $link = 'index.php?option=com_fabrik&c=element';
        break;
    }
    $this->setRedirect( $link );
  }

  /**
   * Save a connection
   */

  function save()
  {
    // Check for request forgeries
    JRequest::checkToken() or die( 'Invalid Token' );
    jimport('joomla.utilities.date');

    $user	  				= &JFactory::getUser();
    $db 						=& JFactory::getDBO();
    $pluginManager	=& JModel::getInstance( 'Pluginmanager', 'FabrikModel' );

    $task = JRequest::getCmd( 'task' );
    $id 					= JRequest::getInt( 'id', 0, 'post' );
    $details			= JRequest::getVar( 'details', array(), 'post', 'array' );
    $className 		= $details['plugin'];
    $elementModel = $pluginManager->getPlugIn( $className, 'element' );
    $elementModel->setId( $id );
    $row =& $elementModel->getElement();
    echo "<pre>";//print_r($row);
    $this->_setSaveRedirect( $task, $row->id );

    $name = JRequest::getVar( 'name', '', 'post', 'CMD' );

    if (FabrikWorker::isReserved( $name )) {
      return JError::raiseWarning( 500, JText::_( 'SORRY THIS NAME IS RESERVED FOR FABRIK') );
    }

    
     $tableModel =& $elementModel->getTableModel();
     
     //are we updating the name of the primary key element?
     if ($row->name === str_replace('`', '', $tableModel->_shortKey() )) {
      if ($name !== $row->name) {
        //yes we are so update the table
       $table =& $tableModel->getTable();
       $table->db_primary_key = str_replace( $row->name, $name, $table->db_primary_key );
       $table->store();
      }
     }
     //test for duplicate names
     //unlinking produces this error
    if (!JRequest::getVar( 'unlink', false )) {
      
      $row->group_id = (int)$details['group_id'];
      
      
      $db->setQuery( "SELECT t.id FROM `jos_fabrik_joins` AS j ".
			"\n INNER JOIN jos_fabrik_tables AS t ".
			"\n ON j.table_join = t.db_table_name ".
			"\n WHERE group_id = $row->group_id AND element_id = 0" );
      $joinTblId = (int)$db->loadResult();
      if ($joinTblId === 0) {
	      if ($tableModel->fieldExists( JRequest::getVar( 'name' ), array( $id ) )) {
	        return JError::raiseWarning( 500, JText::_( 'SORRY THIS NAME IS ALREADY IN USE') );
	      }
      } else {
        $jointableModel =& JModel::getInstance( 'table', 'fabrikModel' );
        $jointableModel->setId( $joinTblId );
        $joinEls = $jointableModel->getElements();
        $ignore = array( $id );
        foreach ($joinEls as $joinEl) {
          if ($joinEl->getElement()->name == JRequest::getVar( 'name' )) {
            $ignore[] = $joinEl->getElement()->id;
          }
        }
        
        if ($jointableModel->fieldExists( JRequest::getVar( 'name' ), $ignore )) {
          JError::raiseNotice( 500, JText::_( 'SORRY THIS NAME IS ALREADY IN USE') );
	      }
      }
    }
    //end  duplicate name test
    
    $origGroupId = $row->group_id;

    // $$$ hugh - added maskbits value, otherwise HTML is stripped.
    // we may want to be intelligent about how we set this, but for now
    // just use mask of 4, which allows "safe" HTML.
    $maskbits = 4;
    $post	= JRequest::get( 'post', $maskbits );

    $post['name'] = $name;
    $ar 	= array( 'state', 'use_in_page_title', 'show_in_table_summary', 'link_to_detail', 'can_order', 'filter_exact_match' );
    foreach ($ar as $a) {
      if (!array_key_exists( $a, $post )) {
        $post[$a] = 0;
      }
    }

    // $$$ rob - test for change in element type
    //(eg if changing from db join to field we need to remove the join
    //entry from the #__fabrik_joins table
    $origElementModel =& JModel::getInstance( 'Element', 'FabrikModel' );
    $origElementModel->setId( $id );
    $origEl =& $origElementModel->getElement();
    $origElementPluginModel 	=& $pluginManager->getPlugIn( $origEl->plugin, 'element' );
    $origElementPluginModel->beforeSave();

    if (!$row->bind( $post )) {
      return JError::raiseWarning( 500, $row->getError() );
    }
    //unlink linked elements
    if (JRequest::getVar('unlink') == 'on') {
      $row->parent_id = 0;
    }
    //merge details params into element table fields

    if (!array_key_exists( 'eval', $details )) {
      $details['eval'] = 0;
    }
    if (!array_key_exists( 'hidden', $details )) {
      $details['hidden'] = 0;
    }
    $row->bind( $details );
    $datenow = new JDate();
    if ($row->id != 0) {
      $row->modified 		= $datenow->toFormat();
      $row->modified_by = $user->get('id');
    } else {
      $row->created 		= $datenow->toFormat();
      $row->created_by = $user->get('id');
      $row->created_by_alias = $user->get('username');
    }
    // 	save params
    $params = $elementModel->getParams();
    $row->attribs = $params->updateAttribsFromParams( JRequest::getVar( 'params', array(), 'post', 'array' ) );
    $cond = 'group_id = '.(int) $row->group_id;

    if ($row->id == 0) {
      $row->ordering = $row->getNextOrder( $cond );
    }
    //ensure that if we can't alter existing columns then any element name
    //changes are ignored
    $fbConfig 	=& JComponentHelper::getParams( 'com_fabrik' );
    $origName 	=  JRequest::getVar( 'name_orig', '', 'post', 'cmd' );

    // as tableModel doesnt actually seem to have a table loaded this always stopped you renaming
    // elements e.g. Copyofelement to element didnt work

    /*if ($origName != '' && $origName != $row->name) {
     if (!$tableModel->_canAlterFields()) {
     echo "cant alter files";
     $row->name = $origName;
     }
     }*/

    if (!$row->store()) {
      return JError::raiseWarning( 500, $row->getError() );
    }
    $row->checkin();


    //update table indexes
    $ftype = $elementModel->getFieldDescription();
    //int elements cant have a index size attrib
    $size = stristr( $ftype, 'int' ) ? '' : '10';
    if ($elementModel->getElement()->can_order) {
      $tableModel->addIndex( $row->name, 'order', 'INDEX', $size );
    } else {
      $tableModel->dropIndex( $row->name, 'order', 'INDEX', $size );
    }
    if ($row->filter_type != '') {
      $tableModel->addIndex( $row->name, 'filter', 'INDEX', $size );
    } else {
      $tableModel->dropIndex( $row->name, 'filter', 'INDEX', $size );
    }
    $row->reorder( $cond );
    $elementModel->setId( $row->id );


    //unset and reload the params with newly saved values
    unset($elementModel->_params);
    $elementModel->getParams();
    $elementModel->addToDBTable( $origName );
    $elementModel->updateJavascript();
    $elementModel->onSave();

    //update child elements
    $db->setQuery( "SELECT	id FROM #__fabrik_elements WHERE parent_id = $row->id" );
    $childids = $db->loadResultArray();
    $ignore = array( '_tbl', '_tbl_key', '_db', 'id', 'group_id', 'created', 'created_by', 'parent_id', 'ordering'  );
    foreach ($childids as $id) {
      $table =& JTable::getInstance( 'element', 'Table' );
      $table->load($id);
      foreach ($row as $key=>$val) {
        if (!in_array( $key, $ignore )) {
          // $$$rob - i can't replicate bug #138 but this should fix things anyway???
          if ($key == 'name') {
            $val = str_replace("`", "", $val);
          }
          $table->$key = $val;
        }
      }
      if (!$table->store( )) {
        return JError::raiseWarning( 500, $table->getError() );
      }
    }
    $this->setMessage( JText::_( 'ELEMENT SAVED' ) );
    $this->_setSaveRedirect( $task, $row->id );

    $cache = & JFactory::getCache( 'com_fabrik' );
    $cache->clean();

    //used for prefab
    return $elementModel;
  }

  /**
   * Publish a element
   */

  function publish()
  {
    // Check for request forgeries
    JRequest::checkToken() or die( 'Invalid Token' );

    $this->setRedirect( 'index.php?option=com_fabrik&c=element' );

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

    $query = 'UPDATE #__fabrik_elements'
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
   * Display the list of elements
   */

  function display()
  {
    global $mainframe;
    $db =& JFactory::getDBO();
    $context					= 'com_fabrik.element.list.';
    $filter_order			= $mainframe->getUserStateFromRequest( $context.'filter_order',		'filter_order',	'ordering',	'cmd' );
    $filter_order_Dir	= $mainframe->getUserStateFromRequest( $context.'filter_order_Dir',	'filter_order_Dir',	'',			'word' );
    $limit						= $mainframe->getUserStateFromRequest( $context.'limit', 'limit', $mainframe->getCfg('list_limit'), 'int' );
    $limitstart 			= $mainframe->getUserStateFromRequest( $context.'limitstart', 'limitstart', 0, 'int' );
    $filter_elementTypeId	= $mainframe->getUserStateFromRequest( $context."filter_elementTypeId", 'filter_elementTypeId', '' );
    $filter_groupId 		= $mainframe->getUserStateFromRequest( $context."filter_groupId", 'filter_groupId', 0, 'int' );
    $search 						= $mainframe->getUserStateFromRequest( $context."filter_elementName", 'filter_elementName', '' );
    $filter_showInTable	= $mainframe->getUserStateFromRequest( $context."filter_showInTable", 'filter_showInTable', '' );
    $filter_published 	= $mainframe->getUserStateFromRequest( $context."filter_published", 'filter_published', '' );

    $lists = array();
    $where = array();

    // table ordering
    $lists['order_Dir']	= $filter_order_Dir;
    $lists['order']		= $filter_order;


    // used by filter
    if ($filter_elementTypeId != '') {
      $where[] = " e.plugin = '$filter_elementTypeId' ";
    }
    //used by filter
    if ($filter_groupId >= 1) {
      $where[] = " e.group_id = '$filter_groupId' ";
    }
    // filter the element names
    if ($search != '') {
      $where[] = " e.name LIKE '%$search%' OR e.label LIKE '%$search%'";
    }
    // filter if its shown in table
    if ($filter_showInTable != '') {
      $where[] = " e.show_in_table_summary  = '$filter_showInTable'";
    }

    // filter if its published
    if ($filter_published != '') {
      $where[] = " e.state  = '$filter_published'";
    }
    $where		= count( $where ) ? ' WHERE ' . implode( ' AND ', $where ) : '';
    $orderby	= ' ORDER BY '. $filter_order .' '. $filter_order_Dir .', g.id,  e.ordering';

    // get the total number of records
    $db->setQuery( "SELECT COUNT(*) FROM #__fabrik_elements AS e ". $where );
    $total = $db->loadResult();
    echo $db->getErrorMsg();

    jimport('joomla.html.pagination');
    $pageNav 			= new JPagination( $total, $limitstart, $limit );

    $sql = "SELECT *,u.name AS editor, e.id AS id, " .
			"\n e.checked_out AS checked_out, #__fabrik_plugins.label AS pluginlabel,	 " .
			"\n e.checked_out_time AS checked_out_time, " .
			"\n e.state as state, g.name AS group_name, " .
			"\n e.name AS name, e.label AS label " .
			"\n FROM #__fabrik_elements AS e  " .
			"\n LEFT JOIN #__fabrik_groups AS g " .
			"\n ON e.group_id = g.id " .
			"\n LEFT JOIN #__fabrik_plugins  " .
			"\n ON e.plugin = #__fabrik_plugins.name " .
			"\n LEFT JOIN #__users AS u ON e.checked_out = u.id ".
			"\n $where $orderby ";

    $db->setQuery( $sql, $pageNav->limitstart, $pageNav->limit );
    $rows 				= $db->loadObjectList( );

    //element types
    $pluginManager	 	=& JModel::getInstance( 'Pluginmanager', 'FabrikModel' );
    $pluginManager->_group = 'element';
    $lists['elementId'] = $pluginManager->getElementTypeDd( $filter_elementTypeId, 'filter_elementTypeId', 'class="inputbox"  onchange="document.adminForm.submit( );"', '- ' . JText::_( 'ELEMENT TYPE' ) . ' -' );

    //groups into a drop down list
    $groupModel 			= JModel::getInstance( 'Group', 'FabrikModel' );
    $lists['groupId'] 	= $groupModel->makeDropDown( $filter_groupId,  '- ' . JText::_( 'GROUP' ) . ' -' );

    $yesNoList 			= FabrikHelperHTML::yesNoOptions( '', '- ' . JText::_( 'SHOW IN TABLE' ) . ' -');
    $lists['filter_showInTable'] = JHTML::_( 'select.genericlist',  $yesNoList, 'filter_showInTable', 'class="inputbox"  onchange="document.adminForm.submit( );"', 'value', 'text', $filter_showInTable );

    //filter on published list
    $yesNoList 			= FabrikHelperHTML::yesNoOptions( '', '- ' . JText::_( 'PUBLISHED' ) . ' -' );
    $lists['filter_published'] = JHTML::_( 'select.genericlist', $yesNoList, 'filter_published', 'class="inputbox"  onchange="document.adminForm.submit( );"', 'value', 'text', $filter_published );
    $lists['search'] = $search;

    require_once( JPATH_COMPONENT.DS.'views'.DS.'element.php' );
    FabrikViewElement::show( $rows, $pageNav, $lists );
  }

  /**
   * copy a connection
   * @param int connection id
   */

  function copy()
  {
    // Check for request forgeries
    JRequest::checkToken() or die( 'Invalid Token' );

    $this->setRedirect( 'index.php?option=com_fabrik&c=element' );

    $cid		= JRequest::getVar( 'cid', null, 'post', 'array' );
    $db			=& JFactory::getDBO();
    $rule		=& JTable::getInstance( 'element', 'Table' );
    $join		=& JTable::getInstance( 'join', 'Table' );

    $user		= &JFactory::getUser();
    $n			= count( $cid );

    if ($n > 0)
    {
      foreach ($cid as $id)
      {
        if ($rule->load( (int)$id ))
        {
          $rule->id				= 0;
          $rule->name	= 'Copy of ' . $rule->name;
          if (!$rule->store()) {
            return JError::raiseWarning( $rule->getError() );
          }
          //copy joins if neccesary
          $join->_tbl_key = 'element_id';
          $join->load($id);
          $join->_tbl_key = 'id';
          $join->id = 0;
          $join->element_id = $rule->id;
          if ($join->table_join != ''){
            $join->store();
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
   * ask if the user wants to delete the db column as well as the element
   */

  function checkRemove()
  {
    require_once( JPATH_COMPONENT.DS.'views'.DS.'element.php' );
    $db 	=& JFactory::getDBO();
    $cid	= JRequest::getVar( 'cid', array(), 'post', 'array' );
    $db->setQuery("SELECT id, label FROM #__fabrik_elements WHERE id = " . implode( ' OR id = ', $cid ));
    $elements =& $db->loadObjectList();
    FabrikViewElement::checkRemove( $elements );
  }
  /**
   * delete element
   */

  function remove()
  {
    // Check for request forgeries
    JRequest::checkToken() or die( 'Invalid Token' );

    $this->setRedirect( 'index.php?option=com_fabrik&c=element' );

    // Initialize variables
    $db		=& JFactory::getDBO();
    $cid	= JRequest::getVar( 'cid', array(), 'post', 'array' );
    $n		= count( $cid );
    JArrayHelper::toInteger( $cid );
    $drops = JRequest::getVar( 'drop' );

    //drop any selected columns
    foreach ($cid as $id) {
      if (array_key_exists( $id, $drops )) {
        if ($drops[$id][0] == '1') {
          $model	=& JModel::getInstance( 'element', 'FabrikModel' );
          $model->setId( $id );
          $element =& $model->getElement();
          $tableModel =& $model->getTableModel();
          $table =& $tableModel->getTable();
          $tableDb =& $tableModel->getDb();
          $tableDb->setQuery( "ALTER TABLE `$table->db_table_name` DROP `$element->name`" );
          $tableDb->query();
        }
      }
    }

    if ($n)
    {
      $query = 'DELETE FROM #__fabrik_elements'
      . ' WHERE id = ' . implode( ' OR id = ', $cid )
      ;
      $db->setQuery( $query );
      if (!$db->query()) {
        JError::raiseWarning( 500, $db->getError() );
      }
    }

    $this->setMessage( JText::sprintf( 'Items removed', $n ) );

  }
}
?>