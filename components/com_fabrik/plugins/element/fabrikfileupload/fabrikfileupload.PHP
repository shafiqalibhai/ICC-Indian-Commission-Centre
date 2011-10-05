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

require_once( JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'models'.DS.'element.php' );

require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'image.php');

class FabrikModelFabrikFileupload  extends FabrikModelElement {

  /**
   * Constructor
   */

  function __construct()
  {
    parent::__construct();
    $this->_is_upload = true;
  }

  /**
   * decide whether to ingore data when updating a record
   *
   * @param string $val
   * @return bol true if you shouldnt update the data
   */

  function ignoreOnUpdate( $val )
  {
    
    $fullName = $this->getFullName( true, true, false );
    $groupModel =& $this->_group;
    $return = false;
    if ($groupModel->canRepeat()) {
      //$$$rob could be the case that we aren't uploading an element by have removed
      //a repeat group (no join) with a file upload element, in this case processUpload has the correct
      //file path settings.
      return false;
      
      /*$return = true;
      foreach ($_FILES[$fullName]['name'] as $n) {
        if ($n != '') {
          $return = false;
        }
      }*/
    } else {
      if (@$_FILES[$fullName]['name'] == '') {
        return true;
      } else {
        return false;
      }
    }
    return $return;
  }

  /**
   * run in ajax submit mode (e.g. fancy upload file uploader)
   *  @return bol true if the element type forces the form to
   */

  function requiresAJAXSubmit()
  {
    return ($this->getParams()->get( 'fu_fancy_upload' )) ? true : false;
  }

  /**
   * return tehe javascript to create an instance of the class defined in formJavascriptClass
   * @return string javascript to create instance. Instance name must be 'el'
   */

  function elementJavascript( $repeatCounter )
  {
    $element =& $this->getElement();
    $params =& $this->getParams();
    $id = $this->getHTMLId( $repeatCounter );
    $opts =& $this->getElementJSOptions( $repeatCounter );
    $opts->liveSite = COM_FABRIK_LIVESITE;
    $opts->defaultImage = $params->get( 'default_image' );
    $opts = FastJSON::encode($opts);
    return "new FbFileUpload('$id', $opts)" ;
  }

  /**
   * load the javascript class that manages interaction with the form element
   * should only be called once
   * @return string javascript class file
   */

  function formJavascriptClass()
  {
    FabrikHelperHTML::script( 'javascript.js', 'components/com_fabrik/plugins/element/fabrikfileupload/', false );
  }

  /**
   * shows the data formatted for the table view
   * @param string data
   * @param object all the data in the tables current row
   * @return string formatted value
   */

  function renderTableData( $data, $oAllRowsData )
  {
    $data = explode( $this->_groupSplitter, $data );
    $params =& $this->getParams();
    for ($i=0; $i <count( $data ); $i++) {
      $data[$i] = $this->_renderTableData( $data[$i], $oAllRowsData );
    }
    $data = implode($this->_groupSplitter, $data);
    return parent::renderTableData($data, $oAllRowsData );
  }

  /**
   * examine the file being displayed and load in the corresponding
   * class that deals with its display
   * @param string file
   */

  function loadElement( $file )
  {
    $ext = strtolower( JFile::getExt( $file ));
    if (JFile::exists( COM_FABRIK_FRONTEND.DS.'plugins'.DS.'element'.DS.'fabrikfileupload'.DS.'element'.DS.$ext.'.php' )) {
      require( COM_FABRIK_FRONTEND.DS.'plugins'.DS.'element'.DS.'fabrikfileupload'.DS.'element'.DS.$ext.'.php' );
    } else {
      require( COM_FABRIK_FRONTEND.DS.'plugins'.DS.'element'.DS.'fabrikfileupload'.DS.'element'.DS.'default.php' );
    }
    return $render;
  }

  /**
   * Display the file in the table
   *
   * @param strng $data
   * @param array $oAllRowsData
   * @return string
   */

