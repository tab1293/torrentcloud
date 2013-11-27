<?php
	class FileDB {
		
		const ID = "_id";
		const TORRENT_HASH = "torrentHash";
		const NAME = "name";
		const EXTENSION = "extension";
		const SIZE = "size";
		const PATH = "path";
		const MIME_TYPE = "MIME_type";
		const TAGS = "tags";
		const ARTWORK_FILE = "artworkFile";
		const ARTWORK_MIME = "artworkMIME";

		private $fileDB;

		public function __construct() {
			$m = new MongoClient();
			$this->fileDB = $m->torrentcloud->files;
		}
		
		public function get($id) {
			$mongoId = new MongoId($id);
			$file = null;
			$fileFound = $this->fileDB->findOne(array(self::ID=>$mongoId));
			if($fileFound) {
				if(isset($fileFound[self::TORRENT_HASH])) {
					$file = new TorrentFile($fileFound);
				} else {
					//$file = new File($fileFound);
				}
			}
			
			return $file; 
		}

		public function add(File $file) {
			$mongoId = new MongoId($file->id);
			$fileFound = $this->fileDB->findOne(array(self::ID=>$mongoId));
			if(empty($fileFound)) {
				$fileData = get_object_vars($file);
				unset($fileData['id']);
				$this->fileDB->insert($fileData);
			}
		}

		public function getTorrentFiles(Torrent $torrent) {
			$fileCursor = $this->fileDB->find(array(self::TORRENT_HASH=>$torrent->hashString));

			$files = array();
			foreach($fileCursor as $file) {
				$files[] = new TorrentFile($file);
			}
			return $files;
		}
		
		public function removeTorrentFiles(Torrent $torrent) {
			$this->fileDB->remove(array(self::TORRENT_HASH=>$torrent->hashString));
		
		}
		
		public function getArtwork($name) {
			$artworkFound = $this->fileDB->findOne(array(self::ARTWORK_FILE=>$name));
			if($artworkFound) {
				return array(self::ARTWORK_FILE=>$artworkFound[self::ARTWORK_FILE], self::ARTWORK_MIME=>$artworkFound[self::ARTWORK_MIME]);
			} else {
				return null;
			}
		
		}

	}

?>