<?php declare(strict_types=1);

namespace Base3\Diagnostics\Inspection;

use ParseError;

/** Lexical PHP inspection. Never requires or instantiates inspected plugin classes. */
final class PhpSourceInspector {

	/**
	 * @return array{namespace:string,types:list<string>,imports:array<string,string>,references:list<string>,staticName:?string,dynamicName:bool,classType:bool,abstractType:bool,migration:bool,migrationCertain:bool,legacyMarker:bool,downDeclared:bool,downSignature:bool,error:?string}
	 */
	public function inspect(string $file): array {
		$result = [
			'namespace' => '', 'types' => [], 'imports' => [], 'references' => [],
			'staticName' => null, 'dynamicName' => false, 'classType' => false, 'abstractType' => false,
			'migration' => false, 'migrationCertain' => false, 'legacyMarker' => false,
			'downDeclared' => false, 'downSignature' => false,
			'error' => null
		];
		$source = file_get_contents($file);
		if($source === false) {
			$result['error'] = 'Unreadable PHP file';
			return $result;
		}
		try {
			$tokens = token_get_all($source, TOKEN_PARSE);
		} catch(ParseError $exception) {
			$result['error'] = 'Invalid PHP syntax';
			return $result;
		}

		$depth = 0;
		$excluded = [];
		$imported = [];
		$implemented = [];
		$count = count($tokens);
		for($i = 0; $i < $count; $i++) {
			$token = $tokens[$i];
			if(is_string($token)) {
				if($token === '{') $depth++;
				if($token === '}') $depth--;
				continue;
			}
			$id = $token[0];
			if($id === T_NAMESPACE && $depth === 0) {
				$name = '';
				for($j = $i + 1; $j < $count && $tokens[$j] !== ';' && $tokens[$j] !== '{'; $j++) {
					$name .= is_array($tokens[$j]) ? $tokens[$j][1] : $tokens[$j];
				}
				$result['namespace'] = trim($name);
			}
			if($id === T_IMPLEMENTS && $depth === 0 && $result['classType']) {
				for($j = $i + 1; $j < $count && $tokens[$j] !== '{'; $j++) {
					$part = $tokens[$j];
					if(is_array($part) && in_array($part[0], [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED, T_NAME_RELATIVE], true)) {
						$implemented[] = $part[1];
					}
				}
			}
			if($id === T_USE && $depth === 0) {
				$statement = '';
				for($j = $i + 1; $j < $count && $tokens[$j] !== ';'; $j++) {
					$statement .= is_array($tokens[$j]) ? $tokens[$j][1] : $tokens[$j];
					$excluded[$j] = true;
				}
				$excluded[$i] = true;
				$this->collectImports(trim($statement), $imported);
				$i = $j;
				continue;
			}
			if(in_array($id, [T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM], true)) {
				$previous = $this->previousSignificant($tokens, $i);
				if(in_array($previous, [T_NEW, T_DOUBLE_COLON], true)) continue;
				if($id === T_CLASS) {
					$result['classType'] = true;
					if($previous === T_ABSTRACT) $result['abstractType'] = true;
				}
				for($j = $i + 1; $j < $count; $j++) {
					if(is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) continue;
					if(is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
						$result['types'][] = $tokens[$j][1];
					}
					break;
				}
			}
		}

		$references = [];
		foreach($tokens as $index => $token) {
			if(!is_array($token) || isset($excluded[$index])) continue;
			if($token[0] === T_NAME_FULLY_QUALIFIED) {
				$references[] = ltrim($token[1], '\\');
			}
			if(in_array($token[0], [T_STRING, T_NAME_QUALIFIED], true)) {
					$alias = explode('\\', $token[1])[0];
					if(isset($imported[$alias])) {
						$references[] = $imported[$alias] . substr($token[1], strlen($alias));
					}
			}
		}
		$result['imports'] = $imported;
		$result['references'] = array_values(array_unique($references));
		$interfaces = array_map(fn(string $name): string => $this->resolveType($name, $result['namespace'], $imported), $implemented);
		$directMigration = in_array('Base3\Migration\Api\IDatabaseMigration', $interfaces, true);
		$result['legacyMarker'] = in_array('Base3\Migration\Api\ILegacyDatabaseMigration', $interfaces, true);
		$methods = [];
		foreach($tokens as $index => $token) {
			if(is_array($token) && $token[0] === T_FUNCTION) {
				$method = $this->inspectMethod($tokens, $index);
				if($method !== null) $methods[$method['name']] = $method;
			}
		}
		if(isset($methods['getName']) && $methods['getName']['static']) {
			$result['staticName'] = $methods['getName']['literal'];
			$result['dynamicName'] = $result['staticName'] === null;
		}

		$classMethods = $this->inspectClassMethods($tokens);
		if(isset($classMethods['down'])) {
			$down = $classMethods['down'];
			$result['downDeclared'] = true;
			$result['downSignature'] = $down['public'] && !$down['static'] && $down['emptyParams'] && $down['void'] && $down['hasBody'];
		}
		$inMigrationDirectory = str_contains(str_replace('\\', '/', $file), '/src/Migration/');
		$hasMigrationMethods = isset($classMethods['getVersion'], $classMethods['up']);
		$likelyInherited = $inMigrationDirectory && count($result['types']) === 1
			&& preg_match('/Migration\d+/i', $result['types'][0]) === 1;
		$result['migrationCertain'] = $directMigration || ($inMigrationDirectory && $hasMigrationMethods);
		$result['migration'] = $result['classType'] && !$result['abstractType']
			&& ($result['migrationCertain'] || $result['legacyMarker'] || $likelyInherited);
		return $result;
	}

