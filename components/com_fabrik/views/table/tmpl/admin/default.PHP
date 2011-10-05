<h1><?php echo $this->table->label;?></h1>
<?php echo $this->table->intro;?>
<form action="<?php echo $this->table->action;?>" method="post" id="<?php echo $this->formid;?>" name="fabrikTable">

<?php if ($this->showAdd) {?>
	<span class="pagenav ">
		<a class="addbutton" href="<?php echo $this->addRecordLink;?>"><?php echo JText::_( 'ADD' );?></a>
	</span>
<?php }?>

<?php if ($this->showCSV) {?>
	<span class="csvExportButton" id="fabrikExportCSV">
		<a href="#"><?php echo JText::_( 'EXPORT TO CSV' );?></a>
	</span>
<?php }?>

<?php if($this->showCSVImport){?>
	<span class="csvImportButton" id="fabrikImportCSV">
		<a href="<?php echo $this->csvImportLink;?>"><?php echo JText::_( 'IMPORT FROM CSV' );?></a>
	</span>
<?php }?> 

<?php if ($this->showRSS) {?>
	<span class="pagenav">
		<a href="<?php echo $this->rssLink;?>"><?php echo JText::_( 'SUBSCRIBE RSS' );?></a>
	</span>
<?php }?>

<?php if ($this->showFilters) {?>
	<table class="filtertable">
		<tr>
			<th colspan="2" style="text-align:left"><?php echo JText::_( 'SEARCH' );?>:</th>
		</tr>
		<?php
		$c = 0;
		foreach ($this->filters as $filter) {
			$required = $filter->required == 1 ? ' class="notempty"' : '';?>
			<tr class="fabrik_row oddRow<?php echo ($c % 2);?>">
				<td<?php echo $required ?>><?php echo $filter->label;?></td>
				<td style="text-align:right;"><?php echo $filter->element;?></td>
			</tr>
		<?php $c++;
		} ?>
		<?php if($this->filter_action != 'onchange') {?>
		<tr>
			<td colspan="2" style="text-align:right;"><input type="button"
				class="fabrik_filter_submit button" value="<?php echo JText::_( 'GO' );?>"
				name="filter" /></td>
		</tr>
		<?php }?>
	</table>
<?php } // end show filters ?>


<?php if( count( $this->rows ) == 0 ){?>
	<div class="emptyDataMessage"><?php echo $this->emptyDataMessage; ?></div>
<?php }else{
	echo $this->loadTemplate('group'); 
	?>
<table class="adminlist fabrikTable">	
	<tfoot>
		<tr>
		<td colspan="<?php echo count($this->headings) ?>">
			<?php echo $this->nav;?>
			<div style="text-align:right">
			<?php
			echo $this->deleteButton;
			echo "&nbsp;" . $this->emptyButton;
			foreach ($this->pluginButtons as $pluginbutton) {
				echo "&nbsp;" . $pluginbutton; 
			}?>
			</div>
		</td>
		</tr>
	</tfoot>
	</table>
	<?php print_r( $this->hiddenFields );?>
	</form>
<?php } //end not empty?> 


