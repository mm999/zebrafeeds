<?php

class VisitTracker {

	private $_visits;

	private static $instance=null;
	public static function getInstance(){
		if (self::$instance == null) {
			self::$instance = new VisitTracker();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->_visits = array();
	}


	public function getLastSessionEnd(){
		return $this->_visits['lastsessionend'];
	}


	// store the current time
	public function checkIn() {

		// 1: read visit information

		$this->_visits['lastvisit'] = 0;
		$this->_visits['lastsessionend'] = 0;

		$name = ZF_HISTORYDIR.'/visit.txt';

		$fp = @fopen($name, 'r');
		if ( ! $fp ) {
			zf_debug("Failed to open visit file for reading: $name", DBG_SESSION);
		} else {
			if ($filesize = filesize($name) ) {
				$data = fread( $fp, filesize($name) );
				$this->_visits = unserialize( $data );
			}
			zf_debug('last visit in server file: '.date('dS F Y h:i:s A', $this->_visits['lastvisit']), DBG_SESSION);
		}


		$now = time();
		// if our last visit happened X seconds ago
		if ($now - $this->_visits['lastvisit'] > ZF_SESSION_DURATION) {
			$this->_visits['lastsessionend'] = $this->_visits['lastvisit'];
			zf_debug("Session expired, last session end is now set to last visit", DBG_SESSION);

		}
		//echo date('dS F Y h:i:s A', $now) . ' - '. date('dS F Y h:i:s A', $this->_visits['lastvisit']);
		$this->_visits['lastvisit'] = $now;

		//STEP 2: record visit time
		// write visit information
		$fp = @fopen( $name, 'w' );

		if ( ! $fp ) {
			zf_debug("History unable to open visit file for writing: $name", DBG_SESSION);

		} else {
			zf_debug("saving session file", DBG_SESSION);
			$data = serialize( $this->_visits );
			fwrite( $fp, $data );
			fclose( $fp );
		}
	}

}