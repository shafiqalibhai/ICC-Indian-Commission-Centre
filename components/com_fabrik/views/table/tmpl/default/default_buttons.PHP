<?php if($this->showAdd){?>
	<span class="addbutton" id="<?php echo $this->addRecordId;?>">
		<a href="<?php echo $this->addRecordLink;?>"><?php echo JText::_( 'ADD' );?></a>
	</span>
<?php }?>

<?php if($this->showCSV){?>
	<span class="csvExportButton" id="fabrikExportCSV">
		<a href="#"><?php echo JText::_( 'EXPORT TO CSV' );?></a>
	</span>
<?php }?> 

<?php if($this->showCSVImport){?>
	<span class="csvImportButton" id="fabrikImportCSV">
		<a href="<?php echo $this->csvImportLink;?>"><?php echo JText::_( 'IMPORT FROM CSV' );?></a>
	</span>
<?php }?> 

<?php if($this->showRSS){?>
	<span class="feedButton" id="fabrikShowRSS">
		<a href="<?php echo $this->rssLink;?>"><?php echo JText::_( 'SUBSCRIBE RSS' );?></a>
	</span>
<?php }?>

<?php if($this->showPDF){
echo $this->pdfLink;
}?>