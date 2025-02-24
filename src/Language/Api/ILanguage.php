<?php declare(strict_types=1);

namespace Language\Api;

interface ILanguage {

	public function getLanguage(): string;
	public function setLanguage(string $language);
	public function getLanguages(): array;

}
