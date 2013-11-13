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
					$torrentMetadata['totalSize'] = $helper->formatBytes($torrentMetadata['totalSize']);
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

	});

	

?>