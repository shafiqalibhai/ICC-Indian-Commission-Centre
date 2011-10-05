<?php

$ja_color = $ja_color_default;
$ja_width = $ja_width_default;
$ja_font_size = $ja_font_size_default;

getUserSetting();

function getUserSetting(){
	global $ja_color, $ja_font_size, $ja_width, $ja_menutype, $ja_template_name, $ja_template_absolute_path ;
	global $ja_font_size_default, $ja_color_default, $ja_width_default, $ja_tool;

	if (isset($_COOKIE['JATheme']) && $_COOKIE['JATheme'] == $ja_template_name){
		if (($ja_tool & 2) && isset($_COOKIE['ColorCSS']) && $_COOKIE['ColorCSS']){
			$ja_color = $_COOKIE['ColorCSS'];
		}
		if (($ja_tool & 1) && isset($_COOKIE['ScreenType'])){
			$ja_width = $_COOKIE['ScreenType'];
		}
		if (($ja_tool & 1) && isset($_COOKIE['FontSize'])){
			$ja_font_size = $_COOKIE['FontSize'];
		}
	}else{
		$exp = time() + 60*60*24*355;
		setcookie ('JATheme', $ja_template_name, $exp, '/');
		setcookie ('ColorCSS', $ja_color_default, $exp, '/');
		setcookie ('ScreenType', $ja_width_default, $exp, '/');
		setcookie ('FontSize', $ja_font_size_default, $exp, '/');
	}

	if (!is_file("$ja_template_absolute_path/css/colors/$ja_color.css")) $ja_color = $ja_color_default;
}

function getCurrentURL(){
	$cururl = mosGetParam( $_SERVER, 'REQUEST_URI', '' );
	if(($pos = strpos($cururl, "index.php"))!== false){
		$cururl = substr($cururl,$pos);
	}
	$cururl =  sefRelToAbs($cururl);
	$cururl =  ampReplace($cururl);
	return $cururl;
}

function genMenuHead(){
	global $ja_template_path,$ja_menutype, $ja_tool,$ja_font_size;
	$html = "";
	if ($ja_menutype == 1) {
		$html = '<link href="'.$ja_template_path.'/ja_splitmenu/ja-splitmenu.css" rel="stylesheet" type="text/css" />';
	}else if ($ja_menutype == 2) {
		$html = '<link href="'.$ja_template_path.'/ja_cssmenu/ja-sosdmenu.css" rel="stylesheet" type="text/css" />
					<script language="javascript" type="text/javascript" src="'. $ja_template_path.'/ja_cssmenu/ja.cssmenu.js"></script>';
	} else if ($ja_menutype == 3) {
		$html = '<link href="'. $ja_template_path .'/ja_transmenu/ja-transmenuh.css" rel="stylesheet" type="text/css" />
					<script language="javascript" type="text/javascript" src="'.$ja_template_path.'/ja_transmenu/ja-transmenu.js"></script>';
	} else if ($ja_menutype == 4) {
		$html = '<link href="'. $ja_template_path .'/ja_scriptdlmenu/ja-scriptdlmenu.css" rel="stylesheet" type="text/css" />
					<script language="javascript" type="text/javascript" src="'.$ja_template_path.'/ja_scriptdlmenu/ja-scriptdlmenu.js"></script>';
	}

	if ($ja_tool){
	?>
		<script type="text/javascript">
		var currentFontSize = <?php echo $ja_font_size; ?>;
		</script>
	<?php
	}
	echo $html;
}

function genColorHead(){
	global $ja_color_themes, $ja_color, $ja_template_path, $ja_tool;
	$html = '';
	foreach ($ja_color_themes as $ja_color_theme) {
		if ($ja_color == $ja_color_theme){
			$html .= '<link href="'.$ja_template_path.'/css/colors/'.$ja_color_theme.'.css" rel="stylesheet" type="text/css" title="'.$ja_color_theme.'" />'."\n";
		}else{
			if ($ja_tool & 2) //Load this css when color tool enabled
				$html .= '<link href="'.$ja_template_path.'/css/colors/'.$ja_color_theme.'.css" rel="alternate stylesheet" type="text/css" title="'.$ja_color_theme.'" />'."\n";
		}
	}
	echo $html;
}

