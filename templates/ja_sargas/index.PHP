<?php
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

/*echo "<?xml version=\"1.0\" encoding=\"'. $iso[1] .'\"?' .'>";*/
global $ja_color_themes, $ja_header_images, $ja_width_default, $ja_color_default, $ja_font_size_default, $ja_tool, $ja_menutype, $ja_template_path, $ja_template_absolute_path, $ja_headerimg, $ja_color, $ja_width, $ja_font_size, $ja_template_name;

$ja_template_name = 'ja_sargas';

# TEMPLATE CONFIGURATIONS ##########
####################################

#support extra color themes, font styles
$ja_color_themes = array('default','green','orange'); // You can add more color array if needed
#Header-deco images: you can add more images. 
$ja_header_images = array('leftcol-deco1.gif','leftcol-deco2.gif','leftcol-deco3.gif'); // Each sub header image will be assigned to each menu 
####################################

# Change the width of the template
$ja_width_default = 'wide'; // 'narrow': 800x600; 'wide': 1024x768;

# default color
$ja_color_default = 'default'; //blank for default, else pick one of in extra color themes $ja_color_themes

#font size default
$ja_font_size_default = 3;

# Enable users option
$ja_tool = 3; // 0: disable all; 1: disable color, enable screen; 2: enable color, disable screen; 3: enable all.

# Choose your prefer Menu Type
$ja_menutype = 1; 

# END: TEMPLATE CONFIGURATIONS ##########

# Define the template path ##########
$ja_template_path = $mosConfig_live_site.'/templates/'.$ja_template_name;
$ja_template_absolute_path = $mosConfig_absolute_path.'/templates/'.$ja_template_name;
include ($ja_template_absolute_path."/ja_templatetools.php");

$ja_headerimg = count($ja_header_images)? $ja_header_images [getCurrentMenuIndex() % count($ja_header_images)]:'';

$topnav = "";
$subnav = "";
if ($ja_menutype == 1) {
	require($ja_template_absolute_path."/ja_splitmenu.php");
	$topnav = ja_topNav('mainmenu', array('default'));
	$subnav = ja_subNav('mainmenu');
} else if ($ja_menutype == 4) {
	require($ja_template_absolute_path."/ja_scriptdlmenu.php");
	$topnav = $jamenu->menuObj->main;
	$subnav = $jamenu->menuObj->sub;
}

# Auto Collapse Divs Functions ##########
$ja_left = mosCountModules( 'left' );
$ja_right = mosCountModules( 'right' );

if ( $ja_left && $ja_right ) {
	$divid = '';
	$divwrapid = '';
	} elseif ( $ja_left ) { 
	$divid = '-fr';
	$divwrapid = '';
	} elseif ( $ja_right ) {
	$divid = '-fl';
	$divwrapid = '-fl';
	} else {
	$divid = '-f';
	$divwrapid = '-f';
}

# Top Spotlight ##########
$topmodule = 0;
if (mosCountModules('user8')) $topmodule++;
if (mosCountModules('user9')) $topmodule++;

if ($topmodule == 2) {
	$divtopwidth = '48.5%';
} else if ($topmodule == 1) {
	$divtopwidth = '100%';
}

$user8divstyle = ""; 
$user9divstyle = "box-ended"; 
if ( !mosCountModules('user9') ) {
	$user8divstyle = "box-ended"; 
}

# Bottom Spotlight ##########
$bottommodule = 0;
if (mosCountModules('user1')) $bottommodule++;
if (mosCountModules('user2')) $bottommodule++;
if (mosCountModules('top')) $bottommodule++;

if ( $bottommodule == 3 ) {
	$divbotwidth = '32%';
} else if ($bottommodule == 2) {
	$divbotwidth = '49%';
} else if ($bottommodule == 1) {
	$divbotwidth = '99%';
}

$user1divstyle = ""; 
$user2divstyle = ""; 
$topdivstyle = " box-ended"; 
if ( !mosCountModules('top') ) {
	$user2divstyle = " box-ended"; 
}
if ( !mosCountModules('user2') ) {
	$user1divstyle = " box-ended"; 
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
<jdoc:include type="head" />

<link href="<?php echo $ja_template_path;?>/css/template_css.css" rel="stylesheet" type="text/css" />
<script language="javascript" type="text/javascript" src="<?php echo $this->baseurl ?>/scripts/ja.script.js"></script>
<link href="<?php echo $this->baseurl ?>/css/colors/<?php echo $ja_color; ?>.css" rel="stylesheet" type="text/css" />
<?php genMenuHead(); ?>
</head>

<body id="bd" class="<?php echo "$ja_width fs".$ja_font_size;?>">

<!-- BEGIN: HEADER -->
<a name="Top" id="Top"></a>

<div id="ja-headerwrap">
	<div id="ja-header" class="clearfix">
		<h1>
			<a href="index.php">
			Indian Commission Centre
			</a>
            <span>
            	Solution For Every Official Identity Application
            </span>
		</h1>

		<div id="ja-sh">
		<div id="ja-usertoolswrap">
			<div id="ja-usertools">
							</div>
		</div>
	
		<!-- BEGIN: MAIN NAVIGATION -->
		<div id="ja-mainnav">
			<?php
				switch ($ja_menutype) {
					case 1: echo $topnav;
					break;
					}
			?>
			</div>
		<!-- END: MAIN NAVIGATION -->
		</div>

	</div>
</div>
<!-- END: HEADER -->

<div id="ja-containerwrap">
<div id="ja-container<?php echo $divwrapid; ?>" class="clearfix">

	<!-- BEGIN: CONTENT -->
	<div id="ja-mainbody<?php echo $divid; ?>">
	<a name="Content" id="Content"></a>

	<div id="ja-contentwrap">
		
		
			
<br/><br/>
		<div id="ja-content">
		<jdoc:include type="message" />
        <jdoc:include type="component" />
		</div>

	</div>

	
	<!-- BEGIN: LEFT COLUMN -->
	<div id="ja-leftcol">
		<jdoc:include type="modules" name="left" style="rounded" />
	</div>
	<!-- END: LEFT COLUMN -->


	</div>
	<!-- END: CONTENT -->

	<?php if ($ja_right) { ?>
	<!-- BEGIN: RIGHT COLUMN -->
	<div id="ja-rightcol">
		<jdoc:include type="modules" name="right" style="rounded" />
	</div>
	<br />
	<!-- END: RIGHT COLUMN -->
	<?php } ?>

</div>
</div>

<!-- BEGIN: FOOTER -->
<div id="ja-botnavwrap">
	<div id="ja-botnav">
		<jdoc:include type="modules" name="user3" />
		<a title="Go to Top" href="<?php echo getCurrentURL();?>#Top" class="gotop-button">Top</a>
	</div>
</div>


<div id="ja-footerwrap">
	<div id="ja-footer">

    <p id="ja-bottomline">
		<jdoc:include type="modules" name="syndicate" />
	</p>

	<div id="ja-cert">
		<jdoc:include type="modules" name="footer" />
	</div>

	</div>
</div>
<!-- END: FOOTER -->


<jdoc:include type="modules" name="debug" />
</body>
</html>