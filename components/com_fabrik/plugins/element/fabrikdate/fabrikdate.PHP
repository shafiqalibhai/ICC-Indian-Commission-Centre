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

require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'html.php' );
require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'parent.php' );

class FabrikModelFabrikDate  extends FabrikModelElement {

	//@TODO: filter code

	var $_pluginName = 'date';

	/** @var bol toggle to determine if storedatabaseformat resets the date to GMT*/
	var $_resetToGMT = true;
	/**
	 * Constructor
	 */

	function __construct()
	{
		parent::__construct();
	}

	/**
	 * Dates are stored in database as GMT times
	 * i.e. with no offsets
	 * This is to allow us in the future of render dates based
	 * on user tmezone offsets
	 * Dates are displayed in forms and tables with the global timezone
	 * offset applied
	 */

	/**
	 * shows the data formatted for the table view
	 * @param string data (should be in mySQL format already)
	 * @param string element name
	 * @param object all the data in the tables current row
	 * @return string formatted value
	 */

	function renderTableData( $data, $oAllRowsData )
	{
		//@TODO: deal with time options (currently can be defined in date_table_format param).

		$config =& JFactory::getConfig();
		$tzoffset = (int)$config->getValue('config.offset');
			
		$db 		=& JFactory::getDBO();
		$aNullDates = array( '0000-00-000000-00-00','0000-00-00 00:00:00','0000-00-00','', $db->getNullDate() );
		$params =& $this->getParams();
		$db 		=& JFactory::getDBO();

		$groupModel =& $this->getGroup();
		if ($groupModel->canRepeat()) {
			$data = explode($this->_groupSplitter, $data);
		} else {
			$data = array($data);
		}
		$f = $params->get( 'date_table_format', '%Y-%m-%d' );


		if ($f == 'Y-m-d') {
			$f = '%Y-%m-%d';
		}
		$format = array();
		foreach ($data as $d) {
			if (!in_array( $d, $aNullDates )) {
				$date 	= JFactory::getDate( $d );
				$date->setOffset( $tzoffset );
				if ($f == '{age}') {
					$format[] = date('Y') - $date->toFormat('%Y');
				} else {
					$format[] = $date->toFormat( $f );
				}
			} else {
				$format[] = '';
			}
		}
		if (count($format) > 1) {
			return "<ul class='fabrikRepeatData'><li>".implode("</li><li>", $format) . "</li></ul>";
		} else {
			return implode('', $format);
		}
	}

	/**
	 * shows the data formatted for the CSV export view
	 * @param string data (should be in mySQL format already)
	 * @param string element name
	 * @param object all the data in the tables current row
	 * @return string formatted value
	 */

	function renderTableData_csv( $data, $oAllRowsData )
	{
		//@TODO: deal with time options (currently can be defined in date_table_format param).

		$config =& JFactory::getConfig();
		$tzoffset = (int)$config->getValue('config.offset');
			
		$db 		=& JFactory::getDBO();
		$aNullDates = array( '0000-00-000000-00-00','0000-00-00 00:00:00','0000-00-00','', $db->getNullDate() );
		$params =& $this->getParams();
		$db 		=& JFactory::getDBO();

		$groupModel =& $this->getGroup();
		if ($groupModel->canRepeat()) {
			$data = explode($this->_groupSplitter, $data);
		} else {
			$data = array($data);
		}
		$f = $params->get( 'date_table_format', '%Y-%m-%d' );
		$incRaw = JRequest::getVar('incraw', true);

		if ($f == 'Y-m-d') {
			$f = '%Y-%m-%d';
		}
		$format = array();
		foreach ($data as $d) {
			if (!in_array( $d, $aNullDates )) {
				if ($incRaw) {
					$format[] = $d;
				}
				else {
					$date 	= JFactory::getDate( $d );
					$date->setOffset( $tzoffset );
					if ($f == '{age}') {
						$format[] = date('Y') - $date->toFormat('%Y');
					} else {
						$format[] = $date->toFormat( $f );
					}
				}
			} else {
				$format[] = '';
			}
		}
		if (count($format) > 1) {
			return implode("//..*..//", $format);
		} else {
			return implode('', $format);
		}
	}

