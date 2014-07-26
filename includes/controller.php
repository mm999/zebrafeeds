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
parameters dictionary
======================
q : query type. Values:
 - item: a single new item, with article view
 - channel: we want channel news, sorted by date
 - tag: feeds tagged with a certain tag
 - subs (subs being tagged with specified tag, all if no tag specified) JSON only
 - tags (all tags available in subscriptions) JSON output only
 - force-refresh: force refresh a feed to cache. No output. Only for internal use
 - refresh-all: force refresh all feeds of a tag to cache. No output. Only for internal use

other parameters
----------------
tag: use only subscription with this tag. default empty. applicable only for
	q=subs and q=tag

id: id of the channel to deal with. only for q=channel

itemid : the news item unique id for lookup. only for q=item

f: output type (json, html, innerhtml)
   if json: 'sort' param ignored. always by date, feeds aggregated
   innerhtml: won't output header section of template. suitable for JS calls
   default to html

mode: feed update mode. applicable only for q=channel
	- auto: according to config's refresh time (default)
	- none: force from cache
	- force: force refresh feed from source

sum: if 1 then summary included in news item header, 0 no summary (default)
	 Applicable only when q=channel or q=tag

trim: how to shorten the number of items when getting feeds to get only news
	  or the last hour, since 4 days...
	  valid when q=tag or q=channel
      none: return all
      auto: default, if q=channel then use Xnews (subscription setting).
                     if q=tag then use config values for trim
      <N>news
	  <N>days
	  <N>hours

onlynew: default to 0. If 1 will show only newly fetched items. Valid for q=tag or channel

sort: feed or date. only for q=tag AND for html output (f=html or innerhtml)
	  if trim is set, param ignored and forced to 'date'

decoration: if (q=channel AND f=html) only
			default to 0. if 1, will output channel header
 */




/* main routing function */
function handleRequest() {

	$type = param('q', 'tag');


	switch ($type) {

		case 'item':

			//refresh: always from cache
			$zf_aggregator = new Aggregator();
			$item = $zf_aggregator->getItem(param('id'), param('itemid'));
			$view = zf_createView();
			$view->renderArticle($item);
			break;

		case 'download-item':

			//refresh: always from cache
			$zf_aggregator = new Aggregator();
			$item = $zf_aggregator->downloadItem(param('id'), param('itemid'));
			$view = zf_createView();
			$view->renderArticle($item);
			break;

		case 'summary':

			//refresh: always from cache
			$zf_aggregator = new Aggregator();
			$item = $zf_aggregator->getItem(param('id'), param('itemid'));
			$view = zf_createView();
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
			$view = zf_createView();
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

			$view = zf_createView();
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

function zf_createView() {

	$outputType = param('f','html');
	if (strstr($outputType,'html')) {
		$view = new TemplateView(param('zftemplate', ZF_TEMPLATE), $outputType == 'innerhtml');
	} else {
		$view = new JSONView();
	}
	return $view;

}

