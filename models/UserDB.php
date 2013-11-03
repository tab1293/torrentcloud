<?php 
	
 	class UserDB {

		const USERNAME = "username";
		const PASSWORD = "password";
		const EMAIL = "email";
		const TORRENT_HASHES = "torrentHashes";
		const FRIEND_USERNAMES = "friendUsernames";

		private $userCollection;

		public function __construct() {
			$m = new MongoClient();
			$this->userCollection = $m->torrentcloud->users;
		}

		public function get($username) {
			return $this->userCollection->findOne(array("username"=>$username));
		}

		public function insert($username, $password, $retypePassword, $email) {
			if($password != $retypePassword) {
				return false;
			}

			if($this->userCollection->findOne(array("username"=>$username))) {
				return false;
			}

			$passwordHash = md5($password);
			$user = array(self::USERNAME=>$username, self::PASSWORD=>$passwordHash, self::EMAIL=>$email, self::TORRENT_HASHES=>array(), self::FRIEND_USERNAMES=>array());
			$this->userCollection->insert($user);
			return true;
		}

		public function update(User $user) {
			$updateSelect = array(self::USERNAME=>$user->username);
			$updateQuery = array(
						self::PASSWORD=>$user->password,
						self::EMAIL=>$user->email,
						self::TORRENT_HASHES=>$user->torrentHashes,
						self::FRIEND_USERNAMES=>$user->friendUsernames,
					);
			$this->userCollection->update($updateSelect, array('$set'=>$updateQuery));
		}


	}
	
?>