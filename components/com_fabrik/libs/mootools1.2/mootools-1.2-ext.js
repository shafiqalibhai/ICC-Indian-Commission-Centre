Element.extend({
	
	within: function(p){
		var parenttest = this;
		while(parenttest.parentNode != null){
			if(parenttest == p){
				return true;
			}
			parenttest = parenttest.parentNode;
		}
		return false;
	},
	
	down: function(expression, index) {
	    var descendants = this.getChildren();
		if (arguments.length == 0) return descendants[0];
	    return descendants[index];
    },
	
	up: function(index) {
		index = index ? index : 0;
		var el = this;
		for(i=0;i<=index;i++){
			el = el.getParent();
		}
		return el;
	},
	
	findUp: function(tag){
		if(this.getTag() == tag)
			return this;
		var el = this;
		while(el && el.getTag() != tag){
			el = el.getParent();
		}
		return el;
	},
	
		
	findClassUp: function(classname){
		if(this.hasClass(classname)){
			return this;
		}
		var el = $(this);
		while(el && !el.hasClass(classname)){
			el = el.getParent();
		}
		return el;
	},
	
	toggle: function(){
		if(this.style.display == 'none'){
			this.setStyles({'display':'block'});
		}else{
			this.setStyles({'display':'none'});
		}		
	},
	
	hide: function(){
		this.setStyles({'display':'none'});
	},
	
	show: function(mode){
		this.setStyles({'display':$pick(mode, 'block')});
	},
	
	//x, y = mouse location
	mouseInside: function(x, y){
		var coords = this.getCoordinates();
		var elLeft = coords.left;
		var elRight =  coords.left + coords.width;
		var elTop = coords.top;
		var elBottom = coords.bottom;
		if( x >= elLeft && x <= elRight ){
			if( y >= elTop && y <= elBottom){
				return true;
			}
		}
		return false;
	}
});

/**
 * Misc. functions, nothing to do with Mootools ... we just needed
 * some common js include to put them in!
 */

function fconsole(thing) {
	if (typeof(window["console"]) != "undefined") {
		console.log(thing);
	}
}