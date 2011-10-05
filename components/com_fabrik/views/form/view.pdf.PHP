<?php
/**
 * @package Joomla
 * @subpackage Fabrik
 * @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

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

    $config		=& JFactory::getConfig();
    $model		=& $this->getModel();
    $model->_editable = false;

    //Get the active menu item
    $usersConfig = &JComponentHelper::getParams( 'com_fabrik' );

    if (!isset( $this->_id )) {
      $model->setId( $usersConfig->get( 'fabrik', JRequest::getInt( 'fabrik' ) ) );
    } else {
      //when in a package the id is set from the package view
      $model->setId( $this->_id );
    }

    $form =& $model->getForm();
    $model->render();
    list( $this->plugintop, $this->pluginbottom ) = $model->_getFormPluginHTML();

    if (!$model->canPublish()) {
      if (!$model->_admin) {
        echo JText::_( 'SORRY THIS FORM IS NOT PUBLISHED' );
        return false;
      }
    }

    if ($model->_isModule) {
      if (!defined( '_FABRIK_SESSIONSTART' )) {
        define( '_FABRIK_SESSIONSTART', '1' );
        session_start();
      }
      if (array_key_exists( 'fabrik', $_SESSION ) && array_key_exists( 'moduleErrors', $_SESSION['fabrik'] )) {
        $this->_arErrors = $_SESSION['fabrik']['moduleErrors'];
        unset( $_SESSION['fabrik']['moduleErrors'] );
      }
    }
    $access = $model->checkAccessFromTableSettings();
    if ($access == 0) {
      echo JText::_('ALERTNOTAUTH');
      return false;
    }
    if ($access == 1 && $model->_editable == '1') {
      $model->_editable = 0;
    }
    if ( is_object( $model->_table )) {
      $joins = $model->_table->getJoins();
      $model->getJoinGroupIds( $joins );
    }
    $model->getPageTitle();


    $params =& $model->getParams();
    $params->def( 'icons', $mainframe->getCfg( 'icons' ) );
    $pop =  (JRequest::getVar('tmpl') == 'component') ? 1 : 0;
    $params->set( 'popup', $pop );
    $this->form_template = JRequest::getVar( 'layout', $form->form_template );

    $this->editable = $model->_editable;

    $form->label = $model->getLabel();
    $form->intro = $model->getIntro();

    $params		=& $model->getParams();

    $form->js 		= "";
    if ($model->_admin) {
      $model->_rootPath = JURI::base() ."administrator/index.php";
      $form->action = JURI::base()  . "index.php";
      $form->formid = "adminForm";
      $form->name 	= "adminForm";
    } else {
      $model->_rootPath = JURI::base()."index.php";
      $form->action = $model->_rootPath."?option=com_fabrik";
      $form->formid = "form_".$model->_id;
      $form->name 	= "form_".$model->_id;
    }

    $form->js 	= $model->_js;
    $form->encType = $model->getFormEncType();;

    if (count( $model->_arErrors ) > 0) {
      $form->error = $form->error;
    } else {
      $form->error = '';
    }
    $aGroups 			= array();
    $this->showEmail = $params->get( 'email', 0 );

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
      $group->editable  = 0;
      //only create the group if there are some element inside it
      if (count( $aElements ) != 0) {
        $aGroups[$group->name] = $group;
      }
    }
    $this->assignRef( 'groups', $aGroups );

    $form->startTag = '<div class="fabrikForm"id="detail_'.$model->_id.'">';
    $form->endTag  = '</div>';
    //force front end templates
    $this->_basePath = COM_FABRIK_FRONTEND . DS . 'views' ;

    $t = ($model->_editable)?  $form->form_template : $form->view_only_template;
    $form->form_template = JRequest::getVar( 'layout', $t );
    $tmpl = JRequest::getVar( 'layout', $form->form_template );
    $this->_includeTemplateCSSFile( $tmpl );

    $this->message = '';
    $this->_addButtons();
    $form->error = '';
    $this->hiddenFields = '';
    $form->resetButton = '';
    $form->submitButton = '';
    $form->copyButton = '';
    $form->gobackButton = '';
    $this->assignRef( 'form', $form );
    if ($model->sessionModel) {
      $this->message = $model->sessionModel->status;
      if ($model->sessionModel->statusid == _FABRIKFORMSESSION_LOADED_FROM_COOKIE) {
        $this->message .= " <a href='#' class='clearSession'>" . JText::_('CLEAR') . "</a>";
      }
    }
    $this->_setPath( 'template', $this->_basePath.DS.$this->_name.DS.'tmpl'.DS.$tmpl );

    parent::display();
    return;

  }

  /**
   * include the template css files
   *
   * @param string template name
   */
  function _includeTemplateCSSFile( $formTemplate )
  {
    $config		=& JFactory::getConfig();
    $document =& JFactory::getDocument();
    $ab_css_file = JPATH_SITE.DS."components".DS."com_fabrik".DS."views".DS."form".DS."tmpl".DS."$formTemplate".DS."template.css";
    $live_css_file = COM_FABRIK_LIVESITE  . "components/com_fabrik/views/form/tmpl/$formTemplate/template.css";
    if (file_exists( $ab_css_file )) {
      $document->addStyleSheet($live_css_file);
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

    if (JRequest::getVar('tmpl') != 'component') {
      if ($this->showEmail) {
        $this->emailLink = '';
      }

      $this->showPrint = $params->get( 'print', 0 );
      if ($this->showPrint) {
        $this->printLink = '';
      }

      $this->showPDF = $params->get( 'pdf', 0 );
      if ($this->showPDF) {
        $this->pdfLink = '';
      }
    } else {
      $this->showPDF = $this->showPrint = false;
    }
  }

}
?>