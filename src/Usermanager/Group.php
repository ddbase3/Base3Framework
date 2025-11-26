<?php declare(strict_types=1);

namespace Base3\Usermanager;

class Group {

	public $id;
	public $name;

        public static function fromArray(array $data): self {
                $group = new self();

                foreach (['id', 'name'] as $key) {
                        if (array_key_exists($key, $data)) {
                                $group->$key = $data[$key];
                        }
                }

                return $group;
        }
}