  function _renderTableData( $data, $oAllRowsData )
  {
    // $$$ hugh - if no file, bail out
    // $$$ rob - you cant do this as the default image wont get rendered
    // $$$ hugh - OOOOPS!!
    /*if (empty($data) || !JFile::exists( COM_FABRIK_BASE.DS.$data )) {
     return '';
     }*/
    $element =& $this->getElement();
    $params =& $this->getParams();
    if ($params->get( 'fu_show_image_in_table' )  == '0') {
      $render =& $this->loadElement( 'default' );
    } else {
      $render =& $this->loadElement( $data );
    }
    if (empty($data) || !JFile::exists( COM_FABRIK_BASE.DS.$data )) {
      $render->output = '';
    } else {
      $render->renderTableData( $this, $params, $data );
    }
    if ($render->output == '' && $params->get( 'default_image' ) != ''){
      $render->output = "<img src='{$params->get('default_image')}' alt='image' />";
    }
    return $render->output;
  }

  /**
   * do we need to include the lighbox js code
   *
   * @return bol
   */

  function requiresLightBox()
  {
    $params =& $this->getParams();
    if (JRequest::getVar('view') == 'table') {
      if ($params->get( 'fu_show_image_in_table' )  == '0') {
        return false;
      }
    }
    return true;
  }

  /**
   * get the thumbnail file for the file given
   *
   * @param string $file
   * @return string thumbnail
   */

  function _getThumb( $file )
  {
    $params =& $this->getParams();
    $w =& new FabrikWorker();

    $ulDir = JPath::clean($params->get('ul_directory'));
    $ulDir = str_replace("\\", "/", $ulDir);

    //replace things like $my->id may barf on other stuff
    $afile = str_replace(JURI::root(), '', $file);
    $afile = ltrim($afile, "/");
    $ulDir = ltrim($ulDir, "/");

    $ulDirbits = explode('/', $ulDir);
    $filebits = explode('/', $afile);

    $match = array();
    $replace = array();
    for ($i=0; $i < count( $filebits ); $i++) {
      if (array_key_exists( $i, $ulDirbits ) && $filebits[$i] != $ulDirbits[$i]) {
        $match[] = $ulDirbits[$i];
        $replace[] = $filebits[$i];
      }
    }
    $ulDir = str_replace($match, $replace, $ulDir);
    $thumbdir = str_replace($match, $replace, $params->get('thumb_dir'));

    $ulDir = $w->parseMessageForPlaceHolder( $ulDir );
    $thumbdir = $w->parseMessageForPlaceHolder( $thumbdir );
    $file = str_replace( $ulDir, $thumbdir , $file );
    $file = $w->parseMessageForPlaceHolder( $file );
    $f = basename($file);
    $dir = dirname($file);
    $file = $dir . '/' . $params->get('thumb_prefix') .  $f;
    return $file;
  }

  function _getCropped( $file )
  {
    $params =& $this->getParams();
    $w =& new FabrikWorker();

    $ulDir = JPath::clean($params->get('ul_directory'));
    $ulDir = str_replace("\\", "/", $ulDir);

    //replace things like $my->id may barf on other stuff
    $afile = str_replace(JURI::root(), '', $file);
    $afile = ltrim($afile, "/");
    $ulDir = ltrim($ulDir, "/");

    $ulDirbits = explode('/', $ulDir);
    $filebits = explode('/', $afile);

    $match = array();
    $replace = array();
    for ($i=0; $i < count( $filebits ); $i++) {
      if (array_key_exists( $i, $ulDirbits ) && $filebits[$i] != $ulDirbits[$i]) {
        $match[] = $ulDirbits[$i];
        $replace[] = $filebits[$i];
      }
    }
    $ulDir = str_replace($match, $replace, $ulDir);
    $thumbdir = str_replace($match, $replace, $params->get('fileupload_crop_dir'));

    $ulDir = $w->parseMessageForPlaceHolder( $ulDir );
    $thumbdir = $w->parseMessageForPlaceHolder( $thumbdir );
    $file = str_replace( $ulDir, $thumbdir , $file );
    $file = $w->parseMessageForPlaceHolder( $file );
    $f = basename($file);
    $dir = dirname($file);
    $file = $dir . '/'  .  $f;
    return $file;
  }

