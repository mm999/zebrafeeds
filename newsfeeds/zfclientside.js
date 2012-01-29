/*
ZebraFeeds - copyright (c) 2006 Laurent Cazalet
http://www.cazalet.org/zebrafeeds
client side functions - ajax stuff, included only if the template
has {dynamiclength} in "template header" area
*/

function getHTTPObject() {
	var xmlhttp;

	try {
	  xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
	} catch (e) {
	  try {
		xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
	  } catch (E) {
		xmlhttp = false;
	  }
	}


	if (!xmlhttp && typeof XMLHttpRequest != 'undefined') {
		try {
			xmlhttp = new XMLHttpRequest();
		} catch (e) {
			xmlhttp = false;
		}
	}
	return xmlhttp;
}

function requestContent(requestparams) {
	scripturl = ZFURL + "/zebrafeeds.php";

	//http.onload = null;
	http.open("GET", scripturl + '?zftemplate='+ ZFTEMPLATE + '&' + requestparams, true);
	//alert(scripturl + '?' + requestparams);
	//xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");

	http.onreadystatechange = handleResponse;
	http.send(null);
}

/* when data returning from server arrives
structured this way
<OBJECT ID>|,|,|<content>
*/

function handleResponse() {
	if (http.readyState == 4) { // Complete

		if (http.status == 200) { // OK response

			/* split according to our separator */
			results = http.responseText.split("|,|,|");

			//alert(results);
			element = parent.document.getElementById(results[0]);
			if (element == null) {
				element = document.getElementById(results[0]);
			}


			if (element == null) {
				alert('Problem with response: ' + http.responseText);
			} else {
				element.innerHTML = results[1];
			}


		}
	}
}


/* all in one: fetch and show
itemid is the zfeeder news item id, not the html element id
outputid is the id of the element to send the output to. 
the server know what to do...
*/
function showItem(feedurl, itemid, outputid) {

	fetchItem(feedurl, itemid, outputid);
	toggleVisibleById('ZFCONTENT' + itemid, "none");
}

function fetchItem(feedurl, itemid, outputid) {
	/* if the output element id exists in the document, then 
	 we have to send the result of the ajax query to a fixed
	 CSS element whose id is outputid */
	if (outputid != null && document.getElementById(outputid) )  {
		// we have to output in another div
		requestparams = "type=item&xmlurl=" + escape(feedurl) + "&outputid=" + outputid + "&itemid=" + itemid;
		requestContent(requestparams);
		
	} else {
		/* if we have to output in the same div as the caller */
		if(document.getElementById('ZFCONTENT'+ itemid))  {
			var element = document.getElementById('ZFCONTENT'+ itemid);

			// if the element is empty, load from server
			var contentlen = element.innerHTML.trim().length;
			if ( contentlen == 0 ) {
				element.innerHTML = '<br/><br/><br/><br/><br/>';
				requestparams = "type=item&xmlurl=" + escape(feedurl) + "&outputid=" + itemid + "&itemid=" + itemid;
				requestContent(requestparams);
			}

		}
	}
}


function getAllItems(feedurl,refreshtime) {
	requestparams = "type=channelallitems&xmlurl=" + escape(feedurl) + "&refreshtime="+refreshtime;
	requestContent(requestparams);
}

function refreshChannel(feedurl,showeditems,refreshtime) {
	requestparams = "type=channelforcerefresh&xmlurl=" + escape(feedurl) + "&maxitems="+showeditems+ "&refreshtime="+refreshtime;
	requestContent(requestparams);

}


var http = getHTTPObject();

// another global var, ZFURL will be defined on the fly

