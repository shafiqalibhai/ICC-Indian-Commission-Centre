<?php
/**
 * @package		Joomla
 * @subpackage	Fabrik
 * @license		GNU/GPL, see LICENSE.php
 */

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

class FabrikViewForm {
	
	/**
	 * set up the menu when viewing the list of forms
	 */

	function setFormsToolbar()
	{
		JToolBarHelper::publishList();
		JToolBarHelper::unpublishList();
		JToolBarHelper::title( JText::_( 'FORMS' ), 'fabrik-form.png' );
		JToolBarHelper::customX( 'copy', 'copy.png', 'copy_f2.png', 'Copy' );
		JToolBarHelper::deleteList( );
		JToolBarHelper::editListX( );
		JToolBarHelper::addNewX( );
	}
	
	/**
	 * set up the menu when editing the form 
	 */

	function setFormToolbar()
	{
		$task = JRequest::getVar( 'task', '', 'method', 'string');
		JToolBarHelper::title( $task == 'addForm' ? JText::_( 'FORM' ) . ': <small><small>[ '. JText::_( 'NEW' ) .' ]</small></small>' : JText::_( 'FORM' ) . ': <small><small>[ '. JText::_( 'EDIT' ) .' ]</small></small>', 'fabrik-form.png' );
		JToolBarHelper::save( );
		JToolBarHelper::apply( );
		JToolBarHelper::cancel( );
	}
	
	/**
	* Display all available forms
	* @param array array of form_rule objects
	* @param object page navigation 
	*/
	
