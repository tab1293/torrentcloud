<?php

	class TorrentCloud {
		private $user;
		private $torrentDB;
		private $torrentMetadataDB;
		private $transmission;

  		public function __construct(User $user) {
			$this->torrentDB = new TorrentDB();
			$this->torrentMetadataDB = new TorrentMetadataDB();
			$this->user = $user;
			$this->transmission = new Transmission(TRANSMISSION_URL, TRANSMISSION_USER, TRANSMISSION_PASS);
		}

		
		public function getTorrentMetadata($torrentURL) {
			$parsedUrl = parse_url($torrentURL);
			if($parsedUrl['scheme'] == "magnet") {
				$torrentMetadata = $this->getMagnetMetadata($torrentURL);
			} else if($parsedUrl['scheme'] == "http") {
				// Not implemented yet
				//$torrentMetadata = $this->getTorrentMetadata($torrentURL);
			} else {
				throw new Exception("Not a valid torrent link!");
			}

			// Check if the python script threw any errors
			if(isset($torrentMetadata['error'])) {
				throw new Exception($torrentMetadata['message']);
			}

			$torrentFound = $this->torrentDB->get($torrentMetadata['hashString']);
			if(empty($torrentFound)) {
			 	// Check if torrent size will exceed the user's space
				if($torrentMetadata["totalSize"] + $this->user->spaceUsed >= $this->user->spaceAllowed) {
					throw new TorrentMetadataException("Not enough space to add torrent!", $torrentMetadata);
				} 
			} else {
				if($torrentFound->userSpace + $this->user->spaceUsed >= $this->user->spaceAllowed) {
					throw new TorrentMetadataException("Not enough space to add torrent!", $torrentMetadata);
				}
			}
			// Check if the user already has this torrent
			if(in_array($torrentMetadata["hashString"], $this->user->torrentHashes)) {
				throw new TorrentMetadataException("You already have this torrent!", $torrentMetadata);
			} else {
				if(!in_array($torrentMetadata['hashString'], $_SESSION['validTorrents'])) {
					$_SESSION['validTorrents'][] = $torrentMetadata['hashString'];
				}
				return $torrentMetadata;
			}
		}

		public function addTorrent($torrentURL, $hashString, $torrentSize) {
			if(in_array($hashString, $_SESSION['validTorrents'])) {
				// Check if the torrent already exists in our database
				$existingTorrent = $this->torrentDB->get($hashString);
				if(empty($existingTorrent)) {
					$addedHashString = $this->transmission->add($torrentURL, $hashString);
					$torrentData = $this->transmission->poll($addedHashString);
					$torrentData[TorrentDB::TOTAL_SIZE] = $torrentSize;
					$torrentData[TorrentDB::USER_SPACE] = $torrentSize;
					$torrentData[TorrentDB::USERS] = array($this->user->username);
					$this->torrentDB->add($torrentData);
					$newTorrent = new Torrent($torrentData);
					$this->user->spaceUsed += $torrentSize;
					return $this->user->addTorrent($newTorrent);
				} else {
					// Add user to torrent's list and vice versa
					$oldSpaceUsed = round($existingTorrent->totalSize / count($existingTorrent->users));
					$newSpaceUsed = round($existingTorrent->totalSize / (count($existingTorrent->users) + 1));
					$spaceDifference = $oldSpaceUsed - $newSpaceUsed;
					$this->redistributeTorrentUsersSpace($existingTorrent, $spaceDifference);
					$existingTorrent->userSpace = $newSpaceUsed;
					$existingTorrent->users[] = $this->user->username;
					$existingTorrent->updateUsers();
					$this->user->spaceUsed += $newSpaceUsed;
					return $this->user->addTorrent($existingTorrent);
				}
				$hashKey = array_search($hashString, $_SESSION['validTorrents']);
				unset($_SESSION['validTorrents'][$hashKey]);
			} else {
				throw new Exception("You cannot add this torrent!");
			}
		}

		public function getMagnetMetadata($magnetUri) {
			$torrentHash = $this->torrentHashFromMagnetUri($magnetUri);
			if(is_null($torrentHash) || is_null($this->torrentMetadataDB->get($torrentHash))) {
				$torrentData = json_decode(exec("python /var/www/torrentcloud/python/magnet2metadata.py '" . $magnetUri . "'"), true);
				if(!isset($torrentData['error'])) {
					$torrentMetadata = array(
										TorrentMetadataDB::NAME => $torrentData['name'],
										TorrentMetadataDB::HASH_STRING => $torrentData['hashString'],
										TorrentMetadataDB::TOTAL_SIZE => $torrentData['totalSize'],

									);
					$this->torrentMetadataDB->add($torrentMetadata);
				}
				return $torrentData;
			} else {
				return $this->torrentMetadataDB->get($torrentHash);
			}
		}

		public function torrentHashFromMagnetUri($magnetUri) {
			$parsedUrl = parse_url($magnetUri);
			if($parsedUrl['scheme'] == "magnet") {
				$magnetURI = new MagnetUri($magnetUri);
				$xt = $magnetURI->xt;
				$splitLink = split(":", $xt);
				if ($splitLink[1] == "btih") {
					$torrentHash = $splitLink[2];
				} else {
					$torrentHash = null;
				}
			} else {
				$torrentHash = null;
			}
			return $torrentHash;
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

		public function removeTorrent($hashString) {
			$torrentToRemove = $this->torrentDB->get($hashString);
			if(empty($torrentToRemove)) {
				return array("status"=>false, "message"=>"This torrent could not be found!");
			} else {
				$userKey = array_search($this->user->username, $torrentToRemove->users);
				if(!is_null($userKey)) {
					$oldSpaceUsed = round($torrentToRemove->totalSize / count($torrentToRemove->users));
					$this->user->spaceUsed -= $oldSpaceUsed;
					unset($torrentToRemove->users[$userKey]);
					$newUserCount = count($torrentToRemove->users);
					if($newUserCount == 0) {
						$newSpaceUsed = 0;
					} else {
						$newSpaceUsed = round($torrentToRemove->totalSize / $newUserCount);
					}
					$this->redistributeTorrentUsersSpace($torrentToRemove, $oldSpaceUsed - $newSpaceUsed);
					$torrentToRemove->userSpace = $newSpaceUsed;
				} else {
					return array("status"=>false, "message"=>"You are not an owner of this torrent!");
				}
				$this->user->removeTorrent($torrentToRemove);
				$torrentToRemove->updateUsers();
				if(count($torrentToRemove->users) == 0) {
					$transmission = new Transmission(TRANSMISSION_URL, TRANSMISSION_USER, TRANSMISSION_PASS);
					$this->torrentDB->remove($torrentToRemove->hashString);
					$transmission->remove($torrentToRemove->hashString);
				}
				
				return array("status"=>true, "torrentHash"=>$torrentToRemove->hashString);
			}
		}
		
		public function redistributeTorrentUsersSpace(Torrent $torrent, $spaceDifference) {
			foreach($torrent->users as $userName) {
				$user = new User($userName);
				$user->spaceUsed -= $spaceDifference;
				$user->update();
			}
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