  /**
   * formats the posted data for insertion into the database
   * @param mixed thie elements posted form data
   * @param array posted form data
   */

  function storeDatabaseFormat( $val, $data )
  {
    //val already contains group splitter from processUpload() code
    return $val;
  }

  /**
   * checks the posted form data against elements INTERNAL validataion rule - e.g. file upload size / type
   * @param string elements data
   * @param int repeat group counter
   * @return bol true if passes / false if falise validation
   */

  function validate( $data, $repeatCounter = 0 )
  {
    $params 		=& $this->getParams();
    $groupModel =& $this->_group;
    $group 			=& $groupModel->getGroup();
    $this->_validationErr = '';
    $errors = array();
    $elName = $this->getFullName();
    $elName = str_replace('[]', '', $elName); //remove any repeat group labels
    if ($group->is_join) {
      $elTempName = str_replace('join', '', $elName);
      $elTempName = str_replace('[', '', $elTempName);
      $joinArray = explode(']', $elTempName);
      if (!array_key_exists( 'join', $_FILES)){
        return true;
      }
      $aFile 	=  $_FILES['join'];
      $myFileName = $aFile['name'][$joinArray[0]][$joinArray[1]];
      $myFileSize = $aFile['size'][$joinArray[0]][$joinArray[1]];
    } else {
      if (!array_key_exists( $elName, $_FILES)) {
        return true;
      }
      $aFile 	=  $_FILES[$elName];
      if ($groupModel->canRepeat()) {
        $myFileName = $aFile['name'][$repeatCounter];
        $myFileSize = $aFile['size'][$repeatCounter];
      } else {
        $myFileName = $aFile['name'];
        $myFileSize = $aFile['size'];
      }
    }
    $ok = true;

    if (!$this->_fileUploadFileTypeOK( $myFileName )) {
      $errors[] = JText::_( 'File type not allowed' );
      $ok = false;
    }
    if (!$this->_fileUploadSizeOK( $myFileSize )) {
      $ok = false;
      $mySize = $myFileSize / 1000;
      $errors[] = JText::sprintf( 'The file is too large. The maximum file size is %s KB and your file is %s KB.',  $params->get('ul_max_file_size'), $mySize );
    }
    $filepath = $this->_getFilePath( $repeatCounter );
    jimport('joomla.filesystem.file');
    if (JFile::exists( $filepath )) {
      if (!$params->get( 'ul_file_increment', 0 )) {
        $errors[] = JText::_('A file of that name already exists');
        $ok = false;
      }
    }
    $this->_validationErr = implode('<br />', $errors);
    return $ok;
  }

  function _getAllowedExtension()
  {
    $params =& $this->getParams();
    $allowedFiles = $params->get('ul_file_types') ;
    if ($allowedFiles != '') {
      $aFileTypes = explode( ",", $allowedFiles );
    } else {
      $mediaparams =& JComponentHelper::getParams( 'com_media' );
      $aFileTypes = explode( ',', $mediaparams->get( 'upload_extensions' ));
    }
    return $aFileTypes;
  }

  /**
   * This checks the uploaded file type against the csv specified in the upload
   * element
   * @access PRIVATE
   * @param string filename
   * @return bol true if upload file type ok
   */

  function _fileUploadFileTypeOK( $myFileName )
  {
    $aFileTypes = $this->_getAllowedExtension();
    if ($myFileName == '') {
      return true;
    }
    $bits = explode( ".", $myFileName );
    $curr_f_ext = strtolower( array_pop( $bits ) );
    if (in_array( $curr_f_ext, $aFileTypes ) || in_array( ".".$curr_f_ext, $aFileTypes )) {
      return true;
    }
    return false;
  }

  /**
   * This checks that thte fileupload size is not greater than that specified in
   * the upload element
   * @access PRIVATE
   * @param string file size
   * @return bol true if upload file type ok
   */

