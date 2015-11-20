<?php


if (!defined('ZF_VER')) exit;

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
        	$source = Source::create(
        						strip_tags($this->sp->get_title()), 
        						$this->sp->get_link(), 
        						strip_tags($this->sp->get_description()), 
        						$xmlurl);
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

		$myfeed = null;
	    if ($this->sp->data) {
	        $myfeed = $this->sp;
	    } else {
			if ($this->sp->error()) {
				$resultString = $this->sp->error() . " on ".$source->xmlurl;
			} else
				$resultString = 'Error fetching or parsing '.$source->xmlurl;
		}
		//only for PHP <5.3 $this->cleanUp();

		return $myfeed;
	}

}