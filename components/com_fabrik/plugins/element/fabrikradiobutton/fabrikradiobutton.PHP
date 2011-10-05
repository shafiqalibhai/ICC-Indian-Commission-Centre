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

class FabrikModelFabrikRadiobutton  extends FabrikModelElement {

	var $_pluginName = 'radiobutton';

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
	 * shows the data formatted for the table view
	 * @param string data
	 * @param object all the data in the tables current row
	 * @return string formatted value
	 */

	function renderTableData( $data, $oAllRowsData )
	{
		$data = explode( $this->_groupSplitter, $data );
		for ($i=0; $i <count($data); $i++) {
			$data[$i] =  $this->_renderTableData( $data[$i], $oAllRowsData );
		}
		$data = implode( $this->_groupSplitter, $data );
		return parent::renderTableData( $data, $oAllRowsData );
	}


	function _renderTableData( $data, $oAllRowsData )
	{
		$params =& $this->getParams();
		//check if the data is in csv format, if so then the element is a multi drop down
		if (strstr( $data, ',' ) && $params->get( 'multiple', 0 ) == 1) {
			$aData = explode(',', $data);
			$sLabels = '';
			foreach ($aData as $tmpVal) {
				if ($params->get('icon_folder') != -1 && $params->get('icon_folder') != '') {
					$sLabels .= $this->_replaceWithIcons( $tmpVal ). "<br />";
				} else {
					$sLabels .= $this->_getLabelForValue( $tmpVal ). "<br />";
				}
			}
			return FabrikString::rtrimword( $sLabels, "<br />" );
		} else {
			if ($params->get('icon_folder') != -1 && $params->get('icon_folder') != '') {
				return $this->_replaceWithIcons( $data );
			} else {
				return $this->_getLabelForValue( $data );
			}
		}
		return $data;
	}

	/**
	 *  can be overwritten in add on classes
	 * @param mixed thie elements posted form data
	 * @param array posted form data
	 */

	function storeDatabaseFormat( $val, $data )
	{
		$str = '';
		if (!is_array( $val )) {
		  //import from csv the data is in a string format
		  $val = explode($this->_groupSplitter, $val);
		}
		$element = $this->getElement();
		foreach ($val as $v) {
			if (is_array( $v )) { //repeat group
				foreach ($v as $w) {
					$str .= $w . $this->_groupSplitter;
				}
			} else {
				$str .= $v . $this->_groupSplitter;
			}
		}
		$str = FabrikString::rtrimword( $str, $this->_groupSplitter );
		return $str;
	}

	/**
	 * draws the form element
	 * @param array data
	 * @param int repeat group counter
	 * @return string returns element html
	 */

