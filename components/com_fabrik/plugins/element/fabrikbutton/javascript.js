var fbButton = FbElement.extend({
	initialize: function(element, options) {
		this.plugin = 'fabrikButton';
		this.setOptions(element, options);
	},
	setOptions: function( element ) {
		this.element = $(element);
		var d = [];
		this.options = Object.extend({
			element:      element,
			defaultVal: d
		}, arguments[1] || {});
		this.setorigId();
	},
	// used to assertain the original element id (used on return from ajax validation)
	setorigId: function()
	{
		if(this.options.repeatCounter > 0){
			var e = this.options.element;
			this.origId = e.substring(0, e.length - 1 - this.options.repeatCounter.toString().length);
		}
	}
});