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
function zf_getArticle(feedurl, itemid, outputelementid) {
	/* if the output element id exists in the document, then 
	 we have to send the result of the ajax query to a fixed
	 CSS element whose id is outputelementid */
	if (outputelementid != null && document.getElementById(outputelementid) )  {
		// we have to output in another div
		requestparams = "type=item&xmlurl=" + escape(feedurl) + "&outputelementid=" + outputelementid + "&itemid=" + itemid;
		requestContent(requestparams);
		
	}
}


function zf_getAllNews(feedurl,refreshtime, outputelementid) {
	requestparams = "type=channelallitems&xmlurl=" + escape(feedurl) + "&refreshtime="+refreshtime+"&outputelementid=" + outputelementid;
	requestContent(requestparams);
}

function zf_getRefreshedNews(feedurl,showeditems,refreshtime,outputelementid) {
	requestparams = "type=channelforcerefresh&xmlurl=" + escape(feedurl) + "&maxitems="+showeditems+ "&refreshtime="+refreshtime+"&outputelementid=" + outputelementid;
	requestContent(requestparams);

}


var http = getHTTPObject();

// another global var, ZFURL will be defined on the fly

