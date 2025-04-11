<?php declare(strict_types=1);

namespace Base3\Api;

interface IMvcView {
	public function setPath(string $path = '.');
	public function assign(string $key, $value);
	public function setTemplate(string $template = 'default');
	public function loadTemplate(): string;
	public function loadBricks(string $set, string $language = '');
	public function getBricks(string $set): ?array;
}
