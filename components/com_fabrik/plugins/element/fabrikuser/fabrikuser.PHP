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
require_once( COM_FABRIK_FRONTEND.DS.'plugins'.DS.'element'.DS.'fabrikdatabasejoin'.DS.'fabrikdatabasejoin.php' );

class FabrikModelFabrikUser extends FabrikModelFabrikDatabasejoin {

	/** @var string plugin name */
	var $_pluginName = 'user';

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
	 * draws the form element
	 * @param int repeat group counter
	 * @return string returns element html
	 */

	function render( $data, $repeatCounter = 0 )
	{
		$element	=& $this->getElement();
		$name 		= $this->getHTMLName( $repeatCounter );
		$id 			= $this->getHTMLId( $repeatCounter );
		$params 	=& $this->getParams();
		$db				=& JFactory::getDBO();
		
		$rowid = JRequest::getVar( 'rowid', false );
		//@TODO when editing a form with joined repeat group the rowid will be set but
		//the record is in fact new
		//echo "default = ($element->name} = ".$element->default;
		if ($params->get( 'update_on_edit' ) || !$rowid || ($this->_inRepeatGroup && $this->_inJoin &&  $this->_repeatGroupTotal == $repeatCounter)) {
			//set user to logged in user
			if ($this->_editable) {
				$user  		=& JFactory::getUser();
			}else{
				$user  		=& JFactory::getUser( (int)$element->default );
			}
		} else {
			//keep previous user
			$user  		=& JFactory::getUser( (int)$element->default );
		}
		
		$config =& JFactory::getConfig();
		
		$tableModel =& $this->getTableModel();
		$cnnModel =& $tableModel->getConnection();
		$cnn = $cnnModel->getConnection();
		
		// if the table database is not the same as the joomla database then 
		// we should simply return a hidden field with the user id in it.
		if ($config->getValue( 'db' ) != $cnn->database) {
			return $this->_getHiddenField( $name, $value, $user->get('id') );
		}
		$str = '';
		if ($this->_editable) {
			$value = $user->get('id');
			if ($element->hidden) {
				$str = $this->_getHiddenField( $name, $value, $id );
			} else {
				$str = parent::render( $data, $repeatCounter );
			}
		} else {
			$displayParam = $params->get( 'my_table_data', 'username' );
			if (is_a( $user, 'JUser' )) {
				$str = $user->get($displayParam);
			} else {
				JError::raiseWarning(E_NOTICE, "didnt load for $element->default");
			}
		}
		return $str;
	}

	/**
	 * get element's hidden field 
	 *
	 * @access private
	 * @param string $name
	 * @param string $value
	 * @param string $id
	 * @return strin
	 */
	function _getHiddenField( $name, $value, $id )
	{
		return "<input class='fabrikinput inputbox' type='hidden' name='$name' value='$value' id='$id' />\n";
	}
	
	/**
	 * if the table db isnt the same as the joomla db the element
	 * will be rendered as a hidden field so return true from isHidden()
	 *
	 * @return bol
	 */
	
	function isHidden()
	{
		$db				=& JFactory::getDBO();
		if ($db != $this->getDb()) {
			return true;
		}
		return parent::isHidden();
	}
	/**
	 * if we are creating a new record, and the element was set to readonly
	 * then insert the users data into the record to be stored
	 *
	 * @param unknown_type $data
	 */

	function onStoreRow( &$data )
	{
		$element =& $this->getElement();
		if ($data['rowid'] == 0 && !in_array( $element->name, $data )) {
			$user		=& JFactory::getUser();
			$data[$element->name] = $user->get('id');
		}
	}

	/**
	 * shows the data formatted for the table view
	 * @param string data
	 * @param object all the data in the tables current row
	 * @return string formatted value
	 */

	function renderTableData( $data, $oAllRowsData )
	{
		return parent::renderTableData( $data, $oAllRowsData );
	}

	/**
	 * get js ini code
	 * overwritten in plugin classes
	 *@param int repeat group counter
	 */

	function elementJavascript( $repeatCounter )
	{
		return '';
	}

