<?php
defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );
global $ja_template_name;
$japarams = new mosParameters('');
$japarams->set( 'template', $ja_template_name );	//	Change this value to correct template
$japarams->set( 'absPath', $mosConfig_absolute_path . '/templates/' . $japarams->get( 'template' ) . '/ja_scriptdlmenu' );
$japarams->set( 'LSPath', $mosConfig_live_site . '/templates/' . $japarams->get( 'template' ) . '/ja_scriptdlmenu' );
$japarams->set( 'menutype', 'mainmenu' );					//	Source of menu
//Set style for menu

$japarams->set( 'menuclass', 'scriptdlmenu' );
include_once( $japarams->get( 'absPath' ) .'/ja-menulib.php' );
global $my;
$jamenu= new JAMenu ($database, $japarams);
$jamenu->genMenu();
?>