function genToolMenu($jatool){
	global $ja_template_path,$ja_font_size_default, $ja_font_size, $ja_color_themes, $ja_width, $ja_color;
	if ($jatool & 1){//show screen tools
		?>
		<span class="ja-usertools">
	    <a href="#Narrow" onclick="setScreenType('narrow');return false;"><img title="Narrow screen" src="<?php echo $ja_template_path;?>/images/<?php echo $ja_color?>/user-screen1<?php echo ( ($ja_width=="narrow") ? "-hilite" : "" ) ?>.gif" alt="Narrow screen resolution" id="ja-tool-narrow" onclick="changeToolHilite(curtool, this);curtool=this;" /></a>
	    <a href="#Wide" onclick="setScreenType('wide');return false;"><img title="Wide screen" src="<?php echo $ja_template_path;?>/images/<?php echo $ja_color?>/user-screen2<?php echo ( ($ja_width=="wide") ? "-hilite" : "" ) ?>.gif" alt="Wide screen resolution" id="ja-tool-wide" onclick="changeToolHilite(curtool, this);curtool=this;" /></a>
		<a href="#Increase" onclick="changeFontSize(1); return false;"><img title="Increase font size" src="<?php echo $ja_template_path;?>/images/<?php echo $ja_color?>/user-increase.gif" alt="Increase font size" id="ja-tool-increase" /></a>
	    <a href="#Decrease" onclick="changeFontSize(-1); return false;"><img title="Decrease font size" src="<?php echo $ja_template_path;?>/images/<?php echo $ja_color?>/user-decrease.gif" alt="Decrease font size" id="ja-tool-decrease" /></a>
	    <a href="#Default" onclick="revertStyles(<?php echo $ja_font_size_default;?>); return false;"><img title="Default size" src="<?php echo $ja_template_path;?>/images/<?php echo $ja_color?>/user-reset.gif" alt="Default font size" id="ja-tool-reset" /></a>
    </span>
	<script type="text/javascript">
	var curtool = document.getElementById('<?php echo "ja-tool-$ja_width"; ?>');
	</script>
		<?php
	}
	if ($jatool & 2){//show color tools
		?>
    <span class="ja-colorthemes">
	<?php
 	foreach ($ja_color_themes as $ja_color_theme) {
	?>
     	<a href="#<?php echo $ja_color_theme;?>" onclick="setActiveStyleSheet('<?php echo $ja_color_theme;?>');"><img style="cursor: pointer;" src="<?php echo $ja_template_path;?>/images/<?php echo $ja_color?>/<?php echo $ja_color_theme;?><?php echo ( ($ja_color==$ja_color_theme) ? "-hilite" : "" ) ?>.gif" title="<?php echo $ja_color_theme;?> color" alt="<?php echo $ja_color_theme;?> color" id="ja-tool-<?php echo $ja_color_theme;?>color" onclick="changeToolHilite(curcolor, this);curcolor=this;return false;" /></a>
	<?php
	}
	?>
    </span>
	<script type="text/javascript">
	var curcolor = document.getElementById('<?php echo ( ($ja_color=="") ? "ja-tool-defaultcolor" : "ja-tool-{$ja_color}color" ) ?>');
	</script>
		<?php
	}
}

function ja_loadHeader($position){
	global $ja_template_path;
	$filename =  ja_getImageSrc($position);
	if ($filename) {
		echo $filename;
	}
}

function ja_getImageSrc ($position){
	global $ja_template_path, $ja_template_absolute_path;
	if (isset( $GLOBALS['_MOS_MODULES'][$position] )) {
		$modules = $GLOBALS['_MOS_MODULES'][$position];
	} else {
		$modules = array();
	}
	foreach ($modules as $module){
		$filename = $module->title;
		$regex = '/(\.gif)|(.jpg)|(.png)|(.bmp)$/i';
		if (is_file($ja_template_absolute_path."/images/header/".$filename) && preg_match($regex, $filename)) {
			return "$ja_template_path/images/header/" . $filename;
		}
	}
	return "";
}

function getCurrentMenuIndex(){
	global $Itemid, $database, $mosConfig_shownoauth, $my;
	//Get top menu id;
	$id = $Itemid;
	$menutype = 'mainmenu';
	$ordering = '0';
	while (1){
		$sql = "select parent, menutype, ordering from #__menu where id = $id limit 1";
		$database->setQuery($sql);
		$row = null;
		$database->loadObject($row);
		if ($row) {
			$menutype = $row->menutype;
			$ordering = $row->ordering;
			if ($row->parent > 0)
			{
				$id = $row->parent;
			}else break;
		}else break;
	}
	if ($mosConfig_shownoauth) {
		$sql = "SELECT count(*) FROM #__menu AS m"
		. "\nWHERE menutype='". $menutype ."' AND published='1' AND parent=0 and ordering < $ordering";
	} else {
		$sql = "SELECT count(*) FROM #__menu AS m"
		. "\nWHERE menutype='". $menutype ."' AND published='1' AND access <= '$my->gid' AND parent=0 and ordering < $ordering";
	}
	$database->setQuery($sql);

	return $database->loadResult();
}
?>
