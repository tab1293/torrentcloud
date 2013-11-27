#!/usr/bin/php
<?php
	ini_set("log_errors", 1);
	ini_set("extension", "mongo.so");
	ini_set("error_log", "/tmp/php-error.log");
	require_once '/var/www/torrentcloud/models/FileDB.php';
	require_once '/var/www/torrentcloud/models/TorrentDB.php';
	require_once '/var/www/torrentcloud/models/Torrent.php';
	require_once '/var/www/torrentcloud/models/Helper.php';
	require_once '/var/www/torrentcloud/models/File.php';
	require_once '/var/www/torrentcloud/models/TorrentFile.php';
	require_once '/var/www/torrentcloud/models/getid3/getid3.php';
	
  	$fileDB = new FileDB();
	$torrentDB = new TorrentDB();
	$torrentHash = getenv('TR_TORRENT_HASH');
	
	$torrentData = $torrentDB->get($torrentHash);
	$torrent = new Torrent($torrentData);

	foreach($torrent->files as $file) {
		$name = basename($file['name']);
		$extension = pathinfo($name, PATHINFO_EXTENSION);
		$path = $torrent->downloadDir . '/' . $file['name'];
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$MIME_type = finfo_file($finfo, $path);

		$fileData = array(
				FileDB::TORRENT_HASH => $torrent->hashString,
				FileDB::NAME => $name,
				FileDB::EXTENSION => $extension,
				FileDB::SIZE => $file['length'],
				FileDB::PATH => $path,
				FileDB::MIME_TYPE => $MIME_type,
			);
		$torrentFile = new TorrentFile($fileData);
		$torrentFile->analyze();
		$fileDB->add($torrentFile);
	}

?>
