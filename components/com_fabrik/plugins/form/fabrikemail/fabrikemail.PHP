<?php
/**
 * Form email plugin
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

class FabrikModelfabrikEmail extends FabrikModelFormPlugin {

	/**
	 * @var array of files to attach to email
	 */
	var $_counter = null;

	var $_aAttachments = array();

	var $_dontEmailKeys = null;
	/**
	 * Constructor
	 */

	function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * determines if a condition has been set and decides if condition is matched
	 *
	 * @param object $params
	 * @return bol true if you sould send the email, false stops sending of eaml
	 */
	
	function shouldSend( &$params )
	{
		$post	= JRequest::get( 'post' );
		$condition = $params->get( 'email_conditon' );
		
		if ($condition == '') {
			return true;
		}
		$w = new FabrikWorker();
		$condition = trim( $w->parseMessageForPlaceHolder( $condition, $this->data ) );
		$res = @eval( $condition );
		if (is_null( $res )) {
			return true;
		}
		return $res;
	}

	/**
	 * process the plugin, called when form is submitted
	 *
	 * @param object $params
	 * @param object form model
	 * @returns bol
	 */

	function onAfterProcess( $params, &$formModel )
	{
		$user						= &JFactory::getUser();
		$config					=& JFactory::getConfig();
		
		$this->formModel =& $formModel;
		$formParams			= $formModel->getParams();
		$emailTemplate	= JPath::clean( JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'plugins'.DS.'form'.DS.'fabrikemail'.DS.'tmpl'.DS . $params->get( 'email_template', '' ) );
		
		//check if condition exists and is met
		if (!$this->shouldSend( $params )) {
			return;
		}
		
		//$this->data = $this->getEmailData();
		//getEmailData returns correctly formatted {tablename___elementname} keyed results
		//_formData is there for legacy and may allow you to use {elementname} only placeholders for simple forms
		$this->data 		= array_merge( $this->getEmailData(), $formModel->_formData );
		$htmlEmail = true; //always send as html as even text email can contain html from wysiwg editors
		if (JFile::exists( $emailTemplate )) {
			if (JFile::getExt( $emailTemplate ) == 'php') {
				$message = $this->_getPHPTemplateEmail( $emailTemplate );
			} else {
				$message = $this->_getTemplateEmail( $emailTemplate );
			}
		} else {
			$message = $this->_getTextEmail();
		}
		$this->addAttachments();

		$cc 		= null;
		$bcc 		= null;
		$w = new FabrikWorker();
		// $$$ hugh - test stripslashes(), should be safe enough.
		$message 	= stripslashes( $message );
		$message 	= $w->parseMessageForPlaceholder( $message, $this->data );
		$email_to 	= $w->parseMessageForPlaceholder( $params->get( 'email_to' ), $this->data );
		$email_from = $w->parseMessageForPlaceholder( $params->get( 'email_from' ), $this->data );
		$subject		= $params->get('email_subject');
		if ($subject == "") {
			$subject = $config->getValue( 'sitename' ) . " :: Email";
		}
		$subject = $w->parseMessageForPlaceholder( $subject, $this->data );
		$subject = preg_replace_callback( '/&#([0-9a-fx]+);/mi', array( $this, 'replace_num_entity' ), $subject );
		/* Send email*/
		$emails = explode( ',', $email_to );
		foreach ($emails as $email) {
			if (!empty( $email )) {
				$res = JUtility::sendMail( $email_from, $email_from, $email, $subject, $message, $htmlEmail, $cc, $bcc, $this->_aAttachments );
			}
		}
		return true;
	}

	/**
	 * use a php template for advanced email templates, partularly for forms with repeat group data
	 *
	 * @param bol if file uploads have been found
	 * @param string path to template
	 * @return string email message
	 */

	function _getPHPTemplateEmail( $tmpl )
	{
		// start capturing output into a buffer
		ob_start();
		require( $tmpl );
		$message = ob_get_contents();
		ob_end_clean();
		return  $message;
	}

	/**
	 * add attachments to the email
	 */

	function addAttachments()
	{
		//get attachments
		$pluginManager =& JModel::getInstance( 'Pluginmanager', 'FabrikModel' );
		$groups =& $this->formModel->getGroupsHiarachy();
		foreach ($groups as $groupModel) {
			$elementModels =& $groupModel->getPublishedElements();
			foreach ($elementModels as $elementModel) {
				$element =& $elementModel->getElement();
				$element->label = strip_tags( $element->label );
				if (!array_key_exists( $element->name, $this->data )) {
					$elName = $elementModel->getFullName( );
				} else {
					$elName =  $element->name;
				}
				$key = $elName;
				if (array_key_exists( $elName, $this->data )) {
					if (is_array( $this->data[$elName] )) {
						$this->data[$elName] = implode(",", $this->data[$elName]);
					}
					$val = stripslashes( $this->data[$elName] );
					if (method_exists( $elementModel, 'addEmailAttachement' )) {
						$file = $elementModel->addEmailAttachement( $val );
						if ($file !== false) {
							$this->_aAttachments[] = $file;
						}
					}
				}
			}
		}
	}

	/**
	 * get an array of keys we dont want to email to the user
	 *
	 * @return array
	 */

	function getDontEmailKeys()
	{
		if (is_null( $this->_dontEmailKeys )) {
			$this->_dontEmailKeys = array();
			foreach ($_FILES as $key => $file) {
				$this->_dontEmailKeys[] = $key;
			}
		}
		return $this->_dontEmailKeys;
	}
	/**
	 * template email handling routine, called if email template specified
	 * @param string path to template
	 * @return string email message
	 */

	function _getTemplateEmail( $emailTemplate )
	{
		$message 	= file_get_contents( $emailTemplate );
		return $message;
	}

	/**
	 * default email handling routine, called if no email template specified
	 * @return string email message
	 */

	function _getTextEmail()
	{
		$config =& JFactory::getConfig();
		$ignore = $this->getDontEmailKeys();
		$message = "";
		$pluginManager =& JModel::getInstance( 'Pluginmanager', 'FabrikModel' );
		$groupModels =& $this->formModel->getGroupsHiarachy();

		foreach ($groupModels as &$groupModel) {
			$elementModels = $groupModel->getPublishedElements();
			foreach ($elementModels as &$elementModel) {
				$element = $elementModel->getElement();
				$key = (!array_key_exists( $element->name, $this->data )) ? $elementModel->getFullName( true, true, false ) : $element->name;
				if (!in_array( $key, $ignore )) {
					$val = '';
					if (is_array( $this->data[$key] )) {
						//repeat group data
						foreach ($this->data[$key] as $k=>$v) {
							$val .= $k++ .": $v\r\n";
						}
					} else {
						$val = $this->data[$key];
					}
					$val = FabrikString::rtrimword($val, "<br />");
					$val = stripslashes( $val );
						

					// set $val to default value if empty
					if($val == '')
					$val = " - ";

					// don't add a second ":"
					$label = trim( strip_tags( $element->label ) );
					$message .= $label;
					if (strlen( $label ) != 0 && strpos( $label, ':', strlen( $label )-1 ) === false) {
						$message .=":";
					}
					$message .= "\r\n" . $val . "\r\n\r\n";
				}
			}
		}
		$message = JText::_('Email from') . ' ' . $config->getValue( 'sitename' ) . "\r\n".JText::_('Message').":"
		."\r\n===================================\r\n".
		"\r\n" . stripslashes($message);
		return $message;
	}
}
?>