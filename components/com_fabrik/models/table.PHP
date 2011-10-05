<?php
/**
 * @package Joomla
 * @subpackage Fabrik
 * @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'json.php' );
require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'pagination.php' );
require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'string.php' );
require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'joomfish.php' );


class FabrikModelTable extends JModel {

  /** @var object the tables connection object */
  var $_oConn = null;

  /** @var object table Table */
  var $_table = null;

  /** @var object table's database connection object (loaded from connection object) */
  var $_oConnDB = null;

  /** @var object table's form model */
  var $_oForm = null;

  /** @var array joins */
  var $_aJoins = null;

  /** @var array column calculations */
  var $_aRunCalculations = array();

  /** @var string table output format - set to rss to collect correct element data within function gettData()*/
  var $_outPutFormat = 'html';

  var $_isMambot = false;

  var $_admin = false;

  /** @var object to contain access rights **/
  var $_access = null;

  /** @var int the id of the last inserted record (or if updated the last record updated) **/
  var $_lastInsertId = null;

  /** @var bol determine if we apply prefilters (0 = yes, 1 = no )*/
  var $_togglePreFilters = 0;

  /** @var array store data to create joined records from */
  var $_joinsToProcess = null;

  /** @var array database fields */
  var $_dbFields = null;

  /** @var bol force reload table calculations **/
  var $_reloadCalculations = false;

  /** @var array data contains request data **/
  var $_aData = null;

  /** @var string method to use when submitting form data // post or ajax*/
  var $_postMethod = null;

  /** @var int package id */
  var $_packageId = null;

  /** @var object plugin manager */
  var $_pluginManager = null;

  /** @var int id of table to load */
  var $_id = null;

  /** @var string join sql **/
  var $_joinsSQL = null;

  /** @var array order by column names **/
  var $orderByFields = null;

  /** @var bol is the object inside a package? */
  //var $_inPackage  = false;

  /** @var bol  when getting the tableData this decides if only the elements published to the
   table view are loaded. Primarily used by visualization plugins to get all table data regardless
   of whether its published to the table view */
  //depreciated - always false now - use view to decide if we need to show elements
  ///var $_onlyTableData = true;

  var $_joinsToThisKey = null;

  var $_real_filter_action = null;

  /** array merged request and session data used to potentially filter the table **/
  var $_request = null;

  var $_aRow = null;

  /** array rows to delete **/
  var $_rowsToDelete = null;

  /** @var array original table data BEFORE form saved - used to ensure uneditable data is stored */
  var $_origData = null;

  /** @var bol set to true to load records STARTING from a random id (used in the getPageNav func) **/
  var $_randomRecords = false;

  var $_data = null;
  var $nav = null;
  var $fields = null;
  var $prefilters = null;
  var $filters = null;
  var $aJoinsToThisKey = null;
  var $canSelectRows = null;
  var $asfields = null;

  /**
   * Constructor
   *
   * @since 1.5
   */

  function __construct()
  {
    parent::__construct();
    $usersConfig = &JComponentHelper::getParams( 'com_fabrik' );
    $id = JRequest::getInt( 'tableid', $usersConfig->get( 'tableid' ) );
    $this->setId($id);
    $this->_access = new stdClass();
  }


  function processPlugin()
  {
    $table =& $this->getTable();
    $name = JRequest::getVar('fabrik_tableplugin_name');
    $pluginManager =& $this->getPluginManager();
    $plugin =& $pluginManager->getPlugin( $name, 'table' );
    $plugin->renderOrder = JRequest::getVar( 'fabrik_tableplugin_renderOrder', 0 );
    $msg[] = $plugin->process( $this );
    return implode( '<br />', $msg );
  }

  /**
   * get the html that is outputted by table plugin buttons
   *
   * @return array buttons
   */

  function getPluginButtons()
  {
    $pluginManager =& $this->getPluginManager();
    $params =& $this->getParams();
    $table =& $this->getTable();
    $usedPlugins 		= $params->get( 'plugin', '', '_default', 'array' );
    $pluginManager->getPlugInGroup( 'table' );
    $c = 0;
    $aValidations = array();
    foreach ($usedPlugins as $usedPlugin) {
      $oPlugin 		= $pluginManager->_plugIns['table'][$usedPlugin];
      $oPlugin->_params 	=& new fabrikParams( $table->attribs, $oPlugin->_xmlPath, 'fabrikplugin' );

      $c ++;
    }
    $res = $pluginManager->runPlugins( 'button', $this, 'table' );
    $buttons = $pluginManager->_data;
    $pluginManager->runPlugins( 'loadJavascriptClass', $this, 'table' );
    return $buttons;
  }

  /**
   * main query to build table
   *
   */

  function render()
  {
    global $mainframe, $_PROFILER;
    $document =& JFactory::getDocument();
    if (is_null( $this->_id ) || $this->_id == '0') {
      return JError::raiseError( 500, JText::_('TABLE ID NOT SET CANT RENDER'));
    }
    $params =& $this->getParams();
    $session =& JFactory::getSession();
    $registry	=& $session->get('registry');
    $this->_outPutFormat = JRequest::getVar( 'format', 'html' );
    if ($this->_outPutFormat == 'fabrikfeed') {
      $this->_outPutFormat = 'feed';
    }
    $pluginManager =& $this->getPluginManager();

    $table 		=& $this->getTable();

    //cant set time limit in safe mode so suppress warning
    @set_time_limit ( 800 );

    $this->_request 	= $this->getRequestData();
    $filters =& $this->getFilterArray();
    $context					= 'com_fabrik.table'. $this->_id.'.list.';

    $limitLength 			= $mainframe->getUserStateFromRequest( $context.'limitlength', 		'limit', $table->rows_per_page );
    $limitStart				= $mainframe->getUserStateFromRequest( $context.'limitstart',			'limitstart',		0,	'int' );
    if ($this->_outPutFormat == 'feed') {
      $limitLength = JRequest::getVar( 'limit',  $params->get( 'rsslimit',150 ) );
      $maxLimit = $params->get( 'rsslimitmax', 2500 );
      if ($limitLength > $maxLimit) {
        $limitLength = $maxLimit;
      }
    }

    $total 						= $this->getTotalRecords();
    $this->getPagination( $total, $limitStart, $limitLength );
    if ($limitLength == 0) {
      $pageNav->limit = 0;
    }
    $this->getData();
    //think we really have to do these as the calc isnt updated when the table is filtered
    $this->doCalculations();
    $this->getCalculations();
    $table->hit();
  }

  /**
   * this merges session data for the fromForm with any request data
   * allowing us to filter data results from both search forms and filters
   *
   * @return array
   */

  function getRequestData()
  {
    //@TODO investigate moveing the $_SESSION stuff into Joomla's $session object
    //not sure how this would effect accessing the session data outside of the J framework
    // you would possibly have to create an instance of the framework to access the session.
    global $_SESSION;
    if (isset($this->_request)) {
      return $this->_request;
    }
    $aData 			= JRequest::get( 'request' );
    $formModel 	=& $this->getForm();
    $table =& $this->getTable();
    if (!array_key_exists( "fabrik", $_SESSION )) {
      $_SESSION["fabrik"] = array();
    }
    if (is_array( $_SESSION )) {

      if (array_key_exists( "fabrik",  $_SESSION )) {
        if (!is_array($_SESSION['fabrik'])) {
          $_SESSION['fabrik'] = array();
        }
        if (array_key_exists( 'fromForm',  $_SESSION["fabrik"] )) {
          $fromForm = $_SESSION["fabrik"]['fromForm'];

          //only merge if the fromForm is not the same as the current table's form id
          if ($_SESSION["fabrik"]['fromForm'] != $formModel->_id) {
            $this->_oFromForm =& JModel::getInstance( 'Form', 'FabrikModel' );

            $this->_oFromForm->setId( $fromForm );
            $fromFormTable = $this->_oFromForm->getForm();
             
            $fromFormParams = $this->_oFromForm->getParams();

            //fudge the session so that any search form data's keys are the same as the current
            //tables
            if (array_key_exists( $fromForm, $_SESSION['fabrik'] )) {
              foreach ($_SESSION['fabrik'][$fromForm] as $k1=>$v) {
                //only unset if not done previously
                if (strstr( $k1, "___" )) {
                  $k2 = $table->db_table_name . "___" . array_pop(explode("___", $k1));
                } else {
                  $k2 = $table->db_table_name . "___" . $k1;
                }

                if (array_key_exists( $k2, $aData )) {
                  //override search form session info with any posted filters
                  $v = $aData[$k2];
                }
                $_SESSION['fabrik'][$fromForm][$k2] = $v;
              }
            }
            if (array_key_exists( $fromForm, $_SESSION["fabrik"])) {
              $aData = array_merge( $aData, $_SESSION["fabrik"][$fromForm] );
            }
          }
        }
      }
    }

    //store filters
    if (JRequest::getVar('task') != 'processForm') {
      $request =& JRequest::get( 'request' );
      foreach ($request as $key=>$val) {
        if ($formModel->hasElement( $key )) {

          // @since 2.0b3
          //$$$ rob use _clear_ in querystring to remove filters from session
          if ($val == '_clear_') {
            if (array_key_exists($key, $_SESSION['fabrik'][$formModel->_id])) {
              unset($_SESSION['fabrik'][$formModel->_id][$key]);
            }
          } else {
            $_SESSION['fabrik'][$formModel->_id][$key] = $val;
          }
        }
      }
    }

    if (array_key_exists($formModel->_id, $_SESSION["fabrik"])) {
      $aData = array_merge( $aData, $_SESSION["fabrik"][$formModel->_id] );
    }
    return $aData;
  }

  /**
   * get the table's data
   *
   * @return array of objects (rows)
   */

  function getData()
  {
    $pluginManager =& $this->getPluginManager();
    $pluginManager->runPlugins( 'onPreLoadData', $this, 'table' );
    if (!is_null( $this->_data )) {
      return $this->_data;
    }
    $fabrikDb =& $this->getDb();
    // big query modifier??
    //$fabrikDb->setQuery('SET SESSION SQL_BIG_SELECTS=1');
    //$fabrikDb->query();

    $query 		= $this->_buildQuery();
    $nav 			=& $this->getPagination();
    // $$$ hugh - once we have a few join elements, our select statements are
    // getting big enough to hit default select length max in MySQL.  Will need
    // to add this, maybe as a global option?
    /*
    $fabrikDb->setQuery("SET OPTION SQL_BIG_SELECTS=1");
    $fabrikDb->query();
    */
    $fabrikDb->setQuery( $query, $nav->limitstart, $nav->limit );
    FabrikHelperHTML::debug( $fabrikDb->getQuery(), 'table GetData' );
    $this->_data =  $fabrikDb->loadObjectList();
    if ($fabrikDb->getErrorNum() != 0) {
      JError::raiseNotice( 500,  'getData: ' . $fabrikDb->getErrorMsg() );
    }
    //append the cursor & total to the data
    // $$$rob - do we really need to do this? need to test record srolling without it
    if ($this->_outPutFormat == 'html' || $this->_outPutFormat == 'raw') {
      for ($i=0; $i<count($this->_data); $i++) {
        $this->_data[$i]->_cursor = $i + $nav->limitstart;
        $this->_data[$i]->_total = $nav->total;
      }
    }
    $this->formatData( $this->_data );
    //$pluginManager =& JModel::getInstance( 'Pluginmanager', 'FabrikModel' );
    //$pluginManager =& $this->getPluginManager();
    $pluginManager->runPlugins( 'onLoadData', $this, 'table' );
    return $this->_data;
  }

  /**
   * run the table data through element filters
   *
   * @param array $data
   */

  function formatData( &$data )
  {
    jimport( 'joomla.filesystem.file' );
    $form =& $this->getForm();
    $tableParams =& $this->getParams();
    $table =& $this->getTable();
    $pluginManager =& $this->getPluginManager();
    $method 				= 'renderTableData_' . $this->_outPutFormat;
    $this->_aLinkElements = array();

    // $$$ hugh - temp foreach fix
    $groups = $form->getGroupsHiarachy();

    foreach ($groups as $groupModel) {
      $elementModels =& $groupModel->getPublishedElements();
      foreach ($elementModels as $elementModel ) {
        $e =& $elementModel->getElement();
        $elementModel->setContext( $groupModel, $form, $this );
        $params =& $elementModel->getParams();
        $col 	= $elementModel->getFullName( false, true, false );

        //check if there is  a custom out put handler for the tables format
        // currently supports "renderTableData_csv", "renderTableData_rss", "renderTableData_html", "renderTableData_json"
        if (!empty( $data ) && array_key_exists( $col, $data[0] )) {
          if (method_exists( $elementModel, $method )) {
            for ($i=0; $i<count( $data ); $i++) {
              $thisRow = $data[$i];
              $coldata = $thisRow->$col;
              $d = $elementModel->$method( $coldata, $col, $thisRow );
              $data[$i]->$col = $this->_addLink( $d, $elementModel, $thisRow );
            }

          } else {
            $ec = count( $data );
             
            //$$$rob http://fabrikar.com/forums/showthread.php?t=8995
            // change suggested by Francesco - seems to work fine for rendering cascading dropdowns in tables
            /*for ($i=0; $i< $ec; $i++) {
             $thisRow = $data[$i];
             $coldata = $thisRow->$col;
             $rawCol = $col . "_raw";
             if (array_key_exists( $rawCol, $thisRow )) {
             $d = $elementModel->renderRawTableData( $coldata, $thisRow );
             } else {
             $d = $elementModel->renderTableData( $coldata, $thisRow );
             }


             $data[$i]->$col = $this->_addLink( $d, $elementModel, $thisRow );
             }*/

            for ( $i=0; $i< $ec; $i++ ) {
              $thisRow = $data[$i];
              $coldata = $thisRow->$col;
              $d = $elementModel->renderTableData( $coldata, $thisRow );
              $data[$i]->$col = $this->_addLink( $d, $elementModel, $thisRow );
              $rawCol = $col . "_raw";
              if (!array_key_exists( $rawCol, $thisRow)){
                $data[$i]->$rawCol = $elementModel->renderRawTableData( $coldata, $thisRow );
              }
            }

            // run a final function for each fo the elements (basically here to
            // avoid you doing extraneous sql calls in renderTableData
            // ie create sql query in rendertabledata, then run it in mergeTableData
            // currently used for advanced table joins only
            $elementModel->mergeTableData( $data, $this );
          }
        }
      }
    }
    $this->_aGroupInfo = array();
    $groupTitle = array();

    $this->grouptemplates = array();
    //check if the data has a group by applied to it
    if ($table->group_by != '' && $this->_outPutFormat != 'csv') {
      $w = new FabrikWorker();
      $groupTemplate = $tableParams->get('group_by_template');
      $groupedData = array();
      $thisGroupedData = array();
      $groupBy = $table->group_by;
      //see if we can use a raw value instead
      if (!empty( $data ) && array_key_exists( $groupBy . "_raw", $data[0] )) {
        $groupBy = $groupBy . "_raw";
      }
      $groupTitle = null;
      $aGroupTitles = array();
      $groupId = 0;
      for ($i=0; $i <count( $data ); $i++) {
        if (!in_array( $data[$i]->$groupBy , $aGroupTitles )) {
          $aGroupTitles[] = $data[$i]->$groupBy;
          $grouptemplate = $w->parseMessageForPlaceHolder( $groupTemplate, JArrayHelper::fromObject($data[$i]) );
          $this->grouptemplates[$data[$i]->$groupBy] = nl2br ($grouptemplate);
          $groupedData[$data[$i]->$groupBy] = array();
        }
        $data[$i]->_groupId = $data[$i]->$groupBy;
        $gKey = $data[$i]->$groupBy;
        //	if the group_by was added in in getAsFields remove it from the returned data set (to avoid mess in package view)
        if ($this->_group_by_added) {
          unset( $data[$i]->$groupBy );
        }
        if ($this->_temp_db_key_addded) {
          $k = $table->db_primary_key;;
        }
        $groupedData[$gKey][] = $data[$i];

      }
      $data = $groupedData;
    } else {
      for ($i=0; $i<count( $data ); $i++) {
        if ($this->_temp_db_key_addded ){
          $k = $table->db_primary_key;
        }
      }
      //make sure that the none grouped data is in the same format
      $data = array( $data );
    }

    if ($this->_outPutFormat != 'pdf' && $this->_outPutFormat != 'csv' && $this->_outPutFormat != 'feed') {
      $this->addSelectBoxAndLinks( $data );
      FabrikHelperHTML::debug( $data, 'table:data' );
    }
  }

  /**
   * add the select box and various links into the data array
   * @param array table row objects
   */

  function addSelectBoxAndLinks( &$data )
  {
    global $Itemid;
    $table 		=& $this->getTable();
    $db 			=& JFactory::getDBO();
    $params 	=& $this->getParams();
    $nextview = ($this->canEdit()) ? "form" : "details";
    $tmpKey 	= '__pk_val';

    $aExisitngLinkedTables 	= $params->get( 'linkedtable', '', '_default', 'array' );
    $aExisitngLinkedForms 	= $params->get( 'linkedform', '', '_default', 'array' );
    $linkedform_linktype 		= $params->get( 'linkedform_linktype', '', '_default', 'array' );
    $linkedtable_linktype 	= $params->get( 'linkedtable_linktype', '', '_default', 'array' );
    $aExistingTableHeaders 	= $params->get( 'linkedtableheader', '', '_default', 'array' );
    $aExistingFormHeaders 	= $params->get( 'linkedformheader', '', '_default', 'array' );

    //get a list of fabrik tables and ids for view table and form links
    $linksToForms =  $this->getLinksToThisKey();
    $action = ($this->_admin) ? "task" : "view";
    $sql = "SELECT id, label FROM #__fabrik_tables";
    $db->setQuery( $sql );
    $aTableNames = $db->loadObjectList( 'label' );
    $cx = count($data);
    $viewLinkAdded = false;

    $detailsLink = $params->get( 'detailurl' );
    //for ($x=0; $x<$cx; $x++) { //if grouped data then the key is not numeric

    foreach ($data as $groupKey=>$group) {
      //$group =& $data[$key]; //Messed up in php 5.1 group positioning in data became ambiguous
      $cg = count( $group );
      for( $i=0; $i < $cg; $i++ ){
        $row =& $data[$groupKey][$i];
        //done each row as its result can change
        $canEdit = $this->canEdit( $row );
        $nextview = $canEdit ? "form" : "details";
        $pKeyVal = (array_key_exists( $tmpKey, $row )) ? $row->$tmpKey : '';
        $row->fabrik_delete =  ($this->canSelectRows()) ?  '<input type="checkbox" id="id_'.$row->_cursor .'" name="ids['.$row->_cursor.']" value="' . $pKeyVal . '" />' : '';

        //add in some default links if no element choosen to be a link
        $row->fabrik_edit_url = '';

        //if (empty( $this->_aLinkElements ) && ($this->canView( $row ) || $canEdit)){
        if (empty( $this->_aLinkElements ) && ($this->canView( $row ) || $canEdit)){
          $link = $this->viewDetailsLink2( $pKeyVal, $data[$groupKey][$i] );//dont use $row as it generates a pas by ref error
          if ($canEdit == 1) {
            $row->fabrik_edit = "<a class='fabrik___rowlink' href='$link'>" . JText::_('EDIT') . "</a>";
            $row->fabrik_edit_url =$link;
          } else {
            if($this->canViewDetails()){
              $viewLinkAdded = true;
              $row->fabrik_edit = "<a class='fabrik___rowlink' href='$link'>" . JText::_('VIEW') . "</a>";
              $row->fabrik_edit_url =$link;
            } else {
              $row->fabrik_edit = '';
            }
          }
        }
        //@TODO: test if all of these are necessary
        $row->fabrik_view_url = '';
        if ($this->canViewDetails()) {
          // $$$ was $action=form but if the edit is only avaible on certain rows this wouldnt work

          $link = $this->viewDetailsLink2( $pKeyVal, $data[$groupKey][$i] ); //dont use $row as it generates a pas by ref error
          $row->fabrik_view_url = $link;
          $row->fabrik_view = "<a class='fabrik___rowlink' href='$link'>" . JText::_('VIEW') . "</a>";
        }

        if ($this->canViewDetails() && $params->get( 'detaillink' ) == '1' && !$viewLinkAdded ) {

          $row->__details_link = $this->viewDetailsLink( $pKeyVal );
          //test!
          //$row->__details_link = $row->fabrik_view; // if you can edit the form then this link is currently the edit link and not the view link
        }
        // create columns containing links which point to tables associated with this table
        $joinsToThisKey = $this->getJoinsToThisKey();
        $f = 0;
        foreach ($joinsToThisKey as $element) {
          $linkedTable 	= array_key_exists($f, $aExisitngLinkedTables) ? $aExisitngLinkedTables[$f] : false;
          $popUpLink 		= array_key_exists($f, $linkedtable_linktype) ? $linkedtable_linktype[$f] : false;
          if ($linkedTable != '0') {
            if ($element->tablelabel == $table->label) { //if the link points to the same table
              $thiskey 	= $table->db_table_name.'.'.$this->_oTable->_tbl_key;
              $key 		= $element->element_name;
              $x 			= $element->element_name;
              $val 		= $row->$thiskey;
            } else {
              $linkKey	= $element->db_table_name . "___" . $element->name;
              $key 		= $linkKey . "_table_heading";
              $val 		= $pKeyVal;
            }
            $element->table_id = ( array_key_exists( $element->tablelabel, $aTableNames)) ?  $aTableNames[$element->tablelabel]->id : '';
            $group[$i]->$key = $this->viewDataLink( $popUpLink, $element->table_id, $row, $linkKey, $val );
          }
          $f ++;
        }

        $f = 0;
        //create columns containing links which point to forms assosciated with this table
        foreach ($linksToForms as $element) {
          // $$$ hugh - this line was raising "Object of class stdClass could not be converted to int" warnings
          //if ($element != '') {
          $linkedForm 	= array_key_exists( $f, $aExisitngLinkedForms ) ? $aExisitngLinkedForms[$f] : false;
          $popUpLink 		= array_key_exists( $f, $linkedform_linktype ) ? $linkedform_linktype[$f] : false;

          // $$$ hugh @TODO - rob, can you check this, I added this line,
          // but the logic applied for $val in the linked table code above seems to be needed?
          // http://fabrikar.com/forums/showthread.php?t=9535
          $val = $pKeyVal;
          if ($linkedForm !== '0') {
            if (is_object( $element )) {
              //$$$rob moved these two lines here as there were giving warnings since Hugh commented out the if ($element != '') {
              $linkKey	= @$element->db_table_name . "___" . @$element->name;
              $key 		= $linkKey . "_form_heading";

              $group[$i]->$key = $this->viewFormLink( $popUpLink, $element->table_id, $element->form_id, $row, $linkKey, $val );
            }
          }
          //}
          $f ++;
        }
      }
    }
  }

  /**
   * returns an edit or view URL depending on the table state
   *
   * @param string $pKeyVal row's primary key value
   * @param object $row active table row
   * @return string url
   */

  function viewDetailsLink2( $pKeyVal, &$row )
  {
    global $Itemid;
    $params =& $this->getParams();
    $detailsLink = $params->get( 'detailurl' );
    $table =& $this->getTable();
    $nextview = $this->canEdit( $row )? "form" : "details";
    $action = ($this->_admin) ? "task" : "view";
    if (in_array( $this->_outPutFormat, array( 'json', 'raw', 'ajax' ) )) {
      $link = "#";
    } else {
      if ($detailsLink == ''){
        $link = JRoute::_("index.php?option=com_fabrik&c=form&$action=$nextview&Itemid=$Itemid&fabrik=" . $table->form_id . "&rowid=$pKeyVal&tableid=" .$this->_id . "&fabrik_cursor=" . $row->_cursor . "&fabrik_total=" . $row->_total );
      } else {
        $link = htmlspecialchars($detailsLink);
        $link = $this->parseMessageForRowHolder($link, JArrayHelper::fromObject($row));
        if (strstr($link,'?')) {
          $link .= '&amp;rowid=' . $pKeyVal;
        }
        else {
          $link .= '?rowid=' . $pKeyVal;
        }
        $link = JRoute::_($link);
      }
    }
    return $link;
  }

  /**
   * creates the html <a> link allowing you to edit other forms from the table
   * E.g. Faceted browsing: those specified in the table's "Form's whose primary keys link to this table"
   *
   * @param bol $popUp
   * @param int $tableid
   * @param int $formid
   * @param object $row
   * @param string $key
   * @param string $val
   * @return string <a> html part
   */

  function viewFormLink( $popUp = false, $tableid = null, $formid = null, &$row, $key = '', $val = '', $usekey = false )
  {
    global $Itemid;
    if (is_null($tableid)) {
      $table = $this->getTable();
      $tableid = $table->id;
    }

    if(is_null($formid)){
      $form = $this->getForm();
      $formid = $form->id;
    }
    $facetTabel = $this->_facetedTable( $tableid );
    if (!$facetTabel->canAdd()) {
      return '<div style="text-align:center"><a title="'.JText::_('Insufficient access rights. Please login').'"><img src="media/com_fabrik/images/login.png" alt="'.JText::_('iInsufficient access rights. Please login').'" /></a></div>';
    }
    if ($this->_admin){
      $bits[] = "c=form";
      $bits[] = "task=form";
      $bits[] = "cid=$tableid";
    } else {
      $bits[] = "Itemid=$Itemid";
      $bits[] = "view=form";
      $bits[] = "tableid=$tableid";

    }
    $bits[] = "fabrik=$formid";

    // $$$ hugh - change in fabrikdatabasejoin getDefaultValue() means we have to append _raw to key name
    if ($key != '') {
      $bits[] = "{$key}_raw=$val";
    }

    if (!is_null($row)) {
      $bits[] = "fabrik_cursor={$row->_cursor}";
      $bits[] = "fabrik_total={$row->_total}";
    }

    if ($popUp) {
      $bits[] = "tmpl=component";
    }

    if ($usekey and $key != '' and !is_null($row)) {
      $bits[] = "usekey=" . FabrikString::shortColName( $key );
      $bits[] = "rowid=" . $row->__pk_val;
    }

    $url = "index.php?option=com_fabrik&";

    $url .= implode("&", $bits);
    $url = JRoute::_($url);

    if ($popUp) {
      FabrikHelperHTML::mocha( 'a.popupwin' );
      $url = '<a rel="{\'maximizable\':true,\'title\':\''.JText::_('ADD').'\'}" href="'. $url.'" class="popupwin">'. JText::_('ADD') .'</a>';
    } else {
      $url =  "<a href=\"$url\">" .  JText::_('ADD'). "</a>";
    }
    $url = "<span class='addbutton'>$url</span></a>";
    return $url;
  }

  /**
   * get one of the current tables facet tables
   *(used in tables that link to this tables links)
   * @param int table id
   * @return object table
   */

  function _facetedTable( $id )
  {
    if (!isset( $this->facettables )) {
      $this->facettables = array();
    }
    if (!array_key_exists( $id, $this->facettables )) {
      $this->facettables[$id] =& JModel::getInstance( 'table', 'FabrikModel' );
      $this->facettables[$id]->setId( $id );
    }
    return $this->facettables[$id];
  }
  /**
   * build the link (<a href..>) for viewing table data
   *
   * @param bol $popUp
   * @param int $tableid
   * @param object $row
   * @param string $key
   * @param string $val
   * @return strng
   */

  function viewDataLink( $popUp = false, $tableid = null, &$row, $key = '', $val = ''  )
  {
    global $Itemid;
    if ($this->_outPutFormat == 'json' || $this->_outPutFormat == 'raw' || $this->getPostMethod() == 'ajax') {
      return '#';
    }

    $action = ($this->_admin) ? "task" : "view";
    $url = "index.php?option=com_fabrik&";
    //$bits[] = "action=$action";

    if (is_null($tableid)) {
      $table = $this->getTable();
      $tableid = $table->id;
    }
    $facetTabel = $this->_facetedTable( $tableid );
    if (!$facetTabel->canView()) {
      return '<div style="text-align:center"><a title="'.JText::_('Insufficient access rights. Please login').'"><img src="media/com_fabrik/images/login.png" alt="'.JText::_('iInsufficient access rights. Please login').'" /></a></div>';
    }
    if ($this->_admin){
      $bits[] = "c=table";
      $bits[] = "task=viewTable";
      $bits[] = "cid=$tableid";
    } else {
      $bits[] = "Itemid=$Itemid";
      $bits[] = "view=table";
      $bits[] = "tableid=$tableid";
    }

    if ($key != '') {
      //$bits[] = "{$key}[value]=$val";
      $bits[] = "{$key}=$val";
    }
    if (!is_null($row)) {
      $bits[] = "fabrik_cursor={$row->_cursor}";
      $bits[] = "fabrik_total={$row->_total}";
    }
    $bits[] = 'limitstart=0';

    if ($popUp) {
      $bits[] = "tmpl=component";
    }
    $url .= implode("&", $bits);
    $url = JRoute::_($url);

    if ($popUp) {
      FabrikHelperHTML::mocha( 'a.popupwin' );
      $url = '<a rel="{\'maximizable\':true,\'title\':\''.JText::_('VIEW').'\'}" href="'. $url.'" class="popupwin">'. JText::_('VIEW') .'</a>';
    } else {
      $url =  "<a href=\"$url\">" .  JText::_('VIEW'). "</a>";
    }
    return $url;
  }

  /**
   * add a custom link to the element data
   *
   * @param string element $data
   * @param object element
   * @param object of all row data
   */

  function _addLink( $data, &$elementModel, &$row )
  {
    global $Itemid;
    if ($this->_outPutFormat == 'csv') {
      return $data;
    }

    $nextview = ($this->canEdit( $row )) ? "form" : "details";
    $params 	=& $elementModel->getParams();
    $element 	=& $elementModel->getElement();
    $table 		=& $this->getTable();
    $customLink = $params->get( 'custom_link' );

    if ($element->link_to_detail == '1' && ( $this->canEdit( $row ) || $this->canView( $row )) && $element->show_in_table_summary == 1) {
      if ($customLink == '') {
        $this->_aLinkElements = $element->name;
        if ($this->getPostMethod() == 'post') {
          $primaryKeyVal = $this->getKeyIndetifier( $row );
          if ($this->_admin) {
            $link = JRoute::_( "index.php?option=com_fabrik&c=form&task=$nextview&fabrik=" . $table->form_id . "$primaryKeyVal&fabrik_cursor=" . @$row->_cursor . "&fabrik_total=" . @$row->_total . "&tableid=" .$this->_id );
          } else {
            $link = JRoute::_( "index.php?option=com_fabrik&c=form&view=$nextview&Itemid=$Itemid&fabrik=" . $table->form_id . "$primaryKeyVal&fabrik_cursor=" . @$row->_cursor . "&fabrik_total=" . @$row->_total . "&tableid=" .$this->_id );
          }
        } else {
          $link = '#';
        }
        //try to remove any previously entered links
        $data = preg_replace( '/<a(.*?)>|<\/a>/', '', $data );
        $data = "<a class='fabrik___rowlink' href='$link'>$data</a>";
      } else {
         
        $array = JArrayHelper::fromObject( $row );
        $array['rowid'] = $array['__pk_val'];
        $customLink = $this->parseMessageForRowHolder($customLink, $array);
        //try to remove any previously entered links
        $data = preg_replace( '/<a(.*?)>|<\/a>/', '', $data );
        $data = "<a class='fabrik___rowlink' href='$customLink'>$data</a>";
      }
    }
    return $data;
  }

  /**
   * get query to make records
   * @return string sql
   */

  function _buildQuery()
  {
    $conf =& JFactory::getConfig();
    if ($conf->getValue( 'config.caching' )) {
      $query = $this->getCacheQuery();
    } else {
      $query = $this->_buildQuerySelect();
      $query .= $this->_buildQueryJoin();
    }
    //incfilters set when exporting as CSV
    $query .= $this->_buildQueryWhere( JRequest::getVar('incfilters', 1) );
    $query .= $this->_buildQueryOrder();
    return $query;
  }

  function getCacheQuery()
  {
    $conf =& JFactory::getConfig();
    $cache = &JFactory::getCache( 'com_fabrik' );
    $callback = array('FabrikCacheHelper', 'tableSelect');
    $contents =  $cache->get( $callback, array($this), 'table'.$this->_id );
    return $contents;
  }


  /**
   * get the select part of the query
   *
   * @return string
   */

  function _buildQuerySelect()
  {
    $form =& $this->getForm();
    $table =& $this->getTable();
    $form->getGroupsHiarachy( true );
    $fields =& $this->getAsFields();
    $sfields = (empty( $fields )) ? '' : implode( ", \n ", $fields ) . "\n ";
    //$$$rob added raw as an option to fix issue in saving calendener data
    if (trim( $table->db_primary_key ) != '' && ( $this->_outPutFormat == 'raw' || $this->_outPutFormat == 'html' || $this->_outPutFormat == 'feed' ) ) {
      if ($this->isView()) {
        //$strPKey = ''; //view dont have primary key!
        // $$$ hugh - well, they do in a Fabrik sense!
        $sfields .= ", ";
        $strPKey = $table->db_primary_key . " AS __pk_val\n";
      } else {
        $sfields .= ", ";
        $strPKey = $table->db_primary_key . " AS __pk_val\n";
      }
      $query = 'SELECT DISTINCT ' . $sfields . $strPKey;
    } else {
      $query = 'SELECT DISTINCT ' . trim($sfields, ", \n")  . "\n";
    }
    $query .= " FROM `$table->db_table_name` \n" ;
    return $query;
  }

  /**
   * get the part of the sql statement that orders the table data
   * @return string ordering part of sql statement
   */

  function _buildQueryOrder()
  {
    $params = $this->getParams();
    $table =& $this->getTable();
    $db =& JFactory::getDBO();
    if ($this->_outPutFormat == 'feed' )
    {
      $dateColId = (int)$params->get( 'feed_date', 0 );
      $db->setQuery( 'SELECT name FROM #__fabrik_elements WHERE id = '.$dateColId );
      $dateCol = '`'.$table->db_table_name.'`.`'.$db->loadResult().'`';
      if ($dateColId !== 0) {
        $this->order_dir = 'DESC';
        $this->order_by 	= $dateCol;
        return "\n ORDER BY $dateCol DESC";
      }
    }
    $session =& JFactory::getSession();

    $postOrderBy =  JRequest::getVar( 'orderby', '' );
    $postOrderBy = str_replace( ".", "___", $postOrderBy );

    $postOrderDir = JRequest::getVar( 'orderdir', '' );
    $arOrderVals = array('asc', 'desc', '-');
    $orderByFields =& $this->getOrderByFields();
    if (in_array( $postOrderDir, $arOrderVals )) {
      $context			= 'com_fabrik.table'. $this->_id.'.order.'.$postOrderBy;
      $session->set( $context, $postOrderDir );
    }

    //build the order by statement from the session
    $strOrder = '';
    foreach ($orderByFields as $field) {
      $context			= 'com_fabrik.table'. $this->_id.'.order.'.str_replace('.', '___', $field);
      $dir = $session->get( $context );
      if ($dir != '' && $dir != '-' && trim( $dir ) != 'Array' ) {
        $strOrder == '' ? $strOrder = "\n ORDER BY " : $strOrder .= ',';
        $strOrder .= " $field $dir";
      }
    }

    //if nothing found in session use default ordering
    if ($strOrder == '') {
      if ($table->order_by != '') {
        $table->order_dir != ''? $dir = $table->order_dir : $dir = 'desc';
        $field = str_replace( "___", ".", $table->order_by );
        $field = explode( ".", $field );
        if (count($field) == 2 && $field[1] != '') {
          $field = "`" . $field[0] . "`.`" . $field[1] . "`";
          $strOrder = "\n ORDER BY $field $dir";
        } else {
          $strOrder = "\n ORDER BY $table->order_by ";
        }
      }
    }

    // apply group ordering
    // @TODO - explain something to hugh!  Why is this "group ordering"?  AFAICT, it's just a secondary
    // order by, isn't specific to the Group By feature in any way?  So why not just put this option in
    //
    $groupOrderBy = $params->get( 'group_by_order' );
    if ($groupOrderBy != '' ) {
      $groupOrderDir = $params->get( 'group_by_order_dir' );
      $strOrder == '' ? $strOrder = "\n ORDER BY " : $strOrder .= ',';
      $strOrder .= FabrikString::safeColName($groupOrderBy) ." $groupOrderDir ";
    }
    return $strOrder;
  }

  /**
   * get the part of the sql query that creates the joins
   * used when building the table's data
   *
   * @return string join sql
   */

  function _buildQueryJoin()
  {
    if (isset( $this->_joinSQL )) {
      return $this->_joinsSQL;
    }
    $statements = array();
    $table =& $this->getTable();

    $selectedTables[] = $table->db_table_name;
    $return = '';
    $joins 	=& $this->getJoins();
    $tableGroups = array();
    foreach ($joins as $join) {
      //used to bypass user joins if the table connect isnt the Joomla
      //connection
      if (!$join->canUse) {
        continue;
      }
      if ($join->join_type == '') {
        $join->join_type = 'LEFT';
      }
      $sql = strtoupper($join->join_type) ." JOIN `$join->table_join`" ;

      $k = "`$join->keytable`.`$join->table_key`";
      FabrikString::safeColName($k);

      if ($join->table_join_alias == '') {
        $on = "`$join->table_join`.`$join->table_join_key`";
        FabrikString::safeColName($on);
        $sql .=	" ON $on = $k \n";
      } else {
        $on = "`$join->table_join_alias`.`$join->table_join_key`";
        FabrikString::safeColName($on);
        $sql .= " AS `$join->table_join_alias` ON $on = $k \n";
      }
      // try to order join statements to ensure that you are selecting from tables that have
      //already been included (either via a previous join statement or the table select statement)
      if (in_array( $join->keytable, $selectedTables )) {
        $return .= $sql;
        $selectedTables[] = $join->table_join;
      } else {
        //didnt find anything so defer it till later

        //$statements[$join->keytable] = $sql;
        //$$$rob - sometimes the keytable is the same for 2 deferred joins
        //in this case the first join is incorrectly overwritten in the $statements array
        //keying on join->id should solve this
        $statements[$join->id] = array($join->keytable, $sql);
      }

      //go through the deferred join statements and see if their table has now been selected
      foreach ($statements as $joinid => $ar) {
        $t = $ar[0];
        $s = $ar[1];
        if (in_array( $t, $selectedTables )) {
          if (!strstr( $return, $s ) ){ //$$$rob test to avoid duplicate join queries
            //ahhh now its there so we can add the statement
            $return .= $s;
            unset( $statements[$t] );
          }
        }
      }
    }
    return $return;
  }

  /**
   * get the part of the sql query that relates to the where statement
   *
   * @param bol $incFilters
   * @return string where query
   */

  function _buildQueryWhere( $incFilters = true )
  {
    if (isset( $this->_whereSQL )) {
      return $this->_whereSQL[$incFilters];
    }
    $aFilters	=& $this->getFilterArray();
    $params =& $this->getParams();

    # $$$ hugh - added option to 'require filtering', so if no filters specified
    # we return an empty table.  Only do this where $inFilters is set, so we're only doing this
    # on the main row count and data fetch, and things like
    # filter dropdowns still get built.
    if ($incFilters && $params->get('require-filter', false) && empty($aFilters)) {
      return " WHERE 1 = -1 ";
    }

    //test - set table search mode to search all data (search-mode = OR) in which case
    // there is only one search box for the table. (Also for search plugin test setting
    // search-mode to OR.
    //default = AND which is the standard filter by individual element
    $searchMode = $params->get( 'search-mode', 'AND' );

    if (isset( $params ) && $this->_togglePreFilters == 0) {
      $aPrefilters =& $this->getPrefilterArray();
    } else {
      $aPrefilters = array();
    }
    $aOrSQL = array();
    $aFoundOrGroupings = array();
    $i = 0;

    $aSQLBits = array();
    FabrikHelperHTML::debug( $aFilters, 'aFilters' );
    FabrikHelperHTML::debug( $aPrefilters, 'prefilters' );

    if (is_array( $aFilters )) {
      /* stores sql for or statements */
      $c = 0;
      foreach ($aFilters as $key=>$val) {
        /*work through or columns first to build sql ( col = val or col = val...)
         * then remove them from the rest of the array
         */
        if (isset( $val['aOrCols'] )) {

          $aOrColumns = $val['aOrCols'] ;
          $filterVal = isset( $val['value'] ) ? $val['value'] : '';
          /* check if we've already performed it elsewhere */
          $done 		= false;
          foreach ($aFoundOrGroupings as $aFoundOrGroupSet) {
            $a = filter_unused( $aFoundOrGroupSet, $aOrColumns );
            if (empty( $a )){
              $done = true;
            }
          }
          if (!$done) {
            /*
             * ok we havent processed this group -
             * lets add it to the groups the we have processed
             */
            $aFoundOrGroupings[] = $aOrColumns;
            /*
             *now lets build that query string!
             */
            $orSql ='(';
            foreach ($aOrColumns as $col) {
              $col = FabrikWorker::getDbSafeName( $col );
              $orSql .= "`$col` = '$filterVal' OR " ;
            }
            $orSql = substr($orSql, 0, strlen($orSql)-3) . ')';
            $aOrSQL[] = $orSql;
          }
          /* ok, even if its been done before we still need to remove it from the filter array */
          unset ( $aFilters[$key] );
          $c++;
        }
      }


      foreach ($aFilters as $key=>$val) {
        $filterType 			= isset( $val['type'] )  ? $val['type']: 'dropdown';
        $filterVal 				= isset( $val['value'] ) ? $val['value'] : '';
        $filterExactMatch = isset( $val['match'] ) ? $val['match'] 	: '';
        $fullWordsOnly 		= isset( $val['full_words_only'] )? $val['full_words_only'] : '0';

        if (array_key_exists( $key, $aPrefilters )){
          if (array_key_exists( 'sqlCond' , $aPrefilters[$key] )) {
            $sqlCond = "( " .$val['sqlCond'] . " AND " . $aPrefilters[$key]['sqlCond'] . " )";
          } else {
            //$$$rob used for prefilter and table - "Tables with database join elements linking to this table " pointing to same prefilter opt
            $sqlCond = "( " .$val['sqlCond'];
            foreach ($aPrefilters[$key] as $tmpC) {
              $sqlCond .= " $searchMode " . $tmpC['sqlCond'];
            }
            $sqlCond .= " )";
          }
          unset( $aPrefilters[$key] );
        } else {
          $sqlCond = $val['sqlCond'];
        }

        if ( $filterVal != "" ) {
          if(!empty($aSQLBits)){
            $aSQLBits[] = " $searchMode ";
          } else {
            $aSQLBits[] = " ( ";
          }
          $aSQLBits[] = $sqlCond;
          $i ++;
        }
      }
    }
    if (!empty( $aSQLBits )) {
      $aSQLBits[] = " )) ";
    }
    //add in any prefiltres not duplicated by filters
    //put them at the beginning of query as well

    $aSQLBits2 = array();
    $ingroup = false;
    for ($p = 0; $p < count( $aPrefilters ); $p++) {
      $n = $p +1 ;
      $gstart = "";
      $gend = "";
      if (array_key_exists( $n, $aPrefilters )) {
        if ( $aPrefilters[$n]['grouped_to_previous'] == 1) {
          $gstart = "(";
          $ingroup = true;
        } else {
          if($ingroup){
            $gend = ')';
            $ingroup = false;
          }
        }
      } else {
        if($ingroup){
          $gend = ')';
          $ingroup = false;
        }
      }
      $aSQLBits2[] = $aPrefilters[$p]['concat'];
      $aSQLBits2[] = $gstart.$aPrefilters[$p]['sqlCond'].$gend;
    }
    // $$$rob work out the where statment minus any filters (so only include prefilters)
    // this is needed to ensure that the filter drop downs contain the correct info.

    $sqlNoFilter = '';
    if (!empty( $aSQLBits2 )) {
      $aSQLBits2[0] = "WHERE";
      $sqlNoFilter .= implode(  ' ', $aSQLBits2 );

      if (count( $aOrSQL ) > 0) {
        if (empty( $aSQLBits2 )) {
          $sqlNoFilter .= " WHERE " . implode( " $searchMode ", $aOrSQL );
        } else {
          $sqlNoFilter .=  ' AND ' . implode( " $searchMode ", $aOrSQL );
        }
      }
    }

    //apply advanced filter query
    $advancedFilter = JRequest::getVar('advancedFilterContainer', array('value' => ''), 'default', 'none', 2);
    $sql = '';
    // $$$ rob - TESTING  searchbot with pre-filters, as it results in
    // WHERE table.thing = 'prefilter_thing' OR (table.element LIKE '%searchword') OR ...
    // ... but we need ...
    // WHERE table.thing = 'prefilter_thing' AND ((table.element LIKE '%searchword%') OR ...)
    if (!empty( $aSQLBits )) {
      $aSQLBits2[] = ' AND ';
    } else {
      if (!empty( $aSQLBits2 )) {
        $aSQLBits[] = ')';
      }
    }

    $aSQLBits = array_merge( $aSQLBits2, $aSQLBits );
    if (!empty( $aSQLBits )) {
      $aSQLBits[0] = "WHERE (";

      $sql .= implode( ' ', $aSQLBits );

      if (count( $aOrSQL ) > 0) {
        // $$$ hugh - surely $aSQLBits will never be empty 'cos we set [0] to WHERE 3 lines above?
        if (empty( $aSQLBits )) {
          $sql .= " WHERE " . implode(" $searchMode ", $aOrSQL );
        } else {
          $sql .=  " $searchMode " . implode( " $searchMode ", $aOrSQL );
        }
      }
    }
    if ($advancedFilter['value'] != '') {
      $sql .= empty( $sql ) ? " WHERE " : " $searchMode ";
      $sql .= trim( trim( $advancedFilter['value'], "AND" ), "OR" );
    }
    $this->_whereSQL = array( '0'=>$sqlNoFilter, '1'=>$sql );
    return $this->_whereSQL[$incFilters];
  }

  /**
   * get a list of the tables columns' order by field names
   * @return array order by names
   */

  function getOrderByFields()
  {
    if (is_null( $this->orderByFields )) {
      $this->orderByFields = array();
    }
    $form 			=& $this->getForm();
    $groups =& $form->getGroupsHiarachy();
    foreach ($groups as $groupModel) {
      $elementModels =& $groupModel->getPublishedElements();
      foreach ($elementModels as $elementModel) {
        $this->orderByFields[] = $elementModel->getOrderByName();
      }
    }
    return $this->orderByFields;
  }

  /**
   * get the part of the table sql statement that selects which fields to load
   *
   * @return array field names to select in getelement data sql query
   */

  function &getAsFields()
  {
    if (is_null( $this->fields )) {
      $this->fields = array();
    }
    if (is_null( $this->asfields )) {
      $this->asfields = array();
    }
    //test to load all fields
    $key = "0";

    if (array_key_exists( $key, $this->asfields )) {
      return $this->asfields[$key];
    }
    $this->asfields[$key] = array();
    $this->fields[$key] = array();

    $form 			=& $this->getForm();
    $table 			=& $this->getTable();
    $aJoinObjs 	=& $this->getJoins();
    $this->_temp_db_key_addded = false;
    $groups =& $form->getGroupsHiarachy();
    foreach ($groups as $groupModel) {
      $table_name = $table->db_table_name;
      $group =& $groupModel->getGroup();
      if ($group->is_join) {
        foreach ($aJoinObjs as $join) {
          //also ignore any joins that are elements
          if (array_key_exists( 'group_id', $join ) && $join->group_id == $group->id && $join->element_id == 0 ) {
            $table_name =  $join->table_join;
          }
        }
      }
      $elementModels =& $groupModel->getPublishedElements();
      foreach ($elementModels as $elementModel) {
        $method = "getAsField_" . $this->_outPutFormat;
        if (!method_exists( $elementModel, $method )) {
          $method = "getAsField_html";
        }
        $elementModel->$method( $this->asfields[$key], $this->fields[$key], $table_name );
      }
    }
    //temporaraily add in the db key so that the edit links work, must remove it before final return
    //	of getData();

    if (!$this->isView()) {
      if (!$this->_temp_db_key_addded && $table->db_primary_key != '') {
        $str = str_replace( '___', '.', $table->db_primary_key ) . " AS " . str_replace( '.', '___', $table->db_primary_key );
        //if we are quoting the primary key in the db then we need to remove these quotes
        $str = str_replace( '`___`', '___', $str );
        //$this->fields[$key][] = $str;
        $this->fields[$key][] = str_replace( '`.`', '___', $table->db_primary_key );
        $this->asfields[$key][] = $table->db_primary_key;
      }
    }
    //for raw data in packages

    if ($this->_outPutFormat == 'raw') {
      $str = str_replace( '___', '.', $table->db_primary_key ) . " AS __pk_val";
      $str = str_replace( '`___`', '___', $str );
      $this->fields[$key][] = $str;
    }

    //end

    $this->_group_by_added = false;
    //if the group by element isnt in the fields add it (otherwise group by wont work)
    if (is_array($this->fields) && array_key_exists($key, $this->fields) && is_array($this->fields[$key]) && !in_array( $table->group_by, $this->fields[$key] ) && trim( $table->group_by ) != '') {
      //test this - asfields as fields were the other way round but that doesnt make sense!
      $this->asfields[$key][] = str_replace( '___', '.', $table->group_by ) . " AS `$table->group_by`";
      $this->fields[$key] = $table->group_by;
      $this->_group_by_added = true;
    }
    return $this->asfields[$key];
  }

  /**
   * checks if the params object has been created and if not creates and returns it
   * @return object params
   */

  function &getParams()
  {
    $table =& $this->getTable();
    if (!isset( $this->_params )) {
      $this->_params = &new fabrikParams( $table->attribs, JPATH_SITE . '/administrator/components/com_fabrik/models/table.xml', 'component' );
    }
    return $this->_params;
  }


  /**
   * Method to set the table id
   *
   * @access	public
   * @param	int	table ID number
   */

  function setId( $id )
  {
    $this->_id		= $id;
  }

  function getId()
  {
    return $this->_id;
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
   * get the table object for the models _id
   *
   * @return object table
   */

  function &getTable( $force = false )
  {
    if ($force || is_null( $this->_table ) || !is_object($this->_table)) {
      JTable::addIncludePath( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_fabrik'.DS.'tables' );
      $this->_table =& JTable::getInstance( 'table', 'Table' );
      $this->_table->load( $this->_id );
    }
    return $this->_table;
  }

  /**
   * load  the database associated with the table
   *@return object database
   */

  function &getDb()
  {
    if (!isset( $this->_oConnDB )) {
      $cnn =& $this->getConnection();
      if (!is_object($cnn)) {
        JError::raiseError( E_ERROR, JText::_( 'FABRIK WAS UNABLE TO LOAD THE DATABASE OBJECT FOR THIS TABLE' ) );
      }
      $this->_oConnDB =& $cnn->getDb();

      if (JError::isError( $this->_oConnDB )) {
        JError::raiseError( E_ERROR, JText::_( 'FABRIK COULD NOT LOAD DATABASE CONNECTION' ) );
      }
    }
    return $this->_oConnDB;
  }

  /**
   * function get the tables connection object
   * sets $this->_oConn to the tables connection
   * @return object connection
   */

  function &getConnection()
  {
    $config =& JFactory::getConfig();
    if (!isset( $this->_oConn )) {
      $table =& $this->getTable();
      $connectionModel =& JModel::getInstance( 'connection', 'FabrikModel' );
      $connId = ( is_null( $table->connection_id ) ) ? JRequest::getVar( 'connection_id', null ) : $table->connection_id;
      $connectionModel->setId( $connId );
      if ($connId == '' || is_null( $connId ) ||  $connId == '-1' ){ //-1 for creating new table
        $connectionModel->loadDefaultConnection();
        $connectionModel->setId( $connectionModel->_connection->id );
      }
      $connection =& $connectionModel->getConnection( );
      // if its the default connection then load from the
      // config file
      //$$$ ROB - NO!!! connections can be set as default and still NOT BE the J db!!
      /*if ($connectionModel->isDefault() ){
       $connection->host = $config->getValue('config.host');
       $connection->user = $config->getValue('config.user');
       $connection->password = $config->getValue('config.password');
       $connection->database = $config->getValue('config.db');
       }*/
      $this->_oConn =& $connectionModel;
    }
    return $this->_oConn;
  }

  /**
   * is the table published
   * Dates are stored as UTC so we can compare them against a date with no offset applied
   * @return bol published state
   */

  function canPublish()
  {
    $table =& $this->getTable();
    $db =& JFactory::getDBO();
    $nullDate = (method_exists( $db, 'getNullDate' )) ? $db->getNullDate() : $this->getNullDate();
    $publishup =& JFactory::getDate( $table->publish_up );
    $publishup = $publishup->toUnix();

    $publishdown =& JFactory::getDate( $table->publish_down );
    $publishdown = $publishdown->toUnix();

    $jnow		=& JFactory::getDate();
    $now		= $jnow->toUnix();
    if ($table->state == '1') {
      if ($now >= $publishup || $table->publish_up == '' || $table->publish_up == $nullDate) {
        if ($now <= $publishdown || $table->publish_down == '' || $table->publish_down == $nullDate) {
          return true;
        }
      }
    }
    return false;

    return $table->state;
  }

  /**
   * access control to determine if the current user has rights to drop data
   * from the table
   * @return bol yes/no
   */

  function canEmpty()
  {
    $params =& $this->getParams();
    if (!is_object( $this->_access ) || !array_key_exists( 'allow_drop', $this->_access )) {
      $this->_access->allow_drop = FabrikWorker::getACL( $params->get( 'allow_drop' ), 'allow_drop' );
    }
    return $this->_access->allow_drop;
  }

  /**
   * check if the user can view the detailed records
   *
   * @return bol
   */

  function canViewDetails()
  {
    $params =& $this->getParams();
    if (!is_object( $this->_access ) || !array_key_exists( 'viewdetails', $this->_access )) {
      $this->_access->viewdetails = FabrikWorker::getACL( $params->get( 'allow_view_details' ), 'viewdetails' );
    }
    return $this->_access->viewdetails;
  }

  /**
   * checks user access for editing records
   * @param object row of data currently active
   * @return bol access allowed
   */

  function canEdit( $row = null )
  {
    $params =& $this->getParams();
    $user = &JFactory::getUser();
    if (!is_null( $row )) {
      $usercol =$params->get( 'allow_edit_details2', '' );
      if ($usercol !=  '') {
        $usercol = FabrikString::safeColNameToArrayKey( $usercol );

        if (!array_key_exists( $usercol, $row)) {
          return false;
        } else {
          if (array_key_exists($usercol . "_raw", $row)) {
            $usercol .= "_raw";
          }
          $myid = $user->get('id');
          //-1 for menu items that link to their own reocrds
          // $$$ hugh - TODO - test - something doesn't look right about this logic!!
          // $$$ hugh - was $row->$usercol, but $row is an array not an object!
          // $$$ hugh - oops, it's an array when coming here from form, object when coming from table!
          if (is_array($row)) {
            $usercol_val = $row[$usercol];
          }
          else {
            $usercol_val = $row->$usercol;
          }
          if (empty($usercol_val)) {
            return false;
          }
          if (intVal($usercol_val) === intVal($myid) || JRequest::getVar('rowid') == -1 ) {
            return true;
          }
          # $$$ hugh - testing making the "or use field" truly an OR, so they can edit
          # if they either have usercol privs or the regular ACL.  i.e. if this test fails,
          # don't reurn false, rather drop through and test regular ACL.
          /*
           else {
           return false;
           }
           */
        }
      }
    }
    if (!is_object($this->_access) || !array_key_exists( 'edit', $this->_access )) {
      $this->_access->edit = FabrikWorker::getACL( $params->get( 'allow_edit_details', 25 ), 'edit' );
    }
    return $this->_access->edit;
  }

  /**
   * checks user access for deleting records
   *
   * @return bol access allowed
   */

  function canDelete()
  {
    $params =& $this->getParams();
    if (!is_object($this->_access) || !array_key_exists( 'delete', $this->_access )) {
      // @TODO - rob, sanity check - shouldn't this be 'delete', not 'edit'??
      $this->_access->delete = FabrikWorker::getACL( $params->get( 'allow_delete', 25 ), 'delete' );
    }
    return $this->_access->delete;
  }

  /**
   * checks user access for importing csv
   *
   * @return bol access allowed
   */

  function canCSVImport()
  {
    $params =& $this->getParams();
    if (!is_object($this->_access) || !array_key_exists( 'csvimport', $this->_access )) {
      if ($params->get( 'csv_import_frontend', 0 ) == 1){//compat with old yes/no (cant do anything about no option as its the same as 'everybody'
        $this->_access->csvimport = true;
      } else {
        $this->_access->csvimport = FabrikWorker::getACL( $params->get( 'csv_import_frontend', 25 ), 'csvimport' );
      }
    }
    return $this->_access->csvimport;
  }

  /**
   * checks user access for exporting csv
   *
   * @return bol access allowed
   */

  function canCSVExport()
  {
    $params =& $this->getParams();
    if (!is_object($this->_access) || !array_key_exists( 'csvexport', $this->_access )) {
      if ($params->get( 'csv_export_frontend', 0 ) == 1){//compat with old yes/no (cant do anything about no option as its the same as 'everybody'
        $this->_access->csvexport = true;
      } else {
        $this->_access->csvexport = FabrikWorker::getACL( $params->get( 'csv_export_frontend', 25 ), 'csvexport' );
      }
    }
    return $this->_access->csvexport;
  }

  /**
   * checks user access for adding records
   *
   * @return bol access allowed
   */

  function canAdd()
  {
    $params =& $this->getParams();
    if (!is_object( $this->_access ) || !array_key_exists( 'add', $this->_access )) {
      $this->_access->add = FabrikWorker::getACL( $params->get( 'allow_add', 25 ), 'add' );
    }
    return $this->_access->add;
  }

  /**
   * check use can view the table
   * @return bol can view or not
   */

  function canView()
  {
    $params =& $this->getParams();
    if (!is_object($this->_access) || !array_key_exists('view', $this->_access)) {
      $this->_access->view = FabrikWorker::getACL( $params->get('access'), 'view' );
    }
    return $this->_access->view;
  }

  /**
   * load the table from the form_id value
   * @param int $formId
   * @return object table row
   */

  function loadFromFormId($formId)
  {
    JTable::addIncludePath( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_fabrik'.DS.'table' );
    $row = JTable::getInstance( 'table', 'Table' );
    $origKey = $row->_tbl_key;
    $row->_tbl_key = "form_id";
    $row->load( $formId );
    $this->_table = $row;
    $row->_tbl_key = $origKey;
    $this->setId( $row->id );
    return $row;
  }

  /**
   * @return array join objects (table rows - not table objects or models)
   */

  function &getJoins()
  {
    global $mainframe;
    if (!isset( $this->_aJoins )) {
      $form =& $this->getForm();
      $form->getGroupsHiarachy();
      $ids = $form->getElementIds();
      $table =& $this->getTable();
      $db =& JFactory::getDBO();
      $sql = "SELECT * FROM #__fabrik_joins WHERE table_id = '$this->_id'";
      if (!empty( $ids )) {
        $sql .= " OR element_id IN ( " . implode(", ", $ids) .")";
      }
      //maybe we will have to order by element_id asc to ensure that table joins are loaded
      //before element joins (if an element join is in a table join then its 'join_from_table' key needs to be updated
      $sql .= " ORDER BY id";
      $db->setQuery( $sql );
      $this->_aJoins = $db->loadObjectList();

      if ($db->getErrorNum()) {
        JError::raiseError( 500, $db->stderr());
      }

      $aliases = array( $table->db_table_name );
      $tableGroups = array();

      //build up the alias and $tableGroups array first
      foreach ($this->_aJoins as $join) {
        $join->canUse = true;
        if ($join->table_join == '#__users' || $join->table_join == $mainframe->getCfg('dbprefix') . 'users'){
          if ($db != $this->getDb()) {
            $join->canUse = false;
          }
        }
        $tablejoin = str_replace( '#__', $mainframe->getCfg('dbprefix'),  $join->table_join );
        if (in_array( $tablejoin, $aliases )) {
          $base = $tablejoin;
          $a = $base;
          $c = 0;
          while (in_array( $a, $aliases )) {
            $a = "{$base}_{$c}";
            $c ++;
          }
          $join->table_join_alias = $a;
        } else {
          $join->table_join_alias = $tablejoin;
        }

        $aliases[] = str_replace( '#__', $mainframe->getCfg('dbprefix'),  $join->table_join_alias );

        if (!array_key_exists( $join->group_id, $tableGroups )) {
          if ($join->element_id == 0) {
            $tableGroups[$join->group_id] = $join->table_join_alias;
          }
        }
      }
      foreach ($this->_aJoins as $join) {
        //if they are element joins add in this tables name as the calling joining table.
        if ($join->join_from_table == '') {
          $join->join_from_table = $table->db_table_name;
        }

        // test case:
        /*
         * you have a talbe that joins to a 2nd table
         * in that 2nd table there is a database join element
         * that 2nd elements key needs to point to the 2nd tables name and not the first
         *
         * e.g. when you want to create a n-n relationship
         *
         * events -> (table join) events_artists -> (element join) artist
         */

        $join->keytable = $join->join_from_table;
        if (!array_key_exists( $join->group_id, $tableGroups )) {

        } else {
          if ($join->element_id != 0) {
            $join->keytable = $tableGroups[$join->group_id];
            //test
            $join->join_from_table = $join->keytable;
          }
        }
      }
    }
    return $this->_aJoins;
  }


  /**
   * gets the field names for the given table
   * @param string table name
   * @return array table fields
   */

  function getDBFields( $tbl = null )
  {
    if (is_null( $tbl )) {
      $table =& $this->getTable();
      $tbl = $table->db_table_name ;
    }
    $tbl = FabrikString::safeColName($tbl);
    if (!isset( $this->_dbFields[$tbl] )) {
      $db =& $this->getDb();
      $db->setQuery( "DESCRIBE $tbl" );
      $this->_dbFields[$tbl] = $db->loadObjectList();
    }
    return $this->_dbFields[$tbl];
  }

  /**
   * add or update a database column via sql
   * @param object element plugin
   * @param string origional field name
   */

  function alterStructure( &$elementModel, $origColName = null )
  {
    $db =& JFactory::getDBO();
    $element =& $elementModel->getElement();
    $pluginManager =& $this->getPluginManager();
    $basePlugIn =& $pluginManager->getPlugIn( $element->plugin, 'element' );
    $fbConfig 	=& JComponentHelper::getParams( 'com_fabrik' );
    $fabrikDb 	=& $this->getDb();
    $table 			=& $this->getTable();
    $tableName 	= $table->db_table_name;
    // $$$ rob base plugin needs to know group info for date fields in non-join repeat groups
    $basePlugIn->_group =& $elementModel->_group;

    //$$$ rob changing the element's int/decimal options wont alter the objtype if we just use the base plugin
    //$objtype 		= $basePlugIn->getFieldDescription();
    $objtype = $elementModel->getFieldDescription();

    $dbdescriptions = $this->getDBFields( $tableName );
    if (!$fbConfig->get( 'fbConf_alter_existing_db_cols', true )) {
      foreach ($dbdescriptions as $f) {
        if ($f->Field == $origColName) {
          $objtype = $f->Type;
        }
      }
    }
    if (!is_null( $objtype )) {
      foreach ($dbdescriptions as $dbdescription) {
        $fieldname = strtolower( $dbdescription->Field );
        if (strtolower( $element->name ) == $fieldname && strtolower( $dbdescription->Type ) == strtolower( $objtype )) {
          return;
        }
        $exitingfields[] = $fieldname;
      }
      $lastfield = $fieldname;
      $name = $element->name; //none quoted version
      FabrikString::safeColName( $element->name );
      FabrikString::safeColName( $tableName );
      FabrikString::safeColName( $lastfield );
      // @TODO - Rob, please sanity check this!
      // $$$ hugh - erm, this if statement MUST be wrong!  Otherwise we can never change an element name,
      // it'll always create a new colum.
      // if (!in_array( strtolower( $name ), $exitingfields ) || !in_array( strtolower( $origColName ), $exitingfields )) {
      if (empty($origColName) || !in_array( strtolower( $origColName ), $exitingfields )) {

        $fabrikDb->setQuery( "ALTER TABLE $tableName ADD COLUMN $element->name $objtype AFTER $lastfield" );
        if (!$fabrikDb->query()) {
          return JError::raiseError( 500, 'alter structure: ' . $fabrikDb->getErrorMsg());
        }

      } else {
        if ($this->_canAlterFields()) {
          if ($origColName == null) {
            $origColName = $element->name;
          }
          FabrikString::safeColName( $origColName );
          $fabrikDb->setQuery( "ALTER TABLE $tableName CHANGE $origColName $element->name $objtype" );
          if (!$fabrikDb->query()) {
            return JError::raiseError( 500, 'alter structure: ' . $fabrikDb->getErrorMsg());
          }
        }
      }

      $this->createCacheQuery();
    }
    return true;
  }

  function _canAlterFields()
  {
    $fbConfig =& JComponentHelper::getParams( 'com_fabrik' );
    $params =& $this->getParams();
    $alter = $params->get('alter_existing_db_cols', 'notset');
    if ($alter == 'notset') {
      //fall back to old global settting
      $alter = $fbConfig->get( 'fbConf_alter_existing_db_cols', true );
    }
    return $alter;
  }

  /**
   * if not loaded this loads in the table's form model
   * also binds a reference of the table to the form.
   * @return object form model with form table loaded
   */

  function &getForm()
  {
    if (is_null( $this->_oForm )) {
      $this->_oForm =& JModel::getInstance( 'Form', 'FabrikModel' );
      $table =& $this->getTable();
      $this->_oForm->setId( $table->form_id );
      $this->_oForm->getForm();
      $this->_oForm->setTableModel( $this );
      //ensure joins are loaded
    }
    return $this->_oForm;
  }

  /**
   * tests if the table is in fact a view
   * @returns true if table is a view
   */

  function isView()
  {
    $params =& $this->getParams();
    $isview = $params->get('isview', null);

    if (is_null($isview)) {
      $db =& JFactory::getDBO();
      $table =& $this->getTable();
      $cn =& $this->getConnection();
      $c = $cn->getConnection();
      $dbname = $c->database;
      $sql = "show table status like '$table->db_table_name'";
      $sql = " select table_name, table_type, engine from INFORMATION_SCHEMA.tables ".
			"where table_name = '$table->db_table_name' and table_type = 'view' and table_schema = '$dbname'";
      $db->setQuery( $sql );
      $row = $db->loadObjectList();
      $isview = empty($row) ? false : true;
      $intisview = $isview ? 1 : 0;
      //store and save param for following tests
      $params->set('isview', $isview);
      $table->attribs .= "\nisview=$intisview\n";
      $table->store();
    }
    return $isview;
  }

  /**
   * creates filter array (return existing if exists)
   *@return array filters
   */

  function &getFilterArray()
  {
    if (!isset( $this->filters )) {

      $user  					= &JFactory::getUser();
      $request 				= $this->getRequestData();
      //cookie form plugin can set cookie data - dont use that for filters
      $cookieData = JRequest::get('cookie');
      foreach ($cookieData as $key=>$val) {
        if (array_key_exists( $key, $request )) {
          unset( $request[$key] );
        }
      }

      $this->filters 	= array();

      $prefilters 		=& $this->getPrefilterArray();
      $form 					=& $this->getForm();

      $filterCondSQL	= '';

      // $$$ hugh - temp foreach fix
      $allelementgroups =	$form->getGroupsHiarachy();

      $params =& $this->getParams();
      $searchMode = $params->get( 'search-mode', 'AND' );
      if ($searchMode == 'OR') {
        $search = JRequest::getVar('fabrik_table_filter_all');
      }
      foreach ($allelementgroups as $groupModel) {
        $group =& $groupModel->getGroup();
        $elementModels =& $groupModel->getPublishedElements();
        foreach ($elementModels as $elementModel) {
          $element =& $elementModel->getElement();
          $elanmes[] = $element->name;
          $thisData =  $this->_aData;
          if ($group->is_join) {
            if (@array_key_exists( 'join', $request ) && @is_array( $request['join'][$group->join_id] )) {
              $thisData = $this->_aData['join'][$group->join_id];
            }
            $key 	= $elementModel->getFilterFullName( false, true, false );
          } else {
            $key = $elementModel->getFilterFullName( true, true, false );
          }

          $dbKey = str_replace( "___", ".", $key );
          $dbKey = FabrikWorker::getDbSafeName( $dbKey );

          //if its a single search field set all the elements post data to the
          // single search value
          if ($searchMode == 'OR') {
            $request[$key] = $search;
            $element->filter_type = 'field';
          }
          if (!array_key_exists( $key, $request)) {
            $key .="_raw";
          }
          //$$$ rob @since 2.0b3 - ignore any request vars of '_clear_' - these have already been removed
          //from the session in getRequestData()

          if (array_key_exists( $key, $request ) && $request[$key] != '_clear_') {

            $safeKey = FabrikWorker::getDbSafeName( $dbKey );
            $arr = $elementModel->getFilterConditionSQL( $request[$key], $this->filters, $safeKey, $key );
            //only ever turn this option on if you are using the AND search mode (i.e. filters for each element)
            //$arr['no-filter-setup'] = ($element->filter_type == '' && $searchModel == 'AND') ? 1 : 0;

            ///$$$ rob not sure you should be looking on the search mode s well? no-filter-setup is for search form filters
            $arr['no-filter-setup'] = ($element->filter_type == '') ? 1 : 0;
            $arr['ellabel'] = $element->label;
            $arr['filter-label'] = $elementModel->getFilterLabel( @$arr['value'] );
            if (!empty( $arr['sqlCond'] )) {

              $sqlCond = "( " . $arr['sqlCond'] ;
              //check if the filter has a corresponding prefilter set.
              if (array_key_exists( $key, $prefilters )) {
                $sqlCond .=  " AND " . $prefilters[$key]['sqlCond'];
                unset( $prefilters[$key] );
              }
              $sqlCond .= ")";

              $arr['sqlCond'] = $sqlCond;
              $arr['type'] = 'postfilter';
              //TODO: make this and/or choice somehow for use perhaps?
              $arr['concat'] = ' AND ';
              $arr['grouped_to_previous'] = false;
              $arr['isJoinElement'] = $elementModel->_isJoin;
              $arr['required'] = $elementModel->getParams()->get('filter_required');
              $this->filters[$key] = $arr;
            }
            FabrikHelperHTML::debug($arr, 'found key');
          }
        }
      }
      FabrikHelperHTML::debug(@$elanmes, 'all');
      $this->_searchesNoFilterSetup = array();
    }
    $pluginManager =& $this->getPluginManager();
    $res = $pluginManager->runPlugins( 'onFiltersGot', $this, 'table' );
    return $this->filters;
  }

  /**
   * creates array of prefilters
   * @return array prefilter
   */

  function &getPrefilterArray()
  {
    if (!isset($this->prefilters)) {
      $this->prefilters = array();
      $params =& $this->getParams();
      $afilterJoins 			= $params->get( 'filter-join', '', '_default', 'array' );
      $afilterFields 			= $params->get( 'filter-fields', '', '_default', 'array' );
      $afilterConditions 	= $params->get( 'filter-conditions', '', '_default', 'array' );
      $afilterValues 			= $params->get( 'filter-value', '', '_default', 'array' );
      $afilterAccess 			= $params->get( 'filter-access', '', '_default', 'array' );
      $afilterEval	 			= $params->get( 'filter-eval', '', '_default', 'array' );
      $afilterGrouped	 		= $params->get( 'filter-grouped', '', '_default', 'array' );
      $appliedPrefilters = 0;
      $selJoin = 'WHERE';

      $w = new FabrikWorker();
      for ($i=0; $i < count( $afilterFields );$i++) {
        if (!array_key_exists( 0, $afilterJoins ) || $afilterJoins[0] == '') {
          $afilterJoins[0] = 'and';
        }
        $selJoin 	  = $afilterJoins[$i];

        if (trim( strtolower( $selJoin ) ) == 'where') {
          $selJoin = "AND";
        }
        $selFilter 	  = $afilterFields[$i];
        $selCondition = $afilterConditions[$i];
        $selValue 	  = $afilterValues[$i];
        $filterEval	  = $afilterEval[$i]; // "" or "1"
        $filterGrouped= $afilterGrouped[$i];

        if ($filterEval == '1') {
          $selValue 	= stripslashes( htmlspecialchars_decode( $selValue, ENT_QUOTES ) );
          $selValue 	  = @eval( $selValue );
        }
        $selAccess 	  = $afilterAccess[$i];
        if (!$this->mustApplyFilter( $selAccess, $i )) {
          continue;
        }
        $selValue = $this->_prefilterParse( $selValue );
        $selValue 	  = $w->parseMessageForPlaceHolder( $selValue );
        //strip any quotes from the selValue (replaced back in the switch beneath)
        if (!in_array( $selCondition, array( '>',  '&gt;', ' > ', '<', '&lt;', 'in', 'not_in' ))) {
          $selValue = ltrim( $selValue, "'" );
          $selValue = rtrim( $selValue, "'" );
          $selValue = ltrim( $selValue, '"' );
          $selValue = rtrim( $selValue, '"' );
        }
        switch ($selCondition) {
          case 'notequals':
            $selCondition = " <> ";
            // 2 = subquery so dont quote
            $selValue = $filterEval == 2 ? "($selValue)" : "'$selValue'";
            break;
          case 'equals':
            $selCondition = " = ";
            //$selValue = "'$selValue'";
            $selValue = $filterEval == 2 ? "($selValue)" : "'$selValue'";
            break;
          case 'begins':
            $selCondition = " LIKE ";
            //if its a quoted string put the % inside the quote
            if ($string{0} == '"' || $string{0} == '"') {
              $selValue = substr(0, -1) . "%" . $string{0};
            } else {
              $selValue = "'$selValue%'";
            }
            break;
          case 'ends':
            // @TODO test this with subsquery
            $selCondition = " LIKE ";
            if ($string{0} == '"' || $string{0} == '"') {
              $selValue = $string{0} . "%" .  substr(0, 1) ;
            } else {
              $selValue = "'%$selValue'";
            }
            break;
          case 'contains':
            // @TODO test this with subsquery
            $selCondition = " LIKE ";
            if ($string{0} == '"' || $string{0} == '"') {
              $selValue = $string{0} . "%" .  substr(0, 1) ;
            } else {
              $selValue = "'%$selValue%'";
            }
            break;
          case '>':
          case '&gt;':
            $selCondition = ' > ';
            //$selValue = "'$selValue'";
            $selValue = $filterEval == 2 ? "($selValue)" : "'$selValue'";
            break;
          case '<':
          case '&lt;':
            $selCondition = ' < ';
            //$selValue = "'$selValue'";
            $selValue = $filterEval == 2 ? "($selValue)" : "'$selValue'";
            break;
          case '>=':
          case '&gt;=':
            $selCondition = ' >= ';
            //$selValue = "'$selValue'";
            $selValue = $filterEval == 2 ? "($selValue)" : "'$selValue'";
            break;
          case '<=':
          case '&lt;=':
            $selCondition = ' <= ';
            //$selValue = "'$selValue'";
            $selValue = $filterEval == 2 ? "($selValue)" : "'$selValue'";
            break;
          case 'in':
            $selCondition = ' IN ';
            $selValue = $filterEval == 2 ? "($selValue)" : "('$selValue')";
            break;
          case 'not_in':
            $selCondition = ' NOT IN ';
            $selValue = $filterEval == 2 ? "($selValue)" : "'$selValue'";
            break;

        }
        // $$$ hugh - if 'noquotes' (3) selected, strip off the quotes again!
        if ($filterEval == '3') {
          # $$$ hugh - darn, this is stripping the ' of the end of things like "select & from foo where bar = '123'"
          $selValue = ltrim( $selValue, "'" );
          $selValue = rtrim( $selValue, "'" );
        }
        if ($selCondition == ' = ' && $selValue == "'_null_'") {
          $strCond = " $selFilter IS NULL ";
        } else {
          $strCond = " $selFilter $selCondition $selValue ";
        }
        $this->prefilters[] = array('type' => '', 'value' =>$selValue, 'filterVal' => $selValue,  'sqlCond' => $strCond, 'concat' => $selJoin,
					 'type' => 'prefilter', 'grouped_to_previous' => $filterGrouped);
        $appliedPrefilters ++;
      }
    }
    return $this->prefilters;
  }

  /**
   * get the total number of records in the table
   * @return int total number of records
   */

  function getTotalRecords()
  {
    $db =& $this->getDb();
    $table = $this->getTable();
    $formModel =& $this->getForm();
    $this->getFormGroupElementData();
    // $$$ hugh - untested change as per this thread:
    // http://fabrikar.com/forums/showthread.php?p=47550
    // $count = "DISTINCT " . $table->db_primary_key;
    $count = $table->db_primary_key;
    $groups =& $formModel->getGroupsHiarachy();

    //$$$ below isnt event achieved as plugin's are lower case

    /*foreach ($groups as $groupModel) {
     $elementModels =& $groupModel->getPublishedElements();
     foreach ($elementModels as $elementModel) {
     $element =& $elementModel->getElement();
     if ( $element->show_in_table_summary && $element->plugin == "fabrikDatabasejoin" ) {
     $count = "*";
     }
     }
     }*/
    $totalSql  	= "SELECT COUNT(" . $count . ") AS t FROM " . $table->db_table_name . " " . $this->_buildQueryJoin( );
    $totalSql 	.= " " . $this->_buildQueryWhere( JRequest::getVar('incfilters', 1) );
    $db->setQuery( $totalSql );
    FabrikHelperHTML::debug( $db->getQuery(), 'table getTotalRecords' );
    $total  	= $db->loadResult();
    return $total;
  }

  /**
   * load in the elements for the table's form
   * If no form loaded for the table object then one is loaded
   * @return array element objects
   */

  function &getFormGroupElementData()
  {
    $formModel =& $this->getForm();
    return $formModel->getGroupsHiarachy();
  }

  /**
   * require the correct pagenav class based on template
   *
   * @param int total
   * @param int start
   * @param int length
   * @return object pageNav
   */

  function &getPagination( $total = 0, $limitstart =0, $limit =0 )
  {
    if (!isset( $this->nav )) {
      if ($this->_randomRecords) {
        $db =& $this->getDb();
        $table =& $this->getTable();
        $db->setQuery(" SELECT FLOOR(RAND() * COUNT(*)) AS `offset` FROM $table->db_table_name");
        $limitstart = $db->loadResult();
      }
      $this->nav = new FPagination( $total, $limitstart, $limit );
      $this->nav->_postMethod = $this->getPostMethod();
    }
    return $this->nav;
  }

  /**
   * used to determine which filter action to use
   *if a filter is a range then override tables setting with onsubmit
   */

  function getFilterAction()
  {
    if (!isset( $this->_real_filter_action )) {
      $form =& $this->getForm();
      $table =& $this->getTable();
      $this->_real_filter_action = $table->filter_action;
      $groups =& $form->getGroupsHiarachy();
      foreach ($groups as $groupModel) {
        $elementModels =& $groupModel->getPublishedElements();
        foreach ($elementModels as $elementModel) {
          $element =& $elementModel->getElement();
          if ($element->filter_type <> '') {
            if ($elementModel->canView() && $elementModel->canUseFilter() && $element->show_in_table_summary == '1' ) {
              if($element->filter_type == 'range'){
                $this->_real_filter_action = 'submitform';
                return $this->_real_filter_action;
              }
            }
          }
        }
      }
    }
    return  $this->_real_filter_action;
  }

  /**
   * gets the part of a url to describe the key that the link links to
   * if a table this is rowid=x
   * if a view this is view_primary_key={where statement}
   *
   * @param object row $data
   * @return string
   */

  function getKeyIndetifier( $data )
  {
    $table =& $this->getTable();
    if (!$this->isView()) {
      return "&rowid=$data->__pk_val";
    } else {
      $k = array();
      $fields = $this->_fetchFields();
      foreach ($fields as $f) {
        if ($f->primary_key == 1){
          $y = "{$f->table}___{$f->name}";
          $k[] = "{$f->table}.{$f->name} = " . $data->$y;
        }
      }
      $k = implode(' AND ', $k);
      $k = "&view_primary_key=" . urlencode($k);
      return $k;
    }
  }

  /**
   * *get detailed info on each of the tables fields
   *
   * @return unknown
   */

  function _fetchFields()
  {
    $table =& $this->getTable();
    $db =& $this->getDb();
    $db->setQuery("SELECT * FROM $table->db_table_name LIMIT 1");
    if (!($result = $db->query())) {
      return null;
    }
    $fields       = array();
    $num_fields   = mysql_num_fields($result);
    for ($i = 0; $i < $num_fields; $i++) {
      $fields[] = mysql_fetch_field($result, $i);
    }
    return $fields;
  }


  /**
   * @return array of element objects that are database joins and that
   * use this table's key as their foregin key
   *
   */

  function getJoinsToThisKey()
  {
    if (is_null( $this->_joinsToThisKey )) {
      $db =& JFactory::getDBO();
      $table =& $this->getTable();
      if ($table->id == 0 ) {
        $this->_joinsToThisKey = array();
      } else {
        $sql = "SELECT *, t.label AS tablelabel, t.id as table_id, \n" .
				"el.id AS element_id, f.id AS form_id FROM #__fabrik_elements AS el \n" .
	  		"LEFT JOIN #__fabrik_formgroup AS fg \n" . 
				"ON fg.group_id = el.group_id \n" . 
				"LEFT JOIN #__fabrik_forms AS f \n" . 
				"ON f.id = fg.form_id  \n" .
				"LEFT JOIN #__fabrik_tables AS t \n" . 
				"ON t.form_id = f.id \n" .
	  		"WHERE \n" .
	  		" plugin = 'fabrikDatabasejoin' AND \n" .
	  		" el.attribs like '%join_db_name=".$table->db_table_name."%' \n" .
	  		"AND el.attribs like  '%join_conn_id=".$table->connection_id."%' ";
        $db->setQuery( $sql );
        $this->_joinsToThisKey = $db->loadObjectList( );
      }
    }
    return $this->_joinsToThisKey;
  }

  /**
   * get an array of elements that point to a form where their data will be filtered
   * @return array
   */

  function getLinksToThisKey()
  {
    if (!is_null($this->aJoinsToThisKey)) {
      return $this->aJoinsToThisKey;
    }
    $params =& $this->getParams();
    $aExisitngLinked = $params->get( 'linkedform', '', '_default', 'array' );
    $aAllJoinsToThisKey = $this->getJoinsToThisKey( );
    $this->aJoinsToThisKey= array();
    foreach ($aAllJoinsToThisKey as $join) {
      $key = "{$join->table_id}-{$join->form_id}-{$join->element_id}";
      if (in_array( $key, $aExisitngLinked )) {
        $this->aJoinsToThisKey[] = $join;
      } else {
        $this->aJoinsToThisKey[] = '';
      }
    }
    return $this->aJoinsToThisKey;
  }

  /**
   * have all the required filters been met?
   *
   * @return bol true if they have if false we shouldnt show the table data
   */

  function getRequiredFiltersFound()
  {
    $filters 	=& $this->getFilterArray();
    $groups =& $this->getFormGroupElementData();
    $required = array();
    foreach ($groups as $groupModel) {
      $elementModels = null;
      $elementModels =& $groupModel->getPublishedElements();
      foreach ($elementModels as $kk => $val2) {
        $elementModel = $elementModels[$kk]; //dont do with =& as this foobars up the last elementModel
        $element =& $elementModel->getElement();
        if ($element->filter_type <> '' && $element->filter_type != 'null') {
          if ($elementModel->canView() && $elementModel->canUseFilter()) {
            //force the correct group model into the element model to ensure no wierdness in getting the element name
            if ($elementModel->getParams()->get('filter_required') == 1) {
              $name = $elementModel->getFullName( false, true, false );
              if (!array_key_exists( $name, $filters )) {
                return false;
              }
              if ($filters[$name]['value'] == ''){
                return false;
              }
            }
          }
        }
      }
    }
    return true;
  }

  /**
   * creates an array of html code for each filter
   * @param object database
   * @param string table name
   * @param array current filter states
   * @return array of html code for each filter
   */

  function &makeFilters()
  {
    $aFilters = array();
    $filters 	=& $this->getFilterArray();
    $table 		=& $this->getTable();
    $params 	=& $this->getParams();
    $searchMode = $params->get( 'search-mode', 'AND' );
    //$$$rob - this gives us the wrong elements in faceted browsing - eek!
    //$groups =& $form->getGroupsHiarachy();
    //$$$rob - this is ok though?!!
    if ($searchMode == 'AND') {
      $groups =& $this->getFormGroupElementData();
      //foreach ($groups as $k => $val) { //was working fine but trying to standardise this across all code
      //$groupModel =& $form->_groups[$k];
      foreach ($groups as $groupModel) {
        $g =& $groupModel->getGroup();
        $elementModels = null;
        $elementModels =& $groupModel->getPublishedElements();

        foreach ($elementModels as $kk => $val2) {
          $elementModel = $elementModels[$kk]; //dont do with =& as this foobars up the last elementModel
          $element =& $elementModel->getElement();

          //$$ rob added as some filter_types were null, have to double check that this doesnt
          // mess with showing the readonly values from search forms
          if ($element->filter_type <> '' && $element->filter_type != 'null') {
            if ($elementModel->canView() && $elementModel->canUseFilter()) {
              //force the correct group model into the element model to ensure no wierdness in getting the element name
              $elementModel->_group =& $groupModel;
              $o = new stdClass();
              $o->name = $elementModel->getFullName( false, true, false );
              //global $_PROFILER;
              //JDEBUG ? $_PROFILER->mark('About to getFilter for:' . $o->name) : null;
              $o->filter = $elementModel->getFilter();
              $o->required = $elementModel->getParams()->get('filter_required');
              $aFilters[$element->label] = $o;
            }
          }
        }
      }
    } else {
      //test new option to have one field to searc them all
      $v = JRequest::getVar( 'fabrik_table_filter_all');
      $o = new stdClass();
      $o->filter = "<input size='20' value='$v' class='fabrik_filter' name='fabrik_table_filter_all' />";
      $o->name = 'all';
      $aFilters[JText::_('ALL')] = $o;
    }

    //check for search form filters - if they exists create hidden elements for them

    foreach ($filters as $key=>$f) {
      if ($f['no-filter-setup']) {
        // eg for db joins replace key with label
        // $$$ hugh - this is breaking.
        //$firstrow = current(current($this->getData()));
        // $$ splitting it on to three lines seems to cure it.
        $firstdata = $this->getData();
        $firstgroup = current($firstdata);
        // $$$ hugh - taking out as the empty tests, as per this thread:
        // http://fabrikar.com/forums/showthread.php?t=8889
        // Short version ... if URL filter returns no results, we still need to show it in the
        // filter table.
        //if (empty($firstgroup)) {
        //continue;
        //}
        if (!empty($firstgroup)) {
          $firstrow = current($firstgroup);
        }
        //if (empty($firstrow)) {
        //continue;
        //}
        //$$$ rob test for issue reported here http://fabrikar.com/forums/showthread.php?t=8695
        //->data doesnt seem to exist when viewing from a table??? Cant really test as didnt get ftp /site details
        //$firstrow = $firstrow->data;
        $l = ((is_object($firstrow)) && array_key_exists($key, $firstrow)) ? $firstrow->$key : $f['filter-label'];
        $name = str_replace('.', '___', $key) . "[value]";
        $o = new stdClass();
        $o->filter = "<input class='fabrik_filter' type='hidden' name='$name' value='" . $f['filterVal'] . "' />" . $l;
        $o->name = $key;
        $aFilters[$f['ellabel']] = $o;
      }
    }
    // moved advanced filters to table settings

    if ($params->get( 'advanced-filter', '0' )) {

      // $$$ hugh - $groups may not have been loaded yet
      if (empty($groups)) {
        $groups =& $this->getFormGroupElementData();
      }

      $fieldNames[] = JHTML::_( 'select.option', '', JText::_( 'PLEASE SELECT' ) );
      $longLabel = false;

      foreach ($groups as $groupModel) {
        $group = $groupModel->getGroup();
        if ($group->is_join) {
          $longLabel = true;
        }
      }

      foreach ($groups as $groupModel) {
        $elementModels =& $groupModel->getPublishedElements();
        foreach ($elementModels as $elementModel) {
          $element =& $elementModel->getElement();
          $elParams =& $elementModel->getParams();
          if ($elParams->get('inc_in_adv_search', 1)) {
            $elName = $elementModel->getFullName( false, false, false );
            //@TODO add a switch to use $elName as the label??
            //$l =  $longLabel ? $elName : $element->label;
            $l = $element->label;
            $fieldNames[] = JHTML::_( 'select.option', $elName, $l );
          }
        }
      }
      $fields = FastJSON::encode($fieldNames);
      //todo: make database join elements filtereable on all their join table's fields

      $advancedFilters = JRequest::getVar( 'advancedFilterContainer', array(), 'default', 'none', 2 );
      $document =& JFactory::getDocument();
      FabrikHelperHTML::mocha( 'a.popupwin' );
      $url = '#';
      $advancedSearch = '<a rel="{\'id\':\'advanced-search-win\',\'width\':690,\'loadMethod\':\'html\', \'title\':\'' .  JText::_('ADVANCED SEARCH') . '\', \'maximizable\':\'1\',\'contentType\':\'html\'}" href="'. $url.'" class="popupwin">'. JText::_('ADVANCED SEARCH') .'</a>';
      $aWords = (array_key_exists( 'value', $advancedFilters )) ? explode( " ", $advancedFilters['value'] )  : array('');
      if (!($aWords[0] == "AND" || $aWords[0] == "OR" )) {
        array_unshift( $aWords, "AND" );
      }

      $aGroupedAdvFilters = array();
      $g = 0;
      $tmp = array();
      foreach ($aWords as $word) {
        $tmp[] = $word;
        if ($g == 3) {
          $aGroupedAdvFilters[] = $tmp;
          $tmp = array();
          $g = 0;
        } else {
          $g ++;
        }
      }
      $searchOpts = "$fields, ";
      $searchOpts .= empty($aGroupedAdvFilters) ? 'true' : 'false';
      $searchOpts .= ( $table->filter_action != 'submitform') ? ', true' : ', false';
      $searchOpts .=",{
			tableid:'$table->id'
		}";
      $script = "mochaSearch.conf($searchOpts);";

      foreach ($aGroupedAdvFilters as $bits) {
        $selJoin  = $bits[0];
        if ($bits[0] != "AND" && $bits[0] != "OR") {
          array_unshift($bits, "WHERE");
        }
        $selJoin 			= $bits[0];
        $selFilter 		= $bits[1];
        $selCondition = $bits[2];
        $selValue 		= $bits[3];

        switch( $selCondition )
        {
          case "<>":
            $jsSel = 'NOT EQUALS';
            break;
          case "=":
            $jsSel = 'EQUALS';
            break;
          case "<":
            $jsSel = 'LESS THAN';
            break;
          case ">":
            $jsSel = 'GREATER THAN';
            break;
          default:
            $firstChar = substr( $selValue, 1, 1 );
            $lastChar = substr( $selValue, -2, 1 );
            switch( $firstChar )
            {
              case "%":
                $jsSel =( $lastChar == "%")? 'CONTAINS' : $jsSel = 'ENDS WITH';
                break;
              default:
                if( $lastChar == "%"){
                  $jsSel = 'BEGINS WITH';
                }
                break;
            }
            break;
        }
        $selValue = trim( trim( $selValue, '"' ), "%" );
        $script .= "\n mochaSearch.addFilterOption('$selJoin', '$selFilter', '$jsSel', '$selValue');\n";
      }
      $document->addScriptDeclaration( $script );

      $o = new stdClass();
      $o->filter = $advancedSearch . "<input type='hidden' name=\"advancedFilterContainer[value]\" value='' id=\"advancedFilterContainer\" />";
      $o->name = 'fabrik_advanced_search';
      $aFilters['']  = $o;
    }

    return $aFilters;
  }

  /**
   * returns the table headings, seperated from writetable function as
   * when group_by is selected mutliple tables are written
   * @return array (table headings, array columns, $aLinkElements)
   */

  function getHeadings()
  {
    $table =& $this->getTable();
    $aLinkElements 			= array();
    $aNamedTableHeadings 	= array( );
    $aTableHeadings 		= array();
    $aCols 					= array();

    //maps heading labels in $aTableHeadings to the db field name required for ordering by
    //required as dbjoin elments need to be ordered by their joined field rather than the value field
    $orderMap = array();
    $w = new FabrikWorker();
    $session =& JFactory::getSession();
    //$session->destroy();
    $formModel =& $this->getForm();
    $linksToForms =  $this->getLinksToThisKey();
    $groups =& $formModel->getGroupsHiarachy();
    $groupHeadings = array();
    foreach ($groups as $groupModel) {
      $groupHeadings[$groupModel->getGroup()->label] = 0;
      $elementModels =& $groupModel->getPublishedElements();
      foreach ($elementModels as $key => $elementModel) {
        $viewLinkAdded = false;
        $element =& $elementModel->getElement();
        if (!$element->show_in_table_summary) {
          continue;
        }
         
        //hide any elements that should not be access by the user for their group id
        if (!$elementModel->canView()) {
          continue;
        }
        $groupHeadings[$groupModel->getGroup()->label] ++;
        $key 		= $elementModel->getFullName( false, true, false );
        $orderKey 	= $elementModel->getOrderbyFullName( false, false );
        $aCols[$key] = '';
        if ($element->link_to_detail == '1') {
          $aLinkElements[] = $key;
        }
        $elementParams =& $elementModel->getParams();
        $label = $elementParams->get( 'element_alt_table_heading' );
        if ($label == '') {
          $label = $element->label;
        }
        $label = $w->parseMessageForPlaceHolder( $label, array() );

        if ($element->can_order == '1' && $this->_outPutFormat != 'csv' ) {
          $context			= 'com_fabrik.table' . $this->_id . '.order.' . str_replace('.', '___', $elementModel->getOrderByName());
          $orderDir			= $session->get( $context );
          $class 		= "";
          $currentOrderDir = $orderDir;
          switch ($orderDir) {
            case "desc":
              $orderDir = "-";
              $class = "class='fabrikorder-desc'";
              break;
            case "asc":
              $orderDir = "desc";
              $class = "class='fabrikorder-asc'";
              break;
            case "":
            case "-":
              $orderDir = "asc";
              $class = "class='fabrikorder'";
              break;
          }

          if ($class == '') {
            if ($table->order_by == $key) {
              if (strtolower( $table->order_dir ) == 'desc') {
                $class = "class='fabrikorder-desc'";
              }
            }
          }

          //$orderjs = "oPackage.fabrikNavOrder( $table->id, ";
          $heading = "<a $class href='#'>$label</a>";
        } else {
          $heading = $label;
        }
        $aTableHeadings[$key] = $heading;
        $orderMap[$key] = $elementModel->getOrderByName();
        $aNamedTableHeadings[$key . "_heading"] = $heading;
      }
      if ($groupHeadings[$groupModel->getGroup()->label] == 0){
        unset ($groupHeadings[$groupModel->getGroup()->label]);
      }
    }
    if ( $this->_outPutFormat != 'pdf' && $this->_outPutFormat != 'csv' ){
      //@TODO check if any plugins need to use the selector as well!

      if ($this->canSelectRows()) {
        $select = '<input type="checkbox" id="table_' . $this->_id . '_checkAll" />';
        $aTableHeadings['fabrik_delete'] = $select;
        $aNamedTableHeadings['fabrik_delete_heading'] = $select;
      }
      //if no elements linking to the edit form add in a edit column (only if we have the right to edit/view of course!)
      if (empty( $aLinkElements ) and ($this->canView() || $this->canEdit())) {
        if ($this->canEdit()) {
          $aTableHeadings['fabrik_edit'] = JText::_('EDIT');
          $aNamedTableHeadings['fabrik_edit_heading'] = JText::_('EDIT');
        } else {
          if ($this->canViewDetails()) {
            $aTableHeadings['fabrik_view'] = JText::_('VIEW');
            $viewLinkAdded = true;
            $aNamedTableHeadings['fabrik_view_heading'] = JText::_('VIEW');
          }
        }
      }
      if ($this->canViewDetails() && $this->_params->get( 'detaillink' ) == '1' && !$viewLinkAdded ) {
        $aTableHeadings['__details_link'] = JText::_( 'VIEW' );
        $aNamedTableHeadings['__details_link'] = JText::_( 'VIEW' );
      }

      // create columns containing links which point to tables associated with this table
      $params =& $this->getParams();
      $aExisitngLinkedTables = $params->get('linkedtable', '', '_default', 'array');
      $aExistingTableHeaders = $params->get( 'linkedtableheader', '', '_default', 'array' );
      $joinsToThisKey = $this->getJoinsToThisKey( );
      $f = 0;
      foreach ($joinsToThisKey as $element) {
        if (is_object( $element )) {
          $linkedTable 	= array_key_exists( $f, $aExisitngLinkedTables ) ? $aExisitngLinkedTables[$f] : false;
          $heading = array_key_exists( $f, $aExistingTableHeaders ) ? $aExistingTableHeaders[$f] : false;
          if ($linkedTable != '0') {
            $prefix 	= ( $element->tablelabel == $table->label ) ? $table->db_primary_key : $element->db_table_name . "___" . $element->name;
            $aTableHeadings[$prefix . "_table_heading"] = empty($heading) ? $element->tablelabel . " " . JText::_( 'TABLE' ) : $heading;
            $aNamedTableHeadings[ $prefix . "_table_heading"] = empty($heading) ? $element->tablelabel . " " . JText::_( 'TABLE' ) : $heading;
          }
        }
        $f ++;
      }

      $aExisitngLinkedForms = $params->get( 'linkedform', '', '_default', 'array' );
      $aExistingFormHeaders 	= $params->get( 'linkedformheader', '_default', '', 'array' );

      $f = 0;
      foreach ($linksToForms as $element ) {
        $linkedForm 	= array_key_exists( $f, $aExisitngLinkedForms ) ? $aExisitngLinkedForms[$f] : false;
        if ($linkedForm != '0') {
          $heading = array_key_exists( $f, $aExistingFormHeaders ) ? $aExistingFormHeaders[$f] : '';
          $prefix	= $element->db_table_name . "___" . $element->name;
          $aTableHeadings[$prefix . "_form_heading"] = empty($heading) ? $element->tablelabel . " " . JText::_('FORM') : $heading;
          $aNamedTableHeadings[ $prefix . "_form_heading"] = empty($heading) ? $element->tablelabel . " " . JText::_('FORM') : $heading ;
        }
        $f ++;
      }
    }
    //return array( $aTableHeadings, $aCols, $aLinkElements, $aNamedTableHeadings, $orderMap, $groupHeadings );
    return array( $aTableHeadings, $orderMap, $groupHeadings );
  }

  function canSelectRows()
  {
    if (!is_null($this->canSelectRows)) {
      return $this->canSelectRows;
    }
    if ($this->canDelete()) {
      $this->canSelectRows = true;
      return $this->canSelectRows;
    }
    $params =& $this->getParams();
    $usedPlugins 	= $params->get('plugin', "", "_default", "array");

    if (empty( $usedPlugins )) {
      $this->canSelectRows = false;
      return $this->canSelectRows;
    }
    $pluginManager =& $this->getPluginManager();
    $tableplugis =& $pluginManager->getPlugInGroup( 'table' );
    $res = $pluginManager->runPlugins( 'canSelectRows', $this, 'table' );
    $this->canSelectRows = $pluginManager->runPlugins( 'canSelectRows', $this, 'table' );
    return $this->canSelectRows;
  }

  /**
   * return mathematical column calculations (run at doCalculations() on for submission)
   */

  function getCalculations()
  {
    $user  = &JFactory::getUser();
    $aCalculations = array();
    $formModel =& $this->getForm();
    $aAvgs = array();
    $aSums = array();
    $aMedians = array();
    $aCounts = array();
    $groups =& $formModel->getGroupsHiarachy();
    foreach ($groups as $groupModel ){
      $elementModels =& $groupModel->getPublishedElements();
      foreach( $elementModels as $elementModel ){
        $params = $elementModel->getParams();
        $elName = $elementModel->getFullName( false, true, false );
        $sum 			= $params->get( 'sum_on', '0' );
        $avg 			= $params->get( 'avg_on', '0' );
        $median 		= $params->get( 'median_on', '0' );
        $countOn 		= $params->get( 'count_on', '0' );

        $sumAccess 		= $params->get( 'sum_access', 0 );
        $avgAccess 		= $params->get( 'avg_access', 0 );
        $medianAccess 	= $params->get( 'median_access', 0 );
        $countAccess 	= $params->get( 'count_access', 0 );

        if ($sumAccess <= $user->get('gid') && $params->get('sum_value', '') != ''){
          $aSums[ $elName ] = $params->get('sum_value', '');
          $aSums[ $elName . "_obj" ] = unserialize($params->get('sum_value_serialized'));
        }

        if ($avgAccess <= $user->get('gid') && $params->get('avg_value', '') != ''){
          $aAvgs[ $elName ] = $params->get('avg_value', '');
          $aAvgs[ $elName . "_obj" ] = unserialize($params->get('avg_value_serialized'));
        }

        if ($medianAccess <= $user->get('gid') && $params->get('median_value', '') != ''){
          $aMedians[ $elName ] = $params->get('median_value', '');
          $aMedians[ $elName . "_obj" ] = unserialize($params->get('median_value_serialized', ''));
        }

        if ($countAccess <= $user->get('gid') && $params->get('count_value', '') != ''){
          $aCounts[ $elName ] = $params->get('count_value', '');
          $aCounts[ $elName . "_obj" ] = unserialize($params->get('count_value_serialized'));
        }
      }
    }
    $aCalculations['sums'] 			= $aSums;
    $aCalculations['avgs'] 			= $aAvgs;
    $aCalculations['medians'] 	= $aMedians;
    $aCalculations['count'] 		= $aCounts;
    $this->_aRunCalculations =& $aCalculations;
    return $aCalculations;
  }

  /**
   * get table headings to pass into table js oject
   *
   * @return string headings tablename___name
   */

  function _jsonHeadings()
  {
    $aHeadings = array();
    $table =& $this->getTable();
    $formModel =& $this->getForm();
    $groups =& $formModel->getGroupsHiarachy();
    foreach ($groups as $groupModel) {
      $elementModels =& $groupModel->getPublishedElements();
      foreach ($elementModels as $elementModel) {
        $element =& $elementModel->getElement();
        if ($element->show_in_table_summary) {
          $aHeadings[] = $table->db_table_name . '___' . $element->name;
        }
      }
    }
    return "['" . implode("','", $aHeadings) . "']";
  }

  /**
   * when form saved (and set to record in database)
   * this is run to see if there is any table join data,
   * if there is it stores it in $this->_joinsToProcess
   *
   * @return array [joinid] = array(join, group array);
   */

  function preProcessJoin()
  {
    if (!isset( $this->_joinsToProcess )) {
      $this->_joinsToProcess = array();
      $formModel = $this->getForm();
      $groups =& $formModel->getGroupsHiarachy();
      foreach ($groups as $groupModel) {
        $group =& $groupModel->getGroup();
        if ($groupModel->isJoin()) {
          $joinModel =& $groupModel->getJoinModel();
          $join =& $joinModel->getJoin();
          if (!array_key_exists( $join->id, $this->_joinsToProcess )) {
            $this->_joinsToProcess[$join->id] = array( "join" => $join, "groups" => array($groupModel) );
          } else {
            $this->_joinsToProcess[$join->id]["groups"][] = $groupModel;
          }
        }
      }
    }
    return $this->_joinsToProcess;
  }

  /**
   * check to see if a table exists
   * @param string name of table (ovewrites form_id val to test)
   * @param object database that contains the table if null then default $db object used
   * @return boolean false if no table fodund true if table found
   */

  function databaseTableExists( $tableName = null, $fabrikDatabase = null ){
    if ($tableName === '') {
      return false;
    }
    $table =& $this->getTable();
    if (is_null( $tableName )) {
      $tableName = $table->db_table_name;
    }
    $sql = "SHOW TABLES LIKE '$tableName'";
    /* use the default Joomla database if no table database specified */
    if (is_null( $fabrikDatabase ) || !is_object( $fabrikDatabase )) {
      $fabrikDatabase =& $this->getDb();
    }
    $fabrikDatabase->setQuery( $sql );

    $total = $fabrikDatabase->loadResult();
    echo $fabrikDatabase->getError();
    return ($total == "") ? false : true;
  }

  /**
   * strip the table names from the front of the key
   * @param array data to strip
   * @return array stripped data
   */

  function removeTableNameFromSaveData( $data, $split='___' ){
    foreach ($data as $key=>$val) {
      $akey = explode( $split, $key);
      if (count($akey) > 1) {
        $newKey = $akey[1];
        unset($data[$key]);
      } else {
        $newKey = $akey[0];
      }
      $data[$newKey] = $val;
    }
    return $data;
  }

  /**
   * saves posted form data into a table, you can supply table name etc to override the objects
   * variables, this is needed to allow form class to use this object to save to a database table
   * that doesnt have a table view associated with it
   * @param array data to save
   * @param int row id to edit/updated
   * @param bol is the data being saved into a join table
   * @return bol true if saved ok
   */

  function storeRow( $data, $rowId, $isJoin = false )
  {
    //dont save a record if no data collected
    if ($isJoin && implode( $data ) == '') {
      return;
    }
    $fabrikDb 	=& $this->getDb();
    $table =& $this->getTable();

    $formModel =& $this->getForm();

    if ($isJoin) {
      $this->getFormGroupElementData();
    }
    $oRecord = new stdClass();
    $aBindData = array();
    $noRepeatFields = array();
    $c = 0;
    $groups =& $formModel->getGroupsHiarachy();
    foreach ($groups as $groupModel) {
      $group =& $groupModel->getGroup();

      if (($isJoin && $group->is_join) || (!$isJoin && !$group->is_join)) {
        $elementModels =& $groupModel->getPublishedElements();
        foreach ($elementModels as $elementModel) {
          $element = $elementModel->getElement();
          $key = $element->name;

          //for radio buttons and dropdowns otherwise nothing is stored for them??
          if(array_key_exists($key ."_raw", $data)){
            $postkey = $key . "_raw";
          }else{
            $postkey = $key;
          }
          //if the user cant use or view dont update this element's value
          if (!$elementModel->canUse() && !$elementModel->canView()){
            continue;
          }
          if ($elementModel->recordInDatabase( $data )) {

            if (array_key_exists( $key, $data ) && !in_array( $key, $noRepeatFields )) {
              $noRepeatFields[] = $key;
              $lastKey = $key;
               
              $val = $elementModel->storeDatabaseFormat( $data[$postkey], $data, $key );

              //test for backslashed quotes
              if (get_magic_quotes_gpc()) {
                if (!$elementModel->_is_upload) {
                  $val = stripslashes( $val );
                }
              }
              $oRecord->$key = $val;
              $aBindData[$key] = $val;
              $c++;
            }
          }
        }
      }
    }
    $this->_addDefaultDataFromRO( $aBindData, $oRecord, $isJoin );
    $primaryKey = $this->_shortKey();
    if ($rowId != '' && $c == 1 && $lastKey == $primaryKey) {
      return;
    }

    $primaryKey = trim( $primaryKey, '`' );
    if ($usekey = JRequest::getVar('usekey', false)) {
      if (empty($this->_origData)) {
        $rowId = '';
      }
      else {
        $rowId = $this->_origData[$primaryKey];
      }
    }
    else {
      if (!isset( $oRecord->$primaryKey )) {
        $oRecord->$primaryKey = $rowId;
      }
    }
    if ($rowId == '' || $rowId == 0) {
      unset($oRecord->$primaryKey);
      $ok = $fabrikDb->insertObject( $table->db_table_name, $oRecord, $primaryKey, false );
    } else {
      $ok = $fabrikDb->updateObject( $table->db_table_name, $oRecord, $primaryKey, false );
    }
    $this->_tmpSQL = $fabrikDb->getQuery();
    if (!$ok) {
      return JError::raiseWarning( 500, 'Store row failed: ' . $fabrikDb->getQuery() ) ;
    } else {
      // $$$ rob new as if you update a record the insertid() returns 0
      $this->_lastInsertId = ($rowId == '' || $rowId == 0) ?$fabrikDb->insertid() : $rowId;
      return true;
    }
  }

  /**
   * If an element is set to readonly, and has a default value selected then insert this
   * data into the array that is to be bound to the table record
   * @since 1.0.6
   * @param array data
   * @param object to bind to table row
   * @param int is record join record
   */

  function _addDefaultDataFromRO( &$data, &$oRecord, $isJoin )
  {
    jimport( 'joomla.utilities.simplecrypt' );
    // $$$ rob since 1.0.6 : 10 June 08
    // get the current record - not that which was posted
    $formModel =& $this->getForm();
    $table =& $this->getTable();
    //test dont think this is used?
    //$pkv = $data[$this->_shortKey( $table->db_primary_key, true )];

    if (is_null( $this->_origData )) {
      $sql = $formModel->_buildQuery();
      $db =& $this->getDb();
      $db->setQuery( $sql );
      $origdata = $db->loadObject();
      $origdata = JArrayHelper::fromObject( $origdata );
      $origdata = is_array($origdata) ? $origdata : array();
      $this->_origData =& $origdata;
    } else {
      $origdata =& $this->_origData;
    }
    //
    $form =& $formModel->getForm();
    $groups =& $formModel->getGroupsHiarachy();
    $gcounter = 0;
    $repeatGroupCounts = JRequest::getVar('fabrik_repeat_group');
    foreach  ($groups as $groupModel) {
      if (($isJoin && $groupModel->isJoin()) || (!$isJoin && !$groupModel->isJoin())) {
        $elementModels =& $groupModel->getPublishedElements();
        foreach ($elementModels as $elementModel) {
          if (!$elementModel->canUse() && $elementModel->canView()) {
            $element =& $elementModel->getElement();
            $fullkey = $elementModel->getFullName( false, true, false );
            $key = $element->name;
            //force a reload of the default value with $origdata
            unset($elementModel->defaults);
            $default = array();
            for ($i=0; $i < $repeatGroupCounts[$gcounter]; $i++) {
              $def 		= $elementModel->getDefaultValue( $origdata, $i );
              //if its a dropdown radio etc
              if (is_array( $def )) {
                $def = implode( $elementModel->_groupSplitter2, $def );
              }
              $default[] = $def;
            }
            $default = implode( $elementModel->_groupSplitter, $default );
            $data[$key] = $default;
            $oRecord->$key = $default;
          }
        }
      }
      $gcounter ++;
    }
    //check crypted querystring vars (encrypted in form/view.html.php ) _cryptQueryString
    if (array_key_exists( 'fabrik_vars', $_REQUEST ) && array_key_exists( 'querystring', $_REQUEST['fabrik_vars'] )) {
      $crypt = new JSimpleCrypt();
      foreach ($_REQUEST['fabrik_vars']['querystring'] as $key => $encrypted) {

        $key = FabrikString::shortColName( $key );
        foreach  ($groups as $groupModel) {
          $elementModels =& $groupModel->getPublishedElements();
          foreach ($elementModels as $elementModel) {
            $element =& $elementModel->getElement();

            if ($element->name == $key) {
              //dont overwrite if something has been entered
              if (!$elementModel->canUse() && $elementModel->canView()) {
                $data[$key] = $crypt->decrypt( $encrypted );
                $oRecord->$key = $data[$key];
              }
            }
          }
        }
      }
    }
  }


  /**
   * get the short version of the primary key, e.g if key = table.key return "key"
   *
   * @return string short version of the primary key
   */

  function _shortKey( $key = null, $removeQuotes = false ){
    if (is_null( $key )) {
      $table =& $this->getTable();
      $key = $table->db_primary_key;
    }
    if (strstr( $key, "." )) {
      $bits = explode( ".", $key );
      $key = array_pop( $bits );
    }
    if($removeQuotes){
      $key = str_replace("`", "", $key );
    }
    return $key;
  }

  /**
   * called when the form is submitted to perform calculations
   */

  function doCalculations()
  {
    $db =& JFactory::getDBO();
    $formModel =& $this->getForm();
    $groups =& $formModel->getGroupsHiarachy();
    foreach ($groups as $groupModel) {
      $elementModels =& $groupModel->getPublishedElements();
      foreach ($elementModels as $elementModel) {
        $element =& $elementModel->getElement();

        $params =& $elementModel->getParams();
        $update = false;
        if ($params->get( 'sum_on', 0 )) {
          $aSumCals = $elementModel->sum( $this );
          $params->set( 'sum_value_serialized', serialize( $aSumCals[1] ) );
          $params->set( 'sum_value', $aSumCals[0] );
          $update = true;
        }
        if ($params->get( 'avg_on', 0 )) {
          $aAvgCals = $elementModel->avg( $this );
          $params->set( 'avg_value_serialized', serialize( $aAvgCals[1] ) );
          $params->set( 'avg_value', $aAvgCals[0] );
          $update = true;
        }
        if ($params->get( 'median_on', 0 )) {
          //$params->set( 'median_value', $elementModel->median( $this ) );

          $medians = $elementModel->median( $this );
          $params->set( 'median_value_serialized', serialize( $medians[1] ) );
          $params->set( 'median_value', $medians[0] );

          $update = true;
        }
        if ($params->get( 'count_on', 0 )) {
          $aCountCals = $elementModel->count( $this );
          $params->set( 'count_value_serialized', serialize( $aCountCals[1] ) );
          $params->set( 'count_value', $aCountCals[0] );
          $update = true;
        }
        if ($update) {
          $element =& $elementModel->getElement();
          $element->attribs = $params->updateAttribsFromParams( $params->toArray() );
          // $$$ hugh - can't store the whole element, otherwise we overwrite the default val!
          // added storeAttribs() to element model
          //$element->store();
          $elementModel->storeAttribs();
        }
      }
    }
  }

  /**
   * check to see if prefilter should be applied
   * Kind of an inverse access lookup
   * @param int group id to check against
   * @param string ref for filter
   * @return bol must apply filter - true, ignore filter (user has enough access rights) false;
   */

  function mustApplyFilter( $gid, $ref )
  {
    // prefilters with JACL are applied to a single group only
    // not a group and groups beneath them (think author, registered)
    // so if JACL on then prefilters work in the inverse in that they are only applied
    // to the group selected

    if (defined( '_JACL' )) {
      return FabrikWorker::getACL( $gid, 'prefilter' . $ref );
    } else {
      return FabrikWorker::getACL( $gid, 'prefilter' . $ref, '<=' );
    }

  }

  /**
   * set the connection id - used when creating a new table
   * @param int connection id
   */

  function setConnectionId( $id )
  {
    $this->_table->connection_id = $id;
  }

  /**
   * run each time a table is saved (plus when an element is saved )
   * builds the sql query that the table getData function will use
   * stores it in the cache/fabrik folder
   * retrieved by getData when table rendered
   *
   * NOT sure we need/use this
   */

  function createCacheQuery()
  {
    $q = $this->_buildQuery();
    $cache = &JFactory::getCache( 'com_fabrik' );
    $cache->store( $q, 'tablequery' . $this->_id );
  }


  /**
   * return the default set of attributes when creating a new
   * fabrik table
   *
   * @return string attributes
   */

  function getDefaultAttribs()
  {
    return "admin_template=admin
detaillink=0
empty_data_msg=No data found
advanced-filter=0
show-table-nav=1
pdf=
rss=0
feed_title=
feed_date=
rsslimit=150
rsslimitmax=2500
csv_import_frontend=0
csv_export_frontend=0
csvfullname=0
access=0
allow_view_details=0
allow_edit_details=0
allow_add=0
allow_delete=0
group_by_order=
group_by_order_dir=ASC
prefilter_query=
require-filter=0"; 
  }

  /**
   * save the table from admin
   *
   * @return Jerror if not saved true if saved ok
   */

  function save()
  {
    global $mainframe;
    $db 				=& JFactory::getDBO();
    $user 			=& JFactory::getUser();
    $config 		=& JFactory::getConfig();
    $id 				= JRequest::getInt( 'id', 0, 'post' );
    $this->setId( $id );
    $row 				=& $this->getTable();
    $formModel 	=& JModel::getInstance( 'Form', 'FabrikModel' );

    $post				= JRequest::get( 'post' );

    if (!$row->bind( $post )) {
      return JError::raiseWarning( 500, $row->getError() );
    }

    $filter	= new JFilterInput( null, null, 1, 1 );
    $introduction = JRequest::getVar( 'introduction', '', 'post', 'string', JREQUEST_ALLOWRAW );
    $row->introduction = $filter->clean( $introduction );

    $details	= JRequest::getVar( 'details', array(), 'post', 'array' );
    $row->bind( $details );
    if (!$row->check()) {
      $mainframe->setError($row->getError());
      return JError::raiseWarning( 500, $row->getError() );
    }

    if ($id == 0) {
      $newtable = trim( JRequest::getVar( '_database_name', '', 'post' ));

      //check the entered database table doesnt already exist
      if ($newtable != '' && $this->databaseTableExists( $newtable )) {
        return JError::raiseWarning( 500, JText::_( 'DATABASE TABLE ALREADY EXISTS' ) );
      }
      //create fabrik form
      $formModel =& $this->_createLinkedForm();

      //create fabrik group
      $groupData = array( "name" => $row->label , "label" => $row->label );

      JRequest::setVar( '_createGroup', 1, 'post' );
      $groupId = $this->_createLinkedGroup( $groupData, false );

      if ($newtable != '') {
        $row->db_table_name = $newtable;
        $row->db_primary_key = "`".$newtable.'`.`fabrik_internal_id`';
        $row->auto_inc = 1;
        $this->createDBTable( $formModel, $newtable );
      } else {
        //new fabrik table but existing db table
        $this->_createLinkedElements( $groupId, $post );

      }
      //	set the tables form id
      $this->_updateFormId( $formModel->_form->id );
    }

    // 	save params - this file no longer exists? do we use models/table.xml instead??
    $params = new fabrikParams( $row->attribs, JPATH_COMPONENT.DS.'xml'.DS.'table.xml' );

    $row->attribs = $params->updateAttribsFromParams( JRequest::getVar( 'params', array(), 'post', 'array' ) );
    $row->rows_per_page 	= JRequest::getInt( 'rows_per_page', 10, 'post' );
    $row->auto_inc		 		= JRequest::getInt( 'auto_inc', 1, 'post' );

    if ($row->id != 0) {
      $datenow =& JFactory::getDate();
      $row->modified 		= $datenow->toMySQL();
      $row->modified_by 	= $user->get('id');
    }

    FabrikHelper::prepareSaveDate( $row->publish_down );
    FabrikHelper::prepareSaveDate( $row->created );
    FabrikHelper::prepareSaveDate( $row->publish_up );

    $pk = JRequest::getVar( 'db_primary_key' );
    if ($pk == '') {
      $aKey = $this->getPrimaryKeyAndExtra();
      $row->db_primary_key = "`".$row->db_table_name . "`.`" . $aKey['colname'] . "`";
    }

    if (!$row->store()) {
      return JError::raiseWarning( 500, $row->getError() );
    }

    // load in all the tables data - even if it wasnt in the post data
    $table =& $this->getTable();
    //needed if saving a table for first time (otherwise id = 0)
    $this->setId( $table->id );
    $this->updateJoins();
    if (!$this->isView()) {
      // this was only run on a new table - but I've put it here so that if you upload a new table you can ensure that its columns are fixed
      // @TODO - sanity check - do we want to run this if alter_db_cols is No?
      $this->makeSafeTableColumns();
      $this->updatePrimaryKey( $row->db_primary_key, $row->auto_inc );
    }
    $row->checkin();

    //make an array of elments and a presumed index size
    $map = array();
    $groups =& $this->getForm()->getGroupsHiarachy();
    foreach ($groups as $groupModel) {
      $elementModels =& $groupModel->getMyElements();
      foreach ($elementModels as $element) {
        //int elements cant have a index size attrib
        $size = stristr( $element->getFieldDescription(), 'int' ) ? '' : '10';
        $map[$element->getFullName(false, false, false)] = $size;
        $map[$element->getElement()->id] = $size;
      }
    }

    //update indexes
    $this->addIndex( $row->order_by, 'tableorder', 'INDEX', $map[$row->order_by] );
    $this->addIndex( $row->group_by, 'groupby', 'INDEX', $map[$row->group_by] );

    $this->addIndex( $params->get('group_by_order'), 'groupbyorder', 'INDEX', $map[$params->get('group_by_order')] );
    $afilterFields = $params->get( 'filter-fields', '', '_default', 'array' );
    foreach ($afilterFields as $field) {
      $this->addIndex( $field, 'prefilter', 'INDEX', str_replace('`', '', $map[$row->order_by]) );
    }
    $this->createCacheQuery();
    return true;
  }

  /**
   * when saving a table that links to a database for the first time we
   * automatically create a form to allow the update/creation of that tables
   * records
   * @access private
   * @return object form model
   */

  function &_createLinkedForm()
  {
    $config		=& JFactory::getConfig();
    $user			=& JFactory::getUser();

    jimport('joomla.utilities.date');
    $createdate =& JFactory::getDate();
    $createdate = $createdate->toMySQL();

    $form = JTable::getInstance( 'Form', 'Table' );
    $table =& $this->getTable();
    $form->label							= $table->label;
    $form->record_in_database = 1;

    $form->created 						= $createdate;
    $form->created_by 				= $user->get( 'id' );
    $form->created_by_alias 	= $user->get( 'username' );
    $form->error							= JText::_( 'SOME PARTS OF YOUR FORM HAVE NOT BEEN FILLED IN' );
    // @TODO : fabrik : 0.5 : create a overall fabrik params admin page
    //which sets the default settings for all forms, from which these settings below can be populated
    $form->submit_button_label 	= JText::_( 'SAVE' );
    $form->state							= $table->state;
    $form->form_template			= 'default';
    $form->view_only_template	= 'default';

    if(! $form->store() ){
      return JError::raiseError( 500, $form->getError() );
    }

    $this->_oForm =& JModel::getInstance( 'Form', 'FabrikModel' );
    $this->_oForm->setId( $form->id );
    $this->_oForm->getForm();
    return $this->_oForm;
  }

  /**
   * create a group
   * used when creating a fabrik table from an existing db table
   *
   * NEW also creates the formgroup
   *
   * @access private
   * @param array group data
   * @param bol is the group a join default false
   * @return int group id
   */

  function _createLinkedGroup( $data, $isJoin = false )
  {
    $user 			=& JFactory::getUser();
    $createdate = JFactory::getDate();
    $createdate = $createdate->toMySQL();

    $group 										=& JTable::getInstance( 'Group', 'Table' );
    $group->bind( $data );
    $group->created 					= $createdate;
    $group->created_by 				= $user->get('id');
    $group->created_by_alias 	= $user->get('username');
    $group->state 						= 1;
    $group->attribs = "repeat_group_button=0
repeat_group_show_first=1
repeat_group_js_add=
repeat_group_js_delete=";
    $group->is_join = ($isJoin == true) ? 1 : 0;

    $group->store();
    if (!$group->store()) {
      JError::raiseError( 500, $group->getError() );
    }

    //create form group
    $formid = $this->_oForm->_id;
    $formGroup = JTable::GetInstance( 'FormGroup', 'Table' );
    $formGroup->form_id = $formid;
    $formGroup->group_id = $group->id;
    $formGroup->ordering = 999999;
    if (!$formGroup->store()) {
      JError::raiseError( 500, $formGroup->getError() );
    }

    $formGroup->reorder( " form_id = '$formid'" );
    return $group->id;
  }

  /**
   * Create a table to store the forms' data depending upon what groups are assigned to the form
   * @param object form model
   * @param string table name - taken from the table oject linked to the form
   * @param obj tables database object NOT USED!!!!
   */

  function createDBTable( &$formModel, $dbTableName = null, $tableDatabase = null ){
    $db 		=& JFactory::getDBO();
    $tableDatabase =& $this->getDb();
    $user  	=& JFactory::getUser();
    $config =& JFactory::getConfig();
    if (is_null( $dbTableName )) {
      $dbTableName = $this->_table->db_table_name;
    }
    $sql = "CREATE TABLE `$dbTableName` ( " ;

    $db->setQuery( "SELECT group_id FROM #__fabrik_formgroup WHERE form_id = $formModel->_id" );
    $groupIds = $db->loadResultArray();
    /* create elements for the internal id and time_date fields */
    $element =& JTable::getInstance( 'Element', 'Table' );
    $element->name			= "fabrik_internal_id";
    $element->label			= "id";
    $element->plugin 		= 'fabrikinternalid';
    $element->hidden 		= 1;
    $element->group_id 	= $groupIds[0];
    $element->primary_key		= 1;
    $element->auto_increment	= 1;
    $element->created 			= JHTML::_( 'date', date( '%Y-%m-%d %H:%M:%S' ), '%Y-%m-%d %H:%M:%S', - $config->getValue( 'offset' ) );
    $element->created_by 		= $user->get( 'id' );
    $element->created_by_alias = $user->get( 'username' );
    $element->state 			= '1';
    $element->show_in_table_summary = '1';
    $element->link_to_detail = '1';
    $element->width 	= '30';
    $element->ordering 		= 0;
    if (!$element->store()) {
      return JError::raiseWarning( 500, $element->getError() );
    }
    $element =& JTable::getInstance( 'Element', 'Table' );
    $element->name		= "time_date";
    $element->label		= "time_date";
    $element->plugin 	= 'fabrikdate';
    $element->hidden 			= 1;
    $element->eval				= 1;
    $element->default			= "return date('Y-m-d h:i:s');";
    $element->group_id 		= $groupIds[0];
    $element->primary_key		= 0;
    $element->auto_increment	= 0;
    $element->created 			= JHTML::_( 'date', date( '%Y-%m-%d %H:%M:%S' ), '%Y-%m-%d %H:%M:%S', - $config->getValue( 'offset' ) );
    $element->created_by 		= $user->get( 'id' );
    $element->created_by_alias = $user->get( 'username' );
    $element->state 			= '1';
    $element->show_in_table_summary = '1';
    $element->width 	= '30';
    $element->ordering 		= 1;
    if (!$element->store()) {
      return JError::raiseWarning( 500, $element->getError() );
    }
    //reset form and plugin manager to load up newly created elementss
    $formModel->groups = null;
    $formModel->_loadGroupIds();
    $pluginManager =& $formModel->getPluginManager();
    unset($pluginManager->formplugins);
    $aGroups = $formModel->getGroupsHiarachy();
    $arAddedObj = array();
    //these two should be real elements not hacked in here
    $pluginManager =& JModel::getInstance('Pluginmanager', 'FabrikModel');
    foreach ($aGroups as $groupModel) {
      $elementModels =& $groupModel->getMyElements();
      foreach ($elementModels as $elementModel) {
        $element = $elementModel->getElement();
        /* replace all non alphanumeric characters with _ */
        $objname = preg_replace( "/[^A-Za-z0-9]/", "_", $element->name );
        /* any elements that are names the same (eg radio buttons) can not be entered twice into the database */
        if (!in_array( $objname, $arAddedObj )) {
          $arAddedObj[] = $objname;
          $objtype = $elementModel->getFieldDescription();
          if ($objname != "" && !is_null( $objtype )) {
            if (stristr( $objtype, 'not null')) {
              $sql .= " `$objname` $objtype, ";
            } else {
              $sql .= " `$objname` $objtype null, ";
            }
          }
        }
      }
    }
    $sql .= " primary key (fabrik_internal_id))";
    $tableDatabase->setQuery( $sql );
    if (!$tableDatabase->query()) {
      return JError::raiseWarning( 500, $db->getErrorMsg() );
    }
  }

  /**
   * when saving a table that links to a database for the first time we
   * need to create all the elements based on the database table fields and their
   * column type
   *
   * @access private
   * @param int group id
   * @param array newly created table data
   * @param array of element objects - if this is not empty then we've come from the csv import and the elements
   * have already been defined, use this instead of the field analysis to create correctly typed elements
   */

  function _createLinkedElements( $groupId, $aTableData, $aSpecificElements = array() )
  {
    $db 			=& JFactory::getDBO();
    $user  		= &JFactory::getUser();
    $config		=& JFactory::getConfig();
    $createdate = JFactory::getDate();
    $createdate = $createdate->toMySQL();

    $tableName = JRequest::getVar( 'db_table_name' );

    $ordering = 0;
    $fabrikDb =& $this->getDb();

    if (!empty( $aSpecificElements )) {
      //we're asking the method to create predefined elements - e.g. when installing sample data.
      foreach ($aSpecificElements as $elementModel) {
        $element =& $element->getElement();
        if ($element->label == 'id' || $element->label == 'fabrik_internal_id') {
          $element->hidden = 1;
          $element->primary_key = 1;
          $element->auto_increment = 1;
        } else {
          $element->hidden = 0;
        }
        $element->name = strtolower(str_replace(' ', '', $element->name ));
        $element->group_id = $groupId;
        $element->created = $createdate;
        $element->created_by = $user->get('id');
        $element->created_by_alias = $user->get('username');
        $element->state = 1;
        $element->show_in_table_summary = 1;
        $element->width = 30;
        $element->height = 6;
        $element->ordering = 99999;
        $element->attribs = $elementModel->getDefaultAttribs();
        $element->store();
        $where = " group_id = '" . $element->group_id . "'";
        $element->updateOrder( $where );
      }
    } else {
      //here we're importing directly from the database schema
      $db->setQuery( "SELECT id FROM #__fabrik_tables WHERE `db_table_name` = '$tableName'" );
      $id = $db->loadResult();
      if ($id) {
        //a fabrik table already exists - so we can copy the formatting of its elements

        $groupTableModel = JModel::getInstance( 'table', 'FabrikModel' );

        $groupTableModel->setId($id );
        $table = $groupTableModel->getTable();
        //$this->_oForm = null; //reset form so that it loads new table form
        $groups = $groupTableModel->getFormGroupElementData();
        foreach ($groups as $groupModel) {
          $elementModels =& $groupModel->getMyElements();
          foreach ($elementModels as $elementModel) {
            $element =& $elementModel->getElement();
            $copy = $elementModel->copyRow( $element->id, '', $groupId );
            //if its a database join then add in a new join record
            if (is_a( $elementModel, 'FabrikModelFabrikDatabasejoin' )) {
              $join 					=& JTable::getInstance( 'Join', 'Table' );
              $join->_tbl_key = 'element_id';
              $join->load($element->id);
              //
              $join->id = null;
              $join->element_id  = $copy->id;
              $join->_tbl_key = 'id';
              $join->group_id = $copy->group_id;
              $join->store();
            }
          }
        }
      } else {
        $elementModel =& JModel::getInstance('Element', 'FabrikModel');
        $fields = $fabrikDb->getTableFields( array ("`$tableName`") );
        $fields = $fields["`$tableName`"];
        // no existing fabrik table so we take a guess at the most
        //relavent element types to  create
        foreach ($fields as $label => $type) {
          $element =& JTable::getInstance( 'Element', 'Table' );
          $element->label = str_replace( "_", " ", $label );
          switch ( $type )
          {
            case "int" :
            case "tinyint" :
            case "varchar" :
              $plugin = 'fabrikfield';
              break;
            case "text" :
            case "tinytext" :
            case "mediumtext" :
            case "longtext" :
              $plugin = 'fabriktextarea';
              break;
            case "datetime" :
            case "date" :
            case "time" :
            case "timestamp" :
              $plugin = 'fabrikdate';
              break;
            default :
              $plugin = 'fabrikfield';
              break;
          }
          $element->plugin 						= $plugin;
          $element->hidden 						= ( $element->label == 'id' )? '1' : '0';
          $element->group_id 					= $groupId;
          $element->name				 			= $label;
          $element->created 					= $createdate;
          $element->created_by 				= $user->get('id');
          $element->created_by_alias 	= $user->get('username');
          $element->state 						= '1';
          $element->show_in_table_summary = '1';
          switch ($plugin) {
            case 'fabriktextarea':
              $element->width = '40';
              break;
            case 'fabrikdate':
              $element->width = '10';
              break;
            default:
              $element->width = '30';
              break;
          }
          //$element->width 						= ($plugin == 'fabriktextarea') ? '40' : '30';
          $element->height 						= '6';
          $element->ordering 					= $ordering;
          $element->attribs = $elementModel->getDefaultAttribs();
          if (!$element->store()) {
            return JError::raiseError( 500, $element->getError() );
          }
          $ordering ++;
        }
      }
    }
  }

  /**
   * updates the table record to point to the newly created form
   * @params int form id
   */

  function _updateFormId( $formId )
  {
    $db =& JFactory::getDBO();
    $table =& $this->getTable();
    $table->form_id = $formId;
    if (!$table->store()) {
      return JError::raiseWarning( 500, $db->getError() );
    }
  }


  /**
   * get the tables primary key and if the primary key is auto increment
   * @return mixed if ok returns array (key, extra, type, name) otherwise
   * returns false
   */

  function getPrimaryKeyAndExtra()
  {
    $origColNames = $this->getDBFields();
    if (is_array( $origColNames )) {
      foreach ($origColNames as $origColName) {
        $colName 	= $origColName->Field;
        $key 			= $origColName->Key;
        $extra 		= $origColName->Extra;
        $type 		= $origColName->Type;
        if ($key == "PRI") {
          return array( "key"=>$key, "extra"=>$extra, "type"=>$type, "colname"=>$colName );
        }
      }
    }
    return false;
  }

  /**
   * deals with ensuring joins are managed correctly when table is saved
   */

  function updateJoins()
  {
    $db =& JFactory::getDBO();
    // $$$rob getJoins adds in element joins as well - dont use as we can get ids to delete that
    //arent table joins
    //$aOldJoins 			= $this->getJoins( );
    $db->setQuery("SELECT * FROM #__fabrik_joins WHERE table_id = '$this->_id'");
    $aOldJoins = $db->loadObjectList();

    $aOldJoinsToKeep 	= array();
    $joinModel			=& JModel::getInstance( 'Join', 'FabrikModel' );
    $joinIds 				= JRequest::getVar( 'join_id', array(), 'post' );
    $joinTypes 			= JRequest::getVar( 'join_type', array(), 'post' );
    $joinTableFrom  = JRequest::getVar( 'join_from_table', array(), 'post' );
    $joinTable 			= JRequest::getVar( 'table_join', array(), 'post' );
    $tableKey				= JRequest::getVar( 'table_key', array(), 'post' );
    $joinTableKey		= JRequest::getVar( 'table_join_key', array(), 'post' );
    $groupIds				= JRequest::getVar( 'group_id', array(), 'post' );
    for ($i = 0; $i < count( $joinTypes ); $i++) {
      $existingJoin = false;
      foreach ($aOldJoins as $oOldJoin ) {
        if ($joinIds[$i] == $oOldJoin->id) {
          $existingJoin = true;
        }
      }
      //$$$rob make an index on the join element (fk)
      $els =& $this->getElements();
      foreach($els as $el){
        if( $el->getElement()->name == $tableKey[$i]){
          $size = stristr( $el->getFieldDescription(), 'int' ) ? '' : '10';
        }
      }
      $this->addIndex($tableKey[$i], 'join', 'INDEX', $size);
       
      if (!$existingJoin) {
        $this->_makeNewJoin( $tableKey[$i], $joinTableKey[$i], $joinTypes[$i], $joinTable[$i], $joinTableFrom[$i] );
      } else {
        /* load in the exisitng join
         * if the table_join has changed we need to create a new join
         * (with its corresponding group and elements)
         *  and mark the loaded one as to be deleted
         */
        $joinModel->setId( $joinIds[$i] );
        $joinModel->_join = null;
        $join =& $joinModel->getJoin();

        if ($join->table_join != $joinTable[$i]) {
          $this->_makeNewJoin( $tableKey[$i], $joinTableKey[$i], $joinTypes[$i], $joinTable[$i], $joinTableFrom[$i] );
        } else {
          //the talbe_join has stayed the same so we simply update the join info
          $join->table_key 		= str_replace('`', '', $tableKey[$i]);
          $join->table_join_key 	= $joinTableKey[$i];
          $join->join_type 		= $joinTypes[$i];
          $join->store();
          $aOldJoinsToKeep[] 		= $joinIds[$i];
        }
      }
    }
    /* remove non exisiting joins */
    if (is_array( $aOldJoins )) {
      foreach ($aOldJoins as $oOldJoin) {
        if (!in_array( $oOldJoin->id, $aOldJoinsToKeep )) {
          /* delete join */
          $join =& JTable::getInstance( 'Join', 'Table' );
          $joinModel->setId( $oOldJoin->id );
          $joinModel->getJoin();
          $joinModel->deleteAll( $oOldJoin->group_id );
        }
      }
    }
  }

  /**
   * run the prefilter sql and replace any placeholders in the subsequent prefilter
   *
   * @param string prefilter condition
   * @return string prefilter condition
   */

  function _prefilterParse( $selValue )
  {
    $preSQL = htmlspecialchars_decode( $this->_params->get('prefilter_query'), ENT_QUOTES );
    if (trim( $preSQL ) != '') {
      $db =& JFactory::getDBO();
      $w = new FabrikWorker();
      $preSQL = $w->parseMessageForPlaceHolder( $preSQL );
      $db->setQuery( $preSQL );
      $q = $db->loadObject( );
    }
    if (isset( $q )) {
      foreach ( $q as $key=>$val ) {
        if (substr( $key, 0, 1 ) != '_') {
          $found = false;
          if (strstr( $selValue, '{$q-&gt;'. $key )) {
            $found = true;
            $pattern = '{$q-&gt;'. $key. "}";
          }
          if (strstr( $selValue, '{$q->' . $key )) {
            $found = true;
            $pattern = '{$q->'. $key . "}";
          }
          if ($found) {
            $selValue = str_replace( $pattern, $val, $selValue );
          }
        }
      }
    } else {
      //parse for default values only
      $pattern = "/({[^}]+}).*}?/s";
      $ok = preg_match( $pattern, $selValue, $matches );
      foreach ($matches as $match) {
        $matchx = substr( $match, 1, strlen( $match ) - 2 );
        //a default option was set so lets use that
        if (strstr( $matchx, '|' )) {
          $bits = explode( '|', $matchx );
          $selValue = str_replace( $match, $bits[1], $selValue );
        }
      }
    }
    return $selValue;
  }

  /**
   * replaces the table column names with a safer name - ie removes white
   * space and none alpha numeric characters
   */

  function makeSafeTableColumns()
  {
    $form =& $this->getForm();
    $groups = $form->getGroupsHiarachy();
    $db = $this->getDb();
    $table =& $this->getTable();
    $origColNames = $this->getDBFields();
    foreach ($origColNames as $origColName) {
      $colName = strtolower($origColName->Field);
      $type = $origColName->Type;
      $fbConfig =& JComponentHelper::getParams( 'com_fabrik' );
      if ($fbConfig->get('use_wip', false)) {
        // $$$ was causing some problems for a site that was foolishly using - in columns names
        $newColName = preg_replace("/[^A-Za-z0-9-]/", "_", $colName);
      }
      else {
        $newColName = preg_replace("/[^A-Za-z0-9]/", "_", $colName);
      }


      if ($colName != $newColName) {
        // $$$rob - if we change an underlying column name, then we need to also alter the coresponding
        // element name itself, otherwise things go hinky!
        foreach ($groups as $groupModel) {
          $elementModels =& $groupModel->getMyElements();
          foreach ($elementModels as $elementModel ) {
            $element =& $elementModel->getElement();
            if ($element->name == $colName) {
              $element->name = $newColName;
              $element->store();
            }
             
          }
        }
        $sql = "ALTER TABLE `$table->db_table_name` CHANGE `$colName` `$newColName` $type";
        $db->setQuery( $sql );
        if (!$db->query()) {
          JError::raiseWarning( 500, $db->getErrorMsg( ));
        }
      }
    }
  }

  /**
   * adds a primary key to the database table
   * @param string the column name to make into the primary key
   * @param bol is the column an auto incrementing number
   * @param string column type definition (eg varchar(255))
   */

  function updatePrimaryKey( $fieldName, $autoIncrement, $type = 'int(11)' )
  {
    if (!$this->_canAlterFields()) {
      return;
    }
    $fabrikDatabase =& $this->getDb();
    $aPriKey = $this->getPrimaryKeyAndExtra();
    if (!$aPriKey) { // no primary key set so we should set it
      $this->_addKey( $fieldName, $autoIncrement, $type );
    } else {
      $shortKey = $this->_shortKey( $fieldName );
      if ($fieldName !=  $aPriKey['colname'] && $shortKey != $aPriKey['colname']) {
        $this->_dropKey( $aPriKey ); // primary key already exists so we should drop it
        $this->_addKey( $fieldName, $autoIncrement, $type );
      } else {
        //update the key
        $this->_updateKey( $fieldName, $autoIncrement, $type );
      }
    }
  }

  /**
   * internal function: update an exisitng key in the table
   * @param string primary key column name
   * @param bol is the column auto incrementing
   * @param string the primary keys column type
   */

  function _updateKey( $fieldName, $autoIncrement, $type = "INT(11)" )
  {
    $db =& $this->getDb();
    if (strstr($fieldName, '.')) {
      $fieldName = array_pop(explode(".", $fieldName));
    }
    $table =& $this->getTable();
    $sql = "ALTER TABLE `$table->db_table_name` CHANGE `$fieldName` `$fieldName` $type NOT NULL ";
    /* update primary key */
    if ($autoIncrement) {
      $sql .= " AUTO_INCREMENT";
    }
    $db->setQuery( $sql );
    if (!$db->query()) {
      JError::raiseWarning( 500, 'update key:'.$db->getErrorMsg( ));
    }
  }

  /**
   * internal function: add a key to the table
   * @param string primary key column name
   * @param bol is the column auto incrementing
   * @param string the primary keys column type (if autoincrement true then int(6) is always used as the type)
   */

  function _addKey( $fieldName, $autoIncrement, $type = "INT(6)" )
  {
    $db        =& $this->getDb();
    $type      = $autoIncrement != true ? $type : 'INT(6)';
    $table     =& $this->getTable();
    $fieldName = FabrikString::shortColName( $fieldName );
    if ($fieldName === "") {
      return false;
    }
    $sql = "ALTER TABLE `$table->db_table_name` ADD PRIMARY KEY ($fieldName)";
    /* add a primary key */

    $db->setQuery( $sql );
    if (!$db->query()) {
      JError::raiseWarning(500, $db->getErrorMsg( ));
    }
    if ($autoIncrement) {
      $sql = "ALTER TABLE `$table->db_table_name` CHANGE $fieldName $fieldName " . $type .  " NOT NULL AUTO_INCREMENT"; //add the autoinc
      $db->setQuery( $sql );
      if (!$db->query()) {
        return JError::raiseError( 500, 'add key: ' . $db->getErrorMsg( ));
      }
    }
  }

  /**
   * add an index to the table
   * @param string field name
   * @param stirng index name prefix (allows you to differentiate between indexes created in
   * different parts of fabrik)
   * @param string index type
   * @param int index length
   */

  public function addIndex( $field, $prefix = '', $type = 'INDEX', $size = 10 )
  {
    $db =& $this->getDb();
    $table = $this->getTable()->db_table_name;
    $field = FabrikString::shortColName( $field );

    if ($field == '') {
      return;
    }
    if ($size != '') {
      $size = "( $size )";
    }
    $query = " ALTER TABLE `$table` ADD INDEX `fb_{$prefix}_{$field}_{$type}` ( `$field` $size)  ";
    $db->setQuery( $query );
    $db->query();
  }

  /**
   * drop an index
   * @param string field name
   * * @param stirng index name prefix (allows you to differentiate between indexes created in
   * different parts of fabrik)
   * @return string index type
   */

  public function dropIndex( $field, $prefix = '', $type = 'INDEX' )
  {
    $db =& $this->getDb();
    $table = $this->getTable()->db_table_name;
    $field = FabrikString::shortColName( $field );
    if ($field == '') {
      return;
    }
    $db->setQuery( " ALTER TABLE `$table` DROP INDEX `fb_{$prefix}_{$field}_{$type}`");
    $db->query();
  }

  /**
   * internal function: drop the table's key
   * @param array existing key data
   * @return bol true if ke droped
   */

  function _dropKey( $aPriKey )
  {
    $db = $this->getDb();
    $table =& $this->getTable();
    $sql = "ALTER TABLE `$table->db_table_name` CHANGE `" . $aPriKey['colname'] . '` `'. $aPriKey['colname'] . '` '  . $aPriKey['type'] . " NOT NULL";
    /* removes the autoinc */
    $db->setQuery( $sql );
    if (!$db->query( )) {
      JError::raiseWarning( 500, $db->getErrorMsg()) ;
      return false;
    }
    $sql = "ALTER TABLE `$table->db_table_name` DROP PRIMARY KEY";
    /* drops the primary key */
    $db->setQuery( $sql );
    if (!$db->query()) {
      JError::raiseWarning( 500, 'alter table: ' . $db->getErrorMsg()) ;
      return false;
    }
    return true;
  }

  /**
   * deletes records from a table
   * @param string key value to delete
   * @param string key to use (leave empty to default to the table's key)
   * @return string error message
   */

  function deleteRows( $val, $key = '' )
  {
    $table 	=& $this->getTable();
    $db 		=& $this->getDb();

    if ($key == '') {
      $key = $table->db_primary_key;
      if ($key == '') {
        return JError::raiseWarning( JText::_( "NO KEY FOUND FOR THIS TABLE" ));
      }
    }

    if (is_array( $val )) {
      $c = count($val);
      $val = implode("','", $val);
    } else {
      $c = 1;
    }

    //@todo: run element->onDeleteRow()
    //load in rows to be deleted#
    $nav =& $this->getPagination( $c, 0, $c );
    $this->_whereSQL[true] = " WHERE " . $key . " IN ('" . $val . "')";
    $rows 	=& $this->getData();
    $this->_rowsToDelete =& $rows;
    $groupModels =& $this->getFormGroupElementData();

    foreach ($groupModels as $groupModel) {
      $elementModels =& $groupModel->getPublishedElements();
      foreach ($elementModels as $elementModel) {
        $elementModel->onDeleteRows( $rows );
      }
    }

    $pluginManager =& JModel::getInstance( 'Pluginmanager', 'FabrikModel' );
    $pluginManager->getPlugInGroup( 'table' );
    if (!$pluginManager->runPlugins( 'onDeleteRows', $this, 'table' )) {
      return;
    }

    $sql = 	"DELETE FROM `" .  $table->db_table_name  . "` WHERE " . $key . " IN ('" . $val . "')";
    $db->setQuery( $sql );

    if (!$db->query()) {
      return JError::raisWarning( $db->getErrorMsg() );
    }
    return true;
  }

  /**
   * remove all records from the table
   */

  function dropData()
  {
    $db =& $this->getDb();
    $table =& $this->getTable();
    $sql = "DELETE FROM " .  $table->db_table_name;
    $db->setQuery( $sql );
    $msg= '';
    if (!$db->query( )) {
      return JError::raiseWarning( JText::_($db->getErrorMsg( ) ));
    }
    return '';
  }

  /**
   * drop the table containing the fabriktables data
   */

  function drop()
  {
    $db =& $this->getDb();
    $table =& $this->getTable();
    $sql = "DROP TABLE IF EXISTS `$table->db_table_name`;";
    $db->setQuery( $sql );
    if ( !$db->query( ) ) {
      return JError::raiseWarning( JText::_($db->getErrorMsg( ) ));
    }
    return '';
  }

  function truncate()
  {
    $db =& $this->getDb();
    $table =& $this->getTable();
    $db->setQuery( "TRUNCATE `$table->db_table_name`");
    $db->query();
  }

  /**
   *  new join make the group, group elements and formgroup entries for the join data
   * @param string table key
   * @param string join to table key
   * @param string join type
   * @param string join to table
   * @param string join table
   */

  function _makeNewJoin( $tableKey, $joinTableKey, $joinType, $joinTable, $joinTableFrom )
  {
    $db 	=& JFactory::getDBO();
    $formModel 	=& $this->getForm( );
    $aData = array(
			"name" => $this->_table->label ."- [" .$joinTable. "]",
			"label" =>  $joinTable,
    );
    $groupId = $this->_createLinkedGroup( $aData, true );

    $origTable = JRequest::getVar( 'db_table_name' );
    JRequest::setVar( 'db_table_name', $joinTable );
    $this->_createLinkedElements( $groupId, array() );
    JRequest::setVar('db_table_name', $origTable);
    $join =& JTable::getInstance( 'Join', 'Table');
    $join->table_id 		= $this->_id;
    $join->join_from_table = $joinTableFrom;
    $join->table_join 		= $joinTable;
    $join->table_join_key 	= $joinTableKey;
    $join->table_key 		= str_replace( '`', '', $tableKey );
    $join->join_type 		= $joinType;
    $join->group_id 		= $groupId;
    if(!$join->store( )){
      return JError::raiseWarning( 500, $join->getError() );
    }
  }

  /**
   * test if a field already exists in the database
   *
   * @param string $field
   * @param array id's to ignore.
   * @return bol
   */

  function fieldExists( $field, $ignore = array() )
  {
    $field = strtolower( $field );
    $groupModels =& $this->getFormGroupElementData();
    foreach ($groupModels as $groupModel) {
      if (!$groupModel->isJoin()) {//dont check groups that aren't in this table
        $elementModels =& $groupModel->getMyElements();
        foreach ($elementModels as $elementModel) {
          $element =& $elementModel->getElement();
          $n = strtolower($element->name);
          if (strtolower($element->name) == $field && !in_array($element->id, $ignore)) {
            return true;
          }
        }
      }
    }
    return false;
  }

  /**
   * Alter the forms' data collection table when the forms' groups and/or
   * elements are altered
   * @param object form
   * @param string table name
   * @param object database connection object
   */

  function ammendTable( &$oForm, $tableName = null, $tableDatabase = null ){
    $db 		=& JFactory::getDBO();
    $user  	=& JFactory::getUser();
    $table =& $this->getTable();
    $pluginManager =& JModel::getInstance( 'Pluginmanager', 'FabrikModel' );
    $ammend 		= false;
    if (is_null( $tableName )) {
      $tableName = $table->db_table_name;
    }
    if (is_null( $tableDatabase ) || !is_object( $tableDatabase )) {
      //$tableDatabase = $db;
      $tableDatabase =& $this->getDb();
    }

    $dbdescriptions = $this->getDBFields( $tableName );
    //@TODO: test (was strtolower($dbdescription->Field))  if this is going to cause issues, think fields should be case insenitvely compared as some joomla core fields are mixed case
    foreach ($dbdescriptions as $dbdescription) {
      $exitingfields[] = strtolower($dbdescription->Field);
    }
    $lastfield = $exitingfields[count($exitingfields)-1];
    $sql = "ALTER TABLE `$tableName` " ;
    if (!isset( $_POST['current_groups_str'] )) {
      /* get a list of groups used by the form */
      $groupsql = "SELECT group_id FROM #__fabrik_formgroup WHERE form_id = '" . $oForm->_id . "'";
      $db->setQuery( $groupsql );
      $groups = $db->loadObjectList();
      if (!$groups) {
        JError::raiseWarning(500,  'ammendTable: ' . $fabrikDb->getErrorMsg() );
      }
      $arGroups = array( );
      foreach ($groups as $g) {
        $arGroups[] = $g->group_id;
      }
    } else {
      $current_groups_str = JRequest::getVar( 'current_groups_str' );

      $arGroups = explode( ",", $current_groups_str );
    }

    $arAddedObj = array();
    foreach ($arGroups as $group_id) {
      $group = JTable::getInstance( 'Group', 'Table' );
      $group->load( $group_id );
      if ($group->is_join == '0') {
        $groupsql = "SELECT * FROM #__fabrik_elements WHERE group_id = '$group_id'";
        $db->setQuery( $groupsql );
        $elements = $db->loadObjectList();
        foreach ($elements as $obj) {
          $objname = strtolower(preg_replace("/[^A-Za-z0-9]/", "_", $obj->name));
          /* replace all non alphanumeric characters with _*/
          if (!in_array( $objname, $exitingfields )) {
            /* make sure that the object is not already in the table*/
            if (!in_array( $objname, $arAddedObj )) {
              /* any elements that are names the same (eg radio buttons) can not be entered twice into the database*/
              $arAddedObj[] 		= $objname;
              $objtypeid 				= $obj->plugin;
              $pluginClassName 	= $obj->plugin;
              $plugin 					= $pluginManager->getPlugIn( $pluginClassName, 'element' );
              $objtype 					= $plugin->getFieldDescription();
              if ($objname != "" && !is_null( $objtype )) {
                $ammend = true;
                $sql .= ", ADD COLUMN `$objname` $objtype null AFTER `$lastfield`";
              }
            }
          }
        }
      }
    }
    if ($ammend) {
      $tableDatabase->setQuery( $sql );
      if (!$tableDatabase->query()) {
        return JError::raiseWarning( 500, 'amend table: ' . $tableDatabase->getErrorMsg() ) ;
      }
      $this->createCacheQuery();
    }
  }

  /**
   * @param int connection id to use
   * @param string table to load fields for
   * @param string show "please select" top option
   * @param bol append field name values with table name
   * @param string name of drop down
   * @param string selected option
   * @param string class name
   * @return string html to be added to DOM
   */

  function getFieldsDropDown( $cnnId, $tbl, $incSelect, $incTableName = false, $selectListName = 'order_by', $selected = null, $className = "inputbox" ){
    $this->setConnectionId( $cnnId );
    $aFields = $this->getDBFields( $tbl );
    $fieldNames = array( );
    if ($incSelect != '') {
      $fieldNames[] = JHTML::_('select.option', '', $incSelect );
    }
    if (is_array( $aFields ) ) {
      foreach( $aFields as $oField ) {
        if ($incTableName ){
          $fieldNames[] = JHTML::_('select.option', $tbl . "___" . $oField->Field, $oField->Field );
        } else {
          $fieldNames[] = JHTML::_('select.option', $oField->Field );
        }
      }
    }
    $fieldDropDown = JHTML::_('select.genericlist',  $fieldNames, $selectListName, "class=\"$className\"  size=\"1\" ", 'value', 'text', $selected );
    return str_replace("\n", "", $fieldDropDown);
  }

  /**
   * used in advanced search
   * @param bol add slashes to reutrn data
   */

  function getFilterJoinDd( $addSlashes = true, $name = 'join' ){
    $aConditions = array( );
    $aConditions[] = JHTML::_('select.option', 'AND' );
    $aConditions[] = JHTML::_('select.option', 'OR' );
    $dd = str_replace("\n", "", JHTML::_('select.genericlist',  $aConditions, $name, "class=\"inputbox\"  size=\"1\" ", 'value', 'text', '' ));
    if ($addSlashes ){
      $dd = addslashes( $dd );
    }
    return $dd;
  }

  /**
   *used in advanced search
   *@param bol add slashes to reutrn data
   *@param string name of the drop down
   *@param int mode - states what values get put into drop down
   */

  function getFilterConditionDd( $addSlashes = true, $name = 'conditions', $mode = 1 ){
    $aConditions = array( );
    switch ($mode){
      case 1:
        /* used for search filter */
        $aConditions[] = JHTML::_('select.option', '<>', 'NOT EQUALS' );
        $aConditions[] = JHTML::_('select.option', '=', 'EQUALS' );
        $aConditions[] = JHTML::_('select.option', 'like', 'BEGINS WITH' );
        $aConditions[] = JHTML::_('select.option', 'like', 'CONTAINS' );
        $aConditions[] = JHTML::_('select.option', 'like', 'ENDS WITH' );
        $aConditions[] = JHTML::_('select.option', '>', 'GREATER THAN' );
        $aConditions[] = JHTML::_('select.option', '>=', 'GREATER THAN OR EQUALS' );
        $aConditions[] = JHTML::_('select.option', '<', 'LESS THAN' );
        $aConditions[] = JHTML::_('select.option', '<=', 'LESS THAN OR EQUALS' );
        break;
      case 2:
        /* used for prefilter */
        $aConditions[] = JHTML::_('select.option', 'equals', 'EQUALS' );
        $aConditions[] = JHTML::_('select.option', 'notequals', 'NOT EQUAL TO' );
        $aConditions[] = JHTML::_('select.option', 'begins', 'BEGINS WITH' );
        $aConditions[] = JHTML::_('select.option', 'contains', 'CONTAINS' );
        $aConditions[] = JHTML::_('select.option', 'ends', 'ENDS WITH' );
        $aConditions[] = JHTML::_('select.option', '>', 'GREATER THAN' );
        $aConditions[] = JHTML::_('select.option', '>=', 'GREATER THAN OR EQUALS' );
        $aConditions[] = JHTML::_('select.option', '<', 'LESS THAN' );
        $aConditions[] = JHTML::_('select.option', 'IS NULL', 'IS NULL' );
        $aConditions[] = JHTML::_('select.option', '<=', 'LESS THAN OR EQUALS' );
        $aConditions[] = JHTML::_('select.option', 'in', 'IN' );
        $aConditions[] = JHTML::_('select.option', 'not_in', 'NOT IN' );
        break;
    }
    $dd = str_replace("\n", "", JHTML::_('select.genericlist',  $aConditions, $name, "class=\"inputbox\"  size=\"1\" ", 'value', 'text', '' ));
    if ($addSlashes ) {
      $dd = addslashes( $dd );
    }
    return $dd;
  }

  /**
   * create the RSS href link to go in the table template
   * @return string RSS link
   */

  function getRSSFeedLink()
  {
    global $Itemid;
    $link = '';
    if ($this->_params->get('rss') == '1' ) {
      //$$$ rob test fabriks own feed renderer
      //$link = 'index.php?option=com_fabrik&view=table&tableid=' . $this->_id . "&format=feed";
      $link = 'index.php?option=com_fabrik&view=table&tableid=' . $this->_id . "&format=fabrikfeed";
      if (!$this->_admin) {
        $link = JRoute::_( $link );
      }
    }
    return $link;
  }

  /**
   * iterates through string to replace every
   * {placeholder} with row data
   * (added by hugh, does the same thing as parseMessageForPlaceHolder in parent
   * class, but for rows instead of forms)
   * @param string text to parse
   */

  function parseMessageForRowHolder( $msg, &$row ){
    $this->_aRow = $row;
    $msg = FabrikWorker::_replaceWithUserData( $msg );
    $msg = FabrikWorker::_replaceWithGlobals( $msg );
    $msg = preg_replace( "/{}/", "", $msg );
    /* replace {element name} with form data */
    // $$$ hugh - testing changing the regex so we don't blow away PHP structures!  Added the \s so
    // we only match non-space chars in {}'s.  So unless you have some code like "if (blah){foo;}", PHP
    // block level {}'s should remain unmolested.
    $msg = preg_replace_callback( "/{[^}\s]+}/i", array($this,'_replaceWithRowData'), $msg );
    return $msg;
  }

  /**
   * PRVIATE:
   * called from parseMessageForRowHolder to iterate through string to replace
   * {placeholder} with row data
   * @param string placeholder e.g. {placeholder}
   * @param array row
   * @return string posted data that corresponds with placeholder
   */

  function _replaceWithRowData( $matches ){
    $match = $matches[0];
    /* strip the {} */
    $match = substr( $match, 1, strlen($match) - 2 );

    //$match = strtolower( $match );
    $match = str_replace('.','___',$match);
    // $$$ hugh - allow use of {$rowpk} or {rowpk} to mean the rowid of the rwo within a table
    if ($match == 'rowpk' or $match == '$rowpk')
    {
      $match = $this->_table->db_primary_key;
      $match = preg_replace('#^(\w+\.)#','',$match);
    }
    $match = preg_replace( "/ /", "_", $match );
    return($this->_aRow[$match]);
  }

  /**
   * get the link to view the records details
   * @param int record id
   * @param int record cursor
   */

  function viewDetailsLink( $id, $cursor = 0 )
  {
    global $Itemid;
    $link = '';
    if ($this->canViewDetails()) {
      //TODO: test this in admin table & front end
      if ($this->_outPutFormat == 'json' || $this->_outPutFormat == 'raw' || $this->getPostMethod() == 'ajax' ){
        $link = "#" ;
      } else {
        $table =& $this->getTable();
        $action = $this->_admin ? 'task' : 'view';
        $link = 'index.php?option=com_fabrik&c=form&'.$action.'=details&tableid=' . $this->_id . '&fabrik=' . $table->form_id . '&rowid=' . $id . '&Itemid=' . $Itemid;
        if (!$this->_admin) {
          $link = JRoute::_( $link );
        }
      }
      $link = "<a href=\"$link\" class='fabrik___viewrowlink'>" . JText::_('VIEW') . "</a>";
    }
    return $link;
  }


  /**
   * make the drop slq statement for the table
   * @return string drop table sql
   */

  function getDropTableSQL()
  {
    $genTable 	= $this->getGenericTableName();
    $sql = "DROP TABLE IF EXISTS `$genTable`;";
    return $sql;
  }

  function getGenericTableName()
  {
    global $mainframe;
    $table =& $this->getTable();
    return str_replace( $mainframe->getCfg('dbprefix'), '#__', $table->db_table_name );
  }

  /**
   * make the create sql statement for the table
   * @return string sql to drop & or create table
   */

  function getCreateTableSQL( )
  {
    $table 	= $this->getGenericTableName( );
    $fields 	=  $this->getDBFields( );
    $primaryKey = "";
    $sql 		= "";
    FabrikString::safeColName( $table );
    if (is_array( $fields )) {
      $sql .= "CREATE table " .  $table ." (\n";
      foreach ( $fields as $field ) {
        FabrikString::safeColName( $field->Field );
        if ($field->Key == 'PRI'){
          $primaryKey = "PRIMARY KEY ($field->Field)";
        }

        $sql .=	"$field->Field ";

        if ($field->Key == 'PRI'){
          $sql .= ' INT(6) ';
        } else {
          $sql .= ' ' . $field->Type . ' ';
        }
        if ($field->Null == '' ) {
          $sql .= " NOT NULL ";
        }
        if ($field->Default != '' && $field->Key != 'PRI' ) {
          if($field->Default == 'CURRENT_TIMESTAMP'){
            $sql .= "DEFAULT $field->Default";
          } else {
            $sql .= "DEFAULT '$field->Default'";
          }
        }
        if ($field->Key == 'PRI') {
          $sql .= " AUTO_INCREMENT ";
        }

        $sql .= $field->Extra . ",\n";
      }
      if ($primaryKey == '') {
        $sql = rtrim($sql,",\n" );
      }
      $sql .= $primaryKey . ");";
    }
    return $sql;
  }

  /**
   * make the create sql statement for inserting the table data
   * used in package export
   * @param object exporter
   * @return string sql to drop & or create table
   */

  function getInsertRowsSQL( $oExporter )
  {
    @set_time_limit(300);
    $table =& $this->getTable();
    $memoryLimit = ini_get( 'memory_limit' );
    $db 		=& $this->getDb();
    //dont load in all the table data as on large tables this gives a memory error
    //in fact this wasnt the problem, but rather the $sql var becomes too large to hold in memory
    //going to try saving to a file on the server and then compressing that and sending it as a header for download

    $db->setQuery( "SELECT $table->db_primary_key FROM `$table->db_table_name`" );
    $keys = $db->loadResultArray( );
    $sql = "";
    $dump_buffer_len = 0;
    if (is_array( $keys )) {
      foreach ($keys as $id) {
        $db->setQuery( "SELECT * FROM `$table->db_table_name` WHERE $table->db_primary_key = $id" );
        $row = $db->loadObject();
        $fmtsql = "\t<query>INSERT INTO $table->db_table_name ( %s ) VALUES ( %s )</query>";
        $values = array();
        $fields = array();
        foreach ( $row as $k => $v) {
          $fields[] = $db->NameQuote( $k );
          $values[] = $db->Quote( $v );
        }
        $sql .= sprintf( $fmtsql, implode( ",", $fields ) ,  implode( ",", $values ) );
        $sql .= "\n";

        $dump_buffer_len += strlen( $sql );
        if ($dump_buffer_len  > $memoryLimit) {
          $oExporter->writeExportBuffer( $sql );
          $sql = "";
          $dump_buffer_len = 0;
        }
        unset( $values );
        unset( $fmtsql );
      }
    }
    $oExporter->writeExportBuffer( $sql );
  }

  /**
   * records data from a fabrik RSS feed into the table
   * feeds need to be in <dl> format
   * @param string html containing <dl> list
   * @return string out come message can be'saved','error saving',return 'duplicate', 'error'
   */

  function recordFromRSSFeed( $html )
  {
    $table =& $this->getTable();
    $db =& JFactory::getDBO();
    require_once(JPATH_SITE . '/includes/domit/xml_domit_lite_include.php');
    //strip cdata text from html
    $html = str_replace( array(']]>', '<![CDATA['), '', $html );

    $xmlDoc = new DOMIT_Lite_Document();
    $ok = $xmlDoc->parseXML( $html );
    $aData = array('test');
    if ($ok) {
      $key = '';
      $val = '';
      $debug = '';
      $aPseudoKeys = array();
      $isPseudoKey = false;
      $numDefinitionLists = count($xmlDoc->documentElement->childNodes);

      for ( $i = 0; $i < $numDefinitionLists; $i++ ) {
        $currentChannel =& $xmlDoc->documentElement->childNodes[$i];

        if (($i % 2) == 0){ // even so its the col heading
          $label = $currentChannel->firstChild->nodeValue;
          $key = $currentChannel->getAttribute("value");
          $isPseudoKey = $currentChannel->getAttribute("key");

        } else {
          $val = $currentChannel->firstChild->nodeValue;

          if ($key != $table->db_primary_key && $key != '') {
            $aData[$key] = $val;
          }
          if ($isPseudoKey == '1' ) {
            $aPseudoKeys[] = "$key = '$val'";
          }
        }
      }
      //test if record already exists?
      $res = 0;
      if (count( $aPseudoKeys ) > 0) {
        $sql = "SELECT count(*) FROM $table->db_table_name WHERE " . implode( ' AND ', $aPseudoKeys );
        $db->setQuery( $sql );
        $res = $db->loadResult( );
      }
      if ($res == 0) {
        if ($this->storeRow( $aData, 0 ) ) {
          return 'saved';
        } else {
          return 'error saving';
        }
      } else {
        return 'duplicate';
      }
    }
    return 'error';
  }

  /**
   * get a row of data from the table
   *
   * @param int $id
   * @return object row
   */

  function getRow( $id )
  {
    $fabrikDb =& $this->getDb();
    $formModel =& $this->getForm();
    $formModel->_rowId = $id;
    $sql = $formModel->_buildQuery();
    $fabrikDb->setQuery( $sql );
    $row = $fabrikDb->loadObject();
    return $row;
  }

  /**
   * ajax get record specified by row id
   */

  function xRecord( $id )
  {
    $cursor = JRequest::getInt( 'cursor', 1 );
    $this->getConnection( );
    $this->_outPutFormat = 'json';
    $nab	=& $this->getPagination( 1, 0, 1 );
    $data = $this->getData();
    return FastJSON::encode( $data );
  }

  /**
   * ajax get next record
   * @return string json object representing record/row
   */

  function nextRecord()
  {
    $cursor = JRequest::getInt( 'cursor', 1 );
    $this->getConnection();
    $this->_outPutFormat = 'json';
    $nav		=& $this->getPagination( 1, $cursor, 1 );
    $data = $this->getData();
    echo FastJSON::encode( $data );
  }

  /**
   * ajax get previous record
   * @return string json object representing record/row
   */

  function previousRecord()
  {
    $cursor = JRequest::getInt( 'cursor', 1 );
    $this->getConnection();
    $this->_outPutFormat = 'json';
    $nav		=& $this->getPagination( 1, $cursor-2, 1 );
    $data = $this->getData();
    return FastJSON::encode( $data );
  }

  /**
   * ajax get first record
   * @return string json object representing record/row
   */

  function firstRecord()
  {
    $cursor = JRequest::getInt( 'cursor', 1 );
    $this->getConnection();
    $this->_outPutFormat = 'json';
    $nav		=& $this->getPagination( 1, 0, 1 );
    $data = $this->getData();
    return FastJSON::encode( $data );
  }

  /**
   * ajax get last record
   * @return string json object representing record/row
   */

  function lastRecord()
  {
    $total = JRequest::getInt( 'total', 0 );
    $this->getConnection();
    $this->_outPutFormat = 'json';
    $nav		=& $this->getPagination( 1, $total-1, 1 );
    $data = $this->getData( );
    return FastJSON::encode( $data );
  }


  /**
   *  get a single column of data from the table, test for element filters
   * @param string column to get
   * @return array values for the column
   */

  function getColumnData( $col )
  {
    $table =& $this->getTable();
    $db =& $this->getDb();
    FabrikString::safeColName( $col );
    $tablename =  $table->db_table_name;
    FabrikString::safeColName( $tablename );
    $query  	= "SELECT DISTINCT($col) FROM " . $tablename . ' ' . $this->_buildQueryJoin();
    $query 	.= $this->_buildQueryWhere( false );
    $db->setQuery( $query );
    $res  	= $db->loadResultArray();
    return $res;
  }

  /**
   * determine how the model posts data ajax/post
   *
   * @return string post method
   */

  function getPostMethod()
  {
    if (is_null($this->_postMethod)){
      $this->_postMethod = JRequest::getVar( '_postMethod', 'post' );
    }
    return $this->_postMethod;
  }

  /**
   * get an array of the table's elements that match a certain plugin type
   *
   * @param string $plugin
   * @return array matched element models
   */

  function getElementsOfType( $plugin )
  {
    $found = array();
    $groups =& $this->getFormGroupElementData();
    foreach ($groups as $groupModel) {
      $elementModels =& $groupModel->getMyElements();
      foreach ($elementModels as $elementModel) {
        $element =& $elementModel->getElement();
        if ($element->plugin == $plugin) {
          $found[] =  $elementModel;
        }
      }
    }
    return $found;
  }

  function getElements()
  {
    $found = array();
    $groups =& $this->getFormGroupElementData();
    foreach ($groups as $groupModel) {
      $elementModels =& $groupModel->getMyElements();
      foreach ($elementModels as $elementModel) {
        $element =& $elementModel->getElement();
        $found[] =  $elementModel;
      }
    }
    return $found;
  }

  /**
   * determines if the talbe needs mocha js classes loaded
   *
   * @return bol true if required
   */

  function requiresMocha()
  {
    if ($this->canCSVExport()) {
      return true;
    }
    $params =& $this->getParams();
    if ($params->get('advanced-filter') == 1) {
      return true;
    }
    $pluginManager =& $this->getPluginManager();
    $activePlugins = $params->get( 'plugin', array(), '_default',  'array' );

    $tableplugis =& $pluginManager->getPlugInGroup( 'table' );

    foreach ($tableplugis as $name => $plugin) {
      if (in_array( $name, $activePlugins )) {
        if ($plugin->requiresMocha()) {
          return true;
        }
      }
    }
    return false;
  }

  /**
   * does the table need to include the slimbox js code
   *
   * @return bol
   */

  function requiresSlimbox()
  {
    $form =& $this->getForm();
    $groups =& $form->getGroupsHiarachy();
    foreach ($groups as $group) {
      $elements =& $group->getPublishedElements();
      foreach($elements as $elementModel) {
        $element =& $elementModel->getElement();
        if ($element->show_in_table_summary && $elementModel->requiresLightBox()) {
          return true;
        }
      }
    }
    return false;
  }

  /**
   * get pluginmanager (get reference to form's plugin manager
   *
   * @return object plugin manager model
   */

  function getPluginManager()
  {
    $form =& $this->getForm();
    return $form->getPluginManager();
  }

}
?>