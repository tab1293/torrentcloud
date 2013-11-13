<?php

	class TorrentMetadataException extends Exception {

		private $torrentMetadata;

		 public function __construct($message, $torrentMetadata, $code = 0, Exception $previous = null) {
	        $this->torrentMetadata = $torrentMetadata;
	        parent::__construct($message, $code, $previous);
	    }

	    public function getTorrentMetadata() {
	    	return $this->torrentMetadata;
	    }

	}

?>