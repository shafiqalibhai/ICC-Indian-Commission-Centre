<?php

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.view');

class fabrikViewCalendar extends JView
{

  var $_isMambot = false;
  
	function display( $tmpl = 'default' )
	{
		global $mainframe, $Itemid;;
		JHTML::_('behavior.calendar');
		require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'html.php' );
		$pluginManager 	=& JModel::getInstance( 'Pluginmanager', 'FabrikModel' );
		$plugin 				=& $pluginManager->getPlugIn( 'calendar', 'visualization' );
		$mainframe->enqueueMessage( JText::_('Double click a date to add an event') );
		FabrikHelperHTML::mocha();
		FabrikHelperHTML::loadCalendar();
		$model		= &$this->getModel();
		$usersConfig = &JComponentHelper::getParams( 'com_fabrik' );
		$id = JRequest::getVar('id', $usersConfig->get( 'visualizationid', JRequest::getInt('visualizationid', 0) ) );
		$model->setId( $id );

		$this->row =& $model->getVisualization();
		$calendar =& $model->_row;
		$this->calName = $model->getCalName();
		$config		=& JFactory::getConfig();
		$document =& JFactory::getDocument();

		FabrikHelperHTML::mocha();
		FabrikHelperHTML::packageJS();

		$fbConfig =& JComponentHelper::getParams( 'com_fabrik' );

		FabrikHelperHTML::script( 'element.js', 'components/com_fabrik/views/form/', true );
		FabrikHelperHTML::script( 'form.js', 'components/com_fabrik/views/form/', true );
		FabrikHelperHTML::script( 'calendar.js', 'components/com_fabrik/plugins/visualization/calendar/', true );
		$params =& $model->getParams();

		//Get the active menu item
		$usersConfig = &JComponentHelper::getParams( 'com_fabrik' );
		$urlfilters = JRequest::get( 'get' );
		unset( $urlfilters['option'] );
		unset( $urlfilters['view'] );
		unset( $urlfilters['controller'] );
		unset( $urlfilters['Itemid'] );
		unset( $urlfilters['visualizationid'] );
		unset( $urlfilters['format'] );
		if (empty($urlfilters)) {
			$urlfilters = new stdClass();
		}
		$urls = new stdClass();
		//dont JRoute as its wont load with sef?
		$urls->del = 'index.php?option=com_fabrik&controller=visualization.calendar&view=visualization&task=deleteEvent&format=raw&Itemid='. $Itemid. '&id='.$id;
		$urls->add = 'index.php?option=com_fabrik&view=visualization&controller=visualization.calendar&format=raw&Itemid='. $Itemid . '&id='.$id;

		$user 		=& JFactory::getUser();
		$legend = $params->get( 'show_calendar_legend', 0 ) ? $model->getLegend() : '';
		$tmpl = $params->get( 'calendar_layout', 'default' );
		$oPluginManager =& JModel::getInstance( 'Pluginmanager', 'FabrikModel' );
		$oPluginManager->loadJS();
		$options = new stdClass();
		$options->url = $urls;
		$options->eventTables =& $model->getEventTables();
		$options->calendarId = $calendar->id;
		$options->popwiny = $params->get( 'yoffset', 0 );
		$options->urlfilters = $urlfilters;

		$options->tmpl = $tmpl;
		$formView =& $this->_formView;
		$formView->_isMambot = true;

		$o = $model->getAddStandardEventFormInfo();

		if ($o != null) {
			$options->tableid = $o->id;
			$formView->setId( $o->form_id );
			$options->formid = $o->form_id;
		}

		$formModel =& $formView->getModel();
		$form =& $formModel->getForm();

		$fbConfig =& JComponentHelper::getParams( 'com_fabrik' );
		$options->mooversion = ($fbConfig->get( 'usefabrik_mootools', false )) ? 1.2 : 1.1;

		$model->setRequestFilters();
		$options->filters =& $model->filters;
		$options->Itemid = $Itemid;
		$options->standard_event_form = $params->get('use_standard_event_table', 0) == 1 ? true : false;

		$options->show_day 				= $params->get( 'show_day', true );
		$options->show_week 			= $params->get( 'show_week', true );
		$options->days 						= array( JText::_('Sunday'), JText::_('Monday'), JText::_('Tuesday'), JText::_('Wednesday'), JText::_('Thursday'), JText::_('Friday'), JText::_('Saturday') );
		$options->shortDays 			= array( JText::_('Sun'), JText::_('Mon'), JText::_('Tue'), JText::_('Wed'), JText::_('Thu'), JText::_('Fri'), JText::_('Sat') );
		$options->months 					= array( JText::_('January'), JText::_('February'), JText::_('March'), JText::_('April'), JText::_('May'), JText::_('June'), JText::_('July'), JText::_('August'), JText::_('September'), JText::_('October'), JText::_('November'), JText::_('December') );
		$options->shortMonths 		= array( JText::_('Jan'), JText::_('Feb'), JText::_('Mar'), JText::_('Apr'), JText::_('May'), JText::_('Jun'), JText::_('Jul'), JText::_('Aug'), JText::_('Sept'), JText::_('Oct'), JText::_('Nov'), JText::_('Dec') );
		$options->first_week_day 	= $params->get( 'first_week_day', 0 );
		
		$options->monthday = new stdClass();
		$options->monthday->width = (int)$params->get( 'calendar-monthday-width', 90 );
		$options->monthday->height = (int)$params->get( 'calendar-monthday-height', 90 );
		$options->greyscaledweekend = $params->get( 'greyscaled-week-end', 0 );
		
