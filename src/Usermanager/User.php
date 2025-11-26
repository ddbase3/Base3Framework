<?php declare(strict_types=1);

namespace Base3\Usermanager;

class User {

	public $id;
	public $name;
	public $email;
	public $lang;		// "de", "en", ...
	public $role;		// "visit" | "member" | "admin"

	public static function fromArray(array $data): self {
		$user = new self();

		foreach (['id', 'name', 'email', 'lang', 'role'] as $key) {
			if (array_key_exists($key, $data)) {
				$user->$key = $data[$key];
			}
		}

		return $user;
	}
}
