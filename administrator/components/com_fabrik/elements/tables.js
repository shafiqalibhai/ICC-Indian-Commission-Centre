var tablesElement = new Class({
	
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
		// $$ hugh - why are we hard coding g=visualization and plugin=chart?
		// I presume it's because we have to specify something in those fields so we'll load the
		// model and have the default FabrikModelPlugin class, which has the ajax_tables method.
		// And because we aren't really a viz/plugin, we picked something at random?
		// Anywyay - this is breaking stuff for people because the chart viz wasn't in the b2 ZIP.
		var url = this.options.livesite + 'index.php?option=com_fabrik&format=raw&&controller=plugin&task=pluginAjax&g=visualization&plugin=chart&method=ajax_tables&cid=' + cid;
		// $$$ hugh - changed this to 'get' method, because some servers barf (Length Required) if
		// we send it a POST with no postbody.
		var myAjax = new Ajax(url, { method:'get', 
			onComplete: function(r){
				var opts = eval(r);
				this.el.empty();
				opts.each( function(opt){
					//var o = {'value':opt.value};//wrong for calendar
					var o = {'value':opt};
					if(opt == this.options.value){
						o.selected = 'selected';
					}
					if($(this.options.conn+'_loader')){
						$(this.options.conn+'_loader').setStyle('display','none');
					}
					new Element('option', o).appendText(opt).injectInside(this.el);
				}.bind(this));
			}.bind(this)
		}).request();
	}
});