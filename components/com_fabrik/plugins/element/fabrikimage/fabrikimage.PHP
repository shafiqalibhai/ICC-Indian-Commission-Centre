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

class FabrikModelFabrikImage  extends FabrikModelElement {

	var $_pluginName = 'image';

	var $ignoreFolders = array('cache', 'lib', 'install', 'modules', 'themes', 'upgrade', 'locks', 'smarty', 'tmp');
	/**
	 * Constructor
	 */

	function __construct()
	{
		parent::__construct();
	}

	/**
	 * get default value
	 *
	 * @param array $data
	 * @param int $repeatCounter
	 * @param array options
	 * @return string
	 */

	function getDefaultValue( $data, $repeatCounter = 0, $opts = array() )
	{
		if (is_null( $this->defaults )) {
			$this->defaults = array();
		}
		if (!array_key_exists( $repeatCounter, $this->defaults )) {
			$groupModel =& $this->_group;
			$group			=& $groupModel->getGroup();
			$joinid			= $group->join_id;
			$formModel 	=& $this->_form;
			$element		=& $this->getElement();
			$tableModel =& $this->_table;
			$params 		=& $this->getParams();
			$default	 	=& $params->get('imagefile');
			$table 			=& $tableModel->getTable();

			if ($element->eval == "1") {
				$default = @eval( stripslashes( $default ) );
			}

			$name = $this->getFullName( false, true, false );

			if ($groupModel->isJoin()) {
				if ($groupModel->canRepeat()) {
					if (array_key_exists( 'join', $data ) && array_key_exists( $joinid, $data['join']) && is_array($data['join'][$joinid]) &&  array_key_exists( $name, $data['join'][$joinid] ) && array_key_exists( $repeatCounter, $data['join'][$joinid][$name] )) {
						$default = $data['join'][$joinid][$name][$repeatCounter];
					}
				} else {
					if (array_key_exists( 'join', $data ) && array_key_exists( $joinid, $data['join']) && is_array($data['join'][$joinid]) && array_key_exists( $name, $data['join'][$joinid] )) {
						$default = $data['join'][$joinid][$name];
					}
				}
			} else {
				if ($groupModel->canRepeat()) {
					//repeat group NO join
					if (array_key_exists( $name, $data )) {
						if (is_array( $data[$name] )) {
							//occurs on form submission for fields at least
							$a = $data[$name];
						} else {
							//occurs when getting from the db
							$a = 	explode( $this->_groupSplitter, $data[$name] );
						}
						if (array_key_exists( $repeatCounter, $a )) {
							$default = $a[$repeatCounter];
						}
					}

				} else {
					if (array_key_exists( $name, $data )) {
						//$default = $data[$name.'_folder'].'/'.$data[$name];
						$default = $data[$name];
					}
				}
			}
			$element->default = $default;
			//stops this getting called from form validation code as it messes up repeated/join group validations
			if (array_key_exists('runplugins', $opts) && $opts['runplugins'] == 1){
				$formModel->getPluginManager()->runPlugins( 'onGetElementDefault', $formModel, 'form', $this );
			}
			if (is_array( $element->default )) {
				$element->default = implode( ',', $element->default );
			}
			$this->defaults[$repeatCounter] = $element->default;
				
		}
		return $this->defaults[$repeatCounter];
	}

	/**
	 * shows the data formatted for the table view
	 * @param string data
	 * @param object all the data in the tables current row
	 * @return string formatted value
	 */

	function renderTableData( $data, $oAllRowsData )
	{
		$data = explode($this->_groupSplitter, $data);
		$params =& $this->getParams();
		$selectImage_root_folder = $params->get( 'selectImage_root_folder', '');
		$showImage = $params->get( 'show_image_in_table', 0 );
		for ($i=0; $i <count($data); $i++) {
			if ($showImage) {
				$data[$i] = "<img src='" . (COM_FABRIK_LIVESITE .'/images/stories/' . $selectImage_root_folder . '/' . $data[$i] ) ."' alt=\"$data[$i]\" />";
			}
		}
		$data = implode($this->_groupSplitter, $data);
		return parent::renderTableData($data, $oAllRowsData );
	}

	/**
	 * formats the posted data for insertion into the database
	 * @param mixed thie elements posted form data
	 * @param array posted form data
	 */

	function storeDatabaseFormat( $val, $data )
	{
		$groupModel =& $this->getGroup();
		$params =& $this->getParams();
		$selectImage_root_folder = $params->get( 'selectImage_root_folder', '');
		if ($groupModel->canRepeat()) {
			if (is_array( $val )) {
				// @TODO - fix repeat group stuff
			}
		}
		else {
			$key = $this->getFullName( false, true, false );
			if (!array_key_exists( $key, $data )) {
				$element =& $this->getElement();
				$key = $element->name;
			}
			$retval = preg_replace("#^$selectImage_root_folder#", '', $data[$key . '_folder']) . $data[$key . '_image'];
			return $retval;
		}
	}

