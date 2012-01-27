<?php
// ZebraFeeds - copyright (c) 2006 Laurent Cazalet
// http://www.cazalet.org/zebrafeeds
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	 See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.


require_once('init.php');

/* what are our available opml lists? generate a list of links
in the future(?) this could become themable*/

function zfb_subscriptionsLinks($separator=''){
	global $zfb_viewmode;
	$catlist = zf_getListNames();
	foreach ($catlist as $categf) {
		echo '<a href="'.$_SERVER['PHP_SELF'].'?zflist='.urlencode($categf). (empty($zfb_viewmode)?'':'&amp;zfviewmode='.$zfb_viewmode).'">'.$categf.'</a>' . $separator;
	}
}

function zfb_subscriptionsListBox(){
	global $zfb_viewmode;
	$current = zf_getCurrentListName(); 
	echo '<form action="'. $_SERVER['PHP_SELF'] .'" method="get">
			 Subscriptions list: &nbsp;
			<select name="zflist" onchange="this.form.submit();">';
	echo zf_ListsFormElements($current);
	echo '</select>';
	if (! empty($zfb_viewmode)){
		echo '<input type="hidden" name="zfviewmode" value="'.$zfb_viewmode.'"/></form>';
	}
		 
}

function zfb_viewControls($seeGroupBy=false, $seeOnly=true, $seeMatch=false){
	global $zfb_viewmode;
	$current = zf_getCurrentListName();
	
	$currentTrim = isset($_GET['zftrim'])?$_GET['zftrim']:'';
	$currentMatch = isset($_GET['zfmatch'])?$_GET['zfmatch']:'';
	
	/*echo '<form action="'. $_SERVER['PHP_SELF'] .'" method="get">
			 Group by: &nbsp;
			<select name="zfviewmode" onchange="this.form.submit();">';
	echo '<option value="feed" '.($zfb_viewmode == 'feed' ? 'selected="selected"' : '').'>Channel</option>';
	echo '<option value="date" '.($zfb_viewmode == 'date' ? 'selected="selected"' : '').'>Date</option>';
	echo '</select>';
	echo '<input type="hidden" name="zflist" value="'.$current.'"/></form>';
	*/
	if($seeGroupBy) {
		echo '| Group by: ';
		echo '<a href="'.$_SERVER['PHP_SELF'].'?zflist='.urlencode($current).'&amp;zfviewmode=feed">channel</a>';
		echo ' - ';
		echo '<a href="'.$_SERVER['PHP_SELF'].'?zflist='.urlencode($current).'&amp;zfviewmode=date">date</a>';
		echo ' - ';
		echo '<a href="'.$_SERVER['PHP_SELF'].'?zflist='.urlencode($current).'">default</a>';
	
	}
	if($seeOnly) {
		echo ' | See only: ';
		echo '<a href="'.$_SERVER['PHP_SELF'].'?zflist='.urlencode($current).'&amp;zftrim=today">Today</a>';
		echo ' - ';
		echo '<a href="'.$_SERVER['PHP_SELF'].'?zflist='.urlencode($current).'&amp;zftrim=onlynew">Only new</a>';
		echo ' - ';
		echo '<a href="'.$_SERVER['PHP_SELF'].'?zflist='.urlencode($current).'&amp;zftrim=none">Unfiltered</a>';
	}
	if($seeMatch) {
		echo '<div><form action="'. $_SERVER['PHP_SELF'] .'" method="get">
				 News matching: &nbsp;
				<input type="text" size="10" name="zfmatch" value="'.$currentMatch.'"/>
		<input type="hidden" name="zflist" value="'.$current.'"/>';
		if (!empty($currentTrim)) {
			echo '<input type="hidden" name="zftrim" value="'.$currentTrim.'"/>';
		}
		echo '<input type="submit" value="match"/>';
		echo '</form></div>';
	}
 }

function zfb_adminLink() {
	echo '<a href="'.ZF_URL.'/admin/index.php">Admin panel</a>';
}

function zfb_editSubscriptionsLink() {
	echo '<a href="'.ZF_URL.'/admin/index.php?zfaction=subscriptions&amp;zflist='.urlencode(zf_getCurrentListName()).'">Manage subscriptions</a><br/>';
		 
}


/* find out which is the current sort order, and which one is the alternate 
we cant do that if some day we do ajax refreshing of the feeds area
so we should store the current sort order in a cookie

if(isset($_GET['zfviewmode']) ) {
  $zfb_viewmode = $_GET['zfviewmode'];
} else {
  $zfb_viewmode = '';
}
*/
//zfb_subscriptionsLinks(' - ');
//zfb_viewControls();
//zfb_adminBox();



?>
