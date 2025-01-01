<?php declare(strict_types=1);

namespace Usermanager;

class User {

	public $id;
	public $name;
	public $email;
	public $lang;		// "de", "en", ...
	public $role;		// "visit" | "member" | "admin"

}
