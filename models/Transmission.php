<?php

	class Transmission {
		const TORRENT_DIR = "/var/www/torrents/";

		private $url;
		private $user;
		private $pass;

		private $sessionID = "";
		
		function __construct($trans_url, $trans_user, $trans_pass) {
			$this->url = $trans_url;
			$this->user = $trans_user;
			$this->pass = $trans_pass;
			
		}
		
		function getSession() {
			$ch = curl_init($this->url);
			curl_setopt($ch, CURLOPT_USERPWD, $this->user . ":" . $this->pass);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$response = curl_exec($ch);
			$dom = new DOMDocument;
			@$dom->loadHTML($response);
			$sessionID = $dom->getElementsByTagName("code");
			foreach($sessionID as $id) {
				return $id->nodeValue;
			}
		}
		
		function request($args) {
			$this->sessionID = $this->getSession();
			$ch = curl_init($this->url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_USERPWD, $this->user . ":" . $this->pass	);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array($this->sessionID, "Content-type: application/json"));
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($args));
			$response = curl_exec($ch);
			return json_decode($response, 1);
		}
		
		function add($url, $downloadDir, $paused=false) {
			$args = array( "method" => "torrent-add", "arguments" => array("filename" => $url, "download-dir"=>self::TORRENT_DIR . $downloadDir, "paused"=>$paused));
			$response = $this->request($args);
			$result = $response["result"];
			if ($result == "success") {
				return $response["arguments"]["torrent-added"]["hashString"];
			}
			else if ($result == "duplicate torrent") {
				return "duplicate";
			}
		}
		
		function poll($torrentHashes) {
			$args = array("method" => "torrent-get", "arguments" => array("ids" => $torrentHashes, "fields" => array("hashString", "eta", "percentDone", "status", "rateDownload", "rateUpload", "uploadRatio", "peersConnected", "peersGettingFromUs", "peersSendingToUs", "downloadDir", "name", "totalSize", "addedDate", "files")));
			$response = $this->request($args);
			$torrents = $response["arguments"]["torrents"];
			if(count($torrents) > 1) {
				return $torrents;
			} else {
				return $torrents[0];
			}
		}
		
		function remove($torrentHashes, $deleteLocalData = true) {
			$args = array("method" => "torrent-remove", "arguments" => array("ids" => $torrentHashes, "delete-local-data" => $deleteLocalData));
			$response = $this->request($args);
		}
		
		function startTorrent($torrentID) {
			$args = array("method" => "torrent-start", "arguments" => array("ids" => $torrentID));
			$response = $this->request($args);
			var_dump($response);
		}
		
		function moveTorrent($torrentHashes, $newLocation) {
			$args = array("method" => "torrent-set-location", "arguments" => array("ids" => $torrentHashes, "location" => $newLocation, "move" => true));
			$response = $this->request($args);
		}
		
		function sessionStats() {
			$args = array("method" => "session-stats");
			$response = $this->request($args);
			var_dump($response);
		}
	}

?>