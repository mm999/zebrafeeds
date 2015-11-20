<?php

/*
DBProxy implements the logic of persistent storage for all sort of data (as tables)

 difference with feed cache
 - feed cache processes feeds, cache aging etc
 - dbproxy is a dumb provider of persistent storage for a tabularized version of these objects


 ultimately
 DBPRoxy is used by all higher level concepts managers

 FeedCache      SubscriptionStorage    (TBD) SavedItems

CREATE TABLE `items` (
	`id`	TEXT NOT NULL,
	`source_id`	TEXT NOT NULL,
	`title`	TEXT,
	`summary`	TEXT,
	`description`	BLOB,
	`image`	TEXT,
	`enclosures`	BLOB,
	`link`	TEXT,
	`pubdate`	NUMERIC,
	`ts_fetch`	INTEGER,
	`ts_impress`	INTEGER,
	`saved`	INTEGER,
	PRIMARY KEY(id)
);
*/

if (!defined('ZF_VER')) exit;

class DBProxy {

	private static $instance = NULL;

	protected $db;

	public static function getInstance() {
		zf_debug("calling DBProxy::getInstance", DBG_DB);
		if (self::$instance === NULL) {
			zf_debug("constructing new instance" . self::$instance, DBG_DB);
			self::$instance = new DBProxy();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->db = new medoo([
			'database_type' => 'sqlite',
			'database_file' => ZF_DATADIR.'/db.sqlite'
		]);
		zf_debug("constructing DB object to ".ZF_DATADIR.'/db.sqlite', DBG_DB);
	}

	/*
	get items
	sources: array of source IDs
	max: max number of items to return
	$sincePubdate: no item published before this timestamp
	$impressedSince: no item already impressed before this timestamp
	*/
	public function getItems($sources, $max, $sincePubdate, $impressedSince) {
		zf_debug("fetching $max items since $sincePubdate impressed since $impressedSince", DBG_DB);
		if (ZF_DEBUG && DBG_DB) var_dump($sources);
		$entries = $this->db->select(
								'items',
								array(  "enclosures",
										'id',
							  			'source_id',
						  				'title',
										'summary',
										'description',
										'pubdate',
										'ts_impress',
										'link',
										'image',
										'saved'
										),

								array(  'AND' => array( 'pubdate[>]' => $sincePubdate,
										'ts_impress[>=]' => $impressedSince,
										'source_id' => $sources),
										'ORDER'=>'pubdate DESC',
										'LIMIT' => $max));
		if (ZF_DEBUG && DBG_DB) {
			var_dump($this->db->log());
			var_dump($this->db->error());
		}
		return $entries;

	}

	public function getSavedItems() {
		$entries = $this->db->select(
								'items',
								array(  'enclosures',
										'id',
							  			'source_id',
						  				'title',
										'summary',
										'body',
										'pubdate',
										'ts_impress',
										'link',
										'image',
										'saved' ),
								array( 'saved' => 1, 'ORDER'=>'pubdate DESC'));
		return $entries;
	}

	public function getSingleItem($id) {
		$entries = $this->db->select(
								'items',
								array(  'enclosures',
										'id',
							  			'source_id',
						  				'title',
										'summary',
										'description',
										'pubdate',
										'ts_impress',
										'link',
										'image',
										'saved' ),
								array( 'id' => $id));
		if (isset($entries[0]))
			return $entries[0];
		else
			return FALSE;
	}

	/*
	get id of items published by a single source
	*/
	public function getCacheContent($sourceId) {
		$entries = $this->db->select(
								'items',
								array( 'id'),
								array(	'source_id' => $sourceId));
		return $entries;

	}

	/* clean up older items */
	public function purgeOldItems($oldestPubdate) {
		$entries = $this->db->delete('items', array('AND' => array( 'saved' => 0, 'pubdate[<]'=>$oldestPubdate)));
		// maybe overkill $this->db->execute('vacuum');
	}

	/* clean up source's item except the keepers (ie those still in the feed) */
	public function purgeSourceItems($sourceId, $keepers) {
		$entries = $this->db->delete('items', array('AND' => array( 'source_id' => $sourceId, 'id[!]'=>$keepers)));
		// maybe overkill $this->db->execute('vacuum');
	}

	public function setItemSaved($itemId, $savedFlag) {
		$entries = $this->db->update('items', array('saved' => $savedFlag?1:0), array( 'id' => $itemId ));

	}

	public function getLastUpdated($sourceId) {
		$value = $this->db->max('items', 'ts_fetch', array( 'source_id' => $sourceId ));
		return $value;
	}

	// $item: IngestableItem object
	public function recordItem($item){
		$last_id = $this->db->insert('items', $item->data);
	}

	/* useful after item has been fully dowloaded on demand */
	public function recordItemDescription($itemId, $newDesc) {
		$this->db->update('items', array('description' => $newDesc), array('id'=>$itemId));
	}

	public function markItemsAsImpressed($itemIds, $ts=-1) {
		if ($ts == -1) $ts = time();
		$this->db->update('items',
							array('ts_impress' => $ts),
							array('AND' => array('id'=>$itemIds, 'ts_impress' => 0xFFFFFFFF))
						);
	}


}