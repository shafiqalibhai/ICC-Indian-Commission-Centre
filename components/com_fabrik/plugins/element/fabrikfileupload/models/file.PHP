<?php
/**
* Plugin element to render fileuploads of file type 
* @package fabrik
* @author Rob Clayburn
* @copyright (C) Rob Clayburn
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

class fileRender{
	
	function renderTableData( &$model, &$params, $file )
	{
		$this->render( $model, $params, $file );
	}
	
	function render(&$element, &$params, $file )
	{
		jimport('joomla.filesystem.file');
		$filename = basename($file);
		$ext = JFile::getExt($filename);
		$file = str_replace("\\", "/", COM_FABRIK_LIVESITE  . $file);
		$thumb_path = COM_FABRIK_BASE . '/media/com_fabrik/images/' . $ext . '.png';
		if (JFile::exists($thumb_path)) {
			$thumb_file = COM_FABRIK_LIVESITE . "/media/com_fabrik/images/" . $ext . ".png";
			$this->output = "<a class='download-archive fabrik-filetype-$ext' title='$file' href='$file'><img src='$thumb_file' alt='$filename'></a>";
		}
		else {
			$this->output = "<a class='download-archive fabrik-filetype-$ext' title='$file' href='$file'>" . $filename . "</a>";
		}
	}
}

?>