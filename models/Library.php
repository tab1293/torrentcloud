<?php

	 class Library {

		private $user;
		private $fileDB;
		private $files = array();
		private $music;

		public function __construct($user) {
			$this->user = $user;
			$this->fileDB = new FileDB();

			// Get torrent files
			foreach($user->torrentHashes as $hash) {
				$this->files = array_merge($this->files, $this->fileDB->getTorrentFiles($hash));
			}
			


		}

		public function refresh() {

		}

		public function setMusic() {

		}
	}

?>