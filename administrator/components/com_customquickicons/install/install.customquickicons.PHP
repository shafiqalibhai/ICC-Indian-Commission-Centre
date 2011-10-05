<?php
/**
* @version $Id: install.customquickicons.php,v 2.2 2009/01/03 12:42:42 mic $
* @version 2.1.3
* @package Custom QuickIcons
* @copyright (C) 2006/7/8 mic [ http://www.joomx.com ]
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* this installer is based upon a script by mic [ www.joomx.com ] and should not be used anywhere
* without permission from the author!
*/

defined( '_JEXEC' ) or die( 'Restricted access' );

// load the component language file
$language =& JFactory::getLanguage();
$language->load( 'com_customquickicons' );

$db	=& JFactory::getDBO();

$queri		= array();
$errors 	= array();
$msg		= array();
$dataSum	= '';
$modSuccess = false;

// backup old table if existing
$query = 'SELECT count(id)'
. ' FROM #__custom_quickicons'
;
$db->setQuery( $query );
$total = $db->loadResult();

$cqiOLD = '';
if( $total ) {
	$cqiOLD = date( 'YmdHi' );
	$query = 'RENAME TABLE #__custom_quickicons'
	. ' TO #__custom_quickicons_bu_' . $cqiOLD
	;
	$db->setQuery( $query );
    if( !$db->query() ) {
        $errors[] = array( $db->getErrorMsg(), $query );
    }else{
        $dataSum++;
        $msg[] = JTEXT::_( 'Old table successfully saved' );
    }
}

// create table
$query = 'CREATE TABLE IF NOT EXISTS `#__custom_quickicons` ('
. ' `id` int(11) NOT NULL auto_increment,'
. ' `text` varchar(64) NOT NULL default \'\','
. ' `target` varchar(255) NOT NULL default \'\','
. ' `icon` varchar(255) NOT NULL default \'\','
. ' `ordering` int(10) unsigned NOT NULL default \'0\','
. ' `new_window` tinyint(1) NOT NULL default \'0\','
. ' `prefix` varchar(100) NOT NULL default \'\','
. ' `postfix` varchar(100) NOT NULL default \'\','
. ' `published` tinyint(1) unsigned NOT NULL default \'0\','
. ' `title` varchar(64) NOT NULL default \'\','
. ' `cm_check` tinyint(1) NOT NULL default \'0\','
. ' `cm_path` varchar(255) NOT NULL default \'\','
. ' `akey` varchar(1) NOT NULL default \'\','
. ' `display` tinyint(1) NOT NULL default \'0\','
. ' `access` int(11) unsigned NOT NULL default \'0\','
. ' `gid` int(3) default \'25\','
. ' `checked_out` int(11) NOT NULL default \'0\','
. ' `checked_out_time` datetime NOT NULL default \'0000-00-00 00:00:00\','
. ' PRIMARY KEY (`id`)'
. ') TYPE=MyISAM;'
;
$db->setQuery( $query );
if( !$db->query() ) {
    $errors[] = array( $db->getErrorMsg(), $query );
}else{
    $dataSum++;
    $msg[] = JTEXT::_( 'New table successfully created' );
}

