<?php declare(strict_types=1);

namespace Base3\Test\Authorization;

use PHPUnit\Framework\TestCase;

final class AuthorizationMultiplexerTest extends TestCase {

	private function request(string $authority): \Base3\Authorization\Dto\AuthorizationRequest {
		return new \Base3\Authorization\Dto\AuthorizationRequest($authority, \Base3\Usermanager\Permission::for('course', 'read'));
	}

	public function testDispatchesExactAuthoritiesWithoutAllowingUnknown(): void {
		$a = new \Base3\Test\Authorization\NativeAuthorizationTestService('a');
		$b = new \Base3\Test\Authorization\NativeAuthorizationTestService('b');
		$m = new \Base3\Authorization\AuthorizationMultiplexer('a', ['a'=>$a, 'b'=>$b]);
		$this->assertSame('a', $m->getDefaultAuthority());
		$this->assertSame('a', $m->decide($this->request('a'))->reasonCode);
		$this->assertSame('b', $m->decide($this->request('b'))->reasonCode);
		$this->assertSame('unknown_authority', $m->decide($this->request('missing'))->reasonCode);
		$this->assertCount(1, $a->singleRequests);
		$this->assertCount(1, $b->singleRequests);
	}

	public function testGroupsBatchByAuthorityPreservingOriginalKeys(): void {
		$a = new \Base3\Test\Authorization\NativeAuthorizationTestService('a');
		$b = new \Base3\Test\Authorization\NativeAuthorizationTestService('b');
		$m = new \Base3\Authorization\AuthorizationMultiplexer('a', ['a'=>$a, 'b'=>$b]);
		$results = $m->decideMany(['b'=> $this->request('b'), 'unknown'=> $this->request('other'), 'a'=> $this->request('a')]);
		$this->assertSame(['b','unknown','a'], array_keys($results));
		$this->assertSame('b', $results['b']->reasonCode);
		$this->assertSame('a', $results['a']->reasonCode);
		$this->assertSame('unknown_authority', $results['unknown']->reasonCode);
		$this->assertSame(['a'], array_keys($a->batches[0]));
		$this->assertSame(['b'], array_keys($b->batches[0]));
	}

	public function testUnavailableNativeAuthorityIsIndeterminate(): void {
		$a = new \Base3\Test\Authorization\NativeAuthorizationTestService('a');
		$a->throwSingle = true;
		$a->throwBatch = true;
		$m = new \Base3\Authorization\AuthorizationMultiplexer('a', ['a'=>$a]);
		$this->assertSame('authority_unavailable', $m->decide($this->request('a'))->reasonCode);
		$this->assertFalse($m->decideMany(['one'=>$this->request('a')])['one']->isAllowed());
		$this->assertSame('authority_unavailable', $m->decideMany(['one'=>$this->request('a')])['one']->reasonCode);
	}

	public function testInvalidNativeBatchResponseIsIndeterminate(): void {
		$a = new \Base3\Test\Authorization\NativeAuthorizationTestService('a');
		$a->batchReturn = ['one' => true];
		$m = new \Base3\Authorization\AuthorizationMultiplexer('a', ['a'=>$a]);
		$results = $m->decideMany(['one'=>$this->request('a'), 'two'=>$this->request('a')]);
		$this->assertSame('invalid_authority_response', $results['one']->reasonCode);
		$this->assertSame('invalid_authority_response', $results['two']->reasonCode);
	}

	public function testNativeAdministrationDelegatesWithExactResource(): void {
		$a = new \Base3\Test\Authorization\NativeAuthorizationTestService('a');
		$admin = new \Base3\Test\Authorization\NativeAuthorizationAdministrationTestService();
		$m = new \Base3\Authorization\AuthorizationMultiplexer('a', ['a'=>$a], ['a'=>$admin]);
		$resource = new \Base3\Authorization\Dto\ResourceReference('a','course',1);
		$subject = new \Base3\Authorization\Dto\SubjectReference('a','role',2);
		$this->assertTrue($m->describe('a', $resource)->canRead);
		$this->assertSame('read', $m->getPermissions('a', $resource)[0]->permission);
		$this->assertSame([], $m->getSubjects($resource));
		$this->assertSame([], $m->getGrants($resource));
		$m->replaceGrants($resource, $subject, []);
		$this->assertSame([[$resource, $subject, []]], $admin->changes);
	}

	public function testMissingNativeAdministrationRejectsWrite(): void {
		$m = new \Base3\Authorization\AuthorizationMultiplexer('a', ['a'=>new \Base3\Test\Authorization\NativeAuthorizationTestService('a')]);
		$this->expectException(\RuntimeException::class);
		$m->replaceGrants(new \Base3\Authorization\Dto\ResourceReference('a','course',1), new \Base3\Authorization\Dto\SubjectReference('a','role',1), []);
	}

	public function testRejectsNonRequestBatchEntry(): void {
		$m = new \Base3\Authorization\AuthorizationMultiplexer('a', ['a'=>new \Base3\Test\Authorization\NativeAuthorizationTestService('a')]);
		$this->expectException(\InvalidArgumentException::class);
		$m->decideMany(['bad' => null]);
	}

	public function testRejectsUnknownDefaultAuthority(): void {
		$this->expectException(\InvalidArgumentException::class);
		new \Base3\Authorization\AuthorizationMultiplexer('other', ['a'=>new \Base3\Test\Authorization\NativeAuthorizationTestService('a')]);
	}

	public function testRejectsAdminWithoutDecisionAuthority(): void {
		$this->expectException(\InvalidArgumentException::class);
		new \Base3\Authorization\AuthorizationMultiplexer('a', ['a'=>new \Base3\Test\Authorization\NativeAuthorizationTestService('a')], ['other'=>new \Base3\Test\Authorization\NativeAuthorizationAdministrationTestService()]);
	}

}

require_once __DIR__ . '/NativeAuthorizationTestService.php';
require_once __DIR__ . '/NativeAuthorizationAdministrationTestService.php';
