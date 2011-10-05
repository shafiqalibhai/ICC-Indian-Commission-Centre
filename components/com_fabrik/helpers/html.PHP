<?php
/**
 * @package Joomla
 * @subpackage Fabrik
 * @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// no direct access
defined('_JEXEC') or die('Restricted access');
if (!defined('COM_FABRIK_BASE')) {
  //required for editing menu options
  define( "COM_FABRIK_BASE", str_replace(DS.'administrator', '', JPATH_BASE) );
  define( "COM_FABRIK_FRONTEND",  COM_FABRIK_BASE.DS.'components'.DS.'com_fabrik' );
  define( "COM_FABRIK_LIVESITE",  JURI::base() );
}
require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'string.php' );
/**
 * Content Component HTML Helper
 *
 * @static
 * @package		Joomla
 * @subpackage	Content
 * @since 1.5
 */
class FabrikHelperHTML
{

  function packageJS()
  {

    static $packagejsincluded;

    if (JRequest::getVar('tmpl') == 'component') {
      //was commented out? but its needed to not overwrite oPackage
      //in the calendar add event form
      //if there is another reason for us to load it here then we should
      //probably run a test for the existance of oPackage before initialising it

      //inside component tmpl - package should be loaded
      $packagejsincluded = true;
      return;
    }
    if (!isset( $packagejsincluded )) {
      $document =& JFactory::getDocument();
      // Load the javascript
      FabrikHelperHTML::script( 'package.js', 'components/com_fabrik/views/package/', true );
      //dont have this inside an onload
      $script = "var oPackage = new fabrikPackage({'liveSite':'" . COM_FABRIK_LIVESITE . "'});	";
      $document->addScriptDeclaration( $script );
      $packagejsincluded = true;
    }
  }

  /**
   * @param string element select to auto create windows for  - was default = a.modal
   */

  function mocha($selector='', $params = array())
  {
    static $modals;
    static $mocha;
    $script = '';

    //test since 2.0b3 dont do anything if loading from mocha win
    if (JRequest::getVar('tmpl') == 'component') {
      return;
    }
    //end test

    $document =& JFactory::getDocument();
    $fbConfig =& JComponentHelper::getParams( 'com_fabrik' );
    // Load the necessary files if they haven't yet been loaded
    if (!isset( $mocha )) {

      // Load the javascript and css
      switch ($fbConfig->get( 'usefabrik_mootools', 0 )){
        case -1:
        case 0:
          FabrikHelperHTML::script( 'excanvas.js', 'components/com_fabrik/libs/', true );
          FabrikHelperHTML::script( 'mocha.js', 'components/com_fabrik/libs/', true );
          JHTML::stylesheet( 'mocha.css', 'components/com_fabrik/libs/mocha/css/' );
          break;
        case 1:
          FabrikHelperHTML::script( 'excanvas-compressed.js', 'components/com_fabrik/libs/mocha0.9/scripts/', true );
          FabrikHelperHTML::script( 'mocha.js', 'components/com_fabrik/libs/mocha0.9/scripts/', true );
          JHTML::stylesheet( 'mocha.css', 'components/com_fabrik/libs/mocha0.9/css/' );
          break;
      }
    }

    if (!isset( $modals )) {
      $modals = array();
    }

    $sig = md5( serialize( array( $selector,$params ) ) );
    if (isset($modals[$sig]) && ($modals[$sig])) {
      return;
    }

    $script .= "window.addEvent('load', function(){";
    if (!isset( $mocha )) {
      if (array_key_exists( 'dock', $params ) && $params['dock']) {
        $script .= "\n  var dock = new Element('div', {'id':'mochaDock'}).adopt(".
				" [new Element('div', {id:'mochaDockPlacement'}),".
				" new Element('div', {'id':'mochaDockAutoHide'})]".
				");".
				"\ndock.injectInside(document.body);";
      }
      
      //@TODO in google chrome the desktop opton needs to be defined for MochaUI.Desktop
      switch ($fbConfig->get( 'usefabrik_mootools', 0 )){
        case -1:
        case 0:
          $script .= "\n  document.mochaScreens = new MochaScreens();".
				"\n document.mochaDesktop = new MochaDesktop();";
          break;
        case 1:
          $script .= "\n  MochaUI.Desktop = new MochaUI.Desktop();";
          //"\n  MochaUI.Dock = new MochaUI.Dock();;";
          break;
      }
      $mocha = true;
    }
    if ($selector == '') {
      $script .= "\n})";
      $document->addScriptDeclaration($script);
      return;
    }

    // Setup options object
    $opt['ajaxOptions']	= (isset($params['ajaxOptions']) && (is_array($params['ajaxOptions']))) ? $params['ajaxOptions'] : null;
    $opt['size']		= (isset($params['size']) && (is_array($params['size']))) ? $params['size'] : null;
    $opt['onOpen']		= (isset($params['onOpen'])) ? $params['onOpen'] : null;
    $opt['onClose']		= (isset($params['onClose'])) ? $params['onClose'] : null;
    $opt['onUpdate']	= (isset($params['onUpdate'])) ? $params['onUpdate'] : null;
    $opt['onResize']	= (isset($params['onResize'])) ? $params['onResize'] : null;
    $opt['onMove']		= (isset($params['onMove'])) ? $params['onMove'] : null;
    $opt['onShow']		= (isset($params['onShow'])) ? $params['onShow'] : null;
    $opt['onHide']		= (isset($params['onHide'])) ? $params['onHide'] : null;

    $options = FastJSON::encode( $opt );
    //$options = JHTMLBehavior::_getJSObject($opt);
    // Attach modal behavior to document
    //	todo un hardwire this!

    $opts = new stdClass();
    $opts->id = 'mocha-advancedsearch';
    $opts->title = JText::_('ADVANCED SEARCH');
    $opts->loadMethod = 'xhr';
    $opts->minimizable = false;
    $opts->collapsible = true;
    $opts->width = 500;
    $opts->height = 150;

    if ($fbConfig->get( 'usefabrik_mootools', false ) == 1) {
      $script .=

			"\n var opts = " . FastJSON::encode($opts). ";".
			"\n opts.content = mochaSearch.content;".
			"\n opts.onClose = function(){".
			"\n   searchcontent = this.windowEl.getElement('.mochaContent').get('html');".
			"\n   mochaSearch.trs = \$A([]);".
			"\n };".
			"\n $$('".$selector."').each(function(el) {".
			"\n   el.addEvent('click', function(e){".	
			"\n   opts2 = Json.evaluate(el.get('rel'));".
			"\n   Object.extend(opts, opts2 || {});".
			"\n   opts.contentURL = el.href;".
			"\n     new Event(e).stop();".
			"\n     new MochaUI.Window(opts);".
			"\n     mochaSearch.makeEvents();".
			"\n   });".
			"\n});".
		"\n});";			
    } else {
      $script .= "\n  $$('".$selector."').each(function(el) {".
			"\n    el.addEvent('click', function(e) {".
			"\n    new Event(e).stop();".
			"\n    var c= mochaSearch.content;".
			"\n    var o = {".
			"\n      contentType:'',".
			"\n      content:c,".
			"\n      id:el.id".
			"\n      }".
			"\n    var lastWin = document.mochaDesktop.newWindowfromElement(el, o);".
			"\n    mochaSearch.makeEvents();".
			"\n  });".
			"\n});".
		"\n});";
    }

    $document->addScriptDeclaration($script);

    // Set static array
    $modals[$sig] = true;
    return;
  }