	/**
	 * can be overwritten in the plugin class - see database join element for example
	 * @param array containing field sql
	 * @param array containing field aliases
	 * @param string table name
	 */

	function getAsField_html( &$aFields, &$aAsFields, $table )
	{
		$element 	=& $this->getElement();
		$params 	= $this->getParams();

		$db =& JFactory::getDBO();
		$fullElName = $table . "___" . $element->name;
		
		
		//check if main database is the same as the elements database
		if ($db == $this->getDb()) {
			//it is so continue as if it were a database join
			//make sure same connection as this table
			
			$join =& $this->getJoin();
			$k = "`$join->keytable`.`$element->name`";
			FabrikString::safeColName($k);
			$k2 = $this->getJoinLabelColumn();
			FabrikString::safeColName($k2);

			$aFields[]				= "$k AS `$fullElName" . "_raw`" ;
			$aAsFields[]			= "`$fullElName". "_raw`";
			$aFields[] 				= "$k2 AS `$fullElName`" ;
			$aAsFields[] 			= "`$fullElName`";
		} else {
			$k 				= "`$table`.`$element->name`";
			FabrikString::safeColName($k);
			//its not so revert back to selecting the id
			$aFields[]				= "$k AS `$fullElName" . "_raw`" ;
			$aAsFields[]			= "`$fullElName". "_raw`";
			$aFields[]				= "$k AS `$fullElName`" ;
			$aAsFields[]			= "`$fullElName`";
		}
	}

	/**
	 * called when the element is saved
	 */

	function onSave()
	{
		$params	= JRequest::getVar( 'params', array(), 'post', 'array' );
		$details	= JRequest::getVar( 'details', array(), 'post', 'array' );
		$element =& $this->getElement();
		//load join based on this element id
		$join =& JTable::getInstance( 'Join', 'Table' );
		$origKey = $join->_tbl_key;
		$join->_tbl_key = "element_id";
		$join->load( $this->_id );
		$join->_tbl_key = $origKey;
		$join->table_join = '#__users';
		$join->join_type = 'left';
		$join->group_id = $details['group_id'];
		$join->table_key = str_replace('`', '', $element->name);
		$join->table_join_key = 'id';
		$join->join_from_table = '';
		$join->attribs = "join-label=" . $params['my_table_data'] . "\n";
		$join->store();
	}

	/**
	 * get the default value
	 *
	 * @param array $data
	 * @param int $repeatCounter
	 * @param array options
	 * @return unknown
	 */

	function getDefaultValue( $data, $repeatCounter = 0, $opts = array() )
	{
		//cludge for 2 scenarios
		if (array_key_exists('rowid', $data)) {
			//when validating the data on form submission
			$key = 'rowid';
		} else {
			//when rendering the element to the form
			$key = '__pk_val';
		}

		//empty(data) when you are saving a new record and this element is in a joined group
		// $$$ hugh - added !array_key_exists(), as ... well, rowid doesn't always exist in the query string

		// $$$ rob replaced ALL references to rowid with __pk_val as rowid doesnt exists in the data :O

		//$$$ rob
		//($this->_inRepeatGroup && $this->_inJoin &&  $this->_repeatGroupTotal == $repeatCounter)
		//is for saying that the last record in a repeated join group should be treated as if it was in a new form

		if (empty( $data ) || !array_key_exists($key, $data) || (array_key_exists($key, $data) && $data[$key] == '') || ($this->_inRepeatGroup && $this->_inJoin &&  $this->_repeatGroupTotal == $repeatCounter)){
			//new record
			if($this->_inRepeatGroup && $this->_inJoin &&  $this->_repeatGroupTotal == $repeatCounter && $this->_editable){
					
				$user =& JFactory::getUser();
				// $$$ hugh - need to actually set $this->default
				$element =& $this->getElement();
				$element->default = $user->get('id');
				return $element->default;
			}else{
				$user =& JFactory::getUser();
				// $$$ hugh - need to actually set $this->default
				$element =& $this->getElement();
				$element->default = $user->get('id');
				return $element->default;
			}
		}
		return parent::getDefaultValue( $data, $repeatCounter );
	}


