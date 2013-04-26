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

class AbstractView {
	protected $feed;

	public function __construct() {
	}

	public function useFeed($feed) {
		$this->feed = $feed;
	}

	public function renderFeed() {
	}

	public function renderNewsItems() {
		$this->renderFeed();
	}

	public function renderArticle($itemid) {
		$item = $this->feed->lookupItem($itemid);
		if ($item) {
			if (function_exists('zf_itemfilter')) {
				zf_debug('Calling filter');
				zf_itemfilter($item);
			}
			$this->_doPrintArticle($item);
		} else {
			echo  "Content not available";
		}
	}

	protected function _doPrintArticle($item) {
	}

	public function addTags($tags) {
	}
}


class JSONView extends AbstractView {

	public function renderFeed() {
		echo json_encode($this->feed);
	}

	protected function _doPrintArticle($item) {
		echo json_encode($item);
	}

}



class TemplateView extends AbstractView{


	// optional: separate each news day
	public $groupByDay;

	/* this property is used when currently rendering a particular feed
	it's a Feed object	  */

	protected $template;

	public function __construct($templateName) {
		$this->groupByDay = false;
		$this->template = new template($templateName);
	}

	/* render the view,
	  made of an unique "feed" if grouped by date"
	  or made of multiple single feeds if grouped by channel
	at this point, items are supposed to be filtered */
	public function renderFeed() {

		$this->template->printHeader();
		if ($this->groupByDay ) {
			$this->template->printListHeader($this->feed);
		} else {
			$this->template->printChannel($this->feed);
		}
		$this->renderNewsItems();

		if ($this->groupByDay ) {
			$this->template->printListFooter($this->feed->publisher);
		} else {
			$this->template->printChannelFooter($this->feed->publisher);
		}
		$this->template->printFooter();
	}

	/* print only news items, no header */
	public function renderNewsItems() {

		$doNewOnes = true;

		if ((defined('ZF_NEWONTOP') && ZF_NEWONTOP == 'yes') ) {
			if ($this->groupByDay ) {
				$this->renderUnseenNewsitems();
				$doNewOnes = false;
			}
		}
		$this->renderRemainingNewsitems($doNewOnes);

	}

	private function renderUnseenNewsitems() {

		$titleToShow = '';

		//foreach item
		foreach ($this->feed->items as $item) {

			if (!$item->isNew ) continue;

			if ($titleToShow == '') {
				$titleToShow = "Recent news";
				$this->template->printDay($titleToShow);
			}
			$this->template->printNewsByDate($item);
		} // end foreach

		// print day footer
		if ($titleToShow != '') {
			$this->template->printDayFooter($currentDay);
		}

	}

	private function renderRemainingNewsitems($doNewOnes) {

		$currentDay = '';
		//$today = date('m.d.Y');
		//$yesterday = date('m.d.Y',strtotime("-1 day"));

		//foreach item
		foreach ($this->feed->items as $item) {

			if (!$doNewOnes && $item->isNew ) continue;
			/* two ways of rendering:
			- group by day, we use a special template part, and separate each day
			- normal, use the regular news template
			 */
			$renderIt = true;
			if (function_exists('zf_itemfilter')) {
				$renderIt = zf_itemfilter($item);
			}

			if ($this->groupByDay ) {
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
				if ($renderIt) $this->template->printNewsByDate($item);

			} else {
				if ($renderIt) $this->template->printNews($item);
			}

		} // end foreach

		if ($this->groupByDay && ZF_GROUP_BY_DAY == 'yes') {
			// terminate the last day we used
			$this->template->printDayFooter($currentDay);
		}

	}

	protected function _doPrintArticle($item) {
		$this->template->printArticle($item);
	}

	public function addTags($tags) {
		$this->template->addTags($tags);
	}
}

?>
