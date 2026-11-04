<?php declare(strict_types=1);

namespace Base3\Test\ServiceSelector;

final class SelectorTestLanguage {
	public array $selected = [];
	public function setLanguage(string $language): void { $this->selected[] = $language; }
}
