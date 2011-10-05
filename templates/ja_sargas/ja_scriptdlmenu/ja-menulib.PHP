<?php
/* ja-menulib.php @copyright (C) 2005 Joomlart.com (formerly MamboTheme.com) */
defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );

class JAMenu{
	var $menuObj; 
	var $_params = null;
	var $_db = null;	
	var $children = null;
	var $open = null;
	
	function JAMenu( &$database, &$params ){
		$this->_params = $params;
		$this->_db = $database;

		$this->loadMenu();
		$this->createmenuObj($params->get( 'menuclass' ));
	}
	
	function createmenuObj ($jamenu){
			switch ($this->_params->get( 'menutype' )){
				default:
					include_once($this->_params->get( 'absPath' ) ."/ja-$jamenu.class.php");
					$this->menuObj = new $jamenu($this);
				break;
			}
	}
	
	function  loadMenu(){
		global $database, $my, $cur_template, $Itemid;
		global $mosConfig_absolute_path, $mosConfig_live_site, $mosConfig_shownoauth;

		if ($mosConfig_shownoauth) {
			$sql = "SELECT m.* FROM #__menu AS m"
			. "\nWHERE menutype='". $this->_params->get( 'menutype' ) ."' AND published='1'"
			. "\nORDER BY parent,ordering";
		} else {
			$sql = "SELECT m.* FROM #__menu AS m"
			. "\nWHERE menutype='". $this->_params->get( 'menutype' ) ."' AND published='1' AND access <= '$my->gid'"
			. "\nORDER BY parent,ordering";
		}
		$this->_db->setQuery( $sql );
		$rows = $this->_db->loadObjectList( 'id' );

		// establish the hierarchy of the menu
		$this->children = array();
		// first pass - collect children
		foreach ($rows as $v ) {
			$pt = $v->parent;
			$list = @$this->children[$pt] ? $this->children[$pt] : array();
			array_push( $list, $v );
			$this->children[$pt] = $list;
		}

		// second pass - collect 'open' menus
		$this->open = array( $Itemid );
		$count = 20; // maximum levels - to prevent runaway loop
		$id = $Itemid;
		while (--$count) {
			if (isset($rows[$id]) && $rows[$id]->parent > 0) {
				$id = $rows[$id]->parent;
				$this->open[] = $id;
			} else {
				break;
			}
		}
	}
		
	function genMenu(){
		$this->beginMenu();
		$this->menuObj->beginMenu();
		$this->genMenuItems (0, 0);
		$this->menuObj->endMenu();
		$this->endMenu();
	}
	
	/*
	$pid: parent id
	$level: menu level
	$pos: position of parent
	*/
	function genMenuItems($pid, $level) {
		if (@$this->children[$pid]) {
			$i = 0;
			foreach ($this->children[$pid] as $row) {				
				$this->menuObj->genMenuItemBegin( $row, $level, $i);

				// show menu with menu expanded - submenus visible
				$this->genMenuItems( $row->id, $level+1 );
				
				$this->menuObj->genMenuItemEnd( $row, $level, $i);
				$i++;
			}
		}
	}

	function beginMenu(){
	}
	function endMenu(){
	}
	function hasSubItems($id){
		if (@$this->children[$id]) return true;
		return false;
	}
}
?>