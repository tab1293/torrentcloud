<?php

	if($app->request->isAjax()) {
		$library = $_SESSION['library'];
	} else {
		$user = new User($_SESSION['username']);
		$library = new MusicLibrary($user);
		$_SESSION['library'] = $library;
	}

		
	$app->group('/music', $authenticate($app), function() use ($app, $library) {

		$data = array();

		/*if(!($app->request->isAjax())) {
			$library->update();
			$data['allSongs'] = $library->getTracks();
			$data['allAlbumArtwork'] = $library->getAlbumArtwork();
		}*/

		$app->get('/', function() use ($app, $data) {
			$user = new User($_SESSION['username']);
			$library = new MusicLibrary($user);
			$library->update();
			$data['artists'] = $library->getArtists();
			$data['artistImages'] = $library->getArtistImages();
			$data['albums'] = $library->getAlbums();
			$data['albumArtwork'] = $library->getAlbumArtwork();
			$data['songs'] = $library->getTracks();
			$app->view->setData($data);
			$app->render('/library/music/music.html');
		});

		$app->get('/test', function() use ($app, $data) {
			var_dump($_SESSION['username']);
		});

		$app->get('/artists', function() use ($app, $data, $library) {
			var_dump($library->getArtists());
			$data['artistImages'] = $library->getArtistImages();
			//var_dump($library);
			$app->view->setData($data);
			$app->render('/library/music/artists.html');
		});

		$app->get('/artists/:artist', function($artist) use ($app, $data) {
			$data['artist'] = $artist;
			$data['albums'] = $library->getAlbumsByArtist($artist);
			$data['albumArtwork'] = $library->getAlbumArtworkByArtist($artist);
			$app->view->setData($data);
			$app->render('/library/music/albumsByArtist.html');
		});

		$app->get('/artists/:artist/:album', function($artist, $album) use ($app, $data) {
			$data['artist'] = $artist;
			$data['album'] = $album;
			$data['songs'] = $library->getTracksByArtistAndAlbum($artist, $album);
			$data['albumArtwork'] = $library->getAlbumArtwork($album);
			$app->view->setData($data);
			$app->render('/library/music/tracksByArtistAndAlbum.html');
		});


	});


?>