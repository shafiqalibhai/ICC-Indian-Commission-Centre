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

class FabrikModelGroup extends JModel{

  /** @var object parameters */
  var $_params = null;

  /** @var int id of group to load */
  var $_id = null;

  /** @var object group table */
  var $_group = null;

  /** @var object form model */
  var $_form 		= null;

  /** @var object table model */
  var $_table 		= null;

  var $_joinModel = null;

  /** @var array of element plugins */
  var $elements = null;

  /** @var array of published element plugins */
  var $publishedElements = null;

  /** @var int how many times the group's data is repeated */
  var $_repeatTotal = null;

  /** @var array of form ids that the group is in (maximum of one value)*/
  var $_formsIamIn = null;

  /**
   * @param database A database connector object
   */

  function __construct()
  {
    parent::__construct();
  }

  /**
   * Method to set the group id
   *
   * @access	public
   * @param	int	group ID number
   */

  function setId($id)
  {
    // Set new group ID
    $this->_id		= $id;
  }

  function &getGroup()
  {
    if (is_null( $this->_group )) {
      JTable::addIncludePath( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_fabrik'.DS.'tables' );
      $this->_group =& JTable::getInstance( 'group', 'Table' );
      $this->_group->load( $this->_id );
    }
    return $this->_group;
  }

  /**
   * set the context in which the element occurs
   *
   * @param object form model
   * @param object table model
   */

  function setContext( &$formModel, &$tableModel )
  {
    $this->_form 		=& $formModel;
    $this->_table 	=& $tableModel;
  }

  /**
   * get an array of forms that the group is in
   * NOTE: now a group can only belong to one form
   * @return array form ids
   */

  function getFormsIamIn()
  {
    if (!isset( $this->_formsIamIn )) {
      $db =& JFactory::getDBO();
      $sql = "SELECT form_id FROM #__fabrik_formgroup WHERE group_id = '$this->_id'";
      $db->setQuery( $sql );
      $this->_formsIamIn = $db->loadResultArray();
    }
    return $this->_formsIamIn;
  }

  /**
   * returns array of elements in the group
   *
   * NOTE: pretty sure that ->elements will already be loaded
   * within $formModel->getGroupsHiarachy()
   *
   * @return array element objects (bound to element plugin)
   */

  function getMyElements()
  {
    //note dont use static vars here. As static vars are class-methods - so there is only
    //one static value for all generated group objects.
    if (!isset ($this->elements )) {
      $group =& $this->getGroup();
      $this->elements = array();
      $form =& $this->getForm();
      $pluginManager =& $form->getPluginManager();
      $allGroups =& $pluginManager->getFormPlugins( $this->_form );
      if (empty($this->elements)){
        //horrible hack for when saving group
        $this->elements =& $allGroups[$this->_id]->elements;
      }
    }
    return $this->elements;
  }

  function getForm()
  {
    if (!isset( $this->_form )) {
      $formids = $this->getFormsIamIn();
      $formid = $formids[0];
      $this->_form =& JModel::getInstance( 'Form', 'FabrikModel' );
      $this->_form->setId( $formid );
      $this->_form->getForm();
      $this->_form->getTableModel();
    }
    return $this->_form;
  }

  /**
   * get an array of published elements
   *
   * @return array published element objects
   */

  function getPublishedElements()
  {
    if (!isset( $this->publishedElements )) {
      $this->publishedElements = array();
      $elements =& $this->getMyElements();
      foreach ($elements as $elementModel) {
        $element =& $elementModel->getElement();
        if ($element->state == 1) {
          $this->publishedElements[] = $elementModel;
        }
      }
    }
    return $this->publishedElements;
  }
  /*
   * is the group a repeat group
   *
   * @return bol
   */

  function canRepeat()
  {
    $params =& $this->getParams();
    return $params->get( 'repeat_group_button' );
  }

  /**
   * is the group a join?
   *
   * @return bol
   */

  function isJoin()
  {
    $group =& $this->getGroup();
    return $group->is_join;
  }

  /**
   * get the group's associated join model
   *
   * @return object join model
   */

  function getJoinModel()
  {
    $group =& $this->getGroup();
    if (is_null( $this->_joinModel )) {
      $this->_joinModel =& JModel::getInstance( 'Join', 'FabrikModel' );
      $this->_joinModel->setId( $group->join_id );
      $this->_joinModel->getJoin();
    }
    return $this->_joinModel;
  }

  /**
   * load params
   *
   * @return object params
   */

  function &loadParams()
  {
    $this->_params =  new fabrikParams( $this->_group->attribs );
    return $this->_params;
  }

  /**
   * get group params
   *
   * @return object params
   */

  function &getParams()
  {
    if (!$this->_params) {
      $this->_params = $this->loadParams();
    }
    return $this->_params;
  }

  /**
   * creates a html dropdown off all groups
   * @param int selected group id
   * @return string group list
   */

  function makeDropDown( $selectedId = 0, $defaultlabel = '' )
  {
    if ($defaultlabel == '') {
      $defaultlabel = JText::_( 'PLEASE SELECT' );
    }
    $db =& JFactory::getDBO();
    $sql = "SELECT id AS value, name AS text FROM #__fabrik_groups ORDER BY name";
    $db->setQuery( $sql );
    $aTmp[] = JHTML::_('select.option', "-1", $defaultlabel );
    $groups = $db->loadObjectList();
    $groups = array_merge( $aTmp, $groups );
    $list = JHTML::_('select.genericlist',  $groups, 'filter_groupId', 'class="inputbox"  onchange="document.adminForm.submit( );"', 'value', 'text', $selectedId );
    return $list;
  }

  /**
   * make a group object to be used in the form view. Object contains 
   * group display properties
   * @param object form model
   * @return object group display properties
   */
  
  function getGroupProperties( &$formModel )
  {
    $group         = new stdClass();
    $groupTable    =& $this->getGroup();
    $params        =& $this->getParams();

    if (!isset( $this->_editable )) {
      $this->_editable = $formModel->_editable;
    }
    if ($this->_editable) {
	    //if all of the groups elements are not editable then set the group to uneditable
	    $elements =& $this->getPublishedElements();
	    $editable = false;
	    foreach ($elements as $element) {
	      if ($element->canUse()) {
	        $editable = true;
	      }
	    }
	    if (!$editable) {
	      $this->_editable = false;
	    }
    }
    $group->editable = $this->_editable;
    $group->canRepeat = $params->get( 'repeat_group_button', '0' );
    $addJs 				= str_replace( '"', "'",  $params->get( 'repeat_group_js_add' ) );
    $group->addJs = str_replace( array("\n", "\r"), "",  $addJs );
    $delJs 				= str_replace('"', "'",  $params->get( 'repeat_group_js_delete' ) );
    $group->delJs = str_replace( array("\n", "\r"), "",  $delJs );
    $showGroup 		= $params->def( 'repeat_group_show_first', '1' );

    $pages =& $formModel->getPages();

    $startpage = isset($formModel->sessionModel->last_page) ? $formModel->sessionModel->last_page: 0;
    if (is_array($pages[$startpage]) && !in_array($groupTable->id, $pages[$startpage]) || $showGroup == 0) {
      $groupTable->css .= ";display:none;";
    }
    $group->css 		= trim( str_replace( array ("<br />", "<br>"), "", $groupTable->css ) );
    $group->id 			= $groupTable->id;

    $group->title 	= $groupTable->label;
    if (stristr( $group->title , "{Add/Edit}" )) {
      $replace = ($formModel->_rowId == '') ? JText::_('ADD') : JText::_('EDIT');
      $group->title  = str_replace( "{Add/Edit}", $replace, $group->title );
    }

    $group->name		= $groupTable->name;
    $group->displaystate = ($group->canRepeat == 1 && $formModel->_editable) ? 1 : 0;
    return $group;
  }

}
?>