<?php
// ZebraFeeds - copyright (c) 2006 Laurent Cazalet
// http://www.cazalet.org/zebrafeeds
//
// zFeeder 1.6 - copyright (c) 2003-2004 Andrei Besleaga
// http://zvonnews.sourceforge.net
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

if (zfAuth()==false) exit;


if (ZF_RSSPARSER == "magpie") {
    require_once($zf_path . 'includes/magpie_fetch.php');
} else {
    require_once($zf_path . 'includes/simplepie_fetch.php');
}    

function simple_fetch($url, &$resultString) {
	$channeldata['xmlurl'] = $url;
	$resp = "";
	$rss = zf_xpie_fetch_feed($channeldata, $resp);
    
	//$resp = _fetch_remote_file( $url );
    if ( $rss ) {
        return $rss;
    } else {
        $resultString = 'Failed to fetch or parse.';
        return false;
    }


}



/* parse the XML feed to detect type and data*/
/* feedurl: address of the XML feed
 htmldata: template to format the output, to be inserted in the admin page as a form */
function parse_feed($feedurl, $htmldata)
{

    $result = '';

    $rss = simple_fetch($feedurl, $result);

    if ($rss) {
        if (isset($rss->image)) {
            $chanimg = "<a href=\"$rss->image['link']\"><img src=\"$rss->image['url']\" title=\"$rss->image['title']\" border=\"0\" /></a>";
        }
        else {
            $chanimg = '';
        }

        $htmldata = str_replace("{formaction}", $_SERVER['PHP_SELF'] . '?zfaction=addnew', $htmldata);
        //$htmldata = str_replace("{feedurl}", $feedurl, $htmldata);
        $htmldata = str_replace("{feedurl}", htmlentities($feedurl), $htmldata);
        $htmldata = str_replace("{encfeedurl}", urlencode($feedurl), $htmldata);
        $htmldata = str_replace("{htmlurl}", $rss->channel['link'], $htmldata);
        $htmldata = str_replace("{chanimg}", $chanimg, $htmldata);

        $htmldata = str_replace("{chantitle}", $rss->channel['title'], $htmldata);
        //$htmldata = str_replace("{chanlicense}", $chanlicense, $htmldata);
        $htmldata = str_replace("{chandesc}", $rss->channel['tagline'], $htmldata);
        $htmldata = str_replace("{chanlink}", $rss->channel['link'], $htmldata);
        $htmldata = str_replace("{showeditems}", ZF_DEFAULT_NEWS_COUNT, $htmldata);
        $htmldata = str_replace("{chanttl}", ZF_DEFAULT_REFRESH_TIME, $htmldata);
        
        return $htmldata;
        
    } else {
        return "$result $feedurl.";
    }
}


/* parse the webpage to find references to the XML feed */
function getRSSLocation($html, $location)
{
    if (!$html or !$location) {
        return false;
    } else {
        preg_match_all('/<link\s+(.*?)\s*\/?>/si', $html, $matches);
        $links = $matches[1];
        $final_links = array();
        $link_count = count($links);
        for($n = 0; $n < $link_count; $n++) {
            $attributes = preg_split('/\s+/s', $links[$n]);
            foreach($attributes as $attribute) {
                $att = preg_split('/\s*=\s*/s', $attribute, 2);
                if (isset($att[1])) {
                    $att[1] = preg_replace('/([\'"]?)(.*)\1/', '$2', $att[1]);
                    $final_link[strtolower($att[0])] = $att[1];
                }
            }
            $final_links[$n] = $final_link;
        }
        for($n = 0; $n < $link_count; $n++) {
            if (strtolower($final_links[$n]['rel']) == 'alternate') {
                if (strtolower($final_links[$n]['type']) == 'application/rss+xml') {
                    $href = $final_links[$n]['href'];
                }
                if (!$href and strtolower($final_links[$n]['type']) == 'text/xml') {
                    $href = $final_links[$n]['href'];
                }
                if (!$href and strtolower($final_links[$n]['type']) == 'application/atom+xml') {
                    $href = $final_links[$n]['href'];
                }
                if ($href) {
                    if (strstr($href, "http://") !== false) {
                        $full_url = $href;
                    } else {
                        $url_parts = parse_url($location);
                        $full_url = 'http://' . $url_parts['host'];
                        if (isset($url_parts['port'])) {
                            $full_url .= ':' . $url_parts['port'];
                        }
                        if ($href{0} != '/') {
                            $full_url .= dirname($url_parts['path']);
                            if (substr($full_url, -1) != '/') {
                                $full_url .= '/';
                            }
                        }
                        $full_url .= $href;
                    }
                    return $full_url;
                }
            }
        }
        return false;
    }
}

