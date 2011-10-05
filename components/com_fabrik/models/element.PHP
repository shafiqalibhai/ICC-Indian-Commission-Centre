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

class FabrikModelElement extends JModel {

  /** @var int element id */
  var $_id = null;

  /** @var array javascript actions to attach to element */
  var $_jsActions = null;

  /** @var object params */
  var $_params = null;

  /** @var array validation objects associated with the element */
  var $_aValidations = null;

  /** @var array advanced joins */
  var $_advancedJoins = null;

  /** @var bol */
  var $_editable = null;

  /** @var bol */
  var $_is_upload = 0;

  /** @var bol */
  var $_recordInDatabase = 1;

  /** @var object to contain access rights **/
  var $_access = null;

  /** @var string seperator used in repeat groups */
  var $_groupSplitter = "//..*..//";

  /** @var string seperator used to split sub element data */
  var $_groupSplitter2 = "|-|";

  /**@var string validation error **/
  var $_validationErr = null;

  /** @var array stores possible element names to avoid repeat db queries **/
  var $_aFullNames = array();

  /** @var object group model*/
  var $_group = null;

  /** @var object form model*/
  var $_form = null;

  /** @var object table model*/
  var $_table = null;

  /** @var object JTable element object */
  var $_element = null;

  /** @var bol does the element have a label */
  var $hasLabel = true;

  /** @var bol does the element contain sub elements e.g checkboxes radiobuttons */
  var $hasSubElements = false;

  var $_imageExtensions = array('jpg', 'jpeg', 'gif', 'bmp', 'png');

  /** @var bol is the element in a detailed view? **/
  var $_inDetailedView = false;

  var $defaults = null;

  var $_HTMLids = null;

  /** @var bol is a join element */
  var $_isJoin = false;

  var $_inRepeatGroup = null;
  /**
   * Constructor
   */

  function __construct()
  {
    parent::__construct();
    $this->_is_upload = false;
    $this->_access = new stdClass();
  }

  /**
   * Method to set the element id
   *
   * @access	public
   * @param	int	element ID number
   */

  function setId($id )
  {
    // Set new element ID
    $this->_id		= $id;
  }

  /**
   * get the element table object
   *
   * @param bol default false - force load the element
   * @return object element table
   */

