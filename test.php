<?php 
require("models/getid3/getid3.php");
/*const TORRENT_DIR = "/var/www/torrents/";
$url = "http://localhost:9091/transmission/rpc";
$user = "transmission";
$pass = "test";
$sessionID;
$magnet = "magnet:?xt=urn:btih:5baa50233d6ad6a46d02b01d8b0e01626e6a9a17&dn=Jay-Z+-+Magna+Carta+Holy+Grail+%5B320kbps%5D-2013&tr=udp%3A%2F%2Ftracker.openbittorrent.com%3A80&tr=udp%3A%2F%2Ftracker.publicbt.com%3A80&tr=udp%3A%2F%2Ftracker.istole.it%3A6969&tr=udp%3A%2F%2Ftracker.ccc.de%3A80&tr=udp%3A%2F%2Fopen.demonii.com%3A1337";
$hash = "5baa50233d6ad6a46d02b01d8b0e01626e6a9a17";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_USERPWD, $user . ":" . $pass);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$dom = new DOMDocument;
@$dom->loadHTML($response);
$sessionID = $dom->getElementsByTagName("code");
foreach($sessionID as $id) {
	$sessionID = $id->nodeValue;
}

function request($url, $user, $pass, $sessionID, $args) {
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERPWD, $user . ":" . $pass	);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array($sessionID, "Content-type: application/json"));
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($args));
	$response = curl_exec($ch);
	return json_decode($response, 1);
}

$downloadDir = TORRENT_DIR . $hash;
$args = array( "method" => "torrent-add", "arguments" => array("filename" => $magnet, "download-dir" => $downloadDir));
$response = request($url, $user, $pass, $sessionID, $args);
var_dump($response);
/*$result = $response["result"];
if ($result == "success") {
	return $response["arguments"]["torrent-added"];
}
else if ($result == "duplicate torrent") {
	return "duplicate";
}*/

/*$ch = curl_init($url);
curl_setopt($ch, CURLOPT_USERPWD, $user . ":" . $pass);
curl_setopt($ch, CURLOPT_HEADER, 1);
curl_setopt($ch, CURLOPT_PORT, 9091);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$responseInfo = curl_getinfo($ch);
$start = strrpos($response, "X-Transmission-Session-Id");
$sessionID = substr($response, $start + 27, 48);
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_USERPWD, $user . ":" . $pass);
curl_setopt($ch, CURLOPT_HEADER, 1);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-Transmission-Session-Id: " . $sessionID, "Content-type: application/json"	));
$args = array("method" => "session-stats");
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($args));
$response = curl_exec($ch);
echo $response;*/
$getID3 = new getID3;
$fileInfo = $getID3->analyze("/var/www/torrents/5baa50233d6ad6a46d02b01d8b0e01626e6a9a17/Jay-Z - Magna Carta Holy Grail [320kbps]-2013/10.Versus.mp3");
echo $fileInfo['playtime_string'];
?>