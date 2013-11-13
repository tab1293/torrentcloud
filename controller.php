<?php
	
	session_cache_limiter(false);
	session_start();

	require 'composer/vendor/autoload.php';

	spl_autoload_register(function ($class) {
    	require_once 'models/' . $class . '.php';
	});

  	require "constants.php";
	ini_set('mongo.native_long', 1);
	
	$app = new \Slim\Slim(array(
			'debug' => true,
			'mode' => 'development',
			'view' => new \Slim\Views\Twig(),
			//'templates.path' => 'templates',
		));

	$env = $app->environment();
	$app->view->setTemplatesDirectory('templates');

	$authenticateMiddleware = function($route) use($app, $env) {
        if (empty($_SESSION['AUTHED'])) {
            $app->flash('error', 'Login required');
            $app->redirect('/login');
        } else {
        	$env['user'] = new User($_SESSION['username']);
        	$env['torrentCloud'] = new TorrentCloud($env['user']);
        	return true;
        }
	};

	/*
	$app->add(new \Slim\Middleware\SessionCookie(array(
	    'expires' => '20 minutes',
	    'path' => '/',
	    'domain' => null,
	    'secure' => false,
	    'httponly' => false,
	    'name' => 'slim_session',
	    'secret' => 'CHANGE_ME',
	    'cipher' => MCRYPT_RIJNDAEL_256,
	    'cipher_mode' => MCRYPT_MODE_CBC
	)));

	$app->view(new \Slim\Views\Twig());
	$app->view->parserOptions = array(
        'charset' => 'utf-8',
        'cache' => realpath('./cache'),
        'auto_reload' => true,
        'strict_variables' => false,
        'autoescape' => true
    );

    $app->view->parserExtensions = array(new \Slim\Views\TwigExtension());
	$app->view()->twigTemplateDirs = array("templates"); 
	*/






	include 'controllers/torrents.php';
	//require 'controllers/library.php';

	$app->get('/', function() use ($app) {
		if(!empty($_SESSION['AUTHED'])) {
			$viewData = array("username"=>$_SESSION['username']);
			$app->view()->setData($viewData);
			$app->render("index-logged-in.html");
		} else {
			$app->render("index-logged-out.html");
		}
	});


	$app->get('/login', function() use ($app) {
		$app->render('login.html');
	});

	$app->post('/login', function() use ($app) {
		$username = $app->request->post('username');
		$password = $app->request->post('password');
		
		$userDB = new UserDB();
		if(empty($userDB->get($username))) {
			echo json_encode(array('status'=>false, 'message'=>'Wrong username/password combination!'));
			return;
		} else {
			$user = new User($username);
			if($user->login($password)) {
				$_SESSION['AUTHED'] = true;
				$_SESSION['username'] = $username;
				$_SESSION['validTorrents'] = array();
				echo json_encode(array('status'=>true));
				return;

			} else {
				echo json_encode(array('status'=>false, 'message'=>'Wrong username/password combination!'));
			}
		}
	});
	
	$app->get('/logout', function() use ($app) {
		$_SESSION['AUTHED'] = false;
		$app->redirect('/');
		
	});

	
	$app->get('/request', function() use ($app) {
		$app->render('request.html');
	});
	
	$app->post('/request', function() use ($app) {
		$to = "tab1293@gmail.com";
		$subject = "Request for membership at the Torrent Cloud!";
		$message = $app->request->post("name") . " has requested to become a member of the Torrent Cloud. Their email is " . $app->request->post("email");
		$headers = 'From: admin@thetorrentcloud.com' . "\r\n" . 'Reply-To: '. $app->request->post("email") . "\r\n" . 'X-Mailer: PHP/' . phpversion();
		$success = mail($to, $subject, $message, $headers);
		if($success) {
			echo "An email has been sent to us requesting your membership. You will hear back from the Torrent Cloud shortly!";
		} else {
			echo "There was an error sending your request. Please try again!";
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
		$invitationCode = $app->request->post('invitation');
		
		$DBH = new PDO(PDO_DSN, MYSQL_USER, MYSQL_PASS);
		$data = array("code"=>$invitationCode);
		$sth = $DBH->prepare("SELECT code FROM invitation_codes WHERE code = :code");
		$sth->execute($data);
		if(empty($sth->fetch())) {
			echo json_encode(array('status'=>false, 'message'=>'You have not entered a valid invitation code'));
			return;
		}
		
		$userCollection = new UserDB();
		$userCreated = $userCollection->insert($username, $password, $retypePassword, $email);
		if($userCreated['status']) {
			$subject = "Complete your registration at the Torrent Cloud";
			$link = "www.thetorrentcloud.com/complete-registration?username=" . $username . "&code=" . $invitationCode;
			$message = "Click the following link to complete your registration: " . $link;
			$headers = 'From: register@thetorrentcloud.com' . "\r\n" . 'Reply-To: tab1293@gmail.com' . "\r\n" . 'X-Mailer: PHP/' . phpversion();
			$mailed = mail($email, $subject, $message, $headers);
			if(!$mailed) {
				echo json_encode(array('status'=>false, 'message'=>'There was an error sending you the email'));
				return;
			}
		} 
		echo json_encode($userCreated);

	});
	
	$app->get('/complete-registration', function() use ($app) {
		$username = $app->request->get('username');
		$invitationCode = $app->request->get('code');
		if(isset($username) && isset($invitationCode)) {
			$DBH = new PDO(PDO_DSN, MYSQL_USER, MYSQL_PASS);
			$data = array("code"=>$invitationCode);
			$sth = $DBH->prepare("SELECT code FROM invitation_codes WHERE code = :code");
			$sth->execute($data);
			if(!empty($sth->fetch())) {
				$sth = $DBH->prepare("DELETE FROM invitation_codes WHERE code = :code");
				$sth->execute($data);
			} else {
				echo "This is not a valid registration link!";
				return;
			}
			$userDB = new UserDB();
			if(empty($userDB->get($username))) {
				echo "This is not a valid registration link!";
				return;
			} else {
				$user = new User($username);
				$user->registered = true;
				$user->update();
				$_SESSION['AUTHED'] = true;
				$_SESSION['username'] = $username;
				$_SESSION['validTorrents'] = array();
				$app->redirect("/");
			}
		} else {
			echo "This is not a valid registration link!";
		}
		
		
	});


	$app->run();




?>