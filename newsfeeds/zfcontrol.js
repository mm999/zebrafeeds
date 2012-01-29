/*
ZebraFeeds - copyright (c) 2006 Laurent Cazalet
http://www.cazalet.org/zebrafeeds

show/hide functions - can be included by templates
*/


function toggleVisible(element, initial) {

	var state = element.style.display;
	if (state == "") {
		state = initial;
	}

	if (state == "none") {
	   element.style.display = "block";
	} else {
	   element.style.display = "none";
	}
}

function toggleVisibleById(id, initial) {

	if(document.getElementById(id))  {

		var element = document.getElementById(id);
		toggleVisible(element, initial);
	}
}