	/** Resolve a type name only from its lexical namespace and imports. */
	private function resolveType(string $name, string $namespace, array $imports): string {
		if(str_starts_with($name, '\\')) return ltrim($name, '\\');
		if(str_starts_with($name, 'namespace\\')) return ltrim($namespace . substr($name, 9), '\\');
		$first = explode('\\', $name, 2)[0];
		if(isset($imports[$first])) return $imports[$first] . substr($name, strlen($first));
		return ltrim($namespace . '\\' . $name, '\\');
	}

	/**
	 * Inspects only direct methods of the named class, never methods in comments,
	 * nested functions or unrelated PHP types.
	 *
	 * @param array<int,array|string> $tokens
	 * @return array<string,array{name:string,public:bool,static:bool,emptyParams:bool,void:bool,hasBody:bool,literal:?string}>
	 */
	private function inspectClassMethods(array $tokens): array {
		$methods = [];
		$depth = 0;
		$classOpening = false;
		$classDepth = null;
		foreach($tokens as $index => $token) {
			if($token === '{') {
				$depth++;
				if($classOpening && $classDepth === null) {
					$classDepth = $depth;
					$classOpening = false;
				}
				continue;
			}
			if($token === '}') {
				if($depth === $classDepth) $classDepth = null;
				$depth--;
				continue;
			}
			if(!is_array($token)) continue;
			if($token[0] === T_CLASS && $depth === 0 && !in_array($this->previousSignificant($tokens, $index), [T_NEW, T_DOUBLE_COLON], true)) {
				$classOpening = true;
			}
			if($token[0] === T_FUNCTION && $classDepth !== null && $depth === $classDepth) {
				$method = $this->inspectMethod($tokens, $index);
				if($method !== null) $methods[$method['name']] = $method;
			}
		}
		return $methods;
	}

	/** @param array<string,string> $imports */
	private function collectImports(string $statement, array &$imports): void {
		if(preg_match('/^(function|const)\s+/i', $statement) === 1) return;
		if(preg_match('/^([^{}]+)\{([^{}]+)\}$/', $statement, $match) === 1) {
			$prefix = rtrim(trim($match[1]), '\\');
			foreach(explode(',', $match[2]) as $part) {
				$this->addImport($prefix . '\\' . trim($part), $imports);
			}
			return;
		}
		foreach(explode(',', $statement) as $part) {
			$this->addImport(trim($part), $imports);
		}
	}

