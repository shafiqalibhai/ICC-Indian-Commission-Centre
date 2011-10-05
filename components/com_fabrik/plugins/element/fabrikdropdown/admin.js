var fabrikAdminDropdown = new Class({
	initialize: function() {
		this.options = Object.extend({}, arguments[0] || {});
		this.counter = 0;
		this.clickAddOption = this.addOption.bindAsEventListener(this);
		this.clickRemoveSubElement = this.removeSubElement.bindAsEventListener(this);
		$('addDropDown').addEvent('click', this.clickAddOption);
	},
	
	addOption: function( e ){
		this.addSubElement();
		var event = new Event(e);
		event.stop();
	},
	
	removeSubElement: function(e){
		var event = new Event(e);
		var id = event.target.id.replace('deleteSubElements_', '');
		$('drd_content_' + id).remove();
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
		var selVal = $$('.drd_intial_selection').length + 1;
   	var sCurChecked = sChecked ? "checked='" + sChecked +"'" : '';
		//cant build via dom as ie7 doest accept checked status 
		var chx = "<input class='inputbox drd_intial_selection' type='checkbox'  value='"+selVal+"' name='drd_intial_selection' id='drd_checked_"+this.counter+"' " + sCurChecked + " />";
   	var li = new Element('li', {id: 'drd_content_'+ this.counter}).adopt([
   		new Element('table').adopt([
   			new Element('tbody').adopt([
   				new Element('tr').adopt([
   					new Element('td', {'colspan':4,'class':'handle ddhandle'})
   				]),
   				new Element('tr').adopt([
   					new Element('td', {width:'25%'}).adopt(
		       		 new Element('label', {'for':'drd_value_'+this.counter}).appendText('Value')
		       	),
		       	new Element('td', {width:'25%'}).adopt(
     					new Element('input', {'class':'inputbox drd_values', type:'text', name:'drd_values', id:'drd_value_'+this.counter, size:20, value:sValue})
     				),
     				new Element('td', {width:'25%'}).adopt(
      		 			new Element('label', {'for':'drd_text_'+this.counter}).appendText('Label')
      				),
      				new Element('td', {width:'25%'}).adopt(
     					new Element('input', {'class':'inputbox drd_text', type:'text', name:'drd_text', id:'drd_text_'+this.counter, size:20, value:sText})
     				)
   				]),
		     
		      new Element('tr').adopt(
		       [
		        new Element('td', {width:'20%'}).adopt(
		       		 new Element('label', {'for':'drd_checked_'+this.counter}).appendText('Selected as default:')
		       	),
		       	new Element('td', {width:'80%'}).setHTML(
		      		chx
		      		),
		      		new Element('td', {width:'20%', colspan:'2'}).adopt(
		      		new Element('a', {'class':'removeButton',href:'#', id:'deleteSubElements_'+this.counter}).appendText('Delete')
		      	)
		       ]
		      )
   			])
   		])
   	])
   	li.injectInside($('drd_subElementBody'));
		$('deleteSubElements_'+this.counter).addEvent('click', this.clickRemoveSubElement );
		//@TODO: clone:true - the offset is wrong on dragged clone
		if(this.options.mooversion == 1.2){
			if(!this.sortable){
				this.sortable = new Sortables('drd_subElementBody', {'handle':'.handle'});
			}else{
				this.sortable.addItems(li);
			}
		}else{
			new Sortables('drd_subElementBody', {'handles':$$('.ddhandle')});
			//new Sortables(sPrefix + 'subElementBody', {'handles':$$('.'+sPrefix + '_orderhandle')});
		}
		this.counter++;
	},
	
	onSave:function(){
		var values = ''; 
		var text = ''; 
		var ret = true;
		var intial_selection = '';
		$$('.drd_values').each(function(dd){
			if(dd.value == ''){
				alert("please ensure all sub element values are filled in");
				ret = false;
			}
			values += dd.value.replace('|', '') + '|';
		});
		
		$$('.drd_text').each(function(dd){
			text += dd.value.replace('|', '') + '|';
		});
		var avals = values.split('|');
		$$('.drd_intial_selection').each(function(dd, c){
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