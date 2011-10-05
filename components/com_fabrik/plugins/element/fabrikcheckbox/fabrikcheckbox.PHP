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

class FabrikModelFabrikCheckbox  extends FabrikModelElement {

	var $_pluginName = 'checkbox';

	var $hasLabel = false;

	/**
	 * Constructor
	 */

	function __construct()
	{
		$this->hasSubElements = true;
		parent::__construct();
	}

	/**
	 * formats the posted data for insertion into the database
	 * @param mixed thie elements posted form data
	 * @param array posted form data
	 */

	function storeDatabaseFormat( $val, $data )
	{
		$return = '';
		if (is_array( $val )) {
			foreach ($val as $key=>$v) {
				if (is_array( $v )) {
					//checkboxes in repeat group
					$return .= implode( $this->_groupSplitter2, $v );
					$return .= $this->_groupSplitter;
				} else {
					//not in repeat group
					$return .= $v .$this->_groupSplitter2;
				}
			}
		}
		$return = FabrikString::rtrimword($return, $this->_groupSplitter);
		$return = FabrikString::rtrimword($return, $this->_groupSplitter2);
		return $return;
	}

	/**
	 * shows the data formatted for the table view
	 * @param string data
	 * @param object all the data in the tables current row
	 * @return string formatted value
	 */

	function renderTableData( $data, $oAllRowsData )
	{
		$params 	=& $this->getParams();
		$element 	=& $this->getElement();
		$values 	= explode( "|", $element->sub_values );
		$labels 	= explode( "|", $element->sub_labels );
		$sLabels 	= array();
		//repeat group data
		$gdata = explode( $this->_groupSplitter, $data );
		$uls = array();
		$useIcon = ($params->get('icon_folder') == -1 || $params->get('icon_folder') == '') ? false : true;
		foreach ($gdata as $data) {
			$lis = array();
			$vals = explode( $this->_groupSplitter2, $data );
			foreach ($vals as $val) {
				if ($useIcon) {
					$l = $this->_replaceWithIcons( $val );
				} else {
					$key = array_search( $val, $values );
					if (!@array_key_exists( $key, $labels )) {
						$l = $params->get( 'ck_default_label' );
					} else {
						$l = $labels[$key];
					}
				}
				$lis[] = "<li>$l</li>";
			}
			$uls[] = "<ul class='fabrikRepeatData'>".implode(" ",$lis)."</ul>";
		}
		return implode( " ", $uls );
	}

	/**
	 * render raw data
	 *
	 * @param string data
	 * @param object all the data in the tables current row
	 * @return string formatted value
	 */
	
	function renderRawTableData( $data, $thisRow )
	{
		if (is_array( $data )) {
			return implode($this->_groupSplitter2, $data);
		} else {
			return $data;
		}
	}

	/**
	 * draws the form element
	 * @param int repeat group counter
	 * @return string returns element html
	 */

	function render( $data, $repeatCounter = 0 )
	{
		$name 		= $this->getHTMLName( $repeatCounter );
		$id 			= $this->getHTMLId( $repeatCounter );
		$element 	=& $this->getElement();
		$params 	=& $this->getParams();
		$str 			= "<div class='fabrikSubElementContainer' id='$id'>";
		$arVals 	= explode( "|", $element->sub_values );
		$arTxt 		= explode( "|", $element->sub_labels );

		$options_per_row = intval( $params->get( 'ck_options_per_row', 0 )); // 0 for one line

		$selected = $this->getDefaultValue( $data, $repeatCounter );
		$aRoValues = array();
		if ($options_per_row > 0) {
			$percentageWidth = floor(floatval(100) / $options_per_row) - 2 ;
			$div = "<div class='fabrik_subelement' style='float:left;width:" . $percentageWidth . "%'>\n";
		}

		for ($ii = 0; $ii < count( $arVals ); $ii ++) {
			if ($options_per_row > 0 ){
				$str .= $div;
			}
			
			$label = "<span>".$arTxt[$ii]."</span>";
			$chx = "<input type=\"checkbox\" class=\"fabrikinput checkbox\" name=\"$name\" value=\"$arVals[$ii]\" ";
			if (is_array( $selected ) and in_array( $arVals[$ii], $selected )) {
				$aRoValues[] = $arTxt[$ii];
				 $chx .= " checked=\"checked\" />\n";
			} else {
				$chx .= " />\n";
			}
			$str .= ($params->get( 'element_before_label' )  == '1') ? "<label>".$chx.$label."</label>\n" : "<label>".$label.$chx."</label>\n"; 
			if ($options_per_row > 0) {
				$str .= "</div> <!-- end row div -->\n";
			}
		}
		

		if (!$this->_editable) {
			return implode(', ', $aRoValues);
		}
		if ($options_per_row > 0) {
			$str .= "<br />";
		}
		$str .="</div>";
		if ($params->get( 'allow_frontend_addtocheckbox', false )) {
			$onlylabel = $params->get('chk-allowadd-onlylabel');
			$str .= $this->getAddOptionFields( $onlylabel, $repeatCounter );
		}
		return $str;
	}


