/*
ZebraFeeds - copyright (c) 2006 Laurent Cazalet
http://www.cazalet.org/zebrafeeds
client side functions - ajax stuff, included only if the template
has {dynamiclength} in "template header" area
*/

var ZFURL="newsfeeds";

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
	scripturl = ZFURL + "/async.php";

	//http.onload = null;
	http.open("GET", scripturl + '?zftemplate='+ ZFTEMPLATE + '&f=html&' + requestparams, true);
	//alert(scripturl + '?' + requestparams);

	http.onreadystatechange = handleResponse;
	http.send(null);
}

/* when data returning from server arrives
structured this way
<id of element to populate>|,|,|<content>
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



/* lookup in <feedurl>, item with id <itemid>, and put it in <outputelementid>*/
function zf_getArticle(chanid, itemid, outputelementid) {
	/* if the output element id exists in the document, then
	 we have to send the result of the ajax query to a fixed
	 CSS element whose id is outputelementid */
		// we have to output in another div
	requestparams = "q=item&id=" + chanid + "&itemid=" + itemid;
	requestContent(requestparams);
}


function zf_getAllNews(chanid,refreshtime, outputelementid) {
	requestparams = "q=channel&trim=none&id=" + chanid;
	requestContent(requestparams);
}

function zf_getRefreshedNews(chanid,outputelementid) {
	requestparams = "q=channel&trim=auto&mode=refresh&id=" + chanid ;
	requestContent(requestparams);

}


var http = getHTTPObject();