if( !$errors ) {
    // now submit db.entries w lang.vars
    $cqi	= array();
	$index	= 'index.php?option=com_';
	$image	= '/administrator/templates/khepri/images/header/icon-48-';

	$cqi[] = array(
    	'text'	=> 'Joomla Website',
    	'link'	=> 'http://joomla.org',
    	'img'	=> '/administrator/components/com_customquickicons/images/browser.png',
    	'order'	=> 1,
    	'nwind'	=> 1,
    	'pre'	=> '<i><b style="color:green">',
    	'pos'	=> '</b></i>',
    	'acc'	=> '62',
    	'alt'	=> 'Joomla Website',
    	'akey'	=> ''
    );
    $cqi[] = array(
    	'text'	=> JTEXT::_( 'New Article' ),
    	'link'	=> $index . 'content&task=add',
    	'img'	=> $image . 'article-add.png',
    	'order'	=> 2,
    	'nwind'	=> 0,
    	'pre'	=> '',
    	'pos'	=> '',
    	'acc'	=> '',
    	'alt'	=> JTEXT::_( 'New Article' ),
    	'akey'	=> 'N'
    );
    $cqi[] = array(
    	'text'	=> JTEXT::_( 'Sections' ),
    	'link'	=> $index . 'sections&scope=content',
    	'img'	=> $image . 'section.png',
    	'order'	=> 4,
    	'nwind'	=> 0,
    	'pre'	=> '',
    	'pos'	=> '',
    	'acc'	=> '',
    	'alt'	=> JTEXT::_( 'Sections' ),
    	'akey'	=> 'B'
    );
    $cqi[] = array(
    	'text'	=> JTEXT::_( 'Frontpage' ),
    	'link'	=> $index . 'frontpage',
    	'img'	=> $image . 'frontpage.png',
    	'order'	=> 5,
    	'nwind'	=> 0,
    	'pre'	=> '',
    	'pos'	=> '',
    	'acc'	=> '',
    	'alt'	=> JTEXT::_( 'Frontpage' ),
    	'akey'	=> 'F'
    );
    $cqi[] = array(
    	'text'	=> JTEXT::_( 'Article' ),
    	'link'	=> $index . 'content',
    	'img'	=> $image . 'article.png',
    	'order'	=> 3,
    	'nwind'	=> 0,
    	'pre'	=> '',
    	'pos'	=> '',
    	'acc'	=> '',
    	'alt'	=> JTEXT::_( 'Article' ),
    	'akey'	=> 'A'
    );
    $cqi[] = array(
    	'text'	=> JTEXT::_( 'Media' ),
    	'link'	=> $index . 'media',
    	'img'	=> $image . 'media.png',
    	'order'	=> 8,
    	'nwind'	=> 0,
    	'pre'	=> '',
    	'pos'	=> '',
    	'acc'	=> '',
    	'alt'	=> JTEXT::_( 'Media' ),
    	'akey'	=> 'M'
    );
    $cqi[] = array(
    	'text'	=> JTEXT::_( 'Categories' ),
    	'link'	=> $index . 'categories&section=com_content',
    	'img'	=> $image . 'category.png',
    	'order'	=> 6,
    	'nwind'	=> 0,
    	'pre'	=> '',
    	'pos'	=> '',
    	'acc'	=> '',
    	'alt'	=> JTEXT::_( 'Categories' ),
    	'akey'	=> 'K'
    );
    $cqi[] = array(
    	'text'	=> JTEXT::_( 'Menus' ),
    	'link'	=> $index . 'menus',
    	'img'	=> $image . 'menumgr.png',
    	'order'	=> 7,
    	'nwind'	=> 0,
    	'pre'	=> '',
    	'pos'	=> '',
    	'acc'	=> '',
    	'alt'	=> JTEXT::_( 'Menus' ),
    	'akey'	=> 'R'
    );
    $cqi[] = array(
    	'text'	=> JTEXT::_( 'Languages' ),
    	'link'	=> $index . 'languages&client=0',
    	'img'	=> $image . 'language.png',
    	'order'	=> 9,
    	'nwind'	=> 0,
    	'pre'	=> '',
    	'pos'	=> '',
    	'acc'	=> '',
    	'alt'	=> JTEXT::_( 'Languages' ),
    	'akey'	=> 'L'
    );
    $cqi[] = array(
    	'text'	=> JTEXT::_( 'Users' ),
    	'link'	=> $index . 'users',
    	'img'	=> $image . 'user.png',
    	'order'	=> 10,
    	'nwind'	=> 0,
    	'pre'	=> '',
    	'pos'	=> '',
    	'acc'	=> '',
    	'alt'	=> JTEXT::_( 'Users' ),
    	'akey'	=> 'U'
    );
    $cqi[] = array(
    	'text'	=> JTEXT::_( 'Configuration' ),
    	'link'	=> $index . 'config',
    	'img'	=> $image . 'config.png',
    	'order'	=> 11,
    	'nwind'	=> 0,
    	'pre'	=> '',
    	'pos'	=> '',
    	'acc'	=> '',
    	'alt'	=> JTEXT::_( 'Configuration' ),
    	'akey'	=> 'C'
    );

    foreach( $cqi as $cq ) {
    	$query = "INSERT INTO #__custom_quickicons VALUES ('', " . $db->Quote( $cq['text'] ) . ", '" . $cq['link'] . "', '" . $cq['img'] . "', " . $cq['order'] . ", " . $cq['nwind'] . ", '" . $cq['pre'] . "', '" . $cq['pos'] . "', 1, " . $db->Quote( $cq['alt'] ) . ", '', '', '" . $cq['akey'] . "', '', '" . $cq['acc'] . "', 25, '', '0000-00-00 00:00:00')";
        $db->setQuery( $query );
        if( !$db->query() ) {
            $errors[] = array( $db->getErrorMsg(), $query );
        }else{
            $dataSum++;
        }
    }
}

