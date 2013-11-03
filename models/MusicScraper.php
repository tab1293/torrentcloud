<?php

class MusicScraper {

	private $lastfmApiKey = "e30096717458941a133f346b317b4439";
	private $ch;

	private $defaultArtistImage = "libraryData/music/artistImages/default-artist-image.jpg";
	private $defaultAlbumArtwork = "libraryData/music/artistImages/default-artwork.png";

	public function __construct() {
		$this->ch = curl_init();
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
	}

	public function lastfmGetArtistInfo($artist) {
		$artist = urlencode($artist);
		$artistInfoURL = "http://ws.audioscrobbler.com/2.0/?method=artist.getinfo&artist=".$artist."&api_key=".$this->lastfmApiKey."&format=json";
		curl_setopt($this->ch, CURLOPT_URL, $artistInfoURL);
		$artistResults = json_decode(curl_exec($this->ch), true);
		if(isset($artistResults['error'])) {
			return array("error"=>"Artist not found");
		}
		$artistInfo = $artistResults['artist'];
		$name = $artistInfo['name'];
		for($i=4; $i>=0; $i--) {
			if(isset($artistInfo['image'][$i])) {
				$artistImageURL = $artistInfo['image'][$i]['#text'];
				break;
			}
		}

		if(isset($artistImageURL)) {
			$artistImageExtension = strrchr($artistImageURL, ".");
			$artistImageFile = "/var/www/libraryData/music/artistImages/".$name.$artistImageExtension;
			file_put_contents($artistImageFile, file_get_contents($artistImageURL));
		}
		else {
			$artistImageFile = $this->defaultArtistImage;
		}

		return array("name"=>$name, "image"=>$artistImageFile);


	}

	public function lastfmGetArtistImage($artist) {
		$artist = urlencode($artist);
		$artistInfoURL = "http://ws.audioscrobbler.com/2.0/?method=artist.getinfo&artist=".$artist."&api_key=".$this->lastfmApiKey."&format=json";
		curl_setopt($this->ch, CURLOPT_URL, $artistInfoURL);
		$artistResults = json_decode(curl_exec($this->ch), true);
		if(isset($artistResults['error'])) {
			return $this->defaultArtistImage;
		}
		$artistInfo = $artistResults['artist'];
		$name = $artistInfo['name'];
		for($i=4; $i>=0; $i--) {
			if(isset($artistInfo['image'][$i])) {
				$artistImageURL = $artistInfo['image'][$i]['#text'];
				break;
			}
		}
		if(isset($artistImageURL)) {
			$artistImageExtension = strrchr($artistImageURL, ".");
			$artistImageFile = "/var/www/libraryData/music/artistImages/".$name.$artistImageExtension;
			file_put_contents($artistImageFile, file_get_contents($artistImageURL));
		}
		else {
			$artistImageFile = $this->defaultArtistImage;
		}
		$artistImageFile = str_replace("/var/www/", "", $artistImageFile);
		return $artistImageFile;
	}


	public function lastfmGetAlbumInfo($artist, $album) {
		$artist = urlencode($artist);
		$album = urlencode($album);
		$albumSearch = "http://ws.audioscrobbler.com/2.0/?method=album.getinfo&api_key=".$this->lastfmApiKey."&artist=".$artist."&album=".$album."&format=json&limit=1";
		curl_setopt($this->ch, CURLOPT_URL, $albumSearch);
		$albumResults = json_decode(curl_exec($this->ch), true);
		if(isset($albumResults['error'])) {
			return array("error"=>"Album not found");
		}
		else {
			$album = $albumResults['album'];
			$artist = $albumResults['artist'];
			$albumInfo = array();
			$albumInfo['name'] = $album;
			$albumInfo['artist'] = $artist;
			for($i=4; $i>=0; $i--) {
				if(isset($album['image'][$i])) {
					$artworkURL = $album['image'][$i]['#text'];
					$albumInfo['artwork'] = $artworkURL;
					break;
				}
			}

			if(isset($artworkURL)) {
				$artworkExtenstion = strrchr($artworkURL, ".");
				$albumArtworkFile = "/var/www/torrentcloud/libraryData/music/albumArtwork/".$artist."-".$album.$artworkExtenstion;
				file_put_contents($albumArtworkFile, file_get_contents($artworkURL));
			}
			else {
				$albumArtworkFile = $this->defaultArtistImage;
			}
			$albumInfo['artwork'] = $albumArtworkFile;

			$tracks = $album['tracks']['track'];
			$albumInfo['tracks'] = array();
			foreach($tracks as $track) {
				$albumInfo['tracks'][] = array("name"=>$track['name'], "artist"=>$track['artist']['name'], "trackNumber"=>$track["@attr"]["rank"]);
			}
		}
		
		return $albumInfo;

	}


	public function lastfmGetTrackInfo($artist, $track) {
		$artist = urlencode($artist);
		$track = urlencode($track);
		$trackInfoURL = "http://ws.audioscrobbler.com/2.0/?method=track.getInfo&api_key=".$this->lastfmApiKey."&artist=".$artist."&track=".$track."&format=json";
		curl_setopt($this->ch, CURLOPT_URL, $trackInfoURL);
		$trackResults = json_decode(curl_exec($this->ch), true);
		if(isset($trackResults['error'])) {
			return array("error"=>"Track not found");
		}
	
		$trackInfo = $trackResults['track'];
		$name = $trackInfo['name'];
		$artist = $trackInfo['artist']['name'];
		$album = $trackInfo['album']['title'];
		$trackNumber = $trackInfo['@attr']['position'];

		return array("name"=>$name, "artist"=>$artist, "album"=>$album, "trackNumber"=>$trackNumber);

	}


	public function lastfmArtistImageSearch($artist) {
		$artist = urlencode($artist);
		$artistSearch = "http://ws.audioscrobbler.com/2.0/?method=artist.search&artist=".$artist."&limit=1&format=json&api_key=".$this->lastfmApiKey;
		curl_setopt($this->ch, CURLOPT_URL, $artistSearch);
		$artistResults = json_decode(curl_exec($this->ch), true);
		$artistMatch = $artistResults['results']['artistmatches']['artist'];
		$imageUrl = $artistMatch['image'][4]['#text'];
		return $imageUrl;
	}

}

?>