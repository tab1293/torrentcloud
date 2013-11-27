<?php

	class TorrentFile extends File {

		public $torrentHash;

		public function __construct($fileData) {
			foreach($fileData as $key => $value) {
				if($key == "_id") {
					$this->id = $value;
				}
				$this->$key = $value;
			}
			

		}



	}

?>