var fbDatabasejoin = FbElement.extend({
	initialize: function(element, options) {
		this.plugin = 'fabrikdatabasejoin';
		this.options = Object.extend({
			'liveSite':'',
			'popupform':49,
			'id':0,
			'formid':0,
			'key':'',
			'label':'',
			'popwiny':0
		}, options || {});
		
		this.setOptions(element, this.options);
		//if users can add records to the database join drop down
		if($(element + '_add')){
			this.startEvent = this.start.bindAsEventListener(this);
			$(element + '_add').addEvent('click', this.startEvent);
			
			//register the popup window with the form this element is in
			//do this so that the database join drop down can be updated
			oPackage.bindListener('form_' + this.options.popupform, 'form_' + this.options.formid);
		}
	},
	
	getValue:function(){
		this.getElement();
		if(!this.options.editable){
			return this.options.defaultVal;
		}
		if($type(this.element) === false){
			return '';
		}
		if(this.options.display_type != 'dropdown') {
			var v = '';
			this._getSubElements().each(function(sub){
				if(sub.checked){
					v = sub.getValue();
					return v;
				}
				return null;
			});
			return v;
		}
		if($type(this.element.getValue()) === false){
			return '';
		}
		return this.element.getValue();
	},
	
	//prepare data for ajax validation
	prepereForAjaxPost: function(d)
	{
		if(!this.options.editable){
			return d;
		}
		var v = this.getValue();
		if($type(v) !== false){
			var element = this.element;
			if(this.options.display_type != 'dropdown') {
				
				//element is the container for checkboxes so get first checkbox id, minus the '_0' at the end
				var name = element.getElement('input').name.replace('[]', '');
			}else{
				name = element.name;
			}
	
			if(d.hasKey(name)){ //repeat data test
				var orig = d.get(name);
				if($type(orig) !== 'array'){
					orig = [orig];
				}
				orig.push(v)
				d.set(name, orig);
			}else{
				d.set(name, v);
			}
		}
		return d;
	},
	
	start: function(event){
		var e = new Event(event);
		var url = this.options.liveSite + "index.php?option=com_fabrik&view=form&tmpl=component&_postMethod=ajax&fabrik=" + this.options.popupform;
		var id = this.element.id + '-popupwin';
		this.windowopts = {
			'id': id,
			title: 'Add',
			contentType: 'xhr',
			loadMethod:'xhr',
			contentURL: url,
			width: 320,
			height: 320,
			y:this.options.popwiny,
			'minimizable':false,
			'collapsible':true,
			onContentLoaded: function(){
				var myfx = new Fx.Scroll(window).toElement(id);
				//resize //@TODO add check to ensure window size isnt greater than browser window
				windowEl =$(id);
				if(this.options.mooversion > 1.1){
					var currentInstance = MochaUI.Windows.instances.get(windowEl.id);
					var contentWrapperEl = currentInstance.contentWrapperEl;
					var contentEl = currentInstance.contentEl;
				}else{
					contentWrapperEl = windowEl.getElement('.mochaContent');
					contentEl = windowEl.getElement('.mochaScrollerpad');
				}
				var h = contentEl.offsetHeight < window.getHeight() ? contentEl.offsetHeight : window.getHeight();
				var w = contentWrapperEl.getSize().scrollSize.x + 40 < window.getWidth() ? contentWrapperEl.getSize().scrollSize.x + 40 : window.getWidth();  
				contentWrapperEl.setStyle('height', h);
				contentWrapperEl.setStyle('width', w);
				if(this.options.mooversion > 1.1){
					currentInstance.drawWindow(windowEl);
				}else{
					document.mochaDesktop.drawWindow(windowEl);
				}
			}.bind(this)
		};
				
		if(this.options.mooversion > 1.1){
			var mywin = new MochaUI.Window(this.windowopts);
		}else{
			document.mochaDesktop.newWindow(this.windowopts);
		}
		e.stop();

	},
	
	update: function(val){
		this.getElement();
		if($type(this.element) === false){
			return;
		}
		if (!this.options.editable) {
			this.element.innerHTML = '';
			if(val === ''){
				return;
			}
			val = val.split(this.options.splitter);
			var h = $H(this.options.data);
			val.each(function(v){
				this.element.innerHTML += h.get(v) + "<br />";	
			}.bind(this));
			return;
		}
		//@TODO test
		// $$$ hugh - doesn't work 'cos val is a value, not a select index
		//this.element.selectedIndex = val;
		for (var i = 0; i < this.element.options.length; i++) {
			if (this.element.options[i].value == val) {
				this.element.options[i].selected = true;
				break;
			}
		} 
	},
	
	appendInfo: function(data){
		var opts = [];
		if(data === ''){
			return;
		}
		var key = this.options.key;
		var label = this.options.label;
		data = data.data;
		outerLoop:
		for(var i=0;i<data.length;i++){
			var group = data[i];
			for(var j=0;j<group.length;j++){
				var row = group[j];
				if( row[key] && row[label] ){
					//make ajax call to update this dd
					// code requiers us to post and querystring the main vars - not sure y but doesnt work otherwise
					var myajax = new Ajax( this.options.liveSite + 'index.php?option=com_fabrik&format=raw&controller=plugin&task=pluginAjax&method=ajax_getOptions', {
						data :{
							'option':'com_fabrik',
							'format':'raw',
							'controller':'plugin',
							'task':'pluginAjax',
							'plugin':'fabrikdatabasejoin',
							'method':'ajax_getOptions',
							'element_id':this.options.id,
							'formid':this.options.formid
						},
						onSuccess:function(json){
							json = Json.evaluate(json);
							json.each(function(row){
							
								if(this.options.display_type != 'dropdown') {
									var opt = new Element('div',{'class':'fabrik_subelement'}).adopt(
											new Element('label').adopt([
											new Element('input', {'class':'fabrikinput','type':'radio','name':this.options.element,'value':row.value}),
											new Element('span').setText(row.text)
											])
									);
								}else{
									opt = new Element('option', {'value':row.value}).appendText(row.text);
									if(this.options.defaultVal.indexOf(row.value) != -1){
										opt.selected = "selected";
									}
								}
								opts.push(opt);
							}.bind(this));
							$(this.element.id).empty();
							$(this.element.id).adopt(opts);
							if(this.options.mooversion > 1.1){
								MochaUI.closeWindow($(this.element.id + '-popupwin'));
							}else{
								document.mochaDesktop.closeWindow($(this.element.id + '-popupwin'));
							}
							this.setErrorMessage('updated', 'fabrikSuccess');
						}.bind(this)
					}).request();
					break outerLoop;
				}
			}
		}
	
	}
});