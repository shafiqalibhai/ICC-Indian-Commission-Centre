<?php
/**
* Plugin element to render fields
* @package fabrikar
* @author Rob Clayburn
* @copyright (C) Rob Clayburn
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

class imageRender{
	
	function renderTableData( &$model, &$params, $file )
	{
		$this->render( $model, $params, $file );
	}
	
	function render( &$model, &$params, $file )
	{
		// $$$ hugh - added this hack to let people use elementname__title as a title element
		// for the image, to show in the lightbox popup.
		// So we have to work out if we're being called from a table or form
		$title = basename($file);
		$title_name = $model->getFullName() . '__title';
		if (JRequest::getVar('view') == 'table') {
			$tableModel =& $model->getTableModel();
			if (is_array($tableModel->_aRow) && array_key_exists($title_name, $tableModel->_aRow)) {
				$title = $tableModel->_aRow[$title_name];
			}
		}
		else {
			if (is_object($model->_form)) {
				if (is_array($model->_form->_data)) {
					if (array_key_exists($title_name, $model->_form->_data)) {
						$title = $model->_form->_data[$title_name];
					}					
				}
			}
		}
		
		$element =& $model->getElement();

		$file = str_replace("\\", "/", COM_FABRIK_LIVESITE  . $file);
		$fullSize = $file;
		if ($params->get( 'make_thumbnail' )  == '1') {
			$file = $model->_getThumb( $file );
		}
		$this->output = '';
		if ($params->get('filupload_crop')) {
		   if(JRequest::getVar('view') == 'form'){
		    $this->output .= $this->_cropInterface($model, $params, $file);
		   } else {
		     $file = $model->_getCropped( $fullSize );
		     $this->output .=	"<a href='$fullSize' rel='lightbox[]' title='$title'><img class='fabrikLightBoxImage' src='$file' alt='$element->label' /></a>";
		   }
		} else {
		  $this->output .=	"<a href='$fullSize' rel='lightbox[]' title='$title'><img class='fabrikLightBoxImage' src='$file' alt='$element->label' /></a>";
		}
	}
	
	function _cropInterface($model, $params, $file)
	{
	  $document =& JFactory::getDocument();
	  $imageid = $model->getFullName() . "_dragger";
	  $divid = $model->getFullName() . "_dragger_container";
	  $thumbname = $model->getFullName() . "_dragger_thumb";
	  $fieldname = $model->getFullName() . "_cropcoords";
	  $w = (int)$params->get('fileupload_crop_width', 50);
	  $h = (int)$params->get('fileupload_crop_height', 50);
	  $str = "<div id='$divid' style='position:relative;cursor:move;width:{$w}px;height:{$h}px;border:1px solid;overflow:hidden'>";
	  $str .= "<img style='position:absolute' id='$imageid' src='$file' alt='' />";
	  
	  $str .= "</div>";
	  $str .= "<input type='hidden' name='$fieldname' id='$fieldname' value='0,0' />";
	  $str .= "<input type='hidden' name='$thumbname' value='$file' />";
	  $size =  getimagesize($file);
	  $iw = $size[0];
	  $ih = $size[1];
	  
	  $offsetleft = ($iw - $w) * -1;
	  $offsettop = ($ih - $h) * -1;
	  
	  $script = "window.addEvent('domready', function(){
	  	new Drag.Move('$imageid', {
	  	onDrag:function(e){
	  		var x = this.value.now.x.toInt() * -1;
	  		var y =  this.value.now.y.toInt() * -1 ;
	  		
	  		$('$fieldname').value = x +',' +y;
	  	},
	  	limit:{x:[$offsetleft, 0], y:[$offsettop, 0 ]}});
	  })";
	  $document->addScriptDeclaration($script);
	  return $str;
	}
}

?>