	/**
	 * draws the form element
	 * @param int repeat group counter
	 * @return string returns element html
	 */

	function render( $data, $repeatCounter = 0 )
	{
		//@TODO: deal with time picker
		$config =& JFactory::getConfig();
		$tzoffset = $config->getValue('config.offset');
		$db 		=& JFactory::getDBO();
		$aNullDates = array( '0000-00-000000-00-00','0000-00-00 00:00:00','0000-00-00','', $db->getNullDate() );
		FabrikHelperHTML::loadCalendar();
		$name 		= $this->getHTMLName( $repeatCounter );
		$id				= $this->getHTMLId( $repeatCounter );
		$params 	=& $this->getParams();
		$element 	=& $this->getElement();
		$format 	= $params->get( 'date_form_format', $params->get( 'date_table_format', '%Y-%m-%d') );
		$timeformat = $params->get( 'date_time_format' );
		//value should be in mySQL format
		$value = $this->getDefaultValue( $data, $repeatCounter );

		if ($params->get( 'date_showtime', 0 ) && !$element->hidden) {
			$name = str_replace( '[]', '[0]', $name );
			//cant have names as simply [] as json only picks up the last one
			$timeElName = $name."[time]";
			$name .= '[date]';
		}
		if (!in_array( $value, $aNullDates )) {
			$oDate 	= JFactory::getDate( $value );
			$oDate->setOffset( $tzoffset );
			//get the formatted date
			$date 		= $oDate->toFormat( $format );
				
			if (!$this->_editable) {
				$time = ($params->get( 'date_showtime', 0 )) ?  " " .$oDate->toFormat( $timeformat ) : '';
				return $date . $time;
			}
				
			$str = $this->calendar( $date, $name, $id, $format, array('class'=>'fabrikinput inputbox', 'size'=>$element->width,  'maxlength'=>'19'), $repeatCounter);

			//get the formatted time
			if ($params->get( 'date_showtime', 0 )) {
				if (!$element->hidden) {
					$time 			= $oDate->toFormat( $timeformat );
					$timeId = $this->getTimeId($repeatCounter);
					$length = strlen($timeformat);
					$str .= "\n<input class='inputbox' size='$length' value='$time' name='$timeElName' id='$timeId'/>";
					$str .= "\n".'<input type="button" style="background:url('.COM_FABRIK_LIVESITE.'/components/com_fabrik/plugins/element/fabrikdate/images/time.gif) no-repeat top left;border:0;padding:0;margin:0;height:16px;width:16px;"  class="button" value="..." id="' . $timeId . '_button_time" />';
				}

			}
		}
		else {
			if (!$this->_editable) {
				return '';
			}
			$str = $this->calendar( '', $name, $id, $format, array('class'=>'fabrikinput inputbox', 'size'=>$element->width,  'maxlength'=>'19'));

			//get the formatted time
			if ($params->get( 'date_showtime', 0 )) {
				if (!$element->hidden) {
					$timeformat = $params->get( 'date_time_format' );
					$time 			= '';
					$timeId = $this->getTimeId($repeatCounter);
					$length = strlen($timeformat);
					$str .= "\n<input class='inputbox' size='$length' value='' name='$timeElName' id='$timeId'/>";
					$str .= "\n".'<img src="'.COM_FABRIK_LIVESITE.'/components/com_fabrik/plugins/element/fabrikdate/images/time.gif" id="' . $timeId . '_button_time" />';
				}
			}
		}
		return $str;
	}
	
	/**
	 * get the html id for the time element
	 *
	 * @return string time element's html id
	 */
	
	function getTimeId( $repeatCounter = 0 )
	{
		$timeId = $this->getHTMLId( $repeatCounter );
		if (strstr( $timeId, '[]' )) {
			$timeId = FabrikString::rtrimword( $timeId, '[]' );
		}
		$timeId .= '_time';
		return $timeId;
	}

	/**
	 * Enter description here...
	 *
	 * @param unknown_type $val
	 * @return unknown
	 */
	
