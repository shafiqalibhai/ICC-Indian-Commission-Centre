var fabrikAdminRadiobutton = new Class({
	initialize: function() {
	  this.options = Object.extend({}, arguments[0] || {});
		this.counter = 0;
		this.clickAddOption = this.addOption.bindAsEventListener(this);
		this.clickRemoveSubElement = this.removeSubElement.bindAsEventListener(this);
		$('addRadio').addEvent('click', this.clickAddOption);
	},
	
	addOption: function( e ){
		this.addSubElement();
		var event = new Event(e);
		event.stop();
	},
	
	removeSubElement: function(e){
		var event = new Event(e);
		var id = event.target.id.replace('deleteRadioSubElements_', '');
		$('rad_content_' + id).remove();
		event.stop(e);
	},
	
	addSubElements: function(ar){
		ar.each(function(a){
			this.addSubElement(a[0], a[1], a[2]);
		}.bind(this))
	},
	
	addSubElement: function(sValue, sText, sChecked){
		sValue = sValue ? sValue : '';
		rExp = /\"/gi;
		if(typeof(sValue) == 'string'){
			sValue = sValue.replace(rExp, "&quot;");
		}
		sText = sText ? sText : '';
		if(typeof(sValue) == 'string'){
			sText = sText.replace(rExp, "&quot;");	
		}
   	var sCurChecked = sChecked ? "checked='" + sChecked +"'" : '';
		//cant build via dom as ie7 doest accept checked status 
		var rad = "<input type='radio' " +sCurChecked + " class='inputbox rad_intial_selection' value='" + sValue + "' name='rad_intial_selection' id='rad_checked_"+this.counter + "' />";
   	var li = new Element('li', {id: 'rad_content_'+ this.counter}).adopt([
   		new Element('table').adopt([
   			new Element('tbody').adopt([
   				new Element('tr').adopt([
   					new Element('td', {'colspan':4,'class':'handle'})
   				]),
   				new Element('tr').adopt([
   					new Element('td', {width:'25%'}).adopt(
		       		 new Element('label', {'for':'rad_value_'+this.counter}).appendText('Value')
		       	),
		       	new Element('td', {width:'25%'}).adopt(
     					new Element('input', {'class':'inputbox rad_values', type:'text', name:'rad_values', id:'rad_value_'+this.counter, 'size':20, 'value':sValue})
     				),
     				new Element('td', {width:'25%'}).adopt(
      		 			new Element('label', {'for':'rad_text_'+this.counter}).appendText('Label')
      				),
      				new Element('td', {width:'25%'}).adopt(
     					new Element('input', {'class':'inputbox rad_text', type:'text', name:'rad_text', id:'rad_text_'+this.counter, 'size':20, 'value':sText})
     				)
   				]),
		     
		      new Element('tr').adopt(
		       [
		        new Element('td', {width:'20%'}).adopt(
		       		 new Element('label', {'for':'rad_checked_'+this.counter}).appendText('Selected as default:')
		       	),
		       	new Element('td', {width:'80%'}).setHTML(
		      		rad
		      		),
		      		new Element('td', {width:'20%', colspan:'2'}).adopt(
		      		new Element('a', {'class':'removeButton', href:'#', id:'deleteRadioSubElements_'+this.counter}).appendText('Delete')
		      	)
		       ]
		      )
   			])
   		])
   	])
   	li.injectInside($('rad_subElementBody'));
		$('deleteRadioSubElements_'+this.counter).addEvent('click', this.clickRemoveSubElement );
		
		//@TODO: clone:true - the offset is wrong on dragged clone
		if(this.options.mooversion == 1.2){
			if(!this.sortable){
				this.sortable = new Sortables('rad_subElementBody', {'handle':'.handle'});
			}else{
				this.sortable.addItems(li);
			}
		}else{
			this.sortable = new Sortables('rad_subElementBody', {'handles':$$('.handle')});
		}
		this.counter++;
	},
		
	onSave:function(){
		var values = ''; 
		var text = ''; 
		var ret = true;
		var intial_selection = '';
		$$('.rad_values').each(function(dd){
			if(dd.value == ''){
				alert("please ensure all sub element values are filled in");
				ret = false;
			}
			values += dd.value.replace('|', '') + '|';
		});
		$$('.rad_text').each(function(dd){
			text += dd.value.replace('|', '') + '|';
		});
		var avals = values.split('|');
		$$('.rad_intial_selection').each(function(dd, c){
			if(dd.checked) {
				intial_selection += avals[c] + '|';
			}else{
				intial_selection += '|';
			}
		});
		$('sub_values').value = values.substr(0, values.length-1);
		$('sub_labels').value = text.substr(0, text.length-1); 
		$('sub_intial_selection').value = intial_selection.substr(0, intial_selection.length-1);
		return ret;
	}
});