<?php

class Source {

	public $id;

	//feed title
	public $title;

	// source website address
	public $link;

	// source or user generated description
	public $description;

	//URL of the subscription file - feed
	public $xmlurl;

	public function __construct(){
	}


	public static function create($title, $link, $description, $xmlurl) {
		$instance = new Self();
		$instance->xmlurl = $xmlurl;
		$instance->link = $link;
		$instance->description = $description;
		$instance->title = $title;
		$instance->id = zf_makeId($instance->xmlurl, '');
		return $instance;
	}


	public static function fromXMLattributes(&$attributes) {

		$instance = new Self();

		if ($attributes['TITLE'] != '') {
			$instance->title = html2specialchars($attributes['TITLE']);
		}

		if ($attributes['HTMLURL'] != '') {
			$instance->link = html2specialchars($attributes['HTMLURL']);
		}

		if ($attributes['XMLURL'] != '') {
			$instance->xmlurl = html2specialchars($attributes['XMLURL']);
		}

		if ($attributes['DESCRIPTION'] != '') {
			$instance->description = html2specialchars($attributes['DESCRIPTION']);
		}
		$instance->id = (string)zf_makeId($instance->xmlurl, '');

		return $instance;
	}

	public static function fromAddress($xmlurl){
		$proxy = new SourceProxy($xmlurl);
		return $proxy->makeSourceFromAddress($xmlurl);
	}


}
