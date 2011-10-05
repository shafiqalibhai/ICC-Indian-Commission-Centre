<?php

/**
 * @package Joomla
 * @subpackage Fabrik
 * @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.model');
require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'parent.php' );

require_once( JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'models'.DS.'visualization.php' );

class fabrikModelGooglemap extends FabrikModelVisualization { //JModel

	var $txt = null;

	/**
	 * constructor
	 */

	function __construct()
	{
		parent::__construct();
	}

	function getText(){
		return $this->txt;
	}

	function getJs()
	{
		$params =& $this->getParams();
		$str = "window.addEvent('domready', function(){\n";
		$viz = $this->getVisualization();
		
		$opts = new stdClass();
		$opts->icons = $this->getJSIcons();
		$opts->id = $viz->id;
		$opts->zoomlevel = $params->get( 'fb_gm_zoomlevel' );
		$opts->control = $params->get( 'fb_gm_mapcontrol' );
		$opts->scalecontrol = $params->get( 'fb_gm_scalecontrol' );
		$opts->maptypecontrol = $params->get( 'fb_gm_maptypecontrol' );
		$opts->overviewcontrol = $params->get( 'fb_gm_overviewcontrol' );
		$opts->livesite = COM_FABRIK_LIVESITE;
		$opts->center =  $params->get( 'fb_gm_center' );
		$opts->ajax_refresh = $params->get('fb_gm_ajax_refresh', 0);
		$opts->maptype = $params->get('fb_gm_maptype');
		$opts->clustering = $params->get('fb_gm_clustering');
		$opts->cluster_splits = $params->get( 'fb_gm_cluster_splits' );
		$opts->icon_increment = $params->get( 'fb_gm_cluster_icon_increment' );
		$opts = FastJSON::encode($opts);
		$str .= "fabrikMap = new fbGoogleTableMap('table_map', $opts)\n";
		$str .= "});\n";
		return $str;
	}

	function getJSIcons()
	{
		$icons 			= array();
		$w 					= new FabrikWorker();
		$params 		=& $this->getParams();
		$templates 	= $params->get( 'fb_gm_detailtemplate', array(), '_default', 'array' );
		$aTables 		= $params->get( 'googlemap_table', array(), '_default', 'array' );
		//images for file system
		$aIconImgs	= $params->get( 'fb_gm_iconimage', array(), '_default', 'array' );
		//image from marker data
		$markerImages = $params->get( 'fb_gm_iconimage2', array(), '_default', 'array' );
		//specifed letter
		$letters = $params->get( 'fb_gm_icon_letter', array(), '_default', 'array' );
		
		$c = 0;

		foreach ($aTables as $tableid) {
			$tableModel =& JModel::getInstance( 'Table', 'FabrikModel' );
			$template = $templates[$c];
			$tableModel->setId( $tableid );
			$table =& $tableModel->getTable();
			$mapsElements =& $tableModel->getElementsOfType( 'fabrikgooglemap' );
			
			if (empty( $mapsElements )) {
				JError::raiseError( 500, JText::_('No google map element present in this table'));
				continue;
			}
			
			$coordColumn = $mapsElements[0]->getFullName( false, true, false ) . "_raw";
			
			$nav	=& $tableModel->getPagination( 0, 0, 0 );
			$data = $tableModel->getData();

			$this->txt = array();
			foreach ($data as $group) {
				foreach ($group as $row) {
					$customimagefound = false;
					$iconImg = array_key_exists($c, $aIconImgs) ? $aIconImgs[$c] : '';
					$v = trim( $row->$coordColumn );
					$v = FabrikString::ltrimword( $v, "(" );
					if (strstr( $v, "," )) {
						if(strstr($v, ":")){
							$ar = explode( ":", $v );
							array_pop( $ar );
							$v = explode( ",", $ar[0] );
						} else {
							$v = explode( ",", $v );
						}
						$v[1] = FabrikString::rtrimword($v[1], ")");
					} else {
						continue;//dont show icons with no data
						$v = array( 0,0 );
					}
					$rowdata = JArrayHelper::fromObject( $row );
					$html = ( $w->parseMessageForPlaceHolder( $template, $rowdata ) );

					// $$$ hugh - if they provided a template, lets assume they will handle the link themselves.
					// http://fabrikar.com/forums/showthread.php?p=41550#post41550
					if (empty($html) && array_key_exists( 'fabrik_view', $row )) {
						$html .= "<br />" . ( $row->fabrik_view );
					}
					$html = str_replace(array("\n", "\r"), "<br />", $html);
					$html = str_replace("'", '"', $html);
					$this->txt[] = $html;
						
					if ($iconImg == '') {
						$iconImg = array_key_exists($c, $markerImages) ? $markerImages[$c] : '';
						if ($iconImg != '') {
							$iconImg = array_key_exists($iconImg, $rowdata) ? $rowdata[$iconImg] : '';
							//get the src
							preg_match('/src=["|\'](.*?)["|\']/', $iconImg, $matches);
							if (array_key_exists(1, $matches)){
								$iconImg = $matches[1];
								//check file exists
								$path = str_replace(COM_FABRIK_LIVESITE, '', $iconImg);
								if (JFile::exists(JPATH_BASE.$path)) {
									$customimagefound = true;
								}
							}
						}
						
						if ($iconImg != '') {
							@$size = getimagesize($iconImg);
							$width = is_array($size) ? $size[0] : 25;
							$height = is_array($size) ? $size[1] : 25;
							//ensure icons arent too big (25 is max)
							$scale = min(25 / $width, 25 / $height);
							/* If the image is larger than the max shrink it*/
							if ($scale < 1) {
								$width = floor($scale * $width);
								$height = floor($scale * $height);
							}
						}else{
							//standard google map icon size
							$width = 20;
							$height = 34;
						}
					} else {
						//standard google map icon size
						$width = 20;
						$height = 34;
					}
					//just for moosehunt!
					$radomize = ($_SERVER['HTTP_HOST'] == 'moosehunt.mobi') ? true :false;
					
					if (array_key_exists($v[0].$v[1], $icons)) {
						$icons[$v[0].$v[1]][2] = $icons[$v[0].$v[1]][2] . $html;
						if ($customimagefound) {
							$icons[$v[0].$v[1]][3] = $iconImg;
						}
					} else {
						//default icon - lets see if we need to use a letterd icon instead
						if ($letters[$c] != '') {
							$iconImg = 'http://www.google.com/mapfiles/marker'.strtoupper($letters[$c]).'.png';
						}
						$icons[$v[0].$v[1]] = array($v[0], $v[1], $html, $iconImg, $width, $height);
					}
				}
			}
			$c ++;
		}
		$icons = array_values($icons); //replace coord keys with numeric keys
		return $icons;
		return FastJSON::encode($icons);
	}

	function ajax_getMarkers()
	{
		echo $this->getJSIcons();
	}

	function renderAdminSettings()
	{
		JHTML::stylesheet( 'fabrikadmin.css', 'administrator/components/com_fabrik/views/' );
		$pluginParams =& $this->getPluginParams();
		$document =& JFactory::getDocument( );
		?>
	<div id="page-<?php echo $this->_name;?>" class="pluginSettings"
	style="display: none"><?php
	echo $pluginParams->render('params', 'connection');

	$c = count($pluginParams->get('googlemap_table'));
	$pluginParams->_duplicate = true;
	for ($x=0; $x<$c; $x++) {
		echo $pluginParams->render( 'params', '_default', true, $x );
	}
	$pluginParams->_duplicate = false;
	echo $pluginParams->render( 'params', 'rest' );
	?>
	<fieldset><legend><?php echo JText::_('Clustering'); ?></legend>
	<?php  echo $pluginParams->render ('params', 'clustering' );?>
	</fieldset>
	</div>
	<?php
	return ;
	}

	function render()
	{
	}
}

?>