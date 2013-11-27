<?php

	abstract class File {

		const MUSIC_TYPE = "music";
		const OTHER_TYPE = "other";
		const MUSIC_ARTWORK_DIR = "/var/www/artwork/music/";	

		// Static values
		private $supportedAudioMIMES = array("audio/mpeg", "audio/mp4");
		private $supportedAudioExtensions = array("mp3", "m4a");

		// Constructed values
		public $id;
		public $name;
		public $extension;
		public $path;
		public $size;
		public $MIME_type;


		public function analyze() {
			if(in_array($this->MIME_type, $this->supportedAudioMIMES) || in_array($this->extension, $this->supportedAudioExtensions)) {
				$this->makeMusicType();
				$this->type = self::MUSIC_TYPE;
			} else {
				$this->type = self::OTHER_TYPE;
			}
		}

		public function makeMusicType() {
			$getID3 = new getID3;
			$fileInfo = $getID3->analyze($this->path);
			$this->tags = array();

			// Get the latest id3 tags if they exist
			$fileTags = $fileInfo['tags_html'];
			if(isset($fileTags['id3v2'])) {
				$tags = $fileTags['id3v2'];
			} else if(isset($fileTags['id3v1'])) {
				$tags = $fileTags['id3v1'];
			} else {
				$tags = null;
			}

			// Loop through tags and set the array appropriately
			if(isset($tags)) {
				foreach($tags as $tagAttr => $tagValues) {
					$tagCount = count($tagValues);
					if($tagCount == 1) {
						$this->tags[$tagAttr] = $tagValues[0];
					}
					elseif($tagCount > 1) {
						$this->tags[$tagAttr] = array();
						foreach($tagValues as $tag) {
							$this->tags[$tagAttr][] = $tag;
						}
					}
					else {
						$this->tags[$tagAttr] = "";
					}
				}
			}

			$this->tags['playtime_seconds'] = $fileInfo['playtime_seconds'];
			$this->tags['playtime_string'] = $fileInfo['playtime_string'];

			// Artwork code
			if(isset($fileInfo['comments']['picture'][0])) {	
				$pictureData = $fileInfo['comments']['picture'][0]['data'];
				$pictureMIME = $fileInfo['comments']['picture'][0]['image_mime'];
				/*
				Do we really need the picture MIME?
				error_log($pictureMIME);
				switch($pictureMIME) {
					case "image/jpeg":
					case "image/pjpeg":
						$extension = ".jpg";
						break;
					case "image/png": 
						$extension = ".png";
						break;
					default:
						$extension = null; 
				}*/

				if(!is_null($pictureData)) {
					if(isset($this->tags["album"])) {
						$pictureFile = self::MUSIC_ARTWORK_DIR . "album/" . $this->tags["album"];
					} else {
						$pictureFile = self::MUSIC_ARTWORK_DIR . "album/" . md5($pictureData);
					}
					
					$this->artworkMIME = $pictureMIME;
					$this->artworkFile = $pictureFile;

					if(!file_exists($pictureFile)) {
						file_put_contents($pictureFile, $pictureData);
					}
				}
			}
		}


		
	
	}


?>