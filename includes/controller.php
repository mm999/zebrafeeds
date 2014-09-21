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


/*
parameters reference: see reference documentation page
*/



/* main routing function */
function handleRequest() {

	$type = param('q', 'tag');
	$outputType = param('f','html');


	switch ($type) {

		case 'item':

			//refresh: always from cache
			$zf_aggregator = new Aggregator();
			$item = $zf_aggregator->getItem(param('id'), param('itemid'));
			$view = zf_createView($outputType);
			$view->renderArticle($item);
			break;

		case 'download-item':

			//refresh: always from cache
			$zf_aggregator = new Aggregator();
			$item = $zf_aggregator->downloadItem(param('id'), param('itemid'));
			$view = zf_createView($outputType);
			$view->renderArticle($item);
			break;

		case 'summary':

			//refresh: always from cache
			$zf_aggregator = new Aggregator();
			$item = $zf_aggregator->getItem(param('id'), param('itemid'));
			$view = zf_createView($outputType);
			$view->renderSummary($item);
			break;



		case 'channel':

			//refresh: user defined
			$zf_aggregator = new Aggregator();
			$feed = $zf_aggregator->getChannelFeed(
					param('id'),
					param('mode', 'auto'),
					param('trim', 'auto'),
					int_param('onlynew', 0)
					);
			$view = zf_createView($outputType);
			$view->renderFeed($feed, array(
				'groupbyday' => false,
				'decoration' => int_param('decoration'),
				'summary' => (param('sum',1)==1)));
			break;



		case 'tag':

			//refresh: always auto refresh for tag view

			// if html output & sorted by feed, trim every single item
			// according to subcription's settings
			$sort = param('sort', ZF_SORT);

			if ($sort == 'feed' && strstr($outputType, 'html')) {
				$groupbyday = false;
				$aggregate = false;
			} else {
				// otherwise just aggregate in a single feed
				$groupbyday = true;
				$aggregate = true;
			}

			$zf_aggregator = new Aggregator();
			$tag = param('tag',ZF_HOMETAG);
			$feeds = $zf_aggregator->getFeedsForTag(
				$tag,
				$aggregate,
				$trim =  param('trim', 'auto'),
				int_param('onlynew', 0));

			zf_debugRuntime("before rendering");

			$view = zf_createView($outputType);
			$view->renderFeedList($feeds, array(
				'groupbyday' => $groupbyday,
				'summary' => (int_param('sum', 1)==1),
				'tag' => $tag));
			break;



		case 'subs':

			//only JSON
			$subs = SubscriptionStorage::getInstance()->getSortedActiveSubscriptions(param('tag',''));
			if (!headers_sent()) header('Content-Type: application/json; charset='.ZF_ENCODING);
			echo json_encode($subs);
			break;


		case 'tags':

			$tags = SubscriptionStorage::getInstance()->getTags();
			if (!headers_sent()) header('Content-Type: application/json; charset='.ZF_ENCODING);
			echo json_encode($tags);
			break;


		case 'force-refresh':

			// only internal use
			// TODO: check API key
			$sub = SubscriptionStorage::getInstance()->getSubscription(param('id'));
			FeedCache::getInstance()->updateSingle($sub);
			echo $sub->title. ' DONE. ';
			break;

		case 'refresh-all':

			$subs = SubscriptionStorage::getInstance()->getActiveSubscriptions(param('tag',''));
			FeedCache::getInstance()->update($subs);
			echo ' DONE. ';
			break;

	}

}

function zf_createView($outputType) {

	if (strstr($outputType,'html')) {
		$view = new TemplateView(param('zftemplate', ZF_TEMPLATE), $outputType == 'innerhtml');
	} else {
		$view = new JSONView();
	}
	return $view;

}

