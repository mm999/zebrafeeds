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

if (zfAuth()==false) exit;

/* we would need this to clear the history file */
//require_once($zf_path . 'includes/history.php');

function sortChannelsByName($subscriptions) {
	/* sort channels list by setting the array key to the position */
	$sortedchannels = array();
	foreach($subscriptions as $i => $sub) {
		if ($sub->channel->xmlurl != '') {
			/* tackle duplicate names */
			if (isset($sortedchannels[$sub->channel->title]) || (strlen($sub->channel->title)) == 0) {
				$title = $sub->channel->title. ' ('.$sub->position.')';
			} else {
				$title = $sub->channel->title;
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
		if ($sub->channel->xmlurl != '') {
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
<div title="Edit feed properties" class="sub-line">
	<input type="checkbox" name="actionbox{i}" value="checkbox"/>
		<span id="title{i}" class="{class} link" onclick="showEditForm('{i}'); return false;">{chantitle}</span>&nbsp;
	<a href="javascript:open('{htmlurl}')" title="Open the publisher site in a new window" onclick="window.open('{htmlurl}'); return false;"><img src="{zfurl}/images/extlink.png" alt="website"/></a>
</div>
EOD;

		foreach($subs as $id => $sub) {
			$tempdata = '';

			/* first let's do the name line */
			$tempdata = str_replace("{i}", $sub->channel->id, $namehtmldata);
			//$sub->channel->title;
			$class = $sub->isSubscribed?'subscribed':'unsubscribed';
			$tempdata = str_replace("{zfurl}", ZF_URL, $tempdata);
			$tempdata = str_replace("{class}", $class, $tempdata);
			$tempdata = str_replace("{chantitle}", $sub->channel->title, $tempdata);
			$tempdata = str_replace("{htmlurl}", htmlentities($sub->channel->link), $tempdata);
			echo $tempdata;
		}
	} else {
		echo "Subscription list empty";
	}
}

/* function to be called while displaying the channel edit form
arg : channel, an array of feeds (got from opml functions)
*/
function displayChannelEditForm($subs) {

	$channelcount = count($subs);

	if ($channelcount > 0) {

		$formhtmldata = <<<EOD
<div class="editfeed" id="editform{i}" style="display:none;">
	<form action="#">
		<div>
			<label for="chantitle{i}">Title:</label>&nbsp;<br/>
			<input type="text" size="50" name="chantitle" id="chantitle{i}" value="{chantitle}" /><br/><br/>
			<label for="xmlurl{i}"> feed URL:</label>			<a href="javascript:open('{xmlurl}')" title="Open the feed in a new window" onclick="window.open('{xmlurl}'); return false;"><img src="{zfurl}/images/feed.png" alt="RSS/ATOM feed"/></a>
<br/>
			<input type="text" size="50" id="xmlurl{i}" name="xmlurl" value="{xmlurl}" />
			<br/><br/>
			<label for="description{i}">Description</label><br/>
			<textarea rows="2" cols="30" id="description{i}" name="description">{description}</textarea><br/><br/>
		</div>
		<div class="twocols">
			<div class="col1"><label for="issubscribed{i}">Subscribed</label> </div>
			<div class="col2"><input type="checkbox" id="issubscribed{i}" name="issubscribed" {issubscribed} value="Subscribed" title="Subscribed to this feed"/></div>
			<div class="col1"><label for="position{i}">Position:</label> </div>
			<div class="col2"><input name="position" id="position{i}" type="text" size="3" value="{position}"/></div>
			<div class="col1"><label for="refreshtime{i}">Refresh time:</label> </div>
			<div class="col2"><input name="refreshtime" id="refreshtime{i}" type="text" size="4" value="{refreshtime}"/>&nbsp;minutes</div>
			<div class="col1"><label for="showeditems{i}">Displayed items:</label></div>
			<div class="col2"><input name="showeditems" id="showeditems{i}" type="text" size="4" value="{showeditems}"/></div>

			<div class="savepanel">
				<input type="button" name="save" value="Save" onclick="saveChannel('{i}', this.form); return false;"/>&nbsp;
				<input type="reset" name="reset" value="Reset"/>
				<div id="opresult{i}" class="opresult">
				</div>
			</div>
		</div>
	</form>
</div>
EOD;

		foreach($subs as $id => $sub) {
			/* then the form */
			$tempdata = '';
			$tempdata = str_replace("{i}", $sub->channel->id, $formhtmldata);

			$tempdata = str_replace("{zfurl}", ZF_URL, $tempdata);
			$tempdata = str_replace("{xmlurl}", htmlspecialchars($sub->channel->xmlurl), $tempdata);
			$tempdata = str_replace("{description}", $sub->channel->description, $tempdata);
			$tempdata = str_replace("{chantitle}", $sub->channel->title, $tempdata);
			$tempdata = str_replace("{position}", $sub->position, $tempdata);
			$tempdata = str_replace("{refreshtime}", $sub->refreshTime, $tempdata);
			$tempdata = str_replace("{showeditems}", $sub->shownItems, $tempdata);
			if ($sub->isSubscribed) {
				$tempdata = str_replace('{issubscribed}', 'checked="checked"', $tempdata);
			} else {
				$tempdata = str_replace('{issubscribed}', '', $tempdata);
			}
			echo $tempdata;
		}
	}
}


// if we come from the zebrabar, zflist is in the _GET array
/*if (isset($_GET['zflist']) && $_GET['zflist']!='') {
	$_POST['zflist'] = $_GET['zflist'];
}*/

// first thing to do: load the current list file in memory
$currentListName = zf_getCurrentListName();

if (!empty($currentListName)) {
	$list = new opml($currentListName);
}

/* ------------------
handling of actions
--------------------*/
// all results formated in a block

/* save: not used anymore
*/
if ( ($_POST['save'] == 'save changes') || ($_POST['save2'] == 'save changes') ) {

	echo '<div id="core">';
	if ($list->load()) {

		$list->viewMode = $_POST["zfviewmode"];
		$list->trimtype = $_POST["zftrimtype"];
		$list->trimSize = $_POST["zftrimsize"];
		if ($list->save()) {
			displayStatus($list->lastResult);
		} else {
			displayStatus($list->lastError);
		}
		displayGotoButton($list->name);
	}
	echo '</div>';
//----------------------------------------------------------------------------
/* delete feeds */
} elseif ($_POST['delete'] == 'delete') {
	echo '<div id="core">';
	if ($list->load()) {
		$done = 0;
		// make a copy
		//$newList = $list;
		echo "Deleting feeds <ul>";
		$initialCount = count($list->subscription);
		for($i = 0;$i < $initialCount; $i++) {
			if ($_POST["actionbox$i"] == 'checkbox') {
				echo '<li>'.$list->subscriptions[$i]->channel->title.'</li>';
				/*also delete the history file
				$hst = new history($list->channels[$i]['xmlurl']);
				$hst->delete();*/
				$list->removeChannelAtPos($i);
			   $done++;
			}
		}
		echo "</ul>";
		if ($done > 0) {
			if ($list->save()) {
				displayStatus($list->lastResult);
			} else {
				displayStatus($list->lastError);
			}
			displayGotoButton($list->name);
		} else {
			displayStatus("No channel selected");
		}
	} else {
		displayStatus("Error opening the subscription list for reading !");
	}
	echo '</div>';
	//----------------------------------------------------------------------------
	/* create list */
} elseif ($_POST['createlist'] == 'Create new list...') {
	echo '<div id="core">';
	$list = new opml($_POST['newlistname']);
	if ($list->create()) {
		displayStatus($list->lastResult);
		displayGotoButton($list->name);
	} else {
		displayStatus($list->lastError);
	}
	echo '</div>';
//----------------------------------------------------------------------------
/* delete list */
} elseif ($_POST['deletelist'] == 'Delete current list') {
	echo '<div id="core">';
	if ($list->delete()) {
		displayStatus($list->lastResult);
		displayGotoButton();
	} else {
		displayStatus($list->lastError);
		displayGotoButton($list->name);
	}
	echo '</div>';
//----------------------------------------------------------------------------
/* rename list */
} elseif ($_POST['renamelist'] == 'Rename list...') {
	echo '<div id="core">';
	if ($list->rename($_POST['listnewname'])) {
		displayStatus($list->lastResult);
		displayGotoButton($_POST['listnewname']);
	} else {
		displayStatus($list->lastError);
		displayGotoButton($list->name);
	}
	echo '</div>';
//----------------------------------------------------------------------------
/*copy feeds to another list */
} elseif ($_POST['move'] == 'move') {
	echo '<div id="core">';
	$targetList = new opml($_POST['zfdestlist']);

	if ($list->load() && $targetList->load()) {

		$done = 0;
		$nextpos = $targetList->getNextPosition();

		/* see which feeds have been selected */
		echo "Moving channels to <strong>".$targetList->name."</strong><ul>";
		$initialCount = count($list->subscriptions) ;

		for($i = 0;$i < $initialCount;$i++) {
			if ($_POST["actionbox$i"] == 'checkbox') {
				$movingSub = $list->subscriptions[$i];
				echo '<li>'.$movingSub->channel->title.'</li>';
				$movingSub->position = $nextpos++;
				$targetList->addSubscription($movingSub);
				$list->removeChannelAtPos($i);
				$done++;
			}
		}
		echo "</ul>";

		if ($done > 0) {
			if ($targetList->save()) {
				if ($list->save()) {
					displayStatus ($targetList->lastResult.'<br/>'.$list->lastResult);
					displayGotoButton($targetList->name);
				} else {
					displayStatus($list-lastError);
					displayGotoButton($list->name);
				}

			} else {
				displayStatus($targetList->lastError);
				displayGotoButton($targetList->name);
			}
		} else {
			displayStatus("No channel selected");
		}

	} else {
		displayStatus("Error opening the subscription list(s) for reading !");
	}
	echo '</div>';

//----------------------------------------------------------------------------
/* copy feeds to another list */
} elseif ($_POST['copy'] == 'copy') {
	echo '<div id="core">';
	$targetList = new opml($_POST['zfdestlist']);

	if ($list->load() && $targetList->load()) {

		$done = 0;
		$nextpos = $targetList->getNextPosition();

		/* see which feeds have been selected */
		echo 'Copying channels to <strong>'.$targetList->name."</strong><ul>";
		$initialCount = count($list->channels) ;

		for($i = 0;$i < $initialCount;$i++) {
			if ($_POST["actionbox$i"] == 'checkbox') {
				$movingChannel = $list->channels[$i];
				echo '<li>'.$movingChannel['title'].'</li>';
				$movingChannel['position'] = $nextpos++;
				$targetList->channels[] = $movingChannel;
				$done++;
			}
		}
		echo '</ul>';

		if ($done > 0) {
			if ($targetList->save()) {
				displayStatus($targetList->lastResult);
				displayGotoButton($targetList->name);
			} else {
				displayStatus($targetList->lastError);
				displayGotoButton($targetList->name);
			}
		} else {
			displayStatus("No channel selected");
		}

	} else {
		displayStatus("Error opening the subscription list(s) for reading !");
	}

	echo '</div>';

} else {
//----------------------------------------------------------------------------
/* default case : display channels list, with list control form on top */


?>
	<div id="core">
	<div id="listsform">
		<form name="zflists" action="<?php echo $_SERVER['PHP_SELF'];?>?zfaction=subscriptions" method="post">
			<?php
				if (!empty($currentListName)) {

				?>
				Display list:
			<select name="zflist" onchange="this.form.submit();">
				<?php echo zf_ListsFormElements($currentListName); ?>
			</select>
			&nbsp;
			<input name="deletelist" type="submit" id="deletelist" value="Delete current list" onclick="return confirm('Are you sure you want delete the list: <?php echo $currentListName;?>?')"/>
			<input name="listnewname" type="hidden" id="listnewname"/>
		<input name="renamelist" type="submit" id="renamelist" value="Rename list..." onclick="var name = prompt('New name', '<?php echo $currentListName; ?>'); if (name) {document.getElementById('listnewname').value = name; return true;} else {return false;}"/>
			&nbsp;
			<a href="<?php echo $list->getURL(); ?>">Export OPML file</a>
			<?php
				} else {
					echo "No list available ";
				}
			?>
			&nbsp;
			<input name="newlistname" type="hidden" id="createlistname"/>
			<input name="createlist" type="submit" id="createlist" value="Create new list..." onclick="var name = prompt('Name of the new list'); if (name) {document.getElementById('createlistname').value = name; return true;} else {return false;}"/>
	</form>
	</div>


<?php
	if ($list->load() ) {
		if ($list->viewMode == 'feed' ) {
			$sortedChannels = sortChannelsByPosition($list->subscriptions);
		} else {
			$sortedChannels = sortChannelsByName($list->subscriptions);
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

			function onUpdateViewMode(index) {
				switch (index) {
					case 1:
						document.getElementById('trimoptions').style.display='inline';
						break;

					default:
						document.getElementById('trimoptions').style.display='none';
						break;
				}
			}


			function saveChannel(id, aform) {
				var title  = encodeURIComponent(aform.elements["chantitle"].value);
				var xmlurl = encodeURIComponent(aform.elements["xmlurl"].value);
				var description = encodeURIComponent(aform.elements["description"].value);

				var position = aform.elements["position"].value;
				if (aform.elements["issubscribed"].checked) {
					var issubscribed = 'yes';
				} else {
					var issubscribed = 'no';
				}
				var showeditems = aform.elements["showeditems"].value;
				var refreshtime = aform.elements["refreshtime"].value;

				savebutton = aform.elements["save"];
				savebutton.disabled = true;

				var statusbox = document.getElementById('opresult'+id);
				statusbox.style.display = 'none';
				statusbox.innerHTML = '';

				// keep title and id in memory to update them later
				// flawed if we have a fast updater user
				channeldata[0] = id;
				channeldata[1] = aform.elements["chantitle"].value;
				channeldata[2] = issubscribed;

				http.open('POST', 'cmd.php', true);
				http.setRequestHeader("Content-type","application/x-www-form-urlencoded");

				http.onreadystatechange = onSaveCallback;
				var query= "action=savechannel&id=" + id
								+ "&list=" + encodeURIComponent('<?php echo $currentListName; ?>')
								+ "&title=" + title
								+ "&xmlurl=" + xmlurl
								+ "&description=" + description
								+ "&position=" + position
								+ "&issubscribed=" + issubscribed
								+ "&shownitems=" + showeditems
								+ "&refreshtime=" + refreshtime;
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

		</script>
		<script type="text/javascript" src="../zfcontrol.js"></script>
		<script type="text/javascript" src="../zfclientside.js"></script>
		<div class="frame">
			<form name="subscriptionsform" action="<?php echo $_SERVER['PHP_SELF'] . '?zfaction=subscriptions';?>" method="post">
				<input name="zflist" type="hidden" id="zflist" value="<?php echo $currentListName;?>"/>
				<div id="listsettings">
					Default view
					<select name="zfviewmode" onchange="index=this.selectedIndex; onUpdateViewMode(index);">
						<option value="feed" <?php echo ($list->viewMode == 'feed') ? 'selected="selected"' : '';?> >By channel</option>
						<option value="trim" <?php echo ($list->viewMode == 'trim') ? 'selected="selected"' : '';?> >View only last...</option>
						<option value="date" <?php echo ($list->viewMode == 'date') ? 'selected="selected"' : '';?> >By date - view all news</option>
					</select>
					<div id="trimoptions" style="display: <?php echo ($list->viewMode == 'trim')? 'inline' : 'none'; ?>">
						<input name="zftrimsize" type="text" size="3" value="<?php echo $list->trimSize; ?>"/>
						<select name="zftrimtype">
							<option value="days" <?php echo ($list->trimType == 'days') ? 'selected="selected"' : '';?> >days</option>
							<option value="news" <?php echo ($list->trimType == 'news') ? 'selected="selected"' : '';?> >news</option>
							<option value="hours" <?php echo ($list->trimType == 'hours') ? 'selected="selected"' : '';?> >hours</option>
						</select>
					</div>
					<div class="listctrl">
					<input name="save" type="submit" id="save" value="save changes"/>
					<?php if (defined('ZF_HOMEURL') && strlen(ZF_HOMEURL)>0) echo '<a href="',ZF_HOMEURL,'?zflist=',urlencode(zf_getCurrentListName()),'">View website</a>';?>
					</div>
				</div>
				<div id="chancolumn">
					<div id="chancolumnheader">
					<?php echo ($list->viewMode == 'feed')?'<span class="chanlistcheck">
						<small><em>Sorted by position</em></small> </span><br/><br/>':'';?>
						<input type="checkbox" name="checkboxall" value="checkbox" title="check/uncheck all" onclick="Javascript:toggleChecks()"/> <em>toggle all</em>
					</div>
				<div id="chanlist">

						<?php displayChannelList($sortedChannels);?>
					</div>

				</div>
				<?php	if (count($sortedChannels) > 0) { ?>

					<div>
						<strong>Selection:</strong>
						<div>
					<?php  $liststr = listExceptCateg($currentListName);
					  if (strlen($liststr) > 0) { ?>

							=&gt; To list&nbsp;
							<select name="zfdestlist">
							<?php
								echo listExceptCateg($currentListName);
							?>
							</select>
							<input name="copy" type="submit" id="copy" value="copy" onclick="if (!confirm('Are you sure you want to copy the selected feeds?')) {return false;}"/>
							<input name="move" type="submit" id="move" value="move" onclick="if (!confirm('Are you sure you want to move the selected feeds?')) {return false;}"/>

							<div style="display: inline-block; margin-left: 40px;">
							<input name="delete" type="submit" id="delete" value="delete" onclick="if (!confirm('Are you sure you want to delete the selected feeds?')) {return false;}"/>
							</div>
						</div>
					 <?php } ?>
					</div>
				<?php } ?>
			</form>
			<div id="editcolumn" >
				<?php displayChannelEditForm($sortedChannels);?>
			</div>

		</div>
	</div>
<?php
	} else {
			echo '<div id="core">';
			displayStatus($list->lastError);
			echo '</div>';
	}
 }
?>