	/**
	 * shows the data formatted for RSS export
	 * @param string data
	 * @param object all the data in the tables current row
	 * @return string formatted value
	 */

	function renderTableData_rss( $data, $oAllRowsData )
	{
		$params =& $this->getParams();
		$selectImage_root_folder = $params->get( 'selectImage_root_folder', '' );
		return "<img src='" . COM_FABRIK_LIVESITE  . 'images/stories/' . $selectImage_root_folder . '/'. $data . "' />";
	}

	/**
	 * draws the form element
	 * @param int repeat group counter
	 * @return string returns element html
	 */

	function render( $data, $repeatCounter = 0 )
	{
		$params 			=& $this->getParams();
		$name 				= $this->getHTMLName( $repeatCounter );
		$value 				= $this->getDefaultValue( $data, $repeatCounter );
		$id 					= $this->getHTMLId( $repeatCounter );
		$defaultImage = COM_FABRIK_LIVESITE . 'images/stories/'.$value;
		$canSelect 		= $params->get( 'image_front_end_select', '0' );
		$str = "<div class='fabrikSubElementContainer'>";
		$str .= '<img src="' . $defaultImage . '" alt="'. $value .'" id="' . $id . '_img"/>'."\n";

		$rootFolder = $params->get( 'selectImage_root_folder' );

		if ($canSelect && $this->_editable ) {

			$images 	= array();
			$folders 	= array();
			if (array_key_exists( $name, $data )) {
				if (trim($value) == '') {
					$path = "/";
				} else {
					$bits = explode( "/", $value );
					if (count($bits) > 1){
						$path = "/" . array_shift( $bits ) . "/";
						$val = array_shift( $bits );
					} else {
						$path = $rootFolder;
					}
				}
			} else {
				$path 		= $rootFolder;
			}
			$fullpath = JPATH_SITE . '/images/stories';
			$folders[] = JHTML::_('select.option', '/', '/');
			//$this->readImages( $fullpath.'/'.$path, "/", $folders, $images, $this->ignoreFolders );
			$this->readImages( $fullpath.'/'.$rootFolder, "/", $folders, $images, $this->ignoreFolders );
			$str  .=	"<br/>" .	JHTML::_('select.genericlist',  $folders, $id . '_folder', 'class="inputbox" size="1" ', 'value', 'text', $path );
			//$str  .=	"<br/>" .	JHTML::_('select.genericlist', $images['/'], $id . '_image', 'class="inputbox" ' , 'value', 'text', $value );
			$images 	= array();
			$folders 	= array();
			$this->readImages( $fullpath.'/'.$path, "/", $folders, $images, $this->ignoreFolders );
			$str  .=	"<br/>" .	JHTML::_('select.genericlist', $images['/'], $id . '_image', 'class="inputbox" ' , 'value', 'text', $value );//was $val but thats not set??
			$str 	.= "<input type='hidden' id='$id' name='$name' value='" . $value . "' />";
		}
		$str .= "</div>";
		return $str;
	}

	function ajax_files()
	{
		$folder = JRequest::getVar('folder');
		$pathA = JPath::clean(JPATH_SITE.DS.$folder);
		$folder = array();
		$files = array();
		$this->readImages( $pathA, "/", $folders, $images, $this->ignoreFolders );
		echo FastJSON::encode($images['/']);
	}

	/**
	 * return the javascript to create an instance of the class defined in formJavascriptClass
	 * @return string javascript to create instance. Instance name must be 'el'
	 */

	function elementJavascript( $repeatCounter )
	{
		global $Itemid;
		$params =& $this->getParams();
		$element =& $this->getElement();
		$id = $this->getHTMLId( $repeatCounter );
		$selRoot = COM_FABRIK_LIVESITE  . 'images/stories/' . $params->get( 'selectImage_root_folder', '' );
		$opts =& $this->getElementJSOptions( $repeatCounter );
		$opts->liveSite = COM_FABRIK_LIVESITE;
		$opts->rootPath =  'images/stories/' . $params->get( 'selectImage_root_folder', '' );
		$opts->Itemid = $Itemid;
		$opts->id = $element->id;
		$opts = FastJSON::encode($opts);
		return " new fbImage('$id', $opts)" ;
	}

	/**
	 * load the javascript class that manages interaction with the form element
	 * should only be called once
	 * @return string javascript class file
	 */

	function formJavascriptClass()
	{
		FabrikHelperHTML::script( 'javascript.js', 'components/com_fabrik/plugins/element/fabrikimage/', true );
	}

