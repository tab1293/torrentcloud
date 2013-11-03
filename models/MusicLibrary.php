<?php

	

	class MusicLibrary {

		public $user;
		public $musicLibraryDB;
		public $torrentDB;
		public $fileDB;
		public $musicScraper;
		public $libraryData;
		public $filePaths = array();
		public $tracks;
		public $artists = array();
		public $albums = array();
		public $artistImages;
		public $albumArtwork;

		private $defaultArtistImage = "/var/www/libraryData/music/artistImages/default-artist-image.jpg";
		private $defaultAlbumArtwork = "/var/www/libraryData/music/artistImages/default-artwork.png";

		public function __construct(User $user) {
			$this->user = $user;
			$this->musicLibraryDB = new MusicLibraryDB();
			$this->torrentDB = new TorrentDB();
			$this->fileDB = new FileDB();
			$this->musicScraper = new MusicScraper();
			$this->libraryData = $this->musicLibraryDB->get($user);
			if(!empty($this->libraryData)) {	
				foreach($this->libraryData as $key => $value) {
					$this->$key = $value;
				}
			}

			$this->update();

		}

		public function update() {
			
			// Get torrent files
			foreach($this->user->torrentHashes as $hash) {
				$torrent = $this->torrentDB->get($hash);
				$files = $this->fileDB->getTorrentFiles($torrent);
				foreach($files as $file) {
					// If the file hasn't been processed already
					if(!in_array($file->path, $this->filePaths)) {
						// If file is a "music" file
						if(isset($file->tags)) {
							$this->process($file);
						}
					}
				}
			}

			$this->musicLibraryDB->update($this);

		}

		public function process($file) {
			$tags = $file->tags;
			$artist = isset($tags['artist']) ? $tags['artist'] : null;
			$band =  isset($tags['band']) ? $tags['band'] : null;
			$artist = isset($band) ? $band : $artist;
			$album =  isset($tags['album']) ? $tags['album'] : null;
			$title =  isset($tags['title']) ? $tags['title'] : null;
			$genre =  isset($tags['genre']) ? $tags['genre'] : null;
			$year =  isset($tags['year']) ? $tags['year'] : null;
			$trackNumber =  isset($tags['track_number']) ? $tags['track_number'] : null;
			$duration =  isset($tags['playtime_string']) ? $tags['playtime_string'] : null;
			
			if(isset($trackNumber)) {
				$number = strstr($trackNumber, "/", true);
				$trackNumber = empty($number) ? $trackNumber : $number;
			}

			if(!in_array($artist, $this->artists)) {
				$this->artists[] = $artist;
				$artistImage = $this->getArtistImage($artist);
				$this->artistImages[$artist] = $artistImage;
			}

			if(!in_array($album, $this->albums)) {
				$this->albums[] = $album;
				$artwork = isset($file->pictureURL) ? $file->pictureURL : $this->defaultAlbumArtwork;
				$this->albumArtwork[$album] = $artwork;
			}

			$this->tracks[] = array("artist"=>$artist, "album"=>$album, "title"=>$title, "genre"=>$genre, "year"=>$year, "trackNumber"=>$trackNumber, "duration"=>$duration, "path"=>$file->path, "url"=>$file->url);
			
		}

		function sortTracks($tracks) {

			$uSortTracks = function($track, $trackCmp) {
				if($track['artist'] < $trackCmp['artist']) {
					return -1;
				}
				else if($track['artist'] > $trackCmp['artist']) {
					return 1;
				}

				if($track['album'] < $trackCmp['album']) {
					return -1;
				}
				else if($track['album'] > $trackCmp['album']) {
					return 1;
				}
				
				if($track['trackNumber'] < $trackCmp['trackNumber']) {
					return -1;
				}
				else if($track['trackNumber'] > $trackCmp['trackNumber']) {
					return 1;
				}

				return 0;

			};

			usort($tracks, $uSortTracks);
			return $tracks;
		}

		function trackSearch() {
			$args = func_get_args();
			$num_args = func_num_args();
			$returnArray = array();
			foreach($this->tracks as $k=>$v) {
				$temp_array = array();
				for($i=0; $i < $num_args; $i += 2) {
					$property = $args[$i];
					$search = $args[$i + 1];
					if($v[$property] == $search){   
	                    $temp_array[] = $v;
	               }
				}
				if(count($temp_array) == $num_args/2) {
					$returnArray[] = $temp_array[0];
				}
	         }

	         return $returnArray;
		}

		public function getTracks() {
			return $this->sortTracks($this->tracks);
		}

		public function getArtists() {
				return $this->artists;
		}

		public function getAlbums() {
			return $this->albums;
		}

		public function getAlbumArtwork($album = null) {
			if($album == null) {
				return $this->albumArtwork;
			} else {
				return $this->albumArtwork[$album];
			}
		}

		public function getArtistImages() {
			return $this->artistImages;
		}

		public function getTracksByArtist($artist) {
			return $this->trackSearch("artist", $artist);
		}

		public function getTracksByAlbum($album) {
			$tracks = $this->trackSearch("album", $album);
			//return $this->sortTracks($tracks, "trackNumber");
			return $this->sortTracks($tracks);
		}

		public function getTracksByArtistAndAlbum($artist, $album) {
			$tracks = $this->trackSearch("artist",$artist, "album",$album);
			return $this->sortTracks($tracks);
		}

		public function getTrackByTitle($title) {
			return $this->trackSearh("title", $title);
		}

		public function getAlbumsByArtist($artist) {
			$artistTracks = $this->getTracksByArtist($artist);
			$albums = array();
			foreach($artistTracks as $track) {
				if(!in_array($track['album'], $albums)) {
					$albums[] = $track['album'];
				}
			}
			return $albums;
		}

		public function getAlbumArtworkByArtist($artist) {
			$albums = $this->getAlbumsByArtist($artist);
			$albumArtwork = array();
			foreach($albums as $album) {
				$albumArtwork[$album] = $this->albumArtwork[$album];
			}
			return $albumArtwork;
		}

		public function getArtistImage($artist) {
			return $this->musicScraper->lastfmGetArtistImage($artist);
		}

	}

?>