	/**
	 * trigger called when a row is stored
	 * check if new options have been added and if so store them in the element for future use
	 * @param array data to store
	 */

	function onStoreRow($data)
	{
		$element =& $this->getElement();
		$params =& $this->getParams();
		if ($params->get('chk-savenewadditions') && array_key_exists($element->name . '_additions', $data)) {
			$added = stripslashes($data[$element->name . '_additions']);
			if (trim($added) == '') {
				return;
			}
			$json = new Services_JSON();
			$added = $json->decode($added);
			$arVals = explode( "|", $element->sub_values );
			$arTxt 	= explode( "|", $element->sub_labels );
			$found = false;
			foreach ($added as $obj) {
				if (!in_array( $obj->val, $arVals )) {
					$arVals[] = $obj->val;
					$found = true;
					$arTxt[] = $obj->label;
				}
			}
			if ($found) {
				$element->sub_values = implode("|", $arVals);
				$element->sub_labels = implode("|", $arTxt);
				$element->store();
			}
		}
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
	  $val = $this->getDefaultValue( $data, $repeatCounter, $opts );
		$element =& $this->getElement();
		$labels = explode( '|', $element->sub_labels );
		$values = explode( '|',  $element->sub_values );
		$str = '';
		if (is_array( $val )) {
			foreach ($val as $tmpVal) {
				$key = array_search( $tmpVal, $values );
				$str.= ($key === false) ? $tmpVal : $labels[$key];
				$str.= " ";
			}
		} else {
		  $str = $val;
		}
		return $str;
	}

	/**
	 * determines the value for the element in the form view
	 * @param array data
	 * @param int when repeating joinded groups we need to know what part of the array to access
	 * @param array options
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
			$tableModel =& $this->_table;
			$default	 	= explode('|',$element->sub_intial_selection);
			$table 			=& $tableModel->getTable();

			if ($element->eval == "1") {
				$default = @eval( stripslashes( $default ) );
			}

			$name = $this->getFullName( false, true, false );

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
						if (is_array( $data[$name] )) {
							//occurs on form submission for fields at least
							$default = $data[$name];
						} else {
							//occurs when getting from the db
							$default = 	explode( $this->_groupSplitter2, $data[$name] );
						}
					}
				}
			}
			$element->default = $default;
			$formModel =& $this->getForm();
					//stops this getting called from form validation code as it messes up repeated/join group validations
			if (array_key_exists('runplugins', $opts) && $opts['runplugins'] == 1){
				$formModel->getPluginManager()->runPlugins( 'onGetElementDefault', $formModel, 'form', $this );
			}
			if (is_string( $element->default )) {
				$element->default = explode( $this->_groupSplitter2, $element->default );
			}
			$this->defaults[$repeatCounter] = $element->default;
			
		}
		return $this->defaults[$repeatCounter];
	}

	/**
	 * defines the type of database table field that is created to store the element's data
	 */
	function getFieldDescription()
	{
		return "TEXT";
	}

	/**
	 * return the javascript to create an instance of the class defined in formJavascriptClass
	 * @return string javascript to create instance. Instance name must be 'el'
	 */

	function elementJavascript( $repeatCounter )
	{
		$params =& $this->getParams();
		$id = $this->getHTMLId( $repeatCounter );
		$element =& $this->getElement();

		$arVals = explode( "|", $element->sub_values );
		$arTxt 	= explode( "|", $element->sub_labels );
		$data 		=& $this->_form->_data;
		$arSelected = $this->getDefaultValue( $data, $repeatCounter );
		$opts =& $this->getElementJSOptions( $repeatCounter );
		$opts->defaultVal = $arSelected;
		$opts->data 			= array_combine($arVals, $arTxt);
		$opts->allowadd = $params->get( 'allow_frontend_addtocheckbox', false );
		$opts = FastJSON::encode($opts);
		return "new fbCheckBox('$id', $opts)" ;
	}

	/**
	 * load the javascript class that manages interaction with the form element
	 * should only be called once
	 * @return string javascript class file
	 */

	function formJavascriptClass()
	{
		FabrikHelperHTML::script( 'javascript.js', 'components/com_fabrik/plugins/element/fabrikcheckbox/', true );
	}

	/**
	 * render admin settings
	 */