if( !$errors ) {
    /**
     * copy and install module
     * 1. check if directory exists (normally should)
     * 2. check perms
     * 3. copy
     * 4. register module
     * 5. delete install files/dir
     * 6. set standard module inactive
     */
    $installdir	= JPATH_ADMINISTRATOR .DS. 'components' .DS. 'com_customquickicons' .DS. 'modules' .DS;
    $targetdir	= JPATH_ADMINISTRATOR .DS. 'modules' .DS. 'mod_customquickicons' .DS;
    $toCopy		= '';
    $fileCopy	= array(
    	array(
	    	'txt'			=> 'mod_customquickicons.php',
	    	'installFrom'	=> $installdir . 'mod_customquickicons.php',
	    	'installTo'		=> $targetdir . 'mod_customquickicons.php',
	    	'errText'		=> JText::_( 'ERROR: Could not copy module file' ),
	    	'succText'		=> 'Module file %s successfully copied'
	    ),
	    array(
	    	'txt'			=> 'mod_customquickicons.xml',
	    	'installFrom'	=> $installdir . 'mod_customquickicons.xm_',
	    	'installTo'		=> $targetdir . 'mod_customquickicons.xml',
	    	'errText'		=> JText::_( 'ERROR: Could not copy module file' ),
	    	'succText'		=> 'Module file %s successfully copied'
	    ),
	    array(
	    	'txt'			=> 'cqi.css',
	    	'installFrom'	=> $installdir . 'cqi.css',
	    	'installTo'		=> $targetdir . 'cqi.css',
	    	'errText'		=> JText::_( 'ERROR: Could not copy module file' ),
	    	'succText'		=> 'Module file %s successfully copied'
	    )
	);

    if( !JFolder::exists( $targetdir ) ) {
    	//create directory
    	if( !JFolder::create( $targetdir, 0755 ) ) {
    		$errors[] = array( JTEXT::_( 'ERROR: Could not create target directory' ), 'administrator' .DS. 'modules' );
    	}
    }
}

if( !$errors ) {
    // step 3
	foreach( $fileCopy as $fc ) {
		if( !JFile::copy( $fc['installFrom'] , $fc['installTo'] ) ) {
	    	$errors[] = array( $fc['errText'], $fc['txt'] );
	    }else{
	    	$msg[] = JTEXT::sprintf( $fc['succText'], $fc['txt'] );
	    	$modSuccess = true;
	    }
	}
}

