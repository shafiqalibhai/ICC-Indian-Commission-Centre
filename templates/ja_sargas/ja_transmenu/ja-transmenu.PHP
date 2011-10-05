<?php
defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );

class TransMenu{
	var $parent = null;
	function TransMenu(&$parent){
		$this->parent = $parent;
	}
	function getParam($paramName){
		return $this->parent->_params->get($paramName);
	}
	function beginMenu(){
		//if ($this->getParam('menu_style') == 'vertical')
		//	$cssfile = "transmenuv.css";
		//else
		//	$cssfile = "transmenuh.css";
		//echo '
		//	<link rel="stylesheet" type="text/css" href="',$this->getParam('LSPath'),'/',$cssfile,'" />
		//	<script language="javascript" src="',$this->getParam('LSPath'),'/transmenu.js"></script>
		//';
			$direction = "TransMenu.direction.".$this->getParam('menu_direction');
			$position = "TransMenu.reference.".$this->getParam('menu_position');
			$top = $this->getParam('p_t');
			$left = $this->getParam('p_l');
			$subpad_x = $this->getParam('subpad_x');
			$subpad_y = $this->getParam('subpad_y');
		switch ($this->getParam('menu_style')){
			case 'vertical':
				echo '<ul id="ja-transmenu">';
				foreach ($this->parent->children[0] as $v) {
					echo "<li>";
					echo $this->getFirstLevelItem($v);
					echo "</li>";
				}
				echo '</ul>';
			break;
			
			case 'horizontal':
			default:
				echo '<ul id="ja-transmenu">';
				foreach ($this->parent->children[0] as $v) {
					echo "<li>";
					echo $this->getFirstLevelItem($v);
					echo "</li>";
				}
				echo '</ul>';

			break;
		}
		echo '
			<script type="text/javascript" language="javascript">
			if (TransMenu.isSupported()) {
				TransMenu.updateImgPath(\'',$this->getParam('LSPath'),'/\');
				var ms = new TransMenuSet(',$direction,', ', $left,', ',$top,', ',$position,');
				TransMenu.subpad_x = ',$subpad_x,';
				TransMenu.subpad_y = ',$subpad_y,';

			';
	}
	function endMenu(){
		echo '
				TransMenu.renderAll();
			}
			init1=function(){TransMenu.initialize();}
			if (window.attachEvent) {
				window.attachEvent("onload", init1);
			}else{
				TransMenu.initialize();			
			}
			</script>
		';
	}
	function genMenuItem(&$row, $level, $pos){

		global $Itemid, $mosConfig_live_site, $mainframe;
		$txt = '';

		switch ($row->type) {
			case 'separator':
			case 'component_item_link':
			break;
			case 'content_item_link':
			$temp = split("&task=view&id=", $row->link);
			$row->link .= '&Itemid='. $mainframe->getItemid($temp[1]);
			break;
			case 'url':
			if ( eregi( 'index.php\?', $row->link ) ) {
				if ( !eregi( 'Itemid=', $row->link ) ) {
					$row->link .= '&Itemid='. $row->id;
				}
			}
			break;
			case 'content_typed':
			default:
			$row->link .= '&Itemid='. $row->id;
			break;
		}

		//$row->link = ampReplace( $row->link );

		if ( strcasecmp( substr( $row->link,0,4 ), 'http' ) ) {
			$row->link = sefRelToAbs( $row->link );
		}

		
		//echo "$row->name $row->link $level<br>";
		if ($level){
			$pmenu = "tmenu$row->parent";
			//echo "$pmenu.addItem(\"$row->name\", \"$row->link\");\n";
			$active = 0;
			if ( in_array($row->id, $this->parent->open) ) $active = 1;

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
			$txt = str_replace("\"", "\\\"", $txt);
	
			
			echo "$pmenu.addItem(\"$txt\", \"$row->link\", $row->browserNav, $active);\n";
		}else{
			$pmenu = "ms";
		}
		$cmenu = "tmenu$row->id";
		$idmenu = "menu$row->id";
		if ($this->parent->hasSubItems($row->id)){
			if ($level == 0){
				echo "var $cmenu = ".$pmenu.".addMenu(document.getElementById(\"$idmenu\"));\n";
			}else{
				echo "var $cmenu = ".$pmenu.".addMenu(".$pmenu.".items[".$pos."]);\n";
			}
		}else{
			if ($level == 0){
				echo '
				document.getElementById("',$idmenu,'").onmouseover = function() {
					',$pmenu,'.hideCurrent();
				}
				';
			}
		}
	}

	function getFirstLevelItem( $mitem ) {
		global $Itemid, $mosConfig_live_site, $mainframe;
		$txt = '';

		switch ($mitem->type) {
			case 'separator':
			case 'component_item_link':
			break;
			case 'content_item_link':
			$temp = split("&task=view&id=", $mitem->link);
			$mitem->link .= '&Itemid='. $mainframe->getItemid($temp[1]);
			break;
			case 'url':
			if ( eregi( 'index.php\?', $mitem->link ) ) {
				if ( !eregi( 'Itemid=', $mitem->link ) ) {
					$mitem->link .= '&Itemid='. $mitem->id;
				}
			}
			break;
			case 'content_typed':
			default:
			$mitem->link .= '&Itemid='. $mitem->id;
			break;
		}

		$id = 'id="menu'.$mitem->id.'"';

		//$mitem->link = ampReplace( $mitem->link );

		if ( strcasecmp( substr( $mitem->link,0,4 ), 'http' ) ) {
			$mitem->link = sefRelToAbs( $mitem->link );
		}
		
		if (@$this->parent->children[$mitem->id]) $menuclass = "havechild-";
		else $menuclass = "";

		// Active Menu highlighting
		$current_itemid = trim( mosGetParam( $_REQUEST, 'Itemid', 0 ) );
		if ( in_array($mitem->id, $this->parent->open) ) {
			$menuclass .= 'mainlevel-active'. $this->getParam( 'class_sfx' );
		} else {
			$menuclass .= 'mainlevel'. $this->getParam( 'class_sfx' );
		}

		$txt = $mitem->name;
		if ( $this->getParam( 'menu_images' ) ) {
			$menu_params = new stdClass();
			$menu_params =& new mosParameters( $mitem->params );
			$menu_image = $menu_params->def( 'menu_image', -1 );
			if ( ( $menu_image <> '-1' ) && $menu_image ) {
				$image = '<img src="'. $mosConfig_live_site .'/images/stories/'. $menu_image .'" border="0" alt="'. $mitem->name .'"/>';
				if ( $this->getParam( 'menu_images_align' ) ) {
					$txt = $txt .' '. $image;
				} else {
					$txt = $image .' '. $txt;
				}
			}
		}

		$mitem->link = ampReplace( $mitem->link );

		$title = " title=\"{$mitem->name}\"";

		switch ($mitem->browserNav) {
			// cases are slightly different
			case 1:
			// open in a new window
			$txt = '<a href="'. $mitem->link .'" target="_blank" class="'. $menuclass .'" '. $id.$title.'><span>'. $txt .'</span></a>';
			break;

			case 2:
			// open in a popup window
			$txt = "<a href=\"#\" onclick=\"javascript: window.open('". $mitem->link ."', '', 'toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=780,height=550'); return false\" class=\"$menuclass\" ". $id .$title."><span>". $txt ."</span></a>\n";
			break;

			case 3:
			// don't link it
			$txt = '<a href="#" class="'. $menuclass .'" '. $id .$title.'><span>'. $txt .'</span></a>';
			break;

			default:	// formerly case 2
			// open in parent window
			$txt = '<a href="'. $mitem->link .'" class="'. $menuclass .'" '. $id .$title.'><span>'. $txt .'</span></a>';
			break;
		}

		return $txt;
	}
}
?>
