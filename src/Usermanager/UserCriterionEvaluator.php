<?php declare(strict_types=1);

namespace Base3\Usermanager;

use Base3\Api\IClassMap;
use Base3\Usermanager\Api\IUserCriterion;
use InvalidArgumentException;

/** One evaluator for plugin-discoverable user criteria and BASE3 group/feature audiences. */
final class UserCriterionEvaluator {

	public function __construct(private readonly IClassMap $classMap) {}

	/**
	 * Tests a declarative audience expression without changing user, role, group or permission data.
	 * Group leaves are allowed only when the caller supplies effective BASE3 group names; group
	 * membership definitions MUST NOT reference groups, preventing recursive membership cycles.
	 * Empty expressions never match. Invalid and unknown definitions throw (caller fails closed).
	 *
	 * @param User|null $user Trusted identity, null for anonymous
	 * @param array<string,mixed> $definition Leaf or nested any/all/not expression
	 * @param list<string> $groups Effective BASE3 group names, provided only for feature audiences
	 * @return bool Whether the expression matches
	 * @throws InvalidArgumentException On unknown criteria, malformed expressions or excessive depth
	 */
	public function matches(?User $user, array $definition, array $groups = []): bool {
		return $this->evaluate($user, $definition, $groups, 0);
	}

	/**
	 * Validates a persisted audience expression independently of a particular user.
	 * Uses the discovered criterion schemas to reject invalid configuration before saving.
	 * Group references are allowed only when a known group list is supplied.
	 *
	 * @param array<string,mixed> $definition Audience expression; [] means deny all
	 * @param list<string>|null $groups Names of allowed referenced groups, or null to reject group leaves
	 * @return void
	 * @throws InvalidArgumentException On unknown predicates, names, invalid config or syntax
	 */
	public function validate(array $definition, ?array $groups = null): void {
		$this->validateNode($definition, $groups, 0);
	}

	private function evaluate(?User $user, array $definition, array $groups, int $depth): bool {
		if ($depth > 12) throw new InvalidArgumentException('Criterion expression exceeds maximum depth.');
		if ($definition === []) return false;
		if (array_key_exists('group', $definition)) {
			if (count($definition) !== 1 || !is_string($definition['group']) || $definition['group'] === '') {
				throw new InvalidArgumentException('Invalid BASE3 group reference.');
			}
			return in_array($definition['group'], $groups, true);
		}
		if (array_key_exists('criterion', $definition)) {
			if (array_diff(array_keys($definition), ['criterion', 'config']) !== []) throw new InvalidArgumentException('Unexpected criterion fields.');
			$name = $definition['criterion'];
			$config = $definition['config'] ?? [];
			if (!is_string($name) || $name === '' || !is_array($config)) throw new InvalidArgumentException('Invalid criterion leaf.');
			$criterion = $this->classMap->getInstanceByInterfaceName(IUserCriterion::class, $name);
			if (!$criterion instanceof IUserCriterion) throw new InvalidArgumentException('Unknown user criterion: ' . $name);
			return $criterion->matches($user, $config);
		}
		if (count($definition) !== 1) throw new InvalidArgumentException('Criterion expression has ambiguous operators.');
		if (array_key_exists('not', $definition)) {
			if (!is_array($definition['not']) || $definition['not'] === []) throw new InvalidArgumentException('Invalid negated criterion.');
			return !$this->evaluate($user, $definition['not'], $groups, $depth + 1);
		}
		foreach (['any', 'all'] as $operator) {
			if (!array_key_exists($operator, $definition)) continue;
			$items = $definition[$operator];
			if (!is_array($items) || !array_is_list($items) || $items === []) throw new InvalidArgumentException('Compound criterion requires a non-empty list.');
			foreach ($items as $item) {
				if (!is_array($item)) throw new InvalidArgumentException('Invalid nested criterion.');
				$matched = $this->evaluate($user, $item, $groups, $depth + 1);
				if ($operator === 'any' && $matched) return true;
				if ($operator === 'all' && !$matched) return false;
			}
			return $operator === 'all';
		}
		throw new InvalidArgumentException('Unknown criterion operator.');
	}

