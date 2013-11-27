<?php

	class TorrentZip {
	
		public $torrentHash;
		public $zipSize;
		public $zipName;
		
		public function __construct($torrentZipData) {
			foreach($torrentZipData as $property => $value) {
				$this->$property = $value;
			}
		}
	
	}

?>