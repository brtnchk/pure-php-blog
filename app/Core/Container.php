<?php

declare(strict_types=1);

namespace App\Core;

use Closure;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use RuntimeException;

final class Container
{
    /** @var array<string, object> */
    private array $instances = [];

    /** @var array<string, Closure> */
    private array $factories = [];

    public function bind(string $id, Closure $factory): void
    {
        $this->factories[$id] = $factory;
        unset($this->instances[$id]);
    }

    /**
     * @template T of object
     *
     * @param class-string<T>|string $id
     *
     * @return ($id is class-string<T> ? T : object)
     */
    public function get(string $id): object
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (isset($this->factories[$id])) {
            return $this->instances[$id] = ($this->factories[$id])($this);
        }

        return $this->instances[$id] = $this->autowire($id);
    }

    /** @throws ReflectionException */
    private function autowire(string $class): object
    {
        if (!class_exists($class)) {
            throw new RuntimeException("Container: class {$class} does not exist.");
        }

        $reflection = new ReflectionClass($class);
        if (!$reflection->isInstantiable()) {
            throw new RuntimeException("Container: {$class} is not instantiable.");
        }

        $constructor = $reflection->getConstructor();
        if ($constructor === null) {
            return new $class();
        }

        $args = [];
        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();

            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $args[] = $this->get($type->getName());
                continue;
            }

            if ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
                continue;
            }

            throw new RuntimeException(sprintf(
                'Container: cannot resolve parameter $%s of %s — no type hint and no default.',
                $param->getName(),
                $class,
            ));
        }

        return $reflection->newInstanceArgs($args);
    }
}
