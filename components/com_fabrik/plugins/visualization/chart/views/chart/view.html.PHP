<?php

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.view');

class fabrikViewChart extends JView
{

	var $_isMambot = false;

	function display( $tmpl = 'default' )
	{
		JHTML::_('behavior.calendar');
		require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'html.php' );
		FabrikHelperHTML::mocha();
		FabrikHelperHTML::loadCalendar();
		$model		= &$this->getModel();
		$usersConfig = &JComponentHelper::getParams( 'com_fabrik' );
		$model->setId( JRequest::getVar('id', $usersConfig->get( 'visualizationid', JRequest::getInt('visualizationid', 0) ) ));

		$this->row =& $model->getVisualization();
		if ($this->row->state == 0){
			JError::raiseWarning( 500, JText::_( 'ALERTNOTAUTH' ) );
			return '';
		}
		$calendar =& $model->_row;
		$this->chart = $model->getChart();

		$viewName = $this->getName();
		$pluginManager =& JModel::getInstance( 'Pluginmanager', 'FabrikModel' );
		$plugin =& $pluginManager->getPlugIn( 'calendar', 'visualization' );

		$pluginParams =& $model->getPluginParams();
		$tmpl = $pluginParams->get( 'chart_layout', $tmpl );
		$tmplpath = COM_FABRIK_FRONTEND.DS.'plugins'.DS.'visualization'.DS.'chart'.DS.'views'.DS.'chart'.DS.'tmpl'.DS.$tmpl;
		$this->_setPath( 'template', $tmplpath );

		$ab_css_file = $tmplpath.DS."template.css";

		if (file_exists( $ab_css_file ))
		{
			JHTML::stylesheet( 'template.css', 'components/com_fabrik/plugins/visualization/chart/views/chart/tmpl/'.$tmpl.'/', true );
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