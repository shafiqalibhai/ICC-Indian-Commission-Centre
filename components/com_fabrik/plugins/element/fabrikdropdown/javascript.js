var fbDropdown = FbElement.extend({
	initialize: function(element, options) {
		this.plugin = 'fabrikdropdown';
		this.setOptions(element, options);
		this.lang = Object.extend({
			please_enter_value:'Please enter a value and/or label'
		}, arguments[2] || {});
		
		if(this.options.allowadd === true && this.options.editable !== false){
			var id = this.element.id;
			$( this.element.id + '_dd_add_entry').addEvent( 'click', function(event){
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
					var opt = new Element('option', {
						'selected':'selected',
						'value': val
					}).appendText(label).injectInside($(this.element.id));
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
	
	getValue:function(){
		if(!this.options.editable){
			return this.options.defaultVal;
		}
		if($type(this.element.getValue()) === false){
			return '';
		}
		return this.element.getValue();
	},
	
	reset: function()
	{
		var v = this.options.defaultVal.join(this.options.splitter);
		this.update(v);
	},
	
	update: function(val){
		if($type(val) == 'string'){
			val = val.split(this.options.splitter);
		}
		if($type(val) == false){
			val = [];
		}
		var activevals = val;
		if (!this.options.editable) {
			this.element.innerHTML = '';
			if(val.length == 0){
				return;
			}
			var h = $H(this.options.data);
			val.each(function(v){
				this.element.innerHTML += h.get(v) + "<br />";	
			}.bind(this));
			return;
		}else{
			this.element.empty();
			var d = $H(this.options.data);
			d.each(function(val, key){
				var sel = false;
				if(activevals){
					activevals.each(function(v){
						if(v == key){
							sel = true;
						}
					});
				}
				var o = new Element('option', {'value':key, 'selected':sel}).appendText(val);
				this.element.adopt(o);
			}.bind(this));
	
			var r = this.baseName + '_' + val;
			if ($(r)) {
				r.checked = 'checked';
			}
		}
	}
});
	
