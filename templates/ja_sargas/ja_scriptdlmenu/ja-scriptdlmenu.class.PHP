<?php
defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );

class scriptdlmenu{
	var $parent = null;
	var $main = "";
	var $sub = "";
	function scriptdlmenu(&$parent){
		$this->parent = $parent;
	}
	function getParam($paramName){
		return $this->parent->_params->get($paramName);
	}
	function beginMenu(){
		
	}
	function endMenu(){
		$this->main = "<div id=\"jasdl-mainnav\"><ul>\n". $this->main . "</ul></div>";
		if ($this->sub != "") {
			$this->sub = "<div id=\"jasdl-subnav\">\n". $this->sub . "\n</div>\n";
		} else {
			$this->sub = "<div id=\"jasdl-subnav\">\n</div>\n";
		}
		$this->sub .= '
		<script type="text/javascript">
			var jasdl_activemenu = new Array('. ( (count($this->parent->open) == 1) ? "\"{$this->parent->open[0]}\"" : implode(",", array_reverse($this->parent->open)) ) .');
		</script>';
	}
	
	function genMenuItemBegin( &$row, $level, $pos ) {
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
		$txt = "<span>$txt</span>";
		
		$title = " title=\"{$row->name}\"";
		switch ($row->browserNav) {
			// cases are slightly different
			case 1:
			// open in a new window
			$txt = "<a href=\"". $row->link ."\" target=\"_blank\" $title>". $txt . '</a>' ;
			break;

			case 2:
			// open in a popup window
			$txt = "<a href=\"#\" onclick=\"javascript: window.open('". $row->link ."', '', 'toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=780,height=550'); return false\" $title>". $txt . '</a>' ;
			break;

			case 3:
			// don't link it
			$txt = "<a href=\"#\"> $title". $txt . "</a>" ;
			break;

			default:	// formerly case 2
			// open in parent window
			$txt = "<a href=\"". $row->link ."\" $title>". $txt . "</a>" ;
			break;
		}
		
		$indent = $level*3+2;
		
		$lastitem = ($pos == count (@$this->parent->children[$row->parent])-1);

		if ($level == 0) {
			@$this->parent->children[$row->id]? $class = " class=\"havechild\"" : "";
			$this->main .= "<li id=\"jasdl-mainnav{$row->id}\"".$class.">$txt</li>\n";
			if (@$this->parent->children[$row->id]) {
				$this->sub .= "<ul id=\"jasdl-subnav{$row->id}\">\n";
				//$this->sub .= "<li class=\"spacer\">&nbsp;</li>";
			}
		} else if ($level == 1) {
			$class = $lastitem ? " class=\"menuitemend\"" : "";
			$this->sub .= "<li id=\"jasdl-subnavitem{$row->id}\"$class>$txt</li>\n";
		}
	}
	
	function genMenuItemEnd( &$row, $level, $pos ) {
		if ($level == 0) {
			if (@$this->parent->children[$row->id]) 
				$this->sub .= "</ul>";
		} else {
		
		}
	}
	
	function indent( $level ) {
		$r = "";
		for ($i=0;$i<$level;++$i) { $r .= "\t"; }
		return $r;
	}
}
?>