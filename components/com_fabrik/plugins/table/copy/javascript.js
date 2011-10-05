var fbTableCopy = fbTablePlugin.extend({
	initialize: function(tableform, options) {
		this.setOptions(tableform, options);
		window.addEvent('domready', function(){
		this.tableid = this.tableform.getElement('input[name=tableid]').value;
			this.watchButton();
		}.bind(this));
	},
	
	watchButton:function(){
		var button = this.tableform.getElement('input[name=copy]');
		if(!button){
			return;
		}
		button.addEvent('click', function(event){
			var e = new Event(event);
			e.stop();
			var ok = false;
			this.tableform.getElements('input[name^=ids]').each(function(c){
				if(c.checked){
					ok = true;
				}
			});
			if(!ok){
				alert('Please select a row!');
				return;
			}
			this.tableform.getElement('input[name=fabrik_tableplugin_name]').value = 'copy';
			this.tableform.getElement('input[name=fabrik_tableplugin_renderOrder]').value = this.options.renderOrder;
			oPackage.submitfabrikTable(this.tableid,'doPlugin');
		}.bind(this));
	}
	});