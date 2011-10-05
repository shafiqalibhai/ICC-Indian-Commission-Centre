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
require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'parent.php');

class FabrikModelForm extends JModel
{

  /* not used in database (need to be prefixed with "_")*/
  /** @var array form's group elements*/
  var $_elements = null;

  /** @var object table model assocated with form*/
  var $_table = null;

  /** @var array of group ids that are actually tablejoins [groupid->joinid]*/
  var $_aJoinGroupIds = array();

  /** @var bol true if editable if 0 then show view only verion of form */
  var $_editable = 1;

  /** @var string encoding type */
  var $_enctype = "application/x-www-form-urlencoded";

  /** @var string html name of form */
  var $_formName = null;

  /** @var string the path to the form - can probably update this to point to SSL page */
  var $_rootPath = null;

  /** @var string the javascript to run on sumbission of the form */
  var	$_js = null;

  /** @var array validation rule classes */
  var $_validationRuleClasses = null;

  /** @var array specific group sets based on whether they contain elements not in table view or unpublished*/
  var $_specGroups = array();

  /**@var bol is the form running as a module(true)*/
  var $_isModule = false;

  /**@var bol is the form running as a mambot(true)*/
  var $_isMambot = false;

  /** @var array */
  var $_foreignKeyDropDowns = null;

  /** @var string */
  var $_err = '';

  /** @var array of join objects for the form */
  var $_aJoinObjs = array();

  var $_joinTableElementStep = '___';

  /** @var object parameters */
  var $_params = null;

  /** @var string form groups */
  var $_formGroupStr = null;

  /** @var aray form group idds */
  var $_formGroupIds = null;

  /** @var bol form is admin */
  var $_admin = false;

  /** @var int row id to submit */
  var $_rowId = null;

  /** @var string method to use when submitting form data // post or ajax*/
  var $_postMethod = null;

  /** @var int id */
  var $_id = null;

  /**@var int connection id */
  var $_connection_id = null;

  /** @var string database name */
  var $_database_name = null;

  /** @var object form **/
  var $_form = null;

  /** @var object last current element found in hasElement()*/
  var $_currentElement = null;

  /** @var bol if true encase table and element names with "`" when getting elemenet list */
  var $_addDbQuote = false;

  var $_formData = array();

  /** @var array form errors */
  var $_arErrors = array();

  /** @var object uploader helper */
  var $_oUploader = null;

  /** @var array pages (array containing group ids for each page in the form **/
  var $pages = null;

  /** @var object session model deals with storing incomplete pages **/
  var $sessionModel = null;

  /** @var array modified data by any validation rule that uses replace functionality */
  var $_modifiedValidationData = null;

  var $groups = null;

  /** store the form's previous data when processing */
  var $_origData = null;

  /** @var array stores elements not shown in table **/
  var $_elementsNotInTable = null;

  var $_data = null;
  /**
   * Constructor
   *
   * @since 1.5
   */

  function __construct()
  {
    parent::__construct();
    $usersConfig = &JComponentHelper::getParams( 'com_fabrik' );
    $id = JRequest::getInt( 'fabrik', $usersConfig->get( 'fabrik' ) );
    $this->setId( $id );
  }

  /**
   * Method to set the form id
   *
   * @access	public
   * @param	int	table ID number
   */

  function setId( $id )
  {
    // Set new form ID
    $this->_id		= $id;
  }

  /**
   * get form table (alias to getTable())
   *
   * @return object form table
   */

  //function &getForm()
  function &getForm()
  {
    return $this->getTable();
  }

  /**
   * checks if the params object has been created and if not creates and returns it
   * @return object params
   */

  function &getParams()
  {
    if (!isset( $this->_params )) {
      $form =& $this->getForm();
      $this->_params = &new fabrikParams( $form->attribs, JPATH_SITE . '/administrator/components/com_fabrik/xml/form.xml', 'component' );
    }
    return $this->_params;
  }

  /**
   * sets the instances admin state
   * @param bol admin state
   */

  function setAdmin( $bol )
  {
    $this->_admin = $bol;
  }

  /**
   * makes sure that the form is not viewable based on the table's access settings
   * @return int 0 = no access, 1 = view only , 2 = full form view, 3 = add record only
   */

  function checkAccessFromTableSettings()
  {
    $form =& $this->getForm();
    if ($form->record_in_database == 0) {
      return 2;
    }
    $tableModel =& $this->getTableModel();
    if (!is_object( $tableModel )) {
      return 2;
    }
    $ret = 0;
    if ($tableModel->canViewDetails()) {
      $ret = 1;
    }
    /* new form can we add?*/
    if ($this->_rowId == 0) {
      /*if they can edit can they also add?*/
      if ($tableModel->canAdd() ) {
        $ret = 3;
      }
    } else {
      /*editing from - can we edit?*/
      if ($tableModel->canEdit( $this->_data )) {
        $ret = 2;
      }
    }
    return $ret;
  }

  /**
   * Checks : custom css file, template css file. Return an array of file
   * relative paths (may contains 0 to 2 elements)
   * @return array css uri's
   */

  function getFormCss($tmpl)
  {
    $document =& JFactory::getDocument();
    $cssFiles = array();
    /* check for a custom css file */
    $cssFiles[] = JURI::root(true) .'/media/com_fabrik/css/form.css';
    $aCssPath = COM_FABRIK_FRONTEND.DS.'css'.DS.$this->_id.".css";
    if (JFile::exists( $aCssPath )) {
      $cssFiles[] = 	JURI::root(true) . '/components/com_fabrik/css/'.$this->_id.".css";
    }
    /* check for a form template file (code moved from view) */
    if ($tmpl != '') {
      $aCssPath = COM_FABRIK_FRONTEND.DS.'views'.DS.'form'.DS.'tmpl'.DS.$tmpl.DS."template.css";
      if (JFile::exists($aCssPath)) {
        $cssFiles[] = 	JURI::root(true) . '/components/com_fabrik/views/form/tmpl/'."$tmpl".'/template.css';
      }
    }
    if (JRequest::getVar('tmpl') !== 'component') {
      foreach ($cssFiles as $css) {
        $document->addStyleSheet( $css );
      }
    }
    return $cssFiles;
  }

  /**
   * Barbara : get the custom js action script relative path if it exists
   * @return string uri of custom js file
   */
  function getCustomJsAction()
  {
    /* check for a custom javascript file */
    $aJsPath = JPATH_COMPONENT.DS.'js'.DS.$this->_id.".js";
    if (file_exists( $aJsPath )) {
      // Barbara
      $customJsAction = JURI::root(true).'/components/com_fabrik/js/' . $this->_id . ".js";
    } else {
      $customJsAction = '';
    }
    return $customJsAction;
  }

  /**
   * set the page title for form
   * @return string page title
   */

  function getPageTitle( $title )
  {
    $form =& $this->getForm();
    $title = ($title == "") ? $form->label : $title;
    $groups =& $this->getGroupsHiarachy();
    foreach ($groups as $groupModel) {
      $elementModels =& $groupModel->getPublishedElements();
      foreach ($elementModels as $elementModel) {
        $element =& $elementModel->getElement();
        if ($element->use_in_page_title == '1') {
          $default = $elementModel->getTitlePart( $this->_data );
          $s = is_array($default) ? implode(', ', $default) . " " : $default . " ";
          $title .= $s;
        }
      }
    }
    return  $title;
  }

  /**
   * compares the forms table with its groups to see if any of the groups are in fact table joins
   * @param array tables joins
   * @return array array(group_id =>join_id)
   */

  function getJoinGroupIds( $joins )
  {
    $arJoinGroupIds = array();
    $groups =& $this->getGroupsHiarachy();
    foreach ($groups as $groupModel) {
      foreach ($joins as $join) {
        if ($join->element_id == 0 && $groupModel->_id == $join->group_id) {
          $arJoinGroupIds[$groupModel->_id] = $join->id;
        }
      }
    }
    $this->_aJoinGroupIds = $arJoinGroupIds;
    return $arJoinGroupIds;
  }

  /**
   * gets the javascript actions the forms elements
   * @return array of javascript actions
   */

