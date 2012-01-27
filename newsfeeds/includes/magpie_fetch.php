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
// ZebraFeeds RSS fetch layer using MagpieRSS


if (!defined('ZF_VER')) exit;

require_once($zf_path . 'includes/magpierss/rss_fetch.inc');


define('MAGPIE_OUTPUT_ENCODING', ZF_ENCODING);
define('MAGPIE_USER_AGENT', ZF_USERAGENT);
//define('MAGPIE_CACHE_DIR', $zf_path.ZF_CACHEDIR);
define('MAGPIE_DEBUG',0);
define('MAGPIE_CACHE_ON', 0);


/*
module interface entry point
$channeldata must contain one element
 xmlurl
 
returns a feed object
 */
function &zf_xpie_fetch_feed(&$channeldata, &$resultString) {

	$feed = null;

    $rss = fetch_rss($channeldata['xmlurl']);

    if ($rss) {
        $feed = new feed(); 

        $feed->channel['title'] = $rss->channel['title'];
        $feed->channel['xmlurl'] = $channeldata['xmlurl'];
        $feed->channel['link'] = $rss->channel['link'];
        $feed->channel['description'] = $rss->channel['description'];
        $feed->channel['favicon'] = '';
        if (isset($rss->image['url'])) {
        	$feed->channel['logo'] = $rss->image['url'];
        }
        $index = 0;
        foreach($rss->items as $item) {
            zf_fixMagpieItem($rss->items[$index],$feed->channel['link']);
            
            $feed->items[$index]['link'] = $rss->items[$index]['link'];
            $feed->items[$index]['title'] = $rss->items[$index]['title']; 

            $feed->items[$index]['date_timestamp']  = $rss->items[$index]['date_timestamp'];
            $feed->items[$index]['description'] = $rss->items[$index]['description']; 
            $feed->items[$index]['summary'] = $rss->items[$index]['summary']; 
            
            
            $encidx = 0;
            if (isset($item['enclosure'])) {
                foreach ($item['enclosure'] as $enclosure) {
                    $feed->items[$index]['enclosures'][$encidx]['link'] = $enclosure['url'];
                    $feed->items[$index]['enclosures'][$encidx]['type'] = $enclosure['type'];
                    $encidx++;
                } 
            }
            $index++;
        }

        /* metadata */
        $feed->last_fetched = time();
				
    } else {
        global $MAGPIE_ERROR;
        if (strpos($MAGPIE_ERROR, 'fetch') > 0) $resultString = 'Error fetching '.$channeldata['xmlurl'];
        if (strpos($MAGPIE_ERROR, 'parse') > 0) $resultString = 'Error parsing '.$channeldata['xmlurl'];
    }
    return $feed;
}

/* makes sure the item has all data we would need:
 * date, summary, description...
 * and that links in the content are made absolute 
 * arguments
 *    $item: the news item array to fix
 *    $link: the URL to the feed publisher, to make links absolute
 * */
