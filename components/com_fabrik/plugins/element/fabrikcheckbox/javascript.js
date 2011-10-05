var fbCheckBox = FbElement.extend({

	initialize: function(element, options) {
		this.plugin = 'fabrikcheckbox';
		this.setOptions(element, options);
		this.lang = Object.extend({
			please_enter_value:'Please enter a value and/or label'
		}, arguments[2] || {});
		if(this.options.allowadd === true && this.options.editable !== false){
			var id = this.options.element;
			$( id + '_dd_add_entry').addEvent( 'click', function(event){
				var label = $(id + '_ddLabel').value;
				if($( id + '_ddVal')){
					var val = $( id + '_ddVal').value;
				}else{
					val = label;
				}
				if (val === '' || label === '') {
					alert(this.lang.please_enter_value);
				}
				else {
					var r = this.subElements.getLast().findUp('div').clone();
					r.getElement('input').value = val;
					var lastid = r.getElement('input').id.replace(id + '_', '').toInt();
					lastid++;
					r.getElement('input').checked = 'checked';
					r.getElement('input').id = id + '_' + lastid;
					r.getElement('label').setProperty('for', id + '_' + lastid);
					r.getElement('span').setText(label);
					r.injectAfter(this.subElements.getLast().findUp('div'));
					this._getSubElements();
					var e = new Event(event).stop();
					if ($(id + '_ddVal')) {
						$(id + '_ddVal').value = '';
					}
					$(id + '_ddLabel').value = '';
					this.addNewOption(val, label);
				}
			}.bind(this));
		}
	},
	
	getValue: function(){
		if(!this.options.editable){
			return this.options.defaultVal;
		}
		var ret = [];
		if(!this.options.editable){
			return this.options.defaultVal;
		}
		this._getSubElements().each( function(el){
			if(el.checked){
				ret.push(el.getValue());
			}
		});
		return ret;
	},

	setOptions: function( element ) {
		this.element = $(element);
		var d = [];
		this.options = Object.extend({
			element:       element,
			defaultVal: d
		}, arguments[1] || {});
		this._getSubElements();
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
			this._getSubElements();
			this.subElements.each( function(el){
				el.addEvent( action, function(e){
					eval(js);
				});
			});
		}
	},
	
		//get the sub element which are the checkboxes themselves
	
	_getSubElements: function(){
		if(!this.element){
			this.subElements = $A();
		}else{
			this.subElements = this.element.getElements('input');
		}
		return this.subElements;
	},
	
	//prepare data for ajax validation
	prepereForAjaxPost: function(d)
	{
		if(!this.options.editable){
			return d;
		}
		var v = this.getValue();
		if($type(v) !== false){
			//v is an array
			var element = this.element;
			//element is the container for checkboxes so get first checkbox id, minus the '_0' at the end
			var name = element.getElement('input').name.replace('[]', '');
			if(d.hasKey(name)){ //repeat data test
				var orig = d.get(name);
				if($type(orig) !== 'array'){
					orig = [orig];
				}
				orig.push(v)
				d.set(name, orig.join(''));
				//d.set(name, orig);
			}else{
			//test - implode v and validate that 
				d.set(name, v.join(''));
				//d.set(name, v);
			}
		}
		return d;
	},

	update: function(val){
		if($type(val) == 'string'){
			val = val.split(this.options.splitter);
		}
		if (!this.options.editable) {
			this.element.innerHTML = '';
			if(val === ''){
				return;
			}
			var h = $H(this.options.data);
			val.each(function(v){
				this.element.innerHTML += h.get(v) + "<br />";	
			}.bind(this));
			return;
		}
		this._getSubElements();
		this.subElements.each( function(el){
			var chx = false;
			val.each(function(v){
				if(v == el.value){
					chx = true;
				}
			}.bind(this));
			el.checked = chx;
		}.bind(this));
	}

});