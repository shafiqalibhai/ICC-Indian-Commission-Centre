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

class FabrikModelFabrikTextarea extends FabrikModelElement {

	var $_pluginName = 'textarea';

	/**
	 * Constructor
	 */

	function __construct()
	{
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
		$data = parent::renderTableData( $data, $oAllRowsData );
		//$$$rob dont strip slashes here - this is done when saving to db now 
		return $data;
	}

	/**
	 * state if the element uses a wysiwyg editor
	 */

	function useEditor()
	{
		$params =& $this->getParams();
		$element =& $this->getElement();
		if ($params->get( 'use_wysiwyg', 0 )) {
			return preg_replace("/[^A-Za-z0-9]/", "_", $element->name);
		} else {
			return false;
		}
	}

	/**
	 * determines if the element can contain data used in sending receipts, e.g. fabrikfield returns true
	 */

	function isReceiptElement()
	{
		return true;
	}

	/**
	 * draws the form element
	 * @param int repeat group counter
	 * @return string returns element html
	 */

	function render( $data, $repeatCounter = 0 )
	{
		$name		 	= $this->getHTMLName( $repeatCounter );
		$id 			= $this->getHTMLId( $repeatCounter );
		$element	=& $this->getElement();
		if($element->hidden == '1'){
			echo $this->getHiddenField( $name, $data[$name], $id );
			return;
		}
		$params 		=& $this->getParams();
		$cols 			= $element->width;
		$rows 			= $element->height;
		$value 			= $this->getDefaultValue( $data, $repeatCounter );
		if (!$this->_editable) {
			return $value;
		}
		$errorCSS  = '';
		if (isset( $this->_elementError ) && $this->_elementError != '') {
			$errorCSS = " elementErrorHighlight";
		}
		$name = str_replace( '.', '___', $name );
		if ($params->get( 'use_wysiwyg' )) {
			$str = FabrikHelperHTML::getEditorArea( $name, $value, $name, $cols, $rows, $cols, $rows );
		} else {
			$str = "<textarea class=\"fabrikinput inputbox$errorCSS\" name=\"$name\" id=\"". $id. "\" cols=\"$cols\" rows=\"$rows\">".$value."</textarea>\n";
		}
		if ($params->get( 'textarea-showmax' )) {
			$charsLeft = $params->get('textarea-maxlength') - strlen($value);
			$str .= "<div class='fabrik_characters_left'><span id='".$id . "_counter'>" . $charsLeft . "</span> " . JText::_( 'Characters left') . "</div>";
		}
		
		return $str;
	}

	/**
	 * get db field description
	 * @return string db field description
	 */

	function getFieldDescription()
	{
		return "TEXT";
	}


	/**
	 * return tehe javascript to create an instance of the class defined in formJavascriptClass
	 * @param int repeat group counter
	 * @return string javascript to create instance. Instance name must be 'el'
	 */

	function elementJavascript( $repeatCounter )
	{
		$id = $this->getHTMLId( $repeatCounter );
		$params =& $this->getParams();
		$opts =& $this->getElementJSOptions( $repeatCounter );
		$opts->max = $params->get('textarea-maxlength');
		$opts->wysiwyg = $params->get('use_wysiwyg') ? true: false;
		$opts = FastJSON::encode($opts);
		return "new fbTextarea('$id', $opts)"; 
	}

	/**
	 * load the javascript class that manages interaction with the form element
	 * should only be called once
	 * @return string javascript class file
	 */

	function formJavascriptClass()
	{
		FabrikHelperHTML::script( 'javascript.js', 'components/com_fabrik/plugins/element/fabriktextarea/', false );
	}
}
?>