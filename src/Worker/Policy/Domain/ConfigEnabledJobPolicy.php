<?php declare(strict_types=1);

namespace Base3\Worker\Policy\Domain;

use Base3\Configuration\Api\IConfiguration;
use Base3\Worker\Policy\AbstractJobExecutionPolicy;

final class ConfigEnabledJobPolicy extends AbstractJobExecutionPolicy {

	public function __construct(
		private readonly IConfiguration $configuration
	) {}

	public static function getName(): string {
		return 'configenabledjobpolicy';
	}

	public function shouldRun(string $jobName) {
		$section = $this->getString('section', 'job');
		$key = $this->getString('key', $jobName . '.active');
		$expected = $this->getInt('expected', 1);

		$config = (array)$this->configuration->get($section);

		if ((int)($config[$key] ?? 0) !== $expected) {
			$this->setReason('Skip (disabled by configuration)');
			return false;
		}

		return true;
	}

	public function getSchema(): array {
		return [
			'type' => 'object',
			'properties' => [
				'section' => [
					'type' => 'string',
					'description' => 'Configuration section. Default: job.'
				],
				'key' => [
					'type' => 'string',
					'description' => 'Configuration key. Default: <jobName>.active.'
				],
				'expected' => [
					'type' => 'integer',
					'description' => 'Expected integer value. Default: 1.'
				]
			]
		];
	}
}
