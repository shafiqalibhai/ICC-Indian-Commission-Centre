<?php $form = $this->form;
echo $form->startTag; ?>
<h1><?php echo $form->label;?></h1>
<?php 
echo $form->intro;
echo $this->plugintop; 
$active = ($form->error != '') ? '' : ' fabrikHide';
echo "<div class='fabrikMainError fabrikError$active'>" . $form->error . "</div>";?>

<div><?php echo $this->message ?></div>
	<?php 
	if ($this->showEmail) {
		echo $this->emailLink;
	}
	if ($this->showPDF) {
		echo $this->pdfLink;
	}
	if ($this->showPrint) {
		echo $this->printLink;
	}
	
	foreach ( $this->groups as $group ) {
		?>
		<fieldset class="fabrikGroup" id="group<?php echo $group->id;?>" style="<?php echo $group->css;?>">
		<?php if ($group->canRepeat) {
			foreach ($group->subgroups as $subgroup) {
			?>
				<div class="fabrikSubGroup">
					<div class="fabrikSubGroupElements">
						<?php 
						$this->elements = $subgroup;
						echo $this->loadTemplate('group'); 
						?>
					</div>
					<?php if ($group->editable) { ?>
						<div class="fabrikGroupRepeater">
							<a class="addGroup" href="#">
								<img src="components/com_fabrik/views/form/tmpl/default/images/add.png" alt="<?php echo JText::_( 'Add group' );?>" />
							</a> 
							<a class="deleteGroup" href="#">
								<img src="components/com_fabrik/views/form/tmpl/default/images/del.png" alt="<?php echo JText::_( 'Delete group' );?>" />
							</a>
						</div>
					<?php } ?>
				</div>
				<?php 
			}
		} else {
			$this->elements = $group->elements;
			echo $this->loadTemplate('group'); 
		}?>
	</fieldset>
<?php
	}
	echo $this->hiddenFields;
	?>
	<?php echo $this->pluginbottom; ?>
	<div class="fabrikActions"><?php echo $form->resetButton;?> <?php echo $form->submitButton;?>
	<?php echo $form->copyButton  . " " . $form->gobackButton?>
	</div>

<?php 
echo $form->endTag;
echo FabrikHelperHTML::keepalive();?>