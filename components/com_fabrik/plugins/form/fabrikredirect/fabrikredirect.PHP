<?php

/**
* Redirect the user when the form is submitted
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
require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'parent.php' );

class FabrikModelFabrikRedirect extends FabrikModelFormPlugin {
 	
	/**
	* Constructor
	*/
	
	var $_result = true;
	
	var $_counter = null;

	function __construct()
	{
		parent::__construct();
	}
 	
 	/**
 	 * process the plugin, called afer form is submitted
 	 *
 	 * @param object $params (with thc current active plugin values in them)
 	 * @param object form
 	 */

 	function onLastProcess( $params, &$formModel )
	{
 		global $mainframe;
 		$w =& new FabrikWorker(); 
 		$this->_data = new stdClass();
 		$this->_data->jump_page 			= $w->parseMessageForPlaceHolder( $params->get('jump_page'), $formModel->_formData );
 		$this->_data->thanks_message 	= $w->parseMessageForPlaceHolder( $params->get( 'thanks_message' ) );
 		$this->_data->append_jump_url = $params->get( 'append_jump_url' );
 		$this->_data->save_in_session = $params->get( 'save_insession' );
 		$form =& $formModel->getForm();
 		$this->_storeInSession( $formModel );
 		if ($this->_data->jump_page != '') {
 			$this->_data->jump_page = $this->_buildJumpPage( $formModel );
			if (JRequest::getVar('fabrik_postMethod', '') != 'ajax') {
				//dont redirect if form is in part of a package 
				$mainframe->redirect( $this->_data->jump_page, $this->_data->thanks_message );
			}
		} else {
			//stop joomla redirecting
			$this->_result = false;
			$this->displayThanks( $form->label, $this->_data->thanks_message );
		}
		return $this->_result;
	}
	
	/**
	 * once the form has been sucessfully completed, and if no jump page is
	 * specified then show the thanks message
	 * @param string thanks message title
	 * @param string thanks message string
	 */
	 
	function displayThanks( $title, $message )
	{
		require_once( JPATH_SITE . "/includes/HTML_toolbar.php" );
		?>
		<div class="componentheading"><?php echo $title ?></div>
		<p><?php echo $message ?></p>
		<?php
	}
	
	/**
	 * alter the returned plugin manager's result
	 *
	 * @param string $method
	 * @return bol
	 */
	
	
	function customProcessResult( $method )
	{
		if( $method != 'onLastProcess') {
			return true;
		}
		if (JRequest::getVar( 'fabrik_postMethod', '' ) != 'ajax') {
			//return false to stop the default redirect occuring
			return false;
		} else {
			return true;
		}
	}
													
	/**
	 * takes the forms data and merges it with the jump page
	 * @param object form
	 * @return new jump page
	 */

	function _buildJumpPage( &$formModel )
	{
		global $Itemid;
		
		$jumpPage = $this->_data->jump_page;
		$reserved = array( 'format','view','layout','task' );
		$queryvars = array();
		if ($this->_data->append_jump_url == '1') {
			$groups =& $formModel->getGroupsHiarachy();
			foreach ($groups as $group) {
				$elements =& $group->getPublishedElements();
				if ($group->isJoin()){
					$tmpData = $formModel->_fullFormData['join'][$group->getGroup()->join_id];
				} else {
					$tmpData = $formModel->_fullFormData;
				}
				foreach ($elements as $elementModel) {
					
					$name =  $elementModel->getFullName(false);

					if (array_key_exists( $name, $tmpData )) {
						//$queryvars[] = $name . "=" . $tmpData[$name];
						$this->_appendQS( $queryvars, $name, $tmpData[$name] );
					} else {
						$element =& $elementModel->getElement();
						if (array_key_exists( $element->name, $tmpData )) {
							//$queryvars[] = $element->name . "=" . $tmpData[$element->name];
							$this->_appendQS( $queryvars, $element->name, $tmpData[$element->name] );
						}
					}
				}
			}
		}
		$fromModule 		 = JRequest::getBool( 'fabrik_frommodule', 0 );
		if ($fromModule == '1') {
			$queryvars[] =  "frommodule=1";
		}
		if ( (!strstr( $jumpPage, COM_FABRIK_LIVESITE ) && strstr($jumpPage, 'http')) || empty($queryvars) ){
			return $jumpPage;
		}
		if (!strstr( $jumpPage, "?" ) ) {
			$jumpPage .= "?";
		}
		if (!strstr( $jumpPage, "&Itemid=" )) {
			/* if the jump url contains an item id we shouldnt add the forms item id to it*/
			$queryvars[] = "&Itemid=$Itemid";
		}
		$jumpPage .= implode('&', $queryvars);
		return $jumpPage; 
		//return JRoute::_( $jumpPage );
	}
	
	function _appendQS( &$queryvars, $key, $val )
	{
		if (is_array( $val )) {
			foreach ($val as $v) {
				$this->_appendQS( $queryvars, "{$key}[]", $v );
			}
		} else {
			$queryvars[] = "$key=$val";
		}
	}

	function _storeInSession( &$formModel )
	{
		$session 	=& JFactory::getSession();
		$store = $session->get('fabrik');
		if (is_null( $store )) {
			$store = array();
		}
		if (array_key_exists( 'fabrik', $_SESSION )) {
			if (array_key_exists( $formModel->_id,  $_SESSION["fabrik"] )) {
				//unset( $_SESSION["fabrik"][$formModel->_id] );
			}
		}
		if ($this->_data->save_in_session == '1') {
			//@TODO - rob, you need to look at this, I really only put this in as a band-aid.
			// $$$ hugh - we need to guesstimate the 'type', otherwise when the session data is processed
			// on table load as filters, everything will default to 'field', which borks up if (say) it's
			// really a dropdown
			/*
			foreach ($formModel->_formData as $key => $value) {
				if ($formModel->hasElement( $key )) {
					//$value = urlencode( stripslashes( $value ) );
					$store[$formModel->_id]["$key"] = array('type'=>'', 'value'=>$value, 'match'=>false);
				}
			}
			*/
			
			$groups =& $formModel->getGroupsHiarachy();
			foreach ($groups as $group) {
				$elements =& $group->getPublishedElements();
				foreach ($elements as $element) {
					
					if ($group->isJoin()){
						$tmpData = $formModel->_fullFormData['join'][$group->getGroup()->join_id];
					} else {
						$tmpData = $formModel->_fullFormData;
					}
					$name =  $element->getFullName(false);
					if (array_key_exists( $name, $tmpData )) {
						$value = $tmpData[$name];
						switch ($element->_pluginName) {
							case 'dropdown':
							case 'databasejoin':
								$type = 'dropdown';
								break;
							default:
								$type = 'field';
								break;
						}
						$store[$formModel->_id]["$name"] = array('type'=>$type, 'value'=>$value, 'match'=>false);
					}
				}
			}
			$store['fromForm'] = $formModel->_id;
		}
		//$session->set('fabrik', $store);
		$_SESSION['fabrik'] = $store;
		
	}
}
?>