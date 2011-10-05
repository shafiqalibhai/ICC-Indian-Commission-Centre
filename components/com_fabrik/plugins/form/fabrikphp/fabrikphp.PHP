<?php

//@TODO: not at all tested!
/**
* Run some php when the form is submitted
* @package Joomla
* @subpackage Fabrik
* @author Rob Clayburn
* @copyright (C) Rob Clayburn
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

//require the abstract plugin class
require_once( COM_FABRIK_FRONTEND.DS.'models'.DS.'plugin-form.php' );

class FabrikModelFabrikPHP extends FabrikModelFormPlugin {
 	
	var $_counter = null;
	
	/**
	* Constructor
	*/

	function __construct()
	{
		parent::__construct();
	}
	
 	
 	function onBeforeProcess( &$params, &$formModel )
 	{
 		if( $params->get('only_process_curl') == 'onBeforeProcess' ){
 			$this->_runPHP( $params, $formModel );
 		}
 		return true;
 	}
 	
 	function onBeforeStore( &$params, &$formModel )
 	{
 	 	if( $params->get('only_process_curl') == 'onBeforeStore' ){
 			$this->_runPHP( $params, $formModel );
 		}
 		return true;
 	}
 	
 	
 	function onBeforeCalculations( &$params, &$formModel )
 	{
 	 	if( $params->get('only_process_curl') == 'onBeforeCalculations' ){
 			$this->_runPHP( $params, $formModel );
 		}
 		return true;
 	}
 	
 	function onAfterProcess( &$params, &$formModel )
 	{
 	 	if( $params->get('only_process_curl') == 'onAfterProcess' ){
 			$this->_runPHP( $params, $formModel );
 		}
 		return true;
 	}
 	
 	function onLoad( &$params, &$formModel )
 	{
 	 	if( $params->get('only_process_curl') == 'onLoad' ){
 			$this->_runPHP( $params, $formModel );
 		}
 		return true;
 	}
 	
 	/**
 	 * process the plugin, called when form is submitted
 	 *
 	 * @param object $params
 	 * @param object form
 	 */

 	function onError( &$params, &$formModel )
 	{
 	 	if ($params->get('only_process_curl') == 'onError') {
 			$this->_runPHP( $params, $formModel );
 		}
 		return true;
	}
	
	function _runPHP( &$params, &$formModel )
	{
		$this->_data =& $formModel->_formData;
		//echo $params->get('form_php_file');exit;
		if ($params->get('form_php_file') == -1) {
			eval( $params->get('curl_code', '') );
		} else {
			
			// $$$ hugh - give them some way of getting at form data
			// (I'm never sure if $_REQUEST is 'safe', i.e. if it has post-validation data)
			global $fabrikFormData;
			// for some reason, =& wasn't working??
			$fabrikFormData = $this->_data;
			require_once(COM_FABRIK_FRONTEND.DS.'plugins'.DS.'form'.DS.'fabrikphp'.DS.'scripts'.DS.$params->get('form_php_file'));
		}
		
	}

}
?>