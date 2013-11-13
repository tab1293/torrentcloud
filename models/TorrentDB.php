<?php
	
	class TorrentDB {
		
		const HASH_STRING = "hashString";
		const NAME = "name";
		const DOWNLOAD_DIR = "downloadDir";
		const TOTAL_SIZE = "totalSize";
		const FILES = "files";
		const ETA = "eta";
		const STATUS = "status";
		const PEERS_CONNECTED = "peersConnected";
		const PEERS_GETTING_FROM_US = "peersGettingFromUs";
		const PEERS_SENDING_TO_US = "peersSendingToUs";
		const PERCENT_DONE = "percentDone";
		const RATE_DOWNLOAD = "rateDownload";
		const RATE_UPLOAD = "rateUpload";
		const UPLOAD_RATIO = "uploadRatio";
		const USERS = "users";
		const USER_SPACE = "userSpace";

		private $torrentCollection;

		public function __construct() {
			$m = new MongoClient();
			$this->torrentCollection = $m->torrentcloud->torrents;
		}

		public function get($hashStrings) {
			$torrents = array();
			if(is_array($hashStrings)) {
				foreach($hashStrings as $hashString) {
					$torrentData = $this->torrentCollection->findOne(array("hashString"=>$hashString));
					if(isset($torrentData)) {
						$torrents[] = new Torrent($torrentData);
					}
				}
			} else {
				$torrentData = $this->torrentCollection->findOne(array("hashString"=>$hashStrings));
				if(is_null($torrentData)) {
					return null;
				} else {
					return new Torrent($torrentData);
				}
			}
			return $torrents;
		}

		public function add($torrentData) {
			$torrentFound = $this->torrentCollection->findOne(array("hashString"=>$torrentData[self::HASH_STRING]));
			if(is_null($torrentFound)) {
				$this->torrentCollection->insert($torrentData);
			} 
		}

		public function remove($hashString) {
			$this->torrentCollection->remove(array("hashString"=>$hashString));
		}

		public function update(Torrent $torrent) {
			$updateSelect = array(self::HASH_STRING=>$torrent->hashString);
			$updateQuery = array(
						self::FILES=>$torrent->files,
						self::TOTAL_SIZE=>$torrent->totalSize,
						self::ETA=>$torrent->eta,
						self::STATUS=>$torrent->status,
						self::PEERS_CONNECTED=>$torrent->peersConnected,
						self::PEERS_GETTING_FROM_US=>$torrent->peersGettingFromUs,
						self::PEERS_SENDING_TO_US=>$torrent->peersSendingToUs,
						self::PERCENT_DONE=>$torrent->percentDone,
						self::RATE_DOWNLOAD=>$torrent->rateDownload,
						self::RATE_UPLOAD=>$torrent->rateUpload,
						self::UPLOAD_RATIO=>$torrent->uploadRatio,
					);
			$this->torrentCollection->update($updateSelect, array('$set'=>$updateQuery));
		}
		
		public function updateUsers(Torrent $torrent) {
			$updateSelect = array(self::HASH_STRING=>$torrent->hashString);
			$updateQuery = array(
						self::USERS=>$torrent->users,
						self::USER_SPACE=>$torrent->userSpace,
					);
			$this->torrentCollection->update($updateSelect, array('$set'=>$updateQuery));
		}





	}

?>