  function _fileUploadSizeOK( $myFileSize )
  {
    $params =& $this->getParams();
    $max_size = $params->get('ul_max_file_size') * 1000;
    if ($myFileSize <= $max_size) {
      return true;
    }
    return false;
  }

  /**
   * OPTIONAL
   */

  function processUpload()
  {
    //@TODO: test in joins
    $params =& $this->getParams();
    $request 	=& JRequest::get( 'request' );
    $name 		= $this->getFullName( true, true, false );
    $groupModel =& $this->_group;
    $files = array();
     
    $origData = explode( $this->_groupSplitter, $this->_form->_origData->$name );
    if ($groupModel->canRepeat()) {
      for ($i=0; $i<count($_FILES[$name]['name']); $i++) {
        // $$$ hugh @TODO - prolly need to change this, see comment below
        $myFileDir = ( is_array($request[$name][$i]) && array_key_exists( 'ul_end_dir', $request[$name][$i])) ? $request[$name][$i]['ul_end_dir'] : '';

        $file = array(
					'name' 			=> $_FILES[$name]['name'][$i],
					'type' 			=> $_FILES[$name]['type'][$i],
					'tmp_name' 	=> $_FILES[$name]['tmp_name'][$i],
					'error' 		=> $_FILES[$name]['error'][$i],
					'size' 			=> $_FILES[$name]['size'][$i]
        );
        if ($file['name'] != '') {
          $files[] = $this->_processIndUpload( $file, $myFileDir, $i );
        } else {
          $files[] = @$origData[$i];
        }
      }
    } else {
      // $$$ hugh - seem to need the _raw version to get the array-ified data
      // prolly need to fix canRepeat code above as well
      //$myFileDir = @$request[$name]['ul_end_dir'];
      $myFileDir = @$request[$name . '_raw']['ul_end_dir'];
      $files[] = $this->_processIndUpload( $_FILES[$name], $myFileDir );
    }
    $remove = array_diff( $origData, $files );
    if ($params->get('upload_delete_image')) {
	    foreach ($remove as $filename) {
	      $this->deleteFile( $filename );
	    }
    }
    $strfiles = implode( $this->_groupSplitter, $files );
    JRequest::setVar( $name . "_raw", $strfiles );
    JRequest::setVar( $name, $strfiles );
  }

  function deleteFile( $filename )
  {
    $file = JPath::clean( JPATH_SITE . DS . $filename );
    $thumb = JPath::clean( $this->_getThumb($file) );
    if (JFile::exists( $file )) {
      JFile::delete( $file );
    }
    if (JFile::exists( $thumb )) {
      JFile::delete( $thumb );
    }
  }

  /**
   * used in notempty validation rule
   */

  function dataConsideredEmpty( $data, $repeatCounter )
  {
    if ((int)JRequest::getVar('rowid', 0) != 0) {
      return false;
    }
    $name = $this->getFullName(false, true, false);
    $file = JRequest::getVar( $name, '', 'files');
    return $file['name'] == '' ? true : false;
  }


