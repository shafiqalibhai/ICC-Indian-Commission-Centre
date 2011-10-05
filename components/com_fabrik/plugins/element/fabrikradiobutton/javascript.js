var fbRadio = FbElement.extend({

	initialize: function(element, options) {
		
		this.plugin = 'fabrikradiobutton';
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
				if (val === '' && label === '') {
					alert(this.lang.please_enter_value);
				}
				else {
					var r = this.subElements.getLast().findUp('div').clone();
					r.getElement('input').value = val;
					var lastid = r.getElement('input').id.replace(id + '_', '').toInt();
					lastid++;
					r.getElement('input').checked = 'checked';
					r.getElement('input').id = id + '_' + lastid;
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
		var v = '';
		this._getSubElements().each(function(sub){
			if(sub.checked){
				v = sub.getValue();
				return v;
			}
			return null;
		});
		return v;
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
			var element = this.element;
			//element is the container for checkboxes so get first checkbox id, minus the '_0' at the end
			var name = element.getElement('input').name.replace('[]', '');
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
				} );
			});
		}
	},

	update: function(val){
		if (!this.options.editable) {
			if(val === ''){
				this.element.innerHTML = '';
				return;
			}
			this.element.innerHTML =$H(this.options.data).get(val);
			return;
		}
	
	
	}

});