if( !$errors ) {
	// languages - this is a special case, because copying is not handled through the CMS.installer itself
    $targetdir	= JPATH_ADMINISTRATOR .DS. 'language' .DS;
    $lngSuccess	= false;
    $fileCopy	= array(
	    array(
	    	'txt'			=> 'de-DE.mod_customquickicons.ini',
	    	'installFrom'	=> $installdir . 'de-DE.mod_customquickicons.ini',
	    	'installTo'		=> $targetdir . 'de-DE' .DS. 'de-DE.mod_customquickicons.ini',
	    	'errText'		=> 'Error copy language file [%s]',
	    	'succText'		=> 'Copy language file'
	    ),
	    array(
	    	'txt'			=> 'en-GB.mod_customquickicons.ini',
	    	'installFrom'	=> $installdir . 'en-GB.mod_customquickicons.ini',
	    	'installTo'		=> $targetdir . 'en-GB' .DS. 'en-GB.mod_customquickicons.ini',
	    	'errText'		=> 'Error copy language file [%s]',
	    	'succText'		=> 'Copy language file'
	    )
	);

	foreach( $fileCopy as $fc ) {
		if( JFile::copy( $fc['installFrom'] , $fc['installTo'] ) ) {
	    	$msg[] = JTEXT::sprintf( $fc['succText'], $fc['txt'] );
	    	$lngSuccess = true;
	    }else{
	    	$errors[] = array( JTEXT::sprintf( $fc['errText'], $fc['txt'] )
	    	. '<br />' . $fc['installFrom'] . '<br />' . $fc['installTo'], '' );
	    }
	}
}

if( $modSuccess && $lngSuccess ) {
    // step 4
    // register the module and set active
	$query = "INSERT INTO #__modules VALUES ('', 'CQI - CustomQuickIcons', '', 2, 'icon', 0, '0000-00-00 00:00:00', 1, 'mod_customquickicons', 0, 0, 1, '', 0, 1, '')";
	$db->setQuery( $query );
	if( !$db->query() ){
	    $errors[] = array( $db->getErrorMsg(), $query );
	}else{
		$dataSum++;
		$msg[] = JTEXT::_( 'New module CQI successfully registered' );
	}
}else{
	$msg[] = JTEXT::_( 'Module not installed' );
}

if( !$errors ) {
	// disable standard module quick_icon (only if install was successful!)
	if( $modSuccess ) {
		$query = 'SELECT id FROM #__modules WHERE module = \'mod_quickicon\'';
		$db->setQuery( $query );
		$id = $db->loadResult();

		$query = 'UPDATE #__modules SET `published` = \'0\' WHERE id=' . $id . ' LIMIT 1;';
		$db->setQuery( $query );
		$db->query();
	}
}

// copy individual tip.png
$img 	= JPATH_ADMINISTRATOR .DS. 'components' .DS. 'com_customquickicons' .DS. 'images' .DS. 'tip.png';
$target = JPATH_SITE .DS. 'includes' .DS. 'js' . DS. 'ThemeOffice' .DS. 'tip.png';
JFile::copy( $img, $target );

// step 5 - delete files and dir install (we do not need it anymore)
/*
// mic 20081116: due some unknown behaviour this section is disabled!
$delFiles = JFolder::files( $installdir );
foreach( $delFiles as $delFile ) {
	if( !JFile::delete( $installdir . $delFile ) ) {
		$errors[] = array( JTEXT::_( 'ERROR: Could not delete file' ), $delFile);
	}
}
JFolder::delete( $installdir );

// finally delete unwanted dir at user side
JFolder::delete( JPATH_SITE .DS. 'components' .DS. 'com_customquickicons' );
*/
?>
<style type="text/css" media="screen">
    <!--
    .table {
        width       : 95%;
        border      : 1px solid #666666;
        text-align  : left;
        font-size   : 0.95em;
        margin		: 5px auto 5px auto;
    }
    .text {
        color       : #666666;
        width       : 700px;
        text-align  : left;
        margin-left : 3px;
    }
    .code {
        width       : 700px;
        white-space : pre;
        margin      : 5px;
        text-align  : left;
        border      : 1px solid #FF0000;
    }
    .bold {
        font-weight : bold;
    }
    .ads {
        white-space : pre;
        border      : 1px solid #7EA9C2; /* 336699 */
        padding     : 10px;
        margin      : auto;
        width       : 750px;
        background  : #F9FDFF;
        text-align  : center;
    }
    .hint {
        background  : #F3F9FF; // FFDDDD;
        border      : 1px solid #006699; // FF0000;
        margin      : 10px;
        padding     : 10px;
    }
    .info {
		margin			: 3px;
		padding			: 3px;
		border-top		: 2px solid #409F3B;
		border-bottom	: 2px solid #409F3B;
		background-color: #F0FFEF;
		font-weight		: bold;
	}
    -->
