var fbDateTime = FbElement.extend({
	initialize: function(element, options) {
		this.setOptions(element, options);
		this.hour = '0';
		this.plugin = 'fabrikdate';
		this.minute = '00';
		this.buttonBg = '#ffffff';
		this.buttonBgSelected = '#88dd33';
		this.startElement = element;
		this.setUp = false;
		this._getSubElements();
		if(this.options.typing == false){
			this.element.addEvent('focus', function(){
				this.element.blur();
			}.bind(this));
			//doesnt work in ff3 ;(
			//this.element.readonly = 'readonly';
			if(this.timeElement){
				this.timeElement.addEvent('focus', function(){
					this.element.blur();
				}.bind(this));
			}
		}
		
	},
	
	getValue:function(){
		if(!this.options.editable){
			return this.options.defaultVal;
		}
		this.getElement();
		var v = this.element.getValue();
		if ($(this.options.element + '_time')) {
			v += ' ' + $(this.options.element + '_time').getValue();
		}
		return(v);
	},
	
	_getSubElements: function(){
		if ($(this.options.element + '_time')) {
			
			this.timeElement = $(this.options.element + '_time');
		}
		if($(this.options.element + '_img')){
			//$(this.options.element + '_img').removeEvents('click');
			$(this.options.element + '_img').addEvent('click', function(e){
				this.showCalendar('y-mm-dd');
			}.bind(this));
		}
		if($(this.options.element + '_time_button_time')){
			$(this.options.element + '_time_button_time').removeEvents('click');
			$(this.options.element + '_time_button_time').addEvent('click', function(e){
				this.showTime();
			}.bind(this));
			if(!this.setUp){
			if( this.timeElement ){
				this.dropdown = this.makeDropDown();
				this.dropdown.injectInside(document.body);
				this.setAbsolutePos(this.timeElement);
				this.setUp = true;
				}
			}
		}
	},
	
	addNewEvent: function( action, js ){
		this._getSubElements();
		if(action == 'load'){
			eval(js);
		}else{
			if(!this.element){
				this.element = $(this.strElement);
			}
			this.element.addEvent( action, function(e){
				eval(js);
				e = new Event(e);
				e.stop();
			});
		}
	},
	
	update: function(val){
		if($type(val) === false){
			return;
		}
		if (!this.options.editable) {
			this.element.innerHTML = val;
			return;
		}
		var bits = val.split(" ");
		var date = bits[0];
		var time = (bits.length > 1) ? bits[1].substring(0, 5) : '00:00';
		var timeBits = time.split(":");
		this.hour = timeBits[0];
		this.minute = timeBits[1];
		this.element.value = date;
		this.stateTime();
	},
	
	//prepare data for ajax validation
	prepereForAjaxPost: function(d)
	{
		if(!this.options.editable){
			return d;
		}
		var v = this.getValue();
		if($type(v) !== false){
			var element = this.element;
			var name = element.name.replace('[date]', '');
			if(d.hasKey(name)){ //repeat data test
			
				var orig = d.get(name);
				if($type(orig) !== 'array'){
					orig = [orig];
				}
				orig.push(v)
				d.set(name, orig);
			
			}else{
				d.set(name, v);
			}
		}
		return d;
	},
	
	showCalendar:function( format){
		//return showCalendar(this.element.id, format);
		if(window.ie){
		//when scrolled down the page the offset of the calendar is wrong - this fixes it
			newtop = this.element.getTop().toInt()  - $(window.calendar.element).getStyle('height').toInt();
			newtop += 'px';
			$(window.calendar.element).setStyles({'top': newtop});
		}
	},
	
	getAbsolutePos: function(el) {
		var r = { x: el.offsetLeft, y: el.offsetTop };
		if (el.offsetParent) {
			var tmp = this.getAbsolutePos(el.offsetParent);
			r.x += tmp.x;
			r.y += tmp.y;
		}
		return r;
	},
	
	setAbsolutePos: function(el){
		var r = this.getAbsolutePos(el);
		var div = $(this.startElement + '_hourContainer');
		div.setStyles({position:'absolute', left:r.x, top:r.y + 30});
	},

	makeDropDown:function(){
		var h = null;
		var handle = new Element('div', {
			styles:{
				'height':'20px',
				'curor':'move',
				'color':'#dddddd',
				'padding':'2px;',
				'background-color':'#333333'
			},
			'id':this.startElement + '_handle'
		}).appendText(this.options.timelabel);
		var d = new Element('div', {
			'id':this.startElement + '_hourContainer',
			'className':'fbDateTime',
			'styles':{
				'z-index':999999,
				display:'none',
				cursor:'move',width:'264px',height:'125px',border:'1px solid #999999',backgroundColor:'#EEEEEE'
			}
		});
	
		d.appendChild(handle);
		for(var i=0;i<24;i++){
			h = new Element('div', {styles:{width:'20px','float':'left','cursor':'pointer','background-color':'#ffffff','margin':'1px','text-align':'center'}});
			h.innerHTML = i;
			h.className = 'fbdateTime-hour';
			d.appendChild(h);
			$(h).addEvent( 'click', function(event){
				var e = new Event(event);
				this.hour = $(e.target).innerHTML;
				this.stateTime();
				this.setActive();
			}.bind(this));
			$(h).addEvent( 'mouseover', function(event){
				var e = new Event(event);
				var h = $(e.target);
				if(this.hour != h.innerHTML){
					e.target.setStyles( {background:'#cbeefb'});
				}
			}.bind(this));
			$(h).addEvent( 'mouseout', function(event){
				var e = new Event(event);
				var h = $(e.target);
				if(this.hour != h.innerHTML){
					h.setStyles({background:this.buttonBg});
				}
			}.bind(this));
		}
		var d2 = new Element('div', {styles:{clear:'both',paddingTop:'5px'}});
		for(i=0;i<12;i++){
			h = new Element('div', {styles:{width:'41px','float':'left','cursor':'pointer','background':'#ffffff','margin':'1px','text-align':'center'}});
			h.setStyles();
			h.innerHTML = ':' + (i * 5);
			h.className = 'fbdateTime-minute';
			d2.appendChild(h);
			$(h).addEvent( 'click', function(e){
				e = new Event(e);
				this.minute = this.formatMinute(e.target.innerHTML);
				this.stateTime();
				this.setActive();
			}.bind(this));
			h.addEvent( 'mouseover', function(event){
				var e = new Event(event);
				var h = $(e.target);
				if(this.minute != this.formatMinute(h.innerHTML)){
					e.target.setStyles({background:'#cbeefb'});
				}
			}.bind(this));
			h.addEvent( 'mouseout', function(event){
				var e = new Event(event);
				var h = $(e.target);
				if(this.minute != this.formatMinute(h.innerHTML)){
					e.target.setStyles({background:this.buttonBg});	
				}
			}.bind(this) );
		}
		d.appendChild(d2);

		document.addEvent( 'click', function(event){
			var e = new Event(event);
			var t = $(e.target);
			if(t != $(this.element.id + '_time_button_time')){
				if(!t.within(this.dropdown)){
					$(this.dropdown).setStyles({'display':'none'});
				}
			}
		}.bind(this));
		var mydrag = new Drag.Move(d);
		return d;
	},
	
	toggleTime: function(){
		if(this.dropdown.style.display == 'none'){
			$(this.dropdown).setStyles({'display':'block'});
		}else{
			$(this.dropdown).setStyles({'display':'none'});
			this.form.doElementValidation(this.element.id);
		}
	},

	formatMinute:function(m){
		m = m.replace(':','');
		if(m.length == 1){
			m = '0' + m;
		}
		return m;
	},

	stateTime:function(){
		if(this.timeElement){
			$(this.timeElement.id.replace('[]', '')).value = this.hour+ ':' + this.minute;	
		}
	},

	showTime:function(){
		el = this.timeElement;
		this.toggleTime();
		this.setActive();
	},

	setActive: function(){
		//var hours = $A(this.dropdown.getElementsByClassName(''));
		var hours = this.dropdown.getElements('.fbdateTime-hour');
		hours.each(function(e){
			e.setStyles({backgroundColor:this.buttonBg});
		}, this);
		//var mins = $A(this.dropdown.getElementsByClassName('fbdateTime-minute'));
		var mins = this.dropdown.getElements('.fbdateTime-minute');
		mins.each(function(e){
			e.setStyles({backgroundColor:this.buttonBg});
		}, this);
		hours[this.hour].setStyles({backgroundColor:this.buttonBgSelected});
		mins[this.minute / 5].setStyles({backgroundColor:this.buttonBgSelected});
	},
	
	cloned: function(c){
		var button = this.element.getNext();
		button.id = this.element.id + "_img";
		this.options.calendarSetup.inputField = this.element.id;
		this.options.calendarSetup.button = this.element.id + "_img";
		Calendar.setup(this.options.calendarSetup);
	}
});