function zf_fixMagpieItem(&$item, $link) {

    /* try to get a valid date. timestamp should be given by magpie, but some times it's not */

    if ( !isset($item['date_timestamp'])) {
        if (isset($item['date'])) {
            $item['date_timestamp'] = zf_cleanupDate($item['date']);
            if ($item['date_timestamp'] == -1) {
                $item['pubdate'] = $item['date'];
            }
            if (ZF_DEBUG==2) {
                zf_debug('-- using date '. $item['date_timestamp']);
            }
        }

        if (isset($item['dc']['date'])) {
            $item['date_timestamp'] = zf_cleanupDate($item['dc']['date']);
            if ($item['date_timestamp'] == -1) {
                $item['pubdate'] = $item['dc']['date'];
            }
            if (ZF_DEBUG==2) {
                zf_debug( '--using dc date '. $item['date_timestamp'] );
            }
        }

        if (isset($item['issued'])) {
            $item['date_timestamp'] = zf_cleanupDate($item['issued']);
            if ($item['date_timestamp'] == -1) {
                $item['pubdate'] = $item['issued'];
            }
            if (ZF_DEBUG==2) {
                zf_debug('-- using issued '. $item['date_timestamp']);
            }
        }
        if (isset($item['updated'])) {
            $item['date_timestamp'] = zf_cleanupDate($item['updated']);
            if ($item['date_timestamp'] == -1) {
                $item['pubdate'] = $item['updated'];
            }
            if (ZF_DEBUG==2) {
                zf_debug('-- using updated '. $item['date_timestamp']);
            }
        }


    } else {
        /* timestamp is set, but sometimes it can be wrong, especially 
           when publisher puts non standard (french) dates 
           in the pubDate element...
           this code deals with this case that personnally annoys me ;-)

           stolen and adapted from magpie code
         */


        # regex to match a french date
        $pat = "/(\d{2})\/(\d{2})\/(\d{2}) (\d{2}):(\d{2})/";

        if ( preg_match( $pat, $item['pubdate'], $match ) ) {
            list( $year, $month, $day, $hours, $minutes, $seconds) = 
                array( $match[3], $match[2], $match[1], $match[4], $match[5], 0);

            # calc epoch for current date assuming GMT
            $item['date_timestamp'] = mktime( $hours, $minutes, $seconds, $month, $day, $year);
        }


    }


    // if no description, try to get the summary instead
    if ( !isset($item['description'])) {
        if (isset($item['summary']) && (strlen($item['summary']) != 0) ) {
            $item['description'] = $item['summary'];
        }
        if (ZF_DEBUG==2) {
            zf_debug('-- forcing summary ');
        }
    }

    if ( !isset($item['description'])) {
        if (isset($item['atom_content']) && (strlen($item['atom_content']) != 0) ) {
            $item['description'] = $item['atom_content'];
        }
    }

    if ( !isset($item['description'])) {
        if (isset($item['atom']['summary']) && (strlen($item['atom']['summary']) != 0) ) {
            $item['description'] = $item['atom']['summary'];
        }
    }
    // give priority to the encoded version
    if ( isset($item['content']['encoded']) && (ZF_FORCE_ENCODED_CONTENT =='yes') ) {
        if (strlen ($item['content']['encoded']) != 0 ) {
            $item['description'] = $item['content']['encoded'];
        }
        if (ZF_DEBUG==2) {
            zf_debug('-- forcing encoded content');
        }
    }


    //Caution: if some links are relative, it's relative to the channel's site url, not the xml address
    $item['description'] = zf_makeMagpieAbsolute($item['description'], $link);

    if (!isset($item['summary']) || strlen($item['summary']) == 0 ) {
          $item['summary'] = $item['description'];
    } else {
          $item['summary'] = zf_makeMagpieAbsolute($item['summary'], $link);
    }

    /*move to feed class
    if (strlen($item['summary']) >= ZF_MAX_SUMMARY_LENGTH ) {
        $item['summary'] = substr(strip_tags($item['summary']), 0, ZF_SUMMARY_TRUNCATED_LENGTH).'...';
    }*/

}

/* stolen from a forum post: TODO put URL */
function zf_makeMagpieAbsolute($txt, $base_url) {
    $needles = array('href="', 'src="', 'background="', "href='", "src='", "background='");
    $new_txt = '';
    if(substr($base_url,-1) != '/') 
    $base_url .= '/';

    $new_base_url = $base_url;
    $base_url_parts = parse_url($base_url);
    foreach($needles as $needle){
        while($pos = strpos($txt, $needle)){
            $pos += strlen($needle);
            if(substr($txt,$pos,7) != 'http://' && substr($txt,$pos,8) != 'https://' && substr($txt,$pos,6) != 'ftp://' && substr($txt,$pos,9) != 'mailto://') {
                if(substr($txt,$pos,1) == '/') {
                    $new_base_url = $base_url_parts['scheme'].'://'.$base_url_parts['host'];
                }
                $new_txt .= substr($txt,0,$pos).$new_base_url;
            } else {
                $new_txt .= substr($txt,0,$pos);
            }
            $txt = substr($txt,$pos);
        }
        $txt = $new_txt.$txt;
        $new_txt = '';
    }
    return $txt;


}


?>
