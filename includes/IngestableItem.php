<?php
/*

all post-fetch processing of a news item after fetched by simplepie
then ready to store in DB
*/

class IngestableItem {

	public $data;

	public function __construct($sourceId, $itemId, $spItem) {
		$this->data = array(
			'id' => $itemId,
			'source_id' => $sourceId,
			'title' => $spItem->get_title(),
			'link' => $spItem->get_permalink(),
			'description' => $spItem->get_content(),
			'summary' => $spItem->get_description(),
			'pubdate' => $spItem->get_date('U'),
			'ts_fetch' => time(),
			'ts_impress' => 0xFFFFFFFF );

		$this->makeSummary();
		$this->makeEnclosures($spItem->get_enclosures());

		//TODO: purify the description, search for 1st image

	}

	private function makeSummary() {
		zf_debug('Normalizing summary for ' . $this->data['id'], DBG_FEED);
		if ((strlen($this->data['summary']) == 0) ) {
			  $this->data['summary'] = $this->data['description'];
		}

		if ((strlen($this->data['summary']) > 0) ) {
			$strsum = strip_tags($this->data['summary']);
			zf_debug(strlen($strsum). ' chars in stripped summary, unstripped: '. strlen($this->data['summary']), DBG_FEED);

	        //strip out inline css and simplify style tags
	        $search = array('#<(strong|b)[^>]*>(.*?)</(strong|b)>#isu', '#<(em|i)[^>]*>(.*?)</(em|i)>#isu', '#<u[^>]*>(.*?)</u>#isu');
	        $replace = array('<b>$2</b>', '<i>$2</i>', '<u>$1</u>');
	        $strsum = preg_replace($search, $replace, $strsum);

			if (strlen($strsum) > ZF_MAX_SUMMARY_LENGTH ) {
				zf_debug('truncating summary', DBG_FEED);
				$strsum = substr($strsum, 0, ZF_SUMMARY_TRUNCATED_LENGTH);
				// don't chop words. chop at the last space character found
				$lastspace = strrpos($strsum, ' ');
				$strsum = substr($strsum, 0, $lastspace).'...';
				$this->data['summary'] = $strsum;
			}
		}
	}

	private function makeEnclosures($enc) {

		if (is_array($enc)) {
			$this->data['(JSON)enclosures'] = array();
			foreach ($enc as $enclosure) {
				$this->data['(JSON)enclosures'][] = array(
										'link' => $enclosure->get_link(),
										'length' => $enclosure->get_length(),
										'type' => $enclosure->get_type());
			}
		}

	}

}