	/** @param array<string,string> $imports */
	private function addImport(string $part, array &$imports): void {
		if(!preg_match('/^([A-Za-z_\\\\][A-Za-z0-9_\\\\]*)(?:\s+as\s+([A-Za-z_][A-Za-z0-9_]*))?$/iD', $part, $match)) return;
		$name = ltrim($match[1], '\\');
		$alias = $match[2] ?? substr($name, (int)strrpos('\\' . $name, '\\'));
		$imports[$alias] = $name;
	}

	/**
	 * @param array<int,array|string> $tokens
	 * @return array{name:string,public:bool,static:bool,emptyParams:bool,void:bool,hasBody:bool,literal:?string}|null
	 */
	private function inspectMethod(array $tokens, int $index): ?array {
		$count = count($tokens);
		$name = null;
		$open = null;
		for($j = $index + 1; $j < $count; $j++) {
			$token = $tokens[$j];
			if(is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) continue;
			if($token === '&' || (is_array($token) && $token[0] === T_AMPERSAND_NOT_FOLLOWED_BY_VAR_OR_VARARG)) continue;
			if(is_array($token) && $token[0] === T_STRING) {
				$name = $token[1];
				continue;
			}
			if($token === '(') $open = $j;
			break;
		}
		if($name === null || $open === null) return null;
		$static = false;
		$public = false;
		for($j = $index - 1; $j >= 0; $j--) {
			$token = $tokens[$j];
			if(is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) continue;
			if(!is_array($token) || !in_array($token[0], [T_PUBLIC, T_PROTECTED, T_PRIVATE, T_STATIC, T_FINAL, T_ABSTRACT], true)) break;
			if($token[0] === T_STATIC) $static = true;
			if($token[0] === T_PUBLIC) $public = true;
		}
		$depth = 1;
		$emptyParams = true;
		$close = $open;
		for($j = $open + 1; $j < $count; $j++) {
			$token = $tokens[$j];
			if($token === '(') $depth++;
			if($token === ')') $depth--;
			if($depth === 0) { $close = $j; break; }
			if(!is_array($token) || !in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) $emptyParams = false;
		}
		$bodyStart = null;
		$void = false;
		for($j = $close + 1; $j < $count; $j++) {
			$token = $tokens[$j];
			if($token === '{') { $bodyStart = $j; break; }
			if($token === ';') break;
			if($token === ':') {
				for($k = $j + 1; $k < $count; $k++) {
					if(is_array($tokens[$k]) && in_array($tokens[$k][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) continue;
					$void = is_array($tokens[$k]) && strtolower($tokens[$k][1]) === 'void';
					break;
				}
			}
		}
		$literals = [];
		if($bodyStart !== null) {
			$depth = 1;
			for($j = $bodyStart + 1; $j < $count; $j++) {
				$token = $tokens[$j];
				if($token === '{') $depth++;
				if($token === '}') $depth--;
				if($depth === 0) break;
				if(!is_array($token)) continue;
				if($token[0] === T_RETURN) {
					$literal = null;
					for($k = $j + 1; $k < $count; $k++) {
						if(is_array($tokens[$k]) && $tokens[$k][0] === T_WHITESPACE) continue;
						if(is_array($tokens[$k]) && $tokens[$k][0] === T_CONSTANT_ENCAPSED_STRING) {
							$literal = stripcslashes(substr($tokens[$k][1], 1, -1));
						}
						break;
					}
					$literals[] = $literal;
				}
			}
		}
		return [
			'name' => $name, 'public' => $public, 'static' => $static,
			'emptyParams' => $emptyParams, 'void' => $void,
			'hasBody' => $bodyStart !== null,
			'literal' => count($literals) === 1 ? $literals[0] : null
		];
	}

	/** @param array<int,array|string> $tokens */
	private function previousSignificant(array $tokens, int $index): ?int {
		for($i = $index - 1; $i >= 0; $i--) {
			if(is_string($tokens[$i])) return null;
			if(in_array($tokens[$i][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) continue;
			return $tokens[$i][0];
		}
		return null;
	}

}
