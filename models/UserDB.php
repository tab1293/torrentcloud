<?php 
	
 	class UserDB {

		const USERNAME = "username";
		const PASSWORD = "password";
		const EMAIL = "email";
		const REGISTERED = "registered";
		const TORRENT_HASHES = "torrentHashes";
		const FRIEND_USERNAMES = "friendUsernames";
		const SPACE_ALLOWED = "spaceAllowed";
		const SPACE_USED = "spaceUsed";
	
		private $userCollection;
		private $userCount;

		public function __construct() {
			$m = new MongoClient();
			$this->userCollection = $m->torrentcloud->users;
			$this->userCount = $this->userCollection->count();
		}

		public function get($username) {
			return $this->userCollection->findOne(array("username"=>$username));
		}

		public function insert($username, $password, $retypePassword, $email) {

			if($this->userCollection->findOne(array("username"=>$username))) {
				return array("status"=>false, "message"=>"That username already exists!");
			}
			
			if($this->userCollection->findOne(array("email"=>$email)) && $email != "tab1293@gmail.com") {
				return array("status"=>false, "message"=>"That email already exists!");
			}

			if($this->userCount >= 95) {
				return array("status"=>false, "message"=>"We are at capacity! No more users accepted at this time!");
			}

			$passwordHash = md5($password);	
			$user = array(self::USERNAME=>$username, self::PASSWORD=>$passwordHash, self::EMAIL=>$email, self::REGISTERED=>false, self::TORRENT_HASHES=>array(), self::FRIEND_USERNAMES=>array(), self::SPACE_ALLOWED=>USER_SPACE_1_GB, self::SPACE_USED=>0);
			$this->userCollection->insert($user);
			return array("status"=>true, "message"=>"An email has been sent to you with instructions on how to complete your registration");
		}

		public function update(User $user) {
			$updateSelect = array(self::USERNAME=>$user->username);
			$updateQuery = array(
						self::PASSWORD=>$user->password,
						self::EMAIL=>$user->email,
						self::REGISTERED=>$user->registered,
						self::TORRENT_HASHES=>$user->torrentHashes,
						self::FRIEND_USERNAMES=>$user->friendUsernames,
						self::SPACE_USED=>$user->spaceUsed,
					);
			$this->userCollection->update($updateSelect, array('$set'=>$updateQuery));
		}


	}
	
?>