<?php declare(strict_types=1);

namespace Base3\Test\Accesscontrol;

use Base3\Accesscontrol\Selected\SelectedAccesscontrol;
use PHPUnit\Framework\TestCase;

final class SelectedAccesscontrolEdgeCoverageTest extends TestCase {

	public function testEmptyAuthenticationListHasNoIdentity(): void {
		$control = new SelectedAccesscontrol([]);
		$this->assertSame(['authentication_methods_given' => 'Fail'], $control->checkDependencies());
		$this->assertNull($control->getUserId());
		$this->assertNull($control->getUserId());
	}

	public function testLaterNonNullIdentityWinsButAnonymousResultDoesNotEraseIt(): void {
		$first = new RecordingAuthentication('alice');
		$second = new RecordingAuthentication(null);
		$third = new RecordingAuthentication('bob');
		$control = new SelectedAccesscontrol([$first, $second, $third]);
		$this->assertSame('bob', $control->getUserId());
		$this->assertSame(['authentication_methods_given' => 'Ok'], $control->checkDependencies());
		$this->assertSame(['logout', 'login', 'keep:bob', 'finish:bob'], $first->lifecycle);
		$this->assertSame(['logout', 'login', 'keep:bob', 'finish:bob'], $second->lifecycle);
		$this->assertSame(['logout', 'login', 'keep:bob', 'finish:bob'], $third->lifecycle);
	}

	public function testFalsyNonNullIdentityIsPreserved(): void {
		$auth = new RecordingAuthentication('0');
		$control = new SelectedAccesscontrol([$auth]);
		$this->assertSame('0', $control->getUserId());
		$this->assertSame(['logout', 'login', 'keep:0', 'finish:0'], $auth->lifecycle);
	}

	public function testLazyAuthenticationFactoriesAreResolvedExactlyOnce(): void {
		$resolved = 0;
		$auth = new RecordingAuthentication('alice');
		$control = new SelectedAccesscontrol([
			static function() use (&$resolved, $auth): RecordingAuthentication {
				$resolved++;
				return $auth;
			}
		]);
		$this->assertSame(0, $resolved);
		$this->assertSame('alice', $control->getUserId());
		$control->authenticate();
		$this->assertSame('alice', $control->getUserId());
		$this->assertSame(1, $resolved);
		$this->assertCount(4, $auth->lifecycle);
	}

	public function testInvalidAuthenticationObjectIsRejectedBeforeLifecycle(): void {
		$auth = new RecordingAuthentication('alice');
		$control = new SelectedAccesscontrol([$auth, static fn() => new \stdClass()]);
		try {
			$control->authenticate();
			$this->fail('An invalid authentication implementation must be rejected.');
		} catch (\InvalidArgumentException $error) {
			$this->assertSame('Invalid authentication object', $error->getMessage());
		}
		$this->assertSame([], $auth->lifecycle);
	}
}

require_once __DIR__ . '/RecordingAuthentication.php';
