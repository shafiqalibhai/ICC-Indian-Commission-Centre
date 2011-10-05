/**
 * @author Robert
 */
 
 var fbTablePlugin = new Class({
 	setOptions: function(tableform, options) {
		window.addEvent('domready', function(){
			this.tableform = $(tableform);
		}.bind(this));
		this.options = {};
		Object.extend(this.options, options || {});
	}
 });
 
 
var MochaSearch = new Class({
  initialize: function(){
  	this.active = false;
  },
  
  conf: function(fields, addFirst, addApply){
    this.active = true;
    this.trs = $A([]);
    this.condOpts = [new Element('option', {
      'value': '<>'
    }).appendText('NOT EQUALS'), new Element('option', {
      'value': '='
    }).appendText('EQUALS'), new Element('option', {
      'value': 'like'
    }).appendText('BEGINS WITH'), new Element('option', {
      'value': 'like'
    }).appendText('CONTAINS'), new Element('option', {
      'value': 'like'
    }).appendText('ENDS WITH'), new Element('option', {
      'value': '>'
    }).appendText('GREATER THAN'), new Element('option', {
      'value': '<'
    }).appendText('LESS THAN')];
    this.joinOpts = [new Element('option', {
      'value': 'AND'
    }).appendText('AND'), new Element('option', {
      'value': 'OR'
    }).appendText('OR')];
    this.options = Object.extend({
      'admin': false,
      'txtApply': 'Apply',
      'tableid': 0,
      'txtClear': 'Clear',
      'txtClose': 'Close',
      'headings': ['Join', 'Field', 'Condition', 'Value', 'Active', 'Delete'],
      
      filterCondDd: new Element('select', {
        'name': 'conditions',
        'id': 'conditions',
        'class': 'inputbox',
        'size': '1'
      }).adopt(this.condOpts)
    }, arguments[3] ||
    {});
    
    this.fields = $A([]);
    fields.each(function(f){
      this.fields.push(new Element('option', {
        'value': f.value
      }).appendText(f.text));
    }.bind(this));
    this.counter = 0;
    this.onDeleteClick = this.deleteFilterOption.bindAsEventListener(this);
    this.onAdd = this.addRow.bindAsEventListener(this);
    this.onClear = this.resetForm.bindAsEventListener(this);
    this.onRemoveRow = this.removeRow.bindAsEventListener(this);
    this.onApply = this.applyFilters.bindAsEventListener(this);
    this.addApply = addApply;
    this.setUp(addFirst);
  },
  
  setUp: function(addFirst){
    this.content = new Element('div');
     var apply = new Element('input', {
       'type': 'button',
       'id': 'advanced-search-apply',
       'name': 'applyAdvFabrikFilter',
       'class': 'button fabrikFilter',
       'value': this.options.txtApply
     });
    var cancel = new Element('input', {
      'type': 'button',
      'class': 'button',
      'value': this.options.txtClear,
      'id': 'advancedFilterTable-clearall'
    });
    
    var container = new Element('div', {
      'id': 'advancedSearchContainer'
    });
		
    var table = new Element('table', {
      'id': 'advanced-search-table'
    }).adopt(new Element('tbody').adopt(this.headings()));
    
		if (addFirst) {
      this.addFilterOption();
    }
		
    container.adopt(table);
    var addLink = new Element('a', {
      'href': '#',
      'id': 'advanced-search-add'
    }).appendText('add');
    
		this.content.adopt([addLink, container, apply, cancel]);

  },
  
  applyFilters: function(){
    oPackage.submitfabrikTable(this.options.tableid, 'filter');
  },
  
  headings: function(){
    var tr = new Element('tr', {
      'class': 'title'
    });
    this.options.headings.each(function(h){
      var th = new Element('th').appendText(h);
      tr.appendChild(th);
    });
    return tr;
  },
  
  makeEvents: function(){
    if ($('advanced-search-add')) {
      $('advanced-search-add').addEvent("click", this.onAdd);
      $('advancedFilterTable-clearall').addEvent("click", this.onClear);
      this.trs.each(function(tr){
        tr.injectAfter($('advanced-search-table').getElements('tr').getLast());
      });
    }
    if ($('advanced-search-apply')) {
      $('advanced-search-apply').addEvent("click", this.onApply);
    }
    this.watchDelete();
  },
  
  watchDelete: function(){
    $$('.advanced-search-remove-row').removeEvents();
    $$('.advanced-search-remove-row').addEvent('click', this.onRemoveRow);
  },
  
  addRow: function(e){
  	new Event(e).stop();
    var tr = $('advanced-search-table').getElements('tr').getLast();
    var clone = tr.clone();
    clone.injectAfter(tr);
    this._joinSelect().injectInside(clone.getElement('td').empty());
    this.watchDelete();
  },
  
  removeRow: function(event){
    var e = new Event(event);
    e.stop();
    if ($$('.advanced-search-remove-row').length > 1) {
      var tr = e.target.findUp('tr');
      var fx = new Fx.Styles(tr, {
        duration: 800,
        transition: Fx.Transitions.Quart.easeOut,
        onComplete: function(){
          tr.remove();
        }
      });
      fx.start({
        'height': 0,
        'opacity': 0
      });
    }
  },
  
  resetForm: function(){
    var table = $('advanced-search-table');
    if(!table){
    	return;
    }
    $('advanced-search-table').getElements('tr').each(function(tr, i){
	    if(i > 1){
	    	tr.remove();
	    }
	    if(i == 1){
	    	tr.getElements('.inputbox').each(function(dd){dd.selectedIndex = 0;});
	    	tr.getElement('input').value = '';
	    }
    });
    this.watchDelete();
  },
  
  addFilterOption: function(selJoin, selFilter, selCondition, selValue){
    this.trs.push(this.getFilterOption(selJoin, selFilter, selCondition, selValue));
    this.counter++;
    
  },
  _joinSelect: function(sel){
    if (!this._joinDd) {
      sel = sel ? sel : '';
      var joinOpts = this.joinOpts.copy();
      joinOpts.each(function(opt){
        opt.selected = (opt.value == sel) ? true : false;
      });
      this._joinDd = new Element('select', {
        'name': 'join',
        'id': 'join',
        'class': 'inputbox',
        'size': '1'
      }).adopt(joinOpts);
      return this._joinDd;
    }
    else {
      var clone = this._joinDd.clone();
      var opts = this.joinOpts.copy();
      x = 0;
      opts.each(function(opt, y){
        if (opt.value == sel) {
          x = y;
        }
      });
      clone.selectedIndex = x;
      return clone;
    }
  },
  
  _fieldSelect: function(sel){
    sel = sel ? sel : '';
    if (!this._fieldDd) {
      var x = 0;
      var opts = this.fields.copy();
      opts.each(function(opt, y){
        if(opt.value == sel){
          x = y;
				}
      });
      this._fieldDd = new Element('select', {
        'name': 'search_key',
        'class': 'inputbox',
        'size': '1'
      }).adopt(opts);
      this._fieldDd.selectedIndex = x;
      return this._fieldDd;
    }
    else {
      //have to clone or the ref to actual html element is returned
      var clone = this._fieldDd.clone();
      x = 0;
      this.fields.copy().each(function(opt, y){
        if (opt.value == sel) {
          x = y;
        }
      });
      clone.selectedIndex = x;
      return clone;
    }
  },
  
  _condSelect: function(sel){
    sel = sel ? sel : '';
    var opts = {};
    if (!this._condDd) {
      opts = this.condOpts.copy();
      var x = 0;
      opts.each(function(opt, y){
        if(opt.innerHTML == sel){
          x = y;
         }
      });
      this._condDd = new Element('select', {
        'name': 'search_condition',
        'class': 'inputbox',
        'size': '1'
      }).adopt(opts);
      this._condDd.selectedIndex = x;
      return this._condDd;
    }
    else {
      //have to clone or the ref to actual html element is returned
      var clone = this._condDd.clone();
      opts = this.condOpts.copy();
      x = 0;
      opts.each(function(opt, y){
        if (opt.innerHTML == sel){
          x = y;
         }
      });
      clone.selectedIndex = x;
      return clone;
    }
  },
  
  getFilterOption: function(selJoin, selFilter, selCondition, selValue){
    selJoin = selJoin ? selJoin : '';
    selFilter = selFilter ? selFilter : '';
    selCondition = selCondition ? selCondition : '';
    selValue = selValue ? selValue : '';
    
    var joinDd = (this.counter === 0) ? new Element('span').appendText('WHERE') : this._joinSelect(selJoin);
    
    var chx = (selJoin !== '' || selFilter !== '' || selCondition !== '' || selValue !== '') ? true : false;
    chx = 'checked';
    
    //@TODO: the checked option isnt working
    
    var chkBox = new Element('input', {
      'type': 'checkbox',
      'name': 'active[]',
      'checked': chx,
      'id': 'active_' + this.counter
    });
    chkBox.checked = true;
    var tr = new Element('tr').adopt([new Element('td').adopt(joinDd), new Element('td').adopt(this._fieldSelect(selFilter)), new Element('td').adopt(this._condSelect(selCondition)), new Element('td').adopt(new Element('input', {
      'type': 'field',
      'name': 'value',
      'value': selValue
    })), new Element('td').adopt(chkBox), new Element('td').adopt(new Element('a', {
      'href': '#',
      'class': 'advanced-search-remove-row'
    }).appendText('[-]'))]);
    
    this.counter++;
    return tr;
    
  },
  
  deleteFilterOption: function(e){
    var event = new Event(e);
    element = event.target;
    $(element.id).removeEvent("click", this.onDeleteClick);
    var tr = element.parentNode.parentNode;
    var table = tr.parentNode;
    table.removeChild(tr);
    event.stop();
  },
  
  getSQL: function(){
    if(!this.active){
    	return true;
    }
    if (!$('advanced-search-table')) {
      return;
    }
    var tBody = $('advanced-search-table').getElement('tbody');
    var trs = $('advanced-search-table').getElements('tr').slice(1);
    var str = '';
    ok = true;
    trs.each(function(tr){
      var chbox = tr.getElement('input[name^=active]');
      if (chbox.checked) {
        var tmpstr = '';
        var fType = '';
        var dds = $A(tr.getElementsByTagName('SELECT'));
        ok = true;
        dds.each(function(dd){
          if (dd.name == "search_condition") {
            fType = dd.options[dd.options.selectedIndex].innerHTML;
          }
          var thisstr = $(dd).getValue();
          if (thisstr === '') {
            ok = false;
          }
          else {
            tmpstr = tmpstr + thisstr + ' ';
          }
        });
        if (ok) {
          var field = tr.getElement('input[name=value]');
          if (field.value !== '') {
            var fVal = field.value;
            switch (fType) {
              case 'BEGINS WITH':
                fVal = fVal + "%";
                break;
              case 'CONTAINS':
                fVal = "%" + fVal + "%";
                break;
              case 'ENDS WITH':
                fVal = "%" + fVal;
                break;
              default:
                break;
            }
            str = str + tmpstr + '"' + fVal + '" ';
          }
          else {
          }
        }
      }
    });
    $('advancedFilterContainer').value = str;
    return ok;
  }
});

