<?php
/**
* @package Joomla
* @subpackage Fabrik
* @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport( 'joomla.application.component.controller' );

require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'params.php' );
require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'string.php' );
require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'html.php' );
require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'cache.php' );
//just until joomla uses mootools 1.2
jimport( 'joomla.html.editor' );
require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'editor.php' );
//end mootools 1.2

/**
 * Fabrik Plugin Controller
 *
 * @static
 * @package		Joomla
 * @subpackage	Fabrik
 * @since 1.5
 */
class FabrikControllerPlugin extends JController 
{
	/**
	 * ajax action called from element
	 */

	function pluginAjax()
	{
		$formid = JRequest::getInt( 'formid', 0 );
		$plugin = JRequest::getVar( 'plugin', '' );
		$method = JRequest::getVar( 'method', '' );
		$id = JRequest::getInt( 'element_id', 0 );
		$group = JRequest::getVar( 'g', 'element' );
		$pluginManager =& $this->getModel( 'pluginmanager' );
		$model =& $pluginManager->getPlugIn( $plugin, $group );
		$model->setId( $id );
		$model->_formId = $formid;
		if (method_exists( $model, $method )) {
			$model->$method();
		} else {
			echo "alert('method doesnt exist');\n";
		}
	}
 

}
?>