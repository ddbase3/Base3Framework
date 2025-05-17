<?php declare(strict_types=1);

namespace Base3\Core;

use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;
use ReflectionType;

class DynamicMockFactory {
    private static int $counter = 0;

    public static function createMock(string $classOrInterface): object {
        if (!interface_exists($classOrInterface) && !class_exists($classOrInterface)) {
            throw new \InvalidArgumentException("Class or interface $classOrInterface does not exist.");
        }

        if (class_exists($classOrInterface) && !interface_exists($classOrInterface)) {
            $ref = new ReflectionClass($classOrInterface);
            $ctor = $ref->getConstructor();
            if (!$ctor) return new $classOrInterface();

            $args = array_map(fn($param) => self::createParameterValue($param), $ctor->getParameters());
            return $ref->newInstanceArgs($args);
        }

        return self::createInterfaceMock($classOrInterface);
    }

    private static function createInterfaceMock(string $interface): object {
        $reflection = new ReflectionClass($interface);
        $methodsCode = '';

        foreach ($reflection->getMethods() as $method) {
            $methodsCode .= self::generateMethodStub($method) . "\n";
        }

        $className = 'Mock_' . $reflection->getShortName() . '_' . self::$counter++;
        $code = <<<PHP
            return new class implements \\$interface {
                $methodsCode
            };
        PHP;

        return eval($code);
    }

    private static function generateMethodStub(ReflectionMethod $method): string {
        $params = [];

        foreach ($method->getParameters() as $param) {
            $params[] = self::generateParameterCode($param);
        }

        $paramList = implode(', ', $params);

        $returnByReference = $method->returnsReference() ? '&' : '';
        $static = $method->isStatic() ? 'static ' : '';
        $name = $method->getName();

        $returnType = $method->getReturnType();
        $typeHint = $returnType ? ': ' . self::getTypeHint($returnType) : '';

        $returnCode = self::generateReturnValueCode($returnType);

        return <<<PHP
            public {$static}function {$returnByReference}{$name}($paramList)$typeHint {
                $returnCode
            }
        PHP;
    }

    private static function generateParameterCode(ReflectionParameter $param): string {
        $code = '';

        // Typ
        if ($param->hasType()) {
            $type = $param->getType();
            $code .= self::getTypeHint($type) . ' ';
        }

        // Referenz
        if ($param->isPassedByReference()) {
            $code .= '&';
        }

        // Variadisch
        if ($param->isVariadic()) {
            $code .= '...';
        }

        $code .= '$' . $param->getName();

        // Defaultwert
        if ($param->isOptional() && !$param->isVariadic()) {
            if ($param->isDefaultValueAvailable()) {
                $code .= ' = ' . var_export($param->getDefaultValue(), true);
            } elseif ($param->isDefaultValueConstant()) {
                $code .= ' = ' . $param->getDefaultValueConstantName();
            }
        }

        return $code;
    }

    private static function getTypeHint(?ReflectionType $type): string {
        if (!$type) return '';

        $nullable = $type->allowsNull() ? '?' : '';

        if ($type instanceof ReflectionNamedType) {
            $name = $type->getName();
            if (!in_array($name, ['int', 'float', 'string', 'bool', 'array', 'object', 'callable', 'iterable', 'mixed', 'void', 'never'])) {
                $name = '\\' . ltrim($name, '\\');
            }
            return $nullable . $name;
        }

        if ($type instanceof ReflectionUnionType) {
            return implode('|', array_map(
                fn(ReflectionNamedType $t) => ($t->allowsNull() ? '?' : '') .
                    (in_array($t->getName(), ['int','float','string','bool','array','object','callable','iterable','mixed','void','never'])
                        ? $t->getName()
                        : '\\' . ltrim($t->getName(), '\\')),
                $type->getTypes()
            ));
        }

        return ''; // fallback
    }

    private static function generateReturnValueCode(?ReflectionType $type): string {
        if (!$type) return 'return null;';

        if ($type instanceof ReflectionUnionType) {
            // Nimm den ersten gültigen Typ zur Generierung des Rückgabewerts
            foreach ($type->getTypes() as $subtype) {
                if ($subtype instanceof ReflectionNamedType) return self::generateReturnValueCode($subtype);
            }
            return 'return null;';
        }

        if ($type instanceof ReflectionNamedType) {
            $name = $type->getName();

            if ($type->isBuiltin()) {
                return match ($name) {
                    'int' => 'return 0;',
                    'float' => 'return 0.0;',
                    'string' => 'return "' . addslashes(uniqid('mock_', true)) . '";',
                    'bool' => 'return false;',
                    'array' => 'return [];',
                    'void', 'never' => '',
                    'mixed', 'object', 'callable', 'iterable' => 'return null;',
                    default => 'return null;',
                };
            } else {
                // Objekt-Mock rekursiv erzeugen
                $fqcn = '\\' . ltrim($name, '\\');
                return "return \\Base3\\Core\\DynamicMockFactory::createMock('$fqcn');";
            }
        }

        return 'return null;';
    }

    private static function createParameterValue(ReflectionParameter $param): mixed {
        $type = $param->getType();
        if ($type instanceof ReflectionNamedType) {
            if (!$type->isBuiltin()) {
                return self::createMock($type->getName());
            }
            return self::defaultValueFor($type->getName());
        }
        return null;
    }

    private static function defaultValueFor(?string $type): mixed {
        return match ($type) {
            'int' => 0,
            'float' => 0.0,
            'string' => '',
            'bool' => false,
            'array' => [],
            default => null,
        };
    }
}

