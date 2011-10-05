<?php // no direct access
defined('_JEXEC') or die('Restricted access'); ?>
<ul class="menu">
<?php for($i=0;$i<count($menu);$i++) :
	$link = $menu[$i];
	$hasChildren = $childMenu[0] == $i;
	?>
            <li class="<?php echo $hasChildren ? 'parent active' : ''; ?> jforcemenu<?php echo $params->get('moduleclass_sfx'); ?>">
                <a href="<?php echo $link->link; ?>" class="jforcemenu<?php echo $params->get('menuclass_sfx'); ?>">
                   <span><?php echo $link->text; ?></span></a>
            
            <?php if($hasChildren): ?>
				<ul>
                   <?php for($j=1;$j<count($childMenu);$j++): 
				   		$l = $childMenu[$j];
				   ?> 
                    <li class="jforcemenu<?php echo $params->get('moduleclass_sfx'); ?>">
                        <a href="<?php echo $l->link; ?>" class="jforcemenu<?php echo $params->get('menuclass_sfx'); ?>">
                            <span><?php echo $l->text; ?></span></a>
                    </li>
                    <?php endfor; ?>
				</ul>
               </li>
               <?php else: ?>
               </li>
			<?php endif; ?>
	
    <?php endfor; ?>
</ul>