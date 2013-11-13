<?php
	class TorrentMetadata {

		private $torrentMetadataDB;

		public $name;
		public $hashString;
		public $totalSize;

		public function __construct($torrentMetadata) {
			$this->torrentMetadataDB = new TorrentMetadataDB();
			foreach($torrent as $property => $value) {
				if($property != "error") {
					$this->$property = $value;
				}
			}
		}
	}
?>