  function getJsActions()
  {
    $db =& JFactory::getDBO();
    $j =& new JRegistry();
    $aJsActions = array();
    $aElIds = array();
    $groups =& $this->getGroupsHiarachy();
    foreach ($groups as $groupModel) {
      $elementModels =& $groupModel->getPublishedElements();
      foreach ($elementModels as $elementModel) {
        $aJsActions[$elementModel->_id] = array();
        $aElIds[] = $elementModel->_id;
      }
    }
    $sql = 'SELECT * FROM #__fabrik_jsactions WHERE element_id IN (' . implode(',', $aElIds) . ')';
    $db->setQuery( $sql );
    $res = $db->loadObjectList( );
    if (is_array( $res )) {
      foreach ($res as $r) {

        //merge the js attribs back into the array
        $j->loadINI( $r->attribs );
        $a = $j->toArray();
        foreach ($a as $k=>$v) {
          $r->$k = $v;
        }
        unset( $r->attribs );
        $aJsActions[$r->element_id][] = $r;
      }
    }
    return $aJsActions;
  }

  /** get the ids of all the groups in the form
   * @return array of group ids
   */

  function getGroupIds()
  {
    if (!isset( $this->_formGroupIds )) {
      $this->_loadGroupIds();
    }
    return $this->_formGroupIds;
  }

  /**
   * force load in the group ids
   * separate from getGroupIds as you need to force load these
   * when saving the table
   */

  function _loadGroupIds()
  {
    $db =& JFactory::getDBO();
    $sql = "SELECT #__fabrik_formgroup.group_id AS group_id FROM #__fabrik_formgroup".
		"\n WHERE #__fabrik_formgroup.form_id = '$this->_id' ORDER BY ordering" ;
    $db->setQuery( $sql );
    $groups = $db->loadResultArray();
    $form =& $this->getForm();
    if ($form->record_in_database) {
      $tableModel = $this->getTableModel();
      if (is_object( $tableModel ) && $tableModel->_id != '') {
        $db->setQuery( "SELECT group_id FROM #__fabrik_joins WHERE table_id = '$tableModel->_id'" );
        $joinGroups = $db->loadResultArray();
        $groups = array_unique(array_merge( $groups, $joinGroups ));
      }
    }
    $this->_formGroupIds = $groups;
  }

  /**
   * get the forms group objects
   *
   * @return array group objects
   */

  function getGroups()
  {
    if (!isset ($this->groups)) {
      $tableModel =& $this->getTableModel();
      $groupids = $this->getGroupIds();
      $groupModel =& JModel::getInstance( 'Group', 'FabrikModel' );
      foreach ($groupids as $id) {
        $thisGroup = clone( $groupModel );
        $thisGroup->setId( $id );
        $thisGroup->setContext( $this, $tableModel );
        $group =& $thisGroup->getGroup();
        if ($group->state == 1) {
          $this->groups[$id] = $thisGroup; //dont use &=!
        }
      }
    }
    return $this->groups;
  }

  /**
   * gets each element in the form along with its group info
   * @param bol included unpublished elements in the result
   * @return array element objects
   */

  function getFormGroups( $excludeUnpublished = true )
  {
    $db =& JFactory::getDBO();
    $sql = "SELECT *, #__fabrik_groups.attribs AS gattribs, #__fabrik_elements.id as element_id
		, #__fabrik_groups.name as group_name  FROM #__fabrik_formgroup
		LEFT JOIN #__fabrik_groups
		ON #__fabrik_formgroup.group_id = #__fabrik_groups.id
		LEFT JOIN #__fabrik_elements
		ON #__fabrik_groups.id = #__fabrik_elements.group_id
		WHERE #__fabrik_formgroup.form_id = '$this->_id' " ;
    if ($excludeUnpublished) {
      $sql .= " AND #__fabrik_elements.state = '1' ";
    }
    $sql .= "ORDER BY #__fabrik_formgroup.ordering, #__fabrik_formgroup.group_id, #__fabrik_elements.ordering";
    $db->setQuery( $sql );
    $groups = $db->loadObjectList();
    echo $db->getErrorMsg();
    $this->_elements = $groups;
    return $groups;
  }

  /**
   * similar to getFormGroups() except that this returns a data structure of
   * form
   * --->group
   * -------->element
   * -------->element
   * --->group
   * if run before then existing data returned
   * @return array element objects
   */

  function &getGroupsHiarachy()
  {
    $this->getGroups();
    $pluginManager =& $this->getPluginManager();
    $this->groups =& $pluginManager->getFormPlugins( $this );
    return $this->groups;
  }

  /**
   * get an list of elements that aren't shown in the table view
   *
   * @return array of element table objects
   */
  function getElementsNotInTable()
  {
    if (!isset( $this->_elementsNotInTable )) {
      $this->_elementsNotInTable = array();
      $groups =& $this->getGroupsHiarachy();
      foreach ($groups as $group) {
        $elements =& $group->getPublishedElements();
        foreach ($elements as $elementModel) {
          $element =& $elementModel->getElement();
          if (!$element->show_in_table_summary) {
            $this->_elementsNotInTable[] = $element;
          }
        }

      }
    }
    return $this->_elementsNotInTable;

  }

  /**
   * this checks to see if the form has a file upload element
   * and returns the correct
   * encoding type for the form
   * @param int form id
   * @param object forms elements
   * @return string form encoding type
   */

  function getFormEncType()
  {
    $groups =& $this->getGroupsHiarachy();
    foreach ($groups as $groupModel) {
      $elementModels =& $groupModel->getPublishedElements();
      foreach ($elementModels as $elementModel) {
        if ($elementModel->_is_upload == '1') {
          return "multipart/form-data";
        }
      }
    }
    return "application/x-www-form-urlencoded";
  }

  /**
   * run a method on all the element plugins in the form
   *
   * @param string method to call
   * @param array posted form data
   */

  function runElementPlugins( $method, $data )
  {
    $groups =& $this->getGroupsHiarachy();
    foreach ($groups as $groupModel) {
      $elementModels =& $groupModel->getPublishedElements();
      foreach ($elementModels as $elementModel) {
        $params =& $elementModel->getParams();
        if (method_exists( $elementModel, $method )) {
          $elementModel->$method( $params, $data );
        }
      }
    }
  }

  /**
   * get the plugin manager
   *
   * @return object plugin manager
   */

  function getPluginManager()
  {
    if (!isset($this->_pluginManager)) {
      $this->_pluginManager =& JModel::getInstance( 'Pluginmanager', 'FabrikModel' );
    }
    return $this->_pluginManager;
  }

  function setOrigData()
  {
    $tableModel	=& $this->getTableModel();
    $fabrikDb   =& $tableModel->getDb();
    $sql 				= $this->_buildQuery();
    $fabrikDb->setQuery( $sql );
    $this->_origData = $fabrikDb->loadObject();
  }

  /**
   * processes the form data and decides what action to take
   */

  function process()
  {
    global $mainframe;
    @set_time_limit(300);
    require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'uploader.php' );
    $form						=& $this->getForm();
    $pluginManager 	=& $this->getPluginManager();
    $params 				=& $this->getParams();

    $sessionModel =& JModel::getInstance( 'Formsession', 'FabrikModel' );
    $sessionModel->setFormId( $this->_id );
    $sessionModel->setRowId( $this->_rowId );
    $this->getGroupsHiarachy();

    if ($form->record_in_database == '1') {
      $this->setOrigData();
    }

    if (!$pluginManager->runPlugins( 'onBeforeProcess', $this )) {
      return;
    }
    if (!$this->_doUpload()) {
      return false;
    }

    $this->setFormData();

    if (!$pluginManager->runPlugins( 'onBeforeStore', $this )) {
      return false;
    }

    if ($form->record_in_database == '1') {
      $this->processToDB();
    }

