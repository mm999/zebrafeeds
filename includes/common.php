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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	 See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.


/* returns what list has been set in URL or by configured by default
 returns a list name, but only if the list exists
 if return is empty, means that no valid list (requested or default)
 could be found
*/

/* 4 functions borrowed from PicoFarad - by F. Guillot (author of Miniflux) */
function param($name, $default_value = null)
{
	return isset($_GET[$name]) ? $_GET[$name] : (isset($_POST[$name])? $_POST[$name]:$default_value);
}


function int_param($name, $default_value = 0)
{
	return isset($_GET[$name]) && ctype_digit($_GET[$name]) ? (int) $_GET[$name] : $default_value;
}


function value($name)
{
	$values = values();
	return isset($values[$name]) ? $values[$name] : null;
}


function values()
{
	if (! empty($_POST)) {

		return $_POST;
	}

	$result = json_decode(body(), true);

	if ($result) {
		return $result;
	}

	return array();
}

function content_type($mimetype)
{
	header('Content-Type: '.$mimetype);
}



/* returns an array of available user templates
filters out the SYSTEM.* */
function zf_getTemplateNames() {
	$result = array();
	$handle = opendir(ZF_TEMPLATESDIR);
	while($dirfile = readdir($handle)) {
		if( is_file(ZF_TEMPLATESDIR.'/'.$dirfile) && substr($dirfile, strlen($dirfile)-4, strlen($dirfile))=='html' && substr($dirfile,0,7)!='SYSTEM.' ) {
			$templatef = substr($dirfile, 0, strlen($dirfile)-5);
			$result[] = $templatef;
		}
	}
	closedir($handle);
	return $result;
}

/* if encoding functions are available, transcode a string to our target output encoding
configured in the admin page */
function zf_transcode($string, $enc='auto') {
	$transcoded = $string;
	if (function_exists("mb_convert_encoding")) {
		/*if ($enc == 'auto')
			$enc = mb_detect_encoding($string);*/
		if ($enc != ZF_ENCODING)
			$transcoded = mb_convert_encoding($string, ZF_ENCODING);
	}

	return $transcoded;
}



// log in an area. If no area provided, log it anyway
function zf_debug($msg, $area=DBG_ALL) {
	if (ZF_DEBUG & $area) {
		$btr = debug_backtrace();
		$line = $btr[0]['line'];
		$file = basename($btr[0]['file']);

		if (ZF_DEBUG_CONSOLE) {
			trigger_error("(ZF/$file:$line) ".$msg, E_USER_NOTICE);
		} else {
			print "ZF/($file:$line) $msg".(ZF_DEBUG_HTML==1?'<br/>':'')."\n";
		}
	}
}

function zf_error($msg, $lvl=E_USER_WARNING) {
	$btr=debug_backtrace();
	$line=$btr[0]['line'];
	$file=basename($btr[0]['file']);
	print "<pre>ERR ($file:$line) $msg</pre>\n";
}


function zf_debugRuntime($location) {

	if (ZF_DEBUG & DBG_RUNTIME) {
		global $zf_debugData;
		$zf_debugData['clock'][] = microtime();
		$count = count($zf_debugData['clock']);

		// take previous element
		$entry = explode(' ', $zf_debugData['clock'][$count-2]);
		$startVal = (float)$entry[0] + (float)$entry[1];
		$entry = explode(' ', $zf_debugData['clock'][$count-1]);
		$runVal = (float)$entry[0] + (float)$entry[1] - (float)$startVal;
		$part1="[$location] Run time since last check: ".$runVal." sec.";

		// take first element of the debug array
		$entry = explode(' ', $zf_debugData['clock'][0]);
		$startVal = (float)$entry[0] + (float)$entry[1];
		$entry = explode(' ', $zf_debugData['clock'][$count-1]);
		$runVal2 = (float)$entry[0] + (float)$entry[1] - (float)$startVal;
		zf_debug($part1." (total: ".$runVal2." sec.)");

		// try to use PHP build in function
		if( function_exists('memory_get_usage') ) {
			zf_debug("Memory: using ".memory_get_usage()." bytes");
		}

		if (function_exists('getrusage')) {
			$dat = getrusage();
			$utime_after = $dat["ru_utime.tv_sec"].$dat["ru_utime.tv_usec"];
			$stime_after = $dat["ru_stime.tv_sec"].$dat["ru_stime.tv_usec"];

			$utime_elapsed = ($utime_after - $zf_debugData['utime_before']);
			$stime_elapsed = ($stime_after - $zf_debugData['stime_before']);

			zf_debug("Elapsed user time: $utime_elapsed microseconds");
			zf_debug("Elapsed system time: $stime_elapsed microseconds");
		}
	}
}


/* from http://snipplr.com/view.php?codeview&id=4912 */

function plural($num) {
	if ($num != 1)
		return "s";
}

function getRelativeTime($date_ts) {
	$diff = time() - $date_ts;
	$ago = 'ago';

	if ($diff < 0 )
		$ago = 'later';

	$diff = abs($diff);

	if ($diff<60)
		return $diff . "sec $ago";
	$diff = round($diff/60);
	if ($diff<60)
		return $diff . "min $ago";
	$diff = round($diff/60);
	if ($diff<24)
		return $diff . "h $ago";
	$diff = round($diff/24);
	if ($diff<7)
		return $diff . "d $ago";
	$diff = round($diff/7);
	return $diff . " week" . plural($diff) . " $ago";
}


/* nicked from Wallabag */
function getZfUrl() {

        $https = (!empty($_SERVER['HTTPS'])
                    && (strtolower($_SERVER['HTTPS']) == 'on'))
            || (isset($_SERVER["SERVER_PORT"])
                    && $_SERVER["SERVER_PORT"] == '443') // HTTPS detection.
            || (isset($_SERVER["SERVER_PORT"]) //Custom HTTPS port detection
                    && $_SERVER["SERVER_PORT"] == SSL_PORT)
             || (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
                    && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https');

        $serverport = (!isset($_SERVER["SERVER_PORT"])
            || $_SERVER["SERVER_PORT"] == '80'
            || ($https && $_SERVER["SERVER_PORT"] == '443')
            || ($https && $_SERVER["SERVER_PORT"]==SSL_PORT) //Custom HTTPS port detection
            ? '' : ':' . $_SERVER["SERVER_PORT"]);

        $scriptname = str_replace('/index.php', '/', $_SERVER["SCRIPT_NAME"]);

        if (!isset($_SERVER["HTTP_HOST"])) {
            return $scriptname;
        }

        $host = (isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME']));

        if (strpos($host, ':') !== false) {
            $serverport = '';
        }


        return 'http' . ($https ? 's' : '') . '://'
            . $host . $serverport . $scriptname;

}

