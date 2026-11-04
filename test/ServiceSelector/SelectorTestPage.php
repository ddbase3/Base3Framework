<?php declare(strict_types=1);

namespace Base3\Test\ServiceSelector;

use Base3\Page\Api\IPageCatchall;

final class SelectorTestPage implements IPageCatchall {
	public array $calls = [];
	public static function getName(): string { return 'selector-test-page'; }
	public function getOutput(string $out = 'html', bool $final = false): string {
		$this->calls[] = [$out, $final];
		return 'catchall:' . $out;
	}
	public function getUrl() { return '/fallback'; }
}
