<?php

	const TORRENT_HASH = "torrentHash";
	const NAME = "name";
	const SIZE = "size";
	const DIRECTORY = "directory";
	const PATH = "path";
	const URL = "url";
	const DISPLAY = "display";
	const EXTENSION = "extension";
	const MIME_TYPE = "MIME_type";
	const TAGS = "tags";

	class FileDB {

		private $fileCollection;

		public function __construct() {
			$m = new MongoClient();
			$this->fileCollection = $m->torrentcloud->files;
		}

		public function add(File $file) {
			$fileFound = $this->fileCollection->findOne(array(PATH=>$file->path));
			if(empty($fileFound)) {
				$fileData = get_object_vars($file);
				$this->fileCollection->insert($fileData);
			}
		}

		public function getTorrentFiles(Torrent $torrent) {
			$fileCursor = $this->fileCollection->find(array(TORRENT_HASH=>$torrent->hashString));

			$files = array();
			foreach($fileCursor as $file) {
				$files[] = new TorrentFile($file);
			}
			return $files;
		}

	}


?>