	function _indStoreDBFormat( $val ){
		$params =& $this->getParams();

		//test if its already in correct format (or empty)

		if ($val == '') {
			return $val;
		}
		// $$$ rob moved beneath as here $val can be an array which gives errors as getDate expects a string
		/*$orig = JFactory::getDate( $val );
		if ($val === $orig->toMySQL()) {
			return $this->toMySQLGMT( $orig );
		}*/

		if ($params->get( 'date_showtime', 0 )) {
			$format =  $params->get('date_form_format') . ' ' . $params->get('date_time_format');
			// $$$ hugh - no can do, getDefault already munged $val into a string
			// $$$ rob - erm no! - its an array when submitting from the form, perhaps elsewhere its sent 
			// as a string - so added test for array
			if (is_array( $val )) {
			  $val = $val['date'] . ' ' . $this->_fixTime($val['time']);
			}
		} else {
			$format = $params->get('date_form_format', $params->get('date_table_format', '%Y-%m-%d'));
		}
		
	  $orig = JFactory::getDate( $val );
		if ($val === $orig->toMySQL()) {
			return $this->toMySQLGMT( $orig );
		}
		
		$datebits = FabrikWorker::strToDateTime( $val, $format );
		$date = JFactory::getDate( $datebits['timestamp'] );
		//if ($params->get( 'date_showtime', 0 )) {
		  $date = $this->toMySQLGMT( $date );
		//}
		//$date = $date->toMySQL();
		return $date;
	}

	/**
	 * reset the date to GMT - inversing the offset
	 *@param date object
	 * @return string mysql formatted date
	 */
	function toMySQLGMT($date)
	{
		if ($this->_resetToGMT) {
			$config =& JFactory::getConfig();
			$tzoffset = (int)$config->getValue('config.offset') * -1;
			$date->setOffset( $tzoffset );
			return $date->toMySQL(true);
		}
		return $date->toMySQL();
	}

	/**
	 * formats the posted data for insertion into the database
	 * @param mixed thie elements posted form data
	 * @param array posted form data
	 */

	function storeDatabaseFormat( $val, $data )
	{
		//@TODO: deal with failed validations
		$groupModel =& $this->getGroup();
		if ($groupModel->canRepeat()) {
			if (is_array( $val )) {
				$res = array();
				foreach ($val as $v) {
					$res[] = $this->_indStoreDBFormat($v);

				}
				return implode( $this->_groupSplitter, $res );
			}
		}
		return $this->_indStoreDBFormat( $val );
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
		# $$$ hugh - need to convert to database format so we GMT-ified date
		$val = $this->renderTableData( $this->storeDatabaseFormat($value,$data), new stdClass()) ;
		return $val ;
	}

	/**
	 * ensure the time is in a full length format
	 *
	 * @param string $time
	 * @return formatted time
	 */

	function _fixTime( $time )
	{
		//if its 5:00 rather than 05:00
		if (!preg_match( "/^[0-9]{2}/", $time )) {
			$time = "0".$time;
		}
		//if no seconds
		if (preg_match( "/[0-9]{2}:[0-9]{2}/", $time) && strlen($time) <= 5) {
			$time .= ":00";
		}
		//if it doesnt match reset it to 0
		if (!preg_match( "/[0-9]{2}:[0-9]{2}:[0-9]{2}/", $time )) {
			$time = "00:00:00";
		}
		return $time;
	}

	/**
	 * Displays a calendar control field
	 *
	 * hacked from behaviour as you need to check if the element exists
	 * it might not as you could be using a custom template
	 * @param	string	The date value
	 * @param	string	The name of the text field
	 * @param	string	The id of the text field
	 * @param	string	The date format
	 * @param	array	Additional html attributes
	 * @param int repeat group counter
	 */