  function &getElement( $force = false )
  {
    if (!$this->_element || $force) {
      JTable::addIncludePath( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_fabrik'.DS.'tables' );
      $row = JTable::getInstance( 'element', 'Table' );
      $row->load( $this->_id );
      $this->_element =& $row;
    }
    return $this->_element;
  }

  /**
   * bind data to the _element variable - if possible we should run one query to get all the forms
   * element data and then iterrate over that, creating an element plugin for each row
   * and bind each record to that plugins _element. This is instead of using getElement() which
   * reloads in the element increasing the number of queries run
   *
   * @param mixed $row (object or assoc array)
   * @return object element table
   */

  function bindToElement( &$row )
  {
    if (!$this->_element) {
      JTable::addIncludePath( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_fabrik'.DS.'tables' );
      $this->_element =& JTable::getInstance( 'element', 'Table' );
    }
    $this->_element->bind( $row );
    return $this->_element;
  }

  /**
   * set the context in which the element occurs
   *
   * @param object group table
   * @param object form model
   * @param object table model
   */

  function setContext( $groupModel, $formModel, &$tableModel )
  {
    //dont assign these with &= as they already are when passed into the func
    $this->_group 	=& $groupModel;
    $this->_form 		=& $formModel;
    $this->_table 	=& $tableModel;
  }

  /**
   * get the element's fabrik table model
   *
   * @return object table model
   */
  function getTableModel()
  {
    if (is_null( $this->_table )) {
      $element =& $this->getElement();
      $db = JFactory::getDBO();
      $groupModel =& $this->getGroup();
      $db->setQuery("SELECT t.id FROM #__fabrik_formgroup AS g "
      ."\n LEFT JOIN #__fabrik_forms AS f "
      ."\n ON g.form_id = f.id "
      ."\n LEFT JOIN #__fabrik_tables AS t "
      ."\n ON t.form_id = f.id "
      ."\n WHERE group_id = {$groupModel->_id}");
      $tableid = $db->loadResult();
      $this->_table =& JModel::getInstance( 'table', 'FabrikModel' );
      $this->_table->setId( $tableid );
      $this->_table->getTable();
    }
    return $this->_table;
  }

  /**
   * load in the group model
   *
   * @param int group id
   * @return object group model
   */

  function &getGroup( $group_id = null )
  {

    if (is_null( $group_id )) {
      $element =& $this->getElement();
      $group_id = $element->group_id;
    }
    if (is_null( $this->_group) || $this->_group->_id != $group_id) {
      $model =& JModel::getInstance( 'Group', 'FabrikModel' );
      $model->setId( $group_id );
      $model->getGroup();
      $this->_group =& $model;
    }
    return $this->_group;
  }

  /**
   * get the elements form model
   *
   * @return object form model
   */

  function &getForm()
  {
    if (is_null( $this->_form)) {
      $tableModel = $this->getTableModel();
      $table =& $tableModel->getTable();
      $this->_form =& JModel::getInstance( 'form', 'FabrikModel' );
      $this->_form->setId( $table->form_id );
      $this->_form->getForm();
    }
    return $this->_form;
  }

  /**
   * shows the RAW table data - can be overwritten in plugin class
   * @param string data
   * @param object all the data in the tables current row
   * @return string formatted value
   */

  function renderRawTableData( $data, $oAllRowsData )
  {
    return $data;
  }

  /**
   * replace labels shown in table view with icons (if found)
   * @param string data
   * @return string data
   */

  function _replaceWithIcons( $data )
  {
    if ($data == '') {
      return $data;
    }
    $params =&$this->getParams();
    $folder = $params->get('icon_folder');
    if ($folder == -1 || $folder == '') {
      return $data;
    }

    foreach ($this->_imageExtensions as $ex) {
      $f = JPath::clean($folder . DS . $data . '.' . $ex);
      if (JFile::exists( COM_FABRIK_BASE.DS.$f )) {
        $f = str_replace(DS, "/", $f);
        return  "<img src='$f' alt='$data' />";
      }
    }
    return $data;
  }

  /**
   * can be overwritten in the plugin class - see database join element for example
   * @param array
   * @param array
   * @param string table name
   */

  function getAsField_html( &$aFields, &$aAsFields, $dbtable )
  {
    $tableModel =& $this->_table;
    $table 		=& $tableModel->getTable();
    $fullElName = "`$dbtable" . "___" . $this->_element->name . "`";
    $str 				= "`$dbtable`.". "`".$this->_element->name."` AS $fullElName" ;
    if ($table->db_primary_key == $fullElName) {
      array_unshift( $aFields, $fullElName );
      array_unshift( $aAsFields, $fullElName );
    } else {
      $aFields[] 	= $str;
      $aAsFields[] =  $fullElName;

      $fullElName = "`$dbtable" . "___" . $this->_element->name . "_raw`";
      $str 				= "`$dbtable`.". "`".$this->_element->name."` AS $fullElName" ;
      $aFields[] 	= $str;
      $aAsFields[] =  $fullElName;
    }
  }

  /**
   * check user can view the read only element & view in table view
   * @return bol can view or not
   */

  function canView()
  {
    $params =& $this->getParams();
    $element =& $this->getElement();
    if (!is_object( $this->_access ) || !array_key_exists( 'view', $this->_access )) {
      $this->_access->view = FabrikWorker::getACL( $params->get( 'view_access' ), 'viewElement' . $element->name );
    }
    return $this->_access->view;
  }

  /**
   * check user can view the active element
   * @return bol can view or not
   */

  function canUse()
  {
    $element =& $this->getElement();
    if (!is_object($this->_access) || !array_key_exists( 'use', $this->_access )) {
      $this->_access->use = FabrikWorker::getACL( $element->access, 'useElement' . $element->name );
    }
    return $this->_access->use;
  }

  /**
   * Defines if the user can use the filter related to the element
   *
   * @return bol true if you can use
   */

  function canUseFilter()
  {
    $params =& $this->getParams();
    $element =& $this->getElement();
    if (!is_object($this->_access) || !array_key_exists( 'filter', $this->_access )) {
      $this->_access->filter = FabrikWorker::getACL( $params->get( 'filter_access' ), 'filterElement' . $element->name );
    }
    return $this->_access->filter;
  }

  /* overwritten in add on classes */

  function setIsRecordedInDatabase()
  {
    return true;
  }

  /** overwrite in plugin **/

  function validate( $data, $repeatCounter = 0 )
  {
    return true;
  }


  /** overwrite in plugin
   * @param array data
   * @param object table model
   */

  function mergeTableData( &$data, &$tableModel )
  {

  }


  function getValidationErr()
  {
    return $this->_validationErr;
  }

  /**
   * can be overwritten by plugin class
   *
   * Examples of where this would be overwritten include drop downs whos "please select" value might be "-1"
   * @param string data posted from form to check
   * @param int repeat group counter
   * @return bol if data is considered empty then returns true
   */

  function dataConsideredEmpty( $data, $repeatCounter )
  {
    if ($data == '') {
      return true;
    }
    return false;
  }

  /**
   * can be overwritten by plugin class
   *
   * Examples of where this would be overwritten include timedate element with time field enabled
   * @param int repeat group counter
   * @return array html ids to watch for validation
   */

  function getValidationWatchElements( $repeatCounter )
  {
    $id 			= $this->getHTMLId( $repeatCounter );
    $ar = array(
			'id' 			=> $id,
			'triggerEvent' => 'blur'
			);
			return array( $ar );
  }

  /**
   *  can be overwritten in add on classes
   * @param mixed thie elements posted form data
   * @param array posted form data
   */

  function storeDatabaseFormat( $val, $data )
  {
    if (is_array( $val )) {
      $val = implode( $this->_groupSplitter, $val );
    }
    return $val;
  }

  /**
   * can be overwritten in add on classes
   * @param array data
   * @param string table column heading
   * @return array data
   */

  function prepareCSVData( $data, $key )
  {
    return $data;
  }

  /**
   * can be overwritten in plugin class
   * determines if the data in the form element is used when updating a record
   * @param mixed element forrm data
   * @return bol - true if ignored on update, default = false
   */

  function ignoreOnUpdate( $val = null )
  {
    return false;
   /* $params =& $this->getParams();
    if ($params->get( 'password', 0 )) {
      return true;
    } else {
      return false;
    }*/
  }

  /**
   * can be overwritten in plugin class
   * determines if the element can contain data used in sending receipts, e.g. fabrikfield returns true
   */

  function isReceiptElement()
  {
    return false;
  }

  /**
   * can be overwritten in adddon class
   *
   * checks the posted form data against elements INTERNAL validataion rule - e.g. file upload size / type
   * @param array existing errors
   * @param object group model
   * @param object form model
   * @param array posted data
   * @return array updated errors
   */

  function validateData( $aErrors, &$groupModel, &$formModel, $data )
  {
    return $aErrors;
  }


  /**
   * can be overwritten by plugin class
   * determines the label used for the browser title
   * in the form/detail views
   * @param array data
   * @param int when repeating joinded groups we need to know what part of the array to access
   * @param array options
   * @return string default value
   */

  function getTitlePart( $data, $repeatCounter = 0, $opts = array() )
  {
    return $this->getDefaultValue( $data, $repeatCounter, $opts );
  }

  /**
   * can be overwritten by plugin class
   * determines the value for the element in the form view
   * $$$ROB - TESTING POINTING getValue() to here
   * $$$ROB - seems ok to poing getValue() here
   * @TODO: replace all calls to getDefaultValue with getValue();
   * @param array data
   * @param int when repeating joinded groups we need to know what part of the array to access
   * @param array options
   * @return string default value
   */

  function getDefaultValue( $data, $repeatCounter = 0, $opts = array() )
  {
    if (is_null( $this->defaults )) {
      $this->defaults = array();
    }
    if (!array_key_exists( $repeatCounter, $this->defaults )) {
      $groupModel =& $this->getGroup();
      $group			=& $groupModel->getGroup();
      $joinid			= $group->join_id;
      $formModel 	=& $this->_form;
      $element		=& $this->getElement();
      $tableModel =& $this->getTableModel();
      $default	 	=& $element->default;
      $table 			=& $tableModel->getTable();

      if ($element->eval == "1") {
        $default = @eval( stripslashes( $default ) );
      }

      $name = $this->getFullName( false, true, false );
      $rawname = $name . "_raw";

      if ($groupModel->isJoin()) {
        if ($groupModel->canRepeat()) {
          if (array_key_exists( 'join', $data ) && array_key_exists( $joinid, $data['join']) && is_array($data['join'][$joinid]) &&  array_key_exists( $name, $data['join'][$joinid] ) && array_key_exists( $repeatCounter, $data['join'][$joinid][$name] )) {
            $default = $data['join'][$joinid][$name][$repeatCounter];
          }else{
            if (array_key_exists( 'join', $data ) && array_key_exists( $joinid, $data['join']) && is_array($data['join'][$joinid]) &&  array_key_exists( $name, $data['join'][$joinid] ) && array_key_exists( $repeatCounter, $data['join'][$joinid][$name] )) {
              $default = $data['join'][$joinid][$name][$repeatCounter];
            }
          }
        } else {
          if (array_key_exists( 'join', $data ) && array_key_exists( $joinid, $data['join']) && is_array($data['join'][$joinid]) && array_key_exists( $name, $data['join'][$joinid] )) {
            $default = $data['join'][$joinid][$name];
          } else {
            if (array_key_exists( 'join', $data ) && array_key_exists( $joinid, $data['join']) && is_array($data['join'][$joinid]) && array_key_exists( $rawname, $data['join'][$joinid] )) {
              $default = $data['join'][$joinid][$rawname];
            }
          }
        }
      } else {
        if ($groupModel->canRepeat()) {
          //repeat group NO join
          $thisname = $name;
          if (!array_key_exists( $name, $data )) {
            $thisname = $rawname;
          }
          if (array_key_exists( $thisname, $data )) {
            if (is_array( $data[$thisname] )) {
              //occurs on form submission for fields at least
              $a = $data[$thisname];
            } else {
              //occurs when getting from the db
              $a = 	explode( $this->_groupSplitter, $data[$thisname] );
            }
            if (array_key_exists( $repeatCounter, $a )) {
              $default = $a[$repeatCounter];
            }
          }

        } else {
          if (array_key_exists( $name, $data )) {
            $default = $data[$name];
          }else{
            if (array_key_exists( $rawname, $data )) {
              $default = $data[$rawname];
            }
          }
        }
      }

      if (is_array( $default )) {
        $default = implode( ',', $default );
      }
      $element->default = $default;
      //stops this getting called from form validation code as it messes up repeated/join group validations
      if (array_key_exists('runplugins', $opts) && $opts['runplugins'] == 1){
        $formModel->getPluginManager()->runPlugins( 'onGetElementDefault', $formModel, 'form', $this );
      }
      $this->defaults[$repeatCounter] = $element->default ;
    }
    return $this->defaults[$repeatCounter];
  }

  /**
   * can be overwritten by plugin class
   * determines the value for the element in the form view
   * @param array data
   * @param int when repeating joinded groups we need to know what part of the array to access
   * @param array options
   */

  function getValue( $data, $repeatCounter = 0, $opts = array() )
  {
    return $this->getDefaultValue( $data, $repeatCounter, $opts );
  }

  /**
   * is the element hidden or not - if not set then return false
   *
   * @return bol
   */

  function isHidden()
  {
    $element =& $this->getElement();
    return ($element->hidden == true) ? true : false;
  }

  /**
   * can be overwritten in the plugin class
   * @param object form
   */

  function getLabel()
  {
    $bLabel = $this->get('hasLabel');

    global $mosConfig_lang, $mosConfig_mbf_content, $Itemid;
    $element =& $this->getElement();
    $elementHTMLId = $this->getHTMLId();
    if ($element->hidden) {
      return '';
    }
    $task = JRequest::getVar( 'task', '', 'default' );
    $view = JRequest::getVar( 'view', '', 'form');
    if ($view == 'form' && ! ( $this->canUse() || $this->canView() )) {
      return '';
    }
    $params =& $this->getParams();
    $elementid = "fb_el_" . $elementHTMLId;
    $this->_form->loadValidationRuleClasses();
    $str = '';

    if ($this->canView()) {
      $str .= "<div class=\"fabrikLabel";
      $validations =& $this->getValidations();
      if ($this->_editable) {
        foreach ($validations as $validation) {
          $vid = $validation->_pluginName;
          if (array_key_exists( $vid, $this->_form->_validationRuleClasses )) {
            if ($this->_form->_validationRuleClasses[$vid] != '') {
              $str .= " " . $this->_form->_validationRuleClasses[$vid];
            }
          }
        }
      }
      $str .= "\" id=\"$elementid" . "_text\">";
      if ($bLabel) {
        $str .= "<label for=\"$elementHTMLId\">";
      }

      $rollOver = JText::_($params->get( 'hover_text_title' )) . "::" . JText::_( $params->get( 'rollover' ));
      $rollOver = htmlspecialchars( $rollOver, ENT_QUOTES );
      $str .= ($rollOver != '::') ? "<span class='hasTip' title='$rollOver'>{$element->label}</span>" : $element->label;
      if ($bLabel) {
        $str .= "</label>";
      }
      $str .= "</div>\n";
    }
    return $str;
  }

  /**
   * used for the name of the filter fields
   * For element this is an alias of getFullName()
   * Overridden currently only in databasejoin class
   *
   * @param bol $includeJoinString
   * @param bol $useStep
   * @param bol $incRepeatGroup
   * @return string element filter name
   */

  function getFilterFullName( $includeJoinString = true, $useStep = true, $incRepeatGroup = true )
  {
    return $this->getFullName( false, true, false );
  }

  /**
   * refractored from group class - can be overwritten by plugins
   * If already run then stored value returned
   * @param bol add join[joinid][] to element name (default true)
   * @param bol concat name with form's step element (true) or with '.' (false) default true
   * @param bol include "[]" at the end of the name (used for repeat group elements) default true
   */

  function getFullName( $includeJoinString = true, $useStep = true, $incRepeatGroup = true )
  {
    $db					=& JFactory::getDBO();
    $groupModel =& $this->getGroup();
    $formModel 	=& $this->getForm();
    $tableModel =& $this->getTableModel();
    $element 		=& $this->getElement();

    $key = $element->name . $groupModel->_id . "_" . $formModel->_id . "_" .$includeJoinString . "_" . $useStep . "_" . $incRepeatGroup;
    if (isset( $this->_aFullNames[$key] )) {
      return $this->_aFullNames[$key];
    }
    $table =& $tableModel->getTable();

    $defaultVal = '';
    $thisStep = ($useStep) ? $formModel->_joinTableElementStep : '.';
    $group =& $groupModel->getGroup();
    if ($groupModel->isJoin()) {
      $joinModel =& $groupModel->getJoinModel();
      $join =& $joinModel->getJoin();
      if ($includeJoinString) {
        $fullName = 'join[' . $join->id . '][' . $join->table_join . $thisStep . $element->name . ']';
      } else {
        $fullName =  $join->table_join . $thisStep . $element->name ;
      }
    } else {
      $table =& $tableModel->getTable( true );
      $fullName = $table->db_table_name . $thisStep . $element->name;
    }
    if ($groupModel->canRepeat() == 1 && $incRepeatGroup) {
      $fullName .=  "[]";
    }

    $this->_aFullNames[$key] = $fullName;
    return $fullName;
  }


  /**
   *  - can be overwritten by plugins
   * @param bol add join[joinid][] to element name (default true)
   * @param bol concat name with form's step element (true) or with '.' (false) default true
   *
   */

  function getOrderbyFullName( $includeJoinString = true, $useStep = true )
  {
    return $this->getFullName( $includeJoinString , $useStep );
  }

  /**
   * helper function to draw hidden field, used by any plugin that requires to draw a hidden field
   * @param string hidden field name
   * @param string hidden field value
   * @param string hidden field id
   * @return string hidden field
   */

  function getHiddenField( $name, $value, $id = '' )
  {
    if ($id != '') {
      $id = "id=\"$id\"";
    }
    $str = "<input type=\"hidden\" name=\"$name\" $id value=\"$value\" />\n";
    return $str;
  }

  function check()
  {
    return true;
  }

  /**
   * copy a n element table row
   *
   * @param int $id
   * @param string $copytxt
   * @param int $groupid
   * @return mixed error or new row
   */
  function copyRow( $id, $copytxt = 'Copy of ', $groupid = null  )
  {
    global $mainframe;
    $rule		=& JTable::getInstance( 'element', 'Table' );
    if ($rule->load( (int)$id ))
    {
      $rule->id				= 0;
      $rule->label	= $copytxt . $rule->label;
      if (!is_null( $groupid )) {
        $rule->group_id = $groupid;
      }
      $date =& JFactory::getDate();
      $date->setOffset( $mainframe->getCfg('offset') );
      $rule->created = $date->toMySQL();
      $rule->attribs = $rule->attribs . "\nparent_linked=1";
      $rule->parent_id = $id;
      if (!$rule->store()) {
        return JError::raiseWarning( $rule->getError() );
      }
    }
    else {
      return JError::raiseWarning( 500, $rule->getError() );
    }
    return $rule;
  }

  /**
   * this was in the views display and _getElement code but seeing as its used
   * by multiple views its safer to have it here
   *
   * @return mixed - false if you shouldnt continue to render the element
   */
  
  function preRender( $c, &$groupModel, $model, $elCount )
  {
   
    if (!$this->canUse() && !$this->canView()) {
      return false;
    }
  		
		if (!$this->canUse()) {
			$this->_editable = false;
		} else {
		  $this->_editable = ($model->_editable) ? true : false;
		}
    //force reload?
    $this->_HTMLids = null;
   
    $elementTable 	=& $this->getElement( true );

    $element 				= new stdClass();
    $elHTMLName			= $this->getFullName( true, true );
    //if the element is in a join AND is the join's foreign key then we don't show the element

    if ($elementTable->name == $this->_foreignKey) {
      $element->label 	= '';
      $element->error 	= '';
      $this->_element->hidden = true;
    } else {
      //@TODO
      $element->error 	= $this->_getErrorMsg( $model->_arErrors, $c );
    }
  
    $element->plugin = $elementTable->plugin;
    $element->hidden = $this->isHidden();
    $element->id		= $this->getHTMLId( $c );
    $element->className = "fb_el_" . $element->id;
    $element->element 	=  $this->_getElement( $model->_data, $c, $groupModel );
    //getLabel needs to know if the element is editable
    if ($elementTable->name != $this->_foreignKey) {
	 		$l = $this->getLabel();
			$w = new FabrikWorker();
			$element->label 	= $w->parseMessageForPlaceHolder( $l, $_REQUEST );
    }
    $groupParams =& $groupModel->getParams();
    //style attribute for group columns
    $element->column = '';
    $colcount = (int) $groupParams->get('group_columns');
    if ($colcount > 1) {
      $w = floor((100- ($colcount * 6)) / $colcount) ;
      $element->column = ' style="margin-right:1%;float:left;width:'.$w.'%;';
      if ($elCount % $colcount == 0) {
        $element->column .= "clear:both;";
      }
      $element->column .= '" ';
    }

    $element->element_ro = $this->_getROElement( $model->_data, $c, $groupModel );
    if (array_key_exists($elHTMLName, $model->_data)) {
      $element->element_raw = $model->_data[$elHTMLName];
    }
    return $element;
  }
  
  /**
   * merge the rendered element into the views element storage arrays
   * @param object element to merget
   * @param array $aElements
   * @param array $namedData
   * @param array $aSubGroupElements
   */

  function stockResults( $element, &$aElements, &$namedData, &$aSubGroupElements )
  {
    $elHTMLName  = $this->getFullName( true, true );
    $aElements[$this->getElement()->name] = $element;
		$namedData[$elHTMLName] = $element;
		if ($elHTMLName){
			$aSubGroupElements[] = $element;
		}
  }
/**
	 * @access private
	 * @param array data
	 * @param int repeat group counter
	 */

	function _getElement( $data, $repeatCounter = 0, &$groupModel )
	{
	  
		if (!$this->canView() && !$this->canUse()) {
			return '';
		}

		//used for working out if the element should behave as if it was in a new form (joined grouped) even when editing a record
		$this->_inRepeatGroup  = $groupModel->canRepeat();
		$this->_inJoin         = $groupModel->isJoin();

		//$this->_shortHTMLName = $this->getFullName( false, true, false );
		$opts = array( 'runplugins'=>1 );
		$this->getDefaultValue( $data, $repeatCounter, $opts );
		

		if ($this->_editable) {
			return $this->render( $data, $repeatCounter );
		} else {
		  $htmlid = $this->getHTMLId();
			//$$$ rob even when not in ajax mode the element update() method may be called in which case we need the span 
			return "<span id='$htmlid'>" . $this->_getROElement( $data, $repeatCounter, $groupModel ) . "</span>"; //placeholder to be updated by ajax code
		}
	}
	
	/**
	 * @access private
	 * @param array data
	 * @param int repeat group counter
	 */

	function _getROElement( $data, $repeatCounter = 0 )
	{
	  $groupModel =& $this->getGroup();
		if (!$this->canView() && !$this->canUse()) {
			return '';
		}
		$this->_editable 	= false;
		return $this->render( $data, $repeatCounter );
	}
	
/**
	 * get any html errror messages for form element
	 * @param string parsed element name
	 * @param array error messages
	 * @param int group element type
	 * @param int repeat count
	 * @return string error messages
	 */

	function _getErrorMsg( &$arErrors, $repeatCount = 0 )
	{
	  $parsed_name			= $this->getFullName( true, true );
		$err_msg = '';
		// $$$ hugh - wow, that was a biatch!  Just found why repeated diddles aren't showing
		// validation failure message!
		//$parsed_name = rtrim( $parsed_name, "[]" );
		$parsed_name = FabrikString::rtrimword($parsed_name, "[]");
		if (isset( $arErrors[$parsed_name] )) {
			if (array_key_exists( $repeatCount, $arErrors[$parsed_name] )) {
				if (is_array( $arErrors[$parsed_name][$repeatCount] )) {
					$err_msg = implode( '<br />', $arErrors[$parsed_name][$repeatCount] );
				} else {
					$err_msg .= $arErrors[$parsed_name][$repeatCount];
				}
			}
		}
		return $err_msg;
	}

  /**
   * draws out the html form element - overwritten in plugin
   */

  function render()
  {
    return 'need to overwrite in element plugin class';
  }

  /**
   * get the id used in the html element
   * @param int repeat group counter
   * @return string
   */

  function getHTMLId( $repeatCounter = 0 )
  {
    if (!is_array( $this->_HTMLids )) {
      $this->_HTMLids = array();
    }
    if (!array_key_exists( $repeatCounter, $this->_HTMLids )) {
      $groupModel =& $this->getGroup();
      $tableModel =& $this->getTableModel();
      $table 			=& $tableModel->getTable();
      $groupTable =& $groupModel->getGroup();
      $element =& $this->getElement();
      $defaultVal = '';
      if ($groupModel->isJoin()) {
        $joinModel =& $groupModel->getJoinModel();
        $joinTable =& $joinModel->getJoin();
        $fullName = 'join___' . $joinTable->id . '___' . $joinTable->table_join . '___' . $element->name ;
      } else {
        $fullName = $table->db_table_name . '___' . $element->name;
      }
      //change the id for detailed view elements
      if ($this->_inDetailedView) {
        $fullName .= "_ro";
      }
      if ($repeatCounter > 0) {
        $fullName .= "_" . $repeatCounter;
      }
      $this->_HTMLids[$repeatCounter] =  $fullName;
    }
    return $this->_HTMLids[$repeatCounter];
  }

  /**
   * get the element html name
   * @param int repeat group counter
   * @return string
   */

  function getHTMLName( $repeatCounter = 0 )
  {
    $groupModel =& $this->getGroup();
    $table 			=& $this->_table->getTable();
    $group 			=& $groupModel->getGroup();
    $element 		=& $this->getElement();
    $defaultVal = '';
    if ($groupModel->isJoin()) {
      $joinModel 	=& $groupModel->getJoinModel();
      $joinTable 	=& $joinModel->getJoin();
      $fullName 	= 'join[' . $joinTable->id . '][' . $joinTable->table_join . '___' . $element->name . ']' ;
    } else {
      $fullName 	= $table->db_table_name . '___' . $element->name;
    }
    if ($groupModel->canRepeat()) {
      if ($this->hasSubElements) {
        $fullName .= "[$repeatCounter]";
      } else {
        $fullName .= "[]";
      }
    }
     
    if ($this->hasSubElements) {
      $fullName .= "[]";
    }

    //@TODO: check this - repeated elements do need to have something applied to thier
    // id based on their order in the repeated groups
     
    $this->_elementHTMLName =  $fullName;
    return $this->_elementHTMLName;
  }

  /**
   * overwirte the standard load function to load in validations and js
   */

  /**
   *	binds an array/hash to this object
   *	@param int $oid optional argument, if not specifed then the value of current key is used
   *	@return any result from the database operation
   */

  function load( $oid=null )
  {
    $k = $this->_tbl_key;

    if ($oid !== null) {
      $this->$k = $oid;
    }
    $oid = $this->$k;
    if ($oid === null) {
      return false;
    }
    $class_vars = get_class_vars(get_class($this));
    foreach ($class_vars as $name => $value) {
      if (($name != $k) and ($name != "_db") and ($name != "_tbl") and ($name != "_tbl_key")) {
        $this->$name = $value;
      }
    }
    $this->reset();

    $query = "SELECT #__fabrik_elements.*, "
    . "\n CONCAT( action , ' = \"' ,  code , '\"') AS js"
    . "\n FROM $this->_tbl"
    . "\n LEFT JOIN #__fabrik_jsactions ON"
    . "\n $this->_tbl.id"
    . "\n = #__fabrik_jsactions.element_id"
    . "\n WHERE $this->_tbl.$this->_tbl_key = '$oid'";
     
    $this->_db->setQuery( $query );
    return $this->_db->loadObject( $this );
  }

  /**
   * SHOULD USE THIS RATHER THAN loadParams() as nomicalture is more appropriiate
   * also loads _pluginParams for good measure
   * @return object default element params
   */

  function &getParams()
  {
    if (!isset( $this->_params )) {
      $this->_params =& $this->_loadParams();
      $this->getPluginParams();
    }
    return $this->_params;
  }

  /**
   * get specific plugin params (lazy loading)
   *
   * @return object plugin parameters
   */

  function &getPluginParams()
  {
    if (!isset( $this->_pluginParams )) {
      $this->_pluginParams = $this->_loadPluginParams();
    }
    return $this->_pluginParams;
  }

  /**
   * load element params
   * @access private - public call = loadParams()
   *
   * @return object element parameters
   */

  function &_loadParams()
  {
    $element =& $this->getElement();
    $p = &new fabrikParams( $this->_element->attribs, JPATH_SITE . '/administrator/components/com_fabrik/xml/element.xml' , 'component' );
    return $p;
  }

  function _loadPluginParams()
  {
    if (isset( $this->_xmlPath )) {
      $element =& $this->getElement();
      $pluginParams = &new fabrikParams( $element->attribs, $this->_xmlPath, 'fabrikplugin' );
      $pluginParams->bind( $element );
      return $pluginParams;
    }
    return false;
  }

  /**
   * loads in elements validation objects
   * @return array validation objects
   */

  function loadValidations()
  {
    $element =& $this->getElement();
    $params =& $this->getParams();
    $usedPlugins 		= $params->get( 'validation-plugin', '', '_default', 'array' );
    $pluginManager =& $this->getForm()->getPluginManager();
    $pluginManager->getPlugInGroup( 'validationrule' );
    $c = 0;
    $aValidations = array();
    foreach ($usedPlugins as $usedPlugin) {
      $oPlugin 		= $pluginManager->_plugIns['validationrule'][$usedPlugin];
      $oPlugin->_params 	=& new fabrikParams( $element->attribs, $oPlugin->_xmlPath, 'fabrikplugin' );
      $aValidations[] = $oPlugin;
      $c ++;
    }
    $this->_aValidations =& $aValidations;
    return $aValidations;
  }

  /**
   *
   */

  function getValidations()
  {
    if (is_null( $this->_aValidations )) {
      $this->loadValidations();
    }
    return $this->_aValidations;
  }

  /**
   * get javasscript actions
   *
   * @return array js actions
   */

  function getJSActions()
  {
    if (!isset( $this->_jsActions )) {
      $sql = "SELECT * FROM #__fabrik_jsactions WHERE element_id = '$this->_id'";
      $this->_db->setQuery( $sql );
      $this->_jsActions = $this->_db->loadObjectList();
    }
    return $this->_jsActions;
  }

  /**
   *create the js code to observe the elements js actions
   * @param array all javascript events for the form key'd on element id
   * @param string either form_ or _details
   * @return string js events
   */

  function getFormattedJSActions( $allJsActions, $jsControllerKey, $repeatCount )
  {
    $jsStr = '';
    $element =& $this->getElement();
    $form =& $this->_form->getForm();

    if (array_key_exists( $this->_id, $allJsActions )) {
      $fxadded = array();
      $elId = $this->getHTMLId( $repeatCount );
      foreach ($allJsActions[$this->_id] as $jsAct) {
        $js = addSlashes( $jsAct->code );
        $js = str_replace(array("\n", "\r"), "", $js);
        if ($jsAct->action != '' && $js !== '') {
          $jsStr .= $jsControllerKey.".dispatchEvent( '$element->plugin', '$elId', '$jsAct->action', '$js');\n";
        }

        //build wysiwyg code
        if ($jsAct->js_e_event != '') {
          if (!array_key_exists( $jsAct->js_e_trigger, $fxadded )) {
            $jsStr .= $jsControllerKey.".addElementFX('$jsAct->js_e_trigger');\n";
            $fxadded[$jsAct->js_e_trigger] = true;
          }
          $js = "if(this.getValue() $jsAct->js_e_condition '$jsAct->js_e_value'){";
          $js .= $jsControllerKey.".doElementFX('$jsAct->js_e_trigger', '$jsAct->js_e_event')";
          $js .= "}";
          $js = addSlashes( $js );
          $js = str_replace(array("\n", "\r"), "", $js);
          $jsStr .= $jsControllerKey.".dispatchEvent( '$element->plugin', '$elId', '$jsAct->action', '$js');\n";
        }
      }
    }
    return $jsStr;
  }

  /**
   * presumes that the group is in a form (NOT a table joins group)
   * if its a table join group then use getElementsJoinTables();
   * @return array tables that the element is in
   */

  function getElementsGroupTables()
  {
    $db =& JFactory::getDBO();
    $aTableNames = array( );
    $sql = "SELECT form_id FROM #__fabrik_formgroup WHERE group_id = '". $this->_element->group_id ."'";
    $db->setQuery( $sql );
    $aForms = $db->loadResultArray( );
    foreach ($aForms as $formId) {
      $sql = "SELECT * FROM #__fabrik_tables WHERE form_id = '$formId'";
      $db->setQuery( $sql );
      $aTables = $db->loadObjectList( );
      $aTableNames = array_merge( $aTableNames, $aTables );
    }
    return $aTableNames;
  }

  /**
   * presumes that the group is in a form (NOT a table joins group)
   * if its a table join group then use getElementsJoinTables();
   * @param string distinct fields to load from
   * @return array table objects that the element is in
   */

  function myTables( $distinct = '' )
  {
    $db =& JFactory::getDBO();
    $aTableNames = array( );
    $element = $this->getElement();
    $sql = "SELECT form_id FROM #__fabrik_formgroup WHERE group_id = '$element->group_id'";
    $db->setQuery( $sql );
    $aForms = $db->loadResultArray( );
    foreach ($aForms as $formId) {
      if ($distinct != '') {
        $sql = "select id, count($distinct) FROM #__fabrik_tables ".
				"WHERE form_id = '$formId' ".
				"GROUP BY $distinct HAVING count($distinct)>=1 ";
      } else {
        $sql = "SELECT id FROM #__fabrik_tables WHERE form_id = '$formId'";
      }
      $db->setQuery( $sql );
      $aTables = $db->loadObjectList( );
      foreach ($aTables as $o) {
        $model =& JModel::getInstance( 'Table', 'FabrikModel' );
        $model->setId( $o->id );
        $model->getTable();
        $aTableNames[] = $model;
      }
    }
    return $aTableNames;
  }

  /**
   * presumes that the group is NOT in a form (IE in a table joins group)
   * if its a in a form then use getElementsGroupTables();
   * @return array tables that the element is in
   */

  function getElementsJoinTables()
  {
    $db =& JFactory::getDBO();
    $sql = "SELECT table_join FROM #__fabrik_joins WHERE element_id = '$this->_id'";
    $db->setQuery( $sql );
    return $db->loadResultArray( );
  }

  /**
   * $$$ rob DEPRECIATED??
   */

  function getAdvancedJoinsObjs()
  {
    if (is_null( $this->_advancedJoins )) {
      $db =& JFactory::getDBO();
      $sql = "SELECT * FROM #__fabrik_joins WHERE element_id = '$this->_id' AND element_id != '0' ORDER BY id";
      $db->setQuery( $sql );
      $this->_advancedJoins = $db->loadObjectList();
    }
    return $this->_advancedJoins;
  }

  /**
   * used to get the default value for table filters when they are advanced element joins
   * @param array posted filter information
   * @return dafault value for filter
   */

  function getDefaultAdvancedFilterVal( $aFilter )
  {
    $this->getAdvancedJoinsObjs();
    foreach ($this->_advancedJoins as $oJoin) {
      $testKey = $oJoin->join_from_table . "." . $oJoin->table_join_key;
      if (isset( $aFilter[$testKey] )) {
        return $aFilter[$testKey]['filterVal'];
      }
    }
    return '';
  }

  /**
   * get the default value for the table filter
   * @param array exisiting table filter data
   */

  function getDefaultFilterVal()
  {
    $tableModel =& $this->getTableModel();
    $aFilter 		=& $tableModel->getFilterArray();
    $elName 		= $this->getFullName( false, true, false );
    $data = JRequest::get('request');
    $groupModel =& $this->getGroup();
    $group 			=& $groupModel->getGroup();
    $params 		=& $this->getParams();
    //$element =& $this->getElement();
    if ($group->is_join) {
      if (array_key_exists( 'join', $data ) && array_key_exists( $group->join_id, $data['join'] )) {
        $data = $data['join'][$group->join_id];
      }
    }
    $default 		= "";
    if (array_key_exists( $elName, $data )) {
      if (is_array( $data[$elName])) {
        $default = @$data[$elName]['value'];
      }
    }
    if ($default == '') {
      if (isset( $aFilter )) {
        if (isset($aFilter[$elName] )) {
          if (is_array( $aFilter[$elName] )) {
            if (array_key_exists( 'filterVal', $aFilter[$elName] )) {
              $default = $aFilter[$elName]['filterVal'];
            } else {
              $default = $aFilter[$elName]['value'];
            }
          } else {
            print_r($aFilter[$elName]);
          }
        } else {
          $k = $params->get('join_db_name'). "." . $params->get('join_val_column');
          if (@isset($aFilter[$key] )) {
            $default = $aFilter[$k]['filterVal'];;
          }
        }
      }
    }
    return $default;
  }

  /**
   * if the search value isnt what is stored in the database, but rather what the user
   * sees then switch from the search string to the db value here
   * overwritten in things like checkbox and radio plugins
   * @param string $filterVal
   * @return string
   */

  function prepareFilterVal( $filterVal )
  {
    return $filterVal;
  }

  /**
   * can be overwritten by plugin class
   * Get the sql for filtering the table data and the array of filter settings
   * @param array posted data for the element
   * @param array filters
   * @param string db col key name e.g. table.elname
   * @param string form key name e.g. table___elname
   * @return array filter
   */

  function getFilterConditionSQL( $val, $aFilter, $dbKey, $key )
  {
    $cond = '';
    /* if posted data comes from a module we want to strip out its table name
     and replace it with current table name
     not sure how to deal with this for joins ? */

    //TODO: this is a cadidate for caching
    $fromModule 		 = JRequest::getBool( 'fabrik_frommodule', 0 );

    //its its passed in the querystring as key=val
    if (!is_array( $val )) {
      $val = array('value'=> $val);
    }
    $element =& $this->getElement();
    $params =& $this->getParams();
    $filterType =  isset( $val['type']) ? $val['type'] : $element->filter_type;
    $filterVal = isset( $val['value'] )? $val['value'] : '';
    $filterVal = $this->prepareFilterVal( $filterVal );
    $filterVal = addSlashes(urldecode( $filterVal ));
    $filterExactMatch = isset( $val['match'] )? $val['match'] : $element->filter_exact_match;
    $fullWordsOnly = isset( $val['full_words_only'] )? $val['full_words_only'] : $params->get('full_words_only');
    $joinDbName = isset( $val['join_db_name']) ? $val['join_db_name'] : '';
    $joinKey = isset( $val['join_key_column']) ? $val['join_key_column'] : '';
    $joinVal = isset( $val['join_val_column']) ? $val['join_val_column'] : '';
    if ($filterVal == "" ) {
      return;
    }
    switch ($filterType)
    {
      case 'dropdown':
        if ($fromModule) {
          $aKeyParts = explode( '.', $key);
          $key = $this->db_table_name . '.' . $aKeyParts[1];
        }
        if (!is_array( $filterVal )) {
          $filterVal = urldecode($filterVal);
          if ( $filterExactMatch == '0' ){
            $cond = " $dbKey LIKE '%$filterVal%' ";
          } else {
            $cond = " $dbKey = '$filterVal' ";
          }
        } else {
          $cond = "( ";
          foreach ($filterVal as $k=>$v) {
            if (trim($v) == '') {
              unset($filterVal[$k]);
            }
          }
          if (count($filterVal) === 0) {
            $cond = '';
          } else {
            foreach ($filterVal as $fval) {
              $fVal = urldecode($fVal);
              if (trim( $fval ) != '') {
                if ($filterExactMatch == '0') {
                  $cond .= " $dbKey LIKE '%$fval%' OR ";
                } else {
                  if (trim( $fval ) == '_null_') {
                    $cond .= " $dbKey IS NULL OR ";
                  } else {
                    $cond .= " $dbKey = '$fval' OR ";
                  }
                }
              }
            }
             
            $cond = substr( $cond, 0, strlen($cond)-3 );
            $cond .= " ) ";
          }
        }

        if (array_key_exists( $key, $aFilter )) {
          $aFilter[$key][] = $aFilter[$key];
          $aFilter[$key][] = array('type'=>'dropdown', 'value'=>$filterVal , 'filterVal'=>$filterVal, 'sqlCond' =>$cond );
        } else {
          $aFilter[$key] = array('type'=>'dropdown', 'value'=>$filterVal , 'filterVal'=>$filterVal, 'sqlCond' =>$cond );
        }
        break;
      default:
      case "":
      case "field":
        if(is_array($filterVal)){
          $filterVal = $filterVal[0];
        }
        //$$$ rob test for strings such as " alert('hi') "
        $filterVal = addSlashes(urldecode( $filterVal ));
        $filterCondSQL = '';
        if ($joinDbName != '') {
          $filterCondSQL .= " LEFT JOIN $joinDbName ON `$joinDbName`.`$joinKey` = $dbKey ";
        }
        /* full_words_only
         all search for multiple fragments of text*/
        $aFilterVals = explode( "+", $filterVal );
        if ($fullWordsOnly == '1') {
          $cond = " $dbKey REGEXP \"[[:<:]]" . $filterVal . "[[:>:]]\"";
        } else {
          if ($filterExactMatch) {
            $cond = " $dbKey = '$filterVal'";
          } else {
            $cond = " $dbKey LIKE '%$filterVal%'";
          }
        }
        $aFilter[$key] = array('type'=>'field',
				'value'=>$filterVal,
				'filterVal'=>$filterVal,
				'full_words_only'=>$fullWordsOnly,
				'join_db_name' => $joinDbName,
				'join_db_key' => $joinKey,
				'join_val_column' => $joinVal,
				'prewritten_join' => $filterCondSQL,
				'sqlCond' =>$cond
        );

        break;
      case "search":
        if ($joinDbName != '') {
          $filterCondSQL .= " LEFT JOIN $joinDbName ON $joinDbName.$joinKey = " . $this->db_table_name . "." . $this->db_primary_key . " ";
        }
        $filterVal = urldecode($filterVal);
        $cond2 = $key . " " . str_replace( '\"', '"', $filterVal );
        $cond = $cond2;
        $aFilter[$key] = array('type'=>'search',
											   'value'=>$cond2, 
												'filterVal'=>$filterVal, 
												'full_words_only'=>$fullWordsOnly,
												'join_db_name' => $joinDbName,
												'join_db_key' => $joinKey
        , 'sqlCond' =>$cond
        );
        break;

      case "range":
        if ($filterVal[0] != '' & $filterVal[1] != '') {
          $cond = " $dbKey BETWEEN '" . $filterVal[0] . "' AND '" . $filterVal[1] . "'";
          $aFilter[$key] = array('type'=>'range',
				   'value'=>$filterVal, 
					'filterVal'=>$filterVal, 
					'full_words_only'=>$fullWordsOnly,
					'join_db_name' => $joinDbName,
					'join_db_key' => $joinKey
          , 'sqlCond' =>$cond
          );
        } else {
          return ;
        }
        break;
    }
    if (array_key_exists( $key, $aFilter )) {
      return $aFilter[$key];
    } else {
      return '';
    }
  }

  /**
   * can be overwritten by plugin class
   * Get the table filter for the element
   * @param object group model
   * @return string filter html
   */

  function getFilter()
  {
    global $mainframe;
    $tableModel  	=& $this->_table;
    $formModel		=& $tableModel->getForm();
    $dbElName		= $this->getFullName( false, false, false );
    if (!$formModel->hasElement( $dbElName )) {
      return '';
    }

    $groupModel		=& $this->getGroup();
    $table				=& $tableModel->getTable();
    $element			=& $this->getElement();
    $origTable 		= $table->db_table_name;
    $fabrikDb 		=& $tableModel->getDb();
    $params				=& $this->getParams();

    $js 					= "";
    $elName 		= $this->getFullName( false, true, false );

    /*
     $ids 				= $tableModel->getColumnData( $dbElName );
     //for ids that are text with apostrophes in
     for ($x=0; $x<count( $ids ); $x++) {
     $ids[$x] = addSlashes( $ids[$x] );
     }
     */
    $elLabel				= $element->label;
    $elExactMatch 	= $element->filter_exact_match;
    $v 				= $elName . "[value]";
    $t 				= $elName . "[type]";
    $e 				= $elName . "[match]";
    $fullword 		= $elName . "[full_words_only]";
    //corect default got
    $default = $this->getDefaultFilterVal();

    $aThisFilter = array();

    //filter the drop downs lists if the table_view_own_details option is on
    //other wise the lists contain data the user should not be able to see
    // note, this should now use the prefilter data to filter the list

    /* check if the elements group id is on of the table join groups if it is then we swap over the table name*/
    $fromTable = $origTable;
    $joinStr = $tableModel->_buildQueryJoin();

    foreach ($tableModel->_aJoins as $aJoin) {
      /** not sure why the group id key wasnt found - but put here to remove error **/
      if (array_key_exists( 'group_id', $aJoin )) {
         
        if ($aJoin->group_id == $element->group_id && $aJoin->element_id == 0) {
          $fromTable = $aJoin->table_join;
          $elName = str_replace( $origTable . '.', $fromTable . '.', $elName);
          $v = $fromTable . '___' . $element->name . "[value]";
          $t = $fromTable . '___' . $element->name . "[type]";
          $e = $fromTable . '___' . $element->name . "[match]";
          $fullword = $elName . "[full_words_only]";
        }
      }
    }
    /* elname should be in format table.key add quotes:*/
    $dbElName = explode( ".", $dbElName );
    $dbElName = "`" . $dbElName[0] . "`.`" . $dbElName[1] . "`";

    $sql = "SELECT DISTINCT( $dbElName ) AS elText, $dbElName AS elVal FROM `$origTable` $joinStr\n";
    $sql .= " WHERE TRIM($dbElName) <> '' GROUP BY elText ASC";

    $context = "com_fabrik.table" . $tableModel->_id . ".filter." . trim($elName);
    $default = $mainframe->getUserStateFromRequest( $context, trim($elName), $default );


    switch ( $element->filter_type )
    {
      case "range":
        $fabrikDb->setQuery( $sql );
        FabrikHelperHTML::debug( $fabrikDb->getQuery(), 'element getFilter' );
        $oDistinctData = $fabrikDb->loadObjectList( );
        $obj = new stdClass;
        $obj->elVal  = "";
        $obj->elText = JText::_( 'PLEASE SELECT' );
        $aThisFilter[] = $obj;
        if (is_array( $oDistinctData )) {
          $aThisFilter = array_merge( $aThisFilter, $oDistinctData );
        }

        $attribs = 'class="inputbox fabrik_filter" size="1" ';
        $default1 = (is_array($default)) ? $default['value'][0] : '';
        $return 	 = JText::_(' Between' ) . JHTML::_('select.genericlist', $aThisFilter , $v.'[]', $attribs, "elVal", 'elText', $default1, $element->name . "_filter_range_0" );
        $default1 = (is_array($default)) ? $default['value'][1] : '';
        $return 	 .= "<br /> " . JText::_( 'and' ) . ' ' . JHTML::_('select.genericlist', $aThisFilter , $v.'[]', $attribs, "elVal", 'elText', $default1 , $element->name . "_filter_range_1");
        break;

      case "dropdown":
        $fabrikDb->setQuery( $sql );
        FabrikHelperHTML::debug( $fabrikDb->getQuery(), 'element getFilter' );
        $oDistinctData = $fabrikDb->loadObjectList( );
        $obj = new stdClass;
        $obj->elVal  = "";
        $obj->elText = JText::_( 'PLEASE SELECT' );
        $aThisFilter[] = $obj;
        if (is_array( $oDistinctData )) {
          $aThisFilter = array_merge( $aThisFilter, $oDistinctData );
        }
        $return 	 = JHTML::_('select.genericlist',  $aThisFilter , $v, 'class="inputbox fabrik_filter" size="1" ' , "elVal", 'elText', $default );
        break;

      case "field":

        $default = ( is_array( $default ) && array_key_exists( 'value', $default) ) ? $default['value'] : '';
        if (get_magic_quotes_gpc()) {
          $default			= stripslashes( $default );
        }
        $default = htmlspecialchars( $default );
        $return = "<input type='text' name='$v' class=\"inputbox fabrik_filter\" value=\"$default\"  />";
        break;
    }
    $return .= "\n<input type='hidden' name='$t' value='$element->filter_type' />\n";
    $return .= "\n<input type='hidden' name='$e' value='$elExactMatch' />\n";
    $return .= "\n<input type='hidden' name='$fullword' value='" . $params->get('full_words_only', '0') . "' />\n";
    return $return;
  }

  /**
   * delete old javascript actions for the element
   * & add new javascript actions
   */

  function updateJavascript()
  {
    $db =& JFactory::getDBO();
    $db->setQuery( "DELETE FROM #__fabrik_jsactions WHERE element_id = '$this->_id'" );
    $db->query();
    $post	= JRequest::get( 'post' );
    if (isset( $post['js_action'] )) {
      if (is_array( $post['js_action'] )) {
        for ($c = 0; $c < count( $post['js_action'] ); $c ++) {
          $jsAction = $post['js_action'][$c];
          //new
          $attribs = "js_e_event=".$post['js_e_event'][$c]."\n";
          $attribs .= "js_e_trigger=".$post['js_e_trigger'][$c]."\n";
          $attribs .= "js_e_condition=".$post['js_e_condition'][$c]."\n";
          $attribs .= "js_e_value=".$post['js_e_value'][$c]."\n";
          if ($jsAction != '') {
            $code = $post['js_code'][$c];
            $code = str_replace( "}", "}\n", $code );
            $code = str_replace( '"', "'", $code );
            $sql = 'INSERT INTO #__fabrik_jsactions (element_id, action, code, attribs) VALUES ("'.$this->_id.'", "'.$jsAction.'", "'.$code.'", "'.$attribs.'")';
            $db->setQuery( $sql );
            $db->query( );
          }
        }
      }
    }
  }

  /**
   * when adding a new element this will ensure its added to all tables that the
   * elements group is associated with
   * @param string original column name leave null to ignore
   */

  function addToDBTable( $origColName = null )
  {
    $db =& JFactory::getDBO();
    $user	  = &JFactory::getUser();

    // don't bother if the element has no name as it will cause an sql error'
    if ($this->_element->name == '') {
      return;
    }

    $groupModel =& JModel::getInstance( 'Group', 'FabrikModel' );
    $groupModel->setId( $this->_element->group_id );
    $groupTable 	=& $groupModel->getGroup();

    $formTable 	=& JTable::getInstance( 'Form', 'Table' );
    $tableModel	=& JModel::getInstance( 'Table', 'FabrikModel' );
    $afFormIds 	= $groupModel->getFormsIamIn();
    if ($groupModel->isJoin()) {

      $joinModel =& $groupModel->getJoinModel();
      $joinTable =& $joinModel->getJoin();

      if ($joinTable->table_id  != 0) {
        $tableModel->setId( $joinTable->table_id );
        $table =& $tableModel->getTable();
        $table->db_table_name = $joinTable->table_join;
        $tableModel->alterStructure( $this, $origColName );
      }
    } else {

      if (is_array( $afFormIds )) {
        foreach ($afFormIds as $formId) {
          $formTable->load( $formId );
          if ($formTable->record_in_database) {
            $tableTable =& $tableModel->loadFromFormId( $formId );
            $tableModel->alterStructure( $this, $origColName );
          }
        }
      }
    }
  }


  function onSave()
  {
    //overridden in element plugin if needed
  }

  /**
   * DEPRECIATED??
   * a recursive method to return a list of all folders from a given parent directory
   * @param string parent directory
   * @return array child directories of parent directory
   */

  function recursive_listdir( $base ) {
    static $filelist = array( );
    static $dirlist = array( );
    if ( is_dir( $base ) ) {
      $dh = opendir( $base );
      if ( $dh != false ) {
        while ( false !== ( $dir = readdir( $dh ) ) ) {
          if ( is_dir( $base."/".$dir ) && $dir !== '.' && $dir !== '..' && strtolower( $dir ) !== 'cvs' ) {
            $subbase = $base."/".$dir;
            $dirlist[] = $subbase;
            $subdirlist = $this->recursive_listdir( $subbase );
          }
        }
        closedir( $dh );
      }
    }
    return $dirlist;
  }

  /**
   * states if the elemnt contains data which is recorded in the database
   * some elements (eg buttons) dont
   * @param array posted data
   */

  function recordInDatabase( $data = null )
  {
    return $this->_recordInDatabase;
  }

  /**
   * Internal function to recursive scan directories
   * @param string Path to scan
   * @param string root path of this folder
   * @param array  Value array of all existing folders
   * @param array  Value array of all existing images
   * @param bol make options out for the results
   */
  function readImages( $imagePath, $folderPath, &$folders, &$images, $aFolderFilter, $makeOptions = true ) {
    $imgFiles = $this->fabrikReadDirectory( $imagePath, '.', false, false, $aFolderFilter );
    foreach( $imgFiles as $file ) {
      $ff_ 	= $folderPath . $file .'/';
      $ff 	= $folderPath . $file;
      $i_f 	= $imagePath .'/'. $file;
      if ( is_dir( $i_f ) && $file != 'CVS' && $file != '.svn' ) {
        if( !in_array( $file, $aFolderFilter ) ){
          $folders[] = JHTML::_('select.option', $ff_ );
          $this->readImages( $i_f, $ff_, $folders, $images, $aFolderFilter );
        }
      } else if ( eregi( "bmp|gif|jpg|png", $file ) && is_file( $i_f ) ) {
        // leading / we don't need
        $imageFile = substr( $ff, 1 );
        if($makeOptions){
          $images[$folderPath][] = JHTML::_('select.option', $imageFile, $file );
        }else{
          $images[$folderPath][] = $file;
        }
      }
    }
  }


  /**
   * Utility function to read the files in a directory
   * @param string The file system path
   * @param string A filter for the names
   * @param boolean Recurse search into sub-directories
   * @param boolean True if to prepend the full path to the file name
   */

  function fabrikReadDirectory( $path, $filter='.', $recurse=false, $fullpath=false, $aFolderFilter=array(), $foldersOnly = false ) {
    $arr = array();
    if (!@is_dir( $path )) {
      return $arr;
    }
    $handle = opendir( $path );
    while( $file = readdir( $handle ) ){

      $dir = JPath::clean( $path.'/'.$file );
      $isDir = is_dir( $dir );
      if( ( $file != "." ) && ( $file != ".." ) ){

        if( preg_match( "/$filter/", $file ) ){

          if( ( $isDir && $foldersOnly ) || !$foldersOnly ){
            if( $fullpath ){
              $arr[] = trim( JPath::clean( $path.'/'.$file ) );
            } else {
              $arr[] = trim( $file );
            }
          }
        }
        $goDown = true;
        if( $recurse && $isDir ){
          foreach( $aFolderFilter as $sFolderFilter ){
            if( strstr( $dir, $sFolderFilter ) ){
              $goDown = false;
            }
          }

          if( $goDown ){
            $arr2 = $this->fabrikReadDirectory( $dir, $filter, $recurse, $fullpath,$aFolderFilter, $foldersOnly );
            $arrDiff = array_diff( $arr, $arr2 );
            $arr = array_merge( $arrDiff );
          }
        }
      }
    }
    closedir( $handle );
    asort( $arr );
    return $arr;
  }

  /**
   * used by elements with suboptions
   *
   * @param string value
   * @return string label
   */

  function _getLabelForValue( $v )
  {
    $element =& $this->getElement();
    $params =& $this->getParams();
    $values = explode( "|", $element->sub_values );
    $labels = explode( "|", $element->sub_labels );
    $key = array_search( $v, $values );
    // $$$ rob if we allow adding to the dropdown but not recording
    // then there will be no $key set to revert to the $val instead
    return ($key === false) ? $v : $labels[$key];
    //return $labels[$key];
  }

  /**
   * calculation: sum
   * can be overridden in element class
   * @param object table model
   * @return array
   */

  function sum( &$tableModel ){
    $db 				=& $tableModel->getDb();
    $joinSQL 		= $tableModel->_buildQueryJoin();
    $whereSQL 	= $tableModel->_buildQueryWhere();
    $params 		=& $this->getParams();
    $table 			=& $tableModel->getTable();
    $element 		=& $this->getElement();
    $splitSum		= $params->get( 'sum_split', '' );
    switch (strtoupper($this->getFieldDescription())) {
      case 'DATETIME':
        $v = "SEC_TO_TIME(SUM( TIME_TO_SEC($element->name)))";
        break;
      default:
        $v = "SUM($element->name)";
        break;
    }
    $sql = "SELECT $v AS value, 'calc' AS label FROM `$table->db_table_name` $joinSQL $whereSQL";
    $db->setQuery($sql);
    $results =  $db->loadObjectList('label');
    if ($splitSum != '') {
      $splitElementModel =& JModel::getInstance('element', 'FabrikModel');
      $splitElementModel->setId( $splitSum );
      //$pluginManager =& JModel::getInstance( 'Pluginmanager', 'FabrikModel' );
      $pluginManager =& $this->getForm()->getPluginManager();
      //@TODO: find way of loading in plugin as element rather than this two step process

      $splitElement = $splitElementModel->getElement();
      $splitName = "`$table->db_table_name`.`$splitElement->name`";
      $plugin = $pluginManager->getPlugIn( $splitElement->plugin, 'element' );
      $sql = "SELECT $v AS value, $splitName AS label FROM `$table->db_table_name` $joinSQL $whereSQL GROUP BY $splitName ";
      $db->setQuery($sql);
      $results2 =  $db->loadObjectList('label');
      foreach ($results2 as $key => $val) {
        if ($plugin->hasSubElements) {
          $newkey =  $splitElementModel->_getLabelForValue($key);
          $val->label = $newkey;
        }
        $results[$key] = $val;
      }
    }
    $res = '';
    $label = $params->get('element_alt_table_heading') == '' ? $element->label : $params->get('element_alt_table_heading');
    foreach ($results as &$o) {
      $o->elLabel = $label;
      $res .= ($o->label == 'calc') ?  $o->value . "<br />" : $o->label . ': ' . $o->value . "<br />";
    }
    return array( $res, $results );
  }

  /**
   * calculation: avarage
   * can be overridden in element class
   * @param object table model
   * @return string result
   */

  function avg( &$tableModel )
  {
    $db 				=& $tableModel->getDb();
    $joinSQL 		= $tableModel->_buildQueryJoin();
    $whereSQL 	= $tableModel->_buildQueryWhere();
    $params 		=& $this->getParams();
    $splitAvg		= $params->get( 'avg_split', '' );
    $table 			=& $tableModel->getTable();
    $element 		=& $this->getElement();
    $sql = "SELECT ROUND(AVG($element->name),2) AS value, 'calc' AS label FROM `$table->db_table_name` $joinSQL $whereSQL" ;
    $db->setQuery($sql);
    $results =  $db->loadObjectList('label');
    if ($splitAvg != '') {
      $splitElementModel =& JModel::getInstance('element', 'FabrikModel');
      $splitElementModel->setId( $splitAvg );
      //$pluginManager =& JModel::getInstance( 'Pluginmanager', 'FabrikModel' );
      $pluginManager =& $this->getForm()->getPluginManager();
      //@TODO: find way of loading in plugin as element rather than this two step process

      $splitElement = $splitElementModel->getElement();
      $splitName = "`$table->db_table_name`.`$splitElement->name`";
      $plugin = $pluginManager->getPlugIn( $splitElement->plugin, 'element' );
      $sql = "SELECT ROUND(AVG($element->name),2) AS value, $splitName AS label FROM `$table->db_table_name` $joinSQL $whereSQL GROUP BY $splitName " ;
      $db->setQuery($sql);
      $results2 =  $db->loadObjectList('label');
      foreach ($results2 as $key => $val) {
        if ($plugin->hasSubElements) {
          $newkey =  $splitElementModel->_getLabelForValue($key);
          $val->label = $newkey;
        }
        $results[$key] = $val;
      }
    }
    $res = '';
    $label = $params->get('element_alt_table_heading') == '' ? $element->label : $params->get('element_alt_table_heading');
    foreach ($results as $o) {
      $o->elLabel = $label;
      $res .= ($o->label == 'calc') ?  $o->value . "<br />" : $o->label . ': ' . $o->value . "<br />";
    }
    return array( $res, $results );
  }

  /**
   * calculation: median
   * can be overridden in element class
   * @param object table model
   * @return string result
   */

  function median( &$tableModel )
  {
    $db 				=& $tableModel->getDb();
    $table 			=& $tableModel->getTable();
    $element 		=& $this->getElement();
    $joinSQL 		= $tableModel->_buildQueryJoin();
    $whereSQL 	= $tableModel->_buildQueryWhere();
    $params 		=& $this->getParams();
    $split		= $params->get( 'median_split', '' );
    $res = '';

    $results = array();
    if ($split != '') {
      $splitElementModel =& JModel::getInstance('element', 'FabrikModel');
      $splitElementModel->setId( $split );
      //$pluginManager =& JModel::getInstance( 'Pluginmanager', 'FabrikModel' );
      $pluginManager =& $this->getForm()->getPluginManager();
      //@TODO: find way of loading in plugin as element rather than this two step process

      $splitElement = $splitElementModel->getElement();
      $splitName = "`$table->db_table_name`.`$splitElement->name`";
      $plugin = $pluginManager->getPlugIn( $splitElement->plugin, 'element' );

      $sql = "SELECT $element->name AS value, $splitName AS label FROM `$table->db_table_name` $joinSQL $whereSQL ORDER BY $splitName " ;
      $db->setQuery($sql);
      $results2 =  $db->loadObjectList();
      foreach ($results2 as $key => $val) {
        $v = $val->label;
        $results[$v][] = $val->value;
      }
      foreach ($results as $k=>$v) {
        $median = $this->_median($v);
        $c = new stdClass();

        if ($plugin->hasSubElements) {
          $val->label =  $splitElementModel->_getLabelForValue($val->label);
        }

        $c->value = $val->value;
        $c->label = $val->label;

        $results[$k] = $c;
        foreach ($results as $o) {
          $res .= ($o->label == 'calc') ?  $o->value . "<br />" : $o->label . ': ' . $o->value . "<br />";
        }
      }
    } else {
      $sql = "SELECT $element->name FROM `$table->db_table_name` $joinSQL $whereSQL";
      $db->setQuery( $sql );
      $res 				= $this->_median( $db->loadResultArray() );
      $o 					= new stdClass();
      $o->value 	= $res;
      $label = $params->get('element_alt_table_heading') == '' ? $element->label : $params->get('element_alt_table_heading');
      $o->elLabel = $label;
      $o->label 	= 'calc';
      $results = array('calc'=>$o);
    }

    return array( $res, $results );
  }

  /**
   * @access private
   *
   * @param array $results
   * @return string median value
   */

  function _median( $results )
  {
    sort( $results );
    if ((count( $results ) % 2)==1) {
      /* odd */
      $midKey = floor( count( $results ) / 2);
      return $results[$midKey];
    } else {
      $midKey = floor( count( $results ) / 2) - 1;
      $midKey2 = floor( count( $results ) / 2) ;
      return ($results[$midKey] + $results[$midKey2]) / 2;
    }
  }

  /**
   * calculation: count
   * can be overridden in element class
   * @param object table model
   * @return string result
   */


  function count( &$tableModel )
  {
    $db 				=& $tableModel->getDb();
    $table 			=& $tableModel->getTable();
    $element 		=& $this->getElement();
    $joinSQL 		= $tableModel->_buildQueryJoin();
    $whereSQL 	= $tableModel->_buildQueryWhere();
    $params 		=& $this->getParams();
    $where = $params->get( 'count_condition', '' );
    if ($where != '') {
      $whereSQL = str_replace( "WHERE", "AND", $whereSQL );
    }
    $where .= " $whereSQL";
    $split 	= $params->get( 'count_split', '' );
    $sql = "SELECT COUNT($element->name) AS value, 'calc' AS label FROM `" . $table->db_table_name . "` $joinSQL $where";
    $db->setQuery( $sql );
    $results =  $db->loadObjectList('label');
    if ($split != '') {

      $splitElementModel =& JModel::getInstance('element', 'FabrikModel');
      $splitElementModel->setId( $split );
      //$pluginManager =& JModel::getInstance( 'Pluginmanager', 'FabrikModel' );
      $pluginManager =& $this->getForm()->getPluginManager();
      //@TODO: find way of loading in plugin as element rather than this two step process

      $splitElement = $splitElementModel->getElement();
      $splitName = "`$table->db_table_name`.`$splitElement->name`";
      $plugin = $pluginManager->getPlugIn( $splitElement->plugin, 'element' );

      $sql = "SELECT COUNT($element->name) AS value, $splitName AS label FROM `".$table->db_table_name."` $joinSQL $where GROUP BY $splitName ";
      $db->setQuery($sql);
      $results2 =  $db->loadObjectList('label');
      foreach ($results2 as $key => $val) {
        if ($plugin->hasSubElements) {
          $newkey =  $splitElementModel->_getLabelForValue($key);
          $val->label = $newkey;
        }
        $results[$key] = $val;
      }
    }

    $res = '';
    $label = $params->get('element_alt_table_heading') == '' ? $element->label : $params->get('element_alt_table_heading');
    foreach ($results as $o) {
      $o->elLabel = $label;
      $o->label = ($o->label == 'calc') ?  '' : $o->label;
      $res .= $o->label . ': ' . $o->value . "<br />";
    }
    return array($res, $results);
  }

  /**
   * overwritten in plugin classes
   *@param int repeat group counter
   */

  function elementJavascript( $repeatCounter )
  {
  }

  /**
   * create a class for the elements default javascript options
   * @param int repeat group counter
   *	@return object options
   */

  function getElementJSOptions( $repeatCounter )
  {
    $fbConfig =& JComponentHelper::getParams( 'com_fabrik' );
    $element =& $this->getElement();
    $opts = new stdClass();
    $opts->splitter = $this->_groupSplitter2;
    $opts->repeatCounter = $repeatCounter;
    $opts->editable = $this->_editable;
    $opts->defaultVal = $element->default;
    $opts->mooversion = ($fbConfig->get( 'usefabrik_mootools', false )) ? 1.2 : 1.1;
    return $opts;
  }

  /**
   * overwritten in plugin classes
   * @return bol use wysiwyg editor
   */
  function useEditor(){
    return false;
  }

  /**
   * overwritten in plugin classes
   * processes uploaded data
   */
  function processUpload()
  {
  }

  /**
   * overwritten in plugin classes
   * get the class to manage the form element
   */

  function formJavascriptClass()
  {
  }

  /**
   * overwritten in plugin classes
   * eg if changing from db join to field we need to remove the join
   entry from the #__fabrik_joins table
   *
   */
  function beforeSave()
  {
  }

  /**
   * OPTIONAL
   * If your element risks not to post anything in the form (e.g. check boxes with none checked)
   * the this function will insert a default value into the database
   * @param object params
   * @param array form data
   * @return array form data
   */

  function getEmptyDataValue( &$data )
  {
  }

  /**
   * used to format the data when shown in the form's email
   * @param mixed element's data
   * @param array form records data
   * @param int repeat group counter
   * @return string formatted value
   */

  function getEmailValue( $value, $data, $c )
  {
    return $value;
  }

  function isUpload()
  {
    return $this->_is_upload;
  }

  /**
   * can be overwritten in plugin class
   * If a database join element's value field points to the same db field as this element
   * then this element can, within modifyJoinQuery, update the query.
   * E.g. if the database join element points to a file upload element then you can replace
   * the file path that is the standard $val with the html to create the image
   *
   * @param string $val
   * @param string view form or table
   * @return string modified val
   */

  function modifyJoinQuery( $val, $view='form' )
  {
    return $val;
  }

  function ajax_loadTableFields( )
  {
    $tableModel =& JModel::getInstance( 'Table', 'FabrikModel' );
    $this->_cnnId 	= JRequest::getInt( 'cid', 0 );
    $tbl 			= "`" . JRequest::getVar( 'table' ) . "`";
    $fieldDropDown 		= $tableModel->getFieldsDropDown( $this->_cnnId, $tbl, '-', false, 'params[join_val_column]' );
    $fieldDropDown2 	= $tableModel->getFieldsDropDown( $this->_cnnId, $tbl, '-', false, 'params[join_key_column]' );
    echo "$('addJoinVal').innerHTML = '$fieldDropDown';";
    echo "$('addJoinKey').innerHTML = '$fieldDropDown2';";
  }

  /**
   * CAN BE OVERWRITTEN IN PLUGIN CLASS
   * create sql join string to append to table query
   * @return string join statement
   */

  function getJoin( $tableName = '' )
  {
    return null;
  }

  /**
   * render the element admin settings
   * @param object element
   */

  function renderAdminSettings( )
  {
    $params =& $this->getParams();
    $pluginParams =& $this->getPluginParams();
    $element =& $this->getElement();
    ?>
<div id="page-<?php echo $this->_name;?>" class="elementSettings"
	style="display: none"><?php
	echo $pluginParams->render( 'details' );
	echo $pluginParams->render( 'params', 'extra' );
	?></div>
	<?php
  }

  /**
   * CAN BE OVERWRITTEN IN PLUGIN CLASS
   * get js code to insert in edit element page - dont encase in domready code
   *
   */

  function getAdminJS()
  {

  }

  /**
   * CAN BE OVERWRITTEN IN PLUGIN CLASS
   * get db field type
   *
   */

  function getFieldDescription()
  {
    return "VARCHAR(255)";
  }
  /**
   * CAN BE OVERWRITTEN IN PLUGIN CLASS
   * trigger called when a row is deleted, can be used to delete images previously uploaded
   */

  function onDeleteRows()
  {

  }

  /**
   * CAN BE OVERWRITTEN IN PLUGIN CLASS
   * trigger called when a row is stored
   * @param array data to store
   */

  function onStoreRow($data)
  {

  }

  /**
   * CAN BE OVERWRITTEN IN PLUGIN CLASS
   *
   * child classes can then call this function with
   * return parent::renderTableData($data, $oAllRowsData );
   * to perform rendering that is applicable to all plugins
   *
   * shows the data formatted for the table view
   * @param string data
   * @param object all the data in the tables current row
   * @return string formatted value
   */

  function renderTableData( $data, $oAllRowsData )
  {
    $params =& $this->getParams();
    if ($params->get('icon_folder') != -1 && $params->get('icon_folder') != '') {
      return $this->_replaceWithIcons( $data );
    }
    if (strstr( $data, $this->_groupSplitter)) {
      return "<ul class='fabrikRepeatData'><li>".str_replace( $this->_groupSplitter, "</li><li>", $data) . "</li></ul>";
    } else {
      return $data;
    }
  }

  function renderTableData_csv( $data, $oAllRowsData )
  {
    return $data;
  }

  /**
   * determines if the element should be shown in the table view
   *
   * @param object $tableModel
   * @return bol
   */

  function inTableFields( &$tableModel )
  {
    $params =& $this->getParams();
    $element =& $this->getElement();
    $table =& $tableModel->getTable();
    $elFullName = $this->getFullName( true, false, false );

    if ($tableModel->_outPutFormat == 'rss') {
      $bAddElement = ( $params->get( 'show_in_rss_feed' ) == '1' );
      /* if its the date ordering col we should add it to the list of allowed elements */
      if ($elFullName == $tableModel->_params->get( 'feed_date', '' )) {
        $bAddElement = true;
      }
    } else {
      //depreciated - always false now - use view to decide if we need to show elements
      //$bAddElement = ( $tableModel->_onlyTableData ) ? $element->show_in_table_summary : true;
      $bAddElement = $element->show_in_table_summary;
    }
    if ($table->db_primary_key == $elFullName) {
      $tableModel->_temp_db_key_addded  = true;
    }
    return $bAddElement;
  }

  /**
   * builds some html to allow certain elements to display the option to add in new options
   * e.g. pciklists, dropdowns radiobuttons
   *
   * @param bol if true show one field which is used for both the value and label, otherwise show
   * separate value and label fields
   * @param int repeat group counter
   */

  function getAddOptionFields( $onlyfield, $repeatCounter )
  {
    $id = $this->getHTMLId( $repeatCounter );
    $elementHTMLId_ddVal = $id . "_ddVal";
    $elementHTMLId_ddLabel = $id . "_ddLabel";
    $value = "<input class='fabrikinput inputbox text' id='$elementHTMLId_ddVal' name='addPicklistValue' />";
    $label = "<input class='fabrikinput inputbox text' id='$elementHTMLId_ddLabel' name='addPicklistLabel' />";
    $str = "<a href='#' title='".JText::_('add option') ."' class='toggle-addoption'><img src='".COM_FABRIK_LIVESITE."media/com_fabrik/images/action_add.png' /></a>";
    $str .= "<br style='clear:left'/><div class='addoption'><div>" . JText::_('ADD A NEW OPTION TO THOSE ABOVE') . "</div>" ;
    if (!$onlyfield) {
      $str .= "<dl>" .
				"<dt><label for='$elementHTMLId_ddVal'>" . JText::_('VALUE') . "</label></dt><dd>" .
				"$value</dd>" .
				"<dt><label for='$elementHTMLId_ddLabel'>" . JText::_('LABEL'). "</label></dt>" .
				"<dd>$label</dd>" .
				"</dl>";
    } else {
      $str .= $label;
    }
    $str .= "<input class='button' type='button' id='" . $id . "_dd_add_entry' value='" . JText::_('ADD') . "' / ></div>";
    $str .=  $this->getHiddenField( $id . "_additions", '', $id . "_additions" );
    return $str;
  }

  /**
   *  overwritten in plugins
   *  @return bol true if the element type forces the form to
   * run in ajax submit mode (e.g. fancy upload file uploader)
   */

  function requiresAJAXSubmit()
  {
    return false;
  }

  /**
   * Can be overwritten by plugin - see fabrikdate
   * called on failed form validation.
   * Ensures submitted form data is converted back into the format
   * that the form would expect to get it in, if the data had been
   * draw from the database record
   * @param string submitted form value
   * @return string formated value
   */

  function toDbVal( $str )
  {
    return $str;
  }

  /**
   * determine if the element should run its validation plugins on form submission
   * can be overwritten by plugin class (see user plugin)
   *
   * @return bol default true
   */
  function mustValidate()
  {
    return true;
  }

  /**
   * get the name of the field to order the table data by
   * can be overwritten in plugin class - see database join
   * @return string column to order by tablename.elementname
   */

  function getOrderByName()
  {
    return $this->getFullName( false, false, false );
  }

  function getFilterLabel($rawval)
  {
    return $rawval;
  }

  function storeAttribs() {
    if (!$this->_element) {
      return false;
    }
    $db =& JFactory::getDBO();
    $db->setQuery( "UPDATE #__fabrik_elements SET attribs = '" . $this->_element->attribs . "' WHERE id = '" . $this->_element->id . "'" );
    $db->query( );
    return true;
  }

  function getDefaultAttribs()
  {
    return "rollover=
hover_text_title=
comment=
ck_value=
ck_default_label=
element_before_label=1
allow_frontend_addtocheckbox=0
chk-allowadd-onlylabel=0
chk-savenewadditions=0
database_join_display_type=dropdown
joinType=simple
join_conn_id=-1
join_db_name=
join_key_column=
join_val_column=
join_val_column_concat=
database_join_where_sql=
diysql=
database_join_noselectionvalue=
fabrikdatabasejoin_frontend_add=0
yoffset=
date_table_format=%Y-%m-%d
date_form_format=%Y-%m-%d
date_showtime=0
date_time_format=%H:%M
date_defaulttotoday=0
date_firstday=0
multiple=0
allow_frontend_addtodropdown=0
dd-allowadd-onlylabel=0
dd-savenewadditions=0
drd_initial_selection=
password=0
maxlength=255
text_format=text
integer_length=6
decimal_length=2
text_format_string=
guess_linktype=0
disable=0
readonly=0
ul_max_file_size=16000
ul_file_types=
ul_directory=
ul_email_file=0
ul_file_increment=0
upload_allow_folderselect=1
fu_fancy_upload=0
upload_delete_image=1
default_image=
make_link=0
fu_show_image_in_table=0
fu_show_image=0
image_library=gd2
fu_main_max_width=
fu_main_max_height=
make_thumbnail=0
thumb_dir=
thumb_prefix=
thumb_max_height=
thumb_max_width=
imagepath=/
selectImage_root_folder=/
image_front_end_select=0
show_image_in_table=0
image_float=none
link_url=
link_target=_self
radio_element_before_label=0
options_per_row=4
ck_options_per_row=4
allow_frontend_addtoradio=0
rad-allowadd-onlylabel=0
rad-savenewadditions=0
use_wysiwyg=0
textarea-showmax=0
textarea-maxlength=255
my_table_data=id
update_on_edit=0
calculation=
view_access=0
show_in_rss_feed=0
show_label_in_rss_feed=0
use_as_fake_key=0
element_alt_table_heading=
icon_folder=-1
custom_link=
use_as_row_class=0
filter_access=0
full_words_only=0
inc_in_adv_search=1
sum_on=0
sum_access=0
sum_split=
avg_on=0
avg_access=0
avg_split=
median_on=0
median_access=0
median_split=
count_on=0
count_condition=
count_access=0
count_split=";
  }

  /**
   * do we need to include the lighbox js code
   *
   * @return bol
   */

  function requiresLightBox()
  {
    return false;
  }

}
?>