	/**
	 * defines the type of database table field that is created to store the element's data
	 * as we always store the element id turn this into INT(11) and not varchar as it was previously
	 * @return string db field description
	 */

	function getFieldDescription()
	{
		return "INT(11)";
	}

	/**
	 * render admin settings
	 */

	function renderAdminSettings()
	{
		$params =& $this->getParams();
		$pluginParams =& $this->getPluginParams();
		$element =& $this->getElement();
		?>
<div id="page-<?php echo $this->_name;?>" class="elementSettings"
	style="display: none"><?php
	echo $pluginParams->render( 'details' );
	echo $pluginParams->render( 'params', 'extra');?></div>
	<?php
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
		$elName2 		= $this->getFullName( false, false, false );
		if (!$formModel->hasElement( $elName2 )) {
			return '';
		}
		$db =& JFactory::getDBO();
		$groupModel		=& $this->_group;
		$table				=& $tableModel->getTable();
		$element			=& $this->getElement();
		$origTable 		= $table->db_table_name;
		$fabrikDb 		=& $tableModel->getDb();
		$params			=& $this->getParams( );

		$js 					= "";
		$elName 		= $this->getFullName( false, true, false );
		$dbElName		= $this->getFullName( false, false, false );

		$ids 				= $tableModel->getColumnData( $elName2 );
		//for ids that are text with apostrophes in
		for ($x=0;$x<count( $ids );$x++) {
			$ids[$x] = addSlashes( $ids[$x] );
		}
		$elLabel				= $element->label;
		$elExactMatch 	= $element->filter_exact_match;
		$v 				= $elName . "[value]";
		$t 				= $elName . "[type]";
		$e 				= $elName . "[match]";
		$jt 			= $elName . "[join_db_name]";
		$jk 			= $elName . "[join_key_column]";
		$jv 			= $elName . "[join_val_column]";
		$origDate 		= $elName . "[filterVal]";
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

		$tabletype = $params->get( 'my_table_data', 'username' );

		$sql = "SELECT DISTINCT( $tabletype ) AS elText, id AS elVal FROM #__users \n";
		$sql .= "WHERE id IN ('" . implode( "','", $ids ) . "')"
		. "\n GROUP BY elText ASC";


		$context = "com_fabrik.table" . $tableModel->_id . ".filter." . trim($elName);
		$default = $mainframe->getUserStateFromRequest( $context, trim($elName), $default );


		switch ( $element->filter_type )
		{
			case "range":
				$db->setQuery( $sql );
				$oDistinctData = $db->loadObjectList( );
				$aThisFilter[] = JHTML::_('select.option', '', JText::_( 'PLEASE SELECT' ) );
				if (is_array( $oDistinctData )) {
					$aThisFilter = array_merge( $aThisFilter, $oDistinctData );
				}

				$attribs = 'class="inputbox fabrik_filter" size="1" ';
				$default1 = (is_array($default)) ? $default['value'][0] : '';
				$return 	 = JHTML::_('select.genericlist', $aThisFilter , $v.'[]', $attribs, "elVal", 'elText', $default1, $element->name . "_filter_range_0" );
				$default1 = (is_array($default)) ? $default['value'][1] : '';
				$return 	 .= JHTML::_('select.genericlist', $aThisFilter , $v.'[]', $attribs, "elVal", 'elText', $default1 , $element->name . "_filter_range_1");
				break;
			case "dropdown":
				$db->setQuery( $sql );
				$oDistinctData = $db->loadObjectList();
				$aThisFilter[] = JHTML::_('select.option', '', JText::_( 'PLEASE SELECT' ) );
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
				$return = "<input type='text' name='$v' class=\"inputbox fabrik_filter\" value=\"$default\" $jsfilter  />";
				break;
				//moved to table options
				/*case "search":
				 */
		}
		$return .= "\n<input type='hidden' name='$t' value='$element->filter_type' />\n";
		$return .= "\n<input type='hidden' name='$e' value='$elExactMatch' />\n";
		$return .= "\n<input type='hidden' name='$fullword' value='" . $params->get('full_words_only', '0') . "' />\n";
		return $return;
	}
	
	function &getDb()
	{
	  return JFactory::getDBO();
	}
}
?>