</style>

<div class="table">
	<div class="text">
        <?php
        if( $errors ){
            echo '<strong style="color:red;">'
            . JTEXT::_( 'Unfortunately there were some errors during the installation process!' )
            . '</strong>';
            echo '<ul>';
            foreach( $errors as $error ){
                echo '<li>' . $error[0] . '</li>';
            }
            echo '</ul>';
        }

        // in case module could not be installed
        if( !$modSuccess || !$lngSuccess ) {
        	echo '<br /><strong style="color:red;">' . JTEXT::_( 'Module could not be installed' ) . '</strong><br />';
        }

        echo '<strong style="color:green;">' . JTEXT::_( 'Component successfully installed' ) . '</strong>';
        echo ' - ' . $dataSum . ' ' . JTEXT::_( 'Database entries' );
        if( $msg ) {
        	echo '<ul>';
            foreach( $msg as $mgs ){
                echo '<li>' . $mgs . '</li>';
            }
            echo '</ul>';
        } ?>
    </div>
    <div class="text">
    	<ul>
    		<li>
    			<a href="http://joomlacode.org/gf/project/joomx/" title="<?php echo JTEXT::_( 'CQI Project site' ); ?>" target="_blank"><?php echo JTEXT::_( 'CQI Project site' ); ?></a>
    		</li>
    		<li>
    			<a href="http://joomlacode.org/gf/project/joomx/" title="<?php echo JTEXT::_( 'Latest Version' ); ?>" target="_blank"><?php echo JTEXT::_( 'Latest Version' ); ?></a>
    		</li>
    		<li>
    			<a href="http://joomlacode.org/gf/project/joomx/" title="<?php echo JTEXT::_( 'Error/Bug reports' ); ?>" target="_blank"><?php echo JTEXT::_( 'Error/Bug reports' ); ?></a>
    		</li>
    		<li>
    			<a href="http://joomlacode.org/gf/project/joomx/" title="<?php echo JTEXT::_( 'Forum/Discussion' ); ?>" target="_blank"><?php echo JTEXT::_( 'Forum/Discussion' ); ?></a>
    		</li>
    		<li>
    			<a href="mailto:info@joomx.com" title="<?php echo JTEXT::_( 'Email' ); ?>"><?php echo JTEXT::_( 'Email' ); ?></a>
    		</li>
    	</ul>
    </div>
    <div class="text">
        <?php echo JTEXT::_( 'CustomQuickIcons - an add on for Joomla 1.5.x' ); ?>
    </div>
    <div style="clear:both;"></div>
    <?php
    if( $cqiOLD ) { ?>
        <div class="info">
        	<?php echo JTEXT::_( 'Convert old table' ); ?>
        	<div style="text-align:center; margin:5px;">
        		<a href="index.php?option=com_customquickicons&amp;task=convert&amp;table=<?php echo $cqiOLD; ?>" class="button"><?php echo JTEXT::_( 'Take over data' ); ?></a>
        	</div>
        </div>
        <div style="clear:both;"></div>
        <?php
    } ?>
	<div class="hint">
		<?php
    	if( !$errors ) {
    		echo JTEXT::_( 'CQI - CustomQuickIcons sucessfully installed' );
    	} ?>
    	&nbsp;&nbsp;
        Another professional tool from <a href="http://www.joomx.com" target="_blank" title="www.joomx.com">JoomX - Joomla Professionals @ Work</a>
	</div>
</div>