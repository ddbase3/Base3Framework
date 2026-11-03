<?php declare(strict_types=1);

namespace Base3\Test\Usermanager;

use Base3\Usermanager\User;
use Base3\Usermanager\UsermanagerProxy;
use PHPUnit\Framework\TestCase;

final class UsermanagerProxySearchUsersTestDouble4 {
			public function searchUsers(): array { return [['id' => 123], 'not a user']; }
		}
