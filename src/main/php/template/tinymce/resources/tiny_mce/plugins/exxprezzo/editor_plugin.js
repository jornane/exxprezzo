(function() {
	tinymce.create('tinymce.plugins.ExxprezzoPlugin', {
		init : function(ed, url) {
			// Register commands
			ed.addCommand('mceExpImage', function(ui, val) {
				// Internal image object like a flash placeholder
				if (ed.dom.getAttrib(ed.selection.getNode(), 'class', '').indexOf('mceItem') != -1)
					return;

				var w = ed.windowManager.open({
					url : ed.settings.image_href ? ed.settings.image_href : this.url + '/image.htm',
					width : 640,
					height : 480,
					inline : true,
					name : "image"
				}, {
					theme_url : url
				});
				var iframe = document.getElementById(w.iframeElement.id);
				iframe.onload = function() {
					var links = [];
					var callback = new RegExp('\\bcallback\\b');
					var elem = iframe.contentDocument.getElementsByTagName('a');
					for (var i = 0; i < elem.length; i++) {
						var classes = elem[i].className;
						if (callback.test(classes)) elem[i].rel = "imageCallback";
					}
					
					var head = iframe.contentDocument.getElementsByTagName("head")[0];
					var script = document.createElement("script");
					script.setAttribute('src', url + '/../../tiny_mce_popup.js');
					head.appendChild(script);
					script = document.createElement("script");
					script.setAttribute('src', url + '/js/exxprezzo.js');
					head.appendChild(script);
				};
			});
			
			ed.addCommand('mceExpLink', function(ui, val) {
				//var n = "link" + new Date().getTime();
				var n = 'link';
	
				var w = ed.windowManager.open({
					url : ed.settings.link_href ? ed.settings.link_href : this.url + '/link.htm',
					width : 310,
					height : 200,
					inline : true,
					name : 'link'
				}, {
					theme_url : url
				});
				var iframe = document.getElementById(w.iframeElement.id);
				iframe.onload = function() {
					var links = [];
					var callback = new RegExp('\\bcallback\\b');
					var elem = iframe.contentDocument.getElementsByTagName('a');
					for (var i = 0; i < elem.length; i++) {
						var classes = elem[i].className;
						if (callback.test(classes)) elem[i].rel = "linkCallback";
					}
					
					var head = iframe.contentDocument.getElementsByTagName("head")[0];
					var script = document.createElement("script");
					script.setAttribute('src', url + '/../../tiny_mce_popup.js');
					head.appendChild(script);
					script = document.createElement("script");
					script.setAttribute('src', url + '/js/exxprezzo.js');
					head.appendChild(script);
				};
			});

			// Register buttons
			ed.addButton('image', {
				title : 'advimage.image_desc',
				cmd : 'mceExpImage'
			});
			ed.addButton('link', {
				title : 'advimage.link_desc',
				cmd : 'mceExpLink'
			});
		},

		getInfo : function() {
			return {
				longname : 'Exxprezzo support',
				author : 'Yørn de Jong',
				authorurl : 'http://yorn.priv.no',
				infourl : 'http://yorn.priv.no',
				version : "0.1"
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('exxprezzo', tinymce.plugins.ExxprezzoPlugin);
})();