<?php declare(strict_types=1);

namespace Base3\Core;

use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;

class DynamicMockFactory {
    private static int $counter = 0;

    public static function createMock(string $classOrInterface): object {
        if (!interface_exists($classOrInterface) && !class_exists($classOrInterface)) {
            throw new \InvalidArgumentException("Class or interface $classOrInterface does not exist.");
        }

        // Konkrete Klasse → instanziieren mit Dummy- oder rekursiven Parametern
        if (class_exists($classOrInterface)) {
            $ref = new ReflectionClass($classOrInterface);
            $ctor = $ref->getConstructor();

            if (!$ctor) {
                return new $classOrInterface();
            }

            $args = [];

            foreach ($ctor->getParameters() as $param) {
                $args[] = self::createParameterValue($param);
            }

            return $ref->newInstanceArgs($args);
        }

        // Interface → dynamischen Mock erzeugen
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
            $paramCode = '';

            if ($param->hasType()) {
                $paramType = $param->getType();
                if ($paramType instanceof ReflectionNamedType) {
                    $typeName = $paramType->getName();
                    $paramCode .= ($paramType->allowsNull() ? '?' : '');
                    $paramCode .= $paramType->isBuiltin() ? $typeName : '\\' . ltrim($typeName, '\\');
                    $paramCode .= ' ';
                }
            }

            $paramCode .= '$' . $param->getName();

            if ($param->isDefaultValueAvailable()) {
                $paramCode .= ' = ' . var_export($param->getDefaultValue(), true);
            }

            $params[] = $paramCode;
        }

        $paramList = implode(', ', $params);

        $returnType = $method->getReturnType();
        $typeHint = '';
        $returnCode = 'return null;';

        if ($returnType instanceof ReflectionNamedType) {
            $typeName = $returnType->getName();
            $nullable = $returnType->allowsNull() ? '?' : '';
            $typeHint = ': ' . $nullable . ($returnType->isBuiltin() ? $typeName : '\\' . ltrim($typeName, '\\'));
            $returnCode = self::generateReturnValueCode($returnType);
        }

        $static = $method->isStatic() ? 'static ' : '';
        return <<<PHP
            public {$static}function {$method->getName()}($paramList)$typeHint {
                $returnCode
            }
        PHP;
    }

    private static function generateReturnValueCode(ReflectionNamedType $type): string {
        if ($type->isBuiltin()) {
            return match ($type->getName()) {
                'int' => 'return 0;',
                'float' => 'return 0.0;',
                'string' => 'return "' . addslashes(uniqid('mock_', true)) . '";',
                'bool' => 'return false;',
                'array' => 'return [];',
                'void' => '',
                default => 'return null;',
            };
        }

        // Rekursiv einen Mock erzeugen für Klassen oder Interfaces
        $className = '\\' . ltrim($type->getName(), '\\');
        $mock = var_export(self::createMock($className), true);
        // Achtung: eval() kann keine serialisierten Objekte ausführen, daher:
        $mockVar = '$_mock_' . self::$counter;
        return <<<PHP
            $mockVar = \\Base3\\Core\\DynamicMockFactory::createMock('$className');
            return $mockVar;
        PHP;
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

