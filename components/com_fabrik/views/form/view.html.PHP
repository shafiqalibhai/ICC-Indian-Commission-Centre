<?php

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.view');

class fabrikViewForm extends JView
{

  var $_template 	= null;
  var $_errors 	= null;
  var $_data 		= null;
  var $_rowId 	= null;
  var $_params 	= null;
  var $_isMambot = null;

  var $repeatableJoinGroupCount = 0;
  var $_id 			= null;

  function setId($id)
  {
    $this->_id = $id;
  }

  /**
   * main setup routine for displaying the form/detail view
   * @param string template
   */

  function display( $tpl = null )
  {
    global $mainframe, $_SESSION;
    $w =& new FabrikWorker();
    $config		=& JFactory::getConfig();
    $model		=& $this->getModel();
    $document =& JFactory::getDocument();
    FabrikHelperHTML::mootools();

    //Get the active menu item
    $usersConfig = &JComponentHelper::getParams( 'com_fabrik' );
    if (!isset( $this->_id )) {
      $model->setId( $usersConfig->get( 'fabrik', JRequest::getInt( 'fabrik' ) ) );
    } else {
      //when in a package the id is set from the package view
      $model->setId( $this->_id );
    }
    $form 	=& $model->getForm();
    $model->getPostMethod();
    if (JError::isError( $model->render())) {
      return false;
    }

    $this->isMultiPage = $model->isMultiPage();
    list( $this->plugintop, $this->pluginbottom ) = $model->_getFormPluginHTML();

    $tableModel =& $model->getTableModel();
    $table = is_object( $tableModel ) ? $tableModel->getTable() : null;
    if (!$model->canPublish()) {
      if (!$model->_admin) {
        echo JText::_( 'SORRY THIS FORM IS NOT PUBLISHED' );
        return false;
      }
    }

    $access = $model->checkAccessFromTableSettings();
    if ($access == 0) {
      return JError::raiseWarning( 500, JText::_( 'ALERTNOTAUTH' ) );
    }
    $model->_editable = ($access == 1 && $model->_editable == '1') ? false : true;
    if (JRequest::getVar('view', 'form') == 'details') {
      $model->_editable = false;
    }
    if (is_object( $tableModel )) {
      $joins =& $tableModel->getJoins();
      $model->getJoinGroupIds( $joins );
    }
    $this->setTitle($w);

    $params =& $model->getParams();
    FabrikHelperHTML::debug( $params->get('note'), 'note' );
    $params->def( 'icons', $mainframe->getCfg( 'icons' ) );
    $params->set( 'popup', (JRequest::getVar('tmpl') == 'component') ? 1 : 0 );

    $this->editable = $model->_editable;
    if ($model->_editable) {
      foreach ($model->_data as $key=>$val) {
        if (is_string( $val )) {
          $data[$key] = htmlspecialchars( $val, ENT_QUOTES );
        }
      }
    }


    $form->label = $model->getLabel();
    $form->intro = $model->getIntro();


    if ($model->_admin) {
      $model->_rootPath = JURI::base() ."administrator/index.php";
      $form->action =  "administrator/index.php";
      $form->formid = "adminForm";
      $form->name 	= "adminForm";
    } else {
      $model->_rootPath = JURI::base()."index.php?";
      
      $form->action = "index.php?option=com_fabrik";
	    //set for when the popup form is called from admin 
      if (JRequest::getVar('tmpl') == 'component') {
        $form->action = JURI::base().$form->action;
      }
      if ($model->getPostMethod() == 'ajax') {
        $form->action  .= "&amp;format=raw&controller=form";
      }
      $form->formid = ($model->_editable) ? "form_".$model->_id : 'details_' . $model->_id;
      $form->name 	= "form_".$model->_id;
    }
    $form->origerror = $form->error;
    $form->error  = (count( $model->_arErrors ) > 0) ? $form->error : '';
    $aGroups 			= array();

    $this->_addButtons();

    $model->loadValidationRuleClasses();

    // $$$ hugh - temp foreach fix
    $groups = $model->getGroupsHiarachy();


    foreach ($groups as $gkey => $groupModel) {
      $groupTable 	=& $groupModel->getGroup();
      $group 				= $groupModel->getGroupProperties( $model );
      $groupParams 	=& $groupModel->getParams();
      $aElements 		= array();
      //check if group is acutally a table join

      if (array_key_exists( $groupTable->id, $model->_aJoinGroupIds )) {
        $aElements[] = $this->_makeJoinIdElement( $groupTable, $tableModel, $model, 0 );
      }

      $repeatGroup = 1;
      $foreignKey = null;

      if ($groupModel->canRepeat()) {
        if ($groupModel->isJoin()) {

          $joinTable =& $groupModel->getJoinModel()->getJoin();
          $foreignKey  = '';
          if (is_object( $joinTable )) {
            $foreignKey = $joinTable->table_join_key;
            //need to duplicate this perhaps per the number of times
            //that a repeat group occurs in the default data?
            if (array_key_exists( $joinTable->id, $model->_data['join'] )) {
              $elementModels =& $groupModel->getPublishedElements();
              reset( $elementModels );
              $tmpElement = current( $elementModels );
              $smallerElHTMLName = $tmpElement->getFullName( false, true, false );
              $repeatGroup = count( $model->_data['join'][$joinTable->id][$smallerElHTMLName] );
            } else {
              if (!$groupParams->get('repeat_group_show_first')) {
                continue;
              }
            }
          }
        } else {
          // repeat groups which arent joins
          $elementModels =& $groupModel->getPublishedElements();
          foreach ($elementModels as $tmpElement) {
            $smallerElHTMLName = $tmpElement->getFullName( false, true, false );
            // $$$ rob use the raw data if it exists
            // otherwise if you have just one dbjoin el in a repeat group the data would contain
            // the first label only.e.g.
            //[table___dbjoin_raw] => 1//..*..//2
            //[table___dbjoin_raw] => one
            // you could argue that it should be:
            //[table___dbjoin_raw] => one//..*..//two
            // but it isnt at the moment

            if (array_key_exists( $smallerElHTMLName."_raw", $model->_data )){
              $d = $model->_data[$smallerElHTMLName."_raw"];
            } else {
              $d = @$model->_data[$smallerElHTMLName];
            }
            if (is_string($d) && strstr( $d, $tmpElement->_groupSplitter )) {
              $d = explode( $tmpElement->_groupSplitter, $d );
            }
            $c = count($d);
            if ( $c > $repeatGroup ) { $repeatGroup = $c;}
          }
        }
      }
      $groupModel->_repeatTotal = $repeatGroup;

      $aSubGroups = array();
      for ($c = 0; $c < $repeatGroup; $c++) {
        $aSubGroupElements = array();
        $elCount = 0;
        $elementModels =& $groupModel->getPublishedElements();

        foreach ($elementModels as $elementModel) {
          //$$$rob test dont include the element in the form is we can't use and edit it
          //test for captcha element when user logged in
          	
          if (!$model->_editable) {
            $elementModel->_inDetailedView = true;
          }
          $elementModel->_foreignKey = $foreignKey;
          $elementModel->_repeatGroupTotal = $repeatGroup - 1;
          	
          $element = $elementModel->preRender( $c, $groupModel, $model, $elCount );
          if (!$element) {
            continue;
          }
          $elementModel->stockResults( $element, $aElements, $this->data, $aSubGroupElements );
          	
          if (!$element->hidden) {
            $elCount ++;
          }
        }
        //if its a repeatable group put in subgroup
        if ($groupModel->canRepeat()) {
          $aSubGroups[] = $aSubGroupElements;
        }
      }
      $group->elements = $aElements;
      $group->subgroups = $aSubGroups;

      //only create the group if there are some element inside it
      if (count( $aElements ) != 0) {
        $aGroups[$group->name] = $group;
      }
    }
    $this->assignRef( 'groups', $aGroups );
    FabrikHelperHTML::debug( $this->groups, 'form:view:groups' );
    FabrikHelperHTML::debug( $this->data, 'form:view:data' );

    //force front end templates
    $this->_basePath = COM_FABRIK_FRONTEND . DS . 'views' ;

    $t = ($model->_editable)? $form->form_template : $form->view_only_template;
    $form->form_template = JRequest::getVar( 'layout', $t );
    $tmpl = $form->form_template;
    $this->_addJavascript( $tableModel->_id, $tmpl );
    $this->_loadTmplBottom( $form );

    if ($model->_editable) {
      $form->startTag = "<form action=\"{$form->action}\" class=\"fabrikForm\" method=\"post\" name=\"{$form->name}\" id=\"{$form->formid}\" enctype=\"{$model->getFormEncType()}\">";
      $form->endTag = "</form>";
    } else {
      $form->startTag = '<div class="fabrikForm"id="'.$form->formid.'">';
      $form->endTag  = '</div>';
    }
    $this->assignRef( 'form', $form );
    $this->setMessage();

    $this->_setPath( 'template', $this->_basePath.DS.$this->_name.DS.'tmpl'.DS.$tmpl );
    //ensure we don't have an incorrect version of mootools loaded
    FabrikHelperHTML::cleanMootools();
    if ($this->_isMambot) {
      return $this->loadTemplate();
    } else {
      parent::display();
    }
  }

