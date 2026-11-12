<?php declare(strict_types=1);

namespace Base3\Diagnostics\Check;

use Base3\Diagnostics\Base\AbstractSourceDiagnosticCheck;
use Base3\Diagnostics\Dto\DiagnosticCheckResult;
use Base3\Diagnostics\Inspection\ModuleSourceInventory;
use Base3\Diagnostics\Inspection\PhpSourceInspector;
use Base3\Diagnostics\Scope\DependencyDiagnosticScope;

/** Checks statically referenced BASE3 types against source files and hard dependencies. */
final class PhpModuleDependencyDiagnosticCheck extends AbstractSourceDiagnosticCheck {

	public static function getName(): string {
		return 'phpmoduledependencydiagnosticcheck';
	}

	public function getScope(): string {
		return DependencyDiagnosticScope::getName();
	}

	public function run(): DiagnosticCheckResult {
		$inventory = $this->inventory();
		$roots = $inventory->roots();
		$failures = $roots['errors'];
		$warnings = $roots['coverage'] ? [] : ['Module discovery is incomplete.'];
		$modules = [];
		foreach($roots['roots'] as $root) {
			$read = $inventory->manifest($root);
			if($read['errors'] || $read['manifest'] === null) {
				$failures[] = 'Cannot inspect dependencies of ' . basename($root) . ': invalid or missing manifest';
				continue;
			}
			$manifest = $read['manifest'];
			$modules[$manifest['name']] = ['root' => $root, 'manifest' => $manifest];
		}

		$parser = new PhpSourceInspector();
		$inspected = 0;
		$already = [];
		foreach($modules as $moduleName => $module) {
			foreach($inventory->sourceFiles($module['root']) as $file) {
				$inspected++;
				$source = $parser->inspect($file);
				$location = ModuleSourceInventory::relativeFile($module['root'], $file);
				if($source['error'] !== null) {
					$failures[] = $location . ': ' . $source['error'];
					continue;
				}
				foreach($source['references'] as $reference) {
					$owner = $this->ownerOf($reference, $modules);
					if($owner === null) {
						$prefix = explode('\\', $reference)[0];
						if($prefix === 'Base3' || str_ends_with($prefix, 'Foundation')) {
							$key = $moduleName . '|unknown|' . $prefix;
							if(!isset($already[$key])) $warnings[] = $location . ': BASE3 namespace ' . $prefix . ' has no known module owner';
							$already[$key] = true;
						}
						continue;
					}
					if($owner === $moduleName) continue;
					$target = $modules[$owner];
					$prefix = $target['manifest']['namespace'];
					$relative = substr($reference, strlen($prefix) + 1);
					$targetFile = $target['root'] . '/src/' . str_replace('\\', '/', $relative) . '.php';
					$key = $moduleName . '|' . $reference;
					if(!is_file($targetFile)) {
						if(!isset($already[$key])) $failures[] = $location . ': missing referenced type ' . $reference;
						$already[$key] = true;
					} else {
						$declaration = $parser->inspect($targetFile);
						$expectedNamespace = str_contains($reference, '\\') ? substr($reference, 0, (int)strrpos($reference, '\\')) : '';
						$expectedName = substr($reference, (int)strrpos('\\' . $reference, '\\'));
						if($declaration['error'] !== null || $declaration['namespace'] !== $expectedNamespace || !in_array($expectedName, $declaration['types'], true)) {
							if(!isset($already[$key])) $failures[] = $location . ': invalid referenced type ' . $reference;
							$already[$key] = true;
						}
					}
					if(!array_key_exists($owner, $module['manifest']['dependencies'] ?? [])) {
						$key = $moduleName . '|dependency|' . $owner;
						if(!isset($already[$key])) $failures[] = $moduleName . ': missing manifest dependency on ' . $owner . ' (' . $location . ')';
						$already[$key] = true;
					}
				}
			}
		}
		return $this->report($failures, $warnings, $inspected);
	}

	/**
	 * @param array<string,array{root:string,manifest:array<string,mixed>}> $modules
	 */
	private function ownerOf(string $reference, array $modules): ?string {
		$owner = null;
		$longest = 0;
		foreach($modules as $name => $module) {
			$prefix = $module['manifest']['namespace'];
			if(str_starts_with($reference, $prefix . '\\') && strlen($prefix) > $longest) {
				$owner = $name;
				$longest = strlen($prefix);
			}
		}
		return $owner;
	}

}