$siteurl = $_REQUEST['siteurl'];
$feedurl = $_REQUEST['feedurl'];


/* this is the form that displays the parsed feed information */
$htmldata = <<<EOD
<form name="subform" action="{formaction}" method="post">
  
<div class="twocols">
	<div class="col1">
		Feed URL : 
	</div>
	<div class="col2">
	<a href="{feedurl}">{feedurl}</a>
		<input type="hidden" name="feedurl" value="{feedurl}" />
		{chanimg}
	</div>
	<div class="col1">
		Site URL : 
	</div>
	<div class="col2">
		<a href="{chanlink}">{chanlink}</a>
	        <input type="hidden" name="htmlurl" value="{htmlurl}" />
	</div>
	<div class="col1">&nbsp;</div>
	<div class="col2">&nbsp;</div>
    
	<div class="col1"><label for="chantitle">Title :</label></div>
     	<div class="col2">
	        <input name="chantitle" type="text" id="chantitle" size="60" value="{chantitle}"/>
	</div>
	<div class="col1"><label for="chandesc">Description :</label></div>
 	<div class="col2">
		<input name="chandesc" type="text" id="chandesc" size="60" value="{chandesc}"/>
	</div>
	<div class="col1">&nbsp;</div>
	<div class="col2">&nbsp;</div>

	<div class="col1"><label for="refreshtime">Refresh time :</label></div>
	<div class="col2">
	        <input name="refreshtime" type="text" id="refreshtime" size="4" value="{chanttl}"/>&nbsp;minutes.
	</div>
	<div class="col1"><label for="showednews">Display :</label></div>
	<div class="col2">
        	<input name="showednews" type="text" id="showednews" size="4" value="{showeditems}"/>&nbsp;news.
	</div>
	<div class="col1"><label for="issubscribed">Enabled :</label></div>
	<div class="col2">
        <select name="issubscribed" id="issubscribed">
          <option value="yes" selected>yes</option>
          <option value="no">no</option>
        </select>
	</div>
	<div class="col1">&nbsp;</div>
	<div class="col2">&nbsp;</div>

		<a href="http://feedvalidator.org/check.cgi?url={encfeedurl}" target="_blank">Validate feed at feedvalidator.org</a>
	<div class="col1">&nbsp;</div>
	<div class="col2">&nbsp;</div>

	<div class="col1"><label for="zflist">Subscription list :</label></div>
        <div class="col2">
		<select name="zflist" id="zflist">
EOD;

$htmldata .= zf_ListsFormElements(ZF_HOMELIST);
$htmldata .= <<<EOD
        </select> &nbsp;
	</div>
	<br />
	<div id="saveconfig">
        <input name="subscribe" type="submit" id="subscribe" value="add to subscription list"/>
	</div>
</div>
</form>
EOD;



