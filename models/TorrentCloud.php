<?php

	class TorrentCloud {
		private $torrentDB;

		public function __construct() {
			$this->torrentDB = new TorrentDB();
		}

		public function fetchTorrent($url) {
			$transmission = new Transmission(TRANSMISSION_URL, TRANSMISSION_USER, TRANSMISSION_PASS);
			$torrentHash = $this->torrentHashFromUrl($url);
			$addedHash = $transmission->add($url,$torrentHash);
			$torrentData = $transmission->poll($addedHash);
			/*while($torrentData['files'] == null) {
				$torrentData = $transmission->poll($addedHash);
				sleep(1);
			}*/
			$this->torrentDB->add($torrentData);
			$torrent = new Torrent($torrentData);
			return $torrent;
		}

		public function torrentHashFromUrl($url) {
			$parsedUrl = parse_url($url);
			if($parsedUrl['scheme'] == "magnet") {
				$magnetURI = new MagnetUri($url);
				$xt = $magnetURI->xt;
				$splitLink = split(":", $xt);
				if ($splitLink[1] == "btih") {
					$torrentHash = $splitLink[2];
				} else {
					$torrentHash = "badmagnet";
				}
			} else if($parsedUrl['scheme'] == "http") {
				$torrentHash = "http"; 	
			} else {
				$torrentHash = "error";
			}
			return $torrentHash;
		}

		public function getTorrent(Torrent $torrent) {
			$torrentData = $this->torrentDB->get($torrent->hashString);
			return new Torrent($torrentData);
		}

		public function pollTorrent(Torrent $torrent) {
			$transmission = new Transmission(TRANSMISSION_URL, TRANSMISSION_USER, TRANSMISSION_PASS);
			$torrentData = $transmission->poll($torrent->hashString);
			$torrent = new Torrent($torrentData);
			$this->torrentDB->update($torrent);
			return $torrent;
		}

		public function pollTorrents($torrents) {
			$transmission = new Transmission(TRANSMISSION_URL, TRANSMISSION_USER, TRANSMISSION_PASS);

			$polledTorrents = array();
			foreach($torrents as $torrent) {
				$torrentData = $transmission->poll($torrent->hashString);
				$torrent = new Torrent($torrentData);
				$this->torrentDB->update($torrent);
				$polledTorrents[] = $torrent;
			}
			return $polledTorrents;
		}

		public function removeTorrent(Torrent $torrent) {
			$transmission = new Transmission(TRANSMISSION_URL, TRANSMISSION_USER, TRANSMISSION_PASS);

			$this->torrentDB->remove($torrent->hashString);
			$transmission->remove($torrent->hashString);

		}

		public function calculatePollTimeout($torrents) {
			if(empty($torrents)) {
				return STOPPED_POLL_TIME;
			}
			$stopped = false;
			$downloading = false;
			$seeding = false;
			foreach($torrents as $torrent) {
				switch($torrent->status) {
					case 0:
						$stopped = true;
						break;
					case 1:
					case 2:
					case 3:
					case 4:
						$downloading = true;
						break;
					case 5:
					case 6:
						$seeding = true;
						break;
					default:
						return 60000;

				}
				
			}
			if($downloading) {
				return DOWNLOADING_POLL_TIME;
			} else if($seeding) {
				return SEEDING_POLL_TIME;
			} else if($stopped) {
				return STOPPED_POLL_TIME;
			}
		}

	}

?>