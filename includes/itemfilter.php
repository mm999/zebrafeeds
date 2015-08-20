<?php

class FilterChain{

	protected $filters;
	protected $maxSize;

	public function __construct() {
		$this->filters = array();
		$this->maxSize = -1; /* no limit */
	}

	public function addFilter($filter) {
		$this->filters[] = $filter;
	}

	public function filter($items) {

		$result = array();

		foreach($items as $item) {
			zf_debug('Filter check on '.$item->title, DBG_FILTER);

			$accept = $this->acceptItem($item);
			if ($accept) {
				// TODO annoying that the inner implementation of feed->items is used here
				$result[$item->id] = $item;
			}
		}

		if ($this->maxSize > -1) {
			zf_debug("Keeping only last $this->maxSize items", DBG_FILTER);
			$result = array_slice($result, 0, $this->maxSize);

		}
		return $result;
	}


	public function setFeedTrim($trim) {
		// at this stage, 'auto' and 'none' has been handled already
		zf_debug("handling trim string $trim", DBG_FILTER);
		if (preg_match("/([0-9]+)(.*)/",$trim, $matches)) {
            $trimType = $matches[2];
            $trimSize = $matches[1];

			//zf_debug("Preparing TRIM filter $trimSize $trimType", DBG_FILTER);
			if ($trimType !== 'news') {
				$this->addFilter(new AgeFilter($trimType, $trimSize));
			} else {
				$this->maxSize = $trimSize;
			}
        }

	}

	public function acceptItem($item) {
		$accept = true;
		foreach ($this->filters as $filter) {
			$accept = $accept && $filter->accept($item);
			if (!$accept) {
				break;
			}
		}
		zf_debug('Filter '. get_class($filter). ($accept?' accepts ':' rejects ').$item->title, DBG_FILTER);
		return $accept;


	}

}

/* how to do for number of news-based Trim and Sort?? */



abstract class ItemFilter {

	public abstract function accept($item);

}


class OnlyNewFilter extends ItemFilter{

	public function accept($item) {
		return $item->isNew;
	}

}


// this one will need rework
class MarkNewItemFilter extends ItemFilter{

	protected $tracker;

	public function __construct(){
		$this->tracker = ItemTracker::getInstance();
	}

	public function accept($item) {
		$this->tracker->checkNewStatus($item->feed->subscriptionId, $item);
		zf_debug("Mark new item ".$item->id.": is new? ".($item->isNew?'yes':'no'), DBG_FILTER);
		return true;
	}

}



/* record the item and fix the empty pubdate of the item */
class DateTrackerFilter extends ItemFilter{

	protected $tracker;

	public function __construct(){
		$this->tracker = ItemTracker::getInstance();
	}

	public function __destruct() {
		$this->tracker->purge();
	}

	public function accept($item) {
		$this->tracker->checkIn($item->feed->subscriptionId, $item);
		return true;
	}

}


class SummaryNormalizerFilter extends ItemFilter{

	public function accept($item) {

		zf_debug('Normalizing summary for ' . $item->id, DBG_FILTER);
		if ((strlen($item->summary) == 0) ) {
			  $item->summary = $item->description;
		}

		if ((strlen($item->title) > 0) ) {
			$strsum = strip_tags($item->summary);
			zf_debug(strlen($strsum). ' chars in stripped summary, unstripped: '. strlen($item->summary), DBG_FILTER);

	        //strip out inline css and simplify style tags
	        $search = array('#<(strong|b)[^>]*>(.*?)</(strong|b)>#isu', '#<(em|i)[^>]*>(.*?)</(em|i)>#isu', '#<u[^>]*>(.*?)</u>#isu');
	        $replace = array('<b>$2</b>', '<i>$2</i>', '<u>$1</u>');
	        $strsum = preg_replace($search, $replace, $strsum);

			$item->isTruncated = false;
			if (strlen($strsum) > ZF_MAX_SUMMARY_LENGTH ) {
				zf_debug('truncating summary', DBG_FILTER);
				$strsum = substr($strsum, 0, ZF_SUMMARY_TRUNCATED_LENGTH);
				// don't chop words. chop at the last space character found
				$lastspace = strrpos($strsum, ' ');
				$strsum = substr($strsum, 0, $lastspace).'...';
				$item->isTruncated = true;
				$item->summary = $strsum;
			}
		}

		return true;
	}

}


class AgeFilter extends ItemFilter{

	protected $oldest;

	public function __construct($type, $size){

		$this->oldest = 0;

		// get timestamp we don't want to go further
		if ($type == 'hours') {
			// earliest is the timestamp before which we should ignore news
			$this->oldest = time() - (3600 * $size);
		}
		if ($type =='days') {
			// earliest is the timestamp before which we should ignore news

			// get timestamp of today at 0h00
			$todayts = strtotime(date("F j, Y"));

			// substract x-1 times 3600*24 seconds from that
			// x-1 because the current day counts, in the last x days
			$this->oldest = $todayts -  (3600*24*($size-1));
		}
	}


	public function accept($item){
		return $item->date_timestamp >= $this->oldest;
	}
}



class FutureItemFilter extends ItemFilter{

	protected $basetime;

	public function __construct(){
		$this->basetime = time();
	}

	public function accept($item){
		return ($item->date_timestamp <= $this->basetime );
	}

}


//TODO
class UserFilter extends ItemFilter{

	public function accept($item){
		// if function exists, execute and return the result
		return true;
	}

}

//TODO
class HTMLPurifierFilter extends ItemFilter {

	public function accept($item){

		return true;
	}

}
