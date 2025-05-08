<?php declare(strict_types=1);

namespace Base3\Core;

class DynamicMockFactory {
    private static int $counter = 0;

    public static function createMock(string $interface): object {
        if (!interface_exists($interface)) {
            throw new \InvalidArgumentException("Interface $interface does not exist.");
        }

        $reflection = new \ReflectionClass($interface);
        $methodsCode = '';

        foreach ($reflection->getMethods() as $method) {
            $methodCode = self::generateMethodStub($method);
            $methodsCode .= $methodCode . "\n";
        }

        $className = 'Mock_' . $reflection->getShortName() . '_' . self::$counter++;
        $code = <<<PHP
            return new class implements \\$interface {
                $methodsCode
            };
        PHP;

        return eval($code);
    }

    private static function generateMethodStub(\ReflectionMethod $method): string {
        $params = [];

        foreach ($method->getParameters() as $param) {
            $paramCode = '';
            if ($param->hasType()) {
                $paramType = $param->getType();
                if ($paramType instanceof \ReflectionNamedType) {

$typeName = $paramType->getName();
if (!$paramType->isBuiltin()) {
    $paramCode .= ($paramType->allowsNull() ? '?' : '') . '\\' . ltrim($typeName, '\\') . ' ';
} else {
    $paramCode .= ($paramType->allowsNull() ? '?' : '') . $typeName . ' ';
}

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
        $returnCode = 'return null;';
        $typeHint = '';

        if ($returnType instanceof \ReflectionNamedType) {

$typeName = $returnType->getName();
if (!$returnType->isBuiltin()) {
    $typeHint = ': ' . ($returnType->allowsNull() ? '?' : '') . '\\' . ltrim($typeName, '\\');
} else {
    $typeHint = ': ' . ($returnType->allowsNull() ? '?' : '') . $typeName;
}

            $returnCode = self::generateReturnValueCode($returnType);
        }

        return <<<PHP
            public function {$method->getName()}($paramList)$typeHint {
                $returnCode
            }
        PHP;
    }

    private static function generateReturnValueCode(\ReflectionNamedType $type): string {
        if (!$type->isBuiltin()) {
            return 'return null;';
        }

        switch ($type->getName()) {
            case 'int': return 'return 0;';
            case 'float': return 'return 0.0;';
            case 'string': return 'return "' . uniqid('mock_', true) . '";';
            case 'bool': return 'return false;';
            case 'array': return 'return [];';
            case 'void': return ''; // no return statement
            default: return 'return null;';
        }
    }
}

