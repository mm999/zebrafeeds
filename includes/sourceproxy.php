<?php



class SourceProxy {

	protected $sp;

	protected function init($xmlurl){
	    $this->sp = new SimplePie();

	    // check here according to refresh time
	    $this->sp->enable_cache(false);
	    $this->sp->enable_order_by_date(false);
	    $this->sp->set_timeout(20);
	    $this->sp->set_useragent(ZF_USERAGENT);
	    $this->sp->set_stupidly_fast(true);
	    $this->sp->force_feed(true);

	    $this->sp->set_feed_url($xmlurl);
	    $this->sp->init();
	    $this->sp->handle_content_type();

	}

	protected function cleanUp(){
	    $this->sp->__destruct();
	    unset($this->sp);
	}

	public function makeSourceFromAddress($xmlurl, &$resultString){
		$this->init($xmlurl);
	    $source = null;
	    if ($this->sp->data) {
        	$source = Source::create($this->sp->get_title(), $this->sp->get_link(), $this->sp->get_description(), $xmlurl);
		}  else {
			if ($this->sp->error()) {
				$resultString = $this->sp->error() . " on ".$xmlurl;
			} else
				$resultString = 'Error fetching or parsing '.$xmlurl;
		}
		$this->cleanUp();
		return $source;
	}

	public function fetchFeed($source, &$resultString){
		$this->init($source->xmlurl);
	    $filterChain = new FilterChain();
	    $trackerFilter = new DateTrackerFilter();
	    $filterChain->addFilter($trackerFilter);
	    $filterChain->addFilter(new SummaryNormalizerFilter());
		$myfeed = null;
	    if ($this->sp->data) {
	        $myfeed = new PublisherFeed($source);
	        $myfeed->xmlurl = $source->xmlurl;

	        // TODO support logo $myfeed->publisher->logo = $this->sp->get_image_url();

			$items = array_slice($this->sp->get_items(), 0, ZF_MAXFEEDITEMS, true);
	        foreach( $items as $item) {
	        	$pubitem = new NewsItem($myfeed, $source, $item->get_permalink(), $item->get_title(), $item->get_date('U'), $item->get_id(false));
			    $pubitem->description = $item->get_content();
			    $pubitem->summary = $item->get_description();

	            $encidx = 0;
	            $enc = $item->get_enclosures();
	            if (is_array($enc)) {
					foreach ($enc as $enclosure) {
						$newenc = new Enclosure();
						$newenc->link = $enclosure->get_link();
						$newenc->length = $enclosure->get_length();
						$newenc->type = $enclosure->get_type();
						$pubitem->addEnclosure($newenc);
	            	}
	            }
	            $myfeed->addItem($pubitem, $filterChain);
	        }

	        /* metadata */
	        $myfeed->last_fetched = time();
	    } else {
			if ($this->sp->error()) {
				$resultString = $this->sp->error() . " on ".$source->xmlurl;
			} else
				$resultString = 'Error fetching or parsing '.$source->xmlurl;
		}
		$this->cleanUp();

		return $myfeed;
	}

}