function init() {
	tinyMCEPopup.resizeToInnerSize();
}

function getCheckedValue(radioObj) {
	if(!radioObj)
		return "";
	var radioLength = radioObj.length;
	if(radioLength == undefined)
		if(radioObj.checked)
			return radioObj.value;
		else
			return "";
	for(var i = 0; i < radioLength; i++) {
		if(radioObj[i].checked) {
			return radioObj[i].value;
		}
	}
	return "";
}

function insertcfbmButtonLink() {
	
	var tagtext;
    tagtext = "";
	
	var wod = document.getElementById('wod_panel');
	var scores = document.getElementById('scores_panel');
	
	// who is active ?
	if (wod.className.indexOf('current') != -1) {
		var wodid = document.getElementById('wodtag').value;
		var showtag = getCheckedValue(document.getElementsByName('showtag'));

		if (wodid != 0 )
			tagtext = "[cf-benchmark " + wodid + " pic=" + showtag + "]";
		else
			tinyMCEPopup.close();
	}
	
	if (scores.className.indexOf('current') != -1) {
		var showtag = getCheckedValue(document.getElementsByName('showscores'));
		var rx = "";
		var userinput = "";
		
		if(document.getElementById('rx').checked == true) { rx = "yes" } else { rx = "no" };
		if(document.getElementById('userinput').checked == true) { userinput = "yes" } else { userinput = "no" };

		if (showtag == "yes" )
			tagtext = "[cf-scoreboard rx=" + rx + " userinput=" + userinput + "]";
		else
			tinyMCEPopup.close();
	}
	
	if(window.tinyMCE) {
		//TODO: For QTranslate we should use here 'qtrans_textarea_content' instead 'content'
		window.tinyMCE.execInstanceCommand('content', 'mceInsertContent', false, tagtext);
		//Peforms a clean up of the current editor HTML. 
		//tinyMCEPopup.editor.execCommand('mceCleanup');
		//Repaints the editor. Sometimes the browser has graphic glitches. 
		tinyMCEPopup.editor.execCommand('mceRepaint');
		tinyMCEPopup.close();
	}
	return;
}
