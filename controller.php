<?php

	session_cache_limiter(false);
	session_start();

	spl_autoload_register(function ($class) {
    	require_once 'models/' . $class . '.php';
	});

  	require "constants.php";
	require 'composer/vendor/autoload.php';

	$authenticate = function ($app) {
	    return function () use ($app) {
	        if (empty($_SESSION['AUTHED'])) {
	            $app->flash('error', 'Login required');
	            $app->redirect('/login');
	        } else {
	        	$username = $_SESSION['username'];
	        	return true;
	        }
	    };
	};

	$app = new \Slim\Slim(array(
			'debug' => true,
			'mode' => 'development',
			'view' => new \Slim\Views\Twig()
		));

	/*$app->add(new \Slim\Middleware\SessionCookie(array(
	    'expires' => '20 minutes',
	    'path' => '/',
	    'domain' => null,
	    'secure' => false,
	    'httponly' => false,
	    'name' => 'slim_session',
	    'secret' => 'CHANGE_ME',
	    'cipher' => MCRYPT_RIJNDAEL_256,
	    'cipher_mode' => MCRYPT_MODE_CBC
	)));*/

	$app->view()->twigTemplateDirs = array("templates"); 

	require 'controllers/torrents.php';
	require 'controllers/library.php';

	$app->get('/', function() use ($app) {
		$app->render("index.html");
	});


	$app->get('/login', function() use ($app) {
		$app->render('login.html');
	});

	$app->post('/login', function() use ($app) {
		$username = $_POST['username'];
		$password = $_POST['password'];
		$user = new User($username);
		if($user->login($password)) {
			$_SESSION['AUTHED'] = true;
			$_SESSION['username'] = $username;
			$app->redirect('/torrents');

		} else {
			$app->redirect('/login');
		}
	});


	$app->get('/register', function() use ($app) {
		$app->render('register.html');
	});

	$app->post('/register', function() use ($app) {
		$username = $_POST['username'];
		$password = $_POST['password'];
		$retypePassword = $_POST['retypePassword'];
		$email = $_POST['email'];

		$userCollection = new UserDB();
		$userCreated = $userCollection->insert($username, $password, $retypePassword, $email);
		if($userCreated) {
			$app->render("/register/success.html");
		} else {
			$app->render("/register/failure.html");
		}

	});


	$app->run();



?>