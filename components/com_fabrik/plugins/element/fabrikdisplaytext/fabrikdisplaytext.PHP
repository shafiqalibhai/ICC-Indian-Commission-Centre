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

class FabrikModelFabrikDisplaytext  extends FabrikModelElement {

	var $_pluginName = 'displaytext';

	/**
	 * Constructor
	 */

	function __construct()
	{
		parent::__construct();
	}

	function setIsRecordedInDatabase(){
		$this->_recordInDatabase = false;
	}

	/**
	 * write out the label for the form element
	 * @param object form
	 * @param bol encase label in <label> tag
	 * @param string id of element related to the label
	 */

	function getLabel()
	{
		return "";
	}

	/**
	 * draws the form element
	 * @param array data
	 * @param int repeat group counter
	 * @return string returns element html
	 */

	function render( $data, $repeatCounter = 0 ) {
		return $this->getDefaultValue( $data, $repeatCounter );
	}

	/**
	 * draws the form element
	 * @param array data
	 * @param int repeat group counter
	 * @param array options
	 * @return string default value
	 */

	function getDefaultValue( $data, $repeatCounter = 0, $opts = array() )
	{
		$w =& new FabrikWorker();
		$element =& $this->getElement();
		if ($element->eval) {
			//strip html tags
			$element->label = preg_replace(  '/<[^>]+>/i', '', $element->label );
			//change htmlencoded chars back
			$element->label = htmlspecialchars_decode( $element->label );
			$element->default = eval( $element->label );
		} else {
			$element->default = $element->label;
		}
		$formModel =& $this->getForm();
		//stops this getting called from form validation code as it messes up repeated/join group validations
		if (array_key_exists('runplugins', $opts) && $opts['runplugins'] == 1){
			$formModel->getPluginManager()->runPlugins( 'onGetElementDefault', $formModel, 'form', $this );
		}
		return $w->parseMessageForPlaceHolder( $element->default, $data );
	}

	/**
	 * get the db field description
	 *
	 * @return string
	 */

	function getFieldDescription()
	{
		return "TEXT";
	}

	/**
	 * render this elements admin section
	 *
	 */

	function renderAdminSettings( )
	{
		$pluginParams =& $this->getPluginParams();
		?>
<div id="page-<?php echo $this->_name;?>" class="elementSettings"
	style="display: none"><?php echo $pluginParams->render( 'details' );?></div>
		<?php
	}
}
?>