		$json = FastJSON::encode($options);

		$lang 							= new stdClass();
		$lang->next 				= JText::_('Next');
		$lang->previous 		= JText::_('Previous');
		$lang->day 					= JText::_('Day');
		$lang->week 				= JText::_('Week');
		$lang->month 				= JText::_('Month');
		$lang->key 					= htmlspecialchars(JText::_('Key'));
		$lang->today 				= JText::_('Today');
		$lang->start 				= JText::_('Start');
		$lang->end 					= JText::_('End');
		$lang->deleteConf 	= JText::_('Are you sure you want to delete this?');
		$lang->del 					= JText::_('delete');
		$lang->view 				= JText::_('view');
		$lang->edit 				= JText::_('edit');
		$lang->windowtitle 	= JText::_('add/edit event');

		$lang = FastJSON::encode($lang);

		$str = "window.addEvent('domready', function(e){\n".
		"  //var m = new MochaUI.Modal();\n".
		"  $this->calName = new fabrikCalendar('calendar');\n".
		"  $this->calName.render({}, $json, $lang);\n".
		"  oPackage.addBlock('calendar_" . $calendar->id . "', $this->calName);\n";
		if ($o != null) {
			$str .="  $this->calName.addListenTo('form_{$o->form_id}');\n";
		}
		$fids =& $model->getLinkedFormIds();
		foreach ($fids as $fid) {
			$str .= "  $this->calName.addListenTo('form_$fid');\n";
		}
		$str .= $legend . "\n});\n";


		$document->addScriptDeclaration( $str );

		$viewName = $this->getName();


		$pluginParams =& $model->getPluginParams();
		$tmpl = $pluginParams->get('calendar_layout', $tmpl);
		$tmplpath = COM_FABRIK_FRONTEND.DS.'plugins'.DS.'visualization'.DS.'calendar'.DS.'views'.DS.'calendar'.DS.'tmpl'.DS.$tmpl;
		$this->_setPath( 'template', $tmplpath );

		$ab_css_file = $tmplpath.DS."template.css";

		if (file_exists( $ab_css_file ))
		{
			JHTML::stylesheet( 'template.css', 'components/com_fabrik/plugins/visualization/calendar/views/calendar/tmpl/'.$tmpl.'/', true );
		}
		//ensure we don't have an incorrect version of mootools loaded
		FabrikHelperHTML::cleanMootools();
		// $$$ hugh @TODO - _isMambot ain't defined?
		if ($this->_isMambot) {
			return $this->loadTemplate();
		} else {
			parent::display( );
		}
	}

	function chooseaddevent()
	{
		$document =& JFactory::getDocument();
		$view->_layout 	= 'chooseaddevent';
		// include language
		$pluginManager =& JModel::getInstance( 'Pluginmanager', 'FabrikModel' );
		$plugin =& $pluginManager->getPlugIn( 'calendar', 'visualization' );
		$model =& $this->getModel();
		$usersConfig = &JComponentHelper::getParams( 'com_fabrik' );
		$model->setId( JRequest::getVar('id', $usersConfig->get( 'visualizationid', JRequest::getInt('visualizationid', 0) ) ));

		$rows =& $model->getEventTables();
		$o =& $model->getAddStandardEventFormInfo();

		$options = array();
		$options[] = JHTML::_('select.option', '', JText::_('Please select'));

		if ($o != null) {
			$tableid = $o->id;
			$options[] 			= JHTML::_('select.option', $tableid, JText::_('Standard event'));
		}

		$model->getEvents();
		$config =& JFactory::getConfig();
		$prefix = $config->getValue('config.dbprefix');

		$this->_eventTypeDd = JHTML::_( 'select.genericlist', array_merge( $options, $rows ), 'event_type', 'class="inputbox" size="1" ', 'value', 'text', '', 'fabrik_event_type' );

		//tried loading in iframe and as an ajax request directly - however
		//in the end decided to set a call back to the main calendar object (via the package manager)
		//to load up the new add event form

		$script = "window.addEvent('domready', function(){
			oCalendar".$model->_id.".addListenTo('chooseCalendarEvent');
		$('fabrik_event_type').addEvent('change', function(e){
		var fid = $(e.target).getValue();
		var o = ({'d':'','tableid':fid,'rowid':0});
		o.datefield = '{$prefix}fabrik_calendar_events___start_date';
		o.datefield2 = '{$prefix}fabrik_calendar_events___end_date';
		o.labelfield = '{$prefix}fabrik_calendar_events___label';
		";
		foreach($model->_events as $tid=>$arr){
			foreach($arr as $ar){
				$script .= "if(".$ar['formid']." == fid)	{\n";
				$script .= "o.datefield = '".$ar['startdate'] . "'\n";
				$script .= "o.datefield2 = '".$ar['enddate'] . "'\n";
				$script .= "o.labelfield = '".$ar['label'] . "'\n";
				$script .= "}\n";
			}
		}
		$script .= "var o = Json.toString(o);
			oPackage.sendMessage( 'chooseCalendarEvent', 'addEvForm' , true, o );
	});
	});
	";
		$document =& JFactory::getDocument();
		$document->addScriptDeclaration( $script );
		echo "<h2>".JText::_('Please choose an event type:') . "</h2>";
		echo $this->_eventTypeDd;
	}
}
?>
