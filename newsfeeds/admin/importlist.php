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

require_once($zf_path . 'includes/opml.php');

function displaydata(&$list)
{
    //global $items, $itemcount;

    if (count($list->channels) > 0) {
        $htmldata = <<<EOD
		<tr class="sub-line">
		  <td class="sub-subscribed">
			<input type="checkbox" name="addbox{i}" value="checkbox"/>
		</td>
		  <td class="sub-subscribed">
			<select name="subscribed{i}">
			  <option value="yes" {issubscribed}>yes</option>
			  <option value="no" {notsubscribed}>no</option>
			</select></td>
		  <td class="sub-subscribed">
			<div class="feed">
			<a href="javascript:open('{htmlurl}')" onclick="window.open('{htmlurl}'); return false;">{chantitle}</a>
			[{lang}]<br/>
			Feed URL : <i>{xmlurl}</i><br/>
			Website : <i>{htmlurl}</i>
			<a href="javascript:open('{xmlurl}')" onclick="window.open('{xmlurl}'); return false;"><img src="{zfurl}/images/feed.png" border="0" alt="RSS/ATOM feed"/></a><br/>
			{description}
			</div>
		</td>
		  <td class="sub-subscribed">
			<input name="refreshtime{i}" type="text" size="4" value="{refreshtime}"></input>
			</td>
		  <td class="sub-subscribed">
			<input name="showeditems{i}" type="text" size="4" value="{showeditems}"></input>
			<input type="hidden" name="language{i}" value="{lang}" />
			<input type="hidden" name="description{i}" value="{description}" />
			<input type="hidden" name="chantitle{i}" value="{chantitle}" />
			<input type="hidden" name="xmlurl{i}" value="{xmlurl}" />
			<input type="hidden" name="htmlurl{i}" value="{htmlurl}" />
			</td>
		</tr>
EOD;

        $returndata = '';
        $i = 0;
        foreach($list->channels as $key => $item) {
            $tempdata = '';
            $tempdata = str_replace("{i}", $i, $htmldata);
            $tempdata = str_replace("{zfurl}", ZF_URL, $tempdata);
            $tempdata = str_replace("{chantitle}", $item['title'], $tempdata);
            $tempdata = str_replace("{htmlurl}", $item['htmlurl'], $tempdata);
            $tempdata = str_replace("{description}", $item['description'], $tempdata);
            $tempdata = str_replace("{lang}", $item['language'], $tempdata);
            $tempdata = str_replace("{xmlurl}", $item['xmlurl'], $tempdata);
            $tempdata = str_replace("{position}", $item['position'], $tempdata);
            $tempdata = str_replace("{refreshtime}", $item['refreshtime'], $tempdata);
            $tempdata = str_replace("{showeditems}", $item['showeditems'], $tempdata);
            if ($item['issubscribed'] == 'yes') {
                $tempdata = str_replace("{issubscribed}", "selected=\"selected\"", $tempdata);
                $tempdata = str_replace("{notsubscribed}", "", $tempdata);
            } else {
                $tempdata = str_replace("{issubscribed}", "", $tempdata);
                $tempdata = str_replace("{notsubscribed}", "selected=\"selected\"", $tempdata);
            }
            $tempdata = str_replace("{bgcolor}", ($i%2? '#fff':'#f2ebe0'), $tempdata);
            $returndata .= $tempdata;
            $i++;
        }
    } else $returndata = "no subscriptions in subscriptions list";
    return $returndata;
}
// main
$opmlurl = $_REQUEST['opmlurl'];
if (isset($_REQUEST['opmlurl']) && $opmlurl != '') {

    $list = new opml("import");
    if ($list->load($opmlurl)) {
        

    //$opmldata = parse_iopmlfile($opmlurl);
?>
<div id="core">
	  <script type="text/javascript" language="JavaScript">
		function doNow()
		{
		  void(d=document);
		  void(el=d.getElementsByTagName('INPUT'));
		  for(i=0;i<el.length;i++)
			{ if(document.subscriptionsform.checkboxall.checked==1)
				{ void(el[i].checked=1); }
			  else
				{ void(el[i].checked=0); }
			}
		}
		</script>
	  <form name="subscriptionsform" action="<?php echo $_SERVER['PHP_SELF'] . '?zfaction=importlist';?>" method="post">
	  <table id="subscriptions">
		<tr id="firstline">
		  <td><input type="checkbox" name="checkboxall" value="checkbox" title="check/uncheck all" onClick="Javascript:doNow()"/></td>
		  <td><strong>subscribed</strong></td>
          <td><strong>channel</strong></td>
		  <td width="71">refresh
			(minutes) </td>
		  <td width="55">max displayed
			news </td>
		</tr>
		<?php echo displaydata($list);?>
		<tr id="lastline">
		  <td colspan="5" id="lastline"> &nbsp; Target subscription list:
			<select name="zflist">
			<?php
				echo zf_ListsFormElements(ZF_HOMELIST);
			?>
			</select>
			&nbsp;
			<input name="save" type="submit" id="save" value="add selected feeds"></input>
			</td>
		</tr>
	  </table>

	</form>
	</div>
<?php 
        } else {
            //remote list not readable
            
        }
    
    } elseif ($_POST['save'] == 'add selected feeds') {
    // first thing to do: load the current category file in memory
        //TODO fixit, dont we have a function for that?

    if (isset($_POST['zflist']) && $_POST['zflist']!='' && file_exists($zf_path . ZF_OPMLDIR . '/' . $_POST['zflist'] . '.opml')) {
    	$currentCategory=$_POST['zflist'];
    } else {
    	$currentCategory=ZF_HOMELIST;
    }
    $list = new opml($currentCategory);
    echo '<div id="core">';
 
    if ($list->load()) {
        $i = 0;
        foreach($_POST as $key => $value) {
            if ($key == "xmlurl" . $i && $value != '' && $_POST["addbox" . $i] == 'checkbox') {
                $channel['xmlurl'] = stripslashes($_POST["xmlurl" . $i]);
                $channel['htmlurl'] = stripslashes($_POST["htmlurl" . $i]);
                $channel['lang'] = stripslashes($_POST["language" . $i]);
                $channel['title'] = stripslashes($_POST["chantitle" . $i]);
                $channel['description'] = stripslashes($_POST["description" . $i]);
                $channel['refreshtime'] = $_POST["refreshtime" . $i];
                $channel['showeditems'] = $_POST["showeditems" . $i];
                $channel['issubscribed'] = $_POST["subscribed" . $i];
                //$channel['position'] = $lastpos++;
                echo "Importing channel ". $channel['title'] . '<br/>';
                $list->channels[] = $channel;
                $i++;
            } elseif ($key == "xmlurl" . $i && $value != '' && $_POST["addbox" . $i] != 'checkbox') 
                $i++;
        }
        if ($list->save()) {
            if (ZF_DEBUG) print_r($list->channels);
            displayStatus("list saved");
            //TODO : goto list button
            displayGotoButton($list->name);
        } else {
            displayStatus($list->lastError);
        }
        

    } else {
        displayStatus("Error parsing the subscriptions list : " . $list->name ."<br/>Feeds were NOT added.");
    }
    echo '</div>';
} else {
?>

<div id="core">
<div class="frame">
<form method="post" name="subopml" action="<?php echo $_SERVER['PHP_SELF'] . '?zfaction=importlist';?>">
		<strong>Import OPML subscriptions</strong><br/><br/><br/>
      Local path or remote URL of an OPML subscription list<br/>
      <input name="opmlurl" type="text" size="50"/>
      <input name="fetchopml" type="submit" id="fetchopml" value=" go "/>
      <br/>
      <br/><br/>
      (you can export your subscriptions from your desktop aggregator to opml
      - if it supports, or you can give an url address of such subscription list)<br/>
</form>
</div>
</div>
<?php } ?>