if (isset($_POST['subscribe']) && $_POST['subscribe'] == 'add to subscription list') {

echo '<div id="core">';
    /* Case 1: complete the subscription */

    // first thing to do: load the current category file in memory
    $currentListName = zf_getCurrentListName();
    
/*    if (isset($_POST['zflist']) && $_POST['zflist']!='' && file_exists($zf_path . ZF_OPMLDIR . '/' . $_POST['zflist'] . '.opml')) {
    	$currentCategory=$_POST['zflist'];
    } else {
    	$currentCategory=ZF_HOMELIST;
    }
    $categoryData = zf_parseOpmlFile($currentCategory);
*/
    if ($currentListName != '') {
        $list = new opml($currentListName);
        if ($list->load()) {

            //$feed['type'] = strtolower($_POST['feedtype']);
            $feed['xmlurl'] = stripslashes($_POST['feedurl']);
            $feed['htmlurl'] = stripslashes($_POST['htmlurl']);
            $feed['language'] = stripslashes($_POST['chanlang']);
            //$feed['encoding'] = $_POST['encoding'];
            $feed['title'] = stripslashes($_POST['chantitle']);
            $feed['description'] = stripslashes($_POST['chandesc']);
            $feed['refreshtime'] = $_POST['refreshtime'];
            $feed['showeditems'] = $_POST['showednews'];
            $feed['issubscribed'] = $_POST['issubscribed'];


            $list->channels[] = $feed;
            if ($list->save()) {
                displayStatus('Channel added to list '.$currentListName);
                echo '<div style="margin-top: 15px">';
                displayGotoButton($currentListName);
                echo '</div>';
            } else {
                displayStatus($list->lastError);
            }
            
        } else {
            displayStatus("Error parsing the subscriptions list : " . $list->lastError . "<br/>Channel was NOT added.");
        }
    } else {
        displayStatus("No list available<br/>Channel was NOT added.");
    }
echo '</div>';

} elseif (isset($siteurl) && $siteurl != '') {
    /* Case 2: parse web page given in the form
               display a form for each feed
    */
echo '<div id="core">';

    @$fp = fopen($siteurl, "r");
    $sitehtmldata = "";
    while (true) {
        @$datas = fread($fp, 4096);
        if (strlen($datas) == 0) {
            break;
        }
        $sitehtmldata .= $datas;
    }
    @fclose($fp);
    if ($sitehtmldata != '') {
        $rssloc = getRSSlocation($sitehtmldata, $siteurl);
        if ($rssloc != false)
            echo parse_feed($rssloc,$htmldata);
        else
            displayStatus("Autodiscovery didn't detected any feeds. If the site has them add them manually from the Feed URL form.");
    } else
        displayStatus("Error: could not read the specified URL");
echo '</div>';

} elseif (isset($feedurl) && $feedurl != '') {
echo '<div id="core">';
    /* Case 3: parse feed(s) given in the form or via bookmarklet
               display a form for each feed
     */
    //if got from the bookmarklet, all urls are separated by a pipe character
    $tempfeedurl = explode("|", $feedurl);
    foreach($tempfeedurl as $xmlurl)
        echo parse_feed($xmlurl,$htmldata);

echo '</div>';


} else {

    // Default: basic form to enter feed or site address
?>

<div id="core">
	<div class="frame">
		<strong>RSS/RDF/ATOM Autodiscovery</strong><br/>
		<br/>
		<form name="form1" action="<?php echo $_SERVER['PHP_SELF'] . '?zfaction=addnew'?>" method="post">
		  Site URL :
		  <input name="siteurl" type="text" size="40"/>
		  <input type="submit" name="submitsiteurl" value="go"/>
		  </form>
		  (ex: http://cazalet.org/zebrafeeds)
	</div>
	<div class="frame">
	<strong>or RSS/RDF/ATOM feed address</strong><br/>
		  <br/>
		  <form name="form2" action="<?php echo $_SERVER['PHP_SELF'] . '?zfaction=addnew'?>" method="post">
			Feed URL :
			<input name="feedurl" type="text" size="40"/>
			<input type="submit" name="submitfeedurl" value="go"/>
		  </form>
		  (ex: http://cazalet.org/zebrafeeds/feed)
	</div>
	<div class="frame">
	<?php
		if (zfurl() != false) {
			echo "<strong>or drag this bookmarklet : </strong><a href=\"javascript:(function(){els=document.getElementsByTagName('link');feeds='';cnt=0;for(i=0;i<els.length;i++){ty=(els[i].getAttribute('type')||'').toLowerCase();url=els[i].getAttribute('href');if(url&&(ty=='application/rss+xml'||ty=='application/atom+xml'||ty=='text/xml')){cnt++;if(url=prompt('Add this feed (#'+cnt+') to your ZebraFeeds enabled site ?',url)){feeds+=url+'|';}}};if(cnt==0){url=prompt('No feed detected. Enter feed address here:',url);if(url){feeds+=url+'|';}}if(feeds){feeds=feeds.substr(0,feeds.length-1);window.location='" . ZF_URL . "/admin/index.php?zfaction=addnew&amp;feedurl='+encodeURIComponent(feeds);}})()\" title=\"Subscribe with ZebraFeeds\">Subscribe with ZebraFeeds</a><br/> to your browser links toolbar.<br/>";
			echo "<br/>Whenever you visit a site and you want to add it's syndicated content to your site,click &quot;Subscribe with ZebraFeeds&quot; button on your links toolbar, and it will try to find the site's RSS feed and auto-subscribe your site to it.";
			echo "<br/><br/>(note: if you change the location of ZebraFeeds on your webhost you must delete &quot;Subscribe with ZebraFeeds&quot; bookmark from your toolbar and come here and drag and drop again)";
		}

	?>
	</div>
</div>
<?php } ?>
