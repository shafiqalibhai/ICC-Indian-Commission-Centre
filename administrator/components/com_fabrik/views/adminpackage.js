watcher = new Class({
	initialize: function(){
		this.watchButtons();
	},
	
	removeEvents: function(){
		$$('.packageTable .addButton').each(function(add){
			add.removeEvents(); 
		});
		$$('.packageTable .removeButton').each(function(d){
			d.removeEvents(); 
		});		
	},
	
	 watchButtons: function(){
		this.removeEvents();
		$$('.packageTable .addButton').each(function(add){
			add.addEvent('click', function(event){
				var e = new Event(event); 
				var tr = $(e.target.parentNode.parentNode);
				var c = tr.clone()
				c.injectAfter(tr);	
				this.watchButtons();
				e.stop();				
			}.bind(this));	
		}.bind(this));
		
		$$('.packageTable .removeButton').each(function(d){
			d.addEvent('click', function(e){
				var e =new Event(e); 
				if($$('.packageTable .removeButton').length > 1){
					var tr = $(e.target.parentNode.parentNode);
					tr.remove();
				}
				e.stop();
			});	
		});		
	}
});

window.addEvent('domready', function(e){
	new watcher();
});