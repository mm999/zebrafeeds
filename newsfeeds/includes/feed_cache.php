<?php
/*
 * Feed cache class for ZebraFeeds. 
 
 
 Borrowed from MagpieRSS:
 
 * Author:		Kellan Elliott-McCrea <kellan@protest.net>
 * Version:		0.51
 * License:		GPL
 *
 */

if (!defined('ZF_VER')) exit;

require_once($zf_path . 'includes/common.php');

class FeedCache {
	private $BASE_CACHE = './cache';	// where the cache files are stored
	private $MAX_AGE	= 3600;			// when are files stale, default one hour
	public $ERROR	   = "";		   // accumulate error messages

	public function __construct($base='', $age='') {
		if ( $base ) {
			$this->BASE_CACHE = $base;
		}
		if ( $age ) {
			$this->MAX_AGE = $age;
		}

		// attempt to make the cache directory
		if ( ! file_exists( $this->BASE_CACHE ) ) {
			$status = @mkdir( $this->BASE_CACHE, 0755 );

			// if make failed
			if ( ! $status ) {
				zf_error("Cache couldn't make dir '" . $this->BASE_CACHE . "'.");
			}
		}
	}

/*=======================================================================*\
	Function:	set
	Purpose:	add an item to the cache, keyed on url
	Input:		url from wich the rss file was fetched
	Output:		true on sucess
\*=======================================================================*/
	public function set ($url, $rss) {
		$cache_file = $this->file_name( $url );
		$fp = @fopen( $cache_file, 'w' );

		if ( ! $fp ) {
			zf_error(
				"Cache unable to open file for writing: $cache_file"
			);
			return 0;
		}


		$data = $this->serialize( $rss );
		fwrite( $fp, $data );
		fclose( $fp );

		return $cache_file;
	}

/*=======================================================================*\
	Function:	get
	Purpose:	fetch an item from the cache
	Input:		url from wich the rss file was fetched
	Output:		cached object on HIT, false on MISS
\*=======================================================================*/
	public function get ($url) {
		$cache_file = $this->file_name( $url );

		if ( ! file_exists( $cache_file ) ) {
			if (ZF_DEBUG) {
				zf_debug(
				"Cache doesn't contain: $url (cache file: $cache_file)"
				);
			}
			return 0;
		}

		$fp = @fopen($cache_file, 'r');
		if ( ! $fp ) {
			zf_error(
				"Failed to open cache file for reading: $cache_file"
			);
			return 0;
		}

		if ($filesize = filesize($cache_file) ) {
			$data = fread( $fp, filesize($cache_file) );
			$rss = $this->unserialize( $data );

			return $rss;
		}

		return 0;
	}

/*=======================================================================*\
	Function:	check_cache
	Purpose:	check a url for membership in the cache
				and whether the object is older then MAX_AGE (ie. STALE)
	Input:		url from wich the rss file was fetched
	Output:		cached object on HIT, false on MISS
\*=======================================================================*/
	public function check_cache ( $url ) {
		$filename = $this->file_name( $url );

		if ( file_exists( $filename ) ) {
			// find how long ago the file was added to the cache
			// and whether that is longer then MAX_AGE
			$mtime = filemtime( $filename );
			$age = time() - $mtime;
			if ( $this->MAX_AGE > $age ) {
				// object exists and is current
				return 'HIT';
			}
			else {
				// object exists but is old
				return 'STALE';
			}
		}
		else {
			// object does not exist
			return 'MISS';
		}
	}

	public function cache_age( $url ) {
		$filename = $this->file_name( $url );
		if ( file_exists( $filename ) ) {
			$mtime = filemtime( $filename );
			$age = time() - $mtime;
			return $age;
		}
		else {
			return -1;
		}
	}

/*=======================================================================*\
	Function:	serialize
\*=======================================================================*/
	public function serialize ( $feed ) {
		return serialize( $feed );
	}

/*=======================================================================*\
	Function:	unserialize
\*=======================================================================*/
	public function unserialize ( $data ) {
		return unserialize( $data );
	}

/*=======================================================================*\
	Function:	file_name
	Purpose:	map url to location in cache
	Input:		url from wich the rss file was fetched
	Output:		a file name
\*=======================================================================*/
	public function file_name ($url) {
		$filename = md5( $url );
		return join( DIRECTORY_SEPARATOR, array( $this->BASE_CACHE, $filename ) );
	}


}

?>
