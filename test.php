<?php
/*$file = "/tmp/2AeAqs/Jay-Z_-_Magna_Carta_Holy_Grail_[320kbps]-2013.zip";
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename='.basename($file));
header('Content-Length: ' . filesize($file));
ob_clean();
flush();
readfile($file);
*/
/*include("models/ZipStream.php");
$zip = new ZipStream('foo.zip');
$zip->add_file_from_path('jay.zip', '/home/tom/jayz.zip');
$zip->finish();*/

/*include("models/ZipStream.php");
$torrentHash = $_GET['torrentHash'];
$torrentName = $_GET['torrentName'];
$torrentName = str_replace(array('/', ':', '*', '?', '<', '>', '|'), '', $torrentName);
$torrentName = preg_replace('/[[:space:]]+/', '_', $torrentName);
$torrentDir = '/var/www/torrents/' . $torrentHash . '/';
flush();
header('Transfer-Encoding', 'chunked');
$zip = new ZipStream($torrentName . '.zip');
$zip->addDirectoryContent($torrentDir, $torrentName);
//echo $zip->streamFileLength;
//header('Content-Length: ' . $zip->streamFileLength);
return $zip->finalize();*/

			/*$torrentHash = $app->request->get('torrentHash');
			$torrentName = $app->request->get('torrentName');
			$torrentName = str_replace(array('/', ':', '*', '?', '<', '>', '|'), '', $torrentName);
			$torrentName = preg_replace('/[[:space:]]+/', '_', $torrentName);
			$torrentDir = Transmission::TORRENT_DIR . $torrentHash . "/";
			//$torrentZipDB = new TorrentZipDB();
			//$torrentZipFound = $torrentZipDB->get($torrentHash);
			$app->response->headers->set('Content-Type', 'application/zip');
			$app->response->headers->set('Content-disposition', "filename=".$torrentName.".zip");
			//if($torrentZipFound) {

			//} else {

				$tempName = tempnam(sys_get_temp_dir(),'');
				$zipFile = fopen($tempName, 'w');
				$zipPipe = popen('zip -j -r0 - ' . $torrentDir, 'r');
				$bufsize = 8192;
				$buff = '';
				while(!feof($zipPipe) ) {
					$buff = fread($zipPipe, $bufsize);
					fwrite($zipFile, $buff);
				}
				pclose($zipPipe);
				fclose($zipFile);
				ob_end_clean();
				flush();
				$app->response->headers->set('Content-Description', 'File Transfer');
				$app->response->headers->set('Content-Type', 'application/zip');
				$app->response->headers->set('Content-disposition', "filename=".$torrentName.".zip");
				$app->response->headers->set('Content-Transfer-Encoding', 'binary');
				$app->response->headers->set('Expires', 0);
				$app->response->headers->set('Cache-Control', 'must-revalidate');
				$app->response->headers->set('Pragma', 'public');
				$app->response->headers->set('Content-Length', filesize($tempName));
				readfile($tempName);
				unlink($tempName);
			//}*/
			
			var_dump(parse_ini_file("/etc/php5/fpm/php.ini"));


?>