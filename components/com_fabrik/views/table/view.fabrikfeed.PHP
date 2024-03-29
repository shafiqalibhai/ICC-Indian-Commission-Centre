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

class FabrikViewTable extends JView{


	function display()
	{
		
		require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'parent.php' );
		require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'json.php' );
		global $Itemid, $mainframe;
		$config		=& JFactory::getConfig();
		$user 		=& JFactory::getUser();
		$model		=& $this->getModel();
		$model->_outPutFormat == 'feed';
		
		$document =& JFactory::getDocument();
		$document->_itemTags = array();
		
		//Get the active menu item
		$usersConfig = &JComponentHelper::getParams( 'com_fabrik' );

		if (!isset( $this->_id )) {
			if ($model->_admin) {
				$tpl = "admin";
			} else {
				$model->setId( JRequest::getVar( 'tableid', $usersConfig->get('tableid') ) );
			}
		} else {
			//when in a package the id is set from the package view
			$model->setId( $this->_id );
		}

		$table			=& $model->getTable();
		$model->render();
		$params 		=& $model->getParams();

		if ($params->get('rss') == '0') {
			return '';
		}

		$formModel =& $model->getForm();
		$form =& $formModel->getForm();

		$aJoinsToThisKey = $model->getJoinsToThisKey();
		/* get headings */
		$aTableHeadings = array();
		$groupModels =& $formModel->getGroupsHiarachy();
		
		$titleEl = $params->get( 'feed_title' );
		$dateEl = $params->get( 'feed_date' );
		foreach ($groupModels as $groupModel) {
			$elementModels =& $groupModel->getPublishedElements();
			foreach ($elementModels as $elementModel) {
				$element = $elementModel->getElement();
				if ($element->id == $titleEl) {
					$titleEl = $elementModel->getFullName(false, true, false);
				}
				if ($element->id == $dateEl) {
					$dateEl = $elementModel->getFullName(false, true, false);
				}
				$elParams =& $elementModel->getParams();
					
				//$elParams =& new fabrikParams( $oElement->attribs, $mosConfig_absolute_path . '/administrator/components/com_fabrik/xml/element.xml', 'component' );
				if ($elParams->get( 'show_in_rss_feed' ) == '1') {
					$heading = $element->label;
					if ($elParams->get( 'show_label_in_rss_feed' ) == '1') {
						$aTableHeadings[$heading]['label']	 = $heading;
					} else {
						$aTableHeadings[$heading]['label']	 = '';
					}
					$aTableHeadings[$heading]['colName'] = $elementModel->getFullName( false, true );
					$aTableHeadings[$heading]['dbField'] = $element->name;
					$aTableHeadings[$heading]['key'] = $elParams->get( 'use_as_fake_key' );
					// $$$ hugh - adding enclosure stuff for podcasting
					if ($element->plugin == 'fabrikfileupload') {
						$aTableHeadings[$heading]['enclosure'] = true;
					}
					else {
						$aTableHeadings[$heading]['enclosure'] = false;
					}
				}
			}
		}

		foreach ($aJoinsToThisKey as $element) {
			$element = $elementModel->getElement();
			$elParams = new fabrikParams( $element->attribs, JPATH_SITE . '/administrator/components/com_fabrik/xml/element.xml', 'component' );
			if ( $elParams->get( 'show_in_rss_feed' ) == '1' ) {
				$heading = $element->label;

				if( $elParams->get( 'show_label_in_rss_feed' ) == '1' ){
					$aTableHeadings[$heading]['label']	 = $heading;
				} else {
					$aTableHeadings[$heading]['label']	 = '';
				}
				$aTableHeadings[$heading]['colName'] = $element->db_table_name . "___" . $element->name;
				$aTableHeadings[$heading]['dbField'] = $element->name;
				$aTableHeadings[$heading]['key'] = $elParams->get( 'use_as_fake_key' );
				// $$$ hugh - adding enclosure stuff for podcasting
				if ($element->plugin == 'fabrikfileupload') {
					$aTableHeadings[$heading]['enclosure'] = true;
				}
				else {
					$aTableHeadings[$heading]['enclosure'] = false;
				}
			}
		}
		
		$rows =& $model->getData();

		$document->title = $table->label;
		$document->description = $table->introduction;
		$document->link = JRoute::_( 'index.php?option=com_fabrik&view=table&tableid=' . $table->id . '&Itemid=' . $Itemid );

		/* check for a custom css file and include it if it exists*/
		$tmpl = JRequest::getVar( 'layout', $table->template );
		$csspath = COM_FABRIK_FRONTEND.DS."views".DS."table".DS."tmpl".DS.$tmpl.DS.'feed.css';

		if (file_exists( $csspath )) {
			$document->addStyleSheet( COM_FABRIK_LIVESITE . "components/com_fabrik/views/table/tmpl/$tmpl/feed.css" );
		}


		
		$view = $model->canEdit() ? 'form' : 'details';

		//list of tags to look for in the row data
		//- if they are there don't put them in the desc but put them in as a seperate item param
		$rsstags = array(
			'<georss:point>' => 'xmlns:georss="http://www.georss.org/georss"'
		);
		foreach ($rows as $group)
		{
			foreach ($group as $row)
			{
				// strip html from feed item title
				//$title = html_entity_decode($this->escape( $row->$titleEl ));

				//get the content
				$str2 = '';
				$str = '<table style="margin-top:10px;padding-top:10px;">';
				//used for content not in dl
				//ok for feed gator you cant have the same item title so we'll take the first value from the table (asume its the pk and use that to append to the item title)'
				$title = '';
				$item =& new JFabrikFeedItem();
				
				foreach ($aTableHeadings as $heading=>$dbcolname) {
					if ($dbcolname['enclosure']) {
						// $$$ hugh - diddling aorund trying to add enclosures
						$colName = $dbcolname['colName'] . '_raw';
						$enclosure_url = $row->$colName;
						if (!empty($enclosure_url)) {
							$enclosure_file = COM_FABRIK_BASE . $enclosure_url;
							$enclosure_url = COM_FABRIK_LIVESITE . str_replace('\\','/',$enclosure_url);
							if (file_exists($enclosure_file) and !is_dir($enclosure_file)) {
								$enclosure_type = '';
								if ($enclosure_type = FabrikWorker::getAudioMimeType($enclosure_file)) {
									$enclosure_size = filesize($enclosure_file);
									$enclosures[] = array(
										'url' => $enclosure_url,
										'length' => $enclosure_size,
										'type' => $enclosure_type
									);
									// no need to insert the URL in the description, as feed readers should
									// automagically show 'media' when they see an 'enclosure', so just move on ..
									continue;
								}
							}
						}
					}					
					if ($title == '') {
						//set a default title
						$title = $row->$dbcolname['colName'];
					}
					$rsscontent = $row->$dbcolname['colName'];
					
					$found = false;
					foreach ($rsstags as $rsstag =>$namespace) {
						if (strstr($rsscontent, $rsstag)) {
							$found = true;
							if (!strstr($document->_namespace, $namespace)) {
								$rsstag = substr($rsstag, 1, strlen($rsstag)-2);
								$document->_itemTags[] = $rsstag;
								$document->_namespace .=  $namespace . " ";
							}
							break;
						}
					}
					
					if ($found) {
						$item->{$rsstag} = $rsscontent;
					} else {
						if ($dbcolname['label'] == '') {
							$str2 .= $rsscontent . "<br />\n";
						} else {
							$str .= "<tr><td>" . $dbcolname['label'] . ":</td><td>" . $rsscontent . "</td></tr>\n";
						}
					}
				}

				if (isset( $row->$titleEl )) {
					$title = $row->$titleEl;
				}
				$str = $str2 . $str . "</table>";

				// url link to article
				$link = JRoute::_('index.php?option=com_fabrik&view='.$view.'&tableid='.$table->id.'&fabrik='.$form->id.'&rowid='. $row->__pk_val );

				// strip html from feed item description text
				$author			= @$row->created_by_alias ? @$row->created_by_alias : @$row->author;

				if ($dateEl != '') {
					$date = ( $row->$dateEl ? date( 'r', strtotime(@$row->$dateEl) ) : '' );
				} else {
					$date = '';
				}
				// load individual item creator class
				
				$item->title 		= $title;
				$item->link 		= $link;
				$item->guid 		= $link;
				$item->description 	= $str;
				$item->date			= $date;
				$item->category   	= $row->category;
				
				foreach ($enclosures as $enclosure) {
					$item->setEnclosure($enclosure);
				}
				
				// loads item info into rss array
				$document->addItem( $item );
			}
		}
	}

}
?>