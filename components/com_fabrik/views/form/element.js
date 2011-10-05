/**
 * @author Robert
 */
var FbElement =  new Class({
	initialize: function(element, options) {
		this.plugin = '';
		this.strElement = element;
		this.setOptions(element, options);
	},

	setOptions: function(element, options) {
		if($(element)){
			this.element = $(element);
		}
		this.options = {
			element:  element,
			defaultVal: '',
			editable:false
		};
		Object.extend(this.options, options || {});
		this.setorigId();
	},
	
	getElement: function()
	{
		//use this in mocha forms whose elements (such as database jons) arent loaded
		//when the class is ini'd
		if($type(this.element) === false){
			this.element = $(this.options.element); 
		}
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
			if($type(element) === false){
				return d;
			}
			if(d.hasKey(element.name)){ //repeat data test
			
				var orig = d.get(element.name);
				if($type(orig) !== 'array'){
					orig = [orig];
				}
				orig.push(v)
				d.set(element.name, orig);
			
			}else{
				d.set(element.name, v);
			}
		}
		return d;
	},
	
	//used for elements like checkboxes or radio buttons
	
	_getSubElements: function(){
		var element = this.getElement();
		if($type(element) === false){
			return false;
		}
		this.subElements = element.getElements('.fabrikinput');
		return this.subElements;
	},
	
	hasSubElements: function(){
		this._getSubElements();
		if($type(this.subElements) === 'array'){
			return this.subElements.length > 0 ? true : false;
		}
		return false;
	},
	
	addNewEvent: function( action, js ){
		if(action == 'load'){
			eval(js);
		}else{
			if(!this.element){
				this.element = $(this.strElement);
			}
			if(this.element){
				this.element.addEvent( action, function(e){
					eval(js);
					e = new Event(e);
					e.stop();
				} );
				
				this.element.addEvent('blur', function(e){
					this.validate();
				}.bind(this));
			}
		}
	},
	
	validate:function(){},
	
	//store new options created by user in hidden field
	addNewOption: function(val, label)
	{
		var added = $(this.options.element + '_additions').value;
		var json = {'val':val,'label':label};
		if(added !== ''){
			var a = Json.evaluate(added);
		}else{
			a = [];
		}
		a.push(json);
		var s = '[';
		for(var i=0;i<a.length;i++){
			s += Json.toString(a[i]) + ',';
		}
		s = s.substring(0, s.length-1) + ']';
		$(this.options.element + '_additions').value = s;
	},
	
	//below functions can override in plugin element classes
	
	update: function(val){
		if(this.element){
			if (this.options.editable) {
				this.element.value = val;
			}else{
				this.element.innerHTML = val;
			}
		}
	},
	
	getValue: function(){
		if(this.element){
			if (this.options.editable) {
				return this.element.value;
			}else{
				return this.options.defaultVal;
			}
		}
		return false;
	},
	
	reset: function()
	{
		this.update(this.options.defaultVal);
	},
	
	clear:function()
	{
		this.update('');
	},
	
	onsubmit: function(){
		return true;
	},
	
	cloned: function(c){
		//run when the element is cloned in a repeat group
	},
	
	//get the wrapper dom element that contains all of the elements dom objects
	getContainer: function()
	{
		return this.element.findClassUp('fabrikElementContainer');
	},
	
	//get the dom element which shows the error messages
	getErrorElement: function()
	{
		return this.getContainer().getElement('.fabrikErrorMessage');
	},
	
	//get the fx to fade up/down element validation feedback text
	
	getValidationFx: function(){
		if(!this.validationFX){
			this.validationFX = this.getErrorElement().effects({duration:500, wait:true});
		}
		return this.validationFX;
	},
	
	setErrorMessage: function(msg, classname){
		var classes = ['fabrikValidating', 'fabrikError', 'fabrikSuccess'];
		var container = this.getContainer();
		
		classes.each(function(c){
			(classname == c) ? container.addClass(c) : container.removeClass(c);
		});
		this.getErrorElement().setHTML(msg);
		this.getErrorElement().removeClass('fabrikHide');

		var parent = this.form;
		if(classname == 'fabrikError' || classname == 'fabrikSuccess'){
			parent.updateMainError();
		}
		
		var fx = this.getValidationFx();
		switch(classname){
			case 'fabrikValidating':
			case 'fabrikError':
				fx.start({
		 			'opacity':1
		 		});
				break;
			case 'fabrikSuccess':
				fx.start({
			 			'opacity':1
			 		}).chain( function(){
			 		//only fade out if its still the success message
			 			if(container.hasClass('fabrikSuccess')){
			 					container.removeClass('fabrikSuccess');
				 				this.start.delay(700, this, {
									'opacity': 0,
									'onComplete':function(){
										parent.updateMainError();
										classes.each(function(c){
											container.removeClass(c);
										});
									}
								});
							}
			 		});
				break;
		}
	},
	
	setorigId: function()
	{
		if(this.options.repeatCounter > 0){
			var e = this.options.element;
			this.origId = e.substring(0, e.length - 1 - this.options.repeatCounter.toString().length);
		}
	}
});