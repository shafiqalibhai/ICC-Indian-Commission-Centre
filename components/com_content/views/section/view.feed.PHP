<?php
defined('_JEXEC') or die( 'Restricted access' );

jimport( 'joomla.application.component.view');

/**
 * HTML View class for the Content component
 *
 * @static
 * @subpackage	Content
 * @since 1.5
 */
class ContentViewSection extends JView
{
	function display()
	{
		global $mainframe;
		$doc =& JFactory::getDocument();
		$params =& $mainframe->getParams();

		// Get some data from the model
		JRequest::setVar('limit', $mainframe->getCfg('feed_limit'));

		// Lets get our data from the model
		$rows 		= &$this->get( 'Data' );
		$section	= &$this->get( 'Section' );

		$doc->link = JRoute::_(ContentHelperRoute::getSectionRoute($section->id));;

		foreach ( $rows as $row )
		{
			// strip html from feed item title
			$title = $this->escape( $row->title );
			$title = html_entity_decode( $title );

			// url link to article
			// & used instead of &amp; as this is converted by feed creator
			$link = JRoute::_(ContentHelperRoute::getArticleRoute($row->slug, $row->catslug, $row->sectionid));;

			// strip html from feed item description text
			$description	= ($params->get('feed_summary', 0) ? $row->introtext.$row->fulltext : $row->introtext);
			$author			= $row->created_by_alias ? $row->created_by_alias : $row->author;

			// load individual item creator class
			$item = new JFeedItem();
			$item->title 		= $title;
			$item->link 		= $link;
			$item->description 	= $description;
			$item->date			= $row->created;
			$item->category   	= $row->category;

			// loads item info into rss array
			$doc->addItem( $item );
		}
	}
}
