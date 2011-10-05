var fabriktablesElement = new Class({
	
	initialize: function( el ){
		this.el = $(el);
		this.options = Object.extend({
		conn:null
		}, arguments[1] || {});
		this.updateMeEvent = this.updateMe.bindAsEventListener(this);
		$(this.options.conn).addEvent('change', this.updateMeEvent );
		//see if there is a connection selected
		var v = $(this.options.conn).getValue();
		if(v != '' && v != -1){
			this.updateMe();
		}
	},
	
	updateMe: function(e){
		if(e){
			new Event(e).stop();
		}
		if($(this.options.conn+'_loader')){
			$(this.options.conn+'_loader').setStyle('display','inline');
		}
		
		var cid = $(this.options.conn).getValue();
		var url = this.options.livesite + 'index.php?option=com_fabrik&format=raw&&controller=plugin&task=pluginAjax&g=visualization&plugin=chart&method=ajax_tables&showf=1&cid=' + cid;
		var myAjax = new Ajax(url, { method:'get', 
			onComplete: function(r){
				var opts = eval(r);
				this.el.empty();
				opts.each( function(opt){
					//var o = {'value':opt.value};//wrong for calendar
					var o = {'value':opt.id};
					//if(opt.value == this.options.value){
					if(opt.id == this.options.value){
						o.selected = 'selected';
					}
					
					new Element('option', o).appendText(opt.label).injectInside(this.el);
				}.bind(this));
				if($(this.options.conn+'_loader')){
						$(this.options.conn+'_loader').setStyle('display','none');
					}
			}.bind(this)
		}).request();
	}
});