  /**
   * set template message when using session multipages
   */

  private function setMessage()
  {
    $model =& $this->getModel();
    $message = '';
    if ($model->sessionModel) {
      $this->message = $model->sessionModel->status;
      if ($model->sessionModel->statusid == _FABRIKFORMSESSION_LOADED_FROM_COOKIE) {
        $message .= " <a href='#' class='clearSession'>" . JText::_('CLEAR') . "</a>";
      }
    }

    $this->assign( 'message', $message );
  }

  /**
   * if the group is a joined group we want to ensure that its id field is contained with in the group's elements
   *
   * @param object $groupTable
   * @param object $tableModel
   * @param object $model
   * @return unknown
   */

  function _makeJoinIdElement( &$groupTable, &$tableModel, &$model )
  {
    $joinId = $model->_aJoinGroupIds[$groupTable->id];
    $element 			= new stdClass();
    //add in row id for join data
    $element->label = '';
    $element->error = '';
    $element->className = '';
    foreach ($tableModel->_aJoins as $oJoin) {
      if ($oJoin->id == $joinId) {
        $key = $oJoin->table_join . $model->_joinTableElementStep . $oJoin->table_join_key;

        if (array_key_exists( 'join', $model->_data )) {
          $val = @$model->_data['join'][$joinId][$key];
          if (is_array($val)) {
            $val = $val[0];
          }
        } else {
          $val = '';
        }
        $element->element = '<input type="hidden" id="join.' . $joinId . '.rowid" name="join[' . $joinId . '][rowid]" value="' . $val . '" />';
      }
    }
    return $element;
  }

