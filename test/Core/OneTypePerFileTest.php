<?php declare(strict_types=1);

namespace Base3Test\Core;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use FilesystemIterator;

/**
 * Protects BASE3's autoloader convention across framework and plugin tests.
 */
final class OneTypePerFileTest extends TestCase {

	public function testEveryPhpTypeHasItsOwnFileAndTestNamesStayIndependent(): void {
		$frameworkRoot = dirname(__DIR__, 2);
		$pluginRoot = is_dir($frameworkRoot . '/plugin')
			? $frameworkRoot . '/plugin'
			: dirname($frameworkRoot);
		$roots = [$frameworkRoot . '/src', $frameworkRoot . '/test'];
		foreach (glob($pluginRoot . '/*', GLOB_ONLYDIR) ?: [] as $plugin) {
			$roots[] = $plugin . DIRECTORY_SEPARATOR . 'src';
			$roots[] = $plugin . DIRECTORY_SEPARATOR . 'test';
		}

		$sourceNames = [];
		$testNames = [];
		$violations = [];

		foreach ($roots as $root) {
			if (!is_dir($root)) {
				continue;
			}

			$isTest = basename($root) === 'test';
			$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
			foreach ($files as $file) {
				if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
					continue;
				}
				$path = $file->getPathname();
				if (str_contains($path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR)) {
					continue;
				}
				$types = $this->declaredTypes((string)file_get_contents($path), $path, $violations);
				if (count($types) > 1) {
					$violations[] = $path . ': more than one named PHP type';
				}
				foreach ($types as $name) {
					if ($name !== pathinfo($path, PATHINFO_FILENAME)) {
						$violations[] = $path . ': named type ' . $name . ' does not match filename';
					}
					if ($isTest) {
						$testNames[strtolower($name)][] = $path;
					} else {
						$sourceNames[strtolower($name)][] = $path;
					}
				}
			}
		}

		foreach (array_intersect_key($testNames, $sourceNames) as $name => $paths) {
			$violations[] = 'Test type ' . $name . ' reuses a source type name: ' . implode(', ', $paths);
		}

		$this->assertSame([], $violations, implode("\n", $violations));
	}

	/**
	 * @param array<int,string> $violations
	 * @return array<int,string>
	 */
	private function declaredTypes(string $code, string $path, array &$violations): array {
		$tokens = token_get_all($code);
		$types = [];
		foreach ($tokens as $i => $token) {
			if (!is_array($token) || !in_array($token[0], [T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM], true)) {
				continue;
			}
			$previous = $i - 1;
			while ($previous >= 0 && is_array($tokens[$previous]) && in_array($tokens[$previous][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
				$previous--;
			}
			if ($token[0] === T_CLASS && $previous >= 0) {
				$previousToken = $tokens[$previous];
				if (is_array($previousToken) && $previousToken[0] === T_DOUBLE_COLON) {
					continue;
				}
				if (is_array($previousToken) && $previousToken[0] === T_NEW) {
					$violations[] = $path . ':' . $token[2] . ': anonymous class inside PHP file';
					continue;
				}
			}
			$next = $i + 1;
			while (isset($tokens[$next]) && is_array($tokens[$next]) && $tokens[$next][0] === T_WHITESPACE) {
				$next++;
			}
			if (isset($tokens[$next]) && is_array($tokens[$next]) && $tokens[$next][0] === T_STRING) {
				$types[] = $tokens[$next][1];
			}
		}
		return $types;
	}
}
