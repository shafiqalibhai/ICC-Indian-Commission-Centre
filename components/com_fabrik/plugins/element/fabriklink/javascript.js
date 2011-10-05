var fbLink = FbElement.extend({

	initialize: function(element, options) {
		this.plugin = 'fabrikLink';
		this.setOptions(element, options);
	},

	setOptions: function( element ) {
		this.element = $(element);
		var d = [];
		this.options = Object.extend({
			element:       element,
			defaultVal: d
		}, arguments[1] || {});
		var ok = true;
		//this.linkField = $(element + '_link');
		//this.subElements = [this.element, this.linkField];
		this.subElements = this._getSubElements();
		this.setorigId();
	},
	
	setorigId: function()
	{
		if(this.options.repeatCounter > 0){
			var e = this.options.element;
			this.origId = e.substring(0, e.length - 1 - this.options.repeatCounter.toString().length);
		}
	},

	addNewEvent: function( action, js ){
		if(action == 'load'){
			eval(js);
		}else{
			this.subElements.each( function(el){
				el.addEvent( action, function(e){
					eval(js);
				});
			});
		}
	},
	//get the sub element which are the fields themselves
	
	_getSubElements: function(){
		if(!this.element){
			this.subElements = $A();
		}else{
			this.subElements = this.element.getElements('input');
		}
		return this.subElements;
	}
});