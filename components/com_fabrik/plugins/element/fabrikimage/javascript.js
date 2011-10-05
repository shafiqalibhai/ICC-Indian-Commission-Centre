/**
 * @author Robert
*/
var fbImage = FbElement.extend({
	initialize: function(element, options) {
		this.setOptions(element, options);
		this.element = $(element+ '_img');
		this.options.rootPath = options.rootPath;
    this.folderDir = $(element + '_folder');
		this.imageDir = $(element + '_image');
		this.hiddenField = $(element);
		this.image = $(element + '_img');
		this.imageFolderList = [];
		this.selectedFolder = this.getFolderPath();
		
		this.selectedImage = '';
		if(this.imageDir){
			if(this.imageDir.options.length !== 0){
				this.selectedImage = this.imageDir.getValue();
			}
		}
		this.aChangeFolder = this.changeFolder.bindAsEventListener(this);
		this.aShowImage = this.showImage.bindAsEventListener(this);
		if($(this.folderDir)){
		$(this.folderDir).addEvent( 'change', this.aChangeFolder);
		}
		if($(this.imageDir)){
			$(this.imageDir).addEvent( 'change', this.aShowImage);
		}
		// $$$ hugh - don't think we need to do this on init, as the img is
		// already set by the fabrikimage.php render()
		//this.showImage();
	},
	
	getFolderPath: function()
	{
	var f = this.options.rootPath;
		if(this.folderDir){
			if(this.folderDir.options.length !== 0){
				f += '/' +  this.folderDir.getValue();
			}
		}
		return f;
},

	changeFolder: function( e ){
		var event = new Event(e);
		var el = event.target;
		var folder =$( el.id.replace('_folder', '_image'));
		this.selectedFolder = this.getFolderPath();
		folder.empty();
		var url = this.options.liveSite + 'index.php?option=com_fabrik&format=raw&controller=plugin&task=pluginAjax&g=element&plugin=fabrikimage&method=ajax_files';
		var myAjax = new Ajax(url, { method:'post',
		'data':{'folder':this.selectedFolder}, 
			
		onComplete: function(r){
			var newImages = eval(r);
			newImages.each(function(opt){
				folder.adopt(
					new Element('option', {'value':opt.value}).appendText(opt.text)
				);
			});
			this.showImage();
		}.bind(this)}).request();
	},
	
	showImage: function( e ){
		if(e){
			var event = new Event(e);
			var el = event.target;
		}else{
			el = this.imageDir;
		}
		if(el){
			if(el.options.length === 0){
				this.image.src = '';
				this.selectedImage = '';
			}else{
				this.selectedImage = el.getValue();
				this.image.src = this.options.liveSite + '/' + this.selectedFolder + '/' + this.selectedImage;
			}
			this.hiddenField.value =  this.selectedImage;
		}
		
	}	
});