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
 - tag (always news by date)
 - subs (subs being tagged with specified tag, all if no tag specified)
 - tags (all tags available in subscriptions)
 - force-refresh: force refresh a feed to cache. No output. Only for internal use
 - refresh-all: force refresh all feeds of a tag to cache. No output. Only for internal use

tag: use only subscription with this tag. default empty. applicable only for
	q=subs and q=tag

id: id of the channel to deal with

itemid : the news item unique id for lookup

f: output type (json, html) default to html

tags: list of existing tags. JSON output only

subs: list of subscriptions for tag. JSON output only

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

sort: feed or date. only for q=tag
	  if trim is set, param ignored and forced to 'date'
	  only valid for html output (f=html)

decoration: if (q=channel AND f=html) only
			default to 0. if 1, will output channel header
 */




/* main routing function */
function handleRequest() {

	$type = param('q', 'tag');
	$channelId = param('id');
	$itemId = param('itemid');
	$sum = int_param('sum', 1);
	$tag = param('tag','');
	$trim = param('trim', 'auto');
	$outputType = param('f','html');
	$onlyNew = int_param('onlynew',0);

	//refresh mode
	$updateMode = param('mode', 'auto');


	if ($outputType =='html') {
		$contenttype = 'text/html';
		$template = param('zftemplate', ZF_TEMPLATE);
		$sort = param('sort', ZF_SORT);
		$view = new TemplateView($template);
	} else {
		$contenttype = 'application/json';
		$view = new JSONView();
	}
	if (!headers_sent()) header('Content-Type: '.$contenttype.'; charset='.ZF_ENCODING);

	$zf_aggregator = new Aggregator();

	switch ($type) {

		case 'item':
			//refresh: always from cache
			$item = $zf_aggregator->getItem($channelId, $itemId);
			$view->renderArticle($item);
			break;

		case 'summary':
			//refresh: always from cache
			$item = $zf_aggregator->getItem($channelId, $itemId);
			$view->renderSummary($item);
			break;



		case 'channel':
			//refresh: user defined
			$feed = $zf_aggregator->getChannelFeed($channelId, $updateMode, $trim, $onlyNew);
			$view->renderFeed($feed, array(
				'groupbyday' => false,
				'decoration' => int_param('decoration'),
				'summary' => ($sum==1)));
			break;



		case 'tag':
			//refresh: always auto refresh for tag view

			// if html output & sorted by feed, trim every single item
			// according to subcription's settings
			if ($sort == 'feed' && $outputType == 'html') {
				$groupbyday = false;
				$aggregate = false;
			} else {
				// otherwise just aggregate in a single feed
				if ($trim == 'auto') {
					$trim = ZF_TRIMSIZE.ZF_TRIMTYPE;
				}
				$groupbyday = true;
				$aggregate = true;
			}


			$feeds = $zf_aggregator->getFeedsForTag($tag, $aggregate, $trim, $onlyNew);

			zf_debugRuntime("before rendering");

			$view->renderFeedList($feeds, array(
				'groupbyday' => $groupbyday,
				'summary' => ($sum==1),
				'tag' => $tag));
			break;



		case 'subs':
			//only JSON
			$subs = SubscriptionStorage::getInstance()->getSortedActiveSubscriptions($tag);
			echo json_encode($subs);
			break;


		case 'tags':

			$tags = SubscriptionStorage::getInstance()->getTags();
			echo json_encode($tags);
			break;


		case 'force-refresh':
			// only internal use
			// TODO: check API key
			$sub = SubscriptionStorage::getInstance()->getSubscription($channelId);
			FeedCache::getInstance()->updateSingle($sub);
			echo $sub->title. ' DONE. ';
			break;

		case 'refresh-all':
			$subs = SubscriptionStorage::getInstance()->getActiveSubscriptions($tag);
			FeedCache::getInstance()->update($subs);
			echo ' DONE. ';
			break;

	}

}


