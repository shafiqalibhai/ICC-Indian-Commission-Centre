<?php
/**
* Send a receipt
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

class FabrikModelFabrikReceipt extends FabrikModelFormPlugin {
 	
	var $_counter = null;
	
	var $html = null;
	/**
	* Constructor
	*/

	function __construct()
	{
		parent::__construct();
	}

/**
 * set up the html to be injected into the bottom of the form
 *
 * @param object $params (no repeat counter stuff needed here as the plugin manager 
 * which calls this function has already done the work for you
 */
 	
 	function getBottomContent( &$params )
 	{
 		if($params->get('ask-receipt')){
 			$this->html = "
			<label><input type=\"checkbox\" name=\"fabrik_email_copy\" class=\"contact_email_copy\" value=\"1\"  />
			 ".JText::_('Email me a copy') . "</label>";
 		}else{
 			$this->html = '';
 		}
 	}

 	 /**
 	 * inject custom html into the bottom of the form
 	 * @param int plugin counter
 	 * @return string html
 	 */
 	
 	function getBottomContent_result( $c )
 	{
 		return $this->html;
 	}
 	
 	/**
 	 * process the plugin, called when form is submitted
 	 *
 	 * @param object $params
 	 * @param object form
 	 */
 	
 	function onAfterProcess( $params, &$formModel )
	{
		if ($params->get( 'ask-receipt' )) {
			$post = JRequest::get( 'post' );
			if (!array_key_exists( 'fabrik_email_copy', $post )) {
				return ;
			}
		}
		$config =& JFactory::getConfig();
		$w = new FabrikWorker(); 
		$message = $w->parseMessageForPlaceHolder( $params->get('receipt_message') );
		$this->formModel =& $formModel;
		$form =& $formModel->getForm();
		
		//getEmailData returns correctly formatted {tablename___elementname} keyed results
		//_formData is there for legacy and may allow you to use {elementname} only placeholders for simple forms
		$aData 		= array_merge( $this->getEmailData(), $formModel->_formData );
		
		$to = $w->parseMessageForPlaceHolder( $params->get('receipt_to'), $aData );
		$receipt_email = $params->get('receipt_to');
		if (!$form->record_in_database) {
			foreach ($aData as $key=>$val) {
				$aBits = explode('___', $key);
				$newKey = array_pop( $aBits );
				if ($newKey == $receipt_email) {
					$email = $val;
				}
			}
		}
		
		$subject =  html_entity_decode( $params->get( 'receipt_subject', '' ) );
		$subject = $w->parseMessageForPlaceHolder( $subject );
		$from 		= $config->getValue( 'mailfrom' );
		$fromname = $config->getValue( 'fromname' );
		JUTility::sendMail( $from, $fromname, $to, $subject, $message, true );
	}
}
?>