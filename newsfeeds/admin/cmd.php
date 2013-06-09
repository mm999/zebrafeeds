<?php
// ZebraFeeds - copyright (c) 2006 Laurent Cazalet
// http://www.cazalet.org/zebrafeeds
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.


/* AJAX commands script */

/* initialization part */
require_once('../init.php');
require_once($zf_path.'admin/adminfuncs.php');

zfLogin();
if (isset($_POST['action'])) {
    $action = $_POST['action'];
} else {
    $action == 'unknown';
}


if ($action == "savechannel" ) {


    $id = $_POST['id'];
    $listName = $_POST['list'];

    $list = new opml($listName);

    Header('Content-Type: text/html; charset='.ZF_ENCODING);

    if ($list->load()) {

		$sub = $list->getSubscription($id);
		if ($sub) {
			$sub->channel->title = $_POST['title'];
			$sub->channel->xmlurl =  $_POST['xmlurl'];
			$sub->channel->description = $_POST['description'];
			$sub->position = $_POST['position'];
			$sub->shownItems = $_POST['shownitems'];
			$sub->refreshTime = $_POST['refreshtime'];
			$sub->isSubscribed = ($_POST['issubscribed'] =='yes');

            if ($list->save()) {
                echo 'Saved';
            } else {
                echo $list->lastError;
            }
        }
    } else {
        echo $list->lastError;
    }

}


