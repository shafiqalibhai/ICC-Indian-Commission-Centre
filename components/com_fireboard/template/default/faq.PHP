<?php

// Dont allow direct linking
defined ('_VALID_MOS') or die('Direct Access to this location is not allowed.');

global $fbConfig;
?>
<div class="<?php echo $boardclass; ?>_bt_cvr1">
<div class="<?php echo $boardclass; ?>_bt_cvr2">
<div class="<?php echo $boardclass; ?>_bt_cvr3">
<div class="<?php echo $boardclass; ?>_bt_cvr4">
<div class="<?php echo $boardclass; ?>_bt_cvr5">
<table class = "fb_blocktable" id ="fb_forumfaq" border = "0" cellspacing = "0" cellpadding = "0" width="100%">
            <thead>
                <tr>
                    <th >
                        <div class = "fb_title_cover fbm">
                        <span class="fb_title fbl" ><?php echo _COM_FORUM_HELP; ?></span>
                        </div>
                </tr>
            </thead>
            <tbody>
            <tr>
            <td class="fb_faqdesc" valign="top">

        <?php
          $database->setQuery("SELECT introtext FROM #__content  WHERE id=".$fbConfig->help_cid."");
		  $j_introtext = $database->loadResult();

           ?>
            <?php echo $j_introtext; ?>


         </td>
         </tr>
         </tbody>
         </table>
</div>
</div>
</div>
</div>
</div>
                     <!-- Begin: Forum Jump -->
<div class="<?php echo $boardclass; ?>_bt_cvr1">
<div class="<?php echo $boardclass; ?>_bt_cvr2">
<div class="<?php echo $boardclass; ?>_bt_cvr3">
<div class="<?php echo $boardclass; ?>_bt_cvr4">
<div class="<?php echo $boardclass; ?>_bt_cvr5">
<table class = "fb_blocktable" id="fb_bottomarea"  border="0" cellspacing="0" cellpadding="0" width="100%">
  <thead>
    <tr>
       <th class="th-right">
       <?php
//(JJ) FINISH: CAT LIST BOTTOM
if ($fbConfig->enableforumjump)
require_once (JB_ABSSOURCESPATH . 'fb_forumjump.php');
?></th>
    </tr>
  </thead>
  <tbody><tr><td></td></tr></tbody>
  </table>
  </div>
</div>
</div>
</div>
</div>
  <!-- Finish: Forum Jump -->