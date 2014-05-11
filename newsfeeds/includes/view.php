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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	 See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.


// ZebraFeeds view class


/*
 this is the central class of rendering a newsfeed using a template
 it expresses the fact that channel feeds and virtual
 feeds render the same way. The only option here, is to group by day or not
 */
if (!defined('ZF_VER')) exit;


abstract class AbstractFeedView {

	abstract public function renderFeed($feed, $params);
	abstract public function renderFeedList($feeds, $params);
	abstract public function renderArticle($item);
	abstract public function renderSummary($summary);
}


class JSONView extends AbstractFeedView {


	public function renderFeed($feed, $params) {

		$out = array();
		// foreach item of the feed
		$items = $feed->getItems();
		foreach ($items as $item) {
			//   add JSON friendly object to array
			$classname = get_class($feed);
			switch ($classname) {
				case 'PublisherFeed':
					// get short header without publisher
					$out[] = $item->getSerializableHeader($params['summary']);
					break;
				case 'AggregatedFeed':
					// get full header with publisher info
					$out[] = $item->getFullSerializableHeader($params['summary']);
			}
		}

		echo json_encode($out);

	}

	public function renderFeedList($feeds, $params) {
		$feed = array_pop($feeds);
		$this->renderFeed($feed, $params);
	}

	public function renderArticle($item) {
		echo json_encode($item->getSerializableItem());
	}

	public function RenderSummary($summary) {
		echo json_encode($summary);
	}


}



class TemplateView extends AbstractFeedView{


	/* this property is used when currently rendering a particular feed
	it's a Feed object	  */

	protected $template;

	public function __construct($templateName) {
		$this->template = new template($templateName);
	}


	/* render the view,
	  made of an unique "feed" if grouped by date"
	  or made of multiple single feeds if grouped by channel
	at this point, items are supposed to be filtered */
	public function renderFeed($feed, $params) {
		zf_debug('Rendering '.(isset($feed->subscriptionId)?$feed->subscriptionId:'aggregated feed').' in TemplateView', DBG_RENDER);

		if ($params['decoration'] == 1 ) {
			$this->template->printChannel($feed);
		}
		$this->renderNewsItems($feed, $params);

		if ($params['decoration'] == 1 ) {
			$this->template->printChannelFooter();
		}
	}

	public function renderFeedList($feeds, $params) {
		$this->template->printHeader();
		// if only one item: no header or footer to print
		if (!isset($params['decoration'])) {
			$params['decoration'] = (sizeof($feeds)>1)?1:0;
		}
		foreach($feeds as $feed) {
			$this->renderFeed($feed, $params);
		}

		$this->template->printFooter();
		$this->template->printErrors();
		$this->template->printCredits();


	}

	/* print only news items, no header */
	protected function renderNewsItems($feed, $params) {

		zf_debug('Rendering Newsitems of '.(isset($feed->subscriptionId)?$feed->subscriptionId:'aggregated feed').' in TemplateView', DBG_RENDER);
		$currentDay = '';
		//$today = date('m.d.Y');
		//$yesterday = date('m.d.Y',strtotime("-1 day"));

		//foreach item
		$itemsList = $feed->getItems();
		zf_debug(sizeof($itemsList).' items to render', DBG_RENDER);

		$groupbyday = $params['groupbyday'];
		if ( $groupbyday ) {
			zf_debug("group by day is set", DBG_RENDER);
		}

		foreach ($itemsList as $item) {

			/* two ways of rendering:
			- group by day, we use a special template part, and separate each day
			- normal, use the regular news template
			 */

			if ($groupbyday) {

				$day = zf_transcode(strftime(ZF_DATEFORMAT,date($item->date_timestamp)));
				/*
				 * non locale-friendly way...
				 $day_std = date('m.d.Y', $item['date_timestamp']);

				if ($day_std == $today) {
					$day = "Today";
			}
			if ($day_std == $yesterday) {
				$day = "Yesterday";
			}*/
				if ($currentDay != $day && ZF_GROUP_BY_DAY == 'yes') {
					// if not the first time that we enter here
					if ($currentDay != "") {
						// terminate properly our day and start a new one
						$this->template->printDayFooter($currentDay);
					}
					$currentDay = $day;
					//echo zf_formatTemplate(array(), $day, array(), $template->newsDay, false);
					$this->template->printDay($currentDay);
				}

				zf_debug("print news by date", DBG_RENDER);
				$this->template->printNewsByDate($item);

			} else {
				zf_debug("print news", DBG_RENDER);
				$this->template->printNews($item);
			}

		} // end foreach

		if ($params['groupbyday'] && ZF_GROUP_BY_DAY == 'yes') {
			// terminate the last day we used
			$this->template->printDayFooter($currentDay);
		}

	}


	public function renderArticle($item) {
		$this->template->printArticle($item);
	}

	public function renderSummary($summary) {
		$this->template->printSummary($summary);
	}

}