	function show( $forms, $pageNav, $lists ) {
		FabrikViewForm::setFormsToolbar();
		$user	  = &JFactory::getUser();
		$user	  = &JFactory::getUser();
		FabrikHelperHTML::tips();
		?>
		
		<form action="index.php" method="post" name="adminForm">
			<table class="adminlist">
				<tr> 
					<td>
						<?php
						echo JText::_('FORM') . ': ';
						echo $lists['filter_form'];
						?>
					</td>
				</tr>
			</table>
			
		<table class="adminlist"> 
			<thead>
			<tr>
				<th width="2%"><?php echo JHTML::_('grid.sort',  '#', 'f.id', @$lists['order_Dir'], @$lists['order'] ); ?></th>
				<th width="1%" > 
					<input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo count( $forms );?>);" />
				</th>
				<th width="29%" align="center">
					<?php echo JHTML::_('grid.sort',  'Label', 'f.label', @$lists['order_Dir'], @$lists['order'] ); ?>
				</th>
				<th width="5%">
					<?php echo JHTML::_('grid.sort',  'Published', 'f.state', @$lists['order_Dir'], @$lists['order'] ); ?>
				</th>
				<th width="20%">&nbsp;</th>
				<th width="20%">&nbsp;</th>
			</tr>
			</thead>
						<tfoot>
				<tr>
				<td colspan="8">
					<?php echo $pageNav->getListFooter(); ?>
				</td>
				</tr>
			</tfoot>
			<tbody>
			<?php $k = 0;
		for ( $i = 0, $n = count($forms); $i < $n; $i ++ ) {
			$row = & $forms[$i]; 
			$link 	= JRoute::_('index.php?option=com_fabrik&c=form&task=edit&cid='. $row->id );
			$checked		= JHTML::_('grid.checkedout',   $row, $i );
			$row->published = $row->state;
			$params = new JParameter($row->attribs, 'administrator/components/com_fabrik/models/form.xml');
			$published		= JHTML::_('grid.published', $row, $i );
			?> 
			<tr class="<?php echo "row$k"; ?>">
				<td><?php echo $row->id; ?></td>
				<td><?php echo $checked;?></td>
				<td>
					<?php
					if ( $row->checked_out && ( $row->checked_out != $user->get( 'id' ) )) {?>
						<span class="editlinktip hasTip" title="<?php echo $row->label . "::" . $params->get('note'); ?>">
							<?php echo $row->label;?>
						</span>
					<?php } else {
					?>
						<a href="<?php echo $link;?>">
							<span class="editlinktip hasTip" title="<?php echo $row->label . "::" . $params->get('note'); ?>">
							<?php echo $row->label; ?>
							</span>
						</a>
					<?php } ?>
				</td>
				<td>
					<?php echo $published?>
				</td>						
				<td style="text-align:right">
					<?php if ( $row->record_in_database == '1' ) { ?> 
						<a href="#updatedatabase" onclick="return listItemTask('cb<?php echo $i;?>','updatedatabase')"><?php echo JText::_( 'UPDATE DATABASE' );?></a> 
					<?php
					} else {
						echo JText::_( 'NA' );
					} ?> 
				</td>
				<td width="20%" style="text-align:right"> 
					<?php
					if ( $row->record_in_database == '1' ) { ?>
							<a href="<?php echo JRoute::_('index.php?option=com_fabrik&c=table&task=viewTable&cid='.$row->_table_id);?>"><?php echo JText::_( 'VIEW DATA' );?></a>
					<?php } else {
						echo JText::_( 'NA' );
					}?>
				</td>
			</tr> 
			<?php $k = 1 - $k;
			} ?>
			</tbody>
		</table>
		<input type="hidden" name="option" value="com_fabrik" />
		<input type="hidden" name="c" value="form" />
		<input type="hidden" name="task" value="forms" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="filter_order" value="<?php echo $lists['order']; ?>" />
		<input type="hidden" name="filter_order_Dir" value="<?php echo $lists['order_Dir']; ?>" />
		<?php echo JHTML::_( 'form.token' ); ?>
	</form>
	<?php }	

	
	/**
	* Display the form to add or edit an form
	* @param object form table
	* @param object plugin manager
	* @param array lists
	* @param object parameters from attributes
	* @param object form - used to render xml form cdoe
	*/
	
	function edit( $row, $pluginManager, $lists, $params, &$form )
	{
		FabrikHelperHTML::mootools();
		JHTML::stylesheet( 'fabrikadmin.css', 'administrator/components/com_fabrik/views/' );
		jimport('joomla.html.pane');
		JRequest::setVar( 'hidemainmenu', 1 );
		$pane	=& JPane::getInstance();
		FabrikHelperHTML::tips();
		FabrikViewForm::setFormToolbar();
		$editor =& JFactory::getEditor();
		$document =& JFactory::getDocument();
		FabrikHelperHTML::script( 'adminform.js', 'administrator/components/com_fabrik/views/', true );
		JFilterOutput::objectHTMLSafe( $row );
		$js = 
		
	"
  window.addEvent('load', function(){ 
  	var lang = {
			'action':'".JText::_( 'ACTION' )."',
			'do':'".JText::_( 'DO' )."',
			'del':'".JText::_( 'DELETE' )."',
			'in':'".JText::_( 'IN' )."',
			'on':'".JText::_( 'ON' )."',
			'options':'".JText::_( 'OPTIONS' )."',
			'please_select': '".JText::_( 'PLEASE SELECT' )."'
		}
  	var aPlugins = [];\n";

  $c = 0;
/*
 * 	go through ALL the installed form plugins and get their admn form's HTML
 */
  foreach ($pluginManager->_plugIns['form'] as $usedPlugin => $oPlugin) {
		$pluginParams = &new fabrikParams( $row->attribs, $oPlugin->_xmlPath, 'fabrikplugin' );
		$pluginParams->_duplicate = true;
		$oPlugin->_adminVisible = false;
		//Needed for counter override in radio buttons 
		//(set from -1 to the correct num in adminform.js func addAction
		$oPlugin->_counter = -1;
		$pluginHtml = $oPlugin->renderAdminSettings( $usedPlugin, $row, $pluginParams, $lists, 0 );
		$o = new stdClass();
		$o->$usedPlugin = $oPlugin->_pluginLabel;
		$o->value = $usedPlugin;
		$o->label = $oPlugin->_pluginLabel;
		$o->html = $pluginHtml;
		$o = FastJSON::encode($o);
		//json encode rather than create object by hand
	  //$js .= "aPlugins.push({'".$usedPlugin."':'".$oPlugin->_pluginLabel."','value':'".$usedPlugin."','label':'".$oPlugin->_pluginLabel."', 'html':'".$pluginHtml."'});\n";
	  $js .= "aPlugins.push($o);\n";
		$c ++; 
	}
	$js .= "controller = new fabrikAdminForm(aPlugins, lang);\n";
 	$usedPlugins 		= $params->get( 'plugin', '', '_default', 'array' );
 	$usedLocations 	= $params->get( 'plugin_locations', '', '_default', 'array' );
 	$usedEvents 		= $params->get( 'plugin_events', '', '_default', 'array' );
	$c = 0;
	
	//go through all of this form's ACTIVE plugins 
	foreach ($usedPlugins as $usedPlugin) {
		$oPlugin 				= $pluginManager->_plugIns['form'][$usedPlugin];
		$pluginParams 	= new fabrikParams( $row->attribs, $oPlugin->_xmlPath, 'fabrikplugin');
		$names 					= $pluginParams->_getParamNames();
		$tmpAttribs 		= '';
		foreach ($names as $name) {
			$pluginElOpts = $params->get($name, '', '_default', 'array');
			$val 					= (array_key_exists($c, $pluginElOpts)) ? $pluginElOpts[$c] : '';
			//backslash any "|"'s in the data (otherwise the ini.php format thinks you are storing an array
			$val 					= preg_replace('#(?<!\\\)\|#', '\|', $val);
			$tmpAttribs 	.= $name . "=" . $val . "\n";
		}
    //redo the parmas with the exploded data
    $pluginParams = new fabrikParams( $tmpAttribs, $oPlugin->_xmlPath, 'fabrikplugin');
    $pluginParams->_duplicate = true;
    $oPlugin->_adminVisible = true;
    $oPlugin->_counter = $c;
		$data = $oPlugin->renderAdminSettings( $usedPlugin, $row, $pluginParams, $lists, 0);
		//sanitize data as its no longer sanitized in renderAdminSettings
		$data = addslashes(str_replace("\n", "", $data));
		$js .= "controller.addAction( '".$data."', '".$usedPlugins[$c]."', '".@$usedLocations[$c]."', '".@$usedEvents[$c]."');\n";
		$c ++;
  }
  jimport( 'joomla.html.editor' );
  $js .= "
});

function submitbutton(pressbutton) {		

	var form = document.adminForm;"
	. $editor->save( 'intro' )  
	. $editor->save( 'receipt_message' )
	. $editor->save( 'form_submit_message' )
	."
	if (pressbutton == 'cancel') {
		submitform( pressbutton );
		return;
	}

	/* do field validation */
	var err = '';
	
	/* depreciated i think
	if(pressbutton == 'menulinkForm'){
		if($('menuselect').selectedIndex == -1){
			err = err + '". JText::_( 'PLEASE SELECT A MENU TO PUBLISH LINK TO' ) . '\n' . "';
		}
		if(\$('link_name').value == ''){
			err = err + '". JText::_( 'PLEASE ENTER A LABEL FOR YOUR LINK' ). '\n' .  "';
		}
		if(\$('alias').value == ''){
			err = err + '". JText::_( 'PLEASE ENTER AN ALIAS FOR YOUR LINK' ). '\n' . "';
		}
	}
	*/
	if (form.label.value == '') {
		err = err + '". JText::_( 'PLEASE ENTER A FORM LABEL' ). '\n' . "';
	} 
	
	//if(form.id.value == 0 && form.record_in_database.checked == true && form._database_name.value == ''){
	if(form.record_in_database.checked == true && form._database_name.value == ''){
		err = err + '". JText::_( 'PLEASE ENTER A DATABASE TABLE NAME' ) . "';
	}
	if (err == ''){
		/* assemble the form groups back into one field */
		mergeFormGroups()
		submitform( pressbutton );
	}else{
		alert (err);
	}
}

function mergeFormGroups(){
	/* assemble the form groups back into one field */
	var tmp = [];
	if($('current_groups')){
		var opts = $('current_groups').options;
		for (var i=0, n=opts.length; i < n; i++) {	
			tmp.push(opts[i].value);
		}	
		$('current_groups_str').value = tmp.join(',');
	}
}
";
	$document->addScriptDeclaration($js);
	FabrikHelperHTML::cleanMootools();
	?>
		<form action="index.php" method="post" name="adminForm" id="adminForm">
		 	<table style="width:100%;">
		 		<tr>
	 			<td style="width:50%;" valign="top">
	 			<fieldset class="adminform">
				<legend><?php echo JText::_( 'DETAILS' ); ?></legend>
					<table class="admintable"> 
						<tr>
							<td class="key" width="30%">
							<?php echo JHTML::_('tooltip', JText::_( 'FROM LABEL DESC' ), JText::_( 'LABEL' ), 'tooltip.png', JText::_( 'LABEL'));?>
							</td>
							<td width="70%">
								<input class="inputbox" type="text" name="label" id="label" size="50" value="<?php echo $row->label; ?>" />
							</td>
						</tr>
						<tr>
							<td class="key"><?php echo JText::_( 'INTRODUCTION' );?></td>
							<td><?php echo $editor->display( 'intro', $row->intro, '100%', '200', '50', '5', false );?>
							</td>
						</tr>
						<tr> 
							<td class="key">
								<label for="error">
								<?php echo JHTML::_('tooltip', JText::_( 'FROM ERROR MESSAGE DESC' ), JText::_( 'ERROR MESSAGE' ), 'tooltip.png', JText::_( 'ERROR MESSAGE', true ));?>
								</label> 
							</td>
							<td>
								<input class="inputbox" type="text" name="error" id="error" size="50" value="<?php echo $row->error; ?>" />
							</td>
						</tr> 
					</table>
				</fieldset>
				<fieldset class="adminform">
				<legend><?php echo JText::_( 'BUTTONS' ); ?></legend>
						<?php 
						echo $form->render( 'params', 'buttons' );
						?>
						<table class="admintable">
						<tr>
							<td class="key">
								<label for="submit_button_label">
								<?php echo JText::_( 'SUBMIT LABEL' );?>
								</label>
							</td>
							<td>
								<input type="text" class="inputbox" id="submit_button_label" name="submit_button_label" value="<?php echo $row->submit_button_label;?>" />
							</td>
						</tr>
					</table>
				</fieldset>
				<fieldset class="adminform">
				<legend><?php echo JText::_( 'FORM PROCESSING' ); ?></legend>
					<table class="admintable">
						<tr>
							<td class="key">
							<label for="record_in_database">
							<?php echo JText::_( 'RECORD IN DATABASE' );?>
							</label>
							</td>
							<td>
							<input type="checkbox" id="record_in_database" name="record_in_database" value="1" <?php if($row->record_in_database == '1'){echo(" checked=\"checked\"");}?> />
							</td>
						</tr>
						<tr>
							<td class="key">
							<label for="database_name">
							<?php echo JText::_( 'TABLE NAME' );?>
							</label>
							</td>
							<td>
							<?php if($row->record_in_database != '1'){?>
								<input id="database_name" name="_database_name" value="" size="40" />
							<?php }else{ ?>
									<?php echo $row->_database_name;?>
									<input type="hidden" id="database_name" name="_database_name" value="<?php echo $row->_database_name;?>"  />
									<input type="hidden" id="_connection_id" name="_connection_id" value="<?php echo $row->_connection_id;?>"  />
							<?php }?>
							</td>
						</tr>
				</table>
				<?php 
						echo $form->render( 'params', 'processing' );
						 ?>
			</fieldset>
			<fieldset>
				<legend><?php echo JText::_('Notes'); ?></legend>
					<?php echo $form->render( 'params', 'notes' ); ?>
			</fieldset>
					</td>
					<td valign="top">
					<?php 
					echo $pane->startPane( "content-pane" );
					echo $pane->startPanel( 'Publishing', "publish-page" ); 
					echo $form->render( 'details' );
					echo  $pane->endPanel();
					echo $pane->startPanel( JText::_( 'GROUPS' ), "formgroups-page" ); ?>
		<table class="adminform">
			<tr>
				<th colspan="2"><?php echo JText::_( 'GROUPS' ); ?></th>
			</tr>
			<tr>
				<td colspan="2"><label>
				<?php $checked = empty($lists['current_groups']) ? 'checked="checked"' : '';?>
				<input type="checkbox" <?php echo $checked?> name="_createGroup" id="_createGroup" value="1" />
				<?php echo JText::_( 'CREATE A GROUP WITH THE SAME NAME AS THIS FORM' );?>
				</label></td>
			</tr>
			<?php if (empty($lists['groups']) && empty($lists['current_groups'])) {?>
			<tr>
				<td>
				<?php echo JText::_( 'NO GROUPS AVAILABLE' );?>
				<input type="hidden" name="_createGroup" id="_createGroup" value="1" />
				</td>
			</tr>
			<?php }else{?>

			<tr>
				<td colspan="2"><p><?php echo JText::_( 'AVAILABLE GROUPS' );?>: </p>
				<?php echo $lists['grouplist']; ?></td>
			</tr>
			<tr>
				<td colspan="2">
					<input "class="button" type="button" value="<?php echo JText::_('ADD'); ?>"
					onclick="$('_createGroup').checked = false;addSelectedToList('adminForm','groups','current_groups');delSelectedFromList('adminForm','groups');" />
				</td>
			</tr>
			<tr>
				<td colspan="2"><p><?php echo JText::_( 'CURRENT GROUPS' );?>: </p>
				<?php echo $lists['current_grouplist'];?></td>
			</tr>
			<tr>
				<td colspan="2"><input class="button" type="button"
					value="<?php echo JText::_( 'UP' ); ?>"
					onclick="moveInList('adminForm','current_groups',adminForm.current_groups.selectedIndex,-1)" />
				<input class="button" type="button" value="<?php echo JText::_( 'DOWN' ); ?>"
					onclick="moveInList('adminForm','current_groups',adminForm.current_groups.selectedIndex,+1)" />
				<input class="button" type="button" value="<?php echo JText::_( 'REMOVE' ); ?>"
					onclick="addSelectedToList( 'adminForm', 'current_groups', 'groups' );delSelectedFromList('adminForm','current_groups');" />
				</td>
			</tr>
			<?php }?>
		</table>
		<?php echo  $pane->endPanel();
		echo $pane->startPanel( JText::_( 'TEMPLATES' ), "template-page"); ?>
		<table class="adminform">
			<tr>
				<th colspan="2"><?php echo JText::_( 'TEMPLATES' ); ?></th>
			</tr>
			<tr>
				<td><?php echo JText::_( 'DETAILED VIEW TEMPLATE' ); ?></td>
				<td><?php echo $lists['viewOnlyTemplates']; ?></td>
			</tr>
			<tr>
				<td><?php echo JText::_( 'FORM TEMPLATE' ); ?></td>
				<td><?php echo $lists['formTemplates']; ?></td>
			</tr>
		</table>
		<?php echo  $pane->endPanel();
		echo $pane->startPanel( JText::_( 'OPTIONS' ), "menu-page" ); 
		echo $form->render( 'params', 'options' );
		echo $pane->endPanel();
		echo $pane->startPanel( 'Submission plug-ins', "actions-page" );?>
			<table class="adminform">
				<tr>
					<th colspan="2">
					<a href="#" class="addButton" id="addAction"><?php echo JText::_( 'ADD' ); ?></a>
					</th>
				</tr>
				<tr>
					<td colspan="2" id="formActions"><br />
					</td>
				</tr>
			</table>
		<?php echo $pane->endPanel();
		echo $pane->endPane(); ?>
			</td>
		</tr>
	</table>
	<input type="hidden" name="task" id="task" value="" />
	<input type="hidden" name="option" value="com_fabrik" />
	<input type="hidden" name="c" value="form" />
	<input type="hidden" name="id" value="<?php echo $row->id; ?>" />
	<input type="hidden" name="boxchecked" value="" />
	<input type="hidden" name="current_groups_str" id="current_groups_str" value="" />
	<?php echo JHTML::_( 'form.token' );
	echo JHTML::_('behavior.keepalive'); ?>
</form>
	<?php }	
}


?>