  /**
   * set the page title
   *
   * @param object parent worker
   */

  function setTitle($w)
  {
    global $mainframe;
    $title = '';
    if ($mainframe->getName() !== 'administrator') {
      $menus	= &JSite::getMenu();
      $menu	= $menus->getActive();
      if (is_object( $menu )) {
        $menu_params = new JParameter( $menu->params );
        $title = $menu_params->get( 'page_title' );
      }
    }
    $model		=& $this->getModel();
    $document =& JFactory::getDocument();
    if (!$this->_isMambot) {
      $title = $model->getPageTitle( $title );
      $document->setTitle( $w->parseMessageForPlaceHolder( $title, $_REQUEST ) );
    }
  }

  /**
   * add buttons to the view e.g. print, pdf
   */

  function _addButtons()
  {
    $model		=& $this->getModel();
    $params 	=& $model->getParams();
    $this->showEmail = $params->get( 'email', 0 );
    $this->emailLink = '';
    $this->printLink = '';
    $this->pdfLink = '';
    $this->showPrint = $params->get( 'print', 0 );

    if ($this->showPrint) {
      $text = JHTML::_('image.site',  'printButton.png', '/images/M_images/', NULL, NULL, JText::_( 'Print' ) );
      $this->printLink = '<a href="#" onclick="window.print();return false;">'.$text.'</a>';
    }

    if (JRequest::getVar('tmpl') != 'component') {
      if ($this->showEmail) {
        $this->emailLink = FabrikHelperHTML::emailIcon( $model, $params );
      }
      	
      if ($this->showPrint) {
        $this->printLink = FabrikHelperHTML::printIcon( $model, $params, $model->_rowId );
      }

      $this->showPDF = $params->get( 'pdf', 0 );
      if ($this->showPDF) {
        $this->pdfLink = FabrikHelperHTML::pdfIcon( $model, $params, $model->_rowId );
      }
    } else {
      $this->showPDF = false;
    }
  }

