var fbGoogleTableMap = new Class({
	initialize: function(element, options) {
		
		this.element_map = element;
		this.element = $(element);
		
		this.baseIcon = new GIcon(G_DEFAULT_ICON);
		this.baseIcon.shadow = "http://www.google.com/mapfiles/shadow50.png";
		this.baseIcon.iconSize = new GSize(20, 34);
		this.baseIcon.shadowSize = new GSize(37, 34);
		this.baseIcon.iconAnchor = new GPoint(9, 34);
		this.baseIcon.infoWindowAnchor = new GPoint(9, 2);
		this.clusterMarkerCursor = 0;
		this.clusterMarkers = [];
		this.icons = [];
		this.options = Object.extend({
			'lat':0,
			'lon':0,
			'zoomlevel':'13',
			'control':'',
			'maptypecontrol':'0',
			'overviewcontrol':'0',
			'scalecontrol':'0',
			'livesite':'',
			'center':'middle',
			'ajax_refresh':0,
			'maptype':G_NORMAL_MAP
		}, options || {});
		
		if(this.options.ajax_refresh == 1){
			this.updater = 	new Ajax( 'index.php', {
				data :{
					'option':'com_fabrik',
					'format':'raw',
					'controller':'plugin',
					'task':'pluginAjax',
					'plugin':'googlemap',
					'method':'ajax_getMarkers',
					'g':'visualization',
					'element_id':this.options.id
				},
				onSuccess:function(json){
					this.options.icons = Json.evaluate(json);
					this.addIcons();
					this.center();
				}.bind(this)
			});
			this.timer = this.update.periodical(10000,this);
		}
		
		switch(this.options.maptype){
			case 'G_NORMAL_MAP':
				this.options.maptype = G_NORMAL_MAP;
				break;
			case 'G_SATELLITE_MAP':
				this.options.maptype = G_SATELLITE_MAP;
				break;
			case 'G_HYBRID_MAP':
				this.options.maptype = G_HYBRID_MAP;
				break;
		}		
		window.addEvent('domready', function(){
			if (GBrowserIsCompatible()) {
				this.map = new GMap2($(this.element_map));
				this.map.setCenter(new GLatLng(this.options.lat, this.options.lon), this.options.zoomlevel.toInt());
				switch(this.options.control){
					case 'GLargeMapControl':
						this.map.addControl(new GLargeMapControl());
						break;
					case 'GSmallMapControl':
						this.map.addControl(new GSmallMapControl());
						break;
					case 'GSmallZoomControl':
						this.map.addControl(new GSmallZoomControl());
						break;
				}
				if(this.options.scalecontrol != '0'){
					this.map.addControl(new GScaleControl());
				}
				if(this.options.maptypecontrol != '0'){
					this.map.addControl(new GMapTypeControl());
				}
				if(this.options.overviewcontrol != '0'){
					this.map.addControl(new GOverviewMapControl());
				}
				this.bounds = new GLatLngBounds();
				if(this.options.clustering == false){
					this.markerMgr = new MarkerManager(this.map);
				}
				this.addIcons();
				this.center();
			  this.map.setMapType(this.options.maptype);
			}
		}.bind(this))
	},
	
	update: function(){
		this.updater.request();
	},
	
	addIcons: function(){
		if(this.options.clustering == false){
			this.markerMgr.clearMarkers();
		}
		this.options.icons.each(function(i){
			this.bounds.extend(new GLatLng(i[0], i[1]));
			this.addIcon(i[0], i[1], i[2], i[3], i[4], i[5]);
		}.bind(this));
	  
	  if(this.options.clustering != false){
		  this.cluster=new ClusterMarker(this.map, { markers:this.clusterMarkers, 'splits':this.options.cluster_splits, 'icon_increment':this.options.icon_increment} );
			this.cluster.fitMapToMarkers();
			this.map.savePosition();	//	enables the large map control centre button to return the map to initial view
		}
	}, 
	
	center: function(){
	//set the map to center on the center of all the points
		if(this.options.center == 'middle'){
		 	this.map.setCenter(this.bounds.getCenter());
		}else{
			var lasticon = this.options.icons.getLast();
			if(lasticon){
	  		var c = new GLatLng(lasticon[0], lasticon[1]);
	  		this.map.setCenter(c);
	  	}else{
	  		this.map.setCenter(this.bounds.getCenter());
	  	}
	  }
	},
	
	addIcon: function(lat, lon, html, img, w, h){
		var point = new GLatLng(lat, lon);
		
 		if(img !== ''){
 			var thisicon = new GIcon(this.baseIcon);
 			if(img.substr(0,7) != 'http://' && img.substr(0,8) != 'https://'){
 				thisicon.image = this.options.livesite + 'images/stories/' + img;
 			}else{
 				thisicon.image = img;
 			}
 			thisicon.shadowSize = new GSize(0, 0);
 		}else{
 			thisicon = this.baseIcon;
 		}
 		thisicon.iconSize = new GSize(w, h);
 		thisicon.iconAnchor = new GPoint((w/2)-1, h);
		var markerOptions = { 'icon':thisicon };
  	var marker = new GMarker(point, markerOptions);
       
		GEvent.addListener(marker, "click", function() {
 			marker.openInfoWindowHtml(html);
 		}.bind(this));
  	if(this.options.clustering == false){
			this.markerMgr.addMarker(marker, 0);
		}else{
			this.clusterMarkers.push(marker);
			this.clusterMarkerCursor ++;
		}
	}
});