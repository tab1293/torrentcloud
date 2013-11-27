<?php

	class Music {

		public $user;
		public $tracks = array();
		public $artists = array();
		public $albums = array();
		public $artistImages = array();
		public $albumArtwork = array();

		const ALBUM_ARTWORK_PATH = "/artwork/music/album/";
		const ARTIST_IMAGE_PATH = "/artwork/music/artist/";

		const DEFAULT_ARTIST_IMAGE = "/artwork/music/default-artist-image.jpeg";
		const DEFAULT_ALBUM_ART = "/artwork/music/default-albumart.jpg";

		public function __construct(User $user) {
			$this->user = $user;
			$torrentDB = new TorrentDB();
			$fileDB = new FileDB();
			
			foreach($this->user->torrentHashes as $hash) {
				$torrent = $torrentDB->get($hash);
				$files = $fileDB->getTorrentFiles($torrent);
				foreach($files as $file) {
					// If file is a "music" file
					if($file->type == File::MUSIC_TYPE) {
						$this->process($file);
					}
				}
			}
		}

		public function process($file) {
			$tags = $file->tags;
			$artist = isset($tags['artist']) ? $tags['artist'] : "Unknown Artist";
			$band =  isset($tags['band']) ? $tags['band'] : null;
			$artist = isset($band) ? $band : $artist;
			$album =  isset($tags['album']) ? $tags['album'] : "Unknown Album";
			$title =  isset($tags['title']) ? $tags['title'] : $file->name;
			$genre =  isset($tags['genre']) ? $tags['genre'] : "Unknown Genre";
			$year =  isset($tags['year']) ? $tags['year'] : "Unknown Year";
			$trackNumber =  isset($tags['track_number']) ? $tags['track_number'] : null;
			$duration =  isset($tags['playtime_string']) ? $tags['playtime_string'] : null;
			
			if(isset($trackNumber)) {
				$trackNumber = ltrim($trackNumber, '0');
				$number = strstr($trackNumber, "/", true);
				$trackNumber = empty($number) ? $trackNumber : $number;
			}

			if(!in_array($artist, $this->artists)) {
				$this->artists[] = $artist;
				$artistImage = $this->fetchArtistImage($artist);
				$this->artistImages[$artist] = $artistImage;
			}

			if(!in_array($album, $this->albums)) {
				$this->albums[$artist] = $album;
				$artwork = isset($file->artworkFile) ? str_replace("/var/www", "", $file->artworkFile) : self::DEFAULT_ALBUM_ART;
				$this->albumArtwork[$album] = $artwork;
			}

			$this->tracks[] = array("fileId"=>$file->id, "artist"=>$artist, "album"=>$album, "title"=>$title, "genre"=>$genre, "year"=>$year, "trackNumber"=>$trackNumber, "duration"=>$duration);
			
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
				
				if(strtolower($track['title']) < strtolower($trackCmp['title'])) {
					return -1;
				}
				else if(strtolower($track['title']) > strtolower($trackCmp['title'])) {
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

		public function getTracksByArtist($artist) {
			$tracks = $this->trackSearch("artist", $artist);
			return $this->sortTracks($tracks);
		}

		public function getTracksByAlbum($album) {
			$tracks = $this->trackSearch("album", $album);
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


		public function getAlbumArtwork($album = null) {
			if($album == null) {
				return $this->albumArtwork;
			} else {
				return $this->albumArtwork[$album];
			}
		}

		public function getArtistImages($artist = null) {
			if($artist == null) {
				return $this->artistImages;
			} else {
				return $this->artistImages[$artist];
			}
		}

		public function fetchArtistImage($artist) {
			$musicScraper = new MusicScraper();
			$artistImageData = $musicScraper->lastfmGetArtistImage($artist);
			if(is_null($artistImageData)) {
				return self::DEFAULT_ARTIST_IMAGE;
			} else {
				$artistImageFilename = self::ARTIST_IMAGE_PATH . $artistImageData["imageName"];
				$artistImageUrl = $artistImageData['imageUrl'];
				file_put_contents("/var/www".$artistImageFilename, file_get_contents($artistImageUrl));
				return $artistImageFilename;
			}
		}

	}

?>