  /**
   * append the form javascript into the document head
   * @param int table id
   * @param string extra js code to add
   */

  function _addJavascript( $tableId, $tmpl )
  {
    $document 	=& JFactory::getDocument();
    $model 			=& $this->getModel();

    $aLoadedElementPlugins = array();
    $jsActions 		= array();
    $jsControllerKey = ($model->_editable) ? 'form_'. $model->_id : 'details_'. $model->_id;
    $allJsActions = $model->getJsActions();

    if (!defined( '_JOS_FABRIK_FORMJS_INCLUDED' )) {
      define( '_JOS_FABRIK_FORMJS_INCLUDED', 1 );
      FabrikHelperHTML::slimbox();
      FabrikHelperHTML::script( 'form.js', 'components/com_fabrik/views/form/', true );
      FabrikHelperHTML::script( 'element.js', 'components/com_fabrik/views/form/', true );
    }

    $aWYSIWYGNames = array();
    $groups =& $model->getGroupsHiarachy();
    foreach ($groups as $groupModel) {
      $elementModels =& $groupModel->getPublishedElements();
      foreach ($elementModels as $elementModel) {
        $res = $elementModel->useEditor();
        if ($res !== false) {
          $aWYSIWYGNames[] = $res;
        }
        $eparams =& $elementModel->getParams();
        //load in once the element js class files
        $element =& $elementModel->getElement();
        if (!in_array( $element->plugin, $aLoadedElementPlugins )) {
          $aLoadedElementPlugins[] = $element->plugin;
          $elementModel->formJavascriptClass();
        }
        for ($c = 0; $c < $groupModel->_repeatTotal; $c ++ ){
          $jsActions[]		= $elementModel->getFormattedJSActions( $allJsActions, $jsControllerKey, $c );
        }
      }
    }
    //new
    $actions = trim( implode( "\n", $jsActions ) );
    //end new

    $params =& $model->getParams();
    FabrikHelperHTML::packageJS();
    $tableModel =& $model->getTableModel();
    $table 			=& $tableModel->getTable();
    $form				=& $model->getForm();
    $fbConfig 	=& JComponentHelper::getParams( 'com_fabrik' );
    FabrikHelperHTML::mocha();
    FabrikHelperHTML::script( 'package.js', 'components/com_fabrik/views/package/', true );
    FabrikHelperHTML::tips();
    $key = str_replace( ".", "___", $table->db_primary_key );
    $key = str_replace( "`", "", $key );

    //tmp = component when the form is loaded in a pop up window - presumes the doc body has
    //already been loaded

    //can't be domready as it gets fired when wysiwyg editor gets loaded
    if (JRequest::getVar('tmpl') == 'component') {
      $startJs = '';
      $endJs = '';
      $startJs = "window.addEvent('domready', function(){\n";
      $endJs = "});\n";
    } else {
      $startJs = "window.addEvent('load', function(){\n";
      $endJs = "});\n";
    }
    $ajaxValidation = ($this->isMultiPage) ? true : $params->get('ajax_validations');

    $start_page = isset($model->sessionModel->last_page) ? $model->sessionModel->last_page : 0;
    //test to see if we can load the form in before and then attach elements afterwards
    //$str ="$startJs

    $bkey = ($model->_editable) ? 'form_'. $model->_id : 'details_'. $model->_id;

    $opts = new stdClass();
    $opts->customJsAction = $model->getCustomJsAction();
    $opts->formCss 				= $model->getFormCss($tmpl);

    $opts->admin 					= $model->_admin;
    $opts->postMethod 		= $model->getPostMethod();
    $opts->ajaxValidation = $ajaxValidation;
    $opts->primaryKey 		= $key;
    $opts->inPopup 				= (JRequest::getVar('tmpl') == 'component') ? true : false;
    $opts->liveSite 			= COM_FABRIK_LIVESITE;
    $opts->error 					= @$form->origerror;
    $opts->pages 					= $model->getPages();
    $opts->page_save_groups = $model->getPageSaveGroups();
    $opts->editable 			= $model->_editable;
    $opts->start_page 		= $start_page;
    $opts->mooversion			= ($fbConfig->get( 'usefabrik_mootools', false )) ? 1.2 : 1.1;
    $opts 								= FastJSON::encode($opts);


    $lang = new stdClass();
    $lang->validation_altered_content = JText::_('The validation has altered your content');
    $lang->validating 		= JText::_('Validating');
    $lang->success 				= JText::_('Success');
    $lang 								= FastJSON::encode($lang);

    $str ="var $bkey = new fabrikForm( ".$model->_id.", $opts, $lang);\n";

    $str .= "$bkey.addListenTo('table_" . $tableModel->_id . "');\n";
    $str .= "$bkey.addListenTo('form_" . $model->_id . "');\n";

    $str .= "oPackage.addBlock('$bkey', $bkey);\n";
    //instantaite js objects for each element

    $groupstr = '';
    $vstr = "\n";
    $aObjs = array();
    $str .= "$startJs";
    $groups =& $model->getGroupsHiarachy();

    // $$$ rob in php5.2.6 (and possibly elsewhere) $groups's elements havent been updated
    // to contain the default value used by the element
    //foreach ($groups as $groupModel) {

    //testing this one again as Ive updated getGroupsHiarchy

    foreach ($groups as $groupModel) {
      $elementModels =& $groupModel->getPublishedElements();
      foreach ($elementModels as $elementModel) {
        $element =& $elementModel->getElement();
        if ($element->state == 0){
          continue;
        }
        $fullName = $elementModel->getFullName();
        $id = $elementModel->getHTMLId();
        $elementModel->_editable = ($model->_editable);

        for ($c = 0; $c < $groupModel->_repeatTotal; $c ++ ){
          $aObjs[] = $elementModel->elementJavascript( $c );
          $validations =& $elementModel->getValidations();
          if (!empty( $validations ) && $elementModel->_editable) {
            $watchElements = $elementModel->getValidationWatchElements( $c );
            foreach ($watchElements as $watchElement) {
              $vstr .= "$bkey.watchValidation('".$watchElement['id']."', '".$watchElement['triggerEvent']."' );\n";
            }
          }
        }

      }
      $groupParams =& $groupModel->getParams();
      $addJs 			= str_replace( '"', "'",  $groupParams->get( 'repeat_group_js_add' ) );
      $addJs 			= str_replace( array("\n", "\r"), "",  $addJs );
      $delJs 			= str_replace('"', "'",  $groupParams->get( 'repeat_group_js_delete' ) );
      $gdelJs 		= str_replace( array("\n", "\r"), "",  $delJs );
      if ($delJs !== '') {
        $groupstr .= "$bkey.addGroupJS($groupModel->_id, 'delete', \"$delJs\");\n";
      }
      if ($addJs !== '') {
        $groupstr .= "$bkey.addGroupJS($groupModel->_id, 'add', \"$addJs\");\n";
      }
    }
    $str .= "{$bkey}.addElements([" . implode(",\n", $aObjs) . "]);\n";
    $str .= $groupstr;
    $str .=  $actions;
    $str .= $vstr;
    $str .= $endJs;
    $str .= "function submit_form(){";
    if (!empty( $aWYSIWYGNames )) {
      jimport( 'joomla.html.editor' );
      $editor =& FabrikHelperHTML::getEditor();
      $str .= $editor->save( 'label' );

      foreach ($aWYSIWYGNames as $parsedName) {
        $str .= $editor->save( $parsedName );
      }
    }
    $str .="
			return false;
		}
			
