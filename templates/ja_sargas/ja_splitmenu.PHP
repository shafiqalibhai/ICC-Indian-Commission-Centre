<?php
	
	defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );
	
	$hilightid = -1;
	$menuname = "";
	$color = "default"; // default color for orphan (no parent menu) links
	$tab_index = 0;
	
	define( 'JA_CURRENT_MENU', "active_menu");
	
	if (!defined('_JA_SUBMENU_')) {
		define('_JA_SUBMENU_', 1);
		
		function ja_topNav( $menu, $colors ) {
			global $mosConfig_absolute_path, $mosConfig_shownoauth, $my, $database, $Itemid, $hilightid;
			
			$r = "";
			
			if ($mosConfig_shownoauth) {
				$sql = "SELECT * FROM #__menu"
				. "\nWHERE menutype='". $menu ."' AND published='1' AND parent=0"
				. "\nORDER BY ordering";
			} else {
				$sql = "SELECT * FROM #__menu"
				. "\nWHERE menutype='". $menu ."' AND published='1' AND access <= '". $my->gid. "' AND parent=0"
				. "\nORDER BY ordering";
			}
			
			$database->setQuery( $sql );
			$topmenu = $database->loadObjectList( 'id' );
			
			$sql = "SELECT * FROM #__menu"
			. "\nWHERE menutype='". $menu ."' AND published='1'"; 
			$database->setQuery( $sql );
			$subrows = $database->loadObjectList( 'id' );
			$recurse = 5;
			$parentid = $Itemid;
			while ($recurse-- > 0) {
				$parentid = ja_getParentRow($subrows, $parentid);
				if (isset($parentid) && $parentid >= 0 && $subrows[$parentid]) {
					$hilightid = $parentid;
				} else {
					break;	
				}
			}
			
			$links = array();
			$i = 0;
			$c = count ($topmenu);
			foreach ($topmenu as $menuitem) {
				$i++;
				$hilight = ($menuitem->id == $hilightid);
				$links[] = ja_getSubmenu( $menuitem, 0, $colors, $hilight, true);
			}
			
			$menuclass = 'mainlevel';
			if (count( $links )) {
				$r .= '<ul id="ja-splitmenu" class="'. $menuclass .'">';
				foreach ($links as $link) {
					$r .= $link;
				}
				$r .= '</ul>';			
			}
			
			return $r;
		}
		
		function ja_getParentRow($rows, $id) {
			if (isset($rows[$id]) && $rows[$id]) {
				if($rows[$id]->parent > 0) {
					return $rows[$id]->parent;
				}	
			}
			return -1;
		}
		
		function ja_getSubmenu( $menuitem, $depth, $colors = null, $hilight = false, $color_index = false, $lastitem = false) {
			global $mainframe, $tab_index, $color, $hilightid, $menuname;
			$r = "";
			$id = "";
		
			switch ($menuitem->type) {
				case 'separator':
				case 'component_item_link':
					break;
					
				case 'url':
					if ( eregi( 'index.php\?', $menuitem->link ) ) {
						if ( !eregi( 'Itemid=', $menuitem->link ) ) {
							$menuitem->link .= '&Itemid='. $menuitem->id;
						}
					}
					break;
					
				case 'content_item_link':
				case 'content_typed':
					// load menu params
					$menuparams = new mosParameters( $menuitem->params, $mainframe->getPath( 'menu_xml', $menuitem->type ), 'menu' );
					
					$unique_itemid = $menuparams->get( 'unique_itemid', 1 );
					
					if ( $unique_itemid ) {
						$menuitem->link .= '&Itemid='. $menuitem->id;
					} else {
						$temp = split('&task=view&id=', $menuitem->link);
						
						if ( $menuitem->type == 'content_typed' ) {
							$menuitem->link .= '&Itemid='. $mainframe->getItemid($temp[1], 1, 0);
						} else {
							$menuitem->link .= '&Itemid='. $mainframe->getItemid($temp[1], 0, 1);
						}
					}
					break;

				default:
					$menuitem->link .= '&Itemid='. $menuitem->id;
					break;
			}
			
			if ($color_index) {
				$id .= $colors[($tab_index)%count($colors)];
				$tab_index++;
			}
			
			$current_itemid = trim( mosGetParam( $_REQUEST, 'Itemid', 0 ) );
			if ( !$current_itemid && !$hilight ) {
				//$id = '';
			} else if ($hilight || ($current_itemid == $menuitem->id)) {
				if ($depth == 0) {
					$color = $id;
					$menuname = $menuitem->name;
					$hilightid = $menuitem->id;
				} 
				$id = JA_CURRENT_MENU;
			}

			if ($id == JA_CURRENT_MENU) {
				$id = $lastitem ? ' class="' . $id . ' menuitemend"':' class="' . $id . '"';
			} else {
				$id = $lastitem ? ' class="menuitemend"':"";
			}
			$menuitem->link = ampReplace( $menuitem->link );
			if ( strcasecmp( substr( $menuitem->link,0,4 ), 'http' ) ) {
				$menuitem->link = sefRelToAbs( $menuitem->link );
			}
			       
			$title = "title=\"{$menuitem->name}\"";
			switch ($menuitem->browserNav) {
				case 1:
					// open in a new window
					$r = '<li'. $id . '><a href="'. $menuitem->link .'" target="_blank" '.$title.'><span>'. $menuitem->name ."</span></a></li>\n";
					break;
				case 2:
					// open in a popup window
					$r = "<li". $id . "><a href=\"#\" onclick=\"javascript: window.open('". $menuitem->link ."', '', 'toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=780,height=550'); return false\" $title><span>". $menuitem->name ."</span></a></li>\n";
					break;
				case 3:
					// don't link it
					$r = '<li'. $id . '><span class="seperator">'. $menuitem->name ."</span></li>\n";
					break;
				default:
					// open in parent window
					$r = '<li'. $id . '><a href="'. $menuitem->link .'" '.$title.'><span>'. $menuitem->name ."</span></a></li>\n";
					break;
			}
			
			return $r;
		}
		
		function ja_subNav($menutype, $pre = NULL, $post = NULL) {
			global $my, $mosConfig_shownoauth, $Itemid, $database;
		
			$sub = "";			
			$user = 0;
			if ($my->gid) {
				switch ($my->usertype) {
					case "Super Administrator":
						$user = 0;
						break;
					case "Administrator":
						$user = 1;
						break;
					case "Editor":
						$user = 2;
						break;
					case "Registered":
						$user = 3;
						break;
					case "Author":
						$user = 4;
						break;
					case "Publisher":
						$user = 5;
						break;
					case "Manager":
						$user = 6;
						break;
				}
			}
			
			if ($mosConfig_shownoauth) {
				$sql = "SELECT * FROM #__menu"
				. "\nWHERE menutype='". $menutype ."' AND published='1'"
				. "\nAND parent > 0"
				. "\nORDER BY parent,ordering";
			} else {
				$sql = "SELECT * FROM #__menu"
				. "\nWHERE menutype='". $menutype ."' AND published='1' AND access <= '$my->gid'"
				. "\nAND parent > 0"
				. "\nORDER BY parent,ordering";
			}
			
			$database->setQuery( $sql );
			$rows = $database->loadObjectList( 'id' );
			
			$subs = array();
			foreach ($rows as $v ) {
				$pt = $v->parent;
				$list = @$subs[$pt] ? $subs[$pt] : array();
				array_push( $list, $v );
				$subs[$pt] = $list;
			}
			
			$open = array( $Itemid );
			$count = 20; // maximum levels - to prevent runaway loop
			$id = $Itemid;
			while (--$count) {
				if (isset($rows[$id]) && $rows[$id]->parent > 0) {
					$id = $rows[$id]->parent;
					$open[] = $id;
				} else {
					break;
				}
			}
			
			if (isset($subs[$id]) && $subs[$id]) {
				$sub = ja_findSubmenu( $id, 1, $subs, $open);
			}
			
			return $sub;
		}
		
		function ja_findSubmenu( $id, $depth, &$subs, &$open) {
			global $Itemid, $menuname;
			
			$r = "";
			$sub_class = "submenu";
			
			if (@$subs[$id] && $depth<2) {
				//$n = min( $depth, count( $indents )-1 );
				if ($depth == 1 ) {
					$r .= "<ul class=\"" . $sub_class . "\">\n";
				} else {
					$r .= "<ul>\n";
				}

				$i = 0;
				$c = count ($subs[$id]);
				foreach ($subs[$id] as $row) {
					$i++;
					$r .= ja_getSubmenu( $row, $depth, null, false, false, $i == $c );
					if ( in_array( $row->id, $open )) {
						$r .= ja_findSubmenu( $row->id, $depth+1, $subs, $open );
					}
				}
				$r .= "</ul>\n";

			}
			return $r;
		}
	}	
?>