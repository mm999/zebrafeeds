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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	 See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.


/* AJAX commands script */

/* initialization part */
require_once('../init.php');
require_once('adminfuncs.php');

zfLogin();
if (isset($_POST['action'])) {
	$action = $_POST['action'];
	$storage = SubscriptionStorage::getInstance();
} else {
	$action == 'unknown';
}

	Header('Content-Type: text/html; charset='.ZF_ENCODING);

switch ($action) {

	case 'store':
		$id = $_POST['id'];

		$sub = $storage->getSubscription($id);
		if ($sub) {
			$sub->title = $_POST['title'];
			$sub->xmlurl =	 $_POST['xmlurl'];
			$sub->description = $_POST['description'];
			$sub->position = $_POST['position'];
			$sub->shownItems = $_POST['shownitems'];
			$sub->isActive = ($_POST['isactive'] =='yes');
			$sub->tags = explode(',', $_POST['tags']);

			if ($storage->storeSubscription($sub)) {
				echo 'Saved';
			} else {
				echo $storage->lastError;
			}
		} else {
			echo $storage->lastError;
		}
		break;

	case 'delete':
		$id = $_POST['id'];

		if (!$storage->cancelSubscription($id)) echo $storage->lastError;
		break;

}