	function calendar($value, $name, $id, $format = '%Y-%m-%d', $attribs = null, $repeatCounter = 0)
	{
		JHTML::_('behavior.calendar'); //load the calendar behavior

		if (is_array($attribs)) {
			$attribs = JArrayHelper::toString( $attribs );
		}

		$document =& JFactory::getDocument();
		
		$opts = FastJSON::encode($this->_CalendarJSOpts($id));
		$validations =& $this->getValidations();
		
		$script = 'window.addEvent(\'domready\', function() {
		if($("' . $id . '")){ ';
		if (!empty( $validations )) {
			//if we have a validation on the element run it when the calendar closes itself
			//this ensures that alert messages are removed if the new data meets validation criteria
			$id 			= $this->getHTMLId( $repeatCounter );
			$opts = rtrim($opts, "}");
			$formModel =& $this->getForm();
			$opts .= ',"onClose":onclose}';
			$script .= 'var onclose = (function(e){
				this.hide();
				form_'.$formModel->_id.'.doElementValidation(\''.$id.'\');
			});';
		}
		$script .= 'Calendar.setup('.$opts.');'.
		'}'.
		"\n});";
		$document->addScriptDeclaration($script);

		return '<input type="text" name="'.$name.'" id="'.$id.'" value="'.htmlspecialchars($value, ENT_COMPAT, 'UTF-8').'" '.$attribs.' />'.
				 '<img class="calendar" src="'.JURI::root(true).'/templates/system/images/calendar.png" alt="calendar" id="'.$id.'_img" />';
	}


	function _CalendarJSOpts( $id = null )
	{
		$params =& $this->getParams();
		if (!isset( $id )) {
			$id = $this->getHTMLId();
		}
		$opts = new stdClass();
		$opts->inputField = $id;
		$opts->ifFormat = $params->get( 'date_form_format' );
		$opts->button = $id."_img";
		$opts->align = "Tl";
		$opts->singleClick =  true;
		$opts->firstDay = intval($params->get( 'date_firstday' ));
		return $opts;
	}

	/**
	 * load the javascript class that manages interaction with the form element
	 * should only be called once
	 * @return string javascript class file
	 */

	function formJavascriptClass()
	{
		FabrikHelperHTML::script( 'javascript.js', 'components/com_fabrik/plugins/element/fabrikdate/', false );
	}

	/**
	 * return tehe javascript to create an instance of the class defined in formJavascriptClass
	 * @return string javascript to create instance. Instance name must be 'el'
	 */

	function elementJavascript( $repeatCounter )
	{
		$params 					=& $this->getParams();
		$id 							= $this->getHTMLId( $repeatCounter );
		$opts 						=& $this->getElementJSOptions( $repeatCounter );
		$opts->showtime 	= ($params->get( 'date_showtime', 0 )) ? true : false;
		$opts->timelabel 	= JText::_('time');
		$opts->typing			= $params->get('date_allow_typing_in_field', true);
		//for reuse if element is duplicated in repeat group
		$opts->calendarSetup = $this->_CalendarJSOpts( $id );
		$opts 						= FastJSON::encode( $opts );
		return "new fbDateTime('$id', $opts)";
	}

	/**
	 * get the type of field to store the data in
	 *
	 * @return string field description
	 */

	function getFieldDescription()
	{
		$groupModel =& $this->getGroup();
		if (is_object($groupModel) && $groupModel->canRepeat()) {
			return "VARCHAR(255)";
		} else {
			return "DATETIME";
		}
	}
	
	/**
	 *
	 * Examples of where this would be overwritten include timedate element with time field enabled
	 * @param int repeat group counter
	 * @return array html ids to watch for validation
	 */
	
	function getValidationWatchElements( $repeatCounter )
	{
		$params 	=& $this->getParams();
		$return		= array();
		$elName 	= $this->getHTMLName( $repeatCounter );
		$id 			= $this->getHTMLId( $repeatCounter );
		
		$return[] = array(
			'id' 			=> $id,
			'triggerEvent' => 'blur'
		);
		
		if ($params->get( 'date_showtime' )) {
			$timeid = $this->getTimeId($repeatCounter);
			
			//$elName = str_replace( '[]', '[0]', $elName );
			//cant have names as simply [] as json only picks up the last one
			$return[] = array(
				'id' 			=> $timeid,
				'triggerEvent' => 'blur'
			);
		}
		return $return;
	}

	/**
	 * renders admin settings
	 */