	function renderAdminSettings()
	{
		$pluginParams =& $this->getPluginParams();
		FabrikHelperHTML::script( 'admin.js', 'components/com_fabrik/plugins/element/fabrikcheckbox/', true );
		$params =& $this->getParams();
		$element =& $this->getElement();
		?>
<div id="page-<?php echo $this->_name;?>" class="elementSettings"
	style="display: none"><?php
	FabrikHelperAdminHTML::subElementFields( $element );
	echo $pluginParams->render();
	?>
	<fieldset>
		<legend><?php echo JText::_('Sub elements');?></legend> 
		<a class="addButton" href="#" id="addCheckbox" style="text-align: right">
			<?php echo JText::_( 'Add' );?>
		</a>
		<ul id="chk_subElementBody" class="subelements"></ul>
	</fieldset>
		<fieldset>
			<legend><?php echo JText::_('Add options') ?></legend>
			<?php echo $pluginParams->render( 'params', 'add' ); ?>
		</fieldset>
				<fieldset>
			<legend><?php echo JText::_('Advanced') ?></legend>
			<?php echo $pluginParams->render( 'params', 'advanced' ); ?>
		</fieldset>
</div>
	<?php

	}

	function getAdminJS()
	{
		$element =& $this->getElement();
		$fbConfig =& JComponentHelper::getParams( 'com_fabrik' );
		$mooversion = ($fbConfig->get( 'usefabrik_mootools', false )) ? 1.2 : 1.1;
		$sub_values 	= explode( "|", $element->sub_values );
		$sub_texts 	= explode( "|", $element->sub_labels );
		$script = "\tvar fabrikcheckbox = new fabrikAdminCheckbox({'mooversion':'$mooversion'});\n".
		"\tpluginControllers.push({element:'fabrikcheckbox', controller:fabrikcheckbox});\n"
		."\tfabrikcheckbox.addSubElements( [";
		$sub_intial_selections = explode( "|", $element->sub_intial_selection );
		for ($ii = 0; $ii < count( $sub_values ) && $ii < count( $sub_texts ); $ii ++) {
			if (is_array( $sub_intial_selections ) and in_array( $sub_values[$ii], $sub_intial_selections )) {
				$bits[] = "['". addslashes($sub_values[$ii]) ."', '". addslashes( $sub_texts[$ii] )."', 'checked']";
			} else {
				$bits[] = "['". addslashes($sub_values[$ii]) ."', '". addslashes( $sub_texts[$ii] )."']";
			}
		}
		$script .= implode(",", $bits) . "]);\n";
		return $script;
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
		$params 	=& $this->getParams();
		$split_str = $params->get('options_split_str', '\n');
		$element 	=& $this->getElement();
		$values 	= explode( "|", $element->sub_values );
		$labels 	= explode( "|", $element->sub_labels );
		$sLabels 	= '';
		if (is_array( $value )) {
			foreach ($value as $tmpVal) {
				$key = array_search( $tmpVal, $values );
				$sLabels.= $labels[$key]. $split_str;
			}
		}
		$val =  FabrikString::rtrimword( $sLabels, $split_str );
		return $val ;
	}

	/**
	 * OPTIONAL
	 * If your element risks not to post anything in the form (e.g. check boxes with none checked)
	 * the this function will insert a default value into the database
	 * @param array form data
	 * @return array form data
	 */

	function getEmptyDataValue( &$data )
	{
		$params =& $this->getParams();
		$element =& $this->getElement();
		if (!array_key_exists( $element->name, $data )) {
			$data[$element->name] = $params->get( 'ck_value' );
		}
	}

	/**
	 * Get the table filter for the element
	 * @return string filter html
	 */

