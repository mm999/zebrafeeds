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



class view {


	//var $template = null;

	// optional: separate each news day
	public $groupByDay;

	/* this property is used when currently rendering a particular feed
	it's a Feed object	  */

	protected $feed;
	protected $template;

	public function __construct($template, $feed) {
		$this->groupByDay = false;
		$this->feed = &$feed;
		$this->template = &$template;
	}

	/* render the view,
	  made of an unique "feed" if grouped by date"
	  or made of multiple single feeds if grouped by channel
	at this point, items are supposed to be filtered */
	public function render() {

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

	public function renderUnseenNewsitems() {

		$titleToShow = '';

		//foreach item
		foreach ($this->feed->items as $item) {

			if (!$item['isnew'] ) continue;

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

	public function renderRemainingNewsitems($doNewOnes) {

		$currentDay = '';
		//$today = date('m.d.Y');
		//$yesterday = date('m.d.Y',strtotime("-1 day"));

		//foreach item
		foreach ($this->feed->items as $item) {

			if (!$doNewOnes && $item['isnew'] ) continue;
			/* two ways of rendering:
			- group by day, we use a special template part, and separate each day
			- normal, use the regular news template
			 */
			$renderIt = true;
			if (function_exists('zf_itemfilter')) {
				$renderIt = zf_itemfilter(&$item);
			}

			if ($this->groupByDay ) {
				$day = zf_transcode(strftime(ZF_DATEFORMAT,date($item['date_timestamp'])));
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

}

?>
