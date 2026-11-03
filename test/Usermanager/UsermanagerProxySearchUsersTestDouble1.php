<?php declare(strict_types=1);

namespace Base3\Test\Usermanager;

use Base3\Usermanager\User;
use Base3\Usermanager\UsermanagerProxy;
use PHPUnit\Framework\TestCase;

final class UsermanagerProxySearchUsersTestDouble1 {
			public array $calls = [];

			public function searchUsers(string $query, int $limit, int $offset): array {
				$this->calls[] = [$query, $limit, $offset];
				return [['id' => '57', 'userid' => 'jane', 'name' => 'Jane']];
			}
		}
