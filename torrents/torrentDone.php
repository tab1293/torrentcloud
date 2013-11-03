#!/usr/bin/php
<?php
	ini_set("log_errors", 1);
	ini_set("error_log", "/var/www/torrents/php-error.log");
	error_reporting(E_ALL);

	spl_autoload_register(function ($class) {
    	require_once '/var/www/models/' . $class . '.php';
	});

  	require_once("/var/www/constants.php");
  	require_once("/var/www/models/getid3/getid3.php");
  	require_once("/var/www/models/FileDB.php");

  	$fileDB = new FileDB();
	$torrentDB = new TorrentDB();
	$torrentCloud = new TorrentCloud();
	$torrentHash = getenv('TR_TORRENT_HASH');
	
	$torrentData = $torrentDB->get($torrentHash);
	$torrent = new Torrent($torrentData);
	$torrent = $torrentCloud->pollTorrent($torrent);

	foreach($torrent->files as $file) {
		$name = substr(strrchr($file['name'], "/"), 1);
		$path = $torrent->downloadDir . '/' . $file['name'];
		$url = str_replace("/var/www/", "", $path);
		$display = substr(strchr(substr(strchr($url, "/"), 1), "/"), 1);
		$extension = substr(strrchr($name, "."), 1);
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$MIME_type = finfo_file($finfo, $path);

		$fileData = array(
				FileDB::TORRENT_HASH => $torrent->hashString,
				FileDB::NAME => $name, 
				FileDB::SIZE => $file['length'],
				FileDB::DIRECTORY => $torrent->downloadDir,
				FileDB::PATH => $path,
				FileDB::URL => $url,
				FileDB::DISPLAY => $display,
				FileDB::EXTENSION => $extension,
				FileDB::MIME_TYPE => $MIME_type,
			);
		$torrentFile = new TorrentFile($fileData);
		$torrentFile->analyze();
		$fileDB->add($torrentFile);
	}

?>