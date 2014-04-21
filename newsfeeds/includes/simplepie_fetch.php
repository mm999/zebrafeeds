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
// ZebraFeeds RSS fetch layer, SimplePie version


if (!defined('ZF_VER')) exit;



/*
module interface entry point
returns a feed object
 */
function zf_xpie_fetch_feed($subId, $url, &$resultString) {

	$myfeed = null;


    $SP_feed = new SimplePie();

    $SP_feed->set_feed_url($url);
    // check here according to refresh time
    $SP_feed->enable_cache(false);
    $SP_feed->enable_order_by_date(false);
    $SP_feed->set_timeout(20);
    $SP_feed->set_useragent(ZF_USERAGENT);
    $SP_feed->set_stupidly_fast(true);
    $SP_feed->force_feed(true);
    //$SP_feed->force_fsockopen(true);
    //set cache duration, set cache location
    $SP_feed->init();
    $SP_feed->handle_content_type();

    if ($SP_feed->data) {

        $myfeed = new PublisherFeed($subId);
        $myfeed->xmlurl = $url;

        // TODO support logo $myfeed->publisher->logo = $SP_feed->get_image_url();

		$items = $SP_feed->get_items();
        foreach( $items as $item) {
        	$pubitem = new NewsItem($myfeed, $item->get_permalink(), $item->get_title(), $item->get_date('U'));
		    $pubitem->description = $item->get_content();

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
            $myfeed->addItem($pubitem);
        }

        /* metadata */
        $myfeed->last_fetched = time();
    } else {
		if ($SP_feed->error()) {
			$resultString = $SP_feed->error() . " on ".$url;
		} else $resultString = 'Error fetching or parsing '.$url;

    }
    // php memory bug, as described in SP documentation
	$SP_feed->__destruct();
	unset($SP_feed);
    return $myfeed;


}

?>