  /** test not sure if needed ***/

  function modal($selector='a.modal', $params = array())
  {
    return;
    static $modals;
    static $included;

    $document =& JFactory::getDocument();

    // Load the necessary files if they haven't yet been loaded
    if (!isset($included)) {

      // Load the javascript and css
      FabrikHelperHTML::script('modal2.js', 'components/com_fabrik/libs/', true);
      JHTML::stylesheet('modal.css');

      $included = true;
    }

    if (!isset($modals)) {
      $modals = array();
    }

    $sig = md5(serialize(array($selector,$params)));
    if (isset($modals[$sig]) && ($modals[$sig])) {
      return;
    }

    // Setup options object
    $opt['ajaxOptions']	= (isset($params['ajaxOptions']) && (is_array($params['ajaxOptions']))) ? $params['ajaxOptions'] : null;
    $opt['size']		= (isset($params['size']) && (is_array($params['size']))) ? $params['size'] : null;
    $opt['onOpen']		= (isset($params['onOpen'])) ? $params['onOpen'] : null;
    $opt['onClose']		= (isset($params['onClose'])) ? $params['onClose'] : null;
    $opt['onUpdate']	= (isset($params['onUpdate'])) ? $params['onUpdate'] : null;
    $opt['onResize']	= (isset($params['onResize'])) ? $params['onResize'] : null;
    $opt['onMove']		= (isset($params['onMove'])) ? $params['onMove'] : null;
    $opt['onShow']		= (isset($params['onShow'])) ? $params['onShow'] : null;
    $opt['onHide']		= (isset($params['onHide'])) ? $params['onHide'] : null;

    $options = FabrikHelperHTML::_getJSObject($opt);
    // Attach modal behavior to document
    $document->addScriptDeclaration("
		window.addEvent('domready', function() {

			SqueezeBox2.initialize(".$options.");

			$$('".$selector."').each(function(el) {
				el.addEvent('click', function(e) {
					new Event(e).stop();
					SqueezeBox2.fromElement(el);
				});
			});
		});");

    // Set static array
    $modals[$sig] = true;
    return;
  }

  /**
   * show form to allow users to email form to a friend
   * @param object form
   */
  function emailForm( $formModel, $template='' )
  {
    global $mosConfig_db;
    $document =& JFactory::getDocument();
    $form =& $formModel->getForm();
    $document->setTitle( $form->label );
    $document->addStyleSheet("templates/'. $template .'/css/template_css.css");
    $url = JRoute::_('index.php?option=com_fabrik&view=emailform&tmpl=component');
    ?>
<form method="post" action="<?php echo $url; ?>" name="frontendForm">
<table>
	<tr>
		<td><label for="email"><?php echo JText::_('YOUR FRIENDS EMAIL') ?>:</label>
		</td>
		<td><input type="text" size="25" name="email" id="email" /></td>
	</tr>
	<tr>
		<td><label for="yourname"><?php echo JText::_('YOUR NAME'); ?>:</label>
		</td>
		<td><input type="text" size="25" name="yourname" id="yourname" /></td>
	</tr>
	<tr>
		<td><label for="youremail"><?php echo JText::_('YOUR EMAIL'); ?>:</label>
		</td>
		<td><input type="text" size="25" name="youremail" id="youremail" /></td>
	</tr>
	<tr>
		<td><label for="subject"><?php echo JText::_('MESSAGE SUBJECT'); ?>:</label>
		</td>
		<td><input type="text" size="40" maxlength="40" name="subject"
			id="subject" /></td>
	</tr>
	<tr>
		<td colspan="2">
		<input type="submit" name="submit" class="button" value="<?php echo JText::_('SEND EMAIL'); ?>" /> &nbsp;&nbsp; 
		<input type="button" name="cancel" value="<?php echo JText::_('CANCEL'); ?>" class="button" onclick="window.close();" />
	</td>
	</tr>
</table>
<input type="hidden" name="fabrik" value="<?php echo $form->id;?>" /> 
<input type="hidden" name="rowid" value="<?php echo JRequest::getVar('rowid');?>" />
<?php echo JHTML::_( 'form.token' ); ?>
</form>
    <?php
  }

  /**
   * once email has been sent to a frind show this message
   */

  function emailSent( $to )
  {
    $config =& JFactory::getConfig();
    $document =& JFactory::getDocument();
    $document->setTitle( $config->getValue('sitename') );
    ?>
<span class="contentheading"><?php echo JText::_('THIS ITEM HAS BEEN SENT TO')." $to";?></span>
<br />
<br />
<br />
<a href='javascript:window.close();'> <span class="small"><?php echo JText::_('CLOSE WINDOW');?></span>
</a>
    <?php
  }

  /**
   * writes a print icon
   * @param object form
   * @param object parameters
   * @param int row id
   * @return string print html icon/link
   */

  function printIcon( $formModel, $params, $rowid = '' )
  {
    global $mainframe;
    $config		=& JFactory::getConfig();
    $form =& $formModel->getForm();
    $table =& $formModel->getTable();
    if ($params->get( 'print' )) {
      $status = "status=no,toolbar=no,scrollbars=yes,titlebar=no,menubar=no,resizable=yes,width=400,height=250,directories=no,location=no";
      $link = JRoute::_(  COM_FABRIK_LIVESITE."index.php?option=com_fabrik&tmpl=component&view=details&fabrik=". $form->id . "&tableid=" . $table->id . "&rowid=" . $rowid );
      if ($params->get( 'icons', true )) {

        if($mainframe->_name == 'administrator'){
          $image = "<img src='".COM_FABRIK_LIVESITE."images/M_images/printButton.png' alt='".JText::_('PRINT')."' />";
        } else {
          $attribs = array();
          $image = JHTML::_('image.site', 'printButton.png', '/images/M_images/', NULL, NULL, JText::_('PRINT'), JText::_('PRINT'));
        }
      } else {
        $image = '&nbsp;'. JText::_('PRINT');
      }
      if ($params->get( 'popup', 1 )) {
        $ahref = '<a href="javascript:void(0)" onclick="javascript:window.print(); return false" title="' . JText::_('PRINT') . '">';
      } else {
        $ahref = "<a href='#' onclick=\"window.open('$link','win2','$status;');return false;\"  title='" .  JText::_('PRINT') . "'>";
      }
      $return = $ahref .
      $image .
			"</a>";
      return $return;
    }
  }

  /**
   * Writes Email icon
   * @param object form
   * @param object parameters
   * @return string email icon/link html
   */

  function emailIcon( $formModel, $params )
  {
    global $mainframe;
    $config		=& JFactory::getConfig();
    $popup = $params->get( 'popup', 1 );
    if ($params->get( 'email' ) && !$popup) {
      $status = "status=no,toolbar=no,scrollbars=yes,titlebar=no,menubar=no,resizable=yes,width=400,height=250,directories=no,location=no";
      $link = JRoute::_( COM_FABRIK_LIVESITE."index.php?option=com_fabrik&view=emailform&tmpl=component&fabrik=". $formModel->_id."&rowid=$formModel->_rowId" );
      if ($params->get( 'icons', true )) {
        if($mainframe->_name == 'administrator'){
          $image = "<img src='".COM_FABRIK_LIVESITE."images/M_images/emailButton.png' alt='".JText::_('EMAIL')."' />";
        } else {
          $image = JHTML::_('image.site', 'emailButton.png', '/images/M_images/', NULL, NULL, JText::_('EMAIL'), JText::_('EMAIL'));
        }
      } else {
        $image = '&nbsp;'. JText::_('EMAIL');
      }
      return "<a href='#' onclick=\"window.open('$link','win2','$status;');return false;\"  title='" .  JText::_('EMAIL') . "'>
      $image
			</a>";
    }
  }

  /**
   * @param string selected join
   */

  function joinTypeList( $sel = '' )
  {
    $joinTypes = array( );
    $joinTypes[] = JHTML::_('select.option', 'inner', JText::_( 'INNER JOIN' ) );
    $joinTypes[] = JHTML::_('select.option', 'left', JText::_( 'LEFT JOIN' ) );
    $joinTypes[] = JHTML::_('select.option', 'right', JText::_( 'RIGHT JOIN' ) );
    return JHTML::_('select.genericlist',  $joinTypes, 'join_type[]', 'class="inputbox" size="1" ', 'value', 'text', $sel );
  }

  /**
   * yes no options for list with please select options
   *
   * @param string $sel
   * @param string default label
   */

  function yesNoOptions( $sel = '', $default = '' )
  {
    if ($default == '') {
      $default = JText::_( 'PLEASE SELECT' );
    }
    $yesNoList[] = JHTML::_('select.option', "", $default );
    $yesNoList[] = JHTML::_('select.option', "1", JText::_( 'Yes') );
    $yesNoList[] = JHTML::_('select.option', "0", JText::_( 'No') );
    return $yesNoList;
  }

  function tableList( $sel = '' )
  {
     
    $db =& JFactory::getDBO();
    $db->setQuery("select id, label from #__fabrik_tables where state = '1'");
    $rows = $db->loadObjectList();
    return JHTML::_('select.genericlist', $rows, 'fabrik__swaptable', 'class="inputbox" size="1" ', 'id', 'label', $sel );
  }

  function loadCalendar()
  {
    static $calendarLoaded;

    // Only load once
    if ($calendarLoaded) {
      return;
    }
    $calendarLoaded = true;

    $document =& JFactory::getDocument();
    JHTML::stylesheet('calendar-jos.css', 'media/system/css/', array(' title' => JText::_( 'green' ) ,' media' => 'all' ));
    JHTML::script( 'calendar.js', 'media/system/js/' );
    JHTML::script( 'calendar-setup.js', 'media/system/js/' );

    $translation = FabrikHelperHTML::_calendartranslation();
    if($translation) {
      $document->addScriptDeclaration($translation);
    }

  }

  /**
   * Internal method to translate the JavaScript Calendar
   *
   * @return	string	JavaScript that translates the object
   * @since	1.5
   */
  function _calendartranslation()
  {
    static $jsscript = 0;

    /*
     * 		Calendar._TT["ABOUT"] =
     "DHTML Date/Time Selector\n" +
     "(c) dynarch.com 2002-2005 / Author: Mihai Bazon\n" +
     "For latest version visit: http://www.dynarch.com/projects/calendar/\n" +
     "Distributed under GNU LGPL.  See http://gnu.org/licenses/lgpl.html for details." +
     "\n\n" +
     "Date selection:\n" +
     "- Use the \xab, \xbb buttons to select year\n" +
     "- Use the " + String.fromCharCode(0x2039) + ", " + String.fromCharCode(0x203a) + " buttons to select month\n" +
     "- Hold mouse button on any of the above buttons for faster selection.";
     Calendar._TT["ABOUT_TIME"] = "\n\n" +
     "Time selection:\n" +
     "- Click on any of the time parts to increase it\n" +
     "- or Shift-click to decrease it\n" +
     "- or click and drag for faster selection.";
     */
    if($jsscript == 0)
    {
      $return = 'Calendar._DN = new Array ("'.JText::_('Sunday').'", "'.JText::_('Monday').'", "'.JText::_('Tuesday').'", "'.JText::_('Wednesday').'", "'.JText::_('Thursday').'", "'.JText::_('Friday').'", "'.JText::_('Saturday').'", "'.JText::_('Sunday').'");Calendar._SDN = new Array ("'.JText::_('Sun').'", "'.JText::_('Mon').'", "'.JText::_('Tue').'", "'.JText::_('Wed').'", "'.JText::_('Thu').'", "'.JText::_('Fri').'", "'.JText::_('Sat').'", "'.JText::_('Sun').'"); Calendar._FD = 0;	Calendar._MN = new Array ("'.JText::_('January').'", "'.JText::_('February').'", "'.JText::_('March').'", "'.JText::_('April').'", "'.JText::_('May').'", "'.JText::_('June').'", "'.JText::_('July').'", "'.JText::_('August').'", "'.JText::_('September').'", "'.JText::_('October').'", "'.JText::_('November').'", "'.JText::_('December').'");	Calendar._SMN = new Array ("'.JText::_('January_short').'", "'.JText::_('February_short').'", "'.JText::_('March_short').'", "'.JText::_('April_short').'", "'.JText::_('May_short').'", "'.JText::_('June_short').'", "'.JText::_('July_short').'", "'.JText::_('August_short').'", "'.JText::_('September_short').'", "'.JText::_('October_short').'", "'.JText::_('November_short').'", "'.JText::_('December_short').'");Calendar._TT = {};Calendar._TT["INFO"] = "'.JText::_('About the calendar').'";
 

		Calendar._TT["PREV_YEAR"] = "'.JText::_('Prev. year (hold for menu)').'";Calendar._TT["PREV_MONTH"] = "'.JText::_('Prev. month (hold for menu)').'";	Calendar._TT["GO_TODAY"] = "'.JText::_('Go Today').'";Calendar._TT["NEXT_MONTH"] = "'.JText::_('Next month (hold for menu)').'";Calendar._TT["NEXT_YEAR"] = "'.JText::_('Next year (hold for menu)').'";Calendar._TT["SEL_DATE"] = "'.JText::_('Select date').'";Calendar._TT["DRAG_TO_MOVE"] = "'.JText::_('Drag to move').'";Calendar._TT["PART_TODAY"] = "'.JText::_('(Today)').'";Calendar._TT["DAY_FIRST"] = "'.JText::_('Display %s first').'";Calendar._TT["WEEKEND"] = "0,6";Calendar._TT["CLOSE"] = "'.JText::_('Close').'";Calendar._TT["TODAY"] = "'.JText::_('Today').'";Calendar._TT["TIME_PART"] = "'.JText::_('(Shift-)Click or drag to change value').'";Calendar._TT["DEF_DATE_FORMAT"] = "'.JText::_('%Y-%m-%d').'"; Calendar._TT["TT_DATE_FORMAT"] = "'.JText::_('%a, %b %e').'";Calendar._TT["WK"] = "'.JText::_('wk').'";Calendar._TT["TIME"] = "'.JText::_('Time:').'";';
      $jsscript = 1;
      return $return;
    } else {
      return false;
    }
  }

  /**
   * Generates an HTML radio list
   * @param array An array of objects
   * @param string The value of the HTML name attribute
   * @param string Additional HTML attributes for the <select> tag
   * @param mixed The key that is selected
   * @param string The name of the object variable for the option value
   * @param string The name of the object variable for the option text
   * @returns string HTML for the select list
   */

  function radioList( &$arr, $tag_name, $tag_attribs, $selected=null, $key='value', $text='text' )
  {
    reset( $arr );
    $html = "";
    for ($i=0, $n=count( $arr ); $i < $n; $i++) {
      $k = $arr[$i]->$key;
      $t = $arr[$i]->$text;
      $id = ( isset($arr[$i]->id) ? @$arr[$i]->id : null);

      $extra = '';
      $extra .= $id ? " id=\"" . $arr[$i]->id . "\"" : '';
      if (is_array( $selected )) {
        foreach ($selected as $obj) {
          $k2 = $obj->$key;
          if ($k == $k2) {
            $extra .= " selected=\"selected\"";
            break;
          }
        }
      } else {
        $extra .= ($k == $selected ? " checked=\"checked\"" : '');
      }
      //$html .= "\n  <div class=\"fabrikRadioDiv\" id=\"$tag_name" . "_div_" . "$k\"><input type=\"radio\" name=\"$tag_name\" id=\"$tag_name$k\" value=\"".$k."\"$extra $tag_attribs />";
      //$html .= "\n  <label for=\"$tag_name$k\">$t</label></div>";
       
      $html .= '<div class="fabrik_subelement">';
      $html .= '<label>';
      $html .= '<input type="radio" value="'.$k.'" name="'.$tag_name.'" class="fabrikinput"/>';
      $html .= '<span>'.$t.'</span></label>';
      $html .= '</div>';

    }
    $html .= "\n";
    return $html;
  }

  /**
   * hack to get the editior code without it being written out to the page straight away
   * think this returns a simple text field
   */

  function getEditorArea( $name, $content, $hiddenField, $width, $height, $col, $row )
  {
    $editor =& FabrikHelperHTML::getEditor();
    return $editor->display( $name, $content, $width, $height, $col, $row, false );
  }

  /**
   * Get an editor object
   *
   * @access public
   * @param string $editor The editor to load, depends on the editor plugins that are installed
   * @return object JEditor
   */
  function &getEditor( $editor = null )
  {
    jimport( 'joomla.html.editor' );

    //get the editor configuration setting
    if (is_null($editor))
    {
      $conf =& JFactory::getConfig();
      $editor = $conf->getValue('config.editor');
    }
    $instance =& FEditor::getInstance($editor);
    return $instance;
  }

  /**
   *
   */

  function PdfIcon( $model, $params, $rowId = 0, $attribs = array())
  {
    global $Itemid, $mainframe;
    $url	    = '';
    $text	= '';
    $view = JRequest::getVar( 'view' );
    $task = JRequest::getVar( 'task' );
    if ($task == 'form' || $view == 'form' || $view == 'details') {
      $form = $model->getForm();
      $table = $model->_table->getTable();
      $user =& JFactory::getUser();
      $url = COM_FABRIK_LIVESITE."index.php?option=com_fabrik&view=details&format=pdf&fabrik=". $form->id . "&tableid=" . $table->id . "&rowid=" . $rowId;
    } else {
      $table = $model->getTable();
      $url = COM_FABRIK_LIVESITE."index.php?option=com_fabrik&view=table&format=pdf&tableid=" . $table->id;
    }

    $status = 'status=no,toolbar=no,scrollbars=yes,titlebar=no,menubar=no,resizable=yes,width=640,height=480,directories=no,location=no';

    // checks template image directory for image, if non found default are loaded
    if ($mainframe->_name == 'administrator') {
      $text = "<img src='".COM_FABRIK_LIVESITE."images/M_images/pdf_button.png' alt='".JText::_('PDF')."' />";
    } else {
      $text = JHTML::_('image.site', 'pdf_button.png', '/images/M_images/', NULL, NULL, JText::_('PDF'), JText::_('PDF'));
    }
    $attribs['title']	= JText::_( 'PDF' );
    $attribs['onclick'] = "\"window.open(this.href,'win2','".$status."'); return false;\"";
    $attribs['rel']     = '"nofollow"';

    $output = JHTML::_('link', JRoute::_($url), $text, $attribs);
    return $output;
  }

  /**
   * should be called JUST before view is rendered (unless in admin when it should be called at end of view
   * ensures that incompatible versions of mootools are removed
   * and that if there is a combined js file it is loaded
   *
   */
  function cleanMootools()
  {
    global $combine;
    FabrikHelperHTML::_getCombine();
    $fbConfig =& JComponentHelper::getParams( 'com_fabrik' );
    if ($fbConfig->get( 'merge_js', false )) {
      $file = $combine->getCacheFile();
      $combine->output();
      $p = FabrikString::ltrimword( str_replace( "\\", "/", str_replace( COM_FABRIK_BASE, '', $combine->outputFolder() ) ), "/" ) . "/";
      JHTML::script( $file, $p, false );
    }
    if ($fbConfig->get( 'usefabrik_mootools', false ) == 1) {

      $document =& JFactory::getDocument();
      $found = false;

      //new order for scripts
      $scripts = array();
      //array of scripts to place first
      $aM2scripts = array(
				'/js/archive/',
				'/components/com_fabrik/libs/mootools1.2/mootools-1.2.js', 
				'/components/com_fabrik/libs/mootools1.2/mootools-1.2-uncompressed.js',
				'/components/com_fabrik/libs/mootools1.2/compat.js', 
				'/components/com_fabrik/libs/mootools1.2/mootools-1.2-ext.js', 
				'/components/com_fabrik/libs/mootools1.2/mootools-1.2-more.js', 
				'/components/com_fabrik/libs/mootools1.2/mootools-1.2-more-uncompressed.js',
				'/components/com_fabrik/libs/mootools1.2/tips.js');
      foreach ($document->_scripts as $script=>$type) {
        foreach ($aM2scripts as $ms) {
          if (strstr( $script, $ms )) {
            $found = true;
            $scripts[$script] = $type;
          }
        }
      }
      if ($found){
        foreach ($document->_scripts as $script=>$type ) {
          if (strstr($script, 'mootools.js') || strstr($script, 'mootools-uncompressed.js')){
            unset( $document->_scripts[$script] );
          } else {
            $scripts[$script] = $type;
          }
        }
        $document->_scripts = null;
        $document->_scripts = $scripts;
      }
    }
  }

  /**
   * Keep session alive, for example, while editing or creating an article.
   */
  function keepalive()
  {
    //test since 2.0b3 dont do anything if loading from mocha win
    if (JRequest::getVar('tmpl') == 'component') {
      return;
    }
    //end test
    // Include mootools framework
    FabrikHelperHTML::mootools();

    $config 	 =& JFactory::getConfig();
    $lifetime 	 = ( $config->getValue('lifetime') * 60000 );
    $refreshTime =  ( $lifetime <= 60000 ) ? 30000 : $lifetime - 60000;
    //refresh time is 1 minute less than the liftime assined in the configuration.php file

    $document =& JFactory::getDocument();
    $script  = '';
    $script .= 'function keepAlive( ) {';
    $script .=  '	var myAjax = new Ajax( "index.php", { method: "get" } ).request();';
    $script .=  '}';
    $script .= 	' window.addEvent("domready", function()';
    $script .= 	'{ keepAlive.periodical('.$refreshTime.' ); }';
    $script .=  ');';

    $document->addScriptDeclaration($script);

    return;
  }
  /**
   * overwrite standard J mootools file with mootools 1.2
   * this isnt really going to work out - too much incompatibilty between the two code bases
   * even with "compatibility mode" on will try again when final 1.2 is out
   */
  function mootools()
  {
    static $mootools;

    if (!isset( $mootools )) {
      $mootools = true;
      $fbConfig =& JComponentHelper::getParams( 'com_fabrik' );
       
      if ($fbConfig->get( 'usefabrik_mootools', false ) == -1 ) {
        FabrikHelperHTML::script( 'mootools-ext.js', 'components/com_fabrik/libs/', true );
        return;
      }
      if ($fbConfig->get( 'usefabrik_mootools', false )) {
        $document =& JFactory::getDocument();
         
        foreach ($document->_scripts as $script=>$type ) {
          if ($script == '/fabrik2.0.x/media/system/js/mootools.js' || $script == '/fabrik2.0.x/media/system/js/mootools-uncompressed.js') {
            unset( $document->_scripts[$script] );
          }
        }

        $config = &JFactory::getConfig();
        $debug = $config->getValue('config.debug');
         
        // TODO NOTE: Here we are checking for Konqueror - If they fix thier issue with compressed, we will need to update this
        $konkcheck = strpos (strtolower($_SERVER['HTTP_USER_AGENT']), "konqueror");

        if ($debug || $konkcheck) {
          FabrikHelperHTML::script( 'mootools-1.2-uncompressed.js', 			'components/com_fabrik/libs/mootools1.2/', false );
          FabrikHelperHTML::script( 'mootools-1.2-more-uncompressed.js', 	'components/com_fabrik/libs/mootools1.2/', false );
        } else {
          FabrikHelperHTML::script( 'mootools-1.2.js', 			'components/com_fabrik/libs/mootools1.2/', false );
          FabrikHelperHTML::script( 'mootools-1.2-more.js', 'components/com_fabrik/libs/mootools1.2/', false );
        }
        FabrikHelperHTML::script( 'compat.js', 'components/com_fabrik/libs/mootools1.2/', false );
        FabrikHelperHTML::script( 'tips.js', 'components/com_fabrik/libs/mootools1.2/', false );
        FabrikHelperHTML::script( 'mootools-1.2-ext.js', 'components/com_fabrik/libs/mootools1.2/', true );
      } else {
        FabrikHelperHTML::script( 'mootools-ext.js', 'components/com_fabrik/libs/', true );
      }
    }
  }

  function _getCombine()
  {
    static $bcombine;
    global $combine;
    if (!isset($bcombine)) {
      $bcombine = true;
      //combine js test
      require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'combine-js.php' );
      global $combine;
      $combine = new combineJS();
      //end test
    }
  }
  /**
   * wrapper for JHTML::Script()
   */
  function script( $filename, $path = 'media/system/js/', $mootools = true)
  {
    global $combine, $mainframe;
    FabrikHelperHTML::_getCombine();
    $fbConfig =& JComponentHelper::getParams( 'com_fabrik' );
    if ($mootools) {
      FabrikHelperHTML::mootools();
    }

    if ($fbConfig->get( 'usefabrik_mootools', false ) == 1) {
      $mootools = false;
    }

    if ($fbConfig->get('merge_js', false)) {
      $combine->addFile($path.$filename);
    }else{
      JHTML::script( $filename, $path, $mootools );
    }
  }

