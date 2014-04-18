<?php
// ZebraFeeds - copyright (c) 2006 Laurent Cazalet
// http://www.cazalet.org/zebrafeeds
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//
//
// ZebraFeeds user functions.
// For the case where ZF_USEOPML is set to no.
// allow to manually configure the output

if (!defined('ZF_VER')) exit;

require_once($zf_path . 'includes/aggregator.php');


/* 4 functions borrowed from PicoFarad - by F. Guillot (author of Miniflux) */
function param($name, $default_value = null)
{
	return isset($_GET[$name]) ? $_GET[$name] : $default_value;
}


function int_param($name, $default_value = 0)
{
	return isset($_GET[$name]) && ctype_digit($_GET[$name]) ? (int) $_GET[$name] : $default_value;
}


function value($name)
{
	$values = values();
	return isset($values[$name]) ? $values[$name] : null;
}


function values()
{
	if (! empty($_POST)) {

		return $_POST;
	}

	$result = json_decode(body(), true);

	if ($result) {
		return $result;
	}

	return array();
}

function content_type($mimetype)
{
	header('Content-Type: '.$mimetype);
}


/* main routing function */
function handleRequest() {

	global $zf_aggregator;

	$type = param('q', 'tag');
	$channelId = param('id');
	$itemId = param('itemid');
	$sum = int_param('sum');
	$tag = param('tag','');
	$trim = param('trim', 'auto');
	$outputType = param('f','html');
	$template = param('zftemplate', ZF_TEMPLATE);



	$zf_aggregator = new Aggregator();
	zf_debug("Aggregator loaded");

	// record the time of the visit, server side mode. WHY???
	$zf_aggregator->recordServerVisit();

	if ($outputType =='html') {
		$zf_aggregator->useTemplate($template);
		header('Content-Type: text/html; charset='.ZF_ENCODING);
	} else {
		$zf_aggregator->useJSON();
		header('Content-Type: application/json; charset='.ZF_ENCODING);
	}


	switch ($type) {

		case 'article':
		case 'item':
			//refresh: from cache always
			$zf_aggregator->useSubscription($channelId, 'cache');
			$zf_aggregator->printArticle($channelId, $itemId);
			break;


		case 'channel':
			//refresh: user defined
			if ($trim != 'auto') $zf_aggregator->setTrimString($trim);

			$mode = param('mode', 'auto');

			$zf_aggregator->useSubscription($channelId, $mode);
			// channel with header and items, auto cache/refresh
			$zf_aggregator->printSingleFeed($sum==1);
			break;

		case 'tag':
			//refresh: auto refresh always
			if ($trim!='auto') $zf_aggregator->setTrimString($trim);
			$zf_aggregator->printTaggedFeeds($tag);
			break;

		case 'summary':
			//refresh: from cache always
			$zf_aggregator->printSummary($channelId, $itemId);
			break;

		case 'subs':
			$sortedchannels = array();
			$subs = SubscriptionStorage::getInstance()->getActiveSubscriptions($tag);
			foreach( $subs as $i => $subscription) {
				if ($subscription->isActive) {
					$sortedchannels[$subscription->position] = $subscription;
					//Why this??? $subscription->opmlindex = $i;
				}
			}
			ksort($sortedchannels);
			echo json_encode($sortedchannels);
			break;

		case 'tags':

			$tags = SubscriptionStorage::getInstance()->getTags();
			echo json_encode($tags);
			break;



	}



}


