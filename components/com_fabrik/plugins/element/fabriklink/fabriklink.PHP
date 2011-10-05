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

class FabrikModelFabrikLink  extends FabrikModelElement {

	var $_pluginName = 'link';

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
		$data = explode($this->_groupSplitter, $data);
		for ($i=0; $i <count($data); $i++) {
			$data[$i] = $this->_renderTableData( $data[$i], $oAllRowsData );
		}
		$data = implode($this->_groupSplitter, $data);
		return parent::renderTableData($data, $oAllRowsData );
	}

	/**
	 * @access private
	 *
	 * @param string data
	 * @param object all the data in the tables current row
	 * @return string formatted value
	 */

	function _renderTableData( $data, $oAllRowsData )
	{
		$data = explode( $this->_groupSplitter2, $data );
		$tableModel =& $this->_table;
		$params =& $this->getParams();
		if ( is_array( $data ) ){
			if ( count( $data ) == 1 ){ $data[1] = $data[0];}
			if(empty($data[1]) && empty($data[0])){
				return '';
			}
			if( $tableModel->_outPutFormat != 'rss' ){
				$smart_link = $params->get('link_smart_link', false);
				$target = $params->get('link_target', '');
				if ($smart_link || $target == 'mediabox') {
					$smarts = $this->_getSmartLinkType($data[1]);
					$link = "<a href='" . $data[1] . "' rel='lightbox[" . $smarts['type'] . " " . $smarts['width'] . " " . $smarts['height'] . "]'>" . $data[0] . "</a>";
				}
				else {
					$target = $params->get('link_target', '');
					$link = "<a href='" . $data[1] . "' target='" . $params->get('link_target', '') . "'>" . $data[0] . "</a>";
				}
			} else {
				$link = $data[1];
			}
			$w = new FabrikWorker();
			$link = $tableModel->parseMessageForRowHolder( $link, JArrayHelper::fromObject($oAllRowsData) );
			return $link;
		}
		return $data;
	}

	/**
	 * draws the form element
	 * @param int repeat group counter
	 * @return string returns element html
	 */

	function render( $data, $repeatCounter = 0 ) {
		$name 			= $this->getHTMLName( $repeatCounter );

		$id 				= $this->getHTMLId( $repeatCounter );
		$params 		=& $this->getParams();
		$element 		=& $this->getElement();
		$size 			= $element->width;
		$maxlength 	= $params->get( 'maxlength' );
		if ($maxlength == "0" or $maxlength == "") {
			$maxlength = $size;
		}
		$value 			= $this->getDefaultValue( $data, $repeatCounter );
		$sizeInfo 	=  " size=\"$size\" maxlength=\"$maxlength\"";
		if ($value == "") {
			$value = array('label'=>'', 'link'=>'');
		}else{
			if (!is_array( $value )) {
				$tmpvalue = explode($this->_groupSplitter2, $value);
				$value = array();
				$value['label']=$tmpvalue[0];
				if (count($tmpvalue) > 1) {
					$value['link']=$tmpvalue[1];
				} else {
					$value['link']=$tmpvalue[0];
				}
			}
		}
			
		if (count( $value ) == 0) {
			$value = array('label'=>'', 'link'=>'');
		}
		if (!$this->_editable) {
			$w = new FabrikWorker();
			if (is_array( $data )) {
				$value['link'] 	= $w->parseMessageForPlaceHolder( $value['link'], $data );
			} else {
				$value['link'] 	= $w->parseMessageForPlaceHolder( $value['link'] );
			}
			return "<a href='" . $value['link']. "'>"  . $value['label'] . "</a>";
		}
		$errorCSS  = '';
		if (isset( $this->_elementError ) && $this->_elementError != '') {
			$errorCSS = " elementErrorHighlight";
		}
		$labelname = FabrikString::rtrimword( $name, "[]" ) . '[label]';
		$linkname = FabrikString::rtrimword( $name, "[]" ) . '[link]';

		$str = "<div class='fabrikSubElementContainer' id='$id'>";
		$str .= JText::_('Label') . ":<br />";
		$str .= "<input  class=\"fabrikinput inputbox$errorCSS\" name=\"$labelname\" $sizeInfo value=\"" . $value['label'] . "\" />\n";
		$str .= "<br />" . JText::_('URL') . ":<br />";
		$str .= "<input class=\"fabrikinput inputbox$errorCSS\" name=\"$linkname\" $sizeInfo value=\"" . $value['link'] . "\" />\n";
		$str .="</div>";
		return $str;
	}

	function getFieldDescription()
	{
		return "TEXT";
	}

	function renderAdminSettings()
	{
		$params =& $this->getParams();
		$pluginParams =& $this->getPluginParams();
		?>
<div id="page-<?php echo $this->_name;?>" class="elementSettings"
	style="display: none"><?php
	echo $pluginParams->render( 'details' );
	echo $pluginParams->render( 'params', 'extra' );
	?></div>
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
		if ( is_array( $value ) ){
			$w = new FabrikWorker();
			$link 	= $w->parseMessageForPlaceHolder( $value['link'] );
			$value = "<a href='" . $link . "' >" . $value['label'] . "</a>";
		}
		return $value;
	}

	/**
	 *  manupulates posted form data for insertion into database
	 * @param mixed thie elements posted form data
	 * @param array posted form data
	 */

	function storeDatabaseFormat($val, $data)
	{
		/*
		$res = array();
		if (is_array( $val )) {
			// $$$ hugh - data already seems to be in split format
			
			// for($i=0;$i<count($val['label']);$i++){
			//	$res[] = $val['label'][$i] .$this->_groupSplitter2 . $val['link'][$i];
			//	}
			//	$val = implode( $this->_groupSplitter, $res );
			//	}
				
			$val = implode( $this->_groupSplitter2, $val );
		}
		return $val;
		*/
		$return = '';
		if (is_array( $val )) {
			foreach ($val as $key=>$v) {
				if (is_array( $v )) {
					// in repeat group
					$return .= implode( $this->_groupSplitter2, $v );
					$return .= $this->_groupSplitter;
				} else {
					// not in repeat group
					$return .= $v .$this->_groupSplitter2;
				}
			}
		}
		$return = FabrikString::rtrimword($return, $this->_groupSplitter);
		$return = FabrikString::rtrimword($return, $this->_groupSplitter2);
		return $return;
}
	  
	/**
	 * load the javascript class that manages interaction with the form element
	 * should only be called once
	 * @return string javascript class file
	 */

	function formJavascriptClass()
	{
		FabrikHelperHTML::script( 'javascript.js', 'components/com_fabrik/plugins/element/fabriklink/', true );
	}

	/**
	 * return the javascript to create an instance of the class defined in formJavascriptClass
	 * @return string javascript to create instance. Instance name must be 'el'
	 */

	function elementJavascript( $repeatCounter )
	{
		$id = $this->getHTMLId( $repeatCounter );
		$opts =& $this->getElementJSOptions( $repeatCounter );
		$opts = FastJSON::encode($opts);
		return "new fbLink('$id', $opts)" ;
	}

	/**
	 * can be overwritten by plugin class
	 * determines the value for the element in the form view
	 * @TODO: whats the diff between this and getValue() ?????
	 * $$$ROB - TESTING POINTING getValue() to here
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
			//stops this getting called from form validation code as it messes up repeated/join group validations
			if (array_key_exists('runplugins', $opts) && $opts['runplugins'] == 1){
				$formModel->getPluginManager()->runPlugins( 'onGetElementDefault', $formModel, 'form', $this );
			}
			if (is_array( $element->default )) {
				$element->default = implode( $this->_groupSplitter2, $element->default );
			}
			$this->defaults[$repeatCounter] = $element->default;
				
		}
		return $this->defaults[$repeatCounter];
	}

	function _getSmartLinkType ($link) {
		/* $$$ hugh - not really sure how much of this is necessary, like setting different widths
		 * and heights for different social video sites.  I copied the numbers from the examples page
		 * for mediabox: http://iaian7.com/webcode/mediaboxAdvanced
		 */
		$ret = array (
			'width' => '800',
			'height' => '600',
			'type' => 'mediabox'
			);
			if (preg_match('#^http://([\w\.]+)/#',$link,$matches)) {
				$site = $matches[1];
				// @TODO should probably make this a little more intelligent, like optional www,
				// and check for site specific spoor in the URL (like '/videoplay' for google,
				// '/photos' for flicker, etc).
				switch ($site) {
					case 'www.flickr.com':
						$ret['width'] = '400';
						$ret['height'] = '300';
						$ret['type'] = 'social';
						break;
					case 'video.google.com':
						$ret['width'] = '640';
						$ret['height'] = '400';
						$ret['type'] = 'social';
						break;
					case 'www.metacafe.com':
						$ret['width'] = '400';
						$ret['height'] = '350';
						$ret['type'] = 'social';
						break;
					case 'vids.myspace.com':
						$ret['width'] = '430';
						$ret['height'] = '346';
						$ret['type'] = 'social';
						break;
					case 'myspacetv.com':
						$ret['width'] = '430';
						$ret['height'] = '346';
						$ret['type'] = 'social';
						break;
					case 'www.revver.com':
						$ret['width'] = '480';
						$ret['height'] = '392';
						$ret['type'] = 'social';
						break;
					case 'www.seesmic.com':
						$ret['width'] = '425';
						$ret['height'] = '353';
						$ret['type'] = 'social';
						break;
					case 'www.youtube.com':
						$ret['width'] = '480';
						$ret['height'] = '380';
						$ret['type'] = 'social';
						break;
					case 'www.veoh.com':
						$ret['width'] = '540';
						$ret['height'] = '438';
						$ret['type'] = 'social';
						break;
					case 'www.viddler.com':
						$ret['width'] = '437';
						$ret['height'] = '370';
						$ret['type'] = 'social';
						break;
					case 'vimeo.com':
						$ret['width'] = '400';
						$ret['height'] = '302';
						$ret['type'] = 'social';
						break;
					case '12seconds.tv':
						$ret['width'] = '431';
						$ret['height'] = '359';
						$ret['type'] = 'social';
						break;
				}
				if ($ret['type'] == 'mediabox') {
					$ext = strtolower( JFile::getExt( $link ));
					switch ($ext) {
						case 'swf':
						case 'flv':
						case 'mp4':
							$ret['width'] = '640';
							$ret['height'] = '360';
							$ret['type'] = 'flash';
							break;
						case 'mp3':
							$ret['width'] = '400';
							$ret['height'] = '20';
							$ret['type'] = 'audio';
							break;
					}
				}
			}
			return $ret;
	}
}
?>