  function slimbox()
  {
    $fbConfig =& JComponentHelper::getParams( 'com_fabrik' );
    if ($fbConfig->get( 'use_mediabox', false) && $fbConfig->get( 'usefabrik_mootools', false ) == 1 ) {
      $folder = 'components/com_fabrik/libs/mediabox/';
      JHTML::stylesheet( 'mediabox.css', $folder . 'css/');
      FabrikHelperHTML::script( 'mediabox.js', $folder, true );
    }
    else {
      if ($fbConfig->get( 'usefabrik_mootools', false ) == 1) {
        $folder = 'components/com_fabrik/libs/slimbox1.64/js/';
        JHTML::stylesheet( 'slimbox.css', 'components/com_fabrik/libs/slimbox1.64/css/');
      } else {
        JHTML::stylesheet( 'slimbox.css', 'components/com_fabrik/css/slimbox/' );
        $folder = 'components/com_fabrik/libs/';
      }
      FabrikHelperHTML::script( 'slimbox.js', $folder, true );
    }
  }

  function tips($selector='.hasTip', $params = array())
  {
    static $tips;

    if (!isset($tips)) {
      $tips = array();
    }

    // Include mootools framework
    FabrikHelperHTML::mootools();

    $sig = md5(serialize(array($selector,$params)));
    if (isset($tips[$sig]) && ($tips[$sig])) {
      return;
    }

    // Setup options object
    $opt['maxTitleChars']	= (isset($params['maxTitleChars']) && ($params['maxTitleChars'])) ? (int)$params['maxTitleChars'] : 50 ;
    $opt['offsets']			= (isset($params['offsets'])) ? (int)$params['offsets'] : null;
    $opt['showDelay']		= (isset($params['showDelay'])) ? (int)$params['showDelay'] : null;
    $opt['hideDelay']		= (isset($params['hideDelay'])) ? (int)$params['hideDelay'] : null;
    $opt['className']		= (isset($params['className'])) ? $params['className'] : null;
    $opt['fixed']			= (isset($params['fixed']) && ($params['fixed'])) ? '\\true' : '\\false';
    $opt['onShow']			= (isset($params['onShow'])) ? '\\'.$params['onShow'] : null;
    $opt['onHide']			= (isset($params['onHide'])) ? '\\'.$params['onHide'] : null;

    $options = FabrikHelperHTML::_getJSObject($opt);

    // Attach tooltips to document
    //
    $document =& JFactory::getDocument();
    $tooltipInit = '		window.addEvent(\'domready\', function(){ var JTooltips = new Tips($$(\''.$selector.'\'), '.$options.');});';
    $document->addScriptDeclaration($tooltipInit);

    // Set static array
    $tips[$sig] = true;
    return;

  }

