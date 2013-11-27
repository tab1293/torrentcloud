<?php

	class MusicDB {
		const USERNAME = "username";
		const FILE_IDS = "fileIds";
		const TRACKS = "tracks";
		const ARTISTS = "artists";
		const ALBUMS = "albums";
		const ARTIST_IMAGES = "artistImages";
		const ALBUM_ARTWORK = "albumArtwork";

		private $musicDB;

		public function __construct() {
			$m = new MongoClient();
			$this->musicDB = $m->torrentcloud->music;
		}

		public function get(User $user) {
			$userMusic = $this->musicDB->findOne(array(self::USERNAME, $user->username));
			return $userMusic;
		}

		public function update(Music $userMusic) {
			$updateSelect = array(self::USERNAME=>$userMusic->user->username);
			$updateQuery = array(
							self::FILE_IDS=> $userMusic->fileIds,
							self::TRACKS => $userMusic->tracks,
							self::ARTISTS => $userMusic->artists,
							self::ALBUMS => $userMusic->albums,
							self::ARTIST_IMAGES => $userMusic->artistImages,
							self::ALBUM_ARTWORK => $userMusic->albumArtwork,
						);
			$this->musicDB->update($updateSelect, array('$set'=>$updateQuery));
		} 

	}

?>