  /**
   * it parameters set so we make a cropped image
   * (non-PHPdoc)
   * @see components/com_fabrik/models/FabrikModelElement#onStoreRow($data)
   */
  function onStoreRow($tmpdata)
  {

    $params =& $this->getParams();
    if ($params->get( 'fileupload_crop' ) == false) {
      return;
    }
    $toploc = explode( ",", JRequest::getVar( $this->getFullName() . "_cropcoords"), array(0,0) );

    $k = $this->getElement()->name . "_raw";
    if (array_key_exists( $k, $tmpdata ) && (int)JRequest::getVar( 'rowid' ) === 0) {
      $file = COM_FABRIK_BASE .$tmpdata[$k];
    } else {
      $file = str_replace(COM_FABRIK_LIVESITE, COM_FABRIK_BASE, JRequest::getVar($this->getFullName() . "_dragger_thumb"));
    }

    if (!JFile::exists( $file )) {
      return;
    }
    $newwidth = $params->get('fileupload_crop_width', 50);
    $newheight = $params->get('fileupload_crop_height', 50);

    list( $sourcewidth, $sourceheight ) = getimagesize( $file );

    $cropped = imagecreatetruecolor( $newwidth, $newheight );
    $source = imagecreatefromjpeg($file);
    $cropLeft =  (int)$toploc[0];
    $cropTop = (int)$toploc[1];
    $destx= 0;
    $desty = 0;

    $dest = $params->get('fileupload_crop_dir');
    $w =& new FabrikWorker();
    $destfolder = $w->parseMessageForPlaceHolder( COM_FABRIK_BASE . DS.$dest);
    if (!JFolder::exists( $destfolder )) {
      JFolder::create($destfolder);
    }
    $dest = $destfolder . DS. basename($file);
    $dest = JPath::clean($dest);

    $ok =  imagecopy( $cropped, $source, $destx, $desty, $cropLeft, $cropTop, $newwidth, $newheight);
    if(!$ok){
      JError::raiseWarning(21, "could not make cropped image - $dest, source = $file");
    }

    $ext = JFile::getExt($file);

    if ($ext == "jpeg" || $ext == 'jpg') {
      if (!imagejpeg( $cropped, $dest )) {
        //go figure sometimes this returns false but the image is saved??
        JError::raiseWarning( 21, "could not create image - $dest" );
      }
      ob_start();
      imagejpeg( $cropped, "", 100 );
      $image = ob_get_contents();
      ob_end_clean();
      JFile::write( $dest, $image );
    } else {
      if ($ext == "png") {
        ob_start();
        imagepng( $cropped, "", 100 );
        $image = ob_get_contents();
        ob_end_clean();
        JFile::write( $dest, $image );
      } else {
        if (function_exists("imagegif")) {
          ob_start();
          imagegif( $cropped, "", 100 );
          $image = ob_get_contents();
          ob_end_clean();
          JFile::write( $dest, $image );
        }else{
          /* try using imagemagick to convert gif to png:*/
          $image_file = imageHelper::imgkConvertImage( $image_file, $baseDir, $destDir, ".png" );
        }
      }
    }
    $file = str_replace(COM_FABRIK_BASE, '', $file);
    $dest = str_replace(COM_FABRIK_BASE, '', $dest);
  }

  /**
   * process the uplad
   * @access private
   *
   * @param array $file info
   * @param string user selected upload folder
   * @param int repeat group counter
   * @return string location of uploaded file
   */

  function _processIndUpload( $file, $myFileDir ='', $repeatGroupCounter = 0 )
  {
    global $mainframe;
    $myFileName = $file['name'];
    $tmpFile 		= $file['tmp_name'];
    $uploader 	=& $this->_form->getUploader();
    $params 		=& $this->getParams();
    if ($params->get( 'ul_file_types' ) == '') {
      $params->set('ul_file_types', implode(',', $this->_getAllowedExtension()));
    }
    $err		= null;
    // Set FTP credentials, if given
    jimport('joomla.client.helper');
    JClientHelper::setCredentialsFromRequest('ftp');

    if ( $myFileName == '' ) {
      return;
    }
    $filepath = $this->_getFilePath( $repeatGroupCounter );

    if (!uploader::canUpload( $file, $err, $params )) {
      JError::raiseNotice(100, $file .': '. JText::_($err));
    }

    if (JFile::exists($filepath) &&  $params->get('ul_file_increment', 0) ){
      $filepath = uploader::incrementFileName( $filepath, $filepath, 1 );
    }
     
    if (!JFile::upload($tmpFile, $filepath)) {
      $uploader->moveError = true;
      JError::raiseWarning(100, JText::_("Error. Unable to upload file (from $tmpFile to $filepath)"));
      return;
    }
    jimport('joomla.filesystem.path');

    JPath::setPermissions( $filepath );
	
	// $$$ hugh @TODO - shouldn't we check to see if it's actually an image before we do any of this stuff???
	
    //resize main image
    $oImage 		= imageHelper::loadLib( $params->get( 'image_library' ) );
    $mainWidth 		= $params->get('fu_main_max_width');
    $mainHeight 	= $params->get('fu_main_max_height');

    if ($mainWidth != '' || $mainHeight != '') {
      $oImage->resize( $mainWidth, $mainHeight, $filepath, $filepath );
    }

    if ($params->get( 'make_thumbnail' ) == '1') {
      $thumbPath 		=  JPath::clean( JPATH_SITE . DS .$params->get('thumb_dir') . DS . $myFileDir . DS);
      $w =& new FabrikWorker();
      $thumbPath = $w->parseMessageForPlaceHolder( $thumbPath );

      $thumbPrefix 	= $params->get('thumb_prefix');
      $maxWidth 		= $params->get('thumb_max_width');
      $maxHeight 		= $params->get('thumb_max_height');
      if ($thumbPath != '') {
        $uploader->_makeRecursiveFolders( $thumbPath, '0777' );
      }
      $destThumbFile =  JPath::clean( $thumbPath . DS . $thumbPrefix . basename($filepath) );
      $oImage->resize( $maxWidth, $maxHeight, $filepath, $destThumbFile );
      if(!JPath::setPermissions( $thumbPath )){
        //JError::raiseWarning(21, 'Couldnt reset thumbnail folder permissions');
      }
    }


    JPath::setPermissions( $filepath );
    $res = str_replace(JPATH_SITE, '', $filepath);
    return $res;
  }

