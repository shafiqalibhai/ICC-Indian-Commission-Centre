<?php
class JCEHelper {
	// Update from 1.5.0 to 1.5.1
	function checkDB(){
		$db	=& JFactory::getDBO();

		$query = 'SELECT id'
		. ' FROM #__jce_plugins'
		. ' WHERE name = ' . $db->Quote('article')
		;
		$db->setQuery( $query );	
		return $db->loadResult();
	}
	function checkEditorInstall(){
		$db	=& JFactory::getDBO();
		
		$query = 'SELECT id'
		. ' FROM #__plugins'
		. ' WHERE element = "jce" AND folder = "editors"'
		;
		$db->setQuery( $query );	
		return $db->loadResult();
	}
	function checkEditor(){
		$db	=& JFactory::getDBO();
		
		$query = 'SELECT published'
		. ' FROM #__plugins'
		. ' WHERE element = "jce" AND folder = "editors"'
		;
		$db->setQuery( $query );	
		return $db->loadResult();
	}
	function checkEditorPath(){
		$path = JPATH_PLUGINS .DS. 'editors';
		if( file_exists( $path .DS. 'jce.php' ) && file_exists( $path .DS. 'jce.xml' ) && is_dir( $path .DS. 'jce' ) ){
			return true;
		}
		return false;
	}
	function checkPlugins(){
		$db =& JFactory::getDBO();
		$query = 'SELECT id'
		. ' FROM #__jce_plugins'
		;
		$db->setQuery( $query );
		$result = $db->loadResult();
		
		return $result ? true : false;
	}
	function checkGroups(){
		$db =& JFactory::getDBO();
		$query = 'SELECT id'
		. ' FROM #__jce_groups'
		;
		$db->setQuery( $query );
		$result = $db->loadResult();
		
		return $result ? true : false;
	}
	function updateDB(){
		global $mainframe;
		$db =& JFactory::getDBO();
		
		// Add Article Breaks plugin
		$query = "UPDATE #__jce_plugins SET `title` = 'Article Breaks', `name` = 'article', `icon` = 'readmore,pagebreak', `layout` = 'article'"
		. " WHERE name = " . $db->Quote('readmore')
		;
		
		$db->setQuery( $query );
		
		if( !$db->query() ){
			$mainframe->enqueueMessage( JText::_('Unable to add Article Breaks plugin data!'), 'error' );
		}else{
			$mainframe->enqueueMessage( JText::_('Article Breaks plugin installed successfully') );
		}
		// Add Admin Menu options
		$query = "UPDATE #__components SET `admin_menu_img` = '../administrator/components/com_jce/img/logo.png'"
		. " WHERE link = " . $db->Quote('option=com_jce')
		;
		
		$db->setQuery( $query );
		
		if( !$db->query() ){
			$mainframe->enqueueMessage( JText::_('Unable add Administration menu image!'), 'error' );
		}else{
			$mainframe->enqueueMessage( JText::_('Administration menu image added successfully') );
		}
		$mainframe->redirect( 'index.php?option=com_jce&redirected=1' );
	}
	function installGroups( $install = false ){
		global $mainframe;
		$db =& JFactory::getDBO();
		
		// Groups
		$query = "CREATE TABLE IF NOT EXISTS `#__jce_groups` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `name` varchar(255) NOT NULL,
		  `description` varchar(255) NOT NULL,
		  `users` text NOT NULL,
		  `types` varchar(255) NOT NULL,
		  `components` text NOT NULL,
		  `rows` text NOT NULL,
		  `plugins` varchar(255) NOT NULL,
		  `published` tinyint(3) NOT NULL,
		  `ordering` int(11) NOT NULL,
		  `checked_out` tinyint(3) NOT NULL,
		  `checked_out_time` datetime NOT NULL,
		  `params` text NOT NULL,
		  PRIMARY KEY (`id`)
		);";
		$db->setQuery( $query );
		
