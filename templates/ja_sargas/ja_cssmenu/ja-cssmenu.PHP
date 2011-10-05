<?php
defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );

class CSSMenu{
	var $parent = null;
	function CSSMenu(&$parent){
		$this->parent = $parent;
	}
	function getParam($paramName){
		return $this->parent->_params->get($paramName);
	}
	function beginMenu(){
		echo "<ul id=\"ja-cssmenu\" class=\"clearfix\">\n";
	}
	function endMenu(){
		echo "\n\n</ul>";
	}
	function genMenuItem(&$row, $level, $pos){

		global $Itemid, $mosConfig_live_site, $mainframe;
		$txt = '';

		switch ($row->type) {
			case 'separator':
			case 'component_item_link':
				break;
				
			case 'url':
				if ( eregi( 'index.php\?', $row->link ) ) {
					if ( !eregi( 'Itemid=', $row->link ) ) {
						$row->link .= '&Itemid='. $row->id;
					}
				}
				break;
				
			case 'content_item_link':
			case 'content_typed':
				// load menu params
				$menuparams = new mosParameters( $row->params, $mainframe->getPath( 'menu_xml', $row->type ), 'menu' );
				
				$unique_itemid = $menuparams->get( 'unique_itemid', 1 );
				
				if ( $unique_itemid ) {
					$row->link .= '&Itemid='. $row->id;
				} else {
					$temp = split('&task=view&id=', $row->link);
					
					if ( $row->type == 'content_typed' ) {
						$row->link .= '&Itemid='. $mainframe->getItemid($temp[1], 1, 0);
					} else {
						$row->link .= '&Itemid='. $mainframe->getItemid($temp[1], 0, 1);
					}
				}
				break;

			default:
				$row->link .= '&Itemid='. $row->id;
				break;
		}

		$row->link = ampReplace( $row->link );

		if ( strcasecmp( substr( $row->link,0,4 ), 'http' ) ) {
			$row->link = sefRelToAbs( $row->link );
		}
		$active = in_array($row->id, $this->parent->open) ? "class = \"active\"" : "";
		
		$id = 'id="menu'.$row->id.'"';
		
		$txt = $row->name;
		if ( $this->getParam( 'menu_images' ) ) {
			$menu_params = new stdClass();
			$menu_params =& new mosParameters( $row->params );
			$menu_image = $menu_params->def( 'menu_image', -1 );
			if ( ( $menu_image <> '-1' ) && $menu_image ) {
				$image = '<img src="'. $mosConfig_live_site .'/images/stories/'. $menu_image .'" border="0" alt="'. $row->name .'"/>';
				if ( $this->getParam( 'menu_images_align' ) ) {
					$txt = $txt .' '. $image;
				} else {
					$txt = $image .' '. $txt;
				}
			}
		}
		$title = " title=\"{$row->name}\"";
		
		switch ($row->browserNav) {
			// cases are slightly different
			case 1:
			// open in a new window
			$txt = '<a href="'. $row->link .'" target="_blank" '.$active.' '. $id .$title.'><span>'. $txt .'</span></a>';
			break;

			case 2:
			// open in a popup window
			$txt = "<a href=\"#\" onclick=\"javascript: window.open('". $row->link ."', '', 'toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=780,height=550'); return false\"  ".$active." ". $id .$title."><span>". $txt ."</span></a>\n";
			break;

			case 3:
			// don't link it
			$txt = '<a href=\"#\" '.$active.' '. $id .$title.'><span>'. $txt .'</span></a>';
			break;

			default:	// formerly case 2
			// open in parent window
			$txt = '<a href="'. $row->link .'" '.$active.' '. $id .$title.'><span>'. $txt .'</span></a>';
			break;
		}
		
		echo $txt;
	}
}
?>