  /**
   * Internal method to get a JavaScript object notation string from an array
   *
   * @param	array	$array	The array to convert to JavaScript object notation
   * @return	string	JavaScript object notation representation of the array
   * @since	1.5
   */
  function _getJSObject($array=array())
  {
    // Initialize variables
    $object = '{';

    // Iterate over array to build objects
    foreach ((array)$array as $k => $v)
    {
      if (is_null($v)) {
        continue;
      }
      if (!is_array($v) && !is_object($v)) {
        $object .= ' '.$k.': ';
        $object .= (is_numeric($v) || strpos($v, '\\') === 0) ? (is_numeric($v)) ? $v : substr($v, 1) : "'".$v."'";
        $object .= ',';
      } else {
        $object .= ' '.$k.': '.FabrikHelperHTML::_getJSObject($v).',';
      }
    }
    if (substr($object, -1) == ',') {
      $object = substr($object, 0, -1);
    }
    $object .= '}';

    return $object;
  }

  /**
   * add a debug out put section
   *
   * @param mixed string/object $content
   * @param string $title
   *
   */

  function debug($content, $title = 'output:')
  {
    if (JRequest::getBool( 'fabrikdebug', 0, 'get' ) != 1) {
      return;
    }
    echo "<div class='fabrikDebugOutputTitle'>$title</div>";
    if (is_object( $content ) || is_array( $content )) {
      echo "<div class='fabrikDebugOutput fabrikDebugHidden'><pre>";print_r($content);echo "</pre></div>";
    } else {
      echo "<div class='fabrikDebugOutput fabrikDebugHidden'>$content</div>";
    }
    static $debug;

    if (!isset($debug)) {
      $debug = true;
      $document =& JFactory::getDocument();
      $style = ".fabrikDebugOutputTitle{padding:5px;background:#efefef;color:#333;border:1px solid #999;cursor:pointer}";
      $style .= ".fabrikDebugOutput{padding:5px;background:#efefef;color:#999;}";
      $style .= ".fabrikDebugOutput pre{padding:5px;background:#efefef;color:#999;}";
      $style .= ".fabrikDebugHidden{display:none}";
      $document->addStyleDeclaration($style);
      $script = "window.addEvent('domready', function(){
			$$('.fabrikDebugOutputTitle').each(function(title){
				title.addEvent('click', function(e){
					title.getNext().toggleClass('fabrikDebugHidden');
				});
			});
			})";
      $document->addScriptDeclaration($script);
    }
  }

}
?>