	/**
	 *
	 */

	function getFieldDescription()
	{
		return "TEXT";
	}

	/**
	 *
	 */

	function getAdminLists( &$lists )
	{

		/**
		 * IMPORTANT NOTE FOR HACKERS!
		 * 	if your images folder contains massive sub directories which you dont want fabrik
		 * accessing (and hance slowing down to a crawl the loading of this page)
		 * then put the folders in the $ignoreFolders array
		 */
		$params =& $this->getParams();

		$images 	= array();
		$folders 	= array();
		$path 		= $params->get('imagepath', '/');
		$file 		= $params->get('imagefile');
		$fullpath = JPATH_SITE . '/images/stories';

		$folders[] = JHTML::_('select.option', '/', '/');

		$model =& JModel::getInstance( 'Element', 'FabrikModel' );
		$model->readImages( $fullpath, "/", $folders, $images, $this->ignoreFolders );

		$lists['folders'] 	=	 	JHTML::_('select.genericlist',  $folders, 'params[imagepath]', 'class="inputbox" size="1" ', 'value', 'text', $path );

		$javascript	= "onchange=\"previewImage( )\" onfocus=\"previewImage( )\"";
		$lists['imagefiles']	= JHTML::_('select.genericlist', $images[$path], 'params[imagefile]', 'class="inputbox" size="10" multiple="multiple" '. $javascript , 'value', 'text', $file );

		$defRootFolder = $params->get( 'selectImage_root_folder', '' );
		$lists['selectImage_root_folder'] 	= JHTML::_('select.genericlist',  $folders, 'params[selectImage_root_folder]', "class=\"inputbox\"  size=\"1\" ", 'value', 'text', $defRootFolder );
	}

	/**
	 *
	 */

	function renderAdminSettings( &$lists )
	{
		$params =& $this->getParams();
		$pluginParams =& $this->getPluginParams();
		$this->getAdminLists( $lists );
		?>
<script language="javascript" type="text/javascript">
			/* <![CDATA[ */
			function setImageName(){
				var image = document.adminForm.imagefiles;
				var linkurl = document.getElementsByName('params[image_path]')[0];
				linkurl.value =  (image).getValue();
			}
			
			function previewImage(){
				var root = '<?php echo COM_FABRIK_LIVESITE ;?>';
				var file = $('paramsimagefile').getValue();
				var folder = $('paramsimagepath').getValue();
				$('view_imagefiles').src = root + "images/stories/"  + file;
			}
			
			window.addEvent('domready', function(){
			$('paramsimagepath').addEvent('change', function(e){
				var event = new Event(e);
				event.stop;
				var folder = '<?php echo 'images/stories' . $params->get( 'selectImage_root_folder', '' ); ?>' + $(event.target).getValue();
				var url = '<?php echo COM_FABRIK_LIVESITE ;?>index.php?option=com_fabrik&format=raw&controller=plugin&task=pluginAjax&g=element&plugin=fabrikimage&method=ajax_files';
				var myAjax = new Ajax(url, { method:'post',
			'data':{'folder':folder}, 
			onComplete: function(r){
				var opts = eval(r);
				var folder = '<?php echo $params->get( 'selectImage_root_folder', '' ); ?>' + $(event.target).getValue();
				$('paramsimagefile').empty()
				opts.each( function(opt){
					$('paramsimagefile').adopt(
						new Element('option', {'value':folder + opt.value}).appendText(opt.text)
					);
				}.bind(this));
				previewImage();
			}.bind(this)
		}).request();
		
			});
			 previewImage();
			});
			/* ]]> */
		</script>
<div id="page-<?php echo $this->_name;?>" class="elementSettings"
	style="display: none">
<table class="admintable">
	<tr>
		<td class="paramlist_key"><?php echo JText::_('Default image' ); ?></td>
		<td><?php echo $lists['folders'] ;echo "<br />" . $lists['imagefiles']; ?>
		<img name="view_imagefiles" id="view_imagefiles"
			src="<?php echo COM_FABRIK_LIVESITE . 'images/stories/'. $params->get('image_path');?>"
			width="100" /> <br />
		</td>
	</tr>
	<tr>
		<td class="paramlist_key"><?php echo JText::_( 'Root folder' );?>:</td>
		<td><?php echo $lists['selectImage_root_folder'];?></td>
	</tr>
</table>
		<?php echo $pluginParams->render();?></div>
		<?php
	}


	/**
	 * used to format the data when shown in the form's email
	 * @param string
	 * @param array form records data
	 * @param int repeat group counter
	 * @return string formatted value
	 */

	function getEmailValue( $value, $data, $c )
	{
		return $this->render( $data );
	}
}
?>