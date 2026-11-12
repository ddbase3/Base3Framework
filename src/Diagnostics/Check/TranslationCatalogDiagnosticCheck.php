<?php declare(strict_types=1);

namespace Base3\Diagnostics\Check;

use Base3\Diagnostics\Base\AbstractSourceDiagnosticCheck;
use Base3\Diagnostics\Dto\DiagnosticCheckResult;
use Base3\Diagnostics\Scope\InstallationDiagnosticScope;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

/** Checks the existence and key completeness of each module-owned language catalog. */
final class TranslationCatalogDiagnosticCheck extends AbstractSourceDiagnosticCheck {

	/** Baseline languages. Extend here when the framework adds a supported language. */
	private const REQUIRED_LANGUAGES = ['ar', 'bg', 'de', 'en', 'es', 'fr', 'hi', 'it', 'pl', 'pt', 'ru', 'zh'];

	public static function getName(): string {
		return 'translationcatalogdiagnosticcheck';
	}

	public function getScope(): string {
		return InstallationDiagnosticScope::getName();
	}

	public function run(): DiagnosticCheckResult {
		$inventory = $this->inventory();
		$roots = $inventory->roots();
		$failures = $roots['errors'];
		$warnings = $roots['coverage'] ? [] : ['Module discovery is incomplete.'];
		$inspected = 0;

		foreach($roots['roots'] as $root) {
			$read = $inventory->manifest($root);
			if($read['errors'] || $read['manifest'] === null) {
				$failures[] = basename($root) . ': cannot inspect language files without a valid base3.json';
				continue;
			}
			$langRoot = $root . '/lang';
			if(!is_dir($langRoot)) continue;
			try {
				$groups = [];
				$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($langRoot, FilesystemIterator::SKIP_DOTS));
				foreach($iterator as $file) {
					if(!$file->isFile() || strtolower($file->getExtension()) !== 'ini') continue;
					$groups[$file->getPath()] = true;
				}
				ksort($groups, SORT_STRING);
				foreach(array_keys($groups) as $directory) {
					$inspected++;
					$setName = ltrim(substr($directory, strlen($langRoot)), '/\\');
					$label = basename($root) . '/lang/' . ($setName === '' ? '' : str_replace('\\', '/', $setName) . '/');
					$this->inspectGroup($directory, $label, $failures);
				}
			} catch(Throwable $exception) {
				$failures[] = basename($root) . ': cannot enumerate language files';
			}
		}

		if($inspected === 0 && !$failures && !$warnings) {
			return new DiagnosticCheckResult(DiagnosticCheckResult::NOT_APPLICABLE, 'not_applicable', ['inspected' => 0]);
		}
		return $this->report($failures, $warnings, $inspected);
	}

	/** @param list<string> $failures */
	private function inspectGroup(string $directory, string $label, array &$failures): void {
		$files = glob($directory . '/*.ini') ?: [];
		sort($files, SORT_STRING);
		$present = [];
		$catalogs = [];
		foreach($files as $file) {
			$language = basename($file, '.ini');
			$present[$language] = true;
			if(is_link($file) || !is_readable($file)) {
				$failures[] = $label . $language . '.ini: not a readable regular file';
				continue;
			}
			$data = @parse_ini_file($file, true, INI_SCANNER_RAW);
			if(!is_array($data)) {
				$failures[] = $label . $language . '.ini: invalid INI syntax';
				continue;
			}
			$entries = [];
			foreach($data as $section => $values) {
				if(!is_array($values)) {
					$failures[] = $label . $language . '.ini: entries must belong to a section';
					continue;
				}
				foreach($values as $key => $value) {
					$identity = '[' . $section . ']' . $key;
					if(!is_string($value) || trim($value) === '') {
						$failures[] = $label . $language . '.ini: empty or invalid translation ' . $identity;
					}
					$placeholders = [];
					if(is_string($value)) {
						preg_match_all('/\{[A-Za-z_][A-Za-z0-9_]*\}/', $value, $matches);
						$placeholders = array_values(array_unique($matches[0]));
						sort($placeholders, SORT_STRING);
					}
					$entries[$identity] = $placeholders;
				}
			}
			$catalogs[$language] = $entries;
		}
		foreach(self::REQUIRED_LANGUAGES as $language) {
			if(!isset($present[$language])) {
				$failures[] = $label . ': missing ' . $language . '.ini';
			}
		}
		$allKeys = [];
		foreach($catalogs as $entries) {
			foreach($entries as $key => $_) $allKeys[$key] = true;
		}
		ksort($allKeys, SORT_STRING);
		$referencePlaceholders = [];
		foreach($catalogs as $language => $entries) {
			foreach($entries as $key => $placeholders) {
				if(!isset($referencePlaceholders[$key]) || $language === 'en') {
					$referencePlaceholders[$key] = $placeholders;
				}
			}
		}
		foreach($catalogs as $language => $entries) {
			foreach($allKeys as $key => $_) {
				if(!isset($entries[$key])) {
					$failures[] = $label . $language . '.ini: missing ' . $key;
				} elseif($entries[$key] !== $referencePlaceholders[$key]) {
					$failures[] = $label . $language . '.ini: placeholders differ in ' . $key;
				}
			}
		}
	}

}
