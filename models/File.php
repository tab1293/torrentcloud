<?php

	abstract class File {

		public $name;
		public $path;
		public $directory;
		public $url;
		public $display;
		public $size;
		public $extension;
		public $MIME_type;

		public function analyze() {
			$supportedAudioMIMES = unserialize(AUDIO_MIMES);
			$supportedAudioExtensions = unserialize(AUDIO_EXTENSIONS);

			if(in_array($this->MIME_type, $supportedAudioMIMES) || in_array($this->extension, $supportedAudioExtensions)) {
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


				if(isset($fileInfo['comments']['picture'][0])) {
					$pictureData = $fileInfo['comments']['picture'][0]['data'];
					$pictureMIME = $fileInfo['comments']['picture'][0]['image_mime'];
					$pictureFile = $this->directory . "/". md5($pictureData);
					$this->tags['image'] = $pictureFile;
					if(!file_exists($pictureFile)) {
						file_put_contents($pictureFile, $pictureData);
					}
				}

			}
		}
	
	}


?>