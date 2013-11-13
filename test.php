<?php
	$magnet_uri = "magnet:?xt=urn:btih:5baa50233d6ad6a46d02b01d8b0e01626e6a9a17&dn=Jay-Z+-+Magna+Carta+Holy+Grail+%5B320kbps%5D-2013&tr=udp%3A%2F%2Ftracker.openbittorrent.com%3A80&tr=udp%3A%2F%2Ftracker.publicbt.com%3A80&tr=udp%3A%2F%2Ftracker.istole.it%3A6969&tr=udp%3A%2F%2Ftracker.ccc.de%3A80&tr=udp%3A%2F%2Fopen.demonii.com%3A1337";
	$return = exec('python /var/www/torrentcloud/python/magnet2metadata.py ' . $magnet_uri);
	$data = json_decode($return);
	var_dump($data);
?>