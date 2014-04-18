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

require_once($zf_path . 'includes/common.php');
require_once($zf_path . 'includes/template.php');


abstract class AbstractFeedView {

	abstract public function renderHeader();
	abstract public function renderFeed($feed);
	abstract public function renderFooter();


	public function renderArticle($item) {

		if (function_exists('zf_itemfilter')) {
			zf_debug('Calling filter');
			zf_itemfilter($item);
		}
		$this->_doPrintArticle($item);
	}

	public function renderSummary($item) {
		if (function_exists('zf_itemfilter')) {
			zf_debug('Calling filter');
			zf_itemfilter($item);
		}
		$this->_doPrintSummary($item);

	}

	abstract protected function _doPrintArticle($item);
	abstract protected function _doPrintSummary($item);

	abstract public function addTags($tags);
}


class JSONView extends AbstractFeedView {

	public $summaryInFeed = false;



	public function renderHeader() {
	}
	public function renderFooter() {
	}
	public function renderFeed($feed) {

		$out = array();
		// foreach item of the feed
		$items = $feed->getItems();
		foreach ($items as $item) {
			//   add JSON friendly object to array
			$classname = get_class($feed);
			switch ($classname) {
				case 'PublisherFeed':
					// get short header without publisher
					$out[] = $item->getSerializableHeader($this->summaryInFeed);
					break;
				case 'AggregatedFeed':
					// get full header with publisher info
					$out[] = $item->getFullSerializableHeader($this->summaryInFeed);
			}
		}

		echo json_encode($out);

	}

	protected function _doPrintArticle($item) {
		echo json_encode($item->getSerializableItem());
	}

	protected function _doPrintSummary($item) {
		echo json_encode($item->summary);
	}

	public function addTags($tags) {
	}

}



class TemplateView extends AbstractFeedView{


	// optional: separate each news day
	public $groupByDay;

	/* this property is used when currently rendering a particular feed
	it's a Feed object	  */

	protected $template;

	public function __construct($templateName) {
		$this->groupByDay = false;
		$this->template = new template($templateName);
	}

	public function renderHeader() {
		zf_debug('Rendering header of TemplateView', DBG_RENDER);
		$this->template->printHeader();
	}

	/* render the view,
	  made of an unique "feed" if grouped by date"
	  or made of multiple single feeds if grouped by channel
	at this point, items are supposed to be filtered */
	public function renderFeed($feed) {
		zf_debug('Rendering feed in TemplateView', DBG_RENDER);

		if ($this->groupByDay ) {
			$this->template->printListHeader($feed);
		} else {
			$this->template->printChannel($feed);
		}
		$this->renderNewsItems($feed);

		if ($this->groupByDay ) {
			$this->template->printListFooter();
		} else {
			$this->template->printChannelFooter();
		}
	}

	public function renderFooter() {
		zf_debug('Rendering footer of TemplateView', DBG_RENDER);
		$this->template->printFooter();
	}

	/* print only news items, no header */
	protected function renderNewsItems($feed) {

		zf_debug('Rendering Newsitems in TemplateView', DBG_RENDER);
		$currentDay = '';
		//$today = date('m.d.Y');
		//$yesterday = date('m.d.Y',strtotime("-1 day"));

		//foreach item
		$itemsList = $feed->getItems();
		zf_debug(sizeof($itemsList).' to render', DBG_RENDER);
		foreach ($itemsList as $item) {

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

	protected function _doPrintSummary($item) {
	}


	public function addTags($tags) {
		$this->template->addTags($tags);
	}
}