	function renderAdminSettings()
	{
		$pluginParams =& $this->getPluginParams();
		?>
<div id="page-<?php echo $this->_name;?>" class="elementSettings"
	style="display: none"><?php
	echo $pluginParams->render( 'details' );
	echo $pluginParams->render( 'params', 'extra' );?></div>
	<?php
	}

	/**
	 * can be overwritten by plugin class
	 * determines the value for the element in the form view
	 * @param array data
	 * @param int when repeating joinded groups we need to know what part of the array to access
	 * @param array options
	 * @return string default date value in GMT time
	 */

	function getDefaultValue( $data, $repeatCounter = 0, $opts = array() )
	{
		//@TODO: allow {now} and {today} to be replaced with current datetime
		if (is_null( $this->defaults )) {
			$this->defaults = array();
		}
		if (!array_key_exists( $repeatCounter, $this->defaults )) {
			$groupModel =& $this->getGroup();
			$group			=& $groupModel->getGroup();
			$joinid			= $group->join_id;
			$element		=& $this->getElement();
			$params			=& $this->getParams();
			// $$$ hugh - adding check for defaulttotoday
			// I *think* we ignore requested format and just do MySQL format here
			// render() will then do the farting around with formats
			//$config =& JFactory::getConfig();
			//$tzoffset = $config->getValue( 'config.offset' );
			//echo $tzoffset;
			if ($params->get('date_defaulttotoday', 0)) {
				$oTmpDate = JFactory::getDate( );
				//dont apply an offset here 
				$default = $oTmpDate->toMySQL();
				//$default = strftime('%Y-%m-%d %H:%M:%S',time()+($tzoffset*60*60));
			}
			else {
				// deafult date should always be entered as gmt date e.g. eval'd default of:
				//return gmdate('Y-m-d H:i:s');
				$default	 	=& $element->default;
				if ($element->eval == "1") {
					$default = @eval( stripslashes( $default ) );
				}
				if ($default != '') {
					$oTmpDate = JFactory::getDate( $default );
					$default = $oTmpDate->toMySQL();
				}
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

			if (is_array( $default )) {
				//TIMEDATE option set - explode with space rather than comma
				//url decode if it comes from ajax calendar form
				// $$$ @TODO - fix this! PHP is throwing notice when time thingy is turned off, see #259
				if ($default['time'] != '' && $default['date'] != '') {
					$default['time'] = $this->_fixTime(urldecode($default['time']));
					$default = implode( ' ', $default );
				}
				else {
					$default = '';
				}
			}
			$element->default = $default;
			$formModel =& $this->getForm();
			//stops this getting called from form validation code as it messes up repeated/join group validations
			if (array_key_exists('runplugins', $opts) && $opts['runplugins'] == 1){
				$formModel->getPluginManager()->runPlugins( 'onGetElementDefault', $formModel, 'form', $this );
			}
			//for validations (empty time and date element gives ' '
			if ($element->default == ' ') {
				$element->default = '';
			}
			$this->defaults[$repeatCounter] = $element->default;
			
		}

		return $this->defaults[$repeatCounter];
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

	function toDbVal( $str, $repeatCounter )
	{
		//only format if not empty otherwise search forms will filter
		//for todays date even when no date entered
		$this->_resetToGMT = false;
		if ($str != '') {
			$str =  $this->storeDatabaseFormat( $str, array() );
		}
		$this->_resetToGMT = true;
		return $str;
	}

	/**
	 * Get the table filter for the element
	 * @return string filter html
	 */
	function &getFilter()
	{
		global $mainframe;
		$params 		=& $this->getParams();
		$tableModel =& $this->getTableModel();
		$table 			=& $tableModel->getTable();
		$element 		=& $this->getElement();
		$origTable 	= $table->db_table_name;
		$fabrikDb 	=& $tableModel->getDb();
		$aFilter 		=& $tableModel->getFilterArray();
		$js 				= "";
		$elName 		= $this->getFullName( true, true, false );
		$dbElName		= $this->getFullName( true, false, false );
		$elName2 		= $this->getFullName( false, false, false );

		$ids 			= $tableModel->getColumnData( $elName2 );
		$elLabel		= $element->label;
		$elExactMatch 	= $element->filter_exact_match;
		$v 				= $elName . "[value]";
		$t 				= $elName . "[type]";
		$e 				= $elName . "[match]";
		$fullword 		= $elName . "[full_words_only]";
		//corect default got


		$default = $this->getDefaultFilterVal();
		$aThisFilter = array();

		$format = $params->get( 'date_table_format', '%Y-%m-%d' );

		$fromTable = $origTable;
		$joinStr = '';
		foreach ( $tableModel->_aJoins as $aJoin ) {
			// not sure why the group id key wasnt found - but put here to remove error
			if ( array_key_exists( 'group_id', $aJoin ) ) {
				if ($aJoin->group_id == $element->group_id && $aJoin->element_id == 0) {
					$fromTable = $aJoin->table_join;
					$joinStr = " LEFT JOIN $fromTable ON " . $aJoin->table_join . "." . $aJoin->table_join_key . " = " . $aJoin->join_from_table . "." . $aJoin->table_key;
					$elName = str_replace( $origTable . '.', $fromTable . '.', $elName);
					//$where = "\n WHERE TRIM($dbElName) <> '' $where2 GROUP BY elText ASC";
					$v = $fromTable . '___' . $element->name . "[value]";
					$t = $fromTable . '___' . $element->name . "[type]";
					$e = $fromTable . '___' . $element->name . "[match]";
					$fullword = $elName . "[full_words_only]";
				}
			}
		}
		$dbElName = explode(".", $dbElName);
		$dbElName = "`" . $dbElName[0] . "`.`" . $dbElName[1] . "`";

		//dont format here as the format string is different between mysql and php's calendar strftime
		//$sql = "SELECT DISTINCT( DATE_FORMAT($dbElName, '$format') ) AS elText, $dbElName AS elVal FROM `$origTable` $joinStr\n";
		$sql = "SELECT DISTINCT($dbElName) AS elText, $dbElName AS elVal FROM `$origTable` $joinStr\n";

		$sql .= "WHERE $dbElName IN ('" . implode( "','", $ids ) . "')"
		. "\n AND TRIM($dbElName) <> '' GROUP BY elText ASC";
		$requestName 		= $elName . "___filter";
		if (array_key_exists( $elName, $_REQUEST )) {
			if (is_array( $_REQUEST[$elName] ) && array_key_exists( 'value', $_REQUEST[$elName] ) ) {
				$_REQUEST[$requestName] = $_REQUEST[$elName]['value'];
			}
		}

		$context					= 'com_fabrik.table' . $table->id . '.filter.' . $requestName;
		$default			= $mainframe->getUserStateFromRequest( $context, $requestName, $default );

		$format = $params->get( 'date_table_format', '%Y-%m-%d' );
		switch ($element->filter_type)
		{
			case "range":
				FabrikHelperHTML::loadCalendar();
				//@TODO: this messes up if the table date format is different to the form date format
				if (empty( $default )) {
					$default = array('','');
				}
				$return = JText::_( 'DATE RANGE BETWEEN' ) . JHTML::_('calendar',  $default[0], $v.'[]', $this->getHTMLId() . "_filter_range_0", $format, array('class'=>'inputbox fabrik_filter',  'maxlength'=>'19') );
				$return .= "<br />" . JText::_( 'DATE RANGE AND' ) . JHTML::_('calendar',  $default[1], $v.'[]', $this->getHTMLId() . "_filter_range_1", $format, array('class'=>'inputbox fabrik_filter',  'maxlength'=>'19'));
				break;

			case "dropdown":
				$fabrikDb->setQuery( $sql );
				FabrikHelperHTML::debug( $fabrikDb->getQuery(), 'fabrikdate getFilter' );
				$oDistinctData = $fabrikDb->loadObjectList();
				// cant do the format in the MySQL query as its not the same formatting
				// e.g. M in mysql is month and J's date code its minute
				foreach($oDistinctData as $k=>$o){
					if ($fabrikDb->getNullDate() === $o->elText) {
						$o->elText = '';
						$o->elVal = '';
					}else{
						$d = new JDate($o->elText);
						$o->elText = $d->toFormat($format) ;
					}
				}
				$obj = new stdClass;
				$obj->elVal  = "";
				$obj->elText = JText::_( 'Please select' );
				$aThisFilter[] = $obj;
				if (is_array( $oDistinctData )) {
					$aThisFilter = array_merge( $aThisFilter, $oDistinctData );
				}
				$return 	 = JHTML::_('select.genericlist',  $aThisFilter, $v, 'class="inputbox fabrik_filter" size="1" maxlength="19"', "elVal", 'elText', $default,  $this->getHTMLId() . "_filter_range_0");
				break;

			case "field":
				if (is_array( $default )) {
					$default = array_shift($default);
				}
				if (get_magic_quotes_gpc()) {
					$default			= stripslashes( $default );
				}
				$default = htmlspecialchars( $default );

				$return = JHTML::_('calendar', $default, $v, $this->getHTMLId() . "_filter_range_0", $format, array('class'=>'inputbox fabrik_filter',  'maxlength'=>'19') );
				break;

		}
		$return .= "\n<input type='hidden' name='$t' value='$element->filter_type' />\n";
		$return .= "\n<input type='hidden' name='$e' value='$elExactMatch' />\n";
		$return .= "\n<input type='hidden' name='$fullword' value='" . $params->get('full_words_only', '0') . "' />\n";
		return $return;
		/**/
	}

	/**
	 * Get the sql for filtering the table data and the array of filter settings
	 * @param array posted data for the element
	 * @param array filters
	 * @param string db col key name e.g. table.elname
	 * @param string form key name e.g. table___elname
	 * @return array filter
	 * */

	function getFilterConditionSQL( $val, $aFilter, $dbKey, $key )
	{

		$cond = '';
		// if posted data comes from a module we want to strip out its table name
		// and replace it with current table name
		// not sure how to deal with this for joins
		$params 					=& $this->getParams();
		$element 					=& $this->getElement();

		$fromModule 		 	= JRequest::getBool( 'fabrik_frommodule', 0 );

		$filterType 			= isset( $val['type']) ? $val['type'] : $element->filter_type;;
		$filterVal 				= isset( $val['value'] )? $val['value'] : '';
		$filterExactMatch = isset( $val['match'] )? $val['match'] : $element->filter_exact_match;;
		$fullWordsOnly 		= isset( $val['full_words_only'] )? $val['full_words_only'] : $params->get('full_words_only');
		$joinDbName 			= isset( $val['join_db_name']) ?  $val['join_db_name'] : '';
		$joinKey 					= isset( $val['join_key_column']) ?  $val['join_key_column'] : '';
		$joinVal 					= isset( $val['join_val_column']) ?  $val['join_val_column'] : '';

		if ($filterVal == "" ) {
			return;
		}
		$w =& new FabrikWorker();
		$format = $params->get( 'date_table_format' );
		if (is_array( $filterVal )) {
			//ranged
			// $$$ hugh - added test for empty - for some reason, sometimes we get spurious empty
			// date range filters, which won't trip the == "" test above.
			// @TODO we should probably handle cases where they will in one date but not the other.
			$empty = true;
			foreach ($filterVal as $k => $v) {
				if ($v != '') {
					$empty = false;
					$datebits = $w->strToDateTime( $v, $format );
					$date = JFactory::getDate( $datebits['timestamp'] );
					$filterVal[$k] = $date->toMySQL();
				}
			}
			if ($empty) {
				return '';
			}
		}else{
			//field OR driopdown data
			$datebits = $w->strToDateTime( $filterVal, $format );
			if ($datebits) {
				$reformatted = true;
				$date = JFactory::getDate( $datebits['timestamp'] );
			} else {
				//if from a dropdown the value is already in the correct format
				$reformatted = false;
				$date = JFactory::getDate( $filterVal );
			}

			$filterVal = $date->toMySQL();
			$finalformat = $w->getFinalDateFormat();

			if (!$filterExactMatch && $reformatted) {
				//if the search was performed in a field check if the date included a time element
				$chars = array(  '%g', '%H', '%h', '%i', '%s', '%M' );
				$found = false;
				foreach($chars as $char){
					if (strstr( $finalformat, $char )) {
						//date did include a time element so we can continue to use the field element
						$found = true;
						break;
					}
				}
				if (!$found) {
					//ok date didnt include a time element so lets do a range over the current day.
					$endtimestamp = $datebits['timestamp'] + (60 * 60 * 24); //add a day to the date
					$enddate = JFactory::getDate( $endtimestamp );
					$filterVal = array( $filterVal, $enddate->toMySQL() );
					$filterType = 'range';
				}
			}
		}

		switch( $filterType )
		{
			case 'dropdown';

			if ( $fromModule ) {
				$aKeyParts = explode( '.', $key);
				$key = $this->db_table_name . '.' . $aKeyParts[1];
			}
			if (!is_array( $filterVal )) {
				$filterVal = urldecode( $filterVal );
				if ($filterExactMatch == '0') {
					$cond = " $dbKey LIKE '%$filterVal%' ";
				} else {
					$cond = " $dbKey = '$filterVal' ";
				}
			}else{
				$cond = "( ";
				foreach ($filterVal as $fval) {
					if (trim( $fval ) != '') {
						if ( $filterExactMatch == '0' ) {
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
				$aFilter[$key][] = array( 'type'=>'dropdown', 'value'=>$filterVal, 'filterVal'=>$filterVal, 'sqlCond' =>$cond );
			} else {
				$aFilter[$key] = array( 'type'=>'dropdown', 'value'=>$filterVal, 'filterVal'=>$filterVal, 'sqlCond' =>$cond );
			}
			break;
			case "":
			case "field":
				$filterVal = urldecode( $filterVal );
				$filterCondSQL = '';
				if ($joinDbName != '') {
					$filterCondSQL .= " LEFT JOIN $joinDbName ON $joinDbName.$joinKey = $dbKey ";
				}
				$cond = " $dbKey = '$filterVal'";

				$aFilter[$key] = array('type'=>'field',
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
				$filterVal = urldecode( $filterVal );
				$cond2 = $key . " " . str_replace( '\"', '"', $filterVal );
				$cond = $cond2;
				$aFilter[$key] = array('type'=>'search',
				  'value'=>$cond2, 
					'filterVal'=>$filterVal, 
					'full_words_only'=>$fullWordsOnly,
					'join_db_name' => $joinDbName,
					'join_db_key' => $joinKey,
					'sqlCond' =>$cond
				);
				break;

			case "range":
			  $config =& JFactory::getConfig();
		    $tzoffset = (int)$config->getValue('config.offset');
		
				if ($filterVal[0] != '' & $filterVal[1] != '') {
					//bit of a hack to get month ranges working
					$format = $params->get( 'date_table_format', 'Y-m-d' );
					if ($format == 'm/Y') {
						$cond = " unix_timestamp($dbKey) >= " . strtotime($filterVal[0]) . " AND unix_timestamp($dbKey) <= " . strtotime($filterVal[1]);
					} else {
						//$cond = " unix_timestamp($dbKey) BETWEEN " . strtotime($filterVal[0]) . " AND " . strtotime($filterVal[1]) ;
						$cond = " (unix_timestamp($dbKey) + ($tzoffset * 60 * 60)) BETWEEN " . strtotime($filterVal[0]) . " AND " . strtotime($filterVal[1]) ;
					  
					}
					$aFilter[$key] = array('type'=>'range',
					  'value'=>$filterVal, 
						'filterVal'=>$filterVal, 
						'full_words_only'=>$fullWordsOnly,
						'join_db_name' => $joinDbName,
						'join_db_key' => $joinKey
					, 'sqlCond' => $cond
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
	 * when importing csv data you can run this function on all the data to
	 * format it into the format that the form would have submitted the date
	 *
	 * @param array data
	 * @param string table column heading
	 */

	function prepareCSVData( &$data, $key )
	{
		$params =& $this->getParams();
		$format 	= $params->get( 'date_form_format' );
		//go through data and turn any dates into unix timestamps
		for ($j=0; $j < count( $data ); $j++) {
			$date 	= JFactory::getDate( $data[$j][$key] );
			$data[$j][$key] = $date->toFormat( $format );
		}
	}

}

?>
