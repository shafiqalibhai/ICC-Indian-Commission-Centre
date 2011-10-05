<?php
/**
* @package Joomla
* @subpackage Fabrik
* @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

class FabrikViewElement {
	
	/**
	 * set up the menu when viewing the list of  validation rules
	 */

	function setElementsToolbar()
	{
		JToolBarHelper::title( JText::_( 'ELEMENTS' ), 'fabrik-element.png' );
		JToolBarHelper::customX( 'addToTableView', 'publish.png', 'publish_f2.png', JText::_('ADD TO TABLE VIEW') );
		JToolBarHelper::customX( 'removeFromTableView', 'unpublish.png', 'unpublish_f2.png', JText::_('REMOVE FROM TABLE VIEW') );
		JToolBarHelper::publishList();
		JToolBarHelper::unpublishList();
		JToolBarHelper::customX( 'copy', 'copy.png', 'copy_f2.png', 'Copy' );
		JToolBarHelper::deleteList( '', 'checkRemove' );
		JToolBarHelper::editListX();
		JToolBarHelper::addNewX();
	}
	
	/**
	 * set up the menu when editing the validation rule
	 */

	function setElementToolbar()
	{
		$task = JRequest::getVar( 'task', '', 'method', 'string' );
		JToolBarHelper::title( $task == 'add' ? JText::_( 'ELEMENT' ) . ': <small><small>[ '. JText::_( 'NEW' ) .' ]</small></small>' : JText::_( 'ELEMENT' ) . ': <small><small>[ '. JText::_( 'EDIT' ) .' ]</small></small>', 'fabrik-element.png' );
		JToolBarHelper::save();
		JToolBarHelper::apply();
		JToolBarHelper::cancel();
	}
	
	function setCheckRemoveToolBar()
	{
		JToolBarHelper::title( JText::_('DELETE') . ' ' . JText::_('ELEMENT') , 'generic.png' );
		JToolBarHelper::apply( 'remove' );
		JToolBarHelper::cancel();
	}
	
	/**
	* Display the form to add or edit an validation rule
	* @param object element table object
	* @param object plugin manager model
	* @param array lists
	* @param object parameters
	*/
	
	function edit( $row, $pluginManager, $lists, $params, $form )
	{
		
		JFilterOutput::objectHTMLSafe( $row, ENT_QUOTES, '' );
		
		FabrikViewElement::setElementToolbar();
		FabrikHelperHTML::mootools();
		$fbConfig =& JComponentHelper::getParams( 'com_fabrik' );
		JHTML::stylesheet( 'fabrikadmin.css', 'administrator/components/com_fabrik/views/' );
		JRequest::setVar( 'hidemainmenu', 1 );
		$document =& JFactory::getDocument( );
		jimport( 'joomla.html.pane' );
		$pane	=& JPane::getInstance();
		FabrikHelperHTML::tips();
		 if ($fbConfig->get( 'fbConf_wysiwyg_label', false )) {
			$editor =& FabrikHelperHTML::getEditor();
		 }
		FabrikHelperHTML::script( 'element.js', 'components/com_fabrik/views/form/', true );
		FabrikHelperHTML::script( 'adminelement.js', 'administrator/components/com_fabrik/views/', true );
		
		$js = "/* <![CDATA[ */
		
	function submitbutton(pressbutton){
		adminform = $('adminForm');
		/*  do field validation */
		if (pressbutton == 'cancel' ){
			submitform( pressbutton );                   
			return ;
   	}        
		if (adminform.name.value == '') {
			alert( '". JText::_( 'PLEASE SELECT A NAME' )."' );
		} else {
			submitbutton2( pressbutton );
		}
	}
	
	function submitbutton2(pressbutton) {";
		if ($fbConfig->get( 'fbConf_wysiwyg_label', false )) {
			$js .=  $editor->save( 'label' );
		}
		$js .="//iternate through the plugin controllers to match selected plugin
		var adminform = document.adminForm;
		var er = false;
		
		\$A(pluginControllers).each(function(plugin){
			if($('detailsplugin').value == plugin.element){
				var r = plugin.controller.onSave();
				if(r == false){
					er = true;
				}
			}
		});
		
		if(er === false){
			submitform( pressbutton );
		}
		return;
	}
	window.addEvent('domready', function(){\n";
		
		$opts = new stdClass();
		$opts->plugin = $row->plugin;
		$opts->parentid = $row->parent_id;
		$opts->jsevents = $lists['jsActions'];
		$opts->elements = $lists['elements'];
		$js .= "\tvar options = ".FastJSON::encode($opts) . ";\n";
		
		$js .= "\tvar lang = {'jsaction':'".JText::_('ACTION')."','code':'". JText::_('CODE')."'};\n".
		"var aPlugins = [];\n";	
		
	  $c = 0;
		//	set up the template code for creating validation plugin interfaces
	  foreach ($pluginManager->_plugIns['validationrule'] as $usedPlugin => $oPlugin) {
			$pluginParams = &new fabrikParams( $row->attribs, $oPlugin->_xmlPath, 'fabrikplugin' );
			
			$oPlugin->_pluginParams =& $pluginParams; 
			$pluginParams->_duplicate = true;
			$oPlugin->_adminVisible = false;
			$pluginHtml = $oPlugin->renderAdminSettings( $usedPlugin, $row, $pluginParams, 0 );
			
			$o = new stdClass();
			$o->$usedPlugin = $oPlugin->_pluginLabel;
			$o->value = $usedPlugin;
			$o->label = $oPlugin->_pluginLabel;
			$o->html = $pluginHtml;
			$o = FastJSON::encode($o);
			$js .= "aPlugins.push($o);\n";
			$c ++; 
		}
		
		$js .= "\tvar controller = new fabrikAdminElement(aPlugins, options, lang);\n";
		
		//add in active validations
		$usedPlugins 		= $params->get( 'validation-plugin', '', '_default', 'array' );
		$c = 0;
		foreach ($usedPlugins as $usedPlugin) {
			$oPlugin 		=& $pluginManager->_plugIns['validationrule'][$usedPlugin];
			$pluginParams 	=& $oPlugin->_pluginParams;
			$names 			= $pluginParams->_getParamNames();
			$tmpAttribs 	= '';
			foreach ($names as $name) {
				$pluginElOpts = $params->get($name, '', '_default', 'array');
				$val = (array_key_exists($c, $pluginElOpts)) ? $pluginElOpts[$c] : '';
				//backslash any "|"'s in the data (otherwise the ini.php format thinks you are storing an array
				$val = preg_replace('#(?<!\\\)\|#', '\|', $val);
				$tmpAttribs .= $name . "=" . $val . "\n";
			}
	    //redo the parmas with the exploded data
	    
	    $pluginParams =& new fabrikParams( $tmpAttribs, $oPlugin->_xmlPath, 'fabrikplugin');
	    $pluginParams->_duplicate = true;
	    $oPlugin->_adminVisible = true;
	    $oPlugin->_counter = $c;
			$data = $oPlugin->renderAdminSettings( $usedPlugin, $row, $pluginParams, $lists, 0);
			//sanitize data as its no longer sanitized in renderAdminSettings
		  $data = addslashes( str_replace( "\n", "", $data ) );
			$js .= "controller.addValidation( '".$data."', '".$usedPlugins[$c]."');\n";
			$c ++;
	  }
	  
		foreach ($pluginManager->_plugIns['element'] as $key => $tmp) 
		{
			$oPlugin =& $pluginManager->_plugIns['element'][$key];
			//do this to reduce the number of queries
			$oPlugin->_element =& $row;
			$oPlugin->setId( $row->id );
			$js .= $oPlugin->getAdminJS();
		}

		$js .= "});
		/* ]]> */";
		
		$document->addScriptDeclaration($js);

	?>
<form action="index.php" method="post" name="adminForm" id="adminForm">
<?php if ($row->parent_id != 0) {
	?>
	<div id="system-message">
	<dl>
		<dd class="notice">
		<ul>
			<li>
				<?php echo JText::_( 'THIS ELEMENTS PROPERTIES ARE LINKED TO' ) ?>:
			</li>
			<li>
				<a href="#" id="swapToParent" class="element_<?php echo $lists['parent']->id ?>"><?php echo $lists['parent']->label ?></a>
			</li>
			<li>
				<label><input id="unlink" name="unlink" id="unlinkFromParent" type="checkbox"> <?php echo JText::_( 'UNLINK' ) ?></label>	
			</li>
		</ul>
		</dd>
	</dl>
	</div>
<?php }?>
<table style="width:100%" id="elementFormTable" >
	<tr>
		<td style="width:50%" valign="top">
		<fieldset class="admintable">
			<legend><?php echo JText::_( 'DETAILS' );?></legend>
			<table class="admintable">
				<tr>
					<td class="key">
						<label for="name">
						<?php echo JHTML::_( 'tooltip', JText::_( 'NAMEDESC' ), JText::_( 'NAME' ), 'tooltip.png', JText::_( 'NAME', true )); ?>
						</label>
					</td>
					<td>
						<input class="inputbox" type="text" id="name" name="name" size="75" value="<?php echo $row->name; ?>" /> 
						<input type="hidden" id="name_orig" name="name_orig" value="<?php echo $row->name; ?>" />
					</td>
				</tr>
				<tr>
					<td class="key">
						<label for="label">
							<?php echo JHTML::_('tooltip', JText::_( 'LABELDESC' ), JText::_( 'LABEL' ), 'tooltip.png', JText::_( 'LABEL', true ));?>
						</label>
					</td>
					<td>
						<?php if ($fbConfig->get( 'fbConf_wysiwyg_label', false )) {
							echo $editor->display( 'label', $row->label, '100%', '200', '50', '5', false );
						} else { ?>
							<input class="inputbox" type="text" id="label" name="label" size="75" value="<?php echo $row->label; ?>" />
						<?php }
						?>
					</td>
				</tr>
			</table>
			<?php 
			echo $form->render( 'params' );
			echo $form->render( 'details', 'basics' );
					
			?>
		</fieldset>
		
		<fieldset class="admintable">
			<legend><?php echo JText::_( 'OPTIONS' );?></legend>
			<?php 
				
			foreach ($pluginManager->_plugIns['element'] as $key => $tmp) 
				{
					$oPlugin =& $pluginManager->_plugIns['element'][$key];
					$oPlugin->setId( $row->id );
					//do this to reduce the number of queries
					$oPlugin->_element =& $row;
					$oPlugin->renderAdminSettings( $lists );
				}
			?>
		</fieldset>
		
		</td>
		<td style="width:50%" valign="top">
		<?php
		echo $pane->startPane( "content-pane" );
		echo $pane->startPanel( JText::_( 'PUBLISHING' ), "publish-page" );
		?>
		<fieldset>
		<?php
		echo $form->render( 'details', 'publishing' );
		echo $form->render( 'params', 'publishing2' );
		?>
		</fieldset>
		<fieldset class="admintable">
			<legend><?php echo JText::_( 'RSS' );?></legend>
			<?php 
			echo $form->render('params', 'rss');
			?>
			</fieldset>
		<?php
			echo $pane->endPanel();
			echo $pane->startPanel( JText::_( 'TABLE SETTINGS' ), "table-page");
			?>
			<fieldset>
				<?php 
				echo $form->render('details', 'tablesettings');
				echo $form->render('params', 'tablesettings2');
				?>
			</fieldset>
					
			<fieldset>
				<legend><?php echo JText::_( 'FILTERS' ); ?></legend>
				<?php 
				echo $form->render( 'details', 'filtersettings');
				echo $form->render( 'params', 'filtersettings2');
				?>
			</fieldset>
			
			<fieldset>
				<legend><?php echo JText::_( 'CALCULATIONS' ); ?></legend>
				<?php echo $form->render('params', 'calculations');?>
			</fieldset>
		<?php
			echo $pane->endPanel();
			echo $pane->startPanel(JText::_( 'VALIDATIONS' ), "validations-page");
		?>
		<fieldset>
			<legend><?php echo JText::_( 'VALIDATIONS' ); ?></legend>
				<table class="adminform">
					<tr>
						<th colspan="2">
						<a href="#" class="addButton" id="addValidation"><?php echo JText::_( 'ADD' ); ?></a>
						</th>
					</tr>
					<tr>
						<td colspan="2" id="elementValidations"><br />
						</td>
					</tr>
			</table>
		</fieldset>
		<?php
		echo $pane->endPanel();
		echo $pane->startPanel( JText::_( 'JAVASCRIPT' ), "javascript-page" );?>
		<fieldset>
			<legend><?php echo JText::_( 'JAVASCRIPT' ); ?></legend>
			<a class="addButton" href="#" id="addJavascript"><?php echo JText::_('ADD'); ?></a>
			<div id="javascriptActions">
			</div>
		</fieldset>
		<?php
		echo $pane->endPanel();
		echo $pane->endPane();
		?></td>
	</tr>
</table>
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="option" value="com_fabrik" />
	<input type="hidden" name="c" value="element" />
	<input type="hidden" name="id" value="<?php echo $row->id; ?>" />
	<input type="hidden" name="boxchecked" value="" />
	<input type="hidden" name="redirectto" value="" />
	<?php echo JHTML::_( 'form.token' );
	echo JHTML::_('behavior.keepalive'); ?>
	</form>
	<?php  
	FabrikHelperHTML::cleanMootools();
	
	}

	/**
	* Display all available validation rules
	* @param array array of validation_rule objects
	* @param object page navigation 
	* @param array lists
	*/
	
	function show( $elements, $pageNav, $lists )
	{
		FabrikViewElement::setElementsToolbar();	
		$user	  = &JFactory::getUser();
		?> 
		<form action="index.php" method="post" name="adminForm"> 
			<table cellpadding="4" cellspacing="0" border="0" width="100%">
				<tr>
					<td><?php echo JText::_( 'NAME' ).": "; ?>
						<input type="text" name="filter_elementName" value="<?php echo $lists['search'];?>" class="text_area" onChange="document.adminForm.submit();" />
					</td>
					<td>
						<?php echo $lists['groupId']; ?>
					</td>					
					<td>
						<?php echo $lists['elementId']; ?>
					</td>
					<td>
						<?php echo $lists['filter_showInTable'];?>
					</td>
					<td>
						<?php echo $lists['filter_published']; ?>
					</td>
				</tr> 
			</table>
			<table class="adminlist"> 
			<thead>
			<tr> 
				<th width="2%"><?php echo JHTML::_( 'grid.sort',  '#', 'e.id', @$lists['order_Dir'], @$lists['order'] ); ?></th>
				<th width="2%"> <input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo count( $elements );?>);" /></th>
				<th width="25%" >
					<?php echo JHTML::_('grid.sort', 'Name', 'e.name', @$lists['order_Dir'], @$lists['order'] ); ?>
				</th>
				<th width="25%" >
					<?php echo JHTML::_('grid.sort', 'Label', 'e.label', @$lists['order_Dir'], @$lists['order'] ); ?>
				</th>
				<th width="20%" >
					<?php echo JHTML::_('grid.sort', 'Group', 'g.name', @$lists['order_Dir'], @$lists['order'] ); ?>
				</th>
				<th width="10%" >
					<?php echo JHTML::_('grid.sort', 'Element type', 'plugin', @$lists['order_Dir'], @$lists['order'] ); ?>
				</th>
				<th width="3%">
					<?php echo JHTML::_('grid.sort', 'Show in table', 'show_in_table_summary', @$lists['order_Dir'], @$lists['order'] ); ?>
				</th>
				<th width="3%">
					<?php echo JHTML::_('grid.sort', 'Published', 'e.state', @$lists['order_Dir'], @$lists['order'] ); ?>
				</th>
				<th width="10%">
					<?php echo JHTML::_('grid.sort',  'Order', 'e.ordering', @$lists['order_Dir'], @$lists['order'] ); ?>
					<?php echo JHTML::_('grid.order', $elements ); ?>
				</th>
			</tr>
			</thead>
			<tfoot>
				<tr>
				<td colspan="9">
					<?php echo $pageNav->getListFooter(); ?>
				</td>
				</tr>
			</tfoot>
			<tbody>
			<?php $k = 0;
			for ($i = 0, $n = count($elements); $i < $n; $i ++) {
				$row 				= & $elements[$i]; 
				$checked		= JHTML::_('grid.checkedout',   $row, $i );
				$link 			= JRoute::_( 'index.php?option=com_fabrik&c=element&task=edit&cid='. $row->id );
				$row->published = $row->state;
				$published		= JHTML::_('grid.published', $row, $i );
				?>
				<tr class="<?php echo "row$k"; ?>"> 
					<td width="2%"><?php echo $row->id; ?></td>
					<td>
						<?php echo $checked; ?>
					</td>
					<td >
						<?php
						if ($row->checked_out && ( $row->checked_out != $user->get( 'id' ) )) {
							echo $row->name;
						} else {
						?>
							<a href="<?php echo $link;?>"><?php echo $row->name; ?></a>
						<?php } ?>
					</td>
					<td><?php echo ($row->label); ?></td>
					<td><?php echo ($row->group_name); ?></td>
					<td><?php echo htmlentities($row->pluginlabel); ?></td>
					<td>
					<?php if ($row->show_in_table_summary == "1") {
						$img = 'publish_g.png';
						$alt = JText::_( 'SHOW IN TABLE' );
					} else {
						$img = "publish_x.png";
						$alt = JText::_( 'HIDE IN TABLE' );
					}
					?>
						<a href="javascript:void(0);" onclick="return listItemTask('cb<?php echo $i;?>','<?php echo $row->show_in_table_summary ? "removeFromTableview" : "addToTableView";?>');">
							<img src="images/<?php echo $img;?>" border="0" alt="<?php echo $alt; ?>" />
						</a>
					</td>
					<td>
						<?php echo $published;?>
					</td>
					<td class="order"> 
					<?php $condition = $row->group_id == @ $elements[$i -1]->group_id;
					echo '<span>' . $pageNav->orderUpIcon($i, ($condition), 'orderUpElement') . '</span>'; 
					$condition = $row->group_id == @ $elements[$i +1]->group_id;
					echo '<span>' . $pageNav->orderDownIcon($i, $n, ($condition), 'orderDownElement') . '</span>'; 
					?>
						<input type="text" name="order[]" size="5" value="<?php echo $row->ordering; ?>" class="text_area" style="text-align: center" />
					</td>
				</tr> 
			<?php
			$k = 1 - $k;
		} ?>
			</tbody>
		</table>
		<input type="hidden" name="option" value="com_fabrik" />
		<input type="hidden" name="c" value="element" />
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="filter_order" value="<?php echo $lists['order']; ?>" />
		<input type="hidden" name="filter_order_Dir" value="<?php echo $lists['order_Dir']; ?>" />
		<?php echo JHTML::_( 'form.token' ); ?>
	</form>
	<?php }
	
	/**
	 * decide if you want to drop the tables columns for each element marked to be deleted
	 *
	 * @param array $elements
	 */
	
	function checkRemove( $elements )
	{
		FabrikViewElement::setCheckRemoveToolBar();
		?>
		<h1><?php echo JText::_('DO YOU WANT TO DROP THE DATABASE COLUMN AS WELL?') ?></h1>
		<form action="index.php" method="post" name="adminForm" id="adminForm">
		<table class="adminlist">
			<thead>
				<tr>
					<th><?php echo JText::_('Drop') ?></th>
					<th><?php echo JText::_('Element') ?></th>
				</tr>
			</thead>
				<tbody>
				<?php 
				$c = 0;
				foreach ($elements as $element) { ?>
					<tr class="row<?php echo $c % 2; ?>">
						<td>
							<label><input type="radio" name="drop[<?php echo $element->id ?>][]" value="0" checked="checked" /><?php echo JText::_('No') ?></label>
							<label><input type="radio" name="drop[<?php echo $element->id ?>][]" value="1" /><?php echo JText::_('Yes') ?></label>
						</td>
						<td>
							<?php echo $element->label; ?>
							<input type="hidden" name="cid[]" value="<?php echo $element->id ?>" />
						</td>
					</tr>
				<?php $c ++;
				} ?>
				</tbody>
			</table>
			<input type="hidden" name="option" value="com_fabrik" />
			<input type="hidden" name="c" value="element" />
			<input type="hidden" name="task" value="remove" />
			<?php echo JHTML::_( 'form.token' ); ?>
		</form>
		<?php
	}
}
?>