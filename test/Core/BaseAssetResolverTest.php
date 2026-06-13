<?php declare(strict_types=1);

namespace Base3\Core;

use PHPUnit\Framework\TestCase;

final class BaseAssetResolverTest extends TestCase {

	public function testResolveReturnsPathUnchanged(): void {
		$resolver = new BaseAssetResolver();

		self::assertSame('assets/app.css', $resolver->resolve('assets/app.css'));
		self::assertSame('plugin/Foo/assets/js/app.js', $resolver->resolve('plugin/Foo/assets/js/app.js'));
		self::assertSame('', $resolver->resolve(''));
	}
}
