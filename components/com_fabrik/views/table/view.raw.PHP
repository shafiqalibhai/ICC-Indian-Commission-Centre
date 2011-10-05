<?php
/**
* @package Joomla
* @subpackage Fabrik
* @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.view');
require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'json.php');

class FabrikViewTable extends JView{
	
	var $_oTable 	= null;
	
	function FabrikViewTable( &$oTable )
	{
		$this->_oTable = $oTable;
	}
	
	/**
	 * display a json object representing the table data.
	 */

	function display()
	{
		$model			= &$this->getModel();
		$model->setId( JRequest::getInt('tableid') );
		$table 			=& $model->getTable();
		$params 		=& $model->getParams();
		$model->render( );
		$this->emptyDataMessage = $params->get( 'empty_data_msg' );
		$rowid = JRequest::getInt( 'rowid' );
		$headings = $model->getHeadings();
		$data =& $model->getData();
		// $$$ hugh - heading[3] doesn't exist any more?  Trying [0] instead.
		$d = array('id' => $table->id, 'rowid' => $rowid, 'model'=>'table', 'data'=>$data,
		'headings' => $headings[0] );
		echo FastJSON::encode( $d );	  	
	}
	
	//get a given row back 
	function displayRow()
	{
		
	}
	
}

?>