	function getFilter()
	{
		
		global $mainframe;
		$tableModel  	= $this->_table;
		$groupModel		= $this->_group;
		$table				=& $tableModel->getTable();
		$element			=& $this->getElement();
		$origTable 		= $table->db_table_name;
		$fabrikDb 		=& $tableModel->getDb();
		$params 			=& $this->getParams();
		$formModel		= $tableModel->getForm();
		$js 					= "";
		$elName 		= $this->getFullName( false, true, false );
		$dbElName		= $this->getFullName( false, false, false );
		$elName2 		= $this->getFullName( false, false, false );
		$ids 				= $tableModel->getColumnData( $elName2 );
		//for ids that are text with apostrophes in
		for ($x=0;$x<count( $ids );$x++) {
			$ids[$x] = addSlashes($ids[$x]);
		}
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
			if ($aJoin->group_id == $element->group_id && $aJoin->element_id == 0) {
					
				if ($aJoin->group_id == $element->group_id) {
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
		$dbElName = explode(".", $dbElName);
		$dbElName = "`" . $dbElName[0] . "`.`" . $dbElName[1] . "`";

		$sql = "SELECT DISTINCT( $dbElName ) AS elText, $dbElName AS elVal FROM `$origTable` $joinStr\n";
		$sql .= "WHERE $dbElName IN ('" . implode( "','", $ids ) . "')"
		. "\n AND TRIM($dbElName) <> '' GROUP BY elText ASC";

		$context = "com_fabrik.table" . $tableModel->_id . ".filter." . trim($elName);
		$default = $mainframe->getUserStateFromRequest( $context, trim($elName), $default );
		if (!is_array( $default )) {
			$default = array( 'value' => $default );
		}
		$values = explode( "|", $element->sub_values );
		$labels 	= explode( "|", $element->sub_labels );

		switch ( $element->filter_type )
		{
			case "range":
				$fabrikDb->setQuery( $sql );
				$oDistinctData = $fabrikDb->loadObjectList();
				$usedVals = array();
				$options[] = JHTML::_( 'select.option', '', JText::_( 'Please select' ) );
				foreach ($oDistinctData as $pair) {
					
					$a = explode( $this->_groupSplitter2, $pair->elVal );
					foreach ($a as $val) {
						if (!in_array( $val,$usedVals )) {
							$usedVals[] = $val;
							$key = array_search( $val, $values );
							$options[] = JHTML::_( 'select.option', $val, $labels[$key] );
						}
					}
				}
				$attribs = 'class="inputbox" size="1" ';
				$return = JHTML::_('select.genericlist', $options , $v.'[]', 'class="inputbox fabrik_filter" size="1" '  , "value", 'text', $default['value'][0], $element->name . "_filter_range_0");
				$return .= JHTML::_('select.genericlist', $options , $v.'[]', 'class="inputbox fabrik_filter" size="1" '  , "value", 'text', $default['value'][1], $element->name . "_filter_range_0");
				break;

			case "dropdown":
				$fabrikDb->setQuery( $sql );
				$oDistinctData = $fabrikDb->loadObjectList( );
				$usedVals = array();
				$options[] = JHTML::_( 'select.option', '', JText::_( 'Please select' ) );
				foreach ($oDistinctData as $pair) {
					$a = explode( $this->_groupSplitter2, $pair->elVal );
					foreach ($a as $val) {
						if (!in_array( $val, $usedVals )) {
							$usedVals[] = $val;
							$key = array_search( $val, $values );
							$options[] = JHTML::_( 'select.option', $val, $labels[$key] );
						}
					}
				}
					
				$return = JHTML::_('select.genericlist', $options , $v, 'class="inputbox fabrik_filter" size="1" ' , "value", 'text', $default);
				break;

			case "field":
				$default = $default['value'];
				$return = "<input type='text' class='inputbox fabrik_filter'name='$v' value='$default'  />";
				break;

		}
		$return .= "\n<input type='hidden' name='$t' value='$element->filter_type' />\n";
		$return .= "\n<input type='hidden' name='$e' value='$elExactMatch' />\n";
		$return .= "\n<input type='hidden' name='$fullword' value='" . $params->get('full_words_only', '0') . "' />\n";
		return $return;
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
						
						//$cond = " $dbKey = '$filterVal' ";
						$cond = " ($dbKey LIKE '$filterVal{$this->_groupSplitter2}%' 
						OR $dbKey LIKE '%{$this->_groupSplitter2}$filterVal{$this->_groupSplitter2}%'".
						" OR $dbKey LIKE '%{$this->_groupSplitter2}$filterVal' OR $dbKey = '$filterVal') "; 
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
				//$$$ rob test for strings such as " alert('hi') "
				//hack for chart viz
				if (is_array($filterVal)) {
					$filterVal = $filterVal[0];
				}
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
	 * Get the sql for filtering the table data and the array of filter settings
	 * @param string filter value
	 * @return string filter value
	 */

	function prepareFilterVal( $val )
	{
		$element =& $this->getElement();
		$arVals = explode( "|", $element->sub_values );
		$arTxt 	= explode( "|", $element->sub_labels );
		for ($i=0; $i<count($arTxt); $i++) {
			if (strtolower( $arTxt[$i] ) == strtolower( $val )) {
				$val =  $arVals[$i];
				return $val;
			}
		}
		return $val;
	}
	
	/**
	 * used in isempty validation rule
	 *
	 * @param array $data
	 * @return bol
	 */
	function dataConsideredEmpty( $data, $repeatCounter )
	{
		foreach($data as $d) {
			if ($d != '') {
				return false;
			}
		}
		return true;
	}
	
	/**
	 * Examples of where this would be overwritten include timedate element with time field enabled
	 * @param int repeat group counter
	 * @return array html ids to watch for validation
	 */
	
	function getValidationWatchElements( $repeatCounter )
	{
		$id 			= $this->getHTMLId( $repeatCounter );
		$ar = array(
			'id' 			=> $id,
			'triggerEvent' => 'click'
		);
		return array( $ar );
	}
}
?>