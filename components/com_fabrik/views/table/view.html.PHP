<?php

/**
 * @package		Joomla
 * @subpackage	Fabik
 * @copyright	Copyright (C) 2005 - 2008 Pollen 8 Design Ltd. All rights reserved.
 * @license		GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.view');

class FabrikViewTable extends JView{

	var $_data 			= null;
	var $_aLinkElements = null;
	var $_id 				= null;
	var $_isMambot 	= null;

	function setId( $id )
	{
		$this->_id = $id;
	}

	function getManagementJS( $data = array() )
	{
		global $Itemid;
		$model =& $this->getModel();
		$table =& $model->getTable();
		$formModel =& $model->getForm();
		$elementsNotInTable =& $formModel->getElementsNotInTable();
		$keys = array('id'=>'', 'name'=>'', 'label'=>'');
		foreach ($elementsNotInTable as &$i) {
			$i = array_intersect_key( $i->getPublicProperties(), $keys );
		}
		FabrikHelperHTML::packageJS();
		$document =& JFactory::getDocument();
		if ($model->requiresSlimbox()) { 
			FabrikHelperHTML::slimbox();
		}
		if ($model->requiresMocha()) {
			FabrikHelperHTML::mocha();
		}
		FabrikHelperHTML::script( 'table.js', 'components/com_fabrik/views/table/', true );
		$tmpl = JRequest::getVar( 'layout', $table->template );

		// check for a custom css file and include it if it exists
		$ab_css_file = JPATH_SITE.DS."components".DS."com_fabrik".DS."views".DS."table".DS."tmpl".DS.$tmpl.DS."template.css";
		if (file_exists( $ab_css_file )) {
			JHTML::stylesheet( 'template.css', 'components/com_fabrik/views/table/tmpl/'.$tmpl . '/' );
		}
		
		JHTML::stylesheet( 'table.css', 'media/com_fabrik/css/' );

		// check for a custom js file and include it if it exists
		$aJsPath = JPATH_SITE.DS."components".DS."com_fabrik".DS."views".DS."table".DS."tmpl".DS.$tmpl.DS."javascript.js";
		if (file_exists( $aJsPath )) {
			FabrikHelperHTML::script( "javascript.js", 'components/com_fabrik/views/table/tmpl/'.$tmpl . '/', true );
		}
			
		$origRows 	= $this->rows;
		$this->rows = array(array());
			
		$tmpItemid = ( !isset($Itemid)) ?  0 : $Itemid;

		$this->_c = 0;
		$this->_row = new stdClass();
			
		$fbConfig =& JComponentHelper::getParams( 'com_fabrik' );
		
		$script = "/* <![CDATA[ */ \n";

		static $tableini;
		if (!$tableini) {
			$tableini = true;
			$script .= "var oTables = \$H();\n";
		}

		$opts 						= new stdClass();
		$opts->admin 			= $model->_admin;
		$opts->postMethod 	= $model->getPostMethod();
		$opts->filterMethod = $this->filter_action;
		$opts->form 			= 'tableform_' . $model->_id;
		$opts->headings 	= $model->_jsonHeadings();
		$opts->orderMap		= $this->orderMap;
		$opts->labels 		= $this->headings;
		$opts->primaryKey = $table->db_primary_key;
		$opts->data 			= $data;
		$opts->Itemid 		= $tmpItemid;
		$opts->formid 		= $model->_oForm->_id;
		$opts->canEdit 		= ( $model->canEdit() ) ? "1" : "0";
		$opts->canView 		= ( $model->canView() ) ? "1" : "0";
		$opts->page 			= JRoute::_('index.php');
		$opts->mooversion	= ($fbConfig->get( 'usefabrik_mootools', false )) ? 1.2 : 1.1;
		$opts->formels		= $elementsNotInTable;
		$opts 						= FastJSON::encode( $opts );
		
		$lang 							= new stdClass();
		$lang->select_rows 	= JText::_( 'SELECT SOME ROWS FOR DELETION' );
		$lang->yes 					= JText::_( 'Yes' );
		$lang->no 					= JText::_( 'No' );
		$lang = FastJSON::encode( $lang );
		
		
		$script .= "\n" . "var oTable = new fabrikTable($model->_id,";
		$script .= $opts.",".$lang;
		$script .= "\n" . ");";
		$script .= "\n" . "oTable.addListenTo('form_{$model->_oForm->_id}');";
		$script .= "\n" . "oTable.addListenTo('table_{$model->_id}');";
		$script .= "\n" . "oPackage.addBlock('table_{$model->_id}', oTable);";

		//add in plugin objects
		$params =& $model->getParams();
		$activePlugins = $params->get( 'plugin', array(), '_default',  'array' );
		$pluginManager =& $model->getPluginManager();
		$plugins =& $pluginManager->getPlugInGroup( 'table' );
		$aObjs = array();
		// $$$ hugh - think we need to do this the other way round, looping thru $activePlugins
		// otherwise we can only have one instance of each plugin
		/*
		foreach ($plugins as $name => $plugin) {
			if (in_array( $name, $activePlugins)) {
				$aObjs[] = $plugin->loadJavascriptInstance( "tableform_{$model->_id}" );
			}
		}
		*/
		$c = 0;
		foreach ($activePlugins as $name) {
			if (array_key_exists( $name, $plugins)) {
			  $plugins[$name]->renderOrder = $c;
				$aObjs[] = $plugins[$name]->loadJavascriptInstance( "tableform_{$model->_id}" );
				$c ++;
			}
		}
		$script .= "\noTable.addPlugins([\n";
		$script .= "  " . implode(",\n  ", $aObjs);
		$script .= "]\n);\n";

		$script .= "oTables.set($model->_id, oTable);\n";
		$script .= "/* ]]> */";
		$document->addScriptDeclaration( $script );
		//reset data back to original settings
		$this->rows = $origRows;
	}

	/**
	 * display the template
	 *
	 * @param sting $tpl
	 */

	function display( $tpl = null )
	{
		global $Itemid, $mainframe;
		require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'parent.php' );
		require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'json.php' );
		require_once( COM_FABRIK_FRONTEND.DS.'views'.DS.'modifiers.php' );
		$user 		=& JFactory::getUser();
		$model		=& $this->getModel();
		if ($mainframe->_name == 'administrator') {
			$model->_admin = true;
		}

		$document =& JFactory::getDocument();

		//this gets the component settings
		$usersConfig = &JComponentHelper::getParams( 'com_fabrik' );
		if (!isset( $this->_id )) {
			if ($model->_admin) {
				$tpl = "admin";
			} else {
				$model->setId( JRequest::getVar( 'tableid', $usersConfig->get( 'tableid' ) ) );
			}
		} else {
			//when in a package the id is set from the package view
			$model->setId( $this->_id );
		}
		$table			=& $model->getTable();
		$model->getPostMethod();
		$model->render();

		$w = new FabrikWorker();
		if (!$this->_isMambot) {
			$document->setTitle( $w->parseMessageForPlaceHolder( $table->label, $_REQUEST ) );
		}
		$data =& $model->getData();
		 
		//add in some styling short cuts
		$c 		= 0;
		$form =& $model->getForm();
		$nav 	=& $model->getPagination();
		foreach ($data as $groupk => $group) {
			$last_pk = '';
			$last_i = 0;
			for ($i=0; $i<count($group); $i++) {
				$o = new stdClass();
				$fbConfig =& JComponentHelper::getParams( 'com_fabrik' );
				//@TODO: rob - Hugh can you describe what you're doing here? - its not too clear to us preg noobs
				// it's code that collapses 'repeated joined' rows into a single row.  So when a row repeats (i.e.
				// if the PK is the same), it takes just the columns where the data has changed, and appends that
				// to that column in the 'first' row (i.e. the first one in this repeat run), and skips printing
				// the repeated row.  Far from ideal, as it hoses up pagination, but works if you don't need pagination.
				if ($fbConfig->get('use_wip', false)) {
					if (!empty($last_pk) && ($last_pk == $data[$groupk][$i]->__pk_val)) {
						foreach ($data[$groupk][$i] as $key => $val) {
							if (preg_match("#(^_|^fabrik_|_raw$)#", $key)) {
								continue;
							}
							if (isset($data[$groupk][$last_i]->data->$key)) {
								if ($data[$groupk][$last_i]->data->$key != $val) {
									$pk_col = FabrikString::safeColNameToArrayKey($model->_table->db_primary_key);
									$tableid = $table->id;
									if (preg_match("#\W+fabrik___rowlink.*\W+tableid=$tableid\W#",$val)) {
										continue;
									}
									$data[$groupk][$last_i]->data->$key .= "<br >\n" . $val;
								}
							}
						}
						unset($data[$groupk][$i]);
						continue;
					}
					else {
						$last_i = $i;
						$last_pk = $data[$groupk][$i]->__pk_val;
					}
				}
				$o->data = $data[$groupk][$i];
				$o->cursor = $i + $nav->limitstart;
				$o->total = $nav->total;
				$o->id = "table_".$table->id."_row_".@$o->data->__pk_val;
				$o->class = "fabrik_row oddRow".$c;
				$data[$groupk][$i] = $o;
				$c = 1-$c;
			}
		}
		$groups =& $form->getGroupsHiarachy();
		foreach ($groups as $groupModel) {
			$elementModels =& $groupModel->getPublishedElements();
			foreach ($elementModels as $elementModel) {
				$e =& $elementModel->getElement();
				$elementModel->setContext( $groupModel, $form, $model );
				$elparams =& $elementModel->getParams();
				$col 	= $elementModel->getFullName( false, true, false );
				$col .= "_raw";
				$rowclass = $elparams->get( 'use_as_row_class' );
				if ($rowclass == 1) {
					foreach ($data as $groupk => $group) {
						for ($i=0; $i<count( $group ); $i++) {
							$data[$groupk][$i]->class .= " ". preg_replace('/[^A-Z|a-z|0-9]/', '-', $data[$groupk][$i]->data->$col);
						}
					}
				}
			}
		}
		$this->rows =& $data;
		reset($this->rows);
		$firstRow = current($this->rows); //cant use numeric key '0' as group by uses groupd name as key
		$this->assign( 'requiredFiltersFound', $this->get('RequiredFiltersFound') );
		$this->nodata = (empty($this->rows) || (count( $this->rows ) == 1 && empty( $firstRow )) || !$this->requiredFiltersFound) ? true : false;
		$params 		=& $model->getParams();

		if (!$model->canPublish()) {
			echo JText::_( 'SORRY THIS TABLE IS NOT PUBLISHED' );
			return false;
		}

		if (!$model->canView()) {
			echo JText::_( 'ALERTNOTAUTH' );
			return false;
		}
		
		
		$this->table 					= new stdClass();
		$this->table->label 	= $w->parseMessageForPlaceHolder( $table->label, $_REQUEST );
		$this->table->intro 	= $table->introduction;
		$this->table->id			= $table->id;
		$this->group_by				= $table->group_by;
		$this->formid = 'tableform_' . $table->id ;
		$page =  ( $model->getPostMethod() == 'ajax' ) ? "index.php?format=raw" : "index.php?";
		$this->table->action 	=  $page . str_replace('&', '&amp;', $_SERVER['QUERY_STRING']);

		if ($model->getPostMethod() == 'ajax') {
			$this->table->action .= '&format=raw';
			$this->table->action = str_replace("task=package", "task=viewTable", $this->table->action);
		}
		$this->table->action 	= JRoute::_( $this->table->action );

		$this->showCSV 				= $model->canCSVExport();
		$this->showCSVImport	= $model->canCSVImport();
		$this->nav 						= $params->get( 'show-table-nav', 1) ? $nav->getListFooter( $model->_id ) : '';
		$this->fabrik_userid 	= $user->get( 'id' );
		$this->canDelete 			= $model->canDelete() ? true : false;
		$jsdelete =  "oPackage.submitfabrikTable( $table->id, 'delete')";
		$this->deleteButton 	= $model->canDelete() ?  "<input class='button' type='button' onclick=\"$jsdelete\" value='" . JText::_('DELETE') . "' name='delete'/>" : '';

		$this->showPDF = $params->get( 'pdf', 0 );
		if ($this->showPDF) {
			$this->pdfLink 			= FabrikHelperHTML::pdfIcon( $model, $params );
		}
		$this->emptyButton 		= ($model->canEmpty())? "<input class='button' type='button' value='" . JText::_('EMPTY') . "' name='doempty'/>" : "";
		$this->csvImportLink 	= ( $this->showCSVImport ) ? JRoute::_( "index.php?option=com_fabrik&c=import&view=import&filetype=csv&tableid=" . $table->id ) : '';
		$this->showAdd 				= $model->canAdd();
		if ($this->showAdd) {
			$qs = '';
			foreach ($model->filters as $key=>$f) {
				if ($f['isJoinElement']) {
						$key .= "_raw";
				}
				$qs .= "&{$key}={$f['value']}";
			}
			if ($model->_admin) {
				$this->addRecordLink = ( $model->getPostMethod() == 'ajax' ) ? "#" : JRoute::_( "index.php?option=com_fabrik&c=form&task=form&fabrik=" . $table->form_id . "&tableid=" . $model->_id ."&rowid=$qs");
			} else {
				$this->addRecordLink = ( $model->getPostMethod() == 'ajax' ) ? "#" : JRoute::_( "index.php?option=com_fabrik&c=form&view=form&Itemid=$Itemid&fabrik=" . $table->form_id . "&tableid=" . $model->_id ."&rowid=$qs");
			}
		}
		$this->addRecordId = "table_" . $model->_id . "_addRecord";
		$this->showRSS = $model->_params->get('rss', 0) == 0 ?  0 : 1;

		if ($this->showRSS) {
			$this->rssLink = $model->getRSSFeedLink();
			if ($this->rssLink != '') {
				$attribs = array('type' => 'application/rss+xml', 'title' => 'RSS 2.0');
				$document->addHeadLink( $this->rssLink, 'alternate', 'rel', $attribs );
			}
		}
		list( $this->headings, $this->orderMap, $groupHeadings ) = $model->getHeadings();

		$this->filter_action = $model->getFilterAction();
		$modelFilters = $model->makeFilters();
		$form =& $model->getForm();
		$form->getGroupsHiarachy();
		$this->assign( 'showFilters', (count($modelFilters) ) > 0 ?  1 : 0 );
		$this->filters = array();
		
		foreach ($modelFilters as $name => $filter) {
			$f 					= new stdClass();
			$f->label 	= $name;
			$f->element = $filter->filter;
			$f->required = array_key_exists('required', $filter) ? $filter->required : '';
			$this->filters[$filter->name] 	= $f;
		}
		$msg = (!$this->requiredFiltersFound) ? JText::_('Please select all required filters') : $model->_params->get( 'empty_data_msg' );
		$this->assign( 'emptyDataMessage', $msg );

		$this->assignRef( 'groupheadings', $groupHeadings );
		$this->assignRef( 'calculations', $this->_getCalculations( $this->headings ) );

		$this->assign( 'isGrouped', $table->group_by );
		$this->assign( 'colCount', count($this->headings) );
		$this->assignRef( 'grouptemplates', $model->grouptemplates );
		$this->_loadTemplateBottom();

		$this->getManagementJS( $this->rows );

		// get dropdown list of other tables for quick nav in admin
		$this->tablePicker = ($model->_admin) ? FabrikHelperHTML::tableList( $this->table->id ) : '';

		$this->pluginButtons = $model->getPluginButtons();

		//force front end templates
		$this->_basePath = COM_FABRIK_FRONTEND . DS . 'views' ;
		if ($model->_admin) {
			$tmpl = $params->get( 'admin_template' );
			if ($tmpl == -1 || $tmpl == '') {
				$tmpl = JRequest::getVar( 'layout', $table->template );
			}
		} else {
			$tmpl = JRequest::getVar( 'layout', $table->template );
		}

		$this->_setPath( 'template', $this->_basePath.DS.$this->_name.DS.'tmpl'.DS.$tmpl );
		//ensure we don't have an incorrect version of mootools loaded
		
		if ($this->_isMambot) {
			$res = $this->loadTemplate();
			if (JError::isError( $res )) {
				print_r( JError::getErrors() );
			} else {
				return $res;
			}
		} else {
			parent::display();
		}
		FabrikHelperHTML::cleanMootools();
	}

	/**
	 *
	 */

	function _getCalculations( $aCols )
	{
		$aData = array();
		$found = false;
		$model = $this->getModel();
		foreach ( $aCols as $key=>$val) {
			$calc = '';
			$res = '';
			$oCalcs = new stdClass();
			$oCalcs->grouped = array();
			
			if (array_key_exists( $key, $model->_aRunCalculations['sums'] )) {
				$found = true;
				$res = $model->_aRunCalculations['sums'][$key];
				$calc .= JText::_('SUM') . ": " . $res . "<br />";
				$tmpKey = str_replace(".", "___", $key) . "_calc_sum";
				$oCalcs->$tmpKey = $res;
			}
			if (array_key_exists( $key . '_obj', $model->_aRunCalculations['sums'] )) {
				$found = true;
				$res = $model->_aRunCalculations['sums'][$key. '_obj'];
				foreach ($res as $k=>$v) {
					if($k != 'calc'){
							@$oCalcs->grouped[$k] .= JText::_('SUM') . ": " . $v->value . "<br />";
					}
				}
			}

			if (array_key_exists( $key, $model->_aRunCalculations['avgs'] )) {
				$found = true;
				$res = $model->_aRunCalculations['avgs'][$key];
				$calc .= JText::_('AVERAGE') . ": " . $res . "<br />";
				$tmpKey = str_replace(".", "___", $key) . "_calc_average";
				$oCalcs->$tmpKey = $res;
			}
			
			if (array_key_exists( $key . '_obj', $model->_aRunCalculations['avgs'] )) {
				$found = true;
				$res = $model->_aRunCalculations['avgs'][$key. '_obj'];
				foreach ($res as $k=>$v) {
					if($k != 'calc'){
							@$oCalcs->grouped[$k] .=  JText::_('AVERAGE') . ": " . $v->value . "<br />";
					}
				}
			}
			
			if (array_key_exists( $key. '_obj', $model->_aRunCalculations['medians'] )) {
				$found = true;
				$res = $model->_aRunCalculations['medians'][$key. '_obj'];
				foreach ($res as $k=>$v) {
					if($k != 'calc'){
							@$oCalcs->grouped[$k] .=  JText::_('MEDIAN') . ": " . $v->value . "<br />";
					}
				}
			}

			if (array_key_exists( $key, $model->_aRunCalculations['medians'] )) {
				$found = true;
				$res = $model->_aRunCalculations['medians'][$key];
				$calc .= JText::_('MEDIAN') . ": " . $res . "<br />";
				$tmpKey = str_replace(".", "___", $key) . "_calc_median";
				$oCalcs->$tmpKey = $res;
			}
			
			if (array_key_exists( $key. '_obj', $model->_aRunCalculations['count'] )) {
				$found = true;
				$res = $model->_aRunCalculations['count'][$key. '_obj'];
				foreach ($res as $k=>$v) {
					if ($k != 'calc') {
							@$oCalcs->grouped[$k] .=  JText::_('COUNT') . ": " . $v->value . "<br />";
					}
				}
			}
			
			if (array_key_exists( $key, $model->_aRunCalculations['count'] )) {
				$res = $model->_aRunCalculations['count'][$key];
				$calc .= JText::_('COUNT') . ": " . $res . "<br />";
				$tmpKey = str_replace( ".", "___", $key ) . "_calc_count";
				$oCalcs->$tmpKey = $res;
				$found = true;
			}
			$key = str_replace( ".", "___", $key );
			$oCalcs->calc = $calc;
			$aData[$key] = $oCalcs;
		}
		$this->assign( 'hasCalculations', $found );
		return $aData;
	}

	/**
	 *
	 */

	function _loadTemplateBottom()
	{
		global $Itemid, $_SERVER;
		$model =& $this->getModel();
		$table =& $model->getTable();
		$reffer = '';
		if (array_key_exists( 'REQUEST_URI', $_SERVER ) ){
			$reffer = $_SERVER['REQUEST_URI'];
		}
		$this->hiddenFields  = "
		<input type='hidden' name='option' value='".JRequest::getVar('option', 'com_fabrik'). "' id = 'table_".$table->id."_option' />\n
		<input type='hidden' name='orderdir' value='' id ='table_".$table->id."_orderdir' />\n
		<input type='hidden' name='orderby' value='' id = 'table_".$table->id."_orderby' />\n";
		if (!$this->_isMambot) {
		  $this->hiddenFields .= "<input type='hidden' name='controller' value='table' />\n
		  <input type='hidden' name='view' value='table' id = 'table_".$table->id."_view' />\n";
		}
		$this->hiddenFields .="
		<input type='hidden' name='tableid' value='" . $model->_id . "' id = 'table_".$table->id."_tableid' />\n
		<input type='hidden' name='Itemid' value='" . $Itemid . "' id = 'table_".$table->id."_Itemid' />\n";
		//removed in favour of using table_{id}_limit dorop down box

		$this->hiddenFields .= "	<input type='hidden' name='fabrik_referrer' value='" . $reffer . "' id='fabrik_referrer' />\n";
		$this->hiddenFields 	.= JHTML::_( 'form.token' );

		$this->hiddenFields  .= ""
		."<input type='hidden' name='pageURL' value='" . str_replace('&', '&amp;', $_SERVER['QUERY_STRING']) . "' id = 'table_".$table->id."_pageURL' />\n"
		."<input type='hidden' name='format' id='table_".$table->id."_format' value='html' />";
		$packageId = JRequest::getInt( '_packageId', 0 );
		$this->hiddenFields  .= "
		<input type='hidden' name='_packageId' value='$packageId' id='table_".$table->id."_packageId' />\n";
		if ($model->_admin) {
			$this->hiddenFields  .=  "<input type='hidden' name='c' value='table' />";
			$this->hiddenFields  .= "<input type='hidden' name='task' value='viewTable' id = 'task' />\n";
		} else {
			$this->hiddenFields  .= "<input type='hidden' name='task' value='' id = 'task' />\n";
		}
		$this->hiddenFields  .= "<input type='hidden' name='fabrik_tableplugin_name' value='' />\n";
		$this->hiddenFields  .= "<input type='hidden' name='fabrik_tableplugin_renderOrder' value='' />\n";
		
		// $$$ hugh - added this so plugins have somewhere to stuff any random data they need during submit
		$this->hiddenFields  .= "<input type='hidden' name='fabrik_tableplugin_options' value='' />\n";
	}
}
?>