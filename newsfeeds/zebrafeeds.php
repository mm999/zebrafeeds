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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	 See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//
//
// ZebraFeeds main


require_once('init.php');
require_once($zf_path . 'includes/aggregator.php');
require_once($zf_path . 'includes/controller.php');

if (strlen(ZF_URL) == 0) {
	echo "ZebraFeeds is not properly configured!<br/>";
	exit;
}

global $zf_aggregator;
if (!isset($zf_aggregator)) {
	$zf_aggregator = new aggregator();
}


zf_debug("Aggregator loaded");

// record the time of the visit, server side mode. WHY???
$zf_aggregator->recordServerVisit();


/* === 2: find out query type and main parameters =====*/
if (isset($_GET['q'])) {
	$q = $_GET['q'];

	if ($q=='article') {
		$channelId = isset($_GET['id']) ? $_GET['id'] : -1;
		$itemId = isset($_GET['itemid']) ? $_GET['itemid'] : -1;

		$zf_aggregator->useDefaultTemplate();
		$zf_aggregator->printArticle($channelId, $itemId);

		exit;
	}

}

if (ZF_RENDERMODE == 'automatic') {
	zf_debug("Using automatic mode");
	$zf_aggregator->useDefaultTemplate();
	$zf_aggregator->useDefaultTag();
	$zf_aggregator->printMainView();


} else {
	// ZF_MODE set to manual: output will be configured by whoever is integrating
	//the script on their site
	zf_debug("Using manual mode");
}