		function submitbutton(button){
			if(button==\"cancel\"){
				document.location = '".JRoute::_('index.php?option=com_fabrik&task=viewTable&cid='.$tableId). "';
			}
			if(button == \"cancelShowForm\"){
				return false;
			}
		}
";
    $document->addScriptDeclaration( $str );
    $pluginManager =& $model->getPluginManager();
    $pluginManager->runPlugins( 'onAfterJSLoad', $model );
    FabrikHelperHTML::mootools();
  }

  function _loadTmplBottom( &$form )
  {
    global $Itemid, $_SERVER;
    $model 	    =& $this->getModel();
    $params     =& $model->getParams();
    $cursor = JRequest::getInt( 'fabrik_cursor', '' );
    $total 	= JRequest::getInt( 'fabrik_total', '' );
    $reffer = '';
    if (array_key_exists( 'HTTP_REFERER', $_SERVER ) ){
      $reffer = $_SERVER['HTTP_REFERER'];
      $reffer = str_replace('&', '&amp;', $reffer);
    }
    // $$$ hugh trying to fix 'usekey' issue
    if (JRequest::getVar('usekey') != '') {
      // $$$ hugh - if the row was selected with a 'usekey', we need to set the rowid back to the actual PK val
      // The following line is fugly, but I can't find any other way of getting the PK element name.
      //$this_pk_element = FabrikString::safeColNameToArrayKey($model->_table->_table->db_primary_key);
      //$this_rowid = array_key_exists($this_pk_element, $model->_data) ? $model->_data[$this_pk_element] : $model->_rowId;
	  // Something changed which makes the above code un-necessary!
	  $this_rowid = $model->_rowId;
    }
    else {
      $this_rowid = $model->_rowId;
    }

    $aHiddenFields = "<input type='hidden' name='tableid' value='" . $model->_table->_id . "' id = 'tableid' />\n".
		"<input type='hidden' name='fabrik' value='" . $model->_id . "' id = 'fabrik' />\n".
		"<input type='hidden' name='task' value='processForm' id = 'task' />\n".
		"<input type='hidden' name='rowid' value='" . $this_rowid . "' id = 'rowid' />\n".
		"<input type='hidden' name='Itemid' value='" . $Itemid . "' id = 'Itemid' />\n".
		"<input type='hidden' name='option' value='com_fabrik' id = 'option' />\n".
		"<input type='hidden' name='c' value='form' id = 'c' />\n".
    "<input type='hidden' name='view' value='form' id = 'c' />\n".
    "<input type='hidden' name='controller' value='form' />\n".
		"<input type='hidden' name='form_id' value='" . $model->_id . "' id = 'form_id' />\n".
		"<input type='hidden' name='fabrik_frommodule' value='" . $model->_isModule . "' id = 'fabrik_frommodule' />\n".
		"<input type='hidden' name='fabrik_cursor' value='" . $cursor . "' id = 'fabrik_cursor' />\n".
		"<input type='hidden' name='fabrik_total' value='" . $total . "' id = 'fabrik_total' />\n".
		"<input type='hidden' name='returntoform' value='0' id='returntoform' />\n".
		"<input type='hidden' name='fabrik_referrer' value='" . $reffer . "' id='fabrik_referrer' />\n".
		"<input type='hidden' name='fabrik_postMethod' value='" . $model->getPostMethod() . "' id='fabrik_postMethod' />\n";
	if ($usekey = JRequest::getVar('usekey')) {
	    $aHiddenFields .= "<input type='hidden' name='usekey' value='" . $usekey . "' />\n";
	}
	// $$$ hugh - testing a fix for pagination issue when submitting a 'search form'.
    // If this is a search form, we need to clear 'limitstart', otherwise ... say we
    // were last on page 4 of the (unfiltered) target table, and the search yields less than 4 pages,
    // we end up with a blank table 'cos the wrong LIMIT's are applied to the query
    $save_insessions = $params->get('save_insession','');
    if (is_array($save_insessions)) {
      foreach ($save_insessions as $save_insession) {
        if ($save_insession == '1') {
          $aHiddenFields .= "<input type='hidden' name='limitstart' value='0' />\n";
          break;
        }
      }
    }
    $aHiddenFields .= JHTML::_( 'form.token' );

    $form->resetButton = $params->get('reset_button', 0) && $this->editable == "1" ?	"<input type=\"reset\" class=\"button\" name=\"Reset\" value=\"" . $params->get('reset_button_label') . "\" />\n" : '';
    $form->copyButton = $params->get('copy_button', 0) && $this->editable && $model->_rowId != '' ?	"<input type=\"submit\" class=\"button\" name=\"Copy\" value=\"" . $params->get('copy_button_label') . "\" />\n" : '';

    $gobackaction = ( $model->getPostMethod() == 'post' ) ? "onclick=\"history.back();\"" : '';
    $form->gobackButton = $params->get('goback_button', 0) == "1" ?	"<input type=\"button\" class=\"button\" name=\"Goback\" $gobackaction value=\"" . $params->get('goback_button_label') . "\" />\n" : '';
    if ($model->_editable) {
      $button = ( $model->getPostMethod() == 'post') ? "submit" : "button";
      $form->submitButton = '';
      $form->submitButton .= "<input type=\"$button\" id=\"fabrikSubmit" . $model->_id . "\" class=\"button\" name=\"Submit\" value=\"" . $form->submit_button_label ."\" />\n " ;
    } else {
      $form->submitButton = '';
    }
    if ($this->isMultiPage) {
      $form->submitButton .= "<input type='button' class='fabrikPagePrevious button' name='fabrikPagePrevious' value='" . JText::_('PREVIOUS') ."' />\n";
      $form->submitButton .= "<input type='button' class='fabrikPageNext button' name='fabrikPageNext' value='" . JText::_('NEXT') ."' />\n";
    }
    $format = ( $model->getPostMethod() == 'post' ) ? 'html' : 'raw';
    $aHiddenFields .= "<input type='hidden' name='format' value='$format' />";
    $aHiddenFields .= "<input type='hidden' name='_senderBlock' id='_senderBlock' value='form_" .$form->id . "' />";

    $groups =& $model->getGroupsHiarachy();
    foreach ($groups as $groupModel) {
      $group 	=& $groupModel->getGroup();
      $c 			= $groupModel->_repeatTotal;
      //used for validations
      //$aHiddenFields .= "<input type='hidden' name='fabrik_repeat_group[" . $group->id . "]' value='" . $c . "' id='fabrik_repeat_group_" . $group->id . "_counter' />";
      $aHiddenFields .= "<input type='hidden' name='fabrik_repeat_group[]' value='" . $c . "' id='fabrik_repeat_group_" . $group->id . "_counter' />";
    }

    $this->_cryptQueryString( $aHiddenFields );
    $this->hiddenFields = $aHiddenFields;
  }

  /** $$$rob store all fabrik querystring vars as encrypted hidden fields
   * this is used when you have a table with a "Tables with database join elements linking to this table" link to a form.
   * and when the form's pk element (found in the link) is set to read only
   * OR
   * if you are filtering from an url?
   */

  function _cryptQueryString( &$aHiddenFields )
  {
    jimport('joomla.utilities.simplecrypt');
    jimport('joomla.utilities.utility');
    $crypt = new JSimpleCrypt();
    $formModel =& $this->getModel();
    $get = JRequest::get('get');
    foreach ($get as $key => $input) {
      if ($formModel->hasElement( $key )) {
        $input = (is_array($input) && array_key_exists('value', $input)) ? $input['value'] : $input;
        $input = $crypt->encrypt( $input );
        $aHiddenFields .= "<input type='hidden' name='fabrik_vars[querystring][$key]' value='" . $input . "' />\n";
      }
    }
  }

}
?>