	private function validateNode(array $definition, ?array $groups, int $depth): void {
		if ($depth > 12) throw new InvalidArgumentException('Criterion expression exceeds maximum depth.');
		if ($definition === []) return;
		if (array_key_exists('group', $definition)) {
			if (count($definition) !== 1 || $groups === null || !is_string($definition['group']) || !in_array($definition['group'], $groups, true)) {
				throw new InvalidArgumentException('Unknown or disallowed BASE3 group reference.');
			}
			return;
		}
		if (array_key_exists('criterion', $definition)) {
			if (array_diff(array_keys($definition), ['criterion', 'config']) !== []) throw new InvalidArgumentException('Unexpected criterion fields.');
			$name = $definition['criterion'];
			$config = $definition['config'] ?? [];
			if (!is_string($name) || $name === '' || !is_array($config) || array_is_list($config) && $config !== []) {
				throw new InvalidArgumentException('Invalid criterion configuration.');
			}
			$criterion = $this->classMap->getInstanceByInterfaceName(IUserCriterion::class, $name);
			if (!$criterion instanceof IUserCriterion) throw new InvalidArgumentException('Unknown user criterion: ' . $name);
			$this->validateSchema($config, $criterion->getSchema());
			return;
		}
		if (count($definition) !== 1) throw new InvalidArgumentException('Criterion expression has ambiguous operators.');
		if (array_key_exists('not', $definition)) {
			if (!is_array($definition['not']) || $definition['not'] === []) throw new InvalidArgumentException('Invalid negated criterion.');
			$this->validateNode($definition['not'], $groups, $depth + 1);
			return;
		}
		foreach (['any', 'all'] as $operator) {
			if (!array_key_exists($operator, $definition)) continue;
			$items = $definition[$operator];
			if (!is_array($items) || !array_is_list($items) || $items === []) throw new InvalidArgumentException('Compound criterion requires a non-empty list.');
			foreach ($items as $item) {
				if (!is_array($item)) throw new InvalidArgumentException('Invalid nested criterion.');
				$this->validateNode($item, $groups, $depth + 1);
			}
			return;
		}
		throw new InvalidArgumentException('Unknown criterion operator.');
	}

	/** Checks the simple JSON-schema subset used by BASE3 user predicates at the persistence boundary. */
	private function validateSchema(array $value, array $schema): void {
		if (($schema['type'] ?? null) !== 'object') throw new InvalidArgumentException('Criterion requires an object schema.');
		$properties = $schema['properties'] ?? [];
		if (!is_array($properties)) throw new InvalidArgumentException('Invalid criterion schema.');
		foreach ((array)($schema['required'] ?? []) as $name) {
			if (!is_string($name) || !array_key_exists($name, $value)) throw new InvalidArgumentException('Missing required criterion property.');
		}
		foreach ($value as $name => $item) {
			if (!is_string($name) || !isset($properties[$name])) {
				if (($schema['additionalProperties'] ?? true) === false) throw new InvalidArgumentException('Unexpected criterion property.');
				continue;
			}
			$this->validateValue($item, $properties[$name]);
		}
	}

	private function validateValue(mixed $value, array $schema): void {
		$types = (array)($schema['type'] ?? []);
		$valid = false;
		foreach ($types as $type) {
			$valid = $valid || match ($type) {
				'string' => is_string($value),
				'integer' => is_int($value),
				'number' => is_float($value) || is_int($value),
				'boolean' => is_bool($value),
				'array' => is_array($value) && array_is_list($value),
				'object' => is_array($value) && (!array_is_list($value) || $value === []),
				'null' => $value === null,
				default => false
			};
		}
		if ($types !== [] && !$valid) throw new InvalidArgumentException('Invalid criterion value type.');
		if (isset($schema['enum']) && !in_array($value, $schema['enum'], true)) throw new InvalidArgumentException('Invalid criterion enum value.');
		if (is_string($value) && isset($schema['minLength']) && mb_strlen($value) < (int)$schema['minLength']) throw new InvalidArgumentException('Criterion string is too short.');
		if ((is_int($value) || is_float($value)) && isset($schema['minimum']) && $value < $schema['minimum']) throw new InvalidArgumentException('Criterion number is too small.');
		if (is_array($value) && in_array('array', $types, true)) {
			if (($schema['uniqueItems'] ?? false) && count(array_unique(array_map('serialize', $value))) !== count($value)) throw new InvalidArgumentException('Duplicate criterion array value.');
			if (isset($schema['items'])) foreach ($value as $item) $this->validateValue($item, (array)$schema['items']);
		}
		if (is_array($value) && in_array('object', $types, true)) $this->validateSchema($value, $schema);
	}
}