  /**
   * get the full server file path for the upload, including the file name i
   * @param int repeat group counter
   * @return string path
   */

  function _getFilePath( $repeatCounter = 0 )
  {
    $aData   =& JRequest::get( 'post' );
    $elName   = $this->getFullName( true, true, false );
    $elNameRaw = $elName . '_raw';
    $params   =& $this->getParams();
    //@TODO test with fileuploads in join groups

    $groupModel =& $this->_group;
    // $$$ hugh - for some reason we need $elName for repeat groups, and $elNameRaw for onesies
    if ($groupModel->canRepeat()) {
      $myFileDir = (array_key_exists( $elName, $aData ) && is_array( $aData[$elName][$repeatCounter] )) ? @$aData[$elName][$repeatCounter]['ul_end_dir'] : '';
      $myFileName   = @$_FILES[$elName]['name'][$repeatCounter];
    } else {
      $myFileDir    = array_key_exists( $elNameRaw, $aData ) && is_array( $aData[$elNameRaw] ) ? @$aData[$elNameRaw]['ul_end_dir'] : '';
      $myFileName   = @$_FILES[$elName]['name'];
    }
    $folder = $params->get( 'ul_directory' );
    $folder  = $folder . DS . $myFileDir;
    $folder = JPath::clean( JPATH_SITE . DS . $folder );
    $w =& new FabrikWorker();
    $folder = $w->parseMessageForPlaceHolder( $folder );

    JPath::check($folder);
    $uploader =& $this->_form->getUploader();
    $uploader->_makeRecursiveFolders( $folder, '0777' );
    $p =$folder . DS . $myFileName;
    return JPath::clean( $p );
  }

  /**
   * draws the form element
   * @param int repeat group counter
   * @return string returns element html
   */

