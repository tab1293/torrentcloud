<?php

	class TorrentZipDB {
	
		const TORRENT_HASH = "torrentHash";
		const ZIP_NAME = "zipName";
		const ZIP_SIZE = "zipSize";
		
	
		private $torrentZipDB;
		
		public function __construct() {
			$m = new MongoClient();
			$this->torrentZipDB = $m->torrentcloud->torrentZip;
			
		}
		
		public function add($torrentZipData) {
			$torrentZipFound = $this->torrentZipDB->findOne(array(self::TORRENT_HASH=>$torrentZipData[self::TORRENT_HASH]));
			if(is_null($torrentZipFound)) {
				$this->torrentZipDB->insert($torrentZipData);
			} 
		
		}
		
		public function get($torrentHash) {
			$torrentZipData = $this->torrentZipDB->findOne(array(self::TORRENT_HASH=>$torrentHash));
			if(is_null($torrentZipData)) {
				return null;
			} else {
				return $torrentZipData;
			}
		
		}
	
	}

?>