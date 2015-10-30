<?php
// ZebraFeeds - copyright (c) 2006 Laurent Cazalet
// http://www.cazalet.org/zebrafeeds
//
// zFeeder 1.6 - copyright (c) 2003-2004 Andrei Besleaga
// http://zvonnews.sourceforge.net
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

if (!defined('ZF_VER')) exit;

/* we would need this to clear the history file */
//require_once($zf_path . 'includes/history.php');

function sortChannelsByName($subscriptions) {
	/* sort channels list by setting the array key to the position */
	$sortedchannels = array();
	foreach($subscriptions as $i => $sub) {
		if ($sub->xmlurl != '') {
			/* tackle duplicate names */
			if (isset($sortedchannels[$sub->title]) || (strlen($sub->title)) == 0) {
				$title = $sub->title. ' ('.$sub->position.')';
			} else {
				$title = $sub->title;
			}
			/*echo $title.' -- ';*/
			$sortedchannels[$title] = $sub;
			/* but we need to keep the original index to identify the feed, next time we need it in
			a copy/delete operation */
			//$sortedchannels[$title]->opmlindex = $i;
		}
	}
	ksort($sortedchannels);
	return $sortedchannels;
}

function sortChannelsByPosition($subscriptions) {
	/* sort channels list by setting the array key to the position */
	$sortedChannels = array();
	foreach($subscriptions as $i => $sub) {
		if ($sub->xmlurl != '') {
			$sortedchannels[$sub->position] = $sub;
			/* but we need to keep the original index to identify the feed, next time we need it in
			a copy/delete operation */
			//$sortedchannels[$sub->position]['opmlindex'] = $i;
		}
	}
	ksort($sortedchannels);
	return $sortedchannels;
}



/* function to be called while displaying the channel table
channel are sorted according to their position
arg : channel, an array of feeds (got from opml functions)
*/
function displayChannelList($subs) {

	$channelcount = count($subs);

	if ($channelcount > 0) {
		$namehtmldata = <<<EOD
<div title="Edit feed properties" class="sub-line" id="entry{i}">
	<span id="title{i}" class="{class} link" onclick="showEditForm('{i}'); return false;">{chantitle}</span>&nbsp;
	<a href="javascript:open('{htmlurl}')" title="Open the publisher site in a new window" onclick="window.open('{htmlurl}'); return false;"><img src="res/img/extlink.png" alt="website"/></a>
	<div class="editfeed" id="editform{i}" style="display:none;">
		<form action="#">
			<div><a href="?zfaction=feeds&q=channel&id={i}">Read news</a>
				<div><input type="checkbox" id="isactive{i}" name="isactive" {isactive} value="isactive" title="Active"/> <label for="isactive{i}">Active</label>
				:: <a href="" title="remove from the subscription list" onclick="removeChannel('{i}','{chantitle_enc}'); return false;"/>Unsubscribe</a></div>
				<label for="chantitle{i}">Title:</label>&nbsp;<br/>
				<input type="text" class="desc" name="chantitle" id="chantitle{i}" value="{chantitle}" /><br/>
				<label for="xmlurl{i}"> feed URL:</label><br/>
				<input type="url" class="desc2" id="xmlurl{i}" name="xmlurl" value="{xmlurl}" />
				<a href="javascript:open('{xmlurl}')" title="Open the feed in a new window" onclick="window.open('{xmlurl}'); return false;"><img class="icon" src="res/img/feed.png" alt="RSS/ATOM feed"/></a>
				<br/>
				<label for="description{i}">Description</label><br/>
				<textarea rows="2" class="desc" id="description{i}" name="description">{description}</textarea><br/><br/>
			</div>
			<div>
				<div><label for="tags{i}">Tag(s):</label><br/><input name="tags" id="tags{i}" type="text" size="20" value="{tags}"/></div>
				<div><label for="shownitems{i}">Displayed items in feed view:</label><br/><input name="shownitems" id="shownitems{i}" type="text" pattern="[0-9]*" size="4" value="{shownitems}"/></div>
				<div><label for="position{i}">position:</label> <input name="position" id="position{i}" type="text" size="3" pattern="[0-9]*" value="{position}"/></div>

				<div class="savepanel">
					<input type="button" name="save" value="Save" onclick="saveChannel('{i}', this.form); return false;"/>&nbsp;
					<input type="reset" name="reset" value="Reset"/>
					<div id="opresult{i}" class="opresult">
					</div>
				</div>
			</div>
		</form>
	</div>
</div>
EOD;

		foreach($subs as $id => $sub) {
			$tempdata = '';

			/* first let's do the name line */
			$tempdata = str_replace("{i}", $sub->id, $namehtmldata);
			//$sub->title;
			$class = $sub->isActive?'subscribed':'unsubscribed';
			$tempdata = str_replace("{class}", $class, $tempdata);
			$tempdata = str_replace("{chantitle}", $sub->title, $tempdata);
			$tempdata = str_replace("{chantitle_enc}", addslashes($sub->title), $tempdata);
			$tempdata = str_replace("{htmlurl}", htmlentities($sub->link), $tempdata);

			$tempdata = str_replace("{xmlurl}", htmlspecialchars($sub->xmlurl), $tempdata);
			$tempdata = str_replace("{description}", $sub->description, $tempdata);
			$tempdata = str_replace("{position}", $sub->position, $tempdata);
			$tempdata = str_replace("{shownitems}", $sub->shownItems, $tempdata);
			$tempdata = str_replace("{tags}", implode(',', $sub->tags), $tempdata);
			if ($sub->isActive) {
				$tempdata = str_replace('{isactive}', 'checked="checked"', $tempdata);
			} else {
				$tempdata = str_replace('{isactive}', '', $tempdata);
			}


			echo $tempdata;
		}
	} else {
		echo "Subscription list empty";
	}
}



// if we come from the zebrabar, zflist is in the _GET array
/*if (isset($_GET['zflist']) && $_GET['zflist']!='') {
	$_POST['zflist'] = $_GET['zflist'];
}*/

$storage = SubscriptionStorage::getInstance();

//----------------------------------------------------------------------------
/* display channels list, with list control form on top */


?>
	<div id="core">

<?php
		$subs = $storage->getSubscriptions();
		if (ZF_SORT == 'feed' ) {
			$sortedChannels = sortChannelsByPosition($subs);
		} else {
			$sortedChannels = sortChannelsByName($subs);
		}
?>
		<script type="text/javascript">
			var currentid = -1;
			/* 0: id; 1:title; 2; subscribed */
			var channeldata = new Array(3);
			var savedtitle  ='';
			var savedid = -1;
			var savebutton;
			function showEditForm(id) {
					if (currentid != -1) {
						toggleVisibleById('editform' + currentid);
					}
					if (currentid == id) {
						currentid= -1;
					} else {
						currentid = id;
						toggleVisibleById('editform' + currentid);

					}
					//document.getElementById('title' + currentid).scrollIntoView(true);
					document.getElementById('editform' + currentid).top = 25;

				/* hide the last operation result */
				document.getElementById('opresult'+currentid).style.display = 'none';
				/* make sure its save button is enabled*/
				//savebutton = document.getElementById('editform' + currentid).elements["save"];
				//savebutton.disabled = false;
			}

			function toggleChecks() {
				void(d=document);
				void(el=d.getElementsByTagName('INPUT'));
				for(i=0;i<el.length;i++) {
					if(document.subscriptionsform.checkboxall.checked==1) {
						void(el[i].checked=1)
					} else {
						void(el[i].checked=0)
					}
				}
			}


			function saveChannel(id, aform) {
				var title  = encodeURIComponent(aform.elements["chantitle"].value);
				var xmlurl = encodeURIComponent(aform.elements["xmlurl"].value);
				var description = encodeURIComponent(aform.elements["description"].value);

				var position = aform.elements["position"].value;
				if (aform.elements["isactive"].checked) {
					var isactive = 'yes';
				} else {
					var isactive = 'no';
				}
				var shownitems = aform.elements["shownitems"].value;
				var tags = aform.elements["tags"].value;

				savebutton = aform.elements["save"];
				savebutton.disabled = true;

				var statusbox = document.getElementById('opresult'+id);
				statusbox.style.display = 'none';
				statusbox.innerHTML = '';

				// keep title and id in memory to update them later
				// flawed if we have a fast updater user
				channeldata[0] = id;
				channeldata[1] = aform.elements["chantitle"].value;
				channeldata[2] = isactive;

				http.open('POST', 'index.php', true);
				http.setRequestHeader("Content-type","application/x-www-form-urlencoded");

				http.onreadystatechange = onSaveCallback;
				var query= "zfaction=store&id=" + id
								+ "&title=" + title
								+ "&xmlurl=" + xmlurl
								+ "&description=" + description
								+ "&position=" + position
								+ "&isactive=" + isactive
								+ "&shownitems=" + shownitems
								+ "&tags=" + tags;
				http.send(query);
			}

			function onSaveCallback() {
				if (http.readyState == 4) { // Complete
					if (http.status == 200) { // OK response
						var statusbox = document.getElementById('opresult'+channeldata[0]);
						statusbox.innerHTML = http.responseText;
						statusbox.style.display = 'inline-block';
						savebutton.disabled = false;
						/* update channel list */
						var titletext = document.getElementById('title'+channeldata[0]);
						titletext.innerHTML = channeldata[1];
						if (channeldata[2] == 'yes') {
							titletext.className = 'link subscribed';
						} else {
							titletext.className = 'link unsubscribed';
						}
					}
				}
			}

			function removeChannel(id, title) {
				if (!confirm('Are you sure you want to remove this subscription?')) {
					return false;
				}
				http.open('POST', 'index.php', true);
				http.setRequestHeader("Content-type","application/x-www-form-urlencoded");

				channeldata[0] = id;
				channeldata[1] = title;
				http.onreadystatechange = onRemoveCallback;
				var query= "zfaction=delete&id=" + id;
				http.send(query);
			}
			function onRemoveCallback() {
				if (http.readyState == 4) { // Complete
					if (http.status == 200) { // OK response
						// TODO: handle error
						var uielement = document.getElementById('entry'+channeldata[0]);
						uielement.innerHTML= channeldata[1] + ' <i>removed</i>';
						// parentNode.removeChild(uielement);
					}
				}
			}


		</script>
		<script type="text/javascript" src="pub/zfclientside.js"></script>
		<div class="frame">
			<div id="listsettings">
		<?php
			echo "<a href=\"" . $_SERVER['PHP_SELF'] . "?zfaction=addnew\">Add new</a> :: ";
			//echo "<a href=\"" . $_SERVER['PHP_SELF'] . "?zfaction=importlist\">Import feed list</a> :: ";
			// for later <a href=" echo $storage->getOPMLURL(); ">Export OPML file</a>
		?>
			</div>
				<div id="chanlist">
					<?php displayChannelList($sortedChannels);?>
				</div>
		</div>
	</div>
<?php
