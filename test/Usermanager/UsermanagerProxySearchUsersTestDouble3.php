<?php declare(strict_types=1);

namespace Base3\Test\Usermanager;

use Base3\Usermanager\User;
use Base3\Usermanager\UsermanagerProxy;
use PHPUnit\Framework\TestCase;

final class UsermanagerProxySearchUsersTestDouble3 {
			public function searchUsers(): array { throw new \RuntimeException('must not be called'); }
		}
