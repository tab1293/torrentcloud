<?php

	class UserFile extends File {

		public $username;

		public function __construct($path, $username) {
			$this->username = $username;
		}

	}

?>