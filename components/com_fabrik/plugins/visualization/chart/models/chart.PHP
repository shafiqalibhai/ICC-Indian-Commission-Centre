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

require_once( JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'models'.DS.'visualization.php' );

class fabrikModelChart extends FabrikModelVisualization {

	/** @var string google charts api url **/

	var $_url = 'http://chart.apis.google.com/chart';

	function _getMinMax(&$totals)
	{
		// $min will only go lower if data is negative!
		$max = 0;
		$min = 0;
		foreach ($totals as $tots) {
			if (max($totals) > $max){
				$max = max($totals);
			}
			if (min($totals) < $min){
				$min = min($totals);
			}
		}
		return array('min' => $min, 'max' => $max);
	}

	function getChart()
	{
		$params =& $this->getParams();
		$calc_prefixes = array('sum___', 'avg___', 'med___', 'cnt___');
		$calc_prefixmap = array('sum___' => 'sums', 'avg___' => 'avgs', 'med___' => 'medians', 'cnt___' => 'count');
		$w = $params->get('chart_width');
		$h = $params->get('chart_height');

		$graph =$params->get( 'graph_type' );

		$fillGraphs 		= $params->get( 'fill_line_graph' );

		$x_axis_label 	= $params->get( 'x_axis_label', array(), '_default', 'array' );
		$chartElements 	= $params->get('chart_elementList', array(), '_default', 'array');

		$chartColours 	= $params->get('chart_colours', array(), '_default', 'array');
		$tableid 				= $params->get('chart_table', array(), '_default', 'array');
		$axisLabels 		= $params->get('chart_axis_labels', array(), '_default', 'array');
		$legends  			= $params->get( 'graph_show_legend' );
		$c = 0;
		$gdata = array();
		$glabels = array();
		$gcolours = array();
		$gfills = array();
		$max = 0;
		$min = 0;

		$calculationLabels = array();
		$calculationData = array();
		$calcfound = false;
		
		$tmodels = array();
		
		foreach ($tableid as $tid) {
		  if (!array_key_exists($tid, $tmodels)) {
				$tableModel = null;
				$tableModel =& JModel::getInstance( 'Table', 'FabrikModel' );
				$tableModel->setId( $tid );
				$tmodels[$tid] = $tableModel;
		  } else {
		    $tableModel = $tmodels[$tid];
		  }
			
			$table =& $tableModel->getTable();
			$form =& $tableModel->getForm();
			//remove filters?
			// $$$ hugh - remove pagination BEFORE calling render().  Otherwise render() applies
			// session state/defaults when it calls getPagination, which is then returned as a cached
			// object if we call getPagination after render().  So call it first, then render() will
			// get our cached pagination, rather than vice versa.
			$nav			=& $tableModel->getPagination( 0, 0, 0 );
			$tableModel->render();
			//$tableModel->doCalculations();
			$alldata = $tableModel->getData();
			$cals = $tableModel->getCalculations();
			$column = $chartElements[$c];
			$pref =substr($column, 0, 6);

			$label = $x_axis_label[$c];
			
			$tmpgdata = array();
			$tmpglabels = array();
			$colour = str_replace("#", '', $chartColours[$c]);

			if ($fillGraphs) {
				$c2 = $c + 1;
				$gfills[] ='b,'. $colour . "," . $c  . ",". $c2 .",0";
			}

			$gcolours[] = $colour;

			if (in_array( $pref, $calc_prefixes )) {
				
				// you shouldnt mix calculation elements with normal elements when creating the chart
				// so if ONE calculation element is found we use the calculation data rather than normal element data
				// this is because a calculation element only generates one value, if want to compare two averages then 
				//they get rendered as tow groups of data and on bar charts this overlays one average over the other, rather than next to it
				$calcfound = true;
				
				$column = substr($column, 6);
				$calckey = $calc_prefixmap[$pref];
				$caldata = $cals[$calckey][$column.'_obj'];
				if (is_array( $caldata )) {
					foreach ($caldata as $k=>$o) {
						//if($k != 'calc'){
						//$tmpgdata[] = (float)$o->value;
						$calculationData[] = (float)$o->value;
						$calculationLabels[] = trim(strip_tags($o->elLabel));
						/*foreach ($alldata as $group) {
							foreach ($group as $row) {
								if($row->__pk_val == $k){
									if (array_key_exists( $column, $row)) {
										$tmpglabels[] = $row->$label;
										break 2;
									}
								}
							}
						}*/
						//}
					}
				}
			if (!empty($calculationData) && max($calculationData) > $max){
					$max = max($calculationData);
				}
				if(!empty($calculationData) && min($calculationData) < $min){
					$min = min($calculationData);
				}
				
				/*if (!empty($tmpgdata) && max($tmpgdata) > $max){
					$max = max($tmpgdata);
				}
				if(!empty($tmpgdata) && min($tmpgdata) < $min){
					$min = min($tmpgdata);
				}*/
				
				
				$gdata[$c] = implode(',', $tmpgdata);
				$glabels[$c] = implode('|', $tmpglabels);
				// $$$ hugh - playing around with pie charts
				//$gsums[$c] = array_sum($tmpgdata);
				$gsums[$c] = array_sum($calculationData);
			} else {
				$origColumn = $column;
				$column = $column. "_raw"; //_raw fields are most likely to contain the value
				foreach ($alldata as $group) {
					foreach ($group as $row) {
						if (!array_key_exists( $column, $row)) {
							//didnt find a _raw column - revent to orig
							$column = $origColumn;

							if (!array_key_exists( $column, $row)) {
								JError::raiseWarning(E_NOTICE, $column . ': NOT FOUND - PLEASE CHECK IT IS PUBLISHED');
								continue;
							}
						}
						if (trim( $row->$column ) == '') {
							$tmpgdata[] = - 1;
						} else {
							$tmpgdata[] = (float)$row->$column;
						}
						$tmpglabels[] = $row->$label;
					}
					if (!empty($tmpgdata) && max($tmpgdata) > $max){
						$max = max($tmpgdata);
					}
					if(!empty($tmpgdata) && min($tmpgdata) < $min){
						$min = min($tmpgdata);
					}
					$gdata[$c] = implode(',', $tmpgdata);
					$glabels[$c] = implode('|', $tmpglabels);
					// $$$ hugh - playing around with pie charts
					$gsums[$c] = array_sum($tmpgdata);
				}
			}
			$c ++;
		}
		if ($calcfound) {
			$calculationLabels = array_reverse($calculationLabels);
			$glabels = array(implode(',', array_reverse($calculationLabels))); 
 			$gdata =  array(implode(',', $calculationData));
		}
		// $$$ hugh - pie chart data has to be summed - the API only takes a
		// single dataset for pie charts.  And it doesn't make sense trying to
		// chart individual row data for multiple elements in a pie chart.
		// Also, labels need to be axisLabels, not $glabels
		//echo $graph;exit;
		switch ($graph) {
			case 'p':
			case 'p3':
				if ($c > 1) {
					// mutiple table/elements, so use the sums
					$chd = implode(',',$gsums);
					$chxl = '0:|' . implode('|', $axisLabels);
					$chds = '';
					$fillGraphs = false;
					$legends = false;
				}
				else {
					// single table/elements, so use the row data
					$chd = implode('|', $gdata);
					$chxl = '0:|'.$glabels[0].'|'.'1:|'.$min.'|'.$max. '%';
					$chds = '';
				}
				break;
			case 'bhs':
				$chd = implode('|', $gdata);
				if ($c > 1 && !$calcfound) {
					$minmax = $this->_getMinMax($gsums);
					$chds = $minmax['min'] . ',' . $minmax['max'];
				}
				else {
					$chds = $min.','.$max;
				}
				if ($calcfound){ 
					$glabels = array(implode('|', $calculationLabels));
				}
				$chxl = '0:|'.$min.'|'.$max.'%|'.'1:|'.$glabels[0];
				break;
			default:
				$chd = implode('|', $gdata);
				$chxl = '0:|'.$glabels[0].'|'.'1:|'.$min.'|'.$max;
				if ($c > 1 && !$calcfound) {
					$minmax = $this->_getMinMax($gsums);
					$chds = $minmax['min'] . ',' . $minmax['max'];
				}
				else {
					$chds = $min.','.$max;
				}
				break;
		}
		$colours = implode( ($calcfound ? '|': ','), $gcolours);
		
		$return = '<img src="' . $this->_url . '?';
		$qs =  'chs='.$w.'x'.$h;
		$qs .= '&amp;chd=t:'.$chd;
		$qs .= '&amp;cht='.$graph;
		$qs .= '&amp;chco='.$colours;
		$qs .= '&amp;chxt=x,y';
		$qs .= '&amp;chxl='.$chxl;
		$qs .= '&amp;chds='.$chds;
		if ($fillGraphs) {
			$qs .=  '&amp;chm=' . implode('|', $gfills);
		}
		if ($legends) {
			$qs .= '&amp;chdl=' . implode('|', $axisLabels);
		}

		$return .= $qs . '"alt="'.$this->_row->label.'" />';
		$this->image =  $return;
		return $return;
	}




	/**
	 * constructor
	 */

	function __construct()
	{
		parent::__construct();
	}

	function renderAdminSettings()
	{
		$pluginParams =& $this->getPluginParams();
		$document =& JFactory::getDocument();
		FabrikHelperHTML::script( 'adminchart.js', 'components/com_fabrik/plugins/visualization/chart/', true );
		?>
<div id="page-<?php echo $this->_name;?>" class="pluginSettings"
	style="display: none">
	<?php
		//echo $pluginParams->render( 'params' );
		$pluginParams->_duplicate = false;
		echo $pluginParams->render('params', 'rest');
	
		$c = count($pluginParams->get('chart_table'));
		$pluginParams->_duplicate = true;
		echo $pluginParams->render('params', 'connection');
	
		for ($x=0;$x<$c;$x++) {
			echo $pluginParams->render('params', '_default', true, $x);
		}
	}


}


?>