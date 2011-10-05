/**
 * @author Robert
 */

function CloneObject(what) {
for ( var i in what) {
	this[i] = what[i];
}
}

var fabrikForm = new Class( {

	initialize : function(id) {
		this.id = id;
		this.options = Object.extend( {
			'admin' :false,
			'postMethod' :'post',
			'primaryKey' :null,
			'error' :'',
			'delayedEvents' :false,
			'updatedMsg' :'Form saved',
			'liveSite' :'',
			'pages' : [],
			'page_save_groups' : [],
			'start_page' :0,
			'ajaxValidation' :false,
			'customJsAction' :'',
			'inPopup' :false,
			'formCss' : [],
			'plugins' : [],
			'ajaxmethod' :'post',
			'mooversion' :1.1
		}, arguments[1] || {});
	
		this.subGroups = $H( {});
		this.lang = Object.extend( {
			'validation_altered_content' :'The validation has altered your content:',
			'validating' :'Validating',
			'success' :'Success'
		}, arguments[2] || {});
		this.currentPage = this.options.start_page;
		this.formElements = $H( {});
		this.delGroupJS = $H( {});
		this.duplicateGroupJS = $H( {});
		this.listenTo = $A( []);
		this.bufferedEvents = $A( []);
		this.duplicatedGroups = $H();
		this.clickDeleteGroup = this.deleteGroup.bindAsEventListener(this);
		this.clickDuplicateGroup = this.duplicateGroup.bindAsEventListener(this);
	
		window.addEvent('domready', function() {
		this.setUp();
		this.winScroller = new Fx.Scroll(window);
		}.bind(this));
		this.fx = {};
		this.fx.elements = [];
		this.fx.validations = {};
		if (!window.ie6) {
			// only attempt this if you are in a popup form.
			if (this.options.inPopup) {
				this.includeFormCss();
			}
			this.includeCustomJsAction();
		}
		window.addEvent('domready', function() {
		this.watchAddOptions();
		}.bind(this));
	},

	watchAddOptions : function() {
		this.fx.addOptions = [];
		this.getForm().getElements('.addoption').each( function(d) {
		var a = d.getParent().getElement('.toggle-addoption');
		var mySlider = new Fx.Slide(d, {
			duration :500
		});
		mySlider.hide();
		a.addEvent('click', function(e) {
		new Event(e).stop();
		mySlider.toggle();
		});
		});
	},

	// Barbara
	includeCustomJsAction : function() {
		// remove element if present from manual closure of mocha window
		this.removeCustomJsAction();
		// inser new elt
		if (this.options.customJsAction != '') {
			// inject js into head
			this.customJsElement = new Element('script', {
				'type' :'text/javascript',
				'src' :this.options.customJsAction,
				'id' :'customJsAction'
			});
			this.customJsElement.injectInside(document.head);
		}
	},

	// Barbara
	removeCustomJsAction : function() {
		var elt = $E('script#customJsAction');
		if ($defined(elt)) {
			elt.remove();
		}
	},

	// Barbara
	includeFormCss : function() {
		// remove element if present from manual closure of mocha window
		this.removeFormCss();
		// insert new elt
		for ( var c = 0; c < this.options.formCss.length; c++) {
			// inject css into head
			var elt = new Element('link', {
				'type' :'text/css',
				'rel' :'stylesheet',
				'href' :this.options.formCss[c],
				'id' :'formCss' + c
			});
			elt.injectInside(document.head);
		}
	},

	// Barbara
	removeFormCss : function() {
		for ( var c = 0; c < 2; c++) {
			// 0 to 2 form specific css files
			var elt = $E('link#formCss' + c);
			if ($defined(elt)) {
				elt.remove();
			}
		}
	},

	setUp : function() {
		this.form = this.getForm();
		this.watchGroupButtons();
		if (this.options.editable) {
			this.watchSubmit();
		}
		this.createPages();
		this.watchClearSession();
	},

	getForm : function() {
		this.form = this.options.editable == true ? $('form_' + this.id)
				: $('details_' + this.id);
		return this.form;
	},

	// id is the element or group to apply the fx TO, triggered from another
	// element
	addElementFX : function(id) {
		id = id.replace('fabrik_trigger_', '');
		if (id.slice(0, 6) == 'group_') {
			id = id.slice(6, id.length);
			var k = id;
			var c = $(id);
		} else {
			id = id.slice(8, id.length);
			k = 'element' + id;
			if (!$(id)) {
				return;
			}
			c = $(id).findClassUp('fabrikElementContainer');
		}
		if (c) {
			// c will be the <li> element - you can't apply fx's to this as it makes the
			// DOM squiffy with
			// multi column rows, so get the li's content and put it inside a div which
			// is injected into c
			// apply fx to div rather than li - damn im good
			if ((c).getTag() == 'li') {
				var fxdiv = new Element('div').adopt(c.getChildren());
				c.empty();
				fxdiv.injectInside(c);
			} else {
				fxdiv = c;
			}
	
			var opts = {
				duration :800,
				transition :Fx.Transitions.Sine.easeInOut
			};
			this.fx.elements[k] = {};
			this.fx.elements[k].css = fxdiv.effect('opacity', opts);
			if ($type(fxdiv) != false) {
				this.fx.elements[k]['slide'] = new Fx.Slide(fxdiv, opts);
			} else {
				this.fx.elements[k]['slide'] = null;
			}
		}
	},

	doElementFX : function(id, method) {
		id = id.replace('fabrik_trigger_', '');
		if (id.slice(0, 6) == 'group_') {
			id = id.slice(6, id.length);
			var k = id;
		} else {
			id = id.slice(8, id.length);
			k = 'element' + id;
		}
		var fx = this.fx.elements[k];
		if (!fx) {
			return;
		}
		switch (method) {
		case 'show':
			fx.css.set(1);
			fx.css.element.show();
			break;
		case 'hide':
			fx.css.set(0);
			fx.css.element.hide();
			break;
		case 'fadein':
			if (fx.css.lastMethod !== 'fadein') {
				fx.css.element.show();
				fx.css.start(0, 1);
			}
			break;
		case 'fadeout':
			if (fx.css.lastMethod !== 'fadeout') {
				fx.css.start(1, 0).chain( function() {
				fx.css.element.hide();
				});
			}
			break;
		case 'slide in':
			fx.slide.slideIn();
			break;
		case 'slide out':
			fx.slide.slideOut();
			break;
		case 'slide toggle':
			fx.slide.toggle();
			break;
		}
		fx.lastMethod = method;
		this.runPlugins('onDoElementFX');
	},

	watchClearSession : function() {
		if (this.form && this.form.getElement('.clearSession')) {
			this.form.getElement('input[name=task]').value = 'removeSession';
			this.form.getElement('.clearSession').addEvent('click', function(e) {
				this.clearForm();
				this.form.submit();
			}.bind(this));
		}
	},

	createPages : function() {
		if (this.options.pages.length > 1) {
			this.options.pageDisplay = this._getGroupDisplayStyle();
			if ($('fabrikSubmit' + this.id)) {
				$('fabrikSubmit' + this.id).disabled = "disabled";
			}
			this.form.getElement('.fabrikPagePrevious').disabled = "disabled";
			this.form.getElement('.fabrikPageNext').addEvent('click',
					this._doPageNav.bindAsEventListener(this, [ 1 ]));
			this.form.getElement('.fabrikPagePrevious').addEvent('click',
					this._doPageNav.bindAsEventListener(this, [ -1 ]));
			this.setPageButtons();
		}
	},

	_getGroupDisplayStyle : function() {
		var dis = 'block';
		this.options.pages.each( function(pages) {
		if ($type(pages) == 'array') {
			for ( var i = 0; i < pages.length; i++) {
				s = $('group' + pages[i]).getStyle('display');
				if (s !== 'none')
					dis = s;
			}
		} else {
			s = $('group' + pages).getStyle('display');
			if (s !== 'none')
				dis = s;
		}
		});
		return dis;
	},

	_doPageNav : function(e, dir) {
		var url = this.options.liveSite
				+ 'index.php?option=com_fabrik&controller=form&format=raw&task=ajax_validate&form_id='
				+ this.id;
		oPackage.startLoading('form_' + this.id, 'validating');
	
		var d = $H( {});
		// only validate the current groups elements
		var groupId = this.options.pages[this.currentPage.toInt()];
		this.formElements.each( function(el, key) {
		if (el.element) {
			var group = el.element.findClassUp('fabrikGroup');
			if (groupId && group.id == 'group' + groupId) {
				d = el.prepereForAjaxPost(d);
			}
		}
		}.bind(this));
		d = this._prepareRepeatsForAjax(d);
	
		var myAjax = new Ajax(url, {
			method :this.options.ajaxmethod,
			data :d,
			onComplete : function(r) {
			oPackage.stopLoading();
			r = Json.evaluate(r);
			if (!this._showGroupError(r, d)) {
				this.changePage(dir);
				this.saveGroupsToDb();
			}
			}.bind(this)
		}).request();
	
		var event = new Event(e).stop();
	},

	getPageElements : function() {
		var d = new Hash();
		// get elements to validate
		this.options.pages[this.currentPage].each( function(id) {
		var g = $('group' + id);
		this.formElements.each( function(el, k) {
		if (g.getElement('#' + k)) {
			var element = g.getElement('#' + k);
			d.set(k, element.getValue());
		}
		}.bind(this));
		}.bind(this));
		return d;
		},
	
		saveGroupsToDb : function() {
		this.runPlugins('saveGroupsToDb');
		var orig = this.form.getElement('input[name=format]').value;
		var origprocess = this.form.getElement('input[name=task]').value;
		this.form.getElement('input[name=format]').value = 'raw';
		this.form.getElement('input[name=task]').value = 'savepage';
	
		var url = this.options.liveSite
				+ 'index.php?option=com_fabrik&format=raw&page=' + this.currentPage;
		oPackage.startLoading('form_' + this.id, 'saving page');
		var a = new Ajax(url, {
			method :this.options.ajaxmethod,
			data :this.form.toQueryString(),
			onComplete : function(r) {
			this.runPlugins('onCompleteSaveGroupsToDb');
			this.form.getElement('input[name=format]').value = orig;
			this.form.getElement('input[name=task]').value = origprocess;
			if (this.options.postMethod == 'ajax') {
				oPackage.sendMessage('form_' + this.id, 'updateRows', 'ok', json);
			}
			oPackage.stopLoading();
			}.bind(this)
		}).request();
		},
	
		changePage : function(dir) {
		// hide all error messages
		this.runPlugins('onChangePage');
		this.currentPage = this.currentPage.toInt();
		this.form.getElements('.fabrikError').addClass('fabrikHide');
		if (this.currentPage + dir >= 0
				&& this.currentPage + dir < this.options.pages.length) {
			this.currentPage += dir;
		}
		this.setPageButtons();
		this.options.pages.each( function(gids) {
		gids.each( function(id) {
		$('group' + id).hide();
		});
		});
		this.options.pages[this.currentPage].each( function(id) {
		$('group' + id).show(this.options.pageDisplay);
		}.bind(this));
	},

	setPageButtons : function() {
		if (this.currentPage == this.options.pages.length - 1) {
			if ($('fabrikSubmit' + this.id))
				$('fabrikSubmit' + this.id).disabled = "";
			this.form.getElement('.fabrikPageNext').disabled = "disabled";
			this.form.getElement('.fabrikPageNext').setStyle('opacity', 0.5);
		} else {
			this.form.getElement('.fabrikPageNext').disabled = "";
			this.form.getElement('.fabrikPageNext').setStyle('opacity', 1);
		}
		if (this.currentPage === 0) {
			this.form.getElement('.fabrikPagePrevious').disabled = "disabled";
			this.form.getElement('.fabrikPagePrevious').setStyle('opacity', 0.5);
		} else {
			this.form.getElement('.fabrikPagePrevious').disabled = "";
			this.form.getElement('.fabrikPagePrevious').setStyle('opacity', 1);
		}
	},

	addElements : function(a) {
		for ( var i = 0; i < a.length; i++) {
			if ($type(a[i]) !== false) {
				this.addElement(a[i], a[i].options.element);
			}
		}
	},

	addElement : function(oEl, elId) {
		elId = elId.replace('[]', '');
		oEl.form = this;
		this.formElements.set(elId, oEl);
		},
	
		// we have to buffer the events in a pop up window as
		// the dom inserted when the window loads appears after the ajax evalscripts
	
	dispatchEvent : function(elementType, elementId, action, js) {
		if (!this.options.delayedEvents) {
			var el = this.formElements.get(elementId);
			if (el && js != '') {
				// el.storeEvent(action, js);
				el.addNewEvent(action, js);
			}
		} else {
			this.bufferEvent(elementType, elementId, action, js);
		}
	},

	bufferEvent : function(elementType, elementId, action, js) {
		this.bufferedEvents.push( [ elementType, elementId, action, js ]);
	},
	
		// call this after the popup window has loaded
		processBufferEvents : function() {
		this.setUp();
		this.options.delayedEvents = false;
		this.bufferedEvents.each( function(r) {
		// refresh the element ref
				var elementId = r[1];
				var el = this.formElements.get(elementId);
				el.element = $(elementId);
				this.dispatchEvent(r[0], elementId, r[2], r[3]);
				}.bind(this));
		},
	
		action : function(task, element) {
		var oEl = this.formElements.find( function(oEl) {
		return (oEl.element.id == element);
		});
		eval('oEl.' + task + '()');
	},

	/**
	 * @param string
	 *          element id to observe
	 * @param string
	 *          error div for element
	 * @param string
	 *          parent element id - eg for datetime's time field this is the date
	 *          fields id
	 */
	watchValidation : function(id, triggerEvent) {
		if (this.options.ajaxValidation == false) {
			return;
		}
		if ($(id).className == 'fabrikSubElementContainer') {
			// check for things like radio buttons & checkboxes
			$(id).getElements('.fabrikinput').each(
					function(i) {
					i.addEvent(triggerEvent, this.doElementValidation.bindAsEventListener(this));
					}.bind(this));
			return;
		}
		$(id).addEvent(triggerEvent, this.doElementValidation.bindAsEventListener(this));
	},

	// as well as being called from watchValidation can be called from other
	// element js actions, e.g. date picker closing
	doElementValidation : function(event) {
		
	if ($type(event) == 'event' || $type(event) == 'object') { // type object in
																																// mootools1.1
			var e = new Event(event);
			var id = e.target.id;
		} else {
			// hack for closing date picker where it seems the event object isnt
			// available
			id = event;
		}
		// for elements with subelements eg checkboxes radiobuttons
		if (id == '') {
			id = $(e.target).findClassUp('fabrikSubElementContainer').id;
		}
		if($(id).getProperty('readonly') === true){
			return;
		}
		var el = this.formElements.get(id);
		if (!el) {
			//silly catch for date elements you cant do the usual method of setting the id in the 
			//fabrikSubElementContainer as its required to be on the date element for the calendar to work
			id = id.replace('_time', '');
			el = this.formElements.get(id);
			if(!el){
				return;
			}
		}
		this.runPlugins('onStartElementValidation');
		el.setErrorMessage(this.lang.validating, 'fabrikValidating');
	
		var d = $H({});
	
		this.formElements.each( function(el, key) {
			d = el.prepereForAjaxPost(d);
		}.bind(this));
		d = this._prepareRepeatsForAjax(d);
	
		var origid = el.origId ? el.origId : id;
		el.repeatGroupId = el.repeatGroupId ? el.repeatGroupId : 0;
	
		var url = this.options.liveSite
				+ 'index.php?option=com_fabrik&controller=form&format=raw&task=ajax_validate&form_id='
				+ this.id;
		var myAjax = new Ajax(url, {
			method :this.options.ajaxmethod,
			data :d,
			onComplete :this._completeValidaton.bindAsEventListener(this, [ id, origid ])
		}).request();
	},

	_completeValidaton : function(r, id, origid) {
		r = Json.evaluate(r);
		this.runPlugins('onCompleteElementValidation');
		var el = this.formElements.get(id);
		if ($defined(r.modified[origid])) {
			el.update(r.modified[origid]);
		}
		if ($type(r.errors[origid]) !== false) {
			this._showElementError(r.errors[origid][el.repeatGroupId], id);
		} else {
			
			element_test___field
			this._showElementError( [], id);
		}
	},

	_prepareRepeatsForAjax : function(d) {
		this.getForm();
		//ensure we are dealing with a simple object
		if ($type(d) === 'hash'
				|| ($type(d.obj) === 'object' && this.options.mooversion == 1.1)) {
			d = (this.options.mooversion == 1.1) ? d.obj : d.getClean();
		}
		if (this.options.mooversion == '1.1') {
			this.form.getElements('input[name^=fabrik_repeat_group]').each(
					function(e, c) {
					d['fabrik_repeat_group[' + c + ']'] = e.getValue(); // good for mootools
																															// 1.1
					});
		} else {
			d.fabrik_repeat_group = [];
			this.form.getElements('input[name^=fabrik_repeat_group]').each(
					function(e) {
					d.fabrik_repeat_group.push(e.getValue()); // good for mootools 1.2
					});
		}
		return d;
	},

	_showGroupError : function(r, d) {
		var err = false;
		$H(d).each( function(v, k) {
		if (r.errors[k]) {
			// prepare error so that it only triggers for real errors and not sucess
			// msgs
	
				var msg = '';
				if ($type(r.errors[k]) !== false) {
					for ( var i = 0; i < r.errors[k].length; i++) {
						if (r.errors[k][i] != '') {
							msg += r[i] + '<br />';
						}
					}
				}
				if (msg !== '') {
					tmperr = this._showElementError(r.errors[k], k);
					if (err == false) {
						err = tmperr;
					}
				}
			}
			if (r.modified[k]) {
				var el = this.formElements.get(k);
				if (el) {
					el.update(r.modified[k]);
				}
			}
			}.bind(this));
		return err;
		},
	
		_showElementError : function(r, id) {
		// r should be the errors for the specific element, down to its repeat group
		// id.
		var msg = '';
		if ($type(r) !== false) {
			for ( var i = 0; i < r.length; i++) {
				if (r[i] != '') {
					msg += r[i] + '<br />';
				}
			}
		}
		var classname = (msg === '') ? 'fabrikSuccess' : 'fabrikError';
		if (msg === '')
			msg = this.lang.success;
		this.formElements.get(id).setErrorMessage(msg, classname);
		return (classname === 'fabrikSuccess') ? false : true;
	},

	updateMainError : function() {
		var mainEr = this.form.getElement('.fabrikMainError');
		mainEr.setHTML(this.options.error);
		var activeValidations = this.form.getElements('.fabrikError').filter(
				function(e, index) {
				return !e.hasClass('fabrikMainError');
				});
		if (activeValidations.length > 0 && mainEr.hasClass('fabrikHide')) {
			mainEr.removeClass('fabrikHide');
			var myfx = new Fx.Style(mainEr, 'opacity', {
				duration :500
			}).start(0, 1);
		}
		if (activeValidations.length === 0) {
			myfx = new Fx.Style(mainEr, 'opacity', {
				duration :500,
				onComplete : function() {
				mainEr.addClass('fabrikHide');
				}
			}).start(1, 0);
		}
	},

	runPlugins : function(func) {
		var ret = true;
		this.options.plugins.each( function(plugin) {
		if ($type(plugin[func]) != false) {
			if (plugin[func]() == false) {
				ret = false;
			}
		}
		});
		return ret;
	},

	watchSubmit : function() {
		if (!$('fabrikSubmit' + this.id)) {
			return;
		}
	
		$('fabrikSubmit' + this.id).addEvent('click', function(e) {
		var ret = this.runPlugins('onSubmit');
		this.elementsBeforeSubmit(e);
		if (ret == false) {
			new Event(e).stop();
			// update global status error
				this.updateMainError();
			}
			if (ret && this.options.postMethod == 'ajax') {
				// do ajax val only if onSubmit val ok
				if (this.form) {
					oPackage.startLoading('form_' + this.id);
					this.elementsBeforeSubmit(e);
					// get all values from the form
					var data = $H(this.getFormData());
					data = this._prepareRepeatsForAjax(data);
					var myajax = new Ajax(this.form.action, {
						'data' :data,
						'method' :this.options.ajaxmethod,
						onComplete : function(json) {
						var ojson = Json.evaluate(json);
						// process errors if there are some
						if ($defined(ojson.errors) && ojson.errors.length != 0) {
							// for every element of the form update error message
						this.formElements.each( function(el, key) {
						var eltId = el.element.id;
						var errMsg = '';
						if ($defined(ojson.errors[eltId])) {
							errMsg = ojson.errors[eltId];
						}
						// and main error message
								this._showElementError(errMsg, key);
								}.bind(this));
						// update global status error
						this.updateMainError();
						// stop spinner
						oPackage.stopLoading('form_' + this.id);
						// this.runPlugins('onAjaxSubmitComplete'); don't run it I guess
					} else {
						// no errors
						oPackage.sendMessage('form_' + this.id, 'updateRows', 'ok', json);
						this.runPlugins('onAjaxSubmitComplete');
						// this.clearForm();
					}
					}.bind(this)
					}).request();
				}
			}
			}.bind(this));
		},
	
		elementsBeforeSubmit : function(e) {
		e = new Event(e);
		this.formElements.each( function(el, key) {
		if (!el.onsubmit()) {
			e.stop();
		}
		});
	},

	// used to get the querystring data and
	// for any element overwrite with its own data definition
	// required for empty select lists which return undefined as their value if no
	// items
	// available

	getFormData : function() {
		this.getForm();
		var s = this.form.toQueryString();
		var h = {};
		s = s.split('&');
		s.each( function(p) {
		p = p.split('=');
		h[p[0]] = p[1];
		});
		// $$$rob test commenting out - as this messes up for date from ajax popupform
		// in cal
		/*
		 * this.formElements.each(function(el, key){ var v = el.getValue(); if(v !==
		 * false){ h[key] = v; } }.bind(this));
		 */
		return h;
	},

	watchGroupButtons : function() {
		this.unwatchGroupButtons();
		$$('.deleteGroup').each( function(g, i) {
		g.addEvent('click', this.clickDeleteGroup);
		}.bind(this));
		$$('.addGroup').each( function(g, i) {
		g.addEvent('click', this.clickDuplicateGroup);
		}.bind(this));
		$$('.fabrikSubGroup').each( function(subGroup) {
		var r = subGroup.getElement('.fabrikGroupRepeater');
		if (r) {
			subGroup.addEvent('mouseenter', function(e) {
			r.effect('opacity', {
				wait :false,
				duration :500
			}).start(0.3, 1);
			});
			subGroup.addEvent('mouseleave', function(e) {
			r.effect('opacity', {
				wait :false,
				duration :500
			}).start(1, 0.3);
			});
		}
		});
	},

	unwatchGroupButtons : function() {
		$$('.deleteGroup').each( function(g, i) {
		g.removeEvent('click', this.clickDeleteGroup);
		}.bind(this));
		$$('.addGroup').each( function(g, i) {
		g.removeEvent('click', this.clickDuplicateGroup);
		}.bind(this));
		$$('.fabrikSubGroup').each( function(subGroup) {
		subGroup.removeEvents('mouseenter');
		subGroup.removeEvents('mouseleave');
		});
	},

	addGroupJS : function(groupId, e, js) {
		if (e == 'delete') {
			this.delGroupJS.set(groupId, js);
		} else {
			this.duplicateGroupJS.set(groupId, js);
		}
	},

	deleteGroup : function(event) {
		this.runPlugins('onDeleteGroup');
		var e = new Event(event);
		var group = $(e.target).findClassUp('fabrikGroup');
		var i = group.id.replace('group', '');
		this.duplicatedGroups.remove(i);
		var subgroups = group.getElements('.fabrikSubGroup');
		var subGroup = $(e.target).findClassUp('fabrikSubGroup')
	
		this.subGroups.set(i, subGroup.clone());
		var toel = subGroup.getPrevious();
		var js = this.delGroupJS.get(i);
	
		var myFx = new Fx.Style(subGroup, 'opacity', {
			duration :300,
			onComplete : function() {
			if (subgroups.length > 1) {
				subGroup.remove();
			} else {
				var parent = subGroup.getParent();
				var content = this.subGroups.get(i).clone();
				new Element('div', {
					'class' :'fabrikNotice'
				}).appendText('no data').injectInside(
						content.getElement('.fabrikSubGroupElements').empty());
				content.injectInside(parent.empty());
				this.watchGroupButtons();
			}
			eval(js);
			}.bind(this)
		});
		myFx.start(1, 0);
		if (toel) {
			this.winScroller.toElement(toel);
		}
		$('fabrik_repeat_group_' + i + '_counter').value = $(
				'fabrik_repeat_group_' + i + '_counter').getValue().toInt() - 1;
	
		// update the hidden field containing number of repeat groups
	
		e.stop();
	},

	/* duplicates the groups sub group and places it at the end of the group */

	duplicateGroup : function(event) {
		this.runPlugins('onDuplicateGroup');
		var e = new Event(event);
		e.stop();
		var i = $(e.target).findClassUp('fabrikGroup').id.replace('group', '');
		var js = this.duplicateGroupJS.get(i);
		var group = $('group' + i);
		var subgroups = group.getElements('.fabrikSubGroup');
		var c = subgroups.length;
		if (c == 1 && subgroups[0].getElement('.fabrikNotice')) { // no repeated
																															// groups
			clone = this.subGroups.get(i);
			subgroups[0].remove();
		} else {
	
			var subgroup = $('group' + i).getElement('.fabrikSubGroup');
			if (!subgroup) {
				subgroup = this.subGroups.get(i);
			}
	
			var clone = null;
			var found = false;
			if (this.duplicatedGroups.hasKey(i)) {
				found = true;
			}
			if (!found) {
				clone = subgroup.cloneNode(true);
				this.duplicatedGroups.set(i, clone);
			} else {
				if (!subgroup) {
					clone = this.duplicatedGroups.get(i);
				} else {
					clone = subgroup.cloneNode(true);
				}
			}
		}
		$('fabrik_repeat_group_' + i + '_counter').value = $(
				'fabrik_repeat_group_' + i + '_counter').getValue().toInt() + 1;
		group.appendChild(clone);
		var children = clone.getElements('.fabrikinput');
		// remove values and increment ids
		var newElementControllers = [];
		this.subelementCounter = 0;
	
		var hasSubElements = false;
	
		var inputs = clone.getElements('.fabrikinput');
		var lastinput = null;
		this.formElements.each( function(el) {
			var formElementFound = false;
			subElementContainer = null;
			var subElementCounter = -1;

			inputs.each( function(input) {
	
				hasSubElements = el.hasSubElements();
	
				// var testid = (hasSubElements) ?
				// input.findClassUp('fabrikSubElementContainer').id : input.id
				var testid = (hasSubElements) ? el.element.findClassUp('fabrikSubElementContainer').id : input.id;
	
				if (el.options.element == testid) {
					lastinput = input;
					formElementFound = true;
	
					if (hasSubElements) {
						subElementCounter++;
						subElementContainer = input.findClassUp('fabrikSubElementContainer');
						// clone the first inputs event to all subelements
						input.cloneEvents($(testid).getElement('input'));
	
						// id set out side this each() function
					} else {
						input.cloneEvents(el.element);
	
						// update the elment id
						input.id = input.id + '_' + c;
	
						// update labels for non sub elements
						var l = input.findClassUp('fabrikElementContainer').getElement(
								'label');
						if (l) {
							l.setProperty('for', input.id);
						}
					}
	
					input.name = input.name.replace('[0]', '[' + (c) + ']');
				}
			}.bind(this));
	
			if (formElementFound) {
				if (hasSubElements && $type(subElementContainer) != false ) {
					// if we are checking subelements set the container id after they have all
					// been processed
					// otherwise if check only works for first subelement and no further
					// events are cloned
					subElementContainer.id = el.options.element + '_' + c;
				}
				var origelid = el.options.element;
				// clone js element controller
				var newEl = new CloneObject(el);
				// have to deep clone the options otherwise they are still a reference to
				// the el.options
				var newOpts = new CloneObject(el.options);
				newEl.options = newOpts;
				newEl.container = null;
				newEl.repeatGroupId = c;
				newEl.origId = origelid;
				if (hasSubElements && $type(subElementContainer) != false ) {
					newEl.element = $(subElementContainer);
					newEl.options.element = subElementContainer.id
					newEl._getSubElements();
				} else {
					newEl.element = $(lastinput.id);
					newEl.options.element = lastinput.id;
				}
				newEl.clear();
				newElementControllers.push(newEl);
			}
		}.bind(this));
		
		// add new element controllers to form
		this.addElements(newElementControllers);
		this.winScroller.toElement(clone);
		var myFx = new Fx.Style(clone, 'opacity', {
			duration :500
		}).set(0);
		newElementControllers.each( function(newEl) {
			newEl.cloned(c);
		});
		c = c + 1;
		myFx.start(1);
		eval(js);
		this.unwatchGroupButtons();
		this.watchGroupButtons();
	},

	update : function(o) {
		this.runPlugins('onUpdate');
		var leaveEmpties = arguments[1] || false;
		// if (o.id == this.id && o.model == 'form') { //this incorrectly stops the
		// table module's form loading its data
		// if (o.id == this.id) {
		var data = o.data;
		this.getForm();
		if (this.form) { // test for detailed view in module???
			var rowidel = this.form.getElement('input[name=rowid]');
			if (rowidel) {
				rowidel.value = data.rowid;
			}
		}
		this.formElements.each( function(el, key) {
		if (key.substring(key.length - 3, key.length) == '_ro') {
			key = key.substring(0, key.length - 3);
		}
		// this if stopped the form updating empty fields. Element update() methods
		// should test for null
				// variables and convert to their correct values
				// if (data[key]) {
				if ($type(data[key]) === false) {
					// only update blanks if the form is updating itself
					// leaveEmpties set to true when this form is called from updateRows
					if (o.id == this.id && !leaveEmpties) {
						el.update('');
					}
				} else {
					el.update(data[key]);
				}
				}.bind(this));
		// }else{
		// not sure this is a good idea - testing resetting of submitted form module
		// this.reset();
		// }
	},

	reset : function() {
	this.runPlugins('onReset');
	this.formElements.each( function(el, key) {
	el.reset();
	}.bind(this));
	},

	showErrors : function(data) {
	var d = null;
	if (data.id == this.id) {
		// show errors
		var errors = new Hash(data.errors);
		if (errors.keys().length > 0) {
			this.form.getElement('.fabrikMainError').setHTML(this.options.error);
			this.form.getElement('.fabrikMainError').removeClass('fabrikHide');
			errors.each( function(a, key) {
				if ($(key + '_error')) {
					var e = $(key + '_error');
					var msg = new Element('span');
					for ( var x = 0; x < a.length; x++) {
						for ( var y = 0; y < a[x].length; y++) {
							d = new Element('div').appendText(a[x][y]).injectInside(e);
						}
					}
				} else {
					fconsole(key + '_error' + ' not found');
				}
			});
		}
	}
},

	/** add additional data to an element - e.g database join elements */
	appendInfo : function(data) {
		this.formElements.each( function(el, key) {
		if (el.appendInfo) {
			el.appendInfo(data);
		}
		}.bind(this));
	},

	addListenTo : function(blockId) {
	this.listenTo.push(blockId);
	},
	
	clearForm : function() {
		this.getForm();
		if (!this.form) {
			return;
		}
		this.formElements.each( function(el, key) {
			if (key == this.options.primaryKey) {
				this.form.getElement('input[name=rowid]').value = '';
			}
			el.update('');
		}.bind(this));
		// reset errors
		this.form.getElements('.fabrikError').empty();
		this.form.getElements('.fabrikError').addClass('fabrikHide');
	},
		
	receiveMessage : function(senderBlock, task, taskStatus, data) {
		if (this.listenTo.indexOf(senderBlock) != -1) {
			if (task == 'processForm') {
		
			}
			// a row from the table has been loaded
			if (task == 'update') {
				this.update(data);
			}
			if (task == 'clearForm') {
				this.clearForm();
			}
		}
		
		if (senderBlock == 'form_' + this.id) {
			var id = 'fabrik_update_msg_' + this.id;
			var d = new Element('div', {
				'id' :id,
				'class' :'update_msg'
			}).appendText(this.options.updatedMsg);
			try {
				// Barbara : does not work with Mocha window
			d.injectBefore(this.form);
			} catch (err) {
			}
		
			var myfx = new Fx.Style(id, 'opacity', {
				duration :500
			});
			myfx.set(0);
			myfx.start(0, 1).chain( function() {
				this.start(1, 0).chain( function() {
				if ($(id)) {
					$(id).remove();
				}
				});
			});
		}
	
		// a form has been submitted which contains data that should be updated in this
		// form
		// currently for updating database join drop downs, data is used just as a
		// test to see if the dd needs
		// updating. If found a new ajax call is made from within the dd to update
		// itself
		// $$$ hugh - moved showErrors() so it only runs if data.errors has content
		if (task == 'updateRows') {
			// if (!data.errors || data.errors.length === 0) {
			if ($H(data.errors).keys().length === 0) {
				// this.clearForm();
				this.appendInfo(data);
				this.update(data, true);
			} else {
				this.showErrors(data);
			}
		}
		},
	
		addPlugin : function(plugin) {
		this.options.plugins.push(plugin);
	}

});

var Injector = new Class( {
	injectScript : function(src) {
	var script = new Element('script', {
		'type' :'text/javascript',
		'src' :src
	});
	script.injectInside(document.head);
	}
});

var scriptInjector = new Injector();
