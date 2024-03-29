<?php
/**
 * @package Joomla
 * @subpackage Fabrik
 * @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */


// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.application.component.controller' );
jimport( 'joomla.application.component.model' );
JModel::addIncludePath(COM_FABRIK_FRONTEND.DS.'models');

class FabrikViewHome {

	/**
	 * Display home page
	 */

	function show( $feed )
	{
		JHTML::stylesheet('admin.css', 'media/com_fabrik/css/');
		jimport('joomla.html.pane');
		$pane	=& JPane::getInstance( 'Sliders' );
		JToolBarHelper::title( JText::_( 'WELCOME' ), 'fabrik.png' );
		?>

<table class="adminForm" style="width: 100%">
	<tbody>
		<tr>
			<td valign="top" style="width: 50%">
			<a href="http://fabrikar.com">
				<?php echo JHTML::image('media/com_fabrik/images/logo.png', 'Fabrik logo'); ?>
				</a>
			<div style="float:left;width:250px;margin-top:30px;">
			<a href="http://fabrikar.com/index.php?option=com_acctexp&task=register&Itemid=44">
				<?php echo JHTML::image('media/com_fabrik/images/box.png', 'Fabrik'); ?>
			</a>
			</div>
			<div style="margin-left:200px;margin-top:30px;">
			<h1>Subscribe and get</h1>
			<ul>
				<li>Dedicated support</li>
				<li>Concise and clear documentation</li>
				<li>Video tutorials</li>
			</ul>
			<a href="http://fabrikar.com/index.php?option=com_acctexp&task=register&Itemid=44">
			<?php echo JHTML::image('media/com_fabrik/images/subscribe-now.png', 'Fabrik'); ?>
			</a><br />
			</div>

			</td>
			<td valign="top"  style="width: 50%"><?php 
			echo $pane->startPane( "content-pane" );
			echo $pane->startPanel( 'About', "publish-page" );
			echo "<table class='adminlist'>
			<tr><td><p>Fabrik is an open source Joomla application builder
component.</p>
<p>Fabrik gives people the power to create forms, tables and visualizations that run inside
Joomla without requiring knowledge of mySQL and PHP, all from within the
familiar Joomla administration interface.</p>
<p>With Fabrik you can create
applications that range in complexity from simple contact forms to
complex applications such as a job application site or bug tracking
systems.</p></td></tr></table>";
			echo $pane->endPanel();

			echo $pane->startPanel( 'News', "publish-page" );
			echo $feed;
			echo $pane->endPanel();

			echo $pane->startPanel( 'Stats', "publish-page" );
			?>
			<table class='adminlist'>
			<tbody>
					<tr>
						<td>
						Coming soon...
						</td>
					</tr>
				</tbody>
			</table>
			<?php
			echo $pane->endPanel();

			echo $pane->startPanel( 'Useful links', "publish-page" );
			?>
			<table class='adminlist'>
				<tbody>
					<tr>
						<td>
						<ul>
							<li><a href="http://fabrikar.com/">Fabrik web site</a></li>
							<li><a href="http://fabrikar.com/forums">Forum</a>
							<li><a
								href="http://fabrikar.com/index.php?option=com_openwiki&Itemid=11">Documentation
							WIKI</a></li>
						</ul>
						</td>
					</tr>
				</tbody>
			</table>
			<?php
			echo $pane->endPanel();

			echo $pane->startPanel( 'Tools', "publish-page" ); ?>
			<table class='adminlist'>
				<tbody>
					<tr>
						<td>
						<ul>
							<li><a href="index.php?option=com_fabrik&task=installSampleData">Install
							Sample data</a></li>
							<li><a onclick="return confirm('Are you really sure this will wipe ALL your Fabrik data?');" href="index.php?option=com_fabrik&c=home&task=reset"><?php echo JText::_('Reset Fabrik') ?></a></li>
						</ul>
						</td>
					</tr>
				</tbody>
			</table>
			<?php
			echo $pane->endPanel();
			echo $pane->endPane();
			?></td>
		</tr>
	</tbody>
</table>
			<?php }
}
?>