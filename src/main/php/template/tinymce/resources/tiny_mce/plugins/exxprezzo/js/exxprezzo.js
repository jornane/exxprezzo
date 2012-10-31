var Exxprezzo = {
	
	linkCallback : function(linkDest) {
		var ed = tinyMCEPopup.editor, e, b, href = linkDest;

		tinyMCEPopup.restoreSelection();
		e = ed.dom.getParent(ed.selection.getNode(), 'A');

		// Remove element if there is no href
		if (!href) {
			if (e) {
				b = ed.selection.getBookmark();
				ed.dom.remove(e, 1);
				ed.selection.moveToBookmark(b);
				tinyMCEPopup.execCommand("mceEndUndoLevel");
				tinyMCEPopup.close();
				return;
			}
		}

		// Create new anchor elements
		if (e == null) {
			ed.getDoc().execCommand("unlink", false, null);
			tinyMCEPopup.execCommand("mceInsertLink", false, "#mce_temp_url#", {skip_undo : 1});

			tinymce.each(ed.dom.select("a"), function(n) {
				if (ed.dom.getAttrib(n, 'href') == '#mce_temp_url#') {
					e = n;

					ed.dom.setAttribs(e, {
						href : href,
						title : null,
						target : null,
						'class' : null
					});
				}
			});
		} else {
			ed.dom.setAttribs(e, {
				href : href,
				title : null
			});
		}

		// Don't move caret if selection was image
		if (e.childNodes.length != 1 || e.firstChild.nodeName != 'IMG') {
			ed.focus();
			ed.selection.select(e);
			ed.selection.collapse(0);
			tinyMCEPopup.storeSelection();
		}

		tinyMCEPopup.execCommand("mceEndUndoLevel");
		tinyMCEPopup.close();
	},
	
	imageCallback : function(imgDest) {
		var ed = tinyMCEPopup.editor, args = {}, el;

		tinyMCEPopup.restoreSelection();

		if (!imgDest) {
			if (ed.selection.getNode().nodeName == 'IMG') {
				ed.dom.remove(ed.selection.getNode());
				ed.execCommand('mceRepaint');
			}

			tinyMCEPopup.close();
			return;
		}

		if (ed.settings.inline_styles)
			args.style = this.styleVal;

		tinymce.extend(args, {
			src : imgDest,
			alt : null,
			width : null,
			height : null,
		});

		el = ed.selection.getNode();

		if (el && el.nodeName == 'IMG') {
			ed.dom.setAttribs(el, args);
			tinyMCEPopup.editor.execCommand('mceRepaint');
			tinyMCEPopup.editor.focus();
		} else {
			tinymce.each(args, function(value, name) {
					if (value === "") {
						delete args[name];
					}
				});

			ed.execCommand('mceInsertContent', false, tinyMCEPopup.editor.dom.createHTML('img', args), {skip_undo : 1});
			ed.undoManager.add();
		}

		tinyMCEPopup.close();
	},

}

var links = [];
var callback = new RegExp('\\bcallback\\b');
var elem = document.getElementsByTagName('a');
for (var i = 0; i < elem.length; i++) {
	var classes = elem[i].className;
	if (callback.test(classes)) {
		if (elem[i].rel == "imageCallback")
			elem[i].onclick = function(){Exxprezzo.imageCallback(this.href);return false;};
		if (elem[i].rel == "linkCallback")
			elem[i].onclick = function(){Exxprezzo.linkCallback(this.href);return false;};
	}
}