var mochaSearch = new MochaSearch();

var fabrikTable = new Class({

  initialize: function(id){
    this.id = id;
    this.listenTo = $A([]);
    this.options = Object.extend({
      'admin': false,
			'filterMethod':'onchange',
      'postMethod': 'post',
      'form': 'tableform_' + this.id,
      'hightLight': '#ccffff',
      'emptyMsg': 'No records found',
      'primaryKey': '',
      'headings': [],
      'labels':{},
      'Itemid': 0,
      'formid': 0,
      'canEdit': true,
      'canView': true,
      'page': 'index.php',
      'formels':[], //elements that only appear in the form
      'data': [] //[{col:val, col:val},...]
    }, arguments[1] ||
    {});

   this.translate = Object.extend({
	 	'select_rows':'Select some rows for deletion',
		'confirm_drop':"Do you really want to delete all records and reset this tables key to 0?",
		'yes':'Yes',
		'no':'No'
		}, arguments[2] || {});
		
		window.addEvent('domready', function(){
			this.getForm();
	    this.table = $('table_' + id);
	    if (this.table) {
	      this.tbody = this.table.getElementsByTagName('tbody')[0];
	    }
			this.watchAll();
		}.bind(this));
  },
	
	watchAll: function()
	{
		this.watchNav();
		this.watchRows();
		this.watchFilters();
		this.watchOrder();
		this.watchEmpty();
		this.watchButtons();
	},
	
	watchButtons: function()
	{
			//cant build via dom as ie7 doest accept checked status 
		var rad = "<input type='radio' value='1' name='incfilters' checked='checked' />" + this.translate.yes;
		var rad2 = "<input type='radio' value='1' name='incraw' checked='checked' />" + this.translate.yes;
		var rad3 = "<input type='radio' value='1' name='inccalcs' checked='checked' />" + this.translate.yes;
	
	
		var url = 'index.php?option=com_fabrik&view=table&tableid='+this.id+'&format=csv';

		var divopts = {'styles':{'width':'125px','float':'left'}};
		var c = new Element('form', {'action':url, 'method':'post'}).adopt([
			
			new Element('div', divopts).appendText('Include filters:'),
			new Element('label').setHTML(rad),
			new Element('label').adopt([
				new Element('input', {'type':'radio','name':'incfilters','value':'0'}), 
				new Element('span').appendText(this.translate.no)
			]),
			new Element('br'),
			new Element('div', divopts).appendText('Include raw data:'),
			new Element('label').setHTML(rad2),
			new Element('label').adopt([
				new Element('input', {'type':'radio','name':'incraw','value':'0'}), 
				new Element('span').appendText(this.translate.no)
			]),
			new Element('br'),
			new Element('div', divopts).appendText('Include calculations:'),
			new Element('label').setHTML(rad3),
			new Element('label').adopt([
				new Element('input', {'type':'radio','name':'inccalcs','value':'0'}), 
				new Element('span').appendText(this.translate.no)
			])
			]);
			new Element('h4').appendText('Select the columns to export').injectInside(c);
			for(var i in this.options.labels){
				if(i.substr(0, 7) != 'fabrik_'){
				  var rad = "<input type='radio' value='1' name='fields["+i+"]' checked='checked' />" + this.translate.yes;
				  var label =  this.options.labels[i].replace(/<\/?[^>]+(>|$)/g, "");
					var r = new Element('div', divopts).appendText(label);
					r.injectInside(c);
					new Element('label').setHTML(rad).injectInside(c);
					new Element('label').adopt([
					new Element('input', {'type':'radio','name':'fields['+i+']','value':'0'}), 
					new Element('span').appendText(this.translate.no)
					]).injectInside(c);
					new Element('br').injectInside(c);
				}
			}
			
			//elements not shown in table
			if(this.options.formels.length > 0){ 
				new Element('h5').setText('Form fields').injectInside(c);
				this.options.formels.each(function(el){
					var rad = "<input type='radio' value='1' name='fields["+el.name+"]' checked='checked' />" + this.translate.yes;
					var r = new Element('div', divopts).appendText(el.label);
					r.injectInside(c);
					new Element('label').setHTML(rad).injectInside(c);
						new Element('label').adopt([
						new Element('input', {'type':'radio','name':'fields['+el.name+']','value':'0'}), 
						new Element('span').appendText(this.translate.no)
						]).injectInside(c);
						new Element('br').injectInside(c);	
				}.bind(this));
			}
			//calculation fields
			/*var calkeys = [['Sums','sums'], ['Averages','avgs'], ['Medians','medians'], ['Count','count']];
			calkeys.each(function(calkey){
				if(this.options.calcs[calkey[1]].length > 0){ 
					new Element('h5').setText(calkey[0]).injectInside(c);
					this.options.calcs[calkey[1]].each(function(el){
						var rad = "<input type='radio' value='1' name='fields["+calkey[1]+'___'+el.elLabel+"]' checked='checked' />" + this.translate.yes;
						var r = new Element('div', divopts).appendText(el.elLabel);
						r.injectInside(c);
						new Element('label').setHTML(rad).injectInside(c);
							new Element('label').adopt([
							new Element('input', {'type':'radio','name':'fields['+calkey[1]+'___'+el.elLabel+']','value':'0'}), 
							new Element('span').appendText(this.translate.no)
							]).injectInside(c);
							new Element('br').injectInside(c);	
					}.bind(this));
				}
			}.bind(this));*/
			
			
			new Element('div', {'styles':{'text-align':'right'}}).adopt(
				new Element('input', {'type':'submit','name':'submit','value':'Export', 'class':'button'})
			).injectInside(c);
			new Element('input', {'type':'hidden','name':'view','value':'table'}).injectInside(c);
			new Element('input', {'type':'hidden','name':'option','value':'com_fabrik'}).injectInside(c);
			new Element('input', {'type':'hidden','name':'tableid','value':this.id}).injectInside(c);
			new Element('input', {'type':'hidden','name':'format','value':'csv'}).injectInside(c);
			new Element('input', {'type':'hidden','name':'c','value':'table'}).injectInside(c);
			this.options.headings
		this.exportWindowOpts = {
			id: 'exportcsv',
			title: 'Export CSV',
			loadMethod:'html',
			minimizable:false,
			width: 320,
			height: 120,
			content:c			
		};
		
	
		if(this.form.getElements('.csvExportButton')){
			this.form.getElements('.csvExportButton').each(function(b){
				b.addEvent('click', function(e){
					e = new Event(e).stop();
					thisc = c.clone();
					this.form.getElements('.fabrik_filter').each(function(f){
						var fc = f.clone();
						fc.setStyle('display', 'none');
						fc.injectInside(thisc);
					}.bind(this));
					this.exportWindowOpts.content = thisc;
					if(this.options.mooversion > 1.1){
						var win = new MochaUI.Window(this.exportWindowOpts);
					}else{
						document.mochaDesktop.newWindow(this.exportWindowOpts);
					}
				}.bind(this));
			}.bind(this));
		}
	},
	
	addPlugins:function(a){
		this.plugins = a;
	},
	
	watchEmpty: function(e){
		var b = $E('input[name=doempty]', this.options.form);
		if (b) {
			b.addEvent('click', function(e){
				var event = new Event(e).stop();
				if( confirm(this.translate.confirm_drop)){
					oPackage.submitfabrikTable(this.id,'doempty');
				}
			}.bind(this));
		}
	},
	
	watchOrder: function(){
		var hs = $(this.options.form).getElementsBySelector('.fabrikorder, .fabrikorder-asc, .fabrikorder-desc');
		hs.addEvent('click', function(event){
			var e = new Event(event);
			var orderdir = '';
			switch($(e.target).className){
				case 'fabrikorder-asc':
					orderdir = 'desc';
					break;
				case 'fabrikorder-desc':
					orderdir = "-";
					break;
				case 'fabrikorder':
					orderdir = 'asc';
					break;
			}
			var td = $(e.target).getParent().className.replace('_heading', '');
			td = this.options.orderMap[td];
			oPackage.fabrikNavOrder(this.id, td, orderdir);
			e.stop();
		}.bind(this));
	},
	
	watchFilters: function(){
		var e = '';
		if (this.options.filterMethod != 'submitform') {
			$(this.options.form).getElements('.fabrik_filter').each(function(f){
				if(f.getTag() == 'select') {
					e = 'change';
				}else{
					e = 'blur';
				}
				//f.removeEvents();
				f.addEvent(e, function(){
					oPackage.submitfabrikTable(this.id, 'filter');
				}.bind(this));
			}.bind(this));
		}else{
			var f = $(this.options.form).getElement('.fabrik_filter_submit');
			if (f) {
				f.removeEvents();
				f.addEvent('click', function(e){
					if (this.options.postMethod == 'post') {
						$(this.options.form).submit();
					}
					else {
						oPackage.submitfabrikTable(this.id, 'filter');
					}
				}.bind(this));
			}
		}
	},
  
  //highlight active row, deselect others 
  setActive: function(activeTr){
    $A(this.table.getElementsByClassName('fabrik_row')).each(function(tr){
      tr.removeClass('activeRow');
    });
    activeTr.addClass('activeRow');
  },
  
  watchRows: function(){
    if(!this.table){
			return;
		}
		this.rows = $ES('.fabrik_row', this.table);
		
		this.links = this.table.getElements('.fabrik___rowlink');
    if (this.options.postMethod != 'post') {
      var view = '';
      if (this.options.canEdit == 1) {
        view = 'form';
      }
      else {
        if (this.options.canView == 1) {
          view = 'details';
        }
      }
      
      var editopts = {
        option: 'com_fabrik',
        'Itemid': this.options.Itemid,
        'view': view,
        'tableid': this.id,
        'fabrik': this.options.formid,
        'rowid': 0,
        'format': 'raw',
        '_senderBlock': 'table_' + this.id
      };
      this.links.each(function(link){
        link.addEvent('click', function(e){
          var tr = link.findUp('tr');
					this.setActive(tr);
          oPackage.startLoading();
          editopts.rowid = tr.id.replace('table_' + this.id + '_row_', '');
          var url = "index.php?" + Object.toQueryString(editopts);
          var myAjax = new Ajax(url, {
            method: 'get',
            onComplete: function(res){
              oPackage.sendMessage('table_' + this.id, 'update', 'ok', res);
            }.bind(this)
          });
          myAjax.request();
          e = new Event(e);
          e.stop();
        }.bind(this));
      }.bind(this));
    }
    
    //view details 
    
    this.links = this.table.getElements('.fabrik___viewrowlink');
    if (this.options.postMethod != 'post') {
      view =  'details';
      opts = {
        option: 'com_fabrik',
        'Itemid': this.options.Itemid,
        'view': view,
        'tableid': this.id,
        'fabrik': this.options.formid,
        'rowid': 0,
        'format': 'raw',
        '_senderBlock': 'table_' + this.id
      };
      this.links.each(function(link){
        link.addEvent('click', function(e){
          var tr = link.findUp('tr');
					this.setActive(tr);
          oPackage.startLoading();
          opts.rowid = tr.id.replace('table_' + this.id + '_row_', '');
          var url = "index.php?" + Object.toQueryString(opts);
          var myAjax = new Ajax(url, {
            method: 'get',
            onComplete: function(res){
              oPackage.sendMessage('table_' + this.id, 'update', 'ok', res);
            }.bind(this)
          });
          myAjax.request();
          e = new Event(e);
          e.stop();
        }.bind(this));
      }.bind(this));
    }
  },
  
  getForm: function(){
		if (!this.form) {
			this.form = $(this.options.form);
		}
  },
  
  submitfabrikTable: function(task){
    this.getForm();
		if (task == 'delete') {
			var ok = false;
			this.form.getElements('input[name^=ids]').each(function(c){
				if(c.checked){
					ok = true;
				}
			});
			if(!ok){
				alert(this.translate.select_rows);
				return;
			}
			
		}
		if(task == 'resetFilters'){
			var filters = this.form.getElements('.fabrik_filter');
			filters.each(function(f){
				if(f.getTag() == 'select'){
					f.selectedIndex = 0;
				}else{
					f.value = '';
				}
			});
			task = 'filter';
			mochaSearch.resetForm();
		}
    if (task == 'filter') {
      if(!mochaSearch.getSQL() && this.form.getElements('.fabrik_filter').length === 0){
      	return false;
      }
      if (this.form.limitstart) {
        this.form.limitstart.value = 0;
      }
    }
    else {
      if (task !== '') {
        this.form.task.value = task;
      }
    }
    if (this.options.postMethod == 'ajax') {
      $('table_' + this.id + '_format').value = 'raw';
      oPackage.startLoading();
      this.form.send({
        onComplete: function(json){
          oPackage.sendMessage('table_' + this.id, 'updateRows', 'ok', json);
        }.bind(this)
      });
    }
    else {
      this.form.submit();
    }
    return false;
  },
  
  fabrikNav: function(limitStart){
    this.form.limitstart.value = limitStart;
    this.submitfabrikTable('');
    return false;
  },
  
  fabrikNavOrder: function(orderby, orderdir){
	this.form.orderby.value = orderby;
    this.form.orderdir.value = orderdir;
    this.submitfabrikTable('viewTable');
    return false;
  },
  
  removeRows: function(rowids){
    //@TODO: try to do this with FX.Elements 
    for (i = 0; i < rowids.length; i++) {
      var row = $('table_' + this.id + '_row_' + rowids[i]);
      var highlight = new Fx.Styles(row, {
        duration: 1000
      });
      highlight.start({
        'backgroundColor': this.options.hightLight
      }).chain(function(){
        this.start({
          'opacity': 0
        });
      }).chain(function(){
        row.remove();
        this.checkEmpty();
      }.bind(this));
    }
  },
  
  editRow: function(){
  
  },
  
  clearRows: function(){
    this.rows.each(function(tr){
      tr.remove();
    });
  },
  
	
  updateRows: function(data){
		if (data.id == this.id && data.model == 'table') {
			var header = this.table.getElement('.fabrik___heading');
			var headings = new Hash(data.headings);
			headings.each(function(data, key){
				key = "." + key;
				if (header.getElement(key)) {
					header.getElement(key).setHTML(data);
				}
			});
			var rowtemplate = this.table.getElement('.fabrik_row');
			this.clearRows();
			var counter = 0;
			data = data.data;
			data.each(function(groupData, groupKey){
				groupData = new Hash(groupData);
        groupData.each(function(row, rowkey){
          var thisrowtemplate = rowtemplate.clone();
					row = new Hash(row);
					row.each(function(val, key){
            var rowk = '.fabrik_row___' + key;
            var cell = thisrowtemplate.getElement(rowk);
            if (cell) {
							cell.setHTML(val);
            }
          }.bind(this));
					thisrowtemplate.id = 'table_' + this.id + '_row_' + row.get('__pk_val');
					thisrowtemplate.injectInside(this.tbody);
					counter ++;
        }.bind(this));
      }.bind(this));
      this.watchAll();
		}
  },
  
  addRow: function(obj){
    /*var a = 0;
     for(var i in obj){
     a = a+ 1;
     }
     var c =  parseInt(this.options.headings.length) / parseInt(a);*/
    var r = new Element('tr', {
      'class': 'oddRow1'
    });
    var x = {
      test: 'hi'
    };
    for (var i in obj) {
      if (this.options.headings.indexOf(i) != -1) {
        var td = new Element('td', {}).appendText(obj[i]);
        //td.colSpan = c;
        r.appendChild(td);
      }
    }
    r.injectInside(this.tbody);
  },
  
  addRows: function(aData){
    for (i = 0; i < aData.length; i++) {
      for (j = 0; j < aData[i].length; j++) {
        this.addRow(aData[i][j]);
      }
    }
    this.stripe();
  },
  
  stripe: function(){
    var trs = $ES('.fabrik_row', this.table);
    for (i = 0; i < trs.length; i++) {
      if (i !== 0) { //ignore heading
        var row = 'oddRow' + (i % 2);
        trs[i].addClass(row);
      }
    }
  },
  
  checkEmpty: function(){
    var trs = $ES('tr', this.table);
    if (trs.length == 2) {
      this.addRow({
        'label': this.options.emptyMsg
      });
    }
  },
  
  watchCheckAll: function(e){
    var checkAll = $('table_' + this.id + '_checkAll');
    if (checkAll) {
      checkAll.addEvent('change', function(e){
        var chkBoxes = $(this.options.form).getElements('input[name^=ids]'); //document.getElementsByName('ids[]');
				var c = !checkAll.checked ? '' : 'checked';
        for (var i = 0; i < chkBoxes.length; i++) {
          chkBoxes[i].checked = c;
        }
        var event = new Event(e);
        event.stop();
      }.bind(this));
    }
  },
  
  watchNav: function(e){
		var limitBox = this.form.getElement('select[name=limit]');
    if (limitBox) {
      limitBox.addEvent('change', function(e){
        oPackage.submitfabrikTable(this.id, '');
      }.bind(this));
    }
    var addRecord = $('table_' + this.id + '_addRecord');
    
    if ($(addRecord) && (this.options.postMethod != 'post')) {
      addRecord.addEvent('click', function(e){
        e = new Event(e);
        oPackage.startLoading();
        oPackage.sendMessage('table_' + this.id, 'clearForm', 'ok', '');
        e.stop();
      }.bind(this));
    }
		
		if($('fabrik__swaptable')){
			$('fabrik__swaptable').addEvent('change', function(event){
				var e = new Event(event);
				var v = e.target;
				window.location = 'index.php?option=com_fabrik&c=table&task=viewTable&cid=' + v.getValue();
			}.bind(this));
		}
    this.watchCheckAll();
		
		//clear filter list
		var c = this.form.getElement('.clearFilters');
		if(c){
			c.addEvent('click', function(e){
				oPackage.submitfabrikTable(this.id, 'resetFilters');
			}.bind(this));
		}
  },
  
  //todo: refractor addlistento into block class 
  addListenTo: function(blockId){
    this.listenTo.push(blockId);
  },
  
  receiveMessage: function(senderBlock, task, taskStatus, data){
    if (this.listenTo.indexOf(senderBlock) != -1) {
      switch (task) {
        case 'delete':
          this.removeRows(data);
          this.stripe();
          break;
        case 'processForm':
          this.addRows(data);
          break;
        case 'updateRows':
        	//only update rows if no errors returned
        	if ($H(data.errors).keys().length === 0){
          	this.updateRows(data);
          }
          break;
      }
    }
  }
});