    if (!$pluginManager->runPlugins( 'onAfterProcess', $this )) {
      //returning false here stops the default redirect occuring
      return false;
    }
    //$$$rob used ONLY for redirect plugins
    if (!$pluginManager->runPlugins( 'onLastProcess', $this )) {
      //returning false here stops the default redirect occuring
      return false;
    }
    $sessionModel->remove();
    return true;
  }

  function _doUpload()
  {
    $oUploader =& $this->getUploader();
    if ($oUploader->check()) {
      $oUploader->upload();
      if ($oUploader->moveError) {
        return false;
      }
    }
    return true;
  }

  /*
   * this will strip the html from the form data
   * see here - http://forum.joomla.org/index.php/topic,259690.msg1182219.html#msg1182219
   * still not working in J1.5.2 :(
   */

  function setFormData()
  {
    $filter	= JFilterInput::getInstance( null, null, 1, 1 );
    foreach ($_REQUEST as $key=>$val) {
      $val = JRequest::getVar( $key, '', 'request', 'string', JREQUEST_ALLOWRAW ); // JREQUEST_ALLOWHTML doesnt work wth!
      $aData[$key] = $val;
      if (!is_array( $aData[$key] )) {
        $aData[$key] = html_entity_decode((string) $filter->_remove($filter->_decode((string) $aData[$key])));
        //_decode doesnt deal with uppercase letter in the encoded string generated by javascripts encodeURIComponent function
        $aData[$key] = preg_replace('/%([0-9A-F]{2})/mei', "chr(hexdec('\\1'))", $aData[$key]);
      } else {
        foreach ($aData[$key] as $k2 => $val2)
        {
          // filter element for XSS and other 'bad' code etc.
          if (is_string( $val2 )) {
            $aData[$key][$k2] = html_entity_decode($filter->_remove( $filter->_decode( $val2 ) ));
            //_decode doesnt deal with uppercase letter in the encoded string generated by javascripts encodeURIComponent function
            $aData[$key][$k2] = preg_replace('/%([0-9A-F]{2})/mei', "chr(hexdec('\\1'))", $aData[$key][$k2]);
          }
        }
      }
    }
    $this->_formData = $aData;
    $this->_fullFormData = $aData;
  }

  /**
   * process the data to the database
   *
   * @return unknown
   */

  function processToDB()
  {

    $tableModel			=& $this->getTableModel();
    $table					=& $tableModel->getTable();
    $origTableName 	= $table->db_table_name;
    $origTableKey		= $table->db_primary_key;
    $pluginManager 	=& $this->getPluginManager();

    // COPY function should create new records
    if (array_key_exists( 'Copy', $this->_formData )) {
      $this->_rowId = '';
      $k = FabrikString::safeColNameToArrayKey( $table->db_primary_key );
      $origid = $this->_formData[$k];
      $this->_formData[$k] = '';
      $this->_formData['rowid'] = '';
    }
    /* get an array of the joins to process
     note this was processJoin() but now preProcessJoin() does the same except
     no longer stores the results - do this after the main form data has been
     saved and u have an id to use
     for the foreign key value*/
    $aPreProcessedJoins =& $tableModel->preProcessJoin();


    //needed for plugins that are run after the data is submitted to the db
    $this->_formDataWithTableName = $this->_formData;
    $this->_formData 	= $tableModel->removeTableNameFromSaveData( $this->_formData, '___' );

    $insertId 		 		= $this->submitToDatabase( $this->_rowId );
    //set the redirect page to the form's url if making a copy and set the id
    //to the new insertid
    if (array_key_exists( 'Copy', $this->_formData )) {
      $u = str_replace( "rowid=$origid", "rowid=$insertId", $_SERVER['HTTP_REFERER'] );
      JRequest::setVar( 'fabrik_referrer', $u );
    }
    $tmpKey 	= str_replace( ".", "___", $table->db_primary_key );
    $tmpKey 	= str_replace( "`", "", $tmpKey );
    $this->_formData[$tmpKey] 	= $insertId;
    $this->_formData[$tableModel->_shortKey( null, true)] = $insertId;
    $this->_fullFormData[$tmpKey] = $insertId; //need for things like the redirect plugin
    $this->_fullFormData['rowid'] = $insertId;
    $this->__formDataWithTableName['rowid'] = $insertId;
    $_REQUEST[$tmpKey] 	= $insertId;
    $_POST[$tmpKey] 	= $insertId;
    $_POST['rowid'] 	= $insertId;
    $_REQUEST['rowid'] 	= $insertId;

    //save join data
    $this->_removeIgnoredData( $this->_formData );
    if (array_key_exists( 'join', $this->_formData )) {

      foreach ($aPreProcessedJoins as $aPreProcessedJoin) {

        $oJoin = $aPreProcessedJoin['join'];
        
        if (array_key_exists( 'Copy', $this->_formData )) {
          $this->_rowId = '';
          $this->_formData['join'][$oJoin->id][$oJoin->table_join.'___'.$oJoin->table_key] = '';
          $this->_formData['rowid'] = '';
        }

        $data = $this->_formData['join'][$oJoin->id];
        //$$$rob moved till just before join table data saved
        //$data = $oTable->removeTableNameFromSaveData( $data, $split='___' );

        $groups =& $this->getGroupsHiarachy();
        $joinGroup = $groups[$oJoin->group_id];

        //find the primary key for the join table
        // $$$ rob - looks like  $table isn't a reference to $tableModel->_table -go figure?? (php5.2.5 lax) Also reason why Hugh thought we
        // needed to pass in the table name to the storeRow() function.
        //$table->db_table_name 	= $oJoin->table_join;
        $tableModel->_table->db_table_name 	= $oJoin->table_join;
        $fields 				= $tableModel->getDBFields( $oJoin->table_join );
        $aKey 					= $tableModel->getPrimaryKeyAndExtra();
        //$table->db_primary_key = $aKey['colname'];
        $tableModel->_table->db_primary_key = $aKey['colname'];
        $joinDb 				=& $tableModel->getDb();

        if (is_array( $data ) && array_key_exists( $oJoin->table_join . '___' . $oJoin->table_join_key, $data )){
          //$$$rob get the join tables ful primary key
          $joinDb->setQuery( "DESCRIBE $oJoin->table_join" );
          $oJoinPk = $oJoin->table_join . "___";
          $cols = $joinDb->loadObjectList( );
          foreach ($cols as $col) {
            if ($col->Key == "PRI") {
              $oJoinPk .= $col->Field;
            }
          }
          $fullforeginKey = $oJoin->table_join . '___' . $oJoin->table_join_key;

          if ($joinGroup->canRepeat()) {
            //find out how many repeated groups were entered
            // might make more sense to store this value in a hidden field rather than guess it from the data?

            $repeatedGroupCount = 0;
            $elementModels =& $joinGroup->getPublishedElements();
            foreach ($elementModels as $elementModel) {
              $element = $elementModel->getElement();
              $n = $elementModel->getFullName( false, true, false );
              if (count( $data[$n] ) >= $repeatedGroupCount)
              {
                $repeatedGroupCount = count($data[$n]);
              }
            }
            $aUpdatedRecordIds = array();
            for ($c = 0; $c < $repeatedGroupCount; $c++) {
              //get the data for each group and record it seperately
              $repData = array();
              foreach ($elementModels as $elementModel) {
                $element = $elementModel->getElement();
                $n = $elementModel->getFullName( false, true, false );
                $v = (is_array($data[$n]) && array_key_exists($c, $data[$n])) ? $data[$n][$c] : '';
                $repData[$element->name] = $v;
              }
              $repData[$oJoin->table_join_key] = $insertId;

              //find the primary key for the join table

              $table->db_table_name 	= $oJoin->table_join;
              $fields 				= $tableModel->getDBFields( $table->db_table_name );
              $aKey 					= $tableModel->getPrimaryKeyAndExtra();
              $table->db_primary_key = $aKey['colname'];
              $joinCnn 				=& $tableModel->getConnection();
              $joinDb  				=& $joinCnn->getDb( );
              $joinRowId 				= $repData[$table->db_primary_key];

              $aDeleteRecordId 		= $repData[$oJoin->table_join_key];
              //$$$ hugh - need to give it the table name!!
			        // $$$ rob no no no this is not the issue, on SOME setups $table is NOT a reference to $tableModel->_table - this is where the issue is 
			        // not passing in the correct table name - see notes line 720 for explaination
              // $tableModel->storeRow( $repData, $joinRowId, true, $table->db_table_name );
              $tableModel->storeRow( $repData, $joinRowId, true );
              if ($joinRowId == '') {
                $joinRowId = $tableModel->_lastInsertId;
              }
              $aUpdatedRecordIds[] 	= $joinRowId;
            }

            //remove any joins that have been deleted with the groups "delete" button
            if (!$data) {
              $sql = "DELETE  FROM `$oJoin->table_join`  "
              . "\nWHERE ($oJoin->table_join_key = $aDeleteRecordId)";
            } else {
              $sql = "DELETE  FROM `$oJoin->table_join`  "
              . "\nWHERE !($table->db_primary_key IN (" . implode( ',', $aUpdatedRecordIds ) . ")) AND ($oJoin->table_join_key = $aDeleteRecordId)";
            }
            $joinDb->setQuery( $sql );
            $joinDb->query();
            echo $joinDb->getErrorMsg();
          } else {

            // $$$rob test if the joined to table's key (as part of the join) is the same as its primary key
            // if it is then we dont want to overwrite the foreginkey as we will in fact be overwriting the pk
            //also test if the foreign key isnt the same as the joins key - hard to explain cos its v confusing but
            //when you had 2 joins with both of them key'd to the main table things went horribly wrong

            //if ($fullforeginKey != $oJoinPk) {
            if ($fullforeginKey != $oJoinPk && $fullforeginKey != "{$oJoin->table_join}___{$oJoin->table_key}") {
              $data[$fullforeginKey] = $insertId;
              $data[$fullforeginKey . "_raw"] = $insertId;
            }
            if ($table->db_primary_key == '') {
              return JError::raiseWarning( 500, JText::_( "MUST SELECT PRIMARY KEY" ));
            }
            $joinRowId = $data[$table->db_table_name . '___' . $table->db_primary_key];
            
            
            $data = $tableModel->removeTableNameFromSaveData( $data );
         
            //try to catch an pk val when the db_primary_key is in the short format
            // $$$ rob - think the primary key will always been in the short format as we got the
            //JOIN tables pk (ie $table->db_primary_key) direct from the db description
            //if (is_null( $joinRowId )) {
            //	$joinRowId 				= $data[$table->db_primary_key];
            //}
			      //$$$ hugh - need to give it the table name!!
		        // $$$ rob no no no this is not the issue, on SOME setups $table is NOT a reference to $tableModel->_table - this is where the issue is 
		        // not passing in the correct table name - see notes line 720 for explaination
            // $tableModel->storeRow( $repData, $joinRowId, true, $table->db_table_name );
           $tableModel->storeRow( $data, $joinRowId, true );
            //$$$rob if the fk was the same as the pk then go back to the main table and
            // update its fk to match the
            // pk of the inserted table

           
            if ($fullforeginKey == $oJoinPk) {
              $pkVal = $tableModel->_lastInsertId;
              $fk = $oJoin->table_key;
              $this->_formData[$fk] = $pkVal;

              //reset the table's values to the main table
              // $$$ rob same issues as above with $table not being a reference to $tableModel->_table
              //$table->db_table_name = $origTableName;
              //$table->db_primary_key = $origTableKey;
              $tableModel->_table->db_table_name = $origTableName;
              $tableModel->_table->db_primary_key = $origTableKey;
              $tableModel->storeRow( $this->_formData, $insertId );
              $insertId = $tableModel->_lastInsertId;

              // $$$ hugh - I think this needs to be $insertId, not $rowId, otherwise
              // if it's new row (so $rowId was null) we insert a duplicate row in
              // the main table?
              // NOTE TO SELF - test on row edit as well as new row!!
              //$insertId 		= $this->submitToDatabase( $insertId );

            }
          }
        } else {
          // no join data found so delete all joined records
          $k = $oJoin->join_from_table . '___' .$oJoin->table_key;
          $sql = "DELETE  FROM `$oJoin->table_join`  "
          . "\nWHERE ($oJoin->table_join_key = {$this->_formData[$k]})";
          $joinDb->setQuery( $sql );
          $joinDb->query();
        }
      }
    }
    //testing for saving pages/
    JRequest::setVar( 'rowid', $insertId );
    if (!$pluginManager->runPlugins( 'onBeforeCalculations', $this )) {
      return;
    }
    $this->_table->doCalculations();
  }

  /**
   * removes any element which s set to ignore
   * @param array form data
   */

  function _removeIgnoredData( &$data )
  {
    $groups =& $this->getGroupsHiarachy();
    foreach ($groups as $groupModel) {
      $groupTable = $groupModel->getGroup();
      $elementModels =& $groupModel->getPublishedElements();
      foreach ($elementModels as $elementModel) {
        $element =& $elementModel->getElement();
        $element->label = strip_tags( $element->label );
        $params =& $elementModel->getParams();

        //check if the data gets inserted on update
        $v = $elementModel->getValue( $data );
        //currently only field password elements return true and file uploads when no file selected
        if ($elementModel->ignoreOnUpdate( $v )) {
          $fullName = $elementModel->getFullName( false, true, true );
          unset( $data['join'][$groupTable->join_id][$fullName] );
          if (array_key_exists($element->name, $data)) {
            unset($data[$element->name]);
          }
        }
      }
    }
  }

  /**
   * saves the form data to the database
   * @param int rowid - if 0 then insert a new row - otherwise update this row id
   * @return mixed insert id (or rowid if updating existing row) if ok , else string error message
   */

  function submitToDatabase( $rowId = '0' )
  {
    $db =& JFactory::getDBO();
    $this->getGroupsHiarachy();
    $pluginManager =& $this->getPluginManager();
    /*
     *check if there is table data that is not posted by the form
     * (ie if no checkboxes were selected)
     */
    $groups =& $this->getGroupsHiarachy();
    foreach ($groups as $groupModel) {
      $group = $groupModel->getGroup();
      $elementModels =& $groupModel->getPublishedElements();
      foreach ($elementModels as $elementModel) {
        $element = $elementModel->getElement();

        $element->label = strip_tags( $element->label );
        $params = $elementModel->getParams();
        $elementModel->getEmptyDataValue( $this->_formData );

        //check if the data gets inserted on update
        $v = $elementModel->getValue( $this->_formData );
        if ($elementModel->ignoreOnUpdate( $v )) {
          //currently only field password elements return true
          $fullName = $elementModel->getFullName( false, true, true );
          unset( $this->_formData['join'][$group->join_id][$fullName] );
          if (array_key_exists( $element->name, $this->_formData )) {
            unset( $this->_formData[$element->name] );
          }
        }
        $plugin =& $pluginManager->getPlugIn( $element->plugin, 'element' );
        $plugin->_element =& $element;

        if ($groupModel->isJoin()) {
          $tmpdata =& $this->_formData['join'][$group->join_id];
          //maybe no joined data added so test before doing onstorerow
          if (is_array( $tmpdata )) {
            $plugin->onStoreRow( $tmpdata );
          }
        } else {
          $plugin->onStoreRow( $this->_formData );
        }
      }
    }
    $tableModel = $this->getTableModel();
    $tableModel->_oForm =& $this;
    $table =& $tableModel->getTable();
    $tableModel->storeRow( $this->_formData, $rowId );
    return ($rowId == 0) ?  $tableModel->_lastInsertId : $rowId;
  }

  /**
   * get the form's table model
   * (was getTable but that clashed with J1.5 func)
   *
   * @return object fabrik table model
   */

  function &getTableModel()
  {
    if (is_null( $this->_table )) {
      $this->_table =& JModel::getInstance( 'Table', 'FabrikModel');
      $this->_table->loadFromFormId( $this->_id );
      $this->_table->_oForm =& $this;
    }
    return $this->_table;
  }

  /**
   * get the class names for each of the validation rules
   * @return array (validaionruleid => classname )
   */

  function loadValidationRuleClasses()
  {
    if (is_null( $this->_validationRuleClasses )) {
      $pluginManager =& $this->getPluginManager();
      $oValidationRules =& $pluginManager->getPlugInGroup( 'validationrule' );
      $classes = array();
      foreach ($oValidationRules as $rule) {
        $classes[$rule->_pluginName] = $rule->_className;
      }
      $this->_validationRuleClasses = $classes;
    }
    return $this->_validationRuleClasses;
  }

  /**
   * validate the form before processing it
   * called from controller
   * @return bol true if validation passed / otherwise false
   */

  /**
   * validate the form
   *
   * @return bol true if form validated ok
   */

  function validate()
  {
    require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'uploader.php' );
    $pluginManager 		=& JModel::getInstance( 'Pluginmanager', 'FabrikModel' );
    $oValidationRules =& $pluginManager->getPlugInGroup( 'validationrule' );
    $db 	=& JFactory::getDBO();
    $post	=& JRequest::get( 'post', 4 ); //4 allows html
    //contains any data modified by the validations
    $this->_modifiedValidationData = array();

    $groups =& $this->getGroupsHiarachy();
    $joindata = array();
    $ok = true;
    //add in raw fields - the data is already in raw format so just copy the values
    foreach ($groups as $groupModel) {
      $elementModels =& $groupModel->getPublishedElements();
      foreach ($elementModels as $elementModel) {

        $elName2					= $elementModel->getFullName( false, true, false );
        if ($groupModel->isJoin()) {
          $joinModel =& $groupModel->getJoinModel();
          if (array_key_exists( 'join', $post ) && array_key_exists( $joinModel->_id, $post['join'] )) {
	          $joindata[$joinModel->_id][$elName2] = $post['join'][$joinModel->_id][$elName2];
	          $joindata[$joinModel->_id][$elName2."_raw"] = $post['join'][$joinModel->_id][$elName2];
	          $_POST['join'][$joinModel->_id][$elName2."_raw"] = $post['join'][$joinModel->_id][$elName2];//post required getValue() later on
          }
        } else {
          if (!array_key_exists( $elName2."_raw", $post)) {
            JRequest::setVar($elName2."_raw", @$post[$elName2]); //post required getValue() later on
          }
        }
      }
    }
    $groups = $this->getGroupsHiarachy();
    $arErrors = array();
    $repeatTotals = JRequest::getVar( 'fabrik_repeat_group', array(), 'post', 'array' );

    $groupCounter = 0;
    foreach ($groups as $groupModel) {
      $elementModels = $groupModel->getPublishedElements();
      $elDbVals = array();

      if ($groupModel->isJoin()) {
        $joinModel =& $groupModel->getJoinModel();
      }

      foreach ($elementModels as $elementModel) {
        $element 					=& $elementModel->getElement();

        $validation_rules =& $elementModel->getValidations();
        $elName 					= $elementModel->getFullName( true, true, false );
        $this->_arErrors[$elName] = array();
        $elName2					= $elementModel->getFullName( false, true, false );
        for ($c=0; $c < $repeatTotals[$groupCounter]; $c++) {
          $this->_arErrors[$elName][$c] = array();
          $form_data =  $elementModel->getValue( $_POST, $c, array('runplugins'=>0) );
          	
          //internal element plugin validations
          if (!$elementModel->validate( @$form_data, $c )) {
            $ok = false;
            $this->_arErrors[$elName][$c][] = $elementModel->getValidationErr();
          }

          if ($groupModel->canRepeat()) {
            $elDbVals[$c] = $elementModel->toDbVal( $form_data, $c );
          } else {
            $elDbVals = $elementModel->toDbVal( $form_data, $c );
          }
          	
          //validations plugins attached to elemenets
          $pluginc = 0;
          if (!$elementModel->mustValidate()) {
            continue;
          }
          	
          foreach ($validation_rules as $plugin) {
            $plugin->_formModel =& $this;
            $plugin->_tableModel =& $this->getTableModel();
            if ($plugin->shouldValidate( $form_data, $pluginc )) {
              if (!$plugin->validate( $form_data, $elementModel, $pluginc )) {
                $this->_arErrors[$elName][$c][] = $plugin->getMessage( $pluginc );
                $ok = false;
              }
              if (method_exists( $plugin, 'replace' )) {

                if ($groupModel->canRepeat()){

                  $elDbVals[$c] = $elementModel->toDbVal( $form_data, $c );
                  $testreplace = $plugin->replace( $elDbVals[$c], $elementModel, $pluginc );
                  if ($testreplace != $elDbVals[$c]) {
                    $elDbVals[$c] = $testreplace;
                  }
                } else {

                  $testreplace = $plugin->replace( $elDbVals, $elementModel, $pluginc );
                  if ($testreplace != $form_data) {
                    $elDbVals = $testreplace;
                    $this->_modifiedValidationData[$elName] = $testreplace;
                  }
                }
              }
            }
            $pluginc ++;
          }

        }

        if ($groupModel->isJoin()) {
          $joindata[$joinModel->_id][$elName2] = $elDbVals;
        } else {
          JRequest::setVar( $elName, $elDbVals );
        }
        	
        //unset the deafults or the orig submitted form data will be used (see date plugin mysql vs form format)
        $elementModel->defaults = null;

      }
      $groupCounter ++;
    }
    //insert join data into request array
    JRequest::setVar( 'join', $joindata, 'post' );

    //$this->_modifiedValidationData = $returnData;
    if (!empty( $this->_arErrors )) {
      $pluginManager 	=& $this->getPluginManager();
      $pluginManager->runPlugins( 'onError', $this );
    }
    return $ok;
  }

  /**
   * get an instance of the uploader object
   *
   * @return object uploader
   */

  function &getUploader()
  {
    if (is_null( $this->_oUploader )) {
      $this->_oUploader = new uploader( $this );
    }
    return $this->_oUploader;
  }

  /**
   * get the forms table name
   *
   * @return string table name
   */

  function getTableName()
  {
    $this->getTableModel();
    return $this->_table->_table->db_table_name;
  }

  /**
   * get the form row
   *
   * @return object form row
   */

  function &getTable()
  {
    if (is_null( $this->_form )) {
      $this->_form = parent::getTable();
      $this->_form->load( $this->_id );
    }
    return $this->_form;
  }

  /**
   * overloaded check function
   * //$$$rob think this should go in the form's table class???
   */

  function check()
  {
    /* check for valid name*/
    if (trim( $this->label ) == '') {
      $err = JText::_('FORM MUST CONTAIN A TITLE');
    }
    if (isset( $err )) {
      $this->_error = $err;
      return false;
    }
    return true;
  }

  /**
   * when saving a table that links to a database for the first time we
   * need to link together the created form and the created group
   * @param int group id
   */

  function createFormGroup( $groupId ) {
    /*load in existing groups*/
    $str = $this->_getFromGroupsStr() . ",$groupId";
    $this->_setGroupString( $str );
    $this->saveFormGroups();
  }

  function _getFromGroupsStr(){
    if (is_null( $this->_formGroupStr )) {
      $this->_formGroupStr = $this->_loadFromGroupsStr();
    }
    return $this->_formGroupStr;
  }

  function _loadFromGroupsStr(){
    $db =& JFactory::getDBO();
    $sql = "SELECT group_id FROM #__fabrik_formgroup WHERE form_id = '$this->_id'";
    $db->setQuery( $sql );
    $aFormGroups = $db->loadResultArray( );

    //check if table has joins - they should be already in aFormGoups but better to be safe
    $aJoinGroupIds = array();
    $tableModel =& $this->getTableModel();
    if (is_object( $tableModel )) {
      $joins = $this->_table->getJoins();
      foreach ($joins as $join) {
        $aJoinGroupIds[]  = $join->group_id;
      }
    }
    $merged = array_merge( $aJoinGroupIds, $aFormGroups );
    // do double flip for merging values in an array
    $merged = array_flip( $merged );
    $merged = array_flip( $merged );

    $sFromGroups = implode( ',', $merged );
    return $sFromGroups;
  }

  function _setGroupString( $str )
  {
    $this->_formGroupStr = $str;
  }

  /**
   * sets the variable of each of the form's group's elements to the value
   * specified
   * @param string variable name
   * @param string variable value
   * @return bol false if update error occurs
   */

  function setElementVars( $varName, $varVal )
  {
    $db =& JFactory::getDBO();
    if ($this->_elements == null) {
      $this->getFormGroups();
    }
    foreach ($this->_elements as $el) {
      $element =& JTable::getInstance( 'Element', 'Table' );
      $element->load( $el->id );
      if (!$element->setVar( $varName, $varVal )) {
        return false;
      }
      $element->store();
    }
    return true;
  }

  /**
   * determines if the form can be published
   * @return bol true if publish dates are ok
   */

  function canPublish()
  {
    $db =& JFactory::getDBO();
    $form =& $this->getForm();
    $nullDate = (method_exists( $db, 'getNullDate' )) ? $db->getNullDate() : $this->getNullDate();
    $publishup =& JFactory::getDate( $form->publish_up );
    $publishup = $publishup->toUnix();

    $publishdown =& JFactory::getDate($form->publish_down);
    $publishdown = $publishdown->toUnix();

    $jnow		=& JFactory::getDate();
    $now		= $jnow->toUnix();
    if ($form->state == '1') {
      if ($now >= $publishup || $form->publish_up == '' || $form->publish_up == $nullDate) {
        if ($now <= $publishdown || $form->publish_down == '' || $form->publish_down == $nullDate) {
          return true;
        }
      }
    }
    return false;
  }

  /**
   * creates a html dropdown off all forms
   * @param int selected group id
   * @param string default label
   * @return string group list
   */

  function makeDropDown( $selectedId = 0, $defaultlabel = '' )
  {
    if ($defaultlabel == '') {
      $defaultlabel = JText::_( 'PLEASE SELECT' ) ;
    }
    $db =& JFactory::getDBO();
    $db->setQuery( "SELECT id AS value, label AS text FROM #__fabrik_forms ORDER BY label" );
    $aTmp[] = JHTML::_( 'select.option', "-1", $defaultlabel );
    $forms = $db->loadObjectList();
    $forms = array_merge( $aTmp, $forms );
    return JHTML::_( 'select.genericlist', $forms, 'filter_formId', 'class="inputbox"  onchange="document.adminForm.submit( );"', 'value', 'text', $selectedId );
  }

  /**
   * create a drop down list of all the elements in the form
   * @param string drop down name
   * @param string current value
   * @param bol add elements that are unpublished
   * @param bol concat table name and el name with "___" (true) or "." (false)
   * @return string html list
   */

  function getElementList( $name = 'order_by', $default = '', $excludeUnpublished = false, $useStep = false )
  {
    $aEls = array();
    $aEls = $this->getElementOptions( $useStep );
    $aEls[] = JHTML::_( 'select.option', '', '-' );
    asort($aEls);
    return JHTML::_( 'select.genericlist', $aEls, $name, 'class="inputbox" size="1" ', 'value', 'text', $default );
  }

  function getElementIDList( $name, $default )
  {
    $aEls = array();
    $groups =& $this->getGroupsHiarachy();
    foreach ($groups as $groupModel) {
      $elementModels =& $groupModel->getMyElements();
      foreach ($elementModels as $elementModel) {
        $element =& $elementModel->getElement();
        $aEls[] = JHTML::_( 'select.option', $element->id, $element->label );
      }
    }
    return JHTML::_( 'select.genericlist',  $aEls, $name, 'class="inputbox" size="1" ', 'value', 'text', $default );
  }

  function getElementIds()
  {
    $aEls = array();
    $groups =& $this->getGroupsHiarachy();
    foreach ($groups as $groupModel) {
      $elementModels =& $groupModel->getPublishedElements();
      foreach ($elementModels as $elementModel) {
        $element =& $elementModel->getElement();
        $aEls[] = $element->id;
      }
    }
    return $aEls;
  }

  /**
   * creates options array to be then used by getElementList to create a drop down of elements in the form
   * sperated as elements need to collate this options from muliple forms
   * @param bol concat table name and el name with "___" (true) or "." (false)
   * @param string name of key to use (default "name")
   * @return array html options
   */

  function getElementOptions( $useStep = false, $key = 'name', $show_in_table_summary = false )
  {
    $groups =& $this->getGroupsHiarachy();
    $aEls = array( );
    $step =( $useStep ) ? "___" : ".";
    //$step = "___";
    foreach ($groups as $groupModel) {
      $elementModels =& $groupModel->getMyElements();
      foreach ($elementModels as $elementModel) {
        $el =& $elementModel->getElement();
        //$$$ testing
        if ($show_in_table_summary == true && $el->show_in_table_summary != 1) {
          continue;
        }
        $val = $el->$key;
        $label = $el->label;
        if ($key != "id") {
          $val = $elementModel->getFullName( false, $useStep, false );
          if ($this->_addDbQuote) {
            FabrikString::safeColName($val);
          }
        }
        $aEls[] = JHTML::_( 'select.option', $val, $label );
      }
    }
    asort($aEls);
    return $aEls;
  }

  /**
   * collates data to write out the form
   * @param bol if in Joomla administration site
   * @return mixed . Object template if rendered as mambot otherwise empty
   */

  function render( $admin = false )
  {
    @set_time_limit(300);
    $user 				=& JFactory::getUser();
    $usersConfig 	=& JComponentHelper::getParams( 'com_fabrik' );
    $pluginManager =& $this->getPluginManager();

    $this->_rowId = JRequest::getInt( 'rowid', $usersConfig->get('rowid') );
    if ($this->_rowId == '-1') {
      $this->_rowId = $user->get( 'id' );
    }
    $pluginManager->runPlugins( 'onSetRowId', $this );
    $form 	=& $this->getForm();
    $this->_data = array();
    $this->setAdmin( $admin );

    $aJoinFields 	= array();
    $aGroups 			= null;

    //test
    $data 				= array( JArrayHelper::toObject(JRequest::get( 'request' )) );

    $aGroups			=& $this->getGroupsHiarachy();
    if (!$form->record_in_database) {
      $data = JRequest::get( 'request' );
    } else {
      $tableModel 	=& $this->getTableModel();
      $fabrikDb   	=& $tableModel->getDb();

      $table 				=& $tableModel->getTable();
      if (!$fabrikDb) {
        return JError::raiseWarning( JText::_( "COULD NOT CONNECT TO THE DATABASE" ));
      }

      $this->_aJoinObjs 	=& $tableModel->getJoins();

      if (!empty( $this->_arErrors )) {
        //form has failed validation, so get the form data from the request array
        $data = array( FArrayHelper::toObject(JRequest::get( 'post', 4 ), 'stdClass', false) );

      } else {

        //test if its a resumed paged form
        $pagegroups = $this->getPageSaveGroups();
        $srow = $this->getSessionData();
        if (!empty( $pagegroups ) && $srow->data != '') {
          $data = array( FArrayHelper::toObject( unserialize( $srow->data ), 'stdClass', false ) );
        } else {
          // only try and get the row data if its an active record
          if ($this->_rowId > 0) {
            // $$$ hugh - once we have a few join elements, our select statements are
            // getting big enough to hit default select length max in MySQL.  Will need
            // to add this, maybe as a global option?
            /*
            $fabrikDb->setQuery("SET OPTION SQL_BIG_SELECTS=1");
            $fabrikDb->query();
            */

            //otherwise lets get the table record
            $sql 	= $this->_buildQuery();

            $fabrikDb->setQuery( $sql );
            FabrikHelperHTML::debug( $fabrikDb->getQuery(), 'form:render' );
            $data = $fabrikDb->loadObjectList();
            FabrikHelperHTML::debug( $data, 'form:data' );

            // if empty data return and trying to edit a record then show error
            //occurs if user trying to edit a record forbidden by a prefilter rull
            if (empty( $data ) && $this->_rowId != '') {
              // $$$ hugh - special case when using -1, if user doesn't have a record yet
              if (JRequest::getVar('rowid') == '-1') {
                return;
              }
              else {
                return JError::raiseNotice( 500, JText::sprintf( 'COULD NOT FIND RECORD IN DATABASE', $this->_rowId ) );
              }
            }
          }
        }
        //no need to setJoinData if you are correcting a failed validation
        $this->setJoinData( $data );
      }
      	
      //set the main part of the form's default data
      if ($this->_rowId != '') {
        $data = JArrayHelper::fromObject( $data[0] );
      } else {
        //could be a view
        if ($tableModel->isView()) {
          //@TODO test for new records from views
          $data = JArrayHelper::fromObject( $data[0] );
        } else {
          if (count( $this->getPageSaveGroups()) > 0 && is_object($data[0])) {
            $data = JArrayHelper::fromObject( $data[0] );
          }else{
            $data = JRequest::get( 'request' );
          }
        }
      }

      FabrikHelperHTML::debug( $data, 'form:render default data' );
      $this->_table =& $tableModel;
      	


      // $$$ hugh - how about we run this at the end of the func, otherwise
      // there's not a heck of a lot the plugin can do with the form data we're
      // loading up.
      //$$$Rob  - moved as per Hugh's idea
      if (!$pluginManager->runPlugins( 'onLoad', $this )) {
        return;
      }
    }

    //Test to allow {$my->id}'s to be evald from query strings
    $w =& new FabrikWorker();
    $data = $w->parseMessageForPlaceHolder( $data );
    $this->_data =& $data;

    $this->_reduceDataForXRepeatedJoins();
  }

  /**
   *
   * if editing a record which contains repeated join data then on start $data is an
   * array with each records being a row in the database.
   *
   * We need to take this structure and convert it to the same format as when the form
   * is submitted
   *
   */

  function setJoinData( &$data )
  {
    $this->_joinDefaultData = array();

    if (!array_key_exists('join', $data[0])){
      $data[0]->join = array();
    }
    // $$$ hugh - sometimes $data[0]->join is an object not an array?
    // $$$ rob - no longer as in render we use FarrayHelper to not recurse into data when setting to object
    /*if (is_object($data[0]->join)) {
     $data[0]->join = JArrayHelper::fromObject($data[0]->join);
     }*/

    //no joins so leave !
    if (!is_array( $this->_aJoinObjs )) {
      return;
    }

    if ($this->_rowId != '') {


      $groups =& $this->getGroupsHiarachy();
      foreach ($groups as $groupModel) {
        if ($groupModel->isJoin()) {
          $group =& $groupModel->getGroup();
          $data[0]->join[$group->join_id] = array();
          $elementModels =& $groupModel->getMyElements();
          foreach ($elementModels as $elementModel) {
            $name = $elementModel->getFullName(false, true, false);
            $fv_name = 'join[' . $group->join_id . '][' . $name . ']';
            $rawname = $name ."_raw";
            $fv_rawname = 'join[' . $group->join_id . '][' . $rawname . ']';
            foreach ($data as $row) {

              if (array_key_exists( $name, $row ) ) {
                $v = $row->$name;
                if (strstr($row->$name, $elementModel->_groupSplitter2)) {
                  $v = explode( $elementModel->_groupSplitter2, $v );
                }
                $data[0]->join[$group->join_id][$name][] = $v;
                unset($row->$name);
              }
              /* $$$ hugh - seem to have a different format if just failed validation! */
              else if (array_key_exists( $fv_name, $row)) {
                $v = $row->$fv_name;
                if (is_object($v)) {
                  $v = JArrayHelper::fromObject($v);
                }
                $data[0]->join[$group->join_id][$name] = $v;
                unset($row->$fv_name);
              }

              if (array_key_exists( $rawname, $row ) ) {
                $v = $row->$rawname;
                if (strstr($row->$rawname, $elementModel->_groupSplitter2)) {
                  $v = explode( $elementModel->_groupSplitter2, $v );
                }
                $data[0]->join[$group->join_id][$rawname][] = $v;
                unset($row->$rawname);
              }
              /* $$$ hugh - seem to have a different format if just failed validation! */
              else if (array_key_exists( $fv_rawname, $row)) {
                $v = $row->$fv_rawname;
                if (is_object($v)) {
                  $v = JArrayHelper::fromObject($v);
                }
                $data[0]->join[$group->join_id][$rawname][] = $v;
                unset($row->$fv_rawname);
              }
            }
          }
        }
      }
    }
  }

  /**
   * get the forms session data (used when using multipage forms)
   *
   * @return object session data
   */

  function getSessionData()
  {
    $this->sessionModel =& JModel::getInstance( 'Formsession', 'FabrikModel' );
    $this->sessionModel->setFormId( $this->_id );
    $this->sessionModel->setRowId( $this->_rowId );
    return $this->sessionModel->load();
  }

  /**
   * @access private
   * create the sql query to get the rows data for insertion into the form
   */

  function _buildQuery()
  {
    $conf 				=& JFactory::getConfig();
    $form					=& $this->getForm();
    if (!$form->record_in_database) {
      return;
    }
    $tableModel 	=& $this->getTableModel();
    $fabrikDb   	=& $tableModel->getDb();
    $table 				=& $tableModel->getTable();
    $aGroups			=& $this->getGroupsHiarachy();
    $params				=& $this->getParams();

    $_aAsFields 	=& $tableModel->getAsFields();
    if ($conf->getValue( 'config.caching' )) {
      $sql = $tableModel->getCacheQuery();
    } else {
      $sql = $tableModel->_buildQuerySelect();
      $sql .= $tableModel->_buildQueryJoin();
    }
    //$sql = $tableModel->_buildQuerySelect();
    //$sql .= $tableModel->_buildQueryJoin();

    $random = JRequest::getVar( 'random' );
    $usekey = JRequest::getVar( 'usekey' );
    if ($usekey != '') {
      $usekey = $table->db_table_name.'.'.$usekey;
      FabrikString::safeColName( $usekey );
    }

    $viewpk = JRequest::getVar( 'view_primary_key' );
    if (!$random && $this->_rowId != '') {
      $sql .= " WHERE ";
      $sql .=  ($usekey != '') ? " $usekey = '$this->_rowId' " : " $table->db_primary_key = '$this->_rowId' ";
    } else {
      if ($viewpk != '') {
        $sql .= " WHERE $viewpk ";
      } else if ($random) {
        $sql .= " ORDER BY RAND() LIMIT 1 ";
      }
    }
    // get prefilter conditions from table and apply them to the record
    //the false, ignores any filters set by the table
    $where = $tableModel->_buildQueryWhere( false );
    if (strstr($sql, 'WHERE') && $this->_rowId != '') {
      //do it this way as queries may contain subquerues which we want to keep the where
      $firstword = substr( $where, 0, 5 );
      if ($firstword == 'WHERE') {
        $where = substr_replace( $where, 'AND', 0, 5 );
      }
    }
    //set rowId to -2 to indicate random record
    if ($random) {
      $this->_rowId = -2;
    }
    $sql .= $where;
    return $sql;
  }

  /**
   * After having saved the form we
   * 1) Create a new group if none selected in edit form list
   * 2) Delete all old form_group records
   * 3) Recreate the form group records
   * 4) Make a table view if needed
   * @return bol true if you should display the form list, false if you're
   * redirected elsewhere
   */

  function saveFormGroups()
  {
    $db =& JFactory::getDBO();
    $current_groups_str = JRequest::getVar( 'current_groups_str', $this->_getFromGroupsStr( ), 'post' );
    $record_in_database = JRequest::getInt( 'record_in_database', $this->_form->record_in_database, 'post' );
    $createGroup 				= JRequest::getInt( '_createGroup', 0, 'post' );
    $form =& $this->getForm();
    if ($createGroup) {
      $group = JTable::getInstance( 'Group', 'Table' );
      $group->name = $form->label;
      $group->state = 1;
      $group->store();
      $current_groups_str .= "," . $db->insertid() ;
    }

    $db->setQuery( "DELETE FROM #__fabrik_formgroup WHERE form_id = '" . $this->_id . "'");
    // delete the old form groups
    if (!$db->query()) {
      JError::raiseError( 500, $db->stderr());
    }

    $this->_makeFormGroups( $current_groups_str );

    if ($record_in_database == '1') {
      $tableModel =& $this->getTableModel();
      $dbTableName = ( $tableModel->_table->db_table_name == '') ? JRequest::getVar( '_database_name', '', 'post' ) : $tableModel->_table->db_table_name;
      $cnn =& $tableModel->getConnection();
      $cnnId = JRequest::getVar( '_connection_id', $cnn->_id, 'post' );
      $table =& $tableModel->_table;

      $defaultDb =& $tableModel->getDb();

      if (!$tableModel->databaseTableExists( $dbTableName, $defaultDb )) {
        //need to pass the correct database obj here
        $tableModel->createDBTable( $this, $dbTableName, $defaultDb );

        $connection =& $tableModel->getConnection();

        //NEW 2.0 create table view
        //enusre _tbl_key is set to 'id'
        $table->_tbl_key = 'id';
        $table->id = null;
        $table->label 				= $form->label;
        $table->form_id 			= $form->id;
        $table->connection_id = $connection->_id;
        $table->db_table_name	= $dbTableName;
        $table->db_primary_key = '`'.$dbTableName . '`.`fabrik_internal_id`';
        $table->auto_inc 			= 1;
        $table->state 				= $form->state;
        $table->created				= $form->created;
        $table->created_by		= $form->created_by;
        $table->attribs = $tableModel->getDefaultAttribs();
        $res = $table->store();
        if ($res) {
          $tableModel->createCacheQuery();
        } else {
          $fabrikDatabase =& $tableModel->getDb();
          if ($dbTableName == '') {
            $dbTableName = JRequest::getVar( 'db_table_name', '', 'post' );
            if ($dbTableName == '') {
              $table->load( JRequest::getInt( 'id', '', 'post' ) );
              $dbTableName = $table->db_table_name;
            }
          }
          $tableModel = $this->getTableModel();
          $tableModel->ammendTable( $this, $dbTableName, $fabrikDatabase );
        }
      } else {
        $tableModel->ammendTable( $this );
        $tableModel->makeSafeTableColumns();
      }
    }
  }

  function _makeFormGroups( $current_groups_str )
  {
    $db =& JFactory::getDBO();
    $orderid = 1;
    $current_groups = explode( ",", $current_groups_str );
    foreach ($current_groups as $group_id) {
      if ($group_id != '') {
        $sql = "INSERT INTO #__fabrik_formgroup (form_id, group_id, ordering) VALUES ('" . $this->_id . "','$group_id','$orderid')";
        $db->setQuery( $sql );
        if (!$db->query()) {
          JError::raiseError( 500, $db->stderr());
        }
        $orderid ++;
      }
    }
  }

  /**
   * attempts to determine if the form contains the element
   * @param string element name to search for
   * @return bol true if found, false if not found
   */

  function hasElement( $searchName )
  {
    $groups =& $this->getGroupsHiarachy();
    foreach ($groups as $groupModel) {
      $elementModels =& $groupModel->getMyElements();
      if (!is_array($groupModel->elements )) {
        continue;
      }
      foreach ($groupModel->elements as $elementModel) {
        $element =& $elementModel->getElement();
        if ($searchName == $element->name) {
          $this->_currentElement = $elementModel;
          return true;
        }
        if ($searchName == $elementModel->getFullName( true, true, false)) {
          $this->_currentElement = $elementModel;
          return true;
        }
        if ($searchName == $elementModel->getFullName( false, true, false)) {
          $this->_currentElement = $elementModel;
          return true;
        }
        if ($searchName == $elementModel->getFullName( true, false, false)) {
          $this->_currentElement = $elementModel;
          return true;
        }
        if ($searchName == $elementModel->getFullName( false, false, false)) {
          $this->_currentElement = $elementModel;
          return true;
        }
      }
    }
    return false;
  }

  function setTableModel( &$tableModel )
  {
    $this->_table = $tableModel;
  }

  /**
   * is the page a multipage form?
   * @return bol true/false
   *
   */

  function isMultiPage()
  {
    $groups =& $this->getGroupsHiarachy();
    foreach ($groups as $groupModel) {
      $params =& $groupModel->getParams();
      if ($params->get( 'split_page' )) {
        return true;
      }
    }
    return false;
  }

  /**
   * get an array of pages, with their containing group ids
   *
   * @return array
   */

  function getPages()
  {
    if (!is_null( $this->pages )) {
      return $this->pages;
    }

    $this->pages = array();
    $pageCounter = 0;
    $groups = $this->getGroupsHiarachy(); //dont use & as this buggers up in PHP 5.2.0

    $c = 0;

    foreach ($groups as $groupModel) {
      $params =& $groupModel->getParams();
      if ($params->get( 'split_page' ) && $c != 0) {
        $pageCounter ++;
      }
      $this->pages[$pageCounter][] = $groupModel->_id;
      $c ++;
    }

    return $this->pages;
  }

  /**
   *
   *
   * @return array
   */

  function getPageSaveGroups()
  {
    $a = array();
    $groups =& $this->getGroupsHiarachy();
    foreach ($groups as $groupModel) {
      $params =& $groupModel->getParams();
      if ($params->get( 'split_page_save' )) {
        $a[] = $groupModel->_id;
      }
    }
    return $a;
  }

  /**
   * get the method that the form should use on submission
   *
   * @return string ajax/post
   */

  function getPostMethod()
  {
    if (is_null($this->_postMethod)){
      $this->_postMethod = JRequest::getVar( '_postMethod', 'post' );
      $groups =& $this->getGroupsHiarachy();
      foreach ($groups as $groupModel) {
        $elementModels =& $groupModel->getPublishedElements();
        foreach ($elementModels as $elementModel) {
          if ($elementModel->requiresAJAXSubmit()) {
            $this->_postMethod = 'ajax';
          }
        }
      }
    }
    return $this->_postMethod;
  }

  /**
   * @since fabrik2.0rc1
   * Used in special case where you have 2 + n-n joins in a single table
   * In this case the sql query will most likely create four rows of data for
   * each combination of possibilities
   *
   * E.g.
   *
   * tbl classes (id, label)
   *
   * left joined to:
   * tbl student_classes (id, label, student_id)
   *
   * left joined to
   * tbl student_teachers (id, label, teacher_id)
   *
   * entering one records with 2 students and 2 teachers gives you 4 rows in the query
   *
   * classid  student_id, teacher_id
   * 1        1           1
   * 1        2	          1
   * 1        1	          2
   * 1        2           2
   *
   * @param unknown_type $data
   */

  function _reduceDataForXRepeatedJoins()
  {
    $groups = $this->getGroupsHiarachy();
    $tableModel =& $this->getTableModel();
    foreach ($groups as $groupModel) {
      if ($groupModel->canRepeat() && $groupModel->isJoin()) {

        $joinModel 	=& $groupModel->getJoinModel();
        $tblJoin 		=& $joinModel->getJoin();
        if (!array_key_exists($tblJoin->id, $this->_data['join'])) {
          return;
        }

        $jdata 			=& $this->_data['join'][$tblJoin->id];
        $db 				=& $tableModel->getDb();
        $db->setQuery( "DESCRIBE `$tblJoin->table_join`" );
        $fields = $db->loadObjectList();
        foreach ($fields as $f) {
          if ($f->Key == 'PRI') {
            $pkField = $tblJoin->table_join . "___" . $f->Field;
          }
        }
        $usedkeys = array();
        foreach ($jdata[$pkField] as $key=>$value) {
          if (!in_array( $value, $usedkeys )) {
            $usedkeys[$key] = $value;
          }
        }

        $keystokeep = array_keys($usedkeys);

        ///remove unneeded data from array
        foreach ($jdata as $key =>$value) {
          foreach ($value as $key2=>$v) {
            if (!in_array($key2, $keystokeep)) {
              unset($jdata[$key][$key2]);
            }
          }
        }

        //reduce the keys so that we dont have keys of 0, 2
        foreach ($jdata as $key =>$array) {
          $jdata[$key] = array_values($array);
        }
      }
    }
  }

  /**
   * query all active form plugins to see if they inject cutsom html into the top
   * or bottom of the form
   *
   *return array plugin top html, plugin bottom html
   */

  function _getFormPluginHTML()
  {
    $pluginManager =& $this->getPluginManager();
    $formPlugins =& $pluginManager->getPlugInGroup( 'form' );
    $form =& $this->getForm();
    $top = array();
    $bottom = array();

    $pluginManager->runPlugins( 'getBottomContent', $this, 'form' );
    $pluginbottom = implode( "<br />", array_filter( $pluginManager->_data ) );

    $pluginManager->runPlugins( 'getTopContent', $this, 'form' );
    $plugintop = implode( "<br />", array_filter( $pluginManager->_data ) );
    return array( $plugintop, $pluginbottom );
  }

  /**
   * Presuming that our introduction looks like this:
   *
   * {new:this is an intro}
   * {edit:You're editing a record}
   * some more text
   *
   * creating a new form record will show the intro text as:
   *
   * this is an intro
   * some more text
   *
   * and editing an existing record will show:
   *
   * You're editing a record
   * some more text
   *
   * @return string modified intro
   */

  function getIntro()
  {
    $match = ($this->_rowId == '') ? 'new' : 'edit';
    $remove = ($this->_rowId == '') ? 'edit' : 'new';
    $match = "/{".$match.":\s*.*?}/i";
    $remove = "/{".$remove.":\s*.*?}/i";
    $intro =& $this->getForm()->intro;
    $intro = preg_replace_callback( $match, array($this, '_getIntro'), $intro );
    $intro = preg_replace( $remove, '', $intro );
    return $intro;
  }

  /**
   * 
   * @param $match
   * @return unknown_type
   */
  private function _getIntro($match)
  {
    return substr($match[0], 5, strlen($match[0])-6);
  }
  
  /**
   * 
   * @return unknown_type
   */
  function getLabel()
  {
    $label = $this->getForm()->label;
    if (stristr( $label, "{Add/Edit}" )) {
      $replace = ($this->_rowId == '') ? JText::_('ADD') : JText::_('EDIT');
      $label = str_replace( "{Add/Edit}", $replace, $label );
    }
    return $label;
  }
}

?>