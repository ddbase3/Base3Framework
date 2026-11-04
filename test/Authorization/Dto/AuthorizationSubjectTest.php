<?php declare(strict_types=1);

namespace Base3\Test\Authorization\Dto;

use PHPUnit\Framework\TestCase;

final class AuthorizationSubjectTest extends TestCase {

	public function testCarriesDisplayMetadataWithoutGrantingRights(): void {
		$ref = new \Base3\Authorization\Dto\SubjectReference('host', 'user', 123);
		$subject = new \Base3\Authorization\Dto\AuthorizationSubject($ref, 'Person', false, ['department' => 'IT']);
		$this->assertSame($ref, $subject->reference);
		$this->assertSame('Person', $subject->label);
		$this->assertFalse($subject->editable);
		$this->assertSame('IT', $subject->metadata['department']);
	}

}
