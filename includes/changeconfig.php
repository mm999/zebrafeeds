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
} elseif(!is_writable(ZF_CONFIGFILE)) {
	displayStatus('config.php is not writable (you cannot save changes)!');
}



if(isset($_POST['dosave']) && $_POST['dosave']=='Save')
{
	if($_POST['newpassword'] == $_POST['confirmpassword'] && $_POST['newpassword']!='') {
		$_POST['adminpassword'] = md5($_POST['newpassword']);
	} else {
		$_POST['adminpassword'] = ZF_ADMINPASS;
	}

	$_POST['zfurl'] = ZF_URL;
	if (saveConfig($_POST)) {
		displayStatus('Configuration saved.');
	} else {
		displayStatus('Configuration NOT saved.');
	}
} else {

?>

<div id="core">
	<form name="configform" action="<?php echo $_SERVER['PHP_SELF'].'?zfaction=config';?>" method="post">
		<div class="frame">
		<h2>General configuration</h2>

			<div class="twocols">
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
			<h2>Aggregator options</h2>
			<div class="twocols">
				<div class="col1">
					<label for="subtag">Default tag</label>
					<a href="#" class="info">(?)
					<span>the tag of subscriptions displayed by default. All if empty</span>
					</a>:
				</div>
				<div class="col2">
					<input type="text" name="subtag" id="subtag" value="<?php echo ZF_HOMETAG;?>"/>
				</div>
				<div class="col1">
					<label for="refreshmode">Refresh mode</label>
					<a href="#" class="info">(?)
					<span>How to refresh feeds.<br/> Automatic: when page is generated, or on request (special link).<br/>
						  Only on request: manual/scheduled refresh of feeds (by a cronjob for example).
					</span>
					</a>:
				</div>
				<div class="col2">
					<select name="refreshmode" id="refreshmode" >
						<option value="automatic" <?php if(ZF_REFRESHMODE=='automatic') echo 'selected="selected"';?>>Automatic</option>
						<option value="request" <?php if(ZF_REFRESHMODE!='automatic') echo 'selected="selected"';?>>Only by link</option>
					</select>
				</div>
				<div class="col1">
					<label for="sort">Sort mode</label>
					<a href="#" class="info">(?)
					<span>How to sort feeds, only for HTML output.<br/>By feed or by date.<br/>
					</span>
					</a>:
				</div>
				<div class="col2">
					<select name="sortmode" id="sort" >
						<option value="feed" <?php if(ZF_SORT=='feed') echo 'selected="selected"';?>>By feed</option>
						<option value="date" <?php if(ZF_SORT=='date') echo 'selected="selected"';?>>By date</option>
				   </select>
				</div>
				<div class="col1">
					<label for="trimsize">Limit news when sorted by date</label>
					<a href="#" class="info">(?)
					<span>When sorted by date, limit the list of news to the last X days/hours/news.
					</span>
					</a>:
				</div>
				<div class="col2">
					<input type="text" name="trimsize" id="trimsize" size="5" value="<?php echo ZF_TRIMSIZE;?>"/>
					<select name="trimtype" id="trimtype" >
						<option value="news" <?php if(ZF_TRIMTYPE=='news') echo 'selected="selected"';?>>latest news</option>
						<option value="days" <?php if(ZF_TRIMTYPE=='days') echo 'selected="selected"';?>>days</option>
						<option value="hours" <?php if(ZF_TRIMTYPE=='hours') echo 'selected="selected"';?>>hours</option>
					</select>
				</div>
		<?php //TODO: config for trimsize/type ?>
				<div class="col1">
					<label for="nofuture">Hide future news</label>
					<a href="#" class="info">(?)
					<span>Hide news with date in the future</span>
					</a>:
				</div>
				<div class="col2">
					<select name="nofuture" id="nofuture">
					  <option value="yes" <?php if(ZF_NOFUTURE=='yes') echo 'selected="selected"';?>>yes</option>
					  <option value="no" <?php if(ZF_NOFUTURE!='yes') echo 'selected="selected"';?>>no</option>
					</select>
				</div>
			</div>

		</div>
		<div class="frame">
			<h2>Template options</h2>

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
		<div id="saveconfig">
			<input type="submit" name="dosave" id="dosave" value="Save"/>
		</div>
	</form>
	<div id="extrainfo">
		<h3>Extra information</h3>

	<?php
		$refreshurl = ZF_URL.'/pub/refresh.php?key='.md5(ZF_ADMINNAME . ZF_ADMINPASS);
		echo 'Your personal ZebraFeeds refresh link to refresh all feeds via cron job:<br/>
			<textarea style="width: 100%; margin-top: 10px; margin-bottom: 10px;">'.$refreshurl.'</textarea><br/>
			  <em>Note: this link changes whenever you change the admin user or password.</em>';
	?>
	</div>
</div>
<?php

	echo '<div id="core">
			<div class="frame"><strong>Manage cached data</strong>
				<ul>
				<li>
					<a href="';
			echo $_SERVER['PHP_SELF'] . '?zfaction=cleanold" onclick="return confirm(\'Are you sure you want to delete old cache and history data?\');">Clean up</a>
					- clean up caches and history older than 2 weeks.
				</li>
				<li>
					<a href="';
					echo $_SERVER['PHP_SELF'] . '?zfaction=flush" onclick="return confirm(\'Are you sure you want to delete ALL cache and history data?\');">Flush!</a>
					- flush ALL caches and history.
				</li>
				</ul>
			</div>';

	echo '<div class="frame"><strong>Updates</strong><br/><br/>';
	echo "Your ZebraFeeds version: " . ZF_VER . "<br/><br/>";
	@$update = readfile('http://www.cazalet.org/zebrafeeds/latest.php');
	if (!$update)
		echo "Error: could not open update file.<br/><br/>You can check it manually at: <a href=\"http://cazalet.org/zebrafeeds/latest.php\">http://cazalet.org/zebrafeeds/latest.php</a>.";
	echo '</div></div>';

}