  function render( $data, $repeatCounter = 0 )
  {
    $id 			= $this->getHTMLId( $repeatCounter );
    $name 		= $this->getHTMLName( $repeatCounter );
    $element 	=& $this->getElement();
    $params 	=& $this->getParams();
    if ($element->hidden == '1') {
      return $this->getHiddenField( $name, $data[$name], $id );
    }
    $str 			= "<div class='fabrikSubElementContainer' id=''>";

    $value = $this->getDefaultValue( $data, $repeatCounter );
    $ulDir = $params->get( 'ul_directory' );

    if ($params->get( 'fu_show_image' )  == '1') {
      $render =& $this->loadElement( $value );
      if ($value != '' && JFile::exists( COM_FABRIK_BASE.DS.$value )) {
        // $$$ hugh - we're not rendering table data!
        //$render->renderTableData( $this, $params, $value );
        $render->render( $this, $params, $value );
        $str .= $render->output . "<br />";
      } else {
        $str .= '';
      }
       
    }
    if (!$this->_editable) {
      return $str . "</div>";
    }
    if ($params->get('upload_allow_folderselect') == '1') {
      $rDir		= JPATH_SITE . "/" .  $params->get('ul_directory');
      $images 	= array( );
      $folders 	= array( );
      $this->readImages( $rDir, "/", $folders, $images, array() );
      $folderOpts = array();
      if (is_array( $folders )) {
        foreach ($folders as $oDir) {
          $dir = $oDir->value;
          $newFolderopt = str_replace( $rDir, '', $dir );
          $folderOpts[] = JHTML::_('select.option', $newFolderopt );
        }
      }
      $folderList = JHTML::_('select.genericlist',  $folderOpts, $name . '[ul_end_dir]', ' class="inputbox" size="1" ', 'value', 'text' );
      $str .= $folderList . "<br />";
    }
    $str .= '<input class="fabrikinput" name="'.$name.'" type="file" id="'.$id.'" />'."\n";

    $str .= "<span id='{$id}upload-clear'></span>";

    $str .= "<ul class='upload-queue' id='{$id}upload-queue'>
		<li style='display: none' />
		</ul>";

    if ($params->get( 'fu_fancy_upload' )) {
      JHTML::script('swf.js' );
      JHTML::script('uploader.js' );

      static $uploaders;

      if (!isset($uploaders)) {
        $uploaders = array();
      }

      /*if (isset($uploaders[$id]) && ($uploaders[$id])) {
       return;
       }*/

      // Setup options object
      $opt['url']					=  null ;
      $opt['swf']					=  JURI::root(true).'/media/system/swf/uploader.swf';
      $opt['multiple']			=  '\\true';
      $opt['queued']				= '\\true';
      $opt['queueList']			=  $id.'upload-queue';
      $opt['instantStart']		=  '\\false';
      $opt['allowDuplicates']		= '\\true';
      $opt['limitSize']			=  null;
      $opt['limitFiles']			=  null;
      $opt['optionFxDuration']	=  null;
      $opt['container']			=  '\\$(\''.$id.'\').getParent()';
      $opt['types']				=  '\\{\'All Files (*.*)\': \'*.*\'}';


      // Optional functions
      $opt['createReplacement']	=  null;
      $opt['onComplete']			=  null;
      $opt['onAllComplete']		=  null;

      /*  types: Object with (description: extension) pairs, default: Images (*.jpg; *.jpeg; *.gif; *.png)
       */

      $options = JHTMLBehavior::_getJSObject($opt);

      // Attach tooltips to document
      $document =& JFactory::getDocument();
      $document->addStyleSheet('administrator/components/com_media/assets/mediamanager.css');

      $uploaderInit = 'sBrowseCaption=\''.JText::_('Browse Files', true).'\';
				sRemoveToolTip=\''.JText::_('Remove from queue', true).'\';
				window.addEvent(\'load\', function(){
				Uploader = new FancyUpload($(\''.$id.'\'), '.$options.');
				$(\''.$id.'upload-clear\').adopt(new Element(\'input\', { type: \'button\', events: { click: Uploader.clearList.bind(Uploader, [false])}, value: \''.JText::_('Clear Completed').'\' }));				});';
      $document->addScriptDeclaration($uploaderInit);

      // Set static array
      $uploaders[$id] = true;
    }
    $str .= "</div>";
    return $str;
  }

  function getFieldDescription()
  {
    return "TEXT";
  }

  function renderAdminSettings( &$lists )
  {
    $params =& $this->getParams();
    $pluginParams =& $this->getPluginParams();
    ?>
<div id="page-<?php echo $this->_name;?>" class="elementSettings"
	style="display: none"><?php $this->maxUpload(); ?> <?php echo $pluginParams->render();?>
<fieldset><legend> <?php echo JText::_('Display');?> </legend> <?php echo $pluginParams->render( 'params', 'display' );?>
</fieldset>
<fieldset><legend> <?php echo JText::_('Thumbnail');?> </legend> <?php echo $pluginParams->render( 'params', 'thumbnail' );?>
</fieldset>
<fieldset><legend> <?php echo JText::_('CROP');?> </legend> <?php echo $pluginParams->render( 'params', 'crop' );?>
</fieldset>
</div>
    <?php
  }

  /**
   * attach documents to the email
   * @param string data
   * @return string formatted value
   */

  function addEmailAttachement( $data )
  {
    /// @TODO: check what happens here with open base_dir in effect //
    $params =& $this->getParams();

    if ($params->get( 'ul_email_file' )) {
      $config		=& JFactory::getConfig();
      if (strstr( $data, JPATH_SITE )) {
        $p = str_replace(COM_FABRIK_LIVESITE , JPATH_SITE, $data);
      } else {
        $p = JPATH_SITE . DS . $data;
      }
      return $p;
    }
    return false;
  }

  /**
   * If a database join element's value field points to the same db field as this element
   * then this element can, within modifyJoinQuery, update the query.
   * E.g. if the database join element points to a file upload element then you can replace
   * the file path that is the standard $val with the html to create the image
   *
   * @param string $val
   * @param string view form or table
   * @return string modified val
   * @TODO: base the returned string completely on the params specified for the element
   * e.g. thumbnail, show image, link etc
   */

  function modifyJoinQuery( $val, $view='form' )
  {
    $params =& $this->getParams();
    if( !$params->get( 'fu_show_image', 0 ) && $view == 'form'){
      return $val;
    }
    if ($params->get( 'make_thumbnail')) {
      $ulDir = JPath::clean($params->get('ul_directory')) . DS;
      $ulDir = str_replace("\\", "\\\\", $ulDir);
      $thumbDir = $params->get('thumb_dir');
      $thumbDir = JPath::clean($params->get('thumb_dir')) . DS;
      $w =& new FabrikWorker();
      $thumbDir = $w->parseMessageForPlaceHolder( $thumbDir );
      $thumbDir = str_replace("\\", "\\\\", $thumbDir);
       
      $w =& new FabrikWorker();
      $thumbDir = $w->parseMessageForPlaceHolder( $thumbDir );

      $thumbDir .= $params->get('thumb_prefix') ;

      $str = "CONCAT('<img src=\"".COM_FABRIK_LIVESITE."',".
			"REPLACE(".
 			"REPLACE($val, '$ulDir', '".$thumbDir."')".	//replace the main image dir with thumb dir
			", '\\\', '/')".														//replace the backslashes with forward slashes
			", '\" alt=\"database join image\" />')";

    } else {
      $str = " REPLACE(CONCAT('<img src=\"".COM_FABRIK_LIVESITE. "' , $val, '\" alt=\"database join image\"/>'), '\\\', '/') ";
    }
    return $str;
  }

  /**
   * trigger called when a row is deleted
   *
   */
  function onDeleteRows( $groups )
  {
    //cant delete files from unpublished elements
    if (!$this->canUse()) {
      return;
    }

    require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'uploader.php' );
    $params =& $this->getParams();
    if ($params->get('upload_delete_image')) {
      jimport('joomla.filesystem.file');
      $elName   = $this->getFullName( true, true, false );
      foreach ($groups as $rows) {
        foreach ($rows as $row) {
          if (array_key_exists($elName."_raw", $row)) {
            $files = explode( $this->_groupSplitter, $row->{$elName."_raw"} );
            foreach ($files as $filename) {
              $file = JPath::clean( JPATH_SITE . DS . $filename );
              $thumb = JPath::clean( $this->_getThumb($file) );
              if (JFile::exists( $file )) {
                JFile::delete( $file );
              }
              if (JFile::exists( $thumb )) {
                JFile::delete( $thumb );
              }
            }
          }
        }
      }
    }
  }

  /**
   * get the max upload size allowed by the server.
   * @return int kilobyte upload size
   */

  function maxUpload()
  {
    $postkb 	= str_replace("M", "", ini_get('post_max_size')) * 1000;
    $uploadkb = str_replace("M", "", ini_get('upload_max_filesize')) * 1000;
    if ($uploadkb < $postkb) {
      $postkb = $uploadkb;
    }
    return $postkb;
  }
}
?>