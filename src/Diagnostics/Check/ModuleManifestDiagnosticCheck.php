<?php declare(strict_types=1);

namespace Base3\Diagnostics\Check;

use Base3\Diagnostics\Base\AbstractSourceDiagnosticCheck;
use Base3\Diagnostics\Dto\DiagnosticCheckResult;
use Base3\Diagnostics\Scope\InstallationDiagnosticScope;

/** Validates the deployed module manifests and declared hard dependencies. */
final class ModuleManifestDiagnosticCheck extends AbstractSourceDiagnosticCheck {

	public static function getName(): string {
		return 'modulemanifestdiagnosticcheck';
	}

	public function getScope(): string {
		return InstallationDiagnosticScope::getName();
	}

	public function run(): DiagnosticCheckResult {
		$inventory = $this->inventory();
		$roots = $inventory->roots();
		$failures = $roots['errors'];
		$warnings = $roots['coverage'] ? [] : ['Only discoverable and standard module roots were inspected.'];
		$modules = [];
		$namespaces = [];
		foreach($roots['roots'] as $root) {
			$read = $inventory->manifest($root);
			array_push($failures, ...$read['errors']);
			if($read['errors'] || $read['manifest'] === null) continue;
			$manifest = $read['manifest'];
			$name = $manifest['name'];
			$namespace = strtolower($manifest['namespace']);
			if(isset($modules[$name])) {
				$failures[] = 'Duplicate module name ' . $name;
				continue;
			}
			if(isset($namespaces[$namespace])) {
				$failures[] = 'Duplicate namespace ' . $manifest['namespace'];
			}
			$namespaces[$namespace] = $name;
			$modules[$name] = $manifest;
		}

		foreach($modules as $name => $manifest) {
			foreach($manifest['dependencies'] ?? [] as $dependency => $constraint) {
				if(!is_string($dependency) || !is_string($constraint)) continue;
				if($name === $dependency) {
					$failures[] = $name . ' depends on itself';
				} elseif(!isset($modules[$dependency])) {
					$failures[] = $name . ' requires missing ' . $dependency;
				} else {
					$valid = $this->matchesConstraint($modules[$dependency]['version'], $constraint);
					if($valid === null) {
						$failures[] = $name . ' has unsupported constraint for ' . $dependency;
					} elseif(!$valid) {
						$failures[] = $name . ' requires ' . $dependency . ' ' . $constraint . ', found ' . $modules[$dependency]['version'];
					}
				}
			}
		}
		$this->detectCycles($modules, $failures);
		return $this->report($failures, $warnings, count($roots['roots']));
	}

	/**
	 * @param array<string,array<string,mixed>> $modules
	 * @param list<string> $failures
	 */
	private function detectCycles(array $modules, array &$failures): void {
		$state = [];
		$visit = function(string $name) use (&$visit, &$state, &$failures, $modules): void {
			$state[$name] = 1;
			foreach(array_keys($modules[$name]['dependencies'] ?? []) as $dependency) {
				if(!isset($modules[$dependency])) continue;
				if(($state[$dependency] ?? 0) === 1) {
					$failures[] = 'Cyclic dependency between ' . $name . ' and ' . $dependency;
				} elseif(($state[$dependency] ?? 0) === 0) {
					$visit($dependency);
				}
			}
			$state[$name] = 2;
		};
		foreach(array_keys($modules) as $name) {
			if(($state[$name] ?? 0) === 0) $visit($name);
		}
	}

	/**
	 * Checks the documented basic Composer-style constraints. Unknown syntax fails closed.
	 * @return bool|null Null means the expression cannot be evaluated reliably
	 */
	private function matchesConstraint(string $version, string $constraint): ?bool {
		foreach(preg_split('/\s*\|\|\s*/', trim($constraint)) ?: [] as $alternative) {
			$parts = preg_split('/\s*,\s*|\s+/', trim($alternative), -1, PREG_SPLIT_NO_EMPTY);
			if(!$parts) return null;
			$all = true;
			foreach($parts as $part) {
				if($part === '*') continue;
				if(!preg_match('/^(\^|~|>=|<=|>|<|==|=)?(\d+(?:\.\d+){0,2})(\.\*)?$/D', $part, $match)) return null;
				$operator = $match[1] ?: '=';
				$raw = $match[2];
				$digits = array_map('intval', explode('.', $raw));
				$length = count($digits);
				$digits = array_pad($digits, 3, 0);
				$lower = implode('.', $digits);
				if(($match[3] ?? '') === '.*') {
					if($operator !== '=') return null;
					$upper = $length === 1 ? ($digits[0] + 1) . '.0.0' : $digits[0] . '.' . ($digits[1] + 1) . '.0';
					$okay = version_compare($version, $lower, '>=') && version_compare($version, $upper, '<');
				} elseif($operator === '^' || $operator === '~') {
					if($operator === '^') {
						if($digits[0] > 0) $upper = ($digits[0] + 1) . '.0.0';
						elseif($digits[1] > 0) $upper = '0.' . ($digits[1] + 1) . '.0';
						else $upper = '0.0.' . ($digits[2] + 1);
					} else {
						$upper = $length >= 3 ? $digits[0] . '.' . ($digits[1] + 1) . '.0' : ($digits[0] + 1) . '.0.0';
					}
					$okay = version_compare($version, $lower, '>=') && version_compare($version, $upper, '<');
				} else {
					$okay = version_compare($version, $lower, $operator);
				}
				$all = $all && $okay;
			}
			if($all) return true;
		}
		return false;
	}

}
