<?php

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.view');

class fabrikViewGooglemap extends JView
{
	var $_isMambot = null;

	function display( $tmpl = 'default' )
	{
		global $mainframe;
		$params 	   	=& $mainframe->getParams('com_fabrik');
		$document 		=& JFactory::getDocument();
		$usersConfig 	= &JComponentHelper::getParams( 'com_fabrik' );
		$model				= &$this->getModel();
		$model->setId( JRequest::getVar('id', $usersConfig->get( 'visualizationid', JRequest::getInt('visualizationid', 0) ) ));
		$this->row 		=& $model->getVisualization();
		$js 					= $model->getJs();
		$this->txt 		= $model->getText();
		$this->params =& $model->getParams();
		
		$document->addScript( "http://maps.google.com/maps?file=api&amp;v=2&amp;key=" . $this->params->get( 'fb_gm_key' ) );
		
		FabrikHelperHTML::script( 'googlemap.js', 'components/com_fabrik/plugins/visualization/googlemap/', true );
		
		if($this->params->get('fb_gm_clustering') == 1){
			FabrikHelperHTML::script( 'ClusterMarker-uncompressed.js', 'components/com_fabrik/libs/googlemaps/clustermarker/', true );
		}else{
			FabrikHelperHTML::script( 'markermanager.js', 'components/com_fabrik/libs/googlemaps/', true );	
		}
		$document->addScriptDeclaration($js);
		
		
		$pluginParams =& $model->getPluginParams();
		$tmpl = $pluginParams->get( 'fb_gm_layout', $tmpl );
		$tmplpath = COM_FABRIK_FRONTEND.DS.'plugins'.DS.'visualization'.DS.'googlemap'.DS.'views'.DS.'googlemap'.DS.'tmpl'.DS.$tmpl;
		$this->_setPath( 'template', $tmplpath );

		$ab_css_file = $tmplpath.DS."template.css";

		if (file_exists( $ab_css_file ))
		{
			JHTML::stylesheet( 'template.css', 'components/com_fabrik/plugins/visualization/googlemap/views/googlemap/tmpl/'.$tmpl.'/', true );
		}
	
		//ensure we don't have an incorrect version of mootools loaded
		FabrikHelperHTML::cleanMootools();
			if ($this->_isMambot) {
			return $this->loadTemplate();
		} else {
			parent::display( );
		}
	}

	
}
?>