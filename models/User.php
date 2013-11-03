<?php

	class User {
		private $userDB;
		private $torrentDB;

		public $username;
		public $password;
		public $email;
		public $torrentHashes;
		public $torrents;
		public $friendUsernames;

		public function __construct($username) {
			$this->userDB = new UserDB();
			$this->torrentDB = new TorrentDB();
			$user = $this->userDB->get($username);
			foreach($user as $property => $value) {
				switch($property) {
					case UserDB::TORRENT_HASHES:
						$this->torrentHashes = $value;
						$torrentDB = new TorrentDB();
						$torrents = $torrentDB->get($this->torrentHashes);
						$this->torrents = array();
						foreach($torrents as $torrent) {
							$this->torrents[$torrent->hashString] = $torrent;
						}
						break;
					default:
						$this->$property = $value;

				}
			}
		}

		public function update() {
			$this->torrentHashes = array();
			foreach($this->torrents as $torrent) {
				$this->torrentHashes[] = $torrent->hashString;
			}
			$this->userDB->update($this);
		}

		public function login($password) {
			$passwordHash = md5($password);
			if($passwordHash == $this->password) {
				return true;
			}
			else {
				return false;
			}

		}

		public function addTorrent(Torrent $torrent) {
			$this->torrents[$torrent->hashString] = $torrent;
			$this->update();
			return $torrent;
		}

		public function removeTorrent(Torrent $torrent) {
			unset($this->torrents[$torrent->hashString]);
			$this->update();
			return $torrent->hashString;
		}




	}

?>