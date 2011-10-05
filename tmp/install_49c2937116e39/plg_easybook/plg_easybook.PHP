<?php
/**
 * Plugin: plg_easybook
 * 
 * Displays a link to EasyBook and shows unpublished entries
 * 
 * @version $Id: plg_easybook.php 720 2008-09-02 22:05:38Z elkuku $
 * @package EasyToolbar2
 * @subpackage Plugins
 * @author Nikolai Plath (elkuku)
 * Created on 01-Jan-2008
 */

/**
 * Main Plugin function : plg_easybook
 */
function plg_easybook( $config, $params )
{
	$img_src = $params['img_src'];
	$img_alt = JText::_($params['img_alt']);
	$text = ''; // Text to display - optional
	$a_href = 'index.php?option=com_easybook'; // URL - optional
	$a_target = ''; // TARGET - optional
	$accesskey = $params['accesskey']; // Acceskey - optional
	$tooltip_title =  JText::_($params['name']);//Tooltip title
	$tooltip_text =   JText::_($params['description']); // Text to appear in the tooltip

	//
	//
	// Here goes your custom code
	// ...
	//
	$db =& JFactory::getDBO();
	$query = 'SELECT COUNT(*)'
			.' FROM #__easybook'
			.' WHERE published=0';
	$db->setQuery($query);

	$unpublished_entries = $db->loadResult();

	if( $unpublished_entries > 0 )
	{
		$text = '<strong style="color: red;">'.$unpublished_entries.'</strong>';
		
		// or do whatever you want
		//...
	}

	/**
	 * Construct the Tooltip
	 */
	$tooltip = Tooltip( $tooltip_title, $tooltip_text, $img_src, $accesskey, $config );

	/**
	 * Return the Icon
	 */

	return Button( $img_src, $img_alt, $text,  $a_href, $a_target, $accesskey, $tooltip, $config  );
}// function
