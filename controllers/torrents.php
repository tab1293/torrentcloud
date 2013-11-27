<?php

	$app->group('/torrents', $authenticateMiddleware, function() use($app, $env) {
		
		$app->get('/', function() use ($app, $env) {
			$env['user']->torrents = $env['torrentCloud']->pollTorrents($env['user']->torrents);
			$env['user']->update();
			
			$viewData['username'] = $env['user']->username;
			$viewData['torrents'] = $env['user']->torrents;
			$viewData['timeout'] = $env['torrentCloud']->calculatePollTimeout($env['user']->torrents);
			$app->view()->setData($viewData);
			$app->render('torrents.html');
		});

		$app->get('/poll', function() use($app, $env) {
			$env['user']->torrents = $env['torrentCloud']->pollTorrents($env['user']->torrents);
			$timeout = $env['torrentCloud']->calculatePollTimeout($env['user']->torrents);
			$env['user']->update();
			
			$app->response->headers->set('Content-Type', 'application/json');
			echo json_encode(array("timeout"=>$timeout, "torrents"=>$env['user']->torrents));
			
		});

		$app->get('/fetch', function() use ($app, $env) { 
			try {
				$torrentMetadata = $env['torrentCloud']->getTorrentMetadata($app->request->get('torrentURL'));
			} catch(Exception $e) {
				if($e instanceof TorrentMetadataException) {
					$helper = new Helper();
					$torrentMetadata = $e->getTorrentMetadata();
					$torrentMetadata['displaySize'] = $helper->formatBytes($torrentMetadata['totalSize']);
					echo json_encode(array("error"=>true, "message"=>$e->getMessage(), "torrentMetadata"=>$torrentMetadata));
					return;

				} else {
					echo json_encode(array("error"=>true, "message"=>$e->getMessage()));
					return;
				}
			}
			$helper = new Helper();
			$torrentMetadata['displaySize'] = $helper->formatBytes($torrentMetadata['totalSize']);
			echo json_encode(array("error"=>false, "torrentMetadata"=>$torrentMetadata));

		});

		$app->post('/add', function() use ($app, $env) {
			try {
				$torrent = $env['torrentCloud']->addTorrent($app->request->post('torrentURL'), $app->request->post('hashString'), $app->request->post('torrentSize'));
			} catch(Exception $e) {
				echo json_encode(array("error"=>true, "message"=>$e->getMessage()));
				return;
			}

			$view = $app->view();
			$view->setData(array("torrent"=>$torrent));
			$torrentHtml = $view->render('torrents/torrent.html');

			$app->response->headers->set('Content-Type', 'application/json');
			echo json_encode(array("timeout"=>DOWNLOADING_POLL_TIME, "torrent"=>$torrentHtml));
		});

		$app->post('/remove', function() use($app, $env) {
			$torrentRemoved = $env['torrentCloud']->removeTorrent($app->request->post('torrentHash'));
			echo json_encode($torrentRemoved);

		});
		
		$app->get('/download', function() use($app, $env) { 
			$torrentHash = $app->request->get('torrentHash');
			$torrentName = $app->request->get('torrentName');
			$torrentName = str_replace(array('/', ':', '*', '?', '<', '>', '|'), '', $torrentName);
			$torrentName = preg_replace('/[[:space:]]+/', '_', $torrentName);
			$torrentDir = Transmission::TORRENT_DIR . $torrentHash . "/";
			$torrentZipDB = new TorrentZipDB();
			$torrentZipFound = $torrentZipDB->get($torrentHash);
			header('Content-Type: application/zip');
			header("Content-disposition filename='".$torrentName."'.zip");
			if(!is_null($torrentZipFound)) {
				$zipSize = $torrentZipFound[TorrentZipDB::ZIP_SIZE];
				chdir($torrentDir);
				$fp = popen('zip -r0 - *', 'r');
				ob_end_clean();
				flush();
				header('Content-Description: File Transfer');
			    header('Content-Type: application/zip');
				header('Content-disposition: attachment; filename="'.$torrentName.'.zip"');
			    header('Content-Transfer-Encoding: binary');
			    header('Expires: 0');
			    header('Cache-Control: must-revalidate');
			    header('Pragma: public');
			    header('Content-Length: ' . $zipSize);

			    $fp1 = fopen('/tmp/zippy.zip', 'w');
				// pick a bufsize that makes you happy (8192 has been suggested).
				$bufsize = 8192;
				$buff = '';
				while( !feof($fp) ) {
					$buff = fread($fp, $bufsize);
					fwrite($fp1, $buff);
					echo $buff;
				}
				pclose($fp);
				fclose($fp1);
			} else {
				$tempName = tempnam(sys_get_temp_dir(),'');
				$zipFile = fopen($tempName, 'w');
				chdir($torrentDir);
				$zipPipe = popen('zip -r0 - *', 'r');
				$bufsize = 8192;
				$buff = '';
				while(!feof($zipPipe) ) {
					$buff = fread($zipPipe, $bufsize);
					fwrite($zipFile, $buff);
				}
				pclose($zipPipe);
				fclose($zipFile);
				$zipSize = filesize($tempName);
				if($zipSize > 0) {
					$torrentZipData = array(TorrentZipDB::TORRENT_HASH=>$torrentHash, TorrentZipDB::ZIP_NAME=>$torrentName.'.zip', TorrentZipDB::ZIP_SIZE=>$zipSize);
					$torrentZipDB->add($torrentZipData);
				}
				ob_end_clean();
				flush();
				header('Content-Description: File Transfer');
			    header('Content-Type: application/zip');
				header('Content-disposition: attachment; filename="'.$torrentName.'.zip"');
			    header('Content-Transfer-Encoding: binary');
			    header('Expires: 0');
			    header('Cache-Control: must-revalidate');
			    header('Pragma: public');
			    header('Content-Length: ' . $zipSize);
				readfile($tempName);
				unlink($tempName);
			}
			//ob_end_clean();
			//flush();
			//header('Transfer-Encoding', 'chunked');
			//$zip = new ZipStream($torrentName . '.zip');
			//$zip->addDirectoryContent($torrentDir, $torrentName);
			//error_log($zip->streamFileLength);
			//header('Content-Length: ' . $zip->streamFileLength);
			//return $zip->finalize();
			//ob_end_clean();
			//ob_end_clean();
			//$zip = new Zip(true);
			//$zip->addDirectoryContent($torrentDir, $torrentName);
			//$zip->sendZip($torrentName . '.zip');
			
			/*ob_end_clean();
			flush();
			//header('Content-Type: application/zip');
			//header('Content-disposition: attachment; filename="'.$torrentName.'.zip"');

			// use popen to execute a unix command pipeline
			// and grab the stdout as a php stream
			// (you can use proc_open instead if you need to 
			// control the input of the pipeline too)
			//
			$fp = popen('zip -r0 - ' . $torrentDir, 'r');
			$fp1 = fopen('/tmp/zippy.zip', 'w');

			// pick a bufsize that makes you happy (8192 has been suggested).
			$bufsize = 8192;
			$buff = '';
			while( !feof($fp) ) {
				$buff = fread($fp, $bufsize);
				fwrite($fp1, $buff);
				//echo $buff;
			}
			fclose($fp1);
			pclose($fp);
			echo filesize($fp1);*/
		
		});

	});

	

?>