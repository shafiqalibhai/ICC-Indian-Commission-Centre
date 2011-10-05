<?php
/**
 * Plugin element to render fields
 * @package fabrikar
 * @author Rob Clayburn
 * @copyright (C) Rob Clayburn
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

require_once( JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'models'.DS.'element.php' );

//these requires are needed for when the dd is trying to update itself via a package call
require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'json.php' );
require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'parent.php' );

class FabrikModelFabrikDatabasejoin  extends FabrikModelElement {

	var $_pluginName = 'databasejoin';

	var $xmlPath = null; //@TODO is this used???

	var $_aVals = array();

	/** @var object connection */
	var $_cn = null;

	var $_joinDb = null;

	/** @var created in getJoin **/
	var $_join = null;

	/** @var string for simple join query*/
	var $_sql = null;

	/** @var array option values **/
	var $_optionVals = null;

	/** @var bol is a join element */
	var $_isJoin = true;
	
	/**
	 * Constructor
	 */

	function __construct()
	{
		parent::__construct();
	}

	/**
	 * testing to see that if the aFields are passed by reference do they update the table object?
	 * @param array containing field sql
	 * @param array containing field aliases
	 * @param string table name
	 */

	function getAsField_html( &$aFields, &$aAsFields, $table )
	{
	  $params =& $this->getParams();
		$db =& JFactory::getDBO();
		$tableModel =& $this->_table;

		$element =& $this->getElement();
		$tableRow = $tableModel->getTable();
		$joins =& $tableModel->getJoins();
		foreach ($joins as $tmpjoin) {
			if ($tmpjoin->element_id == $element->id) {
				$join =& $tmpjoin;
				break;
			}
		}

		$connection =& $tableModel->getConnection();
		//make sure same connection as this table and that its not an advanced join
		$fullElName = $table . "___" . $element->name;
		if ($params->get( 'join_conn_id' ) == $connection->_id || $element->plugin != 'fabrikdatabasejoin') {

			$join =& $this->getJoin();
			$joinTableName  	=  $join->table_join_alias;

			$tables = $this->getLinkedFabrikTables();

			//	store unjoined values as well (used in non-join group table views)
			//this wasnt working for test case:
			//events -> (db join) event_artists -> el join (artist)
			$k = "`$join->keytable`.`$element->name`";
			FabrikString::safeColName($k);

			$k2 = $this->getJoinLabelColumn();

			$aFields[]				= "$k AS `$fullElName" . "_raw`" ;
			$aAsFields[]			= "`$fullElName". "_raw`";
			$aFields[] 				= "$k2 AS `$fullElName`" ;
			$aAsFields[] 			= "`$fullElName`";

		} else {

			$aFields[] 		= "`$table`.`$element->name` AS `$fullElName`" ;
			$aAsFields[] 	= "`$fullElName`";
		}
	}

	/**
	 * get the field name to use as the column that contains the join's label data
	 *
	 * @return string join label column either returns concat statement or quotes `tablename`.`elementname`
	 */

	function getJoinLabelColumn()
	{
		$params =& $this->getParams();
		if (($params->get('join_val_column_concat') != '')) {
			return "CONCAT(".$params->get('join_val_column_concat').")";
		}
		$join =& $this->getJoin();
		$label 	= $join->_params->get( 'join-label' );
		$joinTableName  	=  $join->table_join_alias;
		return "`$joinTableName`.`$label`";
	}

	/**
	 * get as field for csv export
	 * can be overwritten in the plugin class - see database join element for example
	 * testing to see that if the aFields are passed by reference do they update the table object?
	 *
	 * @param array containing field sql
	 * @param array containing field aliases
	 * @param string table name
	 */

	function getAsField_csv( &$aFields, &$aAsFields, $table )
	{
		$this->getAsField_html( $aFields, $aAsFields, $table );
	}

	/**
	 * @return object join table
	 */

	function getJoin()
	{
	  $params =& $this->getParams();
		$element =& $this->getElement();
		$tableModel =& $this->_table;
		$table =& $tableModel->getTable();
		$joins =& $tableModel->getJoins();
		foreach ($joins as $join) {
			if ($join->element_id == $element->id) {
				if (!isset($join->_params)){
					$join->_params = &new fabrikParams( $join->attribs );
				}
				return $join;
			}
		}
		//	default fall back behaviour - shouldnt get used

		if (!is_null( $this->_join )) {
			return $this->_join;
		}
		$join 					=& JTable::getInstance( 'Join', 'Table' );
		$groupModel 	=& $this->_group;
		$element =& $this->getElement();
		if ($groupModel->isJoin()) {

			$joinModel =& $groupModel->getJoinModel();
			$j =& $joinModel->getJoin();

			//$table = $j->join_from_table;
			$join->join_from_table = $j->table_join;
		} else {
			$join->join_from_table = $table->db_table_name;
		}

		$params =& $this->getParams();

		$join->join_type 		= 'LEFT';
		$join->_name 					= $element->name;
		$join->table_join 			= $params->get( 'join_db_name' );
		$join->table_join_key 	= $params->get( 'join_key_column' );
		$join->table_key 			= $element->name;
		$join->table_join_alias = $join->table_join;
		if (is_null($join->_params)){
			$join->_params = &new fabrikParams( $join->attribs );
		}
		$this->_join = $join;
		return $join;
	}

	/**
	 * load this elements joins
	 */

	function getJoins()
	{
		$db =& JFactory::getDBO();
		if (!isset( $this->_aJoins )) {
			$sql = "SELECT * FROM #__fabrik_joins WHERE element_id = '$this->_id' ORDER BY id";
			$db->setQuery( $sql );
			$this->_aJoins = $db->LoadObjectList( );
		}
		return $this->_aJoins;
	}

	/**
	 * //@TODO $$$rob  not sure if we can depreicate this??
	 *
	 * called to fundge the table data when saving a record
	 *
	 * @param array $oAllRowsData
	 * @param object $tableModel
	 */
	function mergeTableData( &$oAllRowsData, &$tableModel )
	{
	  return;
	}

	function getJoinsToThisKey( &$table )
	{
		$db =& JFactory::getDBO();
		$sql = "SELECT *, t.label AS tablelabel FROM #__fabrik_elements AS el " .
		"LEFT JOIN #__fabrik_formgroup AS fg
				ON fg.group_id = el.group_id 
				LEFT JOIN #__fabrik_forms AS f 
				ON f.id = fg.form_id 
				LEFT JOIN #__fabrik_tables AS t 
				ON t.form_id = f.id " .
		"WHERE " .
		" plugin = 'fabrikdatabasejoin' AND" .
		" join_db_name = '" . $table->db_table_name . "' " .
		"AND join_conn_id = '" . $table->connection_id . "' ";

		$db->setQuery( $sql );
		return $db->loadObjectList( );
	}
	/**
	 * get array of option values
	 *
	 * @param array $data
	 * @return array option values
	 */

	function &_getOptionVals( $data = array())
	{
		if (isset( $this->_optionVals )) {
			return $this->_optionVals;
		}
		$db =& $this->getDb();
		$sql = $this->_buildQuery( $data );
		$db->setQuery( $sql );
		FabrikHelperHTML::debug( $db->getQuery(), 'databasejoin element: get options query' );
		$this->_optionVals = $db->loadObjectList();
		FabrikHelperHTML::debug( $this->_optionVals, 'databasejoin elements' );
		if (!is_array( $this->_optionVals )) {
		  $this->_optionVals = array();
		}
		return $this->_optionVals;
	}

	/**
	 * get a list of the HTML options used in the database join drop down / radio buttons
	 * @param object data from current record (when editing form?)
	 * @return array option objects
	 */

	function _getOptions( $data = array() )
	{
		$element 		=& $this->getElement();
		$params 		=& $this->getParams();
		$showBoth 		= $params->get( 'show_both_with_radio_dbjoin', '0' );
		$this->_joinDb =& $this->getDb();

		$col	= $element->name;
		$tmp = array();

		$aDdObjs =& $this->_getOptionVals( $data );

		$table 	 = $this->_form->_table->_table->db_table_name;
		if (is_array($aDdObjs)) {
			$tmp = array_merge( $tmp, $aDdObjs );
		}
		$displayType 	= $params->get( 'database_join_display_type', 'dropdown' );
		if ($displayType == 'dropdown') {
			array_unshift( $tmp, JHTML::_('select.option', $params->get('database_join_noselectionvalue') , JText::_('Please select') ) );
		}
		return $tmp;
	}
	
	/**
	 * check to see if prefilter should be applied
	 * Kind of an inverse access lookup
	 * @param int group id to check against
	 * @param string ref for filter
	 * @return bol must apply filter - true, ignore filter (user has enough access rights) false;
	 */

	function mustApplyWhere( $gid, $ref )
	{
		// prefilters with JACL are applied to a single group only
		// not a group and groups beneath them (think author, registered)
		// so if JACL on then prefilters work in the inverse in that they are only applied
		// to the group selected

		if (defined( '_JACL' )) {
			return FabrikWorker::getACL( $gid, 'dbjoinwhere' . $ref );
		} else {
			return FabrikWorker::getACL( $gid, 'dbjoinwhere' . $ref, '<=' );
		}

	}

	/**
	 * create the sql query used to get the join data
	 * @param array
	 * @return string
	 */

	function _buildQuery( $data = array() )
	{
		$db =& JFactory::getDBO();
		if (isset( $this->_sql )) {
			return $this->_sql;
		}
		$params =& $this->getParams();
		$element =& $this->getElement();
		$formModel =& $this->getForm();

		$whereaccess  = $params->get( 'database_join_where_access', 26 );
		
		$where= '';
		if ($this->mustApplyWhere( $whereaccess, $element->id )) {
		  $where			= $params->get( 'database_join_where_sql', '' );
			$w = new FabrikWorker();
			if (is_array( $data )) {
				$where 			= $w->parseMessageForPlaceHolder( $where, $data );
			} else {
				$where 			= $w->parseMessageForPlaceHolder( $where );
			}
		}
		
		//$$$rob not sure these should be used anyway?
		$table 	= $params->get( 'join_db_name' );
		$key		= $params->get( 'join_key_column' );
		$val		= $this->_getValColumn();
		$orderby	= 'text';

		if ($table == '') {
			$join =& $this->getJoin();
			$table = $join->table_join;
			$key = $join->table_join_key;
			$val = $db->nameQuote($join->_params->get( 'join-label' ));
		}
		$this->_sql = "SELECT DISTINCT($key) AS value, $val AS text FROM `$table` $where ";
		// $$$ hugh - let them specify an order by, i.e. don't append default if the $where already has an 'order by'
		if (!stristr( $where, 'order by' )) {
			$this->_sql .= "ORDER BY $orderby ASC ";
		}
		
		return $this->_sql;
	}

	/**
	 * get the element name or concat statement used to build the dropdown labels or
	 * table data field
	 *
	 * @return string
	 */

	function _getValColumn()
	{
		$params =& $this->getParams();
		if (($params->get('join_val_column_concat') == '')) {
			return $params->get('join_val_column');
		} else {
			return "CONCAT(".$params->get('join_val_column_concat').")";
		}
	}

	function &getLinkedFabrikTables()
	{
		//get any fabrik tables that link to the join table
		if (!isset( $this->_linkedFabrikTables )) {
			$db		=& JFactory::getDBO();
			$params =& $this->getParams();
			$table 	= $params->get( 'join_db_name' );
			$db->setQuery( "SELECT * FROM #__fabrik_tables WHERE db_table_name = '$table'" );
			$this->_linkedFabrikTables = $db->loadResultArray();
		}
		return $this->_linkedFabrikTables;
	}

	/**
	 * get the database object
	 *
	 * @return object database
	 */

	function &getDb()
	{
		$cn =& $this->getConnection();
		if (!$this->_joinDb) {
			$this->_joinDb =& $cn->getDb();
		}
		return $this->_joinDb;
	}

	/**
	 * get connection
	 *
	 * @return object connection
	 */

	function &getConnection()
	{
		if (is_null( $this->_cn )) {
			$this->_loadConnection();
		}
		return $this->_cn;
	}

	/**
	 * @access private
	 * load connection object
	 */

	function _loadConnection()
	{
		$params 		=& $this->getParams();
		$id 				= $params->get('join_conn_id');
		$this->_cn =& JModel::getInstance( 'Connection', 'FabrikModel' );
		$this->_cn->setId( $id );
		//return $this->_cn->getConnection();
	}

	/**
	 * draws the form element
	 * @param array data to preopulate element with
	 * @param int repeat group counter
	 * @return string returns field element
	 */

	function render( $data, $repeatCounter = 0 )
	{

		$params 			=& $this->getParams();
		$formModel		=& $this->_form;
		$groupModel 	=& $this->_group;
		$element 			=& $this->getElement();
		$aGroupRepeats[$element->group_id] = $groupModel->canRepeat();

		$displayType 	= $params->get( 'database_join_display_type', 'dropdown' );
		$db =& $this->getDb();

		if (!$db) {
			JError::raiseWarning( JText::sprintf('Could not make join to %s', $element->name));
			return '';
		}
		if (isset( $formModel->_aJoinGroupIds[$groupModel->_id] )) {
			$joinId 		= $formModel->_aJoinGroupIds[$groupModel->_id];
			$joinGroupId 	= $groupModel->_id;
		} else {
			$joinId 		= '';
			$joinGroupId 	= '';
		}

		$tmp =& $this->_getOptions( $data );

		/*get the default value */
		$w = new FabrikWorker();
		
		$default = $this->getDefaultValue( $data, $repeatCounter );

		$default 		= $w->parseMessageForPlaceHolder( $default );
		$thisElName	 	= $this->getFullName();

		 
		$id = $this->getHTMLId( $repeatCounter ) ;//. rand(5, 10);
		if ($this->canView()) {
		  $str = ($displayType == "dropdown") ? '' : "<div class='fabrikSubElementContainer' id='$id'>";
			/*if user can access the drop down*/
			if ($displayType == "dropdown") {
				$str .= JHTML::_('select.genericlist', $tmp, $thisElName, 'class="fabrikinput inputbox" size="1"', 'value', 'text', $default, $id);
			} else {
				$str .= FabrikHelperHTML::radioList( $tmp, $thisElName, 'class="fabrikinput inputbox" size="1" id="' . $id . '"', $default, 'value', 'text' );
			}
			if ($params->get( 'fabrikdatabasejoin_frontend_add' )) {
				$str .= "<input type='button' class='button' id='" . $id . "_add' value='" . JText::_('add') . "' />";
			}
			$str .= ($displayType == "dropdown") ? '' : "</div>";
		} else {
			/* make a hidden field instead*/
			$str .= "<input type='hidden' class='fabrikinput' name='$col' id='$id' value='$default' />";
		}

		//get the default label for the drop down (use in read only templates)
		$defaultLabel = '';
		foreach ($tmp as $obj) {
			if ($obj->value == $default) {
				$defaultLabel = $obj->text;
			}
		}

		
		if (!$this->_editable) {
			return $defaultLabel;
		}
		return $str;
	}

	/**
	 * get the default value for the database join element
	 *
	 * @param array $data
	 * @param int $repeatCounter
	 * @param array options
	 * @return string default value
	 */

	function getDefaultValue( $data, $repeatCounter = 0, $opts = array() )
	{

		if (is_null( $this->defaults )) {
			$this->defaults = array();
		}
		if (!array_key_exists( $repeatCounter, $this->defaults )) {
			$groupModel =& $this->_group;

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
			// $$$ hugh - think this needs to be after the isJoin code, 'cos $data['join'] names
			// don't have _raw appended, they seem to have a separate $data['join_raw'] array instead
			
			//$$$ROB wrong! Data structure when editing joins should be data[join][x][elemenentname_raw]  
			$name .=  "_raw";
			if ($groupModel->isJoin()) {
				if ($groupModel->canRepeat()) {
					if (array_key_exists( 'join', $data ) && array_key_exists( $joinid, $data['join']) && is_array($data['join'][$joinid]) &&  array_key_exists( $name, $data['join'][$joinid] ) && array_key_exists( $repeatCounter, $data['join'][$joinid][$name] )) {
						$default = $data['join'][$joinid][$name][$repeatCounter];
					}
				} else {
					if (array_key_exists( 'join', $data ) && array_key_exists( $joinid, $data['join']) && is_array($data['join'][$joinid]) && array_key_exists( $name, $data['join'][$joinid] )) {
						$default = $data['join'][$joinid][$name];
					}
				}
			} else {
				if ($groupModel->canRepeat()) {
					//can repeat NO join
					if (array_key_exists( $name, $data )) {
						if (is_array( $data[$name] )) {
							//occurs on form submission for fields at least
							$a = $data[$name];
						} else {
							//occurs when getting from the db
							$a = 	explode( $this->_groupSplitter, $data[$name] );
						}
						if (array_key_exists( $repeatCounter, $a )) {
							$default = $a[$repeatCounter];
						}
					}
				} else {
					
					if (array_key_exists( $name, $data )) {
						$default = $data[$name];
					}
				}
			}
			$element->default = $default;
			$formModel =& $this->getForm();
					//stops this getting called from form validation code as it messes up repeated/join group validations
			if (array_key_exists('runplugins', $opts) && $opts['runplugins'] == 1){
				$formModel->getPluginManager()->runPlugins( 'onGetElementDefault', $formModel, 'form', $this );
			}
			if (is_array( $element->default )) {
				$default = implode( ',', $element->default );
			}
			$this->defaults[$repeatCounter] = $element->default;
			
		}
		return $this->defaults[$repeatCounter];
	}

	/**
	 * OPTIONAL FUNCTION
	 * code to create lists that are later used in the renderAdminSettings function
	 * @param array list of default values
	 * @param object element to apply lists to
	 */

	function _getAdminLists( &$lists )
	{
		$db 			=& JFactory::getDBO();
		$params 	=& $this->getParams();
		$oConn 		= JModel::getInstance( 'Connection', 'FabrikModel' );
		$realCnns 	= $oConn->getConnections( );
		$lists['connectionTables'] = $oConn->getConnectionTables( $realCnns );
		$tableNames = $lists['connectionTables'][$params->get('join_conn_id', -1)];
		$lists['tablename'] = JHTML::_('select.genericlist', $tableNames, 'params[join_db_name]', 'class="inputbox" size="1"', 'value', 'text', $params->get('join_db_name', ''), 'join_db_name' );
		if ( $params->get('join_db_name', '') == "" ) {
			$tableNames[] = JHTML::_('select.option', '-1', JText::_( 'Select a connection first ....' ) );
		}

		//forms for potential add record pop up form
		$db->setQuery( "SELECT f.id AS value, f.label AS text FROM
		#__fabrik_forms AS f LEFT JOIN #__fabrik_tables As t
		ON f.id = t.form_id 
		WHERE f.state = '1' AND t.db_table_name = '" . $params->get('join_db_name', '') . "'  
		ORDER BY f.label" );
		$forms = $db->loadObjectList();
		$popupformid =  $params->get('fabrikdatabasejoin_popupform');
		$lists['popupform'] = JHTML::_('select.genericlist', $forms, 'params[fabrikdatabasejoin_popupform]', 'class="inputbox" size="1" ', 'value', 'text', $popupformid );
	}

	/**
	 * REQUIRED FUNCTION
	 * defines the type of database table field that is created to store the element's data
	 */

	function getFieldDescription()
	{
		$table =& $this->getTableModel(); 
		$db =& $table->getDb();
		//if the element is in a non joined repeat group we should return varchar 255
		$group =& $this->getGroup();
		if ($group->getGroup()->is_join == 0 && $group->canRepeat() ){
		  return "VARCHAR (255)";
		}
		//lets see if we can get the field type of the field we are joining to
		$join =& JTable::getInstance( 'Join', 'Table' );
		$join->_tbl_key = "element_id";
		$join->load( $this->_id );
		
		$db->setQuery( "DESCRIBE $join->table_join" );
		$fields = $db->loadObjectList();
		foreach ($fields as $field) {
			if ($field->Field == $join->table_join_key) {
				return $field->Type;
			}
		}
		//nope? oh well default to this:
		return "VARCHAR (255)";
	}

	/**
	 * REQUIRED FUNCTION
	 *
	 * @param array $lists
	 */

	function renderAdminSettings( &$lists )
	{
		$params =& $this->getParams();
		$pluginParams =& $this->getPluginParams();
		$this->_getAdminLists( $lists );
		$element =& $this->getElement();
		$checked = $pluginParams->get( 'checked' );
		$checked = ( $checked == '1') ? ' checked="checked"' : '';
		?>
<script language="javascript" type="text/javascript">
			var connectiontables = new Array;
			<?php
			$i = 0;
			if (is_array( $lists['connectionTables'] )) {
				foreach ($lists['connectionTables'] as $k => $items) {
					foreach ($items as $v) {
						echo "connectiontables[".$i ++."] = new Array( '$k','".addslashes($v->value)."','".addslashes($v->text)."' );\n\t\t";
					}
				}
			}?> 
		</script>
<div id="page-<?php echo $this->_name;?>" class="elementSettings"
	style="display: none"><?php
	echo $pluginParams->render( 'details' );
	echo $pluginParams->render( 'params', 'intro' );
	 echo $pluginParams->render( 'params', 'simple' ); ?>
	<?php
	echo $pluginParams->render( 'params', 'xtra' );?>
	<fieldset>
	<legend><?php echo JText::_('FABRIKDATABASEJOIN_FRONTEND_ADD') ?></legend>
	<?php echo $pluginParams->render( 'params', 'frontend' );?>
	
<table class="paramlist admintable">
	<tr>
		<td class="paramlist_key" width="40%"><?php echo JText::_('FABRIKDATABASEJOIN_PPOPUPFORM');?></td>
		<td class="paramlist_value"><?php echo $lists['popupform'] ;?></td>
	</tr>
	<tr>
		<td class="paramlist_key"><?php echo JText::_( 'Default' ); ?></td>
		<td class="paramlist_value"><textarea
			onblur="setAll(this.value, 'default');" rows="8" cols="72"
			name="default" class="inputbox"><?php echo $element->default; ?></textarea>
		</td>
	</tr>
</table>
</fieldset>

</div>
<?php
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
		//value is passed in as the key and rendertabledata's value should be the label
		//iterate over the options to swap the key for the label
		$tmp =& $this->_getOptions( $data );
		foreach ($tmp as $v) {
			if ($v->value == $value) {
				$value = $v->text;
			}
		}
		$val = $this->renderTableData( $value, new stdClass() );
		return $val ;
	}

	/**
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
		$groupModel =& $this->_group;
		if (!$groupModel->isJoin() &$groupModel->canRepeat()) {
			$opts =& $this->_getOptionVals();
			$name =$this->getFullName( false, true, false ) ."_raw";
			//if coming from fabrikemail plugin oAllRowsdata is empty 
			if (isset( $oAllRowsData->$name )) {
			  $data = $oAllRowsData->$name;
			}
			if (!is_array( $data )) { 
			  $data = explode( $this->_groupSplitter, $data );
			}
			$labeldata = array();
			foreach ($data as $d) {
				foreach ($opts as $opt) {
					if ($opt->value == $d) {
						$labeldata[] = $opt->text;
						break;
					}
				}
			}
		} else {
			$labeldata[] = $data;
		}

		if (count( $labeldata ) == 1) {
			$return = $labeldata[0];
		} else {
			$return = "<ul class='fabrikRepeatData'>";
			foreach ($labeldata as $d) {
				$return .= "<li>$d</li>";
			}
		}
		if ($params->get('icon_folder') != -1 && $params->get('icon_folder') != '') {
			$return = $this->_replaceWithIcons($return);
		}
		return $return;
	}


	/**
	 * Get the table filter for the element
	 * @return string filter html
	 */

	function getFilter()
	{

		$params 		=& $this->getParams();
		$element 		=& $this->getElement();
		$tableModel 	=& $this->_table;
		$table 			=& $tableModel->getTable();
		$origTable 		= $table->db_table_name;
		$fabrikDb 		=& $tableModel->getDb();
		$js 			= "";

		$elName 				= $this->getFilterFullName( true, true, false );
		$elName2 			= $this->getFullName( false, false, false );
		//$ids 					= $tableModel->getColumnData( $elName2 );
		$elLabel			= $element->label;
		$elExactMatch = $element->filter_exact_match;
		$v 				= $elName . "[value]";
		$t 				= $elName . "[type]";
		$e 				= $elName . "[match]";
		$jt 			= $elName . "[join_db_name]";
		$jk 			= $elName . "[join_key_column]";
		$jv 			= $elName . "[join_val_column]";
		$origDate 		= $elName . "[filterVal]";
		$fullword 		= $elName . "[full_words_only]";
		$return			= '';

		$default 		= $this->getDefaultFilterVal();
		$aThisFilter = array();

		/*
		 * list of all tables that have been joined to -
		 * if duplicated then we need to join using a table alias
		 */

		$joinTable 	= $params->get( 'join_db_name' );
		$joinKey		= $params->get( 'join_key_column' );
		$joinVal 		= $this->getJoinLabelColumn();
		
		$join =& $this->getJoin();
		$joinTableName  	=  $join->table_join_alias;
	
		//$sql 	= "SELECT DISTINCT( $joinVal ) AS elText, `$joinTable`.`$joinKey` AS elVal \n FROM `$joinTable` \n " ;
		$sql 	= "SELECT DISTINCT( $joinVal ) AS elText, `$joinTableName`.`$joinKey` AS elVal \n FROM  `$joinTable` AS $joinTableName \n " ;
		// $$$ hugh - TESTING performance issues
		// $sql .= " WHERE `$joinTableName`.`$joinKey` IN ('" . implode( "','", $ids ) . "') ORDER BY $joinVal ASC";
		$sql .= " ORDER BY $joinVal ASC";
	
		switch ($element->filter_type) {

			case "dropdown":
				$fabrikDb->setQuery( $sql );
				FabrikHelperHTML::debug( $fabrikDb->getQuery(), 'fabrikdatabasejoin getFilter' );
				$oDistinctData = $fabrikDb->loadObjectList();
				echo $fabrikDb->getErrorMsg();
				// $$$ hugh - added val/text to default,
				// as per http://fabrikar.com/forums/showthread.php?p=47201#post47201
				$aThisFilter[] = JHTML::_('select.option', '', JText::_( 'Please select' ), 'elVal', 'elText' );
				if (is_array( $oDistinctData )) {
					$aThisFilter = array_merge( $aThisFilter, $oDistinctData );
				}

				$return = JHTML::_('select.genericlist', $aThisFilter , $v, 'class="inputbox fabrik_filter" size="1" ' , "elVal", 'elText', $default );
				break;

			case "field":
				$return = "<input type='text' class='inputbox fabrik_filter' name='$v' value='$default'   />";
				$return .= "\n<input type='hidden' name='$jt' value='" . $params->get( 'join_db_name' ) . "'/>";
				$return .= "\n<input type='hidden' name='$jk' value='" . $params->get( 'join_key_column' ) . "'/>";
				$return .= "\n<input type='hidden' name='$jv' value='" . $params->get( 'join_val_column' ) . "'/>";
				break;

		}
		$return .= "\n<input type='hidden' name='$t' value='$element->filter_type' />\n";
		$return .= "\n<input type='hidden' name='$e' value='$elExactMatch' />\n";
		$return .= "\n<input type='hidden' name='$fullword' value='" . $params->get('full_words_only', '0') . "' />\n";
		return $return;
	}

	/**
	 * get the name of the field to order the table data by
	 * @return string column to order by tablename.elementname
	 */

	function getOrderByName()
	{
		$params 		=& $this->getParams();
		$joinTable 		= $params->get( 'join_db_name' );
		$joinKey		= $params->get( 'join_key_column' );
		$joinVal		= $params->get( 'join_val_column' );
		$return = "$joinTable.$joinVal";
		if ($return == '.'){
			$return = parent::getOrderByName();
		}
		return $return;
	}

	/**
	 * used for the name of the filter fields
	 * Over written here as we need to get the label field for field searches
	 *
	 * @param bol $includeJoinString
	 * @param bol $useStep
	 * @param bol $incRepeatGroup
	 * @return string element filter name
	 */

	function getFilterFullName( $includeJoinString = true, $useStep = true, $incRepeatGroup = true )
	{
		$element 	=& $this->getElement();
		$params 	=& $this->getParams();
		$join_db_name = $params->get('join_db_name');
		$tableModel 	=& $this->_table;
		$joins 		=& $tableModel->getJoins();
		foreach ($joins as $join) {
			if ($join->element_id == $element->id) {
				$join_db_name = $join->table_join_alias;
			}
		}
		if ($element->filter_type == 'field') {
			return  $join_db_name . '___' . $params->get('join_val_column') ;
		} else {
			return $this->getFullName( false, true, false );
		}
	}

	/**
	 * get the default value for the table filter
	 */

	function getDefaultFilterVal()
	{
		$tableModel =& $this->getTableModel();
		$aFilter 		=& $tableModel->getFilterArray();
		$elName 		= $this->getFilterFullName( false, true, false );
		$data = JRequest::get('request');
		$groupModel =& $this->_group;
		$group =& $groupModel->_group;
		$params =& $this->getParams();
		$element =& $this->getElement();

		if ($group->is_join) {
			if (array_key_exists( 'join', $data ) && array_key_exists( $group->join_id, $data['join'] )) {
				$data = $data['join'][$group->join_id];
			}
		}

		$default 		= "";
		if (array_key_exists( $elName, $data )) {
			if (is_array( $data[$elName] )) {
				$default = $data[$elName]['value'];
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
						//print_r($aFilter[$elName]);
					}
				} else {
					$k = $params->get('join_db_name'). "." . $params->get('join_val_column');
					if (@isset($aFilter[$key] )) {
						$default = $aFilter[$k]['filterVal'];;
					} else {
						$testKey = '';
					}
				}
			}
		}
		return $default;
	}

	function getFilterLabel($rawval)
	{
		$db =& $this->getDb();
		$params =& $this->getParams();
		$orig = $params->get( 'database_join_where_sql', '' );

		$k = $params->get('join_key_column');
		$l = $params->get('join_val_column');
		$t = $params->get('join_db_name');
		if ($k != '' && $l != '' & $t != '' && $rawval != ''){
			$db->setQuery("select $l from $t where $k  = $rawval" );
			return $db->loadResult();
		} else {
			return $rawval;
		}
	}

	/**
	 * Get the sql for filtering the table data and the array of filter settings
	 * @param array posted data for the element
	 * @param array filters
	 * @param string db col key name e.g. table.elname
	 * @param string form key name e.g. table___elname
	 * @return array filter
	 */

	function getFilterConditionSQL( $val, $aFilter, $dbKey, $key )
	{
		$cond ='';
		$element			=& $this->getElement();

		/* if posted data comes from a module we want to strip out its table name
		 and replace it with current table name
		 not sure how to deal with this for joins ? */

		//TODO: this is a cadidate for caching
		$fromModule 			= JRequest::getBool( 'fabrik_frommodule', 0 );
		$params 					=& $this->getParams();
		//its its passed in the querystring as key=val
		if (!is_array( $val )) {
			$val = array('value'=> $val);
		}
		$filterType 			= isset( $val['type']) ? $val['type'] : 'dropdown';
		$filterVal 				= isset( $val['value'] )? $val['value'] : '';
		$filterExactMatch = isset( $val['match'] )? $val['match'] : $element->filter_exact_match;
		$fullWordsOnly 		= isset( $val['full_words_only'] )? $val['full_words_only'] : '0';
		$joinDbName 			= isset( $val['join_db_name']) ? $val['join_db_name'] : '';
		$joinKey 					= isset( $val['join_key_column']) ? $val['join_key_column'] : '';
		$joinVal 					= isset( $val['join_val_column']) ? $val['join_val_column'] : '';
		if ($filterVal == "" ) {
			return;
		}

		//switch ($element->filter_type) {
		switch ($filterType) {
			case 'dropdown':
				$request = JRequest::get('request');
				//test from url if it is, then set condition to exact
				if (array_key_exists( $key, $request )) {
					$filterExactMatch = true;
				}
				$filterVal = urldecode( $filterVal );
				if ($fromModule) {
					$aKeyParts = explode( '.', $key);
					$key = $this->db_table_name . '.' . $aKeyParts[1];
				}
				if (!is_array( $filterVal )) {
					if ( $filterExactMatch == '0' ){
						$cond = " $dbKey LIKE '%$filterVal%' ";
					} else {
						$cond = " $dbKey = '$filterVal' ";
					}
				} else {
					$cond = "( ";
					foreach ($filterVal as $fval) {
						if (trim( $fval ) != '') {
							if ( $filterExactMatch == '0' ){
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
					$cond = substr( $cond, 0, strlen( $cond )-3 );
					$cond .= " ) ";
				}

				if (array_key_exists( $key, $aFilter )) {
					$aFilter[$key][] = $aFilter[$key];
					$aFilter[$key][] = array('type'=>'dropdown', 'value'=>$filterVal , 'filterVal'=>$filterVal, 'sqlCond' =>$cond );
				} else {
					$aFilter[$key] = array('type'=>'dropdown', 'value'=>$filterVal , 'filterVal'=>$filterVal, 'sqlCond' =>$cond );
				}
				break;
			case "":
			case "field":
				//test for single search field dbKey was the field containing the values and not the labels
				//bit worried about the comment below though?
				$dbKey = $this->getJoinLabelColumn();

				//rob - its passed in correctly from the table model when viewing a form (think filter in url)
				//$dbKey = $params->get( 'join_db_name' ) . '.' . $params->get( 'join_val_column' );
				//$dbKey = FabrikWorker::getDbSafeName( $dbKey );
				$filterVal = urldecode( $filterVal );
				$filterCondSQL = '';
				if ( $joinDbName != '' ) {
					$filterCondSQL .= " LEFT JOIN $joinDbName ON $joinDbName.$joinKey = $dbKey ";
				}
				/* full_words_only
				 all search for multiple fragments of text*/
				$aFilterVals = explode( "+", $filterVal );
				if( $fullWordsOnly == '1' ){
					$cond = " $dbKey REGEXP  \"[[:<:]]" . $filterVal . "[[:>:]]\"";
				} else {
					if ($filterExactMatch) {
						$cond = " $dbKey = '$filterVal'";
					} else {
						$cond = " $dbKey LIKE '%$filterVal%'";
					}
				}
				$aFilter[$key] = array(
					'type'=>'field',
					'value'=>$filterVal,
					'filterVal'=>$filterVal,
					'full_words_only'=>$fullWordsOnly,
					'join_db_name' => $joinDbName,
					'join_db_key' => $joinKey,
					'join_val_column' => $joinVal,
					'prewritten_join' => $filterCondSQL,
					'sqlCond' => $cond
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
		if ( array_key_exists( $key, $aFilter ) ) {
			return $aFilter[$key];
		} else {
			return '';
		}
	}

	/**
	 * Examples of where this would be overwritten include drop downs whos "please select" value might be "-1"
	 * @param string data posted from form to check
	 * @return bol if data is considered empty then returns true
	 */

	function dataConsideredEmpty( $data, $repeatCounter )
	{
		if ($data == '' || $data == '-1') {
			return true;
		}
		return false;
	}

	/**
	 * load in the elements js class
	 *
	 */

	function formJavascriptClass()
	{
		FabrikHelperHTML::script( 'javascript.js', 'components/com_fabrik/plugins/element/fabrikdatabasejoin/', true );
		//$$$rob if we allow the popup form we have to load in all the js files now -
		//same principal as for the calendar viz 
		$params = $this->getParams();
		if ($params->get( 'fabrikdatabasejoin_frontend_add' ) == 1) {
		  $this->getTableModel()->getPluginManager()->loadJs(); 
		}
	}

	/**
	 * create an instance of the elements js class
	 * @param int group repeat counter
	 * @return string js call
	 */

	function elementJavascript( $repeatCounter )
	{
		$params =& $this->getParams();
		$element =& $this->getElement();
		$opts = $this->_getOptionVals();
		$data = new StdClass();
		foreach ($opts as $k=>$v) {
			if (!is_null($v->value)) {
				$data->{$v->value} = $v->text;
			}
		}
		$id 				= $this->getHTMLId( $repeatCounter );
		$data 			=& $this->_form->_data;
		$arSelected = $this->getDefaultValue( $data, $repeatCounter );
		$arVals = explode( "|", $element->sub_values );
		$arTxt 	= explode( "|", $element->sub_labels );
		
		$fbConfig =& JComponentHelper::getParams( 'com_fabrik' );
		$table 		= $params->get( 'join_db_name' );
		$opts 		=& $this->getElementJSOptions( $repeatCounter );

		$opts->mooversion = ($fbConfig->get( 'usefabrik_mootools', false )) ? 1.2 : 1.1;
		$opts->liveSite = COM_FABRIK_LIVESITE;
		$opts->id 		= $this->_id;
		$opts->key 		= $table . "___" . $params->get( 'join_key_column' );
		$opts->label 	= $table . "___" . $params->get( 'join_val_column' );
		$opts->formid = $this->_formId;
		$opts->defaultVal    = $arSelected;
		$opts->popupform    = $params->get( 'fabrikdatabasejoin_popupform' );
		$opts->popwiny      = $params->get('yoffset', 0);
		$opts->display_type = $params->get( 'database_join_display_type', 'dropdown' );
		$opts->data 			= $data;
		$opts = FastJSON::encode($opts);
		return "new fbDatabasejoin('$id', $opts)" ;
	}

	/**
	 * gets the options for the drop down - used in package when forms update
	 *
	 */

	function ajax_getOptions()
	{
		//needed for ajax update
		$formModel =& JModel::getInstance( 'Form', 'FabrikModel' );
		$formModel->setid( $this->_formId );
		$form =& $formModel->getForm();
		$formModel->getTableModel();
		$this->_form =& $formModel;
		//end
		echo FastJSON::encode( $this->_getOptions() );
	}

	/**
	 * called when the element is saved
	 */

	function onSave()
	{
	  $params =& $this->getParams();
		$params	= JRequest::getVar( 'params', array(), 'post', 'array');
		$details	= JRequest::getVar( 'details', array(), 'post', 'array');
		$element =& $this->getElement();
		//load join based on this element id
		$join =& JTable::getInstance( 'Join', 'Table' );
		$origKey = $join->_tbl_key;
		$join->_tbl_key = "element_id";
		$join->load( $this->_id );
		$join->_tbl_key = $origKey;
		$join->table_join = $params['join_db_name'];
		$join->join_type = 'left';
		$join->group_id = $details['group_id'];
		$join->table_key = str_replace('`', '', $element->name);
		//$join->table_key = $params['join_val_column'];
		$join->table_join_key = $params['join_key_column'];
		$join->join_from_table = '';
		$join->attribs = "join-label=" . $params['join_val_column'] . "\n";
		$join->store();
	}

	/**
	 * called before the element is saved
	 *
	 */

	function beforeSave()
	{
		$element =& $this->getElement();
		$maskbits = 4;
		$post	= JRequest::get( 'post', $maskbits );
		if ($post['details']['plugin'] != 'fabrikdatabasejoin') {
			$db =& JFactory::getDBO();
			$db->setQuery("DELETE FROM #__fabrik_joins WHERE element_id =" . $post['id']);
			$db->query();
		}
	}
	
	/**
	 * Examples of where this would be overwritten include timedate element with time field enabled
	 * @param int repeat group counter
	 * @return array html ids to watch for validation
	 */
	
	function getValidationWatchElements( $repeatCounter )
	{
		$params =& $this->getParams();
		$trigger = $params->get( 'database_join_display_type' ) == 'dropdown' ? 'change' : 'click';
		$id 			= $this->getHTMLId( $repeatCounter );
		$ar = array(
			'id' 			=> $id,
			'triggerEvent' => $trigger
		);
		return array( $ar );
	}
}
?>