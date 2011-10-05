<?php
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();
$row = $this->row;
?>
<h1><?php echo $row->label;?></h1>
<br/>
<p><?php echo $row->intro_text;?></p>
<br/>
<?php echo $this->chart; ?>


<h2>Uw reacties</h2>
<?php

//reshow the submitted calculations

$id = $_SESSION['fabrik'][5]['ide_values___fabrik_internal_id']['value'];
$form = JModel::getInstance('form', 'FabrikModel');
$form->setId(5);
JRequest::setVar('rowid', $id);
$form->render();
$used = array('Milieu','Economie','Veiligheid','Bereikbaarheid');
$str = '<ul>';
$groups = $form->getGroups();
foreach($groups as $group){
	$elements = $group->getMyElements();
	foreach($elements as $element){
		$params =& $element->getParams();
		if($params->get('sum_on') || $params->get('avg_on') || $params->get('median_on') || $params->get('count_on') ){
			$data = $form->_data[$element->getFullName()];
			if($data != '//..*..////..*..//'){
				if (!in_array( $element->getElement()->name, $used)){
					$str .= "<li>". $element->getElement()->label . ": <strong>" . $data . "</strong></li>";
				}
			}
		}
	}
}
$str .= '</ul>';
echo $str;
?>