		if( !$db->query() ){
			$mainframe->enqueueMessage( JText::_('Unable to create Groups Table!') );
		}else{
			$mainframe->enqueueMessage( JText::_('Groups Table created successfully.') );
			
			$query = 'SELECT count(id) FROM #__jce_groups';
			$db->setQuery( $query );
			
			if( !$db->loadResult() ){
				$query = "INSERT INTO `#__jce_groups` (`id`, `name`, `description`, `users`, `types`, `components`, `rows`, `plugins`, `published`, `ordering`, `checked_out`, `checked_out_time`, `params`) VALUES
				(1, 'Default', 'Default group for all users with edit access', '', '19,20,21,23,24,25', '', '28,27,32,33,19,20,21,29,45,44,43,46,26,49,36,37,30,31,39,40;56,47,38,5,9,48,42,24,25,22,18,2;7,13,10,3;23,15,14,59,16,76,4,6,8,12,34,41,11', '1,52,53,55,57,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,28,59,76', 1, 1, 0, '2009-02-16 08:23:14', ''),
				(2, 'Front End', 'Sample Group for Authors, Editors, Publishers', '', '19,20,21', '', '28,27,47,32,33,19,20,21,29,45,44,43,46,49;5,38,36,37,30,31,39,40,42;13,9,16,15,23,14,76', '1,57,5,9,13,14,15,16,28,76', 0, 2, 0, '0000-00-00 00:00:00', '');";
		
				$db->setQuery( $query );
				
				if( !$db->query() ){
					$mainframe->enqueueMessage( JText::_('Unable to insert Groups Table data!'), 'error' );
				}else{
					$mainframe->enqueueMessage( JText::_('Groups Table updated successfully.') );
				}
			}
		}
		if( !$install ){	
			$mainframe->redirect( 'index.php?option=com_jce&redirected=1' );
		}
	}
	function installPlugins( $install = false ){
		global $mainframe;
		$db =& JFactory::getDBO();
		
		$query = "CREATE TABLE IF NOT EXISTS `#__jce_plugins` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `title` varchar(100) NOT NULL,
		  `name` varchar(100) NOT NULL,
		  `type` varchar(100) NOT NULL DEFAULT 'plugin',
		  `icon` varchar(255) NOT NULL DEFAULT '',
		  `layout` varchar(255) NOT NULL,
		  `row` int(11) NOT NULL DEFAULT '0',
		  `ordering` int(11) NOT NULL DEFAULT '0',
		  `published` tinyint(3) NOT NULL DEFAULT '0',
		  `editable` tinyint(3) NOT NULL DEFAULT '0',
		  `elements` varchar(255) NOT NULL DEFAULT '',
		  `params` text NOT NULL,
		  `iscore` tinyint(3) NOT NULL DEFAULT '0',
		  `checked_out` int(11) NOT NULL DEFAULT '0',
		  `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
		  PRIMARY KEY (`id`),
		  UNIQUE KEY `plugin` (`name`)
		);";
		$db->setQuery( $query );
		
		if( !$db->query() ){
			$mainframe->enqueueMessage( JText::_('Unable to create Plugins Table!'), 'error' );
		}else{
			$mainframe->enqueueMessage( JText::_('Plugins table created successfully.') );

			$query = 'SELECT count(id) FROM #__jce_plugins';
			$db->setQuery( $query );
			
			if( !$db->loadResult() ){
				$query = "INSERT INTO `#__jce_plugins` (`id`, `title`, `name`, `type`, `icon`, `layout`, `row`, `ordering`, `published`, `editable`, `elements`, `params`, `iscore`, `checked_out`, `checked_out_time`) VALUES
				(1, 'Context Menu', 'contextmenu', 'plugin', '', '', 0, 21, 1, 0, '', '', 1, 0, '0000-00-00 00:00:00'),
				(2, 'Directionality', 'directionality', 'plugin', 'ltr,rtl', 'directionality', 3, 28, 1, 0, '', '', 1, 0, '0000-00-00 00:00:00'),
				(3, 'Emotions', 'emotions', 'plugin', 'emotions', 'emotions', 3, 26, 1, 0, '', '', 1, 0, '0000-00-00 00:00:00'),
				(4, 'Fullscreen', 'fullscreen', 'plugin', 'fullscreen', 'fullscreen', 3, 29, 1, 0, '', '', 1, 0, '0000-00-00 00:00:00'),
				(5, 'Paste', 'paste', 'plugin', 'pasteword,pastetext', 'paste', 2, 17, 1, 1, '', '', 1, 0, '0000-00-00 00:00:00'),
				(6, 'Preview', 'preview', 'plugin', 'preview', 'preview', 3, 31, 1, 0, '', '', 1, 0, '0000-00-00 00:00:00'),
				(7, 'Tables', 'table', 'plugin', 'tablecontrols', 'buttons', 3, 14, 1, 0, '', '', 1, 0, '0000-00-00 00:00:00'),
				(8, 'Print', 'print', 'plugin', 'print', 'print', 3, 27, 1, 0, '', '', 1, 0, '0000-00-00 00:00:00'),
				(9, 'Search Replace', 'searchreplace', 'plugin', 'search,replace', 'searchreplace', 2, 20, 1, 0, '', '', 1, 0, '0000-00-00 00:00:00'),
				(10, 'Styles', 'style', 'plugin', 'styleprops', 'style', 4, 18, 1, 0, '', '', 1, 0, '0000-00-00 00:00:00'),
				(11, 'Non-Breaking', 'nonbreaking', 'plugin', 'nonbreaking', 'nonbreaking', 4, 23, 1, 0, '', '', 1, 0, '0000-00-00 00:00:00'),
				(12, 'Visual Characters', 'visualchars', 'plugin', 'visualchars', 'visualchars', 4, 22, 1, 0, '', '', 1, 0, '0000-00-00 00:00:00'),
				(13, 'XHTML Xtras', 'xhtmlxtras', 'plugin', 'cite,abbr,acronym,del,ins,attribs', 'xhtmlxtras', 4, 19, 1, 0, '', '', 1, 0, '0000-00-00 00:00:00'),
				(14, 'Image Manager', 'imgmanager', 'plugin', 'imgmanager', 'imgmanager', 4, 32, 1, 1, '', '', 1, 0, '0000-00-00 00:00:00'),
				(15, 'Advanced Link', 'advlink', 'plugin', 'advlink', 'advlink', 4, 33, 1, 1, '', '', 1, 0, '0000-00-00 00:00:00'),
				(16, 'Spell Checker', 'spellchecker', 'plugin', 'spellchecker', 'spellchecker', 4, 24, 1, 1, '', '', 1, 0, '0000-00-00 00:00:00'),
				(17, 'Layers', 'layer', 'plugin', 'insertlayer,moveforward,movebackward,absolute', 'layer', 4, 13, 1, 0, '', '', 1, 0, '0000-00-00 00:00:00'),
				(18, 'Font ForeColour', 'forecolor', 'command', 'forecolor', 'forecolor', 2, 17, 1, 0, '', '', 1, 0, '0000-00-00 00:00:00'),
				(19, 'Bold', 'bold', 'command', 'bold', 'bold', 1, 2, 1, 0, '', '', 1, 0, '0000-00-00 00:00:00'),
				(20, 'Italic', 'italic', 'command', 'italic', 'italic', 1, 3, 1, 0, '', '', 1, 0, '0000-00-00 00:00:00'),
				(21, 'Underline', 'underline', 'command', 'underline', 'underline', 1, 4, 1, 0, '', '', 1, 0, '0000-00-00 00:00:00'),
				(22, 'Font BackColour', 'backcolor', 'command', 'backcolor', 'backcolor', 2, 18, 1, 0, '', '', 1, 0, '0000-00-00 00:00:00'),
				(23, 'Unlink', 'unlink', 'command', 'unlink', 'unlink', 2, 11, 1, 0, '', '', 1, 0, '0000-00-00 00:00:00'),
				(24, 'Font Select', 'fontselect', 'command', 'fontselect', 'fontselect', 1, 12, 1, 0, '', '', 1, 0, '0000-00-00 00:00:00'),
				(25, 'Font Size Select', 'fontsizeselect', 'command', 'fontsizeselect', 'fontsizeselect', 1, 13, 1, 0, '', '', 1, 0, '0000-00-00 00:00:00'),
				(26, 'Style Select', 'styleselect', 'command', 'styleselect', 'styleselect', 1, 10, 1, 0, '', '', 1, 0, '0000-00-00 00:00:00'),
				(27, 'New Document', 'newdocument', 'command', 'newdocument', 'newdocument', 1, 1, 1, 0, '', '', 1, 0, '0000-00-00 00:00:00'),
				(28, 'Help', 'help', 'plugin', 'help', 'help', 1, 10, 1, 0, '', '', 1, 0, '0000-00-00 00:00:00'),
				(29, 'StrikeThrough', 'strikethrough', 'command', 'strikethrough', 'strikethrough', 1, 5, 1, 0, '', '', 1, 0, '0000-00-00 00:00:00'),
				(30, 'Indent', 'indent', 'command', 'indent', 'indent', 2, 7, 1, 0, '', '', 1, 0, '0000-00-00 00:00:00'),
				(31, 'Outdent', 'outdent', 'command', 'outdent', 'outdent', 2, 6, 1, 0, '', '', 1, 0, '0000-00-00 00:00:00'),
				(32, 'Undo', 'undo', 'command', 'undo', 'undo', 2, 8, 1, 0, '', '', 1, 0, '0000-00-00 00:00:00'),
				(33, 'Redo', 'redo', 'command', 'redo', 'redo', 2, 9, 1, 0, '', '', 1, 0, '0000-00-00 00:00:00'),
				(34, 'Horizontal Rule', 'hr', 'command', 'hr', 'hr', 3, 2, 1, 0, '', '', 1, 0, '0000-00-00 00:00:00'),
				(35, 'HTML', 'html', 'command', 'code', 'code', 2, 16, 1, 0, '', '', 1, 0, '0000-00-00 00:00:00'),
				(36, 'Numbered List', 'numlist', 'command', 'numlist', 'numlist', 2, 5, 1, 0, '', '', 1, 0, '0000-00-00 00:00:00'),
				(37, 'Bullet List', 'bullist', 'command', 'bullist', 'bullist', 2, 4, 1, 0, '', '', 1, 0, '0000-00-00 00:00:00'),
				(38, 'Clipboard Actions', 'clipboard', 'command', 'cut,copy,paste', 'clipboard', 2, 1, 1, 0, '', '', 1, 0, '0000-00-00 00:00:00'),
				(39, 'Subscript', 'sub', 'command', 'sub', 'sub', 3, 5, 1, 0, '', '', 1, 0, '0000-00-00 00:00:00'),
				(40, 'Superscript', 'sup', 'command', 'sup', 'sup', 3, 6, 1, 0, '', '', 1, 0, '0000-00-00 00:00:00'),
				(41, 'Visual Aid', 'visualaid', 'command', 'visualaid', 'visualaid', 3, 4, 1, 0, '', '', 1, 0, '0000-00-00 00:00:00'),
				(42, 'Character Map', 'charmap', 'command', 'charmap', 'charmap', 3, 7, 1, 0, '', '', 1, 0, '0000-00-00 00:00:00'),
				(43, 'Justify Full', 'full', 'command', 'justifyfull', 'justifyfull', 1, 8, 1, 0, '', '', 1, 0, '0000-00-00 00:00:00'),
				(44, 'Justify Center', 'center', 'command', 'justifycenter', 'justifycenter', 1, 7, 1, 0, '', '', 1, 0, '0000-00-00 00:00:00'),
				(45, 'Justify Left', 'left', 'command', 'justifyleft', 'justifyleft', 1, 6, 1, 0, '', '', 1, 0, '0000-00-00 00:00:00'),
				(46, 'Justify Right', 'right', 'command', 'justifyright', 'justifyright', 1, 9, 1, 0, '', '', 1, 0, '0000-00-00 00:00:00'),
				(47, 'Remove Format', 'removeformat', 'command', 'removeformat', 'removeformat', 3, 3, 1, 0, '', '', 1, 0, '0000-00-00 00:00:00'),
				(48, 'Anchor', 'anchor', 'command', 'anchor', 'anchor', 2, 12, 1, 0, '', '', 1, 0, '0000-00-00 00:00:00'),
				(49, 'Format Select', 'formatselect', 'command', 'formatselect', 'formatselect', 1, 11, 1, 0, '', '', 1, 0, '0000-00-00 00:00:00'),
				(50, 'Image', 'image', 'command', 'image', 'image', 2, 13, 1, 0, '', '', 1, 0, '0000-00-00 00:00:00'),
				(51, 'Link', 'link', 'command', 'link', 'link', 2, 10, 1, 0, '', '', 1, 0, '0000-00-00 00:00:00'),
				(52, 'File Browser', 'browser', 'plugin', '', '', 0, 30, 1, 1, '', '', 1, 0, '0000-00-00 00:00:00'),
				(53, 'Inline Popups', 'inlinepopups', 'plugin', '', '', 0, 15, 1, 0, '', '', 1, 0, '0000-00-00 00:00:00'),
				(54, 'Media Support', 'media', 'plugin', '', '', 0, 12, 1, 1, '', '', 1, 0, '0000-00-00 00:00:00'),
				(55, 'Code Cleanup', 'cleanup', 'command', 'cleanup', 'cleanup', 2, 14, 1, 0, '', '', 1, 0, '0000-00-00 00:00:00'),
				(56, 'Safari Browser Support', 'safari', 'plugin', '', '', 0, 16, 1, 0, '', '', 1, 0, '0000-00-00 00:00:00'),
				(57, 'Advanced Code Editor', 'advcode', 'plugin', 'advcode', 'advcode', 4, 11, 1, 0, '', '', 1, 0, '0000-00-00 00:00:00'),
				(58, 'Article Breaks', 'article', 'plugin', 'readmore,pagebreak', 'article', 4, 1, 1, 1, '', '', 0, 0, '0000-00-00 00:00:00');";
				$db->setQuery( $query );
				
				if( !$db->query() ){
					$mainframe->enqueueMessage( JText::_('Unable to insert Plugins Table data!'), 'error' );
				}else{
					$mainframe->enqueueMessage( JText::_('Plugins table updated successfully.') );
				}
			}
		}
		// Extensions
		$query = "CREATE TABLE IF NOT EXISTS `#__jce_extensions` (
		`id` int(11) NOT NULL auto_increment,
		`pid` int(11) NOT NULL,
		`name` varchar(100) NOT NULL,
		`extension` varchar(255) NOT NULL,
		`folder` varchar(255) NOT NULL,
		`published` tinyint(3) NOT NULL,
		PRIMARY KEY  (`id`)
		)";
		$db->setQuery( $query );
		
		if( !$db->query() ){
			$mainframe->enqueueMessage( JText::_('Unable to create Extensions Table!'), 'error' );
		}else{
			$mainframe->enqueueMessage( JText::_('Extensions table created successfully.') );
			
			$query = 'SELECT count(id) FROM #__jce_extensions';
			$db->setQuery( $query );
			
			if( !$db->loadResult() ){		
				$query = "INSERT INTO `#__jce_extensions` (`id`, `pid`, `name`, `extension`, `folder`, `published`) VALUES
				(1, 15, 'Joomla Links for Advanced Link', 'joomlalinks', 'links', 1)";
				$db->setQuery( $query );
				
				if( !$db->query() ){
					$mainframe->enqueueMessage( JText::_('Unable to insert Extensions Table data!'), 'error' );
				}else{
					$mainframe->enqueueMessage( JText::_('Extensions table updated successfully.') );
				}
			}
		}
		if( !$install ){	
			$mainframe->redirect( 'index.php?option=com_jce&redirected=1' );
		}
	}
	function installEditor(){
		global $mainframe;
		$path = JPATH_PLUGINS .DS. 'editors';
		if( file_exists( $path .DS. 'jce.php' ) && file_exists( $path .DS. 'jce.xml' ) ){	
			// Sourced from various Joomla! core files including the installer plugin adapter			
			$xml =& JFactory::getXMLParser('Simple');
			$ini = '';		
			if( $xml->loadFile( $path .DS. 'jce.xml' ) ){
				$root =& $xml->document;				
				// Get the element of the tag names
				$element =& $root->getElementByPath('params');
				if (!is_a($element, 'JSimpleXMLElement') || !count($element->children())) {
					// Either the tag does not exist or has no children therefore we return zero files processed.
					return null;
				}			
				// Get the array of parameter nodes to process
				$params = $element->children();
				if (count($params) == 0) {
					// No params to process
					return null;
				}			
				// Process each parameter in the $params array.
				$ini = null;
				foreach ($params as $param) {
					if (!$name = $param->attributes('name')) {
						continue;
					}			
					if (!$value = $param->attributes('default')) {
						continue;
					}		
					$ini .= $name."=".$value."\n";
				}
			}
			
			$row =& JTable::getInstance('plugin');
			$row->name 		= 'JCE Editor 1.5.x';
			$row->ordering 	= 0;
			$row->folder 	= 'editors';
			$row->iscore 	= 0;
			$row->access 	= 0;
			$row->published = 1;
			$row->client_id = 0;
			$row->element 	= 'jce';
			$row->params 	= $ini;
			if (!$row->store()) {
			// Install failed, roll back changes
				$mainframe->redirect( 'index.php', JText::_('Plugin').' '.JText::_('Install').': '.$db->stderr(true) );
				return false;
			}
		}else{
			$mainframe->redirect( 'index.php', JText::_('Plugin files missing') );
			return false;
		}
		$mainframe->redirect( 'index.php?option=com_jce', JText::_('Editor successfully installed') );	
	}	
	function getOrderArray( $input, $listname, $itemKeyName = 'element', $orderKeyName = 'order' ) {
		parse_str( $input, $inputArray );
		$inputArray = $inputArray[$listname];
		$orderArray = array();
		for( $i=0; $i<count( $inputArray ); $i++ ) {
			$orderArray[] = array( $itemKeyName => $inputArray[$i], $orderKeyName => $i +1 );
		}
		return $orderArray;
	}
	function getAccessName( $id ){
		$db =& JFactory::getDBO();
		// get list of Groups for dropdown filter
		$query = 'SELECT name'
		. ' FROM #__core_acl_aro_groups'
		. ' WHERE id = '. $id
		. ' AND name != "ROOT"'
		. ' AND name != "USERS"'
		;
		$db->setQuery( $query );
		return $db->loadResult();
	}
	function accessList( $name, $access='', $size=1, $extra ){
		$db =& JFactory::getDBO();
		// get list of Groups for dropdown filter
		$query = 'SELECT id AS value, name AS text'
		. ' FROM #__core_acl_aro_groups'
		. ' WHERE id NOT IN (17,28,29,30)'
		;
		$db->setQuery( $query );
		$types[] = JHTML::_('select.option',  '0', '- '. JText::_( 'Select Access' ) .' -' );
		$i = '-';
		foreach( $db->loadObjectList() as $obj )
		{
			$types[] = JHTML::_('select.option', $obj->value, $i . JText::_( $obj->text ) );
			$i .= '-';
		}
		return JHTML::_('select.genericlist', $types, $name, 'class="inputbox" size="'. $size .'"'. $extra, 'value', 'text', $access );
	}
	function quickiconButton( $link, $image, $text, $disabled=false ){
		global $mainframe;
		$lang		=& JFactory::getLanguage();
		$template	= $mainframe->getTemplate();
		
		if( $disabled ){
			$link = '#';
		}				
		?>
		<div style="float:<?php echo ($lang->isRTL()) ? 'right' : 'left'; ?>;">
			<div class="icon">
				<a href="<?php echo $link; ?>">
					<?php echo JHTML::_('image.site',  $image, '/templates/'. $template .'/images/header/', NULL, NULL, $text ); ?>
					<span><?php echo $text; ?></span>
				</a>
			</div>
		</div>
        <?php
	}
	function getLanguage(){
		$language =& JFactory::getLanguage();
		$tag = $language->getTag();
		if( file_exists( JPATH_SITE .DS. 'language' .DS. $tag .DS. $tag .'.com_jce.xml' ) ){
			return substr( $tag, 0, strpos( $tag, '-' ) );
		}
		return 'en';
	}  
}
class jceToolbarHelper extends JToolbarHelper {
	function access( $alt = 'Plugin Access' ){
		$bar = & JToolBar::getInstance('toolbar');
		$bar->appendButton( 'Popup', 'lock', $alt, "index.php?option=com_jce&tmpl=component&type=plugin&task=access_popup", 400, 150 );
	}
	function popup( $alt, $icon, $type, $task, $width=750, $height=400 ){		
		$bar = & JToolBar::getInstance('toolbar');
		$bar->appendButton( 'Popup', $icon, $alt, "index.php?option=com_jce&tmpl=component&type=".$type."&task=".$task, $width, $height );
	}
	function config( $alt = 'Editor Config' ){
		$bar = & JToolBar::getInstance('toolbar');
		$bar->appendButton( 'Popup', 'config', $alt, "index.php?option=com_jce&tmpl=component&type=config&task=view", 700, 560 );
	}
	function help( $type, $alt='Help' ){
		jimport('joomla.plugin.helper');
		$plugin = JPluginHelper::getPlugin( 'editors', 'jce' );
		
		$url = 'http://www.joomlacontenteditor.net/index.php?option=com_content&tmpl=component&view=article&task=findkey';
				
		if( isset( $plugin->params ) ){
        	$params = new JParameter( $plugin->params );
			$url = $params->get( 'help', $url );
		}		
		$bar = & JToolBar::getInstance('toolbar');
		$bar->appendButton( 'Popup', 'help', $alt, $url. '&lang=' .JCEHelper::getLanguage(). '&keyref=admin.' .$type. '.view', 700, 560 );
	}
}
?>