<?php
defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );
global $ja_template_name;
$japarams = new mosParameters('');
$japarams->set( 'template', $ja_template_name );	//	Change this value to correct template
$japarams->set( 'absPath', $mosConfig_absolute_path . '/templates/' . $japarams->get( 'template' ) . '/ja_transmenu' );
$japarams->set( 'LSPath', $mosConfig_live_site . '/templates/' . $japarams->get( 'template' ) . '/ja_transmenu' );
$japarams->set( 'menutype', 'mainmenu' );					//	Source of menu
//Set style for menu
$japarams->set( 'menu_style', 'horizontal' );			//	Value: horizontal, vertical
$japarams->set( 'menu_direction', 'down' );				//	Value: down, right
$japarams->set( 'menu_position', 'bottomLeft' );		//	Value: bottomLeft, bottomRight, topLeft, topRight
$japarams->set( 'p_t', '0' );
$japarams->set( 'p_l', '0' );
$japarams->set( 'class_sfx', '-trans' );
$japarams->set( 'subpad_x', '0' );
$japarams->set( 'subpad_y', '0' );
$japarams->set( 'menu_images', '0' );
$japarams->set( 'menu_images_align', '0' );
include_once( $japarams->get( 'absPath' ) .'/ja-menulib.php' );
global $my;
$jamenu= new JAMenu ($database, $japarams);
$jamenu->genMenu ();
?>