	function render( $data, $repeatCounter = 0 )
	{
		$name 		= $this->getHTMLName( $repeatCounter );
		$id				= $this->getHTMLId( $repeatCounter );
		$params 	=& $this->getParams();
		$element 	=& $this->getElement();
		$arVals 	= explode( "|", $element->sub_values );
		$arTxt 		= explode( "|", $element->sub_labels );
		$selected = $this->getDefaultValue( $data, $repeatCounter );
		$options_per_row = intval( $params->get( 'options_per_row', 0 )); // 0 for one line
		if ($options_per_row > 0) {
			$percentageWidth = floor(floatval(100) / $options_per_row) - 2 ;
			$div = "<div class='fabrik_subelement' id='$id' style='float:left;width:" . $percentageWidth . "%'>\n";
		}
		$str = "<div class='fabrikSubElementContainer' id='$id'>";
		$aRoValues = array();

		//if we have added an option that hasnt been saved to the database. Note you cant have
		// it not saved to the database and asking the user to select a value and label
		if ($params->get( 'allow_frontend_addtoradio', false ) && !empty( $selected )) {
			foreach ($selected as $sel) {
				if (!in_array( $sel, $arVals)) {
					if (!empty( $sel )) {
						$arVals[] = $sel;
						$arTxt[] = $sel;
					}
				}
			}
		}
		//$$$ rob removed subelement ids for repeat group validation & element js code
		for ($ii = 0; $ii < count($arVals); $ii ++) {
			if ($options_per_row > 0 ){
				$str .= $div;
			}
			if (is_array($selected) and in_array( $arVals[$ii], $selected )) {
				$aRoValues[] = $arTxt[$ii];
				$checked = "checked=\"checked\"";
			} else {
				$checked = "";
			}
			$input = "<input class='fabrikinput' type=\"radio\" name=\"$name\" value=\"$arVals[$ii]\" $checked />";
			if ($params->get( 'radio_element_before_label' )  == '1') {
				$str .= "<label>$input<span>$arTxt[$ii]</span></label>\n";
			} else {
				$str .= "<label><span>$arTxt[$ii]</span>$input</label>\n";
			}
			if ($options_per_row > 0) {
				$str .= "</div> <!-- end row div -->\n";
			}
		}
		if (!$this->_editable) {
			return implode( ',', $aRoValues );
		}
			
		$str .="</div>";
		if ($params->get( 'allow_frontend_addtoradio', false )) {
			$onlylabel = $params->get( 'rad-allowadd-onlylabel' );
			$str .= $this->getAddOptionFields( $onlylabel, $repeatCounter );
		}
		return $str;
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
	 * @return array default value
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
			$element 		=& $this->getElement();
			$default	 	= explode('|',$element->sub_intial_selection);
			$tableModel =& $this->_table;
			$default	 	= str_replace('|', $this->_groupSplitter2, $element->sub_intial_selection);
			$table 			=& $tableModel->getTable();
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
							$a = $data[$name] == '' ? array() :	explode( $this->_groupSplitter, $data[$name] );
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
			//stops this getting called from form validation code as it messes up repeated/join group validations
			if (array_key_exists('runplugins', $opts) && $opts['runplugins'] == 1){
				$formModel->getPluginManager()->runPlugins( 'onGetElementDefault', $formModel, 'form', $this );
			}
      if (is_string( $element->default )) {
				$element->default = explode( $this->_groupSplitter2, $element->default );
			}
			$this->defaults[$repeatCounter] = $element->default ;
			
		}
		return $this->defaults[$repeatCounter];
	}

	function getFieldDescription()
	{
		return "TEXT";
	}

	/**
	 * render admin settings
	 */

	function renderAdminSettings( )
	{
		$params =& $this->getParams();
		$pluginParams =& $this->getPluginParams();
		$element =& $this->getElement();
		FabrikHelperHTML::script( 'admin.js', 'components/com_fabrik/plugins/element/fabrikradiobutton/', true );
		?>
<div id="page-<?php echo $this->_name;?>" class="elementSettings"
	style="display: none"><?php
	FabrikHelperAdminHTML::subElementFields( $element );?>
<fieldset><?php echo $pluginParams->render();?></fieldset>
<fieldset><legend><?php echo JText::_('Sub elements');?></legend> <a
	class="addButton" href="#" id="addRadio" style="text-align: right"><?php echo JText::_('Add'); ?></a>
<ul id="rad_subElementBody" class="subelements"></ul>
</fieldset>
<fieldset><legend><?php echo JText::_('Add options') ?></legend> <?php echo $pluginParams->render( 'params', 'add' ); ?>
</fieldset>
</div>
	<?php

	}

	function getAdminJS()
	{
		$element =& $this->getElement();
		$fbConfig =& JComponentHelper::getParams( 'com_fabrik' );
		$mooversion = ($fbConfig->get( 'usefabrik_mootools', false )) ? 1.2 : 1.1;
		$script  = "\tvar fabrikradiobutton = new fabrikAdminRadiobutton({'mooversion':'$mooversion'});\n".
		"\tpluginControllers.push({element:'fabrikradiobutton', controller: fabrikradiobutton});\n";
		$sub_values 	= explode( "|", $element->sub_values );
		$sub_texts 	= explode( "|", $element->sub_labels );
		$sub_intial_selections = explode( "|", $element->sub_intial_selection );

		if (!is_array( $sub_intial_selections )) {
			$sub_intial_selections = array( $sub_intial_selections );
		}
		for ($ii = 0; $ii < count( $sub_values ) && $ii < count( $sub_texts ); $ii ++) {
			if (in_array($sub_values[$ii], $sub_intial_selections)) {
				$bits[] = "[ '".addslashes( $sub_values[$ii ])."', '".addslashes( $sub_texts[$ii] )."', 'checked']";
			} else {
				$bits[] = "['".addslashes( $sub_values[$ii ])."', '".addslashes( $sub_texts[$ii] )."', '']";
			}
		}
		$script .= "\tfabrikradiobutton.addSubElements([";
		$script .= implode(",", $bits) . "]);\n";
		return $script;
	}


	/**
	 * used to format the data when shown in the form's email
	 * @param array radio button ids
	 * @param array form records data
	 * @param int repeat group counter
	 * @return string formatted value
	 */

	function getEmailValue( $value, $data, $c )
	{
		$params =& $this->getParams();
		$element =& $this->getElement();
		$labels = explode( '|', $element->sub_labels );
		$values = explode( '|',  $element->sub_values );
		$key = array_search( $value[0], $values );
		$val = (!$key) ? $value[0] : $labels[$key];
		return $val;
	}

	/**
	 * return tehe javascript to create an instance of the class defined in formJavascriptClass
	 * @return string javascript to create instance. Instance name must be 'el'
	 */

	function elementJavascript( $repeatCounter )
	{
		$params 		=& $this->getParams();
		$id 				= $this->getHTMLId( $repeatCounter );
		$element 		=& $this->getElement();
		$data 			=& $this->_form->_data;
		$arSelected = $this->getDefaultValue( $data, $repeatCounter );
		$arVals 		= explode( "|", $element->sub_values );
		$arTxt 			= explode( "|", $element->sub_labels );
		$opts =& $this->getElementJSOptions( $repeatCounter );
		$opts->defaultVal = $arSelected;
		$opts->data = array_combine( $arVals, $arTxt );
		$opts->allowadd = $params->get( 'allow_frontend_addtoradio', false ) ? true : false;
		$opts = FastJSON::encode($opts);
		return "new fbRadio('$id', $opts)";
	}

	/**
	 * load the javascript class that manages interaction with the form element
	 * should only be called once
	 * @return string javascript class file
	 */

	function formJavascriptClass( )
	{
		FabrikHelperHTML::script( 'javascript.js', 'components/com_fabrik/plugins/element/fabrikradiobutton/', true );
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
		for ($x=0;$x<count($ids);$x++) {
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

		foreach ( $tableModel->_aJoins as $aJoin ) {
			/** not sure why the group id key wasnt found - but put here to remove error **/
			if ( array_key_exists( 'group_id', $aJoin ) ) {
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
		$dbElName = explode(".", $dbElName);
		$dbElName = "`" . $dbElName[0] . "`.`" . $dbElName[1] . "`";

		$sql = "SELECT DISTINCT( $dbElName ) AS elText, $dbElName AS elVal FROM `$origTable` $joinStr\n";
		$sql .= "WHERE $dbElName IN ('" . implode( "','", $ids ) . "')"
		. "\n AND TRIM($dbElName) <> '' GROUP BY elText ASC";

		$context = "com_fabrik.table" . $tableModel->_id . ".filter." . trim($elName);
		$default = $mainframe->getUserStateFromRequest( $context, trim($elName), $default );
		if (!is_array( $default )) {
			$default = array( 'value' => '' );
		}
		$values = explode( "|", $element->sub_values );
		$labels 	= explode( "|", $element->sub_labels );

		switch ( $element->filter_type )
		{
			case "range":
				if (!is_array( $default )) {
					$default = array( 'value' => array( '', '' ) );
				}
				$fabrikDb->setQuery( $sql );
				$oDistinctData = $fabrikDb->loadObjectList( );
				$usedVals = array();
				$options[] = JHTML::_( 'select.option', '', JText::_( 'Please select' ) );
				foreach ($oDistinctData as $pair) {
					$a = explode( ",", $pair->elVal );
					foreach ($a as $val) {
						if (!in_array( $val,$usedVals )) {
							$usedVals[] = $val;
							$key = array_search( $val, $values );
							$options[] = JHTML::_( 'select.option', $val, $labels[$key] );
						}
					}
				}
				$attribs = 'class="inputbox" size="1" ';
				$return = JHTML::_('select.genericlist', $options , $v.'[]', 'class="inputbox fabrik_filter" size="1" ' . $js , "value", 'text', $default['value'][0], $element->name . "_filter_range_0");
				$return .= JHTML::_('select.genericlist', $options , $v.'[]', 'class="inputbox fabrik_filter" size="1" ' . $js , "value", 'text', $default['value'][1], $element->name . "_filter_range_0");
				break;

			case "dropdown":
				$fabrikDb->setQuery( $sql );
				$oDistinctData = $fabrikDb->loadObjectList( );
				$usedVals = array();
				$options[] = JHTML::_( 'select.option', '', JText::_( 'Please select' ) );
				foreach ($oDistinctData as $pair) {
					$a = explode( ",", $pair->elVal );
					foreach ($a as $val) {
						if (!in_array( $val,$usedVals )) {
							$usedVals[] = $val;
							$key = array_search( $val, $values );
							$options[] = JHTML::_( 'select.option', $val, $labels[$key] );
						}
					}
				}
				if (is_array( $default )) {
					$default = $default['value'];
				}
				$return = JHTML::_('select.genericlist', $options , $v, 'class="inputbox fabrik_filter" size="1" ' , "value", 'text', $default);
				break;

			case "field":
				$default = $default['value'];
				$return = "<input type='text' class='inputbox fabrik_filter' name='$v' value='$default' />";
				break;

		}
		$return .= "\n<input type='hidden' name='$t' value='$element->filter_type' />\n";
		$return .= "\n<input type='hidden' name='$e' value='$elExactMatch' />\n";
		$return .= "\n<input type='hidden' name='$fullword' value='" . $params->get('full_words_only', '0') . "' />\n";
		return $return;
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
	 * trigger called when a row is stored
	 * check if new options have been added and if so store them in the element for future use
	 * @param array data to store
	 */

	function onStoreRow($data)
	{
		$element =& $this->getElement();
		$params =& $this->getParams();
		if ($params->get('rad-savenewadditions') && array_key_exists($element->name . '_additions', $data)) {
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
				if (!in_array($obj->val, $arVals)) {
					$arVals[] = $obj->val;
					$found = true;
					$arTxt[] = $obj->label;
				}
			}

			if($found)
			{
				$element->sub_values = implode("|", $arVals);
				$element->sub_labels = implode("|", $arTxt);
				$element->store();
			}
		}
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
		$params 					=& $this->getParams();
		$element =& $this->getElement();
		if (!array_key_exists( $element->name, $data )) {
			$sel = explode("|", $element->sub_intial_selection);
			$sel = $sel[0];
			$arVals = explode( "|", $element->sub_values );
			$data[$element->name] = array($arVals[$sel]);
		}
	}

	/**
	 *
	 * Examples of where this would be overwritten include drop downs whos "please select" value might be "-1"
	 * @param array data posted from form to check
	 * @return bol if data is considered empty then returns true
	 */

	function dataConsideredEmpty( $data, $repeatCounter )
	{
		if (is_array($data) && count($data) == 1 && $data[0] == ''){
			return true;
		}
		return false;
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