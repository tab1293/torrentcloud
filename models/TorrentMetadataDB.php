<?php
	class TorrentMetadataDB {
		const HASH_STRING = "hashString";
		const NAME = "name";
		const TOTAL_SIZE = "totalSize";

		private $torrentMetadataDB;

		public function __construct() {
			$m = new MongoClient();
			$this->torrentMetadataDB = $m->torrentcloud->torrentMetadata;
		}

		public function get($torrentHash) {
			return $this->torrentMetadataDB->findOne(array(self::HASH_STRING=>$torrentHash));
		}

		public function add($torrentMetadata) {
			$torrentFound = $this->torrentMetadataDB->findOne(array(self::HASH_STRING=>$torrentMetadata[self::HASH_STRING]));
			if(is_null($torrentFound)) {
				$this->torrentMetadataDB->insert($torrentMetadata);
			}
		}
	}
?>