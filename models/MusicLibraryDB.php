<?php
	
	class MusicLibraryDB {

		const USERNAME = "username";
		const FILE_PATHS = "filePaths";
		const TRACKS = "tracks";
		const ARTISTS = "artists";
		const ALBUMS = "albums";
		const ARTIST_IMAGES = "artistImages";
		const ALBUM_ARTWORK = "albumArtwork";

		private $musicLibraryCollection;

		public function __construct() {
			$m = new MongoClient();
			$this->musicLibraryCollection = $m->torrentcloud->musicLibrary;

		}

		public function get(User $user) {
			$musicLibrary = $this->musicLibraryCollection->findOne(array(self::USERNAME, $user->username));
			if(empty($musicLibrary)) {
				return false;
			} else {
				return $musicLibrary;
			}
		}

		public function update(MusicLibrary $library) {
			$updateSelect = array(self::USERNAME=>$library->user->username);
			$updateQuery = array(
							self::FILE_PATHS => $library->filePaths,
							self::TRACKS => $library->tracks,
							self::ARTISTS => $library->artists,
							self::ALBUMS => $library->albums,
							self::ARTIST_IMAGES => $library->artistImages,
							self::ALBUM_ARTWORK => $library->albumArtwork,
						);
			$this->musicLibraryCollection->update($updateSelect, array('$set'=>$updateQuery));
		} 




	}

?>