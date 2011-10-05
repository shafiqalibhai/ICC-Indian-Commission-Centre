<?php

/**
* A cron task to email records to a give set of users
* @package Joomla
* @subpackage Fabrik
* @author Rob Clayburn
* @copyright (C) Rob Clayburn
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

//require the abstract plugin class
require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'parent.php' );
require_once( COM_FABRIK_FRONTEND.DS.'models'.DS.'plugin.php' );
require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'html.php' );


class FabrikModelCronemail extends fabrikModelPlugin {
 	
	var $_counter = null;
	
	/**
	* Constructor
	*/

	function __construct()
	{
		parent::__construct();
	}
	

	function canUse()
	{
		return true;
	}
	
	/**
	 * do the plugin action
	 * @return number of records updated
	 */
	
	function process(&$data)
	{
		global $mainframe;
		jimport('joomla.mail.helper');
		$params =& $this->getParams();
		$msg = $params->get('message');
		$to = $params->get('to');
		$w =& new FabrikWorker();
		$MailFrom	= $mainframe->getCfg('mailfrom');
		$FromName	= $mainframe->getCfg('fromname');
		$subject = $params->get('subject', 'Fabrik cron job');
		$eval = $params->get('cronemail-eval');
		$updates = array();
		foreach ($data as $group) {
			if (is_array($group)) {
				foreach ($group as $row) {
					$row = JArrayHelper::fromObject($row);
					$thisto = $w->parseMessageForPlaceHolder( $to, $row );
					if (JMailHelper::isEmailAddress($thisto)) {
						$thismsg = $w->parseMessageForPlaceHolder( $msg, $row ); 
						if ($eval) {
							$thismsg = eval( $thismsg );
						}
						//echo $thismsg;exit;
						$thissubject = $w->parseMessageForPlaceHolder( $subject, $row );
						$res = JUTility::sendMail( $MailFrom, $FromName, $thisto, $thissubject, $thismsg, true );
					}
					$updates[] = $row['__pk_val'];
					
				}
			}
		}
		$field = $params->get('cronemail-updatefield');
		if (!empty( $updates ) && trim( $field ) != '') {
			//do any update found
			$tableModel =& JModel::getInstance( 'table', 'FabrikModel' );
			$tableModel->setId( $params->get('table'));
			$table =& $tableModel->getTable();
				
			$connection = $params->get('connection');
			$field = $params->get('cronemail-updatefield');
			$value = $params->get('cronemail-updatefield-value');
			
			$field = str_replace("___", ".", $field);
			$query = "UPDATE $table->db_table_name set $field = '$value' WHERE $table->db_primary_key IN (" . implode(',', $updates) . ")";
			$fabrikDb =& $tableModel->getDb();
			$fabrikDb->setQuery($query);
			$fabrikDb->query();
		}
		return count($updates);
	}
	
	/**
	 * show a new for entering the form actions options 
	 */
 	
	function renderAdminSettings()
	{
		//JHTML::stylesheet( 'fabrikadmin.css', 'administrator/components/com_fabrik/views/' );
		$this->getRow();
		$pluginParams =& $this->getParams();
		
		$document =& JFactory::getDocument( );
		?>
		<div id="page-<?php echo $this->_name;?>" class="pluginSettings" style="display:none">
		<?php
			echo $pluginParams->render( 'params' );
			echo $pluginParams->render( 'params', 'fields');
			?>
			<fieldset>
				<legend><?php echo JText::_('update') ?></legend>
				<?php echo $pluginParams->render( 'params','update' ) ?>
			</fieldset>
		</div>		
		
		<?php
		return ;
	}
	
}
?>