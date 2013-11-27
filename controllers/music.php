<?php

	$musicMiddleware = function($route) use($app, $env) {
		$env['userMusic'] = new Music($env['user']);
		$env['ajax'] = $app->request->isAjax();
		
	};

	$app->group('/music', $authenticateMiddleware, $musicMiddleware, function() use($app, $env) {

		$app->get('/', function() use ($app, $env) {
			$viewData = array(
				"tracks"=>$env['userMusic']->getTracks(),
				"artists"=>$env['userMusic']->artists,
				"albums"=>$env['userMusic']->albums,
				"artistImages"=>$env['userMusic']->artistImages,
				"albumArtwork"=>$env['userMusic']->albumArtwork,
				"username" => $env['user']->username,
			);
			
			$app->view->setData($viewData);
			$app->render('/music/music.html');
			
		});
		
		$app->get('/artists/:artist', function($artist) use ($app, $env) {
			$viewData = array();
			$viewData['artist'] = $artist;
			$viewData['tracks'] = $env['userMusic']->getTracksByArtist($artist);
			$viewData['albums'] = $env['userMusic']->getAlbumsByArtist($artist);
			$viewData["artistImages"] = $env['userMusic']->artistImages;
			$viewData["albumArtwork"] = $env['userMusic']->albumArtwork;
			$viewData['ajax'] = $env['ajax'];
			$app->view->setData($viewData);
			if($env['ajax']) {
				$view = $app->view();
				$html = $view->render('/music/artist.html');
				echo $html;
			} else {
				$app->render('/music/artist.html');
			}
		});
		
		$app->get('/artists/:artist/:album', function($artist, $album) use ($app, $env) {
			$viewData = array();
			$viewData['artist'] = $artist;
			$viewData['album'] = $album;
			$viewData['tracks'] = $env['userMusic']->getTracksByArtistAndAlbum($artist, $album);
			$viewData["albumArtwork"] = $env['userMusic']->albumArtwork;
			$viewData['ajax'] = $env['ajax'];
			$app->view->setData($viewData);
			if($env['ajax']) {
				$view = $app->view();
				$html = $view->render('/music/tracksByArtistAndAlbum.html');
				echo $html;
			} else {
				$app->render('/music/tracksByArtistAndAlbum.html');
			}
			
		});
		
		$app->get('/song', function() use ($app, $env) {
			$fileDB = new FileDB();
			$fileId = $app->request->params('id');
			$file = $fileDB->get($fileId);
			if(!is_null($file)) {
				ob_end_clean();
				flush();
				header('Content-Type: ' . $file->MIME_type);
				header('Content-Length: ' . $file->size);
				readfile($file->path);
				exit;
			} else {
				echo "File was not found";
			}
		});
		
		$app->get('/albumArtwork', function() use ($app, $env) { 
			$album = $app->request->params('album');
			$artworkName = Music::ALBUM_ARTWORK_PATH.$album;
			$fileDB = new FileDB();
			$artwork = $fileDB->getArtwork($artworkName);
			if($artwork) {
				$file = $artwork[FileDB::ARTWORK_FILE];
				$mime = $artwork[FileDB::ARTWORK_MIME];
				$size = filesize($file);
				ob_end_clean();
				flush();
				header('Content-Type: ' . $mime);
				header('Content-Length: ' . $size);
				readfile($file);
				exit;
			} else {
				echo "Artwork not found";
			}
			

		});
		
		$app->get('/artistImage', function() use ($app, $env) { 
			$artist = $app->request->params('artist');
			$artworkFile = $env['userMusic']->artistImages[$artist];
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$mime = finfo_file($finfo, $artworkFile);
			$size = filesize($artworkFile);
			ob_end_clean();
			flush();
			header('Content-Length: ' . $size);
			header('Content-Type: ' . $mime);
			readfile($artworkFile);
			exit;
		});
		
	});

?>