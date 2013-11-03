<?php

	class TorrentFile extends File {

		public $torrentHash;

		public function __construct($fileData) {
			foreach($fileData as $key => $value) {
				$this->$key = $value;
			}
			

		}



	}

?>