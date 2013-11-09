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


if(zfAuth()==false) {
    exit;
} elseif(!is_writable($zf_path.'config.php')) {
    displayStatus($zf_path.'config.php is not writable (you cannot save changes)!');
}



if($_POST['dosave']=='Save settings')
{
    if($_POST['newpassword'] == $_POST['confirmpassword'] && $_POST['newpassword']!='') {
        $_POST['adminpassword'] = md5($_POST['newpassword']);
    } else {
        $_POST['adminpassword'] = ZF_ADMINPASS;
    }

    if (saveConfig($_POST)) {
        echo '<div id="core">';
        displayStatus('Configuration saved.');
        if ($_POST['refreshmode'] == 'request') {
            $refreshurl = ZF_URL.'/pub/refresh.php?key='.md5(ZF_ADMINNAME . ZF_ADMINPASS);
            echo '<div>Newsfeeds will <strong>NOT</strong> be updated automatically.
                  You\'ll have to request it manually or through a cron job.<br/><br/>
                  Use the <a href="'.$refreshurl.'">ZebraFeeds refresh link</a> to configure your cronjob. <br/><br/><br/>
                  <em>Note: this link changes whenever you change the admin user or password.</em></div>';
        }
        if ($_POST['rendermode'] == 'manual') {
            echo '<div>Newsfeeds will <strong>NOT</strong> be rendered automatically.<br/>
                  You\'ll have to use the user functions in the page that includes ZebraFeeds. See the documentation for more details.</div>';
        }
        echo '</div>';
    } else {
        displayStatus('Configuration NOT saved.');
    }
}
else
{
?>

<div id="core">  <form name="configform" action="<?php echo $_SERVER['PHP_SELF'].'?zfaction=config';?>" method="post">
<div class="frame">
<h2>General configuration</h2>

	<div class="twocols">
		<div class="col1">
			<label for="zfhomeurl">Location of your web site</label>
<a class="info" href="#">(?)<span>Points to the page embedding ZebraFeeds. Used also in the RSS feed publisher URL.</span></a>:
		</div>
		<div class="col2">
			<input name="zfhomeurl" id="zfhomeurl" type="text" size="50" value="<?php echo ZF_HOMEURL; ?>" />
		</div>

		<div class="col1">
			<label for="zfurl">ZebraFeeds script path URL: </label>
		</div>
		<div class="col2">
			<input name="zfurl" id="zfurl" type="text" size="50" value="<?php if(ZF_URL!='') { echo ZF_URL; } else {if(zfurl()!=false) {echo zfurl();}} ?>" />
		</div>
		<div class="col1">
			<label for="adminname">Admin username: </label>
		</div>
		<div class="col2">
			<input name="adminname" type="text" id="adminname"  value="<?php echo ZF_ADMINNAME;?>" />
		</div>
		<div class="col1">
			<label for="newpassword">Admin new password</label>
<a href="#" class="info">(?)<span>leave empty if you don't want to change pass</span></a>:
		</div>
		<div class="col2">
			<input type="password" name="newpassword" id="newpassword" />
		</div>
		<div class="col1">
			<label for="confirmpassword">Admin new password confirm: </label>
		</div>
		<div class="col2">
			<input type="password" name="confirmpassword" id="confirmpassword"/>
		</div>
		<div class="col1">
			<label for="zflogintype">Admin panel login mechanism</label>
<a href="#" class="info">(?)<span>session: will use cookies. server: requires .htaccess and .htpasswd on server</span></a>:
		</div>
		<div class="col2">
			<select name="zflogintype" id="zflogintype" >
				<option value="server" <?php if(ZF_LOGINTYPE=='server') echo 'selected="selected"';?>>server</option>
				<option value="session" <?php if(ZF_LOGINTYPE=='session') echo 'selected="selected"';?>>session</option>
				<option value="disabled" <?php if(ZF_LOGINTYPE!='session' && ZF_LOGINTYPE!='server') echo 'selected="selected"';?>>no panel</option>
			</select>
		</div>
	</div>
</div>
<div class="frame">
<h2>News feeds options</h2>
	<div class="twocols">
		<div class="col1">
			<label for="usesubs">Use subscription lists: </label>
		</div>
		<div class="col2">
			<select name="usesubs" id="usesubs" >
		              <option value="yes" <?php if(ZF_USEOPML=='yes') echo 'selected="selected"';?>>yes</option>
		              <option value="no" <?php if(ZF_USEOPML!='yes') echo 'selected="selected"';?>>no</option>
			</select>
		</div>
		<div class="col1">
			<label for="subfilename">Default subscription list</label>
		        <a href="#" class="info">(?)
			<span>the tag of subscriptions displayed by default</span>
			</a>:
		</div>
		<div class="col2">
			<select name="subtag" id="subtag" >
			</select>
		</div>
		<div class="col1">
			<label for="refreshmode">Refresh mode</label>
			<a href="#" class="info">(?)
			<span>How to refresh feeds.<br/> Automatic: when page is generated. <br/>
                           On request:  manual/scheduled refresh of feeds (by a cronjob for example).
			</span>
			</a>:
		</div>
		<div class="col2">
			<select name="refreshmode" id="refreshmode" >
		                <option value="automatic" <?php if(ZF_REFRESHMODE=='automatic') echo 'selected="selected"';?>>Automatic</option>
                		<option value="request" <?php if(ZF_REFRESHMODE!='automatic') echo 'selected="selected"';?>>On request</option>
	            </select>

		</div>
	</div>

</div>
<div class="frame">
<h2>General display options</h2>

	<div class="twocols">
		<div class="col1">
			<label for="template">Template used to display news: </label>
		</div>
		<div class="col2">
			<select name="template" id="template">
              <?php
                  $tnames = zf_getTemplateNames();
                  foreach($tnames as $templatef) {
                      if(ZF_TEMPLATE==$templatef) {
                          echo "<option value=\"$templatef\" selected=\"selected\">$templatef</option>";
                      } else {
                          echo "<option value=\"$templatef\">$templatef</option>";
                      }
                  }
             ?>
        		</select>
		</div>
		<div class="col1">
			<label for="displayerror">Display errors</label>
			<a href="#" class="info">(?)
			<span>if feed cannot be retrieved or parsed</span>
			</a>:
		</div>
		<div class="col2">
			<select name="displayerror" id="displayerror">
		              <option value="yes" <?php if(ZF_DISPLAYERROR=='yes') echo 'selected="selected"';?>>yes</option>
		              <option value="no" <?php if(ZF_DISPLAYERROR!='yes') echo 'selected="selected"';?>>no</option>
			</select>
		</div>
	</div>

</div>
<div class="frame">
<h2>Language/localization options</h2>

	<div class="twocols">
		<div class="col1">
			<label for="encoding">Page encoding: </label>
		</div>
		<div class="col2">
		        <select name="encoding" id="encoding">
      <?php
        $encodings = array ( 'UTF-8' ,
                            'ISO-8859-1',
                            'US-ASCII',
                            'ISO-8859-2',
                            'ISO-8859-3',
                            'ISO-8859-4',
                            'ISO-8859-5',
                            'ISO-8859-6',
                            'ISO-8859-7',
                            'ISO-8859-8',
                            'ISO-8859-9',
                            'ISO-2022-JP',
                            'ISO-2022-KR',
                            'ISO-2022-CN',
                            'Big5',
                            'WINDOWS-1251');

          $arrayLength = count($encodings);
          for ($i = 0; $i < $arrayLength; $i++){
             echo "<option value=\"$encodings[$i]\" "; if(ZF_ENCODING==$encodings[$i]) echo "selected=\"selected\""; echo ">$encodings[$i]</option>";
          }
       ?>
		        </select>
		</div>

		<div class="col1">
			<label for="locale">Locale</label>
           		<a href="#" class="info">(?)
			<span>Value to pass to the setlocale PHP function. It tells which language to display dates in.
			</span></a>:
		</div>
		<div class="col2">
            <input name="locale" type="text" id="locale" value="<?php echo ZF_LOCALE;?>"/>
		</div>

		<div class="col1">
			<label for="pubdateformat">News date/Time format</label>
 			<a href="#" class="info">(?)
			<span>Format dates received from feeds (if possible). Used by the strftime PHP function.</span></a>:
		</div>
		<div class="col2">
            <input name="pubdateformat" type="text" id="pubdateformat" value="<?php echo ZF_PUBDATEFORMAT;?>"/>
		</div>

		<div class="col1">
<label for="dateformat">Day date format</label>
        	<a href="#" class="info">(?)
		<span>Format used to display date when news are grouped by date. Should only be a date (no time) format. See the strftime PHP function.</span></a>:
		</div>
		<div class="col2">
            <input name="dateformat" type="text" id="dateformat" value="<?php echo ZF_DATEFORMAT;?>"/>
		</div>
	</div>

</div>
<div class="frame">
<h2>Advanced options</h2>

	<div class="twocols">
		<div class="col1">
<label for="nofuture">Discard future news</label>
		<a href="#" class="info">(?)
		<span>fight RSS spamming by hiding news that want to stay on top!</span>
		</a>:
		</div>
		<div class="col2">
            <select name="nofuture" id="nofuture">
              <option value="yes" <?php if(ZF_NOFUTURE=='yes') echo 'selected="selected"';?>>yes</option>
              <option value="no" <?php if(ZF_NOFUTURE!='yes') echo 'selected="selected"';?>>no</option>
            </select>
		</div>

        <div class="col1">
<label for="newitems">Mark new items</label>
		<a href="#" class="info">(?)
		<span>Mark items appeared since last visit, for each visitor (use cookies) or on server, i.e. since last time page was generated</span>
		</a>:
		</div>
		<div class="col2">
            <select name="newitems" id="newitems">
              <option value="no" <?php if(ZF_NEWITEMS=='no') echo 'selected="selected"';?>>no</option>
              <option value="client" <?php if(ZF_NEWITEMS=='client') echo 'selected="selected"';?>>per visitor (cookie based)</option>
              <option value="server" <?php if(ZF_NEWITEMS=='server') echo 'selected="selected"';?>>on server</option>
            </select>
		</div>

		<div class="col1">
<label for="rendermode">Render mode</label>
		<a href="#" class="info">(?)
		<span>Automatic: automatically display feeds where <code>zebrafeeds.php</code> is included. <br/>
                           Manual: including zebrafeeds.php does nothing. The user functions <strong>MUST</strong> be used to see aggregated news</span>
		</a>:
		</div>
		<div class="col2">
              <select name="rendermode" id="rendermode" >
                <option value="automatic" <?php if(ZF_RENDERMODE=='automatic') echo 'selected="selected"';?>>Automatic</option>
                <option value="manual" <?php if(ZF_RENDERMODE!='automatic') echo 'selected="selected"';?>>Manual</option>
            </select>
		</div>

		<div class="col1">
			<label for="ownername">Feed list owner name: </label>
		</div>
		<div class="col2">
            <input name="ownername" type="text" id="ownername" value="<?php echo ZF_OWNERNAME;?>"/>
		</div>

		<div class="col1">
			<label for="owneremail">Feed list owner email: </label>
		</div>
		<div class="col2">
           		<input name="owneremail" type="text" id="owneremail" value="<?php echo ZF_OWNEREMAIL;?>"/>
		</div>
	</div>
</div>
<div id="saveconfig">
    <input type="submit" name="dosave" id="dosave" value="Save settings"/>
</div>
  </form>
</div>
<?php } ?>
