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

jimport('joomla.application.component.model');

require_once( JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'models'.DS.'element.php' );

class FabrikModelFabrikfield extends FabrikModelElement {

	var $_pluginName = 'field';

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
		$params =& $this->getParams();
		$this->_guessLinkType( $data );
		$format = $params->get( 'text_format_string' );
		if ($format  != '') {
			$data = sprintf( $format, $data );
		}
		return parent::renderTableData( $data, $oAllRowsData );
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
	 * @param array data to preopulate element with
	 * @param int repeat group counter
	 * @return string returns element html
	 */

	function render( $data, $repeatCounter = 0 )
	{
		$name 			= $this->getHTMLName( $repeatCounter );
		$id 				= $this->getHTMLId( $repeatCounter );
		$params 		=& $this->getParams();
		$element 		=& $this->getElement();
		$size 			= $element->width;
		$maxlength  = $params->get('maxlength');
		if ($maxlength == "0" or $maxlength == "") {
			$maxlength = $size;
		}
		$bits = array();
		$data 	=& $this->_form->_data;
		$value 	= $this->getDefaultValue( $data, $repeatCounter );
		$type = ( $params->get('password') == "1" ) ?"password" : "text";
		if (isset( $this->_elementError ) && $this->_elementError != '') {
			$type .= " elementErrorHighlight";
		}
		if ($element->hidden == '1') {
			$type = "hidden";
		}
		if (!$this->_editable) {
			$this->_guessLinkType( $value );
			if ($params->get('guess_linktype') == '1') {
				if (FabrikString::check_email_address( $value )) {
					$value = JHTML::_('email.cloak', $value );
				}
				else if (stristr( $value, 'http' )) {
					$value = "<a href='$value'>$value</a>";
				} else {
					if (stristr($value, 'www.' )) {
						$value = "<a href='http://$value'>$value</a>";
					}
				}
			}
			$format = $params->get( 'text_format_string' );
			if ($format  != '') {
				//$value =  eval( sprintf( $format,$value ) );
				//not sure why this was being evald??
				$value =  sprintf( $format, $value ) ;
			}
			return( $element->hidden == '1' ) ? "<!-- " . $value . " -->" : $value;
		}

		$bits['class']		= "fabrikinput inputbox $type";
		$bits['type']		= $type;
		$bits['name']		= $name;
		$bits['id']			= $id;
		//stop "'s from breaking the content out of the field.
		$bits['value']		= htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
		$bits['size']		= $size;
		$bits['maxlength']	= $maxlength;

		//cant be used with hidden element types
		if ($element->hidden != '1') {
			if ($params->get('readonly')){
				$bits['readonly'] = "readonly";
			}
			if ($params->get('disable')) {
				$bits['disabled'] = 'disabled';
			}
		}
		$str = "<input ";
		foreach ($bits as $key=>$val) {
			$str.= "$key = \"$val\" ";
		}
		$str .= " />\n";
		return $str;
	}

	/**
	 * format guess link type
	 *
	 * @param string $value
	 */
	function _guessLinkType( &$value )
	{
		$params =& $this->getParams();
		if ($params->get('guess_linktype') == '1') {
			if (FabrikString::check_email_address( $value )) {
				$value = JHTML::_('email.cloak', $value );
			}
			else if (stristr( $value, 'http' )) {
				$value = "<a href='$value'>$value</a>";
			} else {
				if (stristr($value, 'www.' )) {
					$value = "<a href='http://$value'>$value</a>";
				}
			}
		}
	}

	/**
	 * return tehe javascript to create an instance of the class defined in formJavascriptClass
	 * @return string javascript to create instance. Instance name must be 'el'
	 */

	function elementJavascript( $repeatCounter )
	{
		$element =& $this->getElement();
		$id = $this->getHTMLId( $repeatCounter );
		$opts =& $this->getElementJSOptions( $repeatCounter );
		$opts = FastJSON::encode($opts);
		return "new fbField('$id', $opts)" ;
	}

	/**
	 * load the javascript class that manages interaction with the form element
	 * should only be called once
	 * @return string javascript class file
	 */

	function formJavascriptClass()
	{
		FabrikHelperHTML::script( 'javascript.js', 'components/com_fabrik/plugins/element/fabrikfield/', false );
	}

	/**
	 * defines the type of database table field that is created to store the element's data
	 */
	function getFieldDescription()
	{
		$p = $this->getParams();
		switch ($p->get( 'text_format' )) {
			case 'text':
			default:
				$objtype = "VARCHAR(255)";
				break;
			case 'integer':
				$objtype = "INT(" . $p->get('integer_length', 10) . ")";
				break;
			case 'decimal':
				$total = (int)$p->get('integer_length', 10) + (int)$p->get('decimal_length', 2);
				$objtype = "DECIMAL(" . $total . "," . $p->get('decimal_length', 2) . ")";
				break;
		}
		return $objtype;
	}

}
?>