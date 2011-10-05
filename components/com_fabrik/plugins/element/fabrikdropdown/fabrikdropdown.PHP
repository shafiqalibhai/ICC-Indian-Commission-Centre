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

class FabrikModelFabrikDropdown  extends FabrikModelElement {

	var $_pluginName = 'dropdown';

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
		$params 	=& $this->getParams();
		$multiple = $params->get( 'multiple', 0 );
		$sLabels 	= array();
		//repeat group data
		$gdata = explode( $this->_groupSplitter, $data );
		$uls = array();
		$useIcon = ($params->get('icon_folder') == -1 || $params->get('icon_folder') == '') ? false : true;
		foreach ($gdata as $data) {
			$lis = array();
			$vals = explode( $this->_groupSplitter2, $data );
			foreach ($vals as $val) {
				$l = ($useIcon) ? $this->_replaceWithIcons( $val ) : $this->_getLabelForValue( $val );
				$lis[] =  "<li>$l</li>";
			}
			$uls[] = $multiple ? "<ul class='fabrikRepeatData'>".implode(" ",$lis)."</ul>" : implode(" ",$lis);
		}
		if (!$multiple) {
			return "<ul class='fabrikRepeatData'>" . implode( " ", $uls ) . "</ul>";
		} else {
			return implode( " ", $uls );
		}
	}

	/**
	 * formats the posted data for insertion into the database
	 * @param mixed thie elements posted form data
	 * @param array posted form data
	 * @return string data formatted to be stored in the database
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
		$return = FabrikString::rtrimword( $return, $this->_groupSplitter );
		$return = FabrikString::rtrimword( $return, $this->_groupSplitter2 );
		return $return;
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
		$allowAdd = $params->get( 'allow_frontend_addtodropdown', false );
		$arVals 	= explode( "|", $element->sub_values );
		$arTxt 		= explode( "|", $element->sub_labels );
		$multiple = $params->get( 'multiple', 0 );
		$multisize = $params->get( 'dropdown_multisize', 3 );
		$selected = $this->getDefaultValue( $data, $repeatCounter );

		$errorCSS = (isset( $this->_elementError ) &&  $this->_elementError != '') ? " elementErrorHighlight" : '';
		$attribs 	= 'class="fabrikinput inputbox'.$errorCSS."\"";

		if ($multiple == "1") {
			$attribs 	.= " multiple=\"multiple\" size=\"$multisize\" ";
		}
		$i 					= 0;
		$aRoValues 	= array();
		$opts 			= array();
		foreach ($arVals as $tmpval) {
			$tmptxt = addslashes( htmlspecialchars( $arTxt[$i] ) );
			$opts[] = JHTML::_('select.option', $tmpval, $tmptxt );
			if (is_array($selected) and in_array( $tmpval, $selected )) {
				$aRoValues[] = $tmptxt;
			}
			$i ++;
		}
		//if we have added an option that hasnt been saved to the database. Note you cant have
		// it not saved to the database and asking the user to select a value and label
		if ($params->get( 'allow_frontend_addtodropdown', false ) && !empty( $selected )) {
			foreach ($selected as $sel) {
				if (!in_array( $sel, $arVals) && $sel !== '') {
					$opts[] = JHTML::_('select.option', $sel, $sel );
				}
			}
		}
		$str = JHTML::_('select.genericlist', $opts, $name, $attribs, 'value', 'text', $selected, $id );
		if (!$this->_editable) {
			return implode( ', ', $aRoValues );
		}
		if ($params->get( 'allow_frontend_addtodropdown', false )) {
			$onlylabel = $params->get( 'dd-allowadd-onlylabel' );
			$str .= $this->getAddOptionFields( $onlylabel, $repeatCounter );
		}
		return $str;
	}

	/**
	 * trigger called when a row is stored
	 * check if new options have been added and if so store them in the element for future use
	 * @param array data to store
	 */

	function onStoreRow( $data )
	{
		$element =& $this->getElement();
		$params =& $this->getParams();
		if ($params->get('dd-savenewadditions') && array_key_exists($element->name . '_additions', $data)) {
			$added = stripslashes($data[$element->name . '_additions']);
			if (trim( $added ) == '') {
				return;
			}
			$json = new Services_JSON();
			$added = $json->decode($added);
			$arVals = explode( "|", $element->sub_values );
			$arTxt 	= explode( "|", $element->sub_labels );
			$data = explode($this->_groupSplitter2, $data[$element->name]);
			$found = false;
			foreach ($added as $obj) {
				if (!in_array($obj->val, $arVals)) {
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
	 * load the javascript class that manages interaction with the form element
	 * should only be called once
	 * @return string javascript class file
	 */

	function formJavascriptClass()
	{
		FabrikHelperHTML::script( 'javascript.js', 'components/com_fabrik/plugins/element/fabrikdropdown/', false );
	}

	/**
	 * return tehe javascript to create an instance of the class defined in formJavascriptClass
	 * @return string javascript to create instance. Instance name must be 'el'
	 */

	function elementJavascript( $repeatCounter )
	{
		$id 				= $this->getHTMLId( $repeatCounter );
		$element 		=& $this->getElement();
		$data 			=& $this->_form->_data;
		$arSelected = $this->getDefaultValue( $data, $repeatCounter );
		$arVals = explode( "|", $element->sub_values );
		$arTxt 	= explode( "|", $element->sub_labels );
		$params =& $this->getParams();

		$opts =& $this->getElementJSOptions( $repeatCounter );
		$opts->allowadd 	= $params->get( 'allow_frontend_addtodropdown', false ) ? true : false;
		$opts->defaultVal = $arSelected;
		$opts->data 			= array_combine( $arVals, $arTxt ) ;
		$opts->splitter 	= $this->_groupSplitter2;
		$opts = FastJSON::encode($opts);
		return "new fbDropdown('$id', $opts)" ;
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
	 * @return array default values
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
			$default	 	= explode( '|',$element->sub_intial_selection );
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
					//repeat group NO join
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
			if (is_string( $element->default )) {
				$element->default = explode( $this->_groupSplitter2, $element->default );
			}
			$this->defaults[$repeatCounter] = $element->default;
				
		}
		return $this->defaults[$repeatCounter];
	}

	/**
	 * get the field description
	 * @return string field description e.g. varchar(255)
	 */

	function getFieldDescription()
	{
		return "VARCHAR (255)";
	}

	/**
	 * render the admin settings
	 */

	function renderAdminSettings( )
	{
		$pluginParams =& $this->getPluginParams();
		$params =& $this->getParams();
		$element =& $this->getElement();
		FabrikHelperHTML::script( 'admin.js', 'components/com_fabrik/plugins/element/fabrikdropdown/', true );
		?>
<div id="page-<?php echo $this->_name;?>" class="elementSettings"
	style="display: none"><?php FabrikHelperAdminHTML::subElementFields( $element ); ?>
<fieldset><?php echo $pluginParams->render(); ?></fieldset>
<fieldset><legend><?php echo JText::_('Sub elements');?></legend> <a
	class="addButton" href="#" id="addDropDown" style="text-align: right"><?php echo JText::_( 'Add' ); ?></a>
<ul id="drd_subElementBody" class="subelements"></ul>
</fieldset>
<fieldset><legend><?php echo JText::_('Add options') ?></legend> <?php echo $pluginParams->render( 'params', 'add' ); ?>
</fieldset>
</div>
<input
	type="hidden" name="params[drd_initial_selection]" value=""
	id="params_drd_initial_selection" />
		<?php
	}

	function getAdminJS()
	{
		$element =& $this->getElement();
		$fbConfig =& JComponentHelper::getParams( 'com_fabrik' );
		$mooversion = ($fbConfig->get( 'usefabrik_mootools', false )) ? 1.2 : 1.1;
		$script = "\tvar fabrikdropdown = new fabrikAdminDropdown({'mooversion':'$mooversion'});\n".
		"\tpluginControllers.push({element:'fabrikdropdown', controller: fabrikdropdown});\n";
		$sub_values 	= explode( "|", $element->sub_values );
		$sub_texts 	= explode( "|", $element->sub_labels );
		$sub_intial_selections = explode( "|", $element->sub_intial_selection );
		for ($ii = 0; $ii < count($sub_values) && $ii < count($sub_texts); $ii ++) {
			if (is_array( $sub_intial_selections ) and in_array( $sub_values[$ii], $sub_intial_selections )) {
				$bits[] = "['".addslashes($sub_values[$ii])."', '".addslashes($sub_texts[$ii])."', 'checked']";
			} else {
				$bits[] = "['".addslashes($sub_values[$ii])."', '".addslashes($sub_texts[$ii])."', '']";
			}
		}
		$script .= "\tfabrikdropdown.addSubElements([";
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
		$params =& $this->getParams();
		$element =& $this->getElement();
		$labels = explode( '|', $element->sub_labels );
		$values = explode( '|',  $element->sub_values );
		$sLabels = '';
		if (is_string( $value )) {
			$value = array($value);
		}
		if (is_array($value)) {
			foreach ($value as $tmpVal) {
				$key = array_search( $tmpVal, $values );
				$sLabels.= (!$key) ? $tmpVal : $labels[$key];
				$sLabels.= "\n";
			}
		}
		$val =  FabrikString::rtrimword( $sLabels, "\n" );
		return $val;
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

	function getFilter()
	{
		$element 	=& $this->getElement();
		$values 	= explode('|', $element->sub_values);
		$labels 	= explode('|', $element->sub_labels);
		$default 	= $this->getDefaultFilterVal();
		$elName 	= $this->getFullName( false, true, false );
		$params		=& $this->getParams();
		$v 				= $elName . "[value]";

		$opts[] 	= JHTML::_('select.option',  '', JText::_( 'PLEASE SELECT' ), 'elVal', 'elText' );
		for ($i=0;$i<count($values);$i++) {
			$opts[] = JHTML::_('select.option',  $values[$i], $labels[$i], 'elVal', 'elText' );
		}

		$attribs = 'class="inputbox fabrik_filter" size="1" ';

		switch ($element->filter_type)
		{
			case "range":
				$default1 = (is_array($default)) ? $default['value'][0] : '';
				$return 	 = JHTML::_('select.genericlist', $opts , $v.'[]', $attribs, 'elVal', 'elText', $default1, $element->name . "_filter_range_0" );
				$default1 = (is_array($default)) ? $default['value'][1] : '';
				$return 	 .= JHTML::_('select.genericlist', $opts , $v.'[]', $attribs, 'elVal', 'elText', $default1 , $element->name . "_filter_range_1");

				break;
			case "dropdown":
				$return 	 = JHTML::_('select.genericlist', $opts , $v, $attribs, 'elVal', 'elText', $default );
				break;

			case "field":
				$default = ( is_array( $default ) && array_key_exists( 'value', $default) ) ? $default['value'] : '';
				if (get_magic_quotes_gpc()) {
					$default			= stripslashes( $default );
				}
				$default = htmlspecialchars( $default );
				$return = "<input type='text' name='$v' class=\"inputbox fabrik_filter\" value=\"$default\" />";
				break;

		}
		$return .= "\n<input type='hidden' name='{$elName}[type]' value='$element->filter_type' />\n";
		$return .= "\n<input type='hidden' name='{$elName}[match]' value='{$element->filter_exact_match}' />\n";
		$return .= "\n<input type='hidden' name='{$elName}[full_words_only]' value='" . $params->get('full_words_only', '0') . "' />\n";
		return $return;
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
			'triggerEvent' => 'change'
			);
			return array( $ar );
	}

}
?>