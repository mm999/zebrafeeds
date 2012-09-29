<?php

/* this script handles RSS and javascript exports
it should redirect to the publisher's home page in case of problem */

/* do this first so that it overrides the config loaded from init.php */
if (isset($_GET['encoding']) && !empty($_GET['encoding'])) {
	define('ZF_ENCODING',$_GET['encoding']);
}
// define it before init
define('ZF_SHOWCREDITS', 'no');
require_once('init.php');
require_once($zf_path . 'includes/aggregator.php');

if (ZF_USEOPML != 'yes') {
	// cannot do anything if we dont use opml lists
	die();
}

/* parse parameters only if $type and $listname not already set
f: type of output. supported: js and rss
zflist: opml list to render
*/

if (!isset($type)) {
	if (isset($_GET['f']) && !empty($_GET['f'])) {
		$type = $_GET['f'];
	}
}

if (!isset($listname)) {
	if (isset($_GET['zflist']) && !empty($_GET['zflist'])) {
		$listname = $_GET['zflist'];
	}
}

if (!isset($matchExpression)) {
	if (isset($_GET['zfmatch']) && !empty($_GET['zfmatch'])) {
		$matchExpression = $_GET['zfmatch'];
	}
}


/*RSS/JS selection*/
if ($type == 'rss') {
	$contentType = 'application/xml';
	$tname = 'SYSTEM.rss';

} elseif ($type == 'js') {
	$contentType = 'text/javascript';
	if (!isset($template)) {
		if (isset($_GET['zftemplate']) && !empty($_GET['zftemplate'])) {
			$tname = $_GET['zftemplate'];
		}
	}
} else {
	//invalid
	if (ZF_DEBUG) echo "type error: want rss or js";
	die();

}

if ($listname == '') {
	if (ZF_DEBUG) echo "listname error";
	die;
}
if ($tname == '') {
	if (ZF_DEBUG) echo "template name error";
	die;
}

/* configure our aggregator object */
$zf_aggregator = new aggregator();

/* General */
Header('Content-Type: '.$contentType.'; charset='.ZF_ENCODING);

$templ = new template($tname);

$zf_aggregator->useTemplate($templ);

if (isset($matchExpression)) {
	$zf_aggregator->matchNews($matchExpression);
}

$list = new opml($listname);
if ($list->load()) {

	// use view and trim options from the list settings...
	$zf_aggregator->useList($list);

	// ...unless we set something different
	if (isset($trimtype) && isset($trimsize)) {
		$zf_aggregator->setTrimOptions($trimtype,$trimsize);
	}


	if ($type == 'rss') {
		$zf_aggregator->exportAggregatedChannels();
	} else {

		$templ->setWrappingType('js');
		$zf_aggregator->viewPage();
	}

}

?>
