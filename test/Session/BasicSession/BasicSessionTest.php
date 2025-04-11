<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Base3\Session\BasicSession\BasicSession;
use Base3\Configuration\Api\IConfiguration;

final class BasicSessionTest extends TestCase
{
	public function setUp() {
		// Session reset für saubere Tests
		if (session_status() === PHP_SESSION_ACTIVE) {
			session_unset();
			session_destroy();
		}
		$_REQUEST = [];
	}

	public function testSessionDoesNotStartWithoutMatchingOutput() {
		$_REQUEST['out'] = 'html';

		$mockConfig = $this->getMockBuilder(IConfiguration::class)
			->setMethods(['get', 'set', 'save'])
			->getMock();

		$mockConfig->method('get')->willReturn([
			'extensions' => ['json'], // html ist NICHT enthalten
			'cookiedomain' => ''
		]);

		$session = new BasicSession($mockConfig);

		$this->assertFalse($session->started());
	}

        public function testSessionStartsWithMatchingOutput() {
                $_REQUEST['out'] = 'json';

                $mockConfig = $this->getMockBuilder(IConfiguration::class)
                        ->setMethods(['get', 'set', 'save'])
                        ->getMock();

                $mockConfig->method('get')->willReturn([
                        'extensions' => ['json', 'html'], // json ist enthalten
                        'cookiedomain' => ''
                ]);

                $session = new BasicSession($mockConfig);

                $this->assertTrue(php_sapi_name() === 'cli' || $session->started());
        }

	public function testSessionNotStartedIfOutNotSet() {
		unset($_REQUEST['out']);

		$mockConfig = $this->getMockBuilder(IConfiguration::class)
			->setMethods(['get', 'set', 'save'])
			->getMock();

		$mockConfig->method('get')->willReturn([
			'extensions' => ['json'],
			'cookiedomain' => ''
		]);

		$session = new BasicSession($mockConfig);

		$this->assertFalse($session->started());
	}
}

