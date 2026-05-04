<?php

declare(strict_types=1);

namespace App\Core;

use Closure;
use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;

/**
 * Tiny DI container with constructor autowiring.
 *
 *  - get(Class::class) lazily resolves a class by reflecting its constructor
 *    and recursively resolving each parameter by its type hint.
 *  - bind(Class::class, fn ($c) => ...) registers an explicit factory for
 *    things that cannot be autowired (PDO, anything that needs config).
 *  - resolved instances are memoized — same id always returns the same object.
 */
final class Container
{
    /** @var array<class-string, object> */
    private array $instances = [];

    /** @var array<class-string, Closure> */
    private array $factories = [];

    public function bind(string $id, Closure $factory): void
    {
        $this->factories[$id] = $factory;
        unset($this->instances[$id]);
    }

    /**
     * @template T of object
     * @param class-string<T> $id
     * @return T
     */
    public function get(string $id): object
    {
        if (isset($this->instances[$id])) {
            /** @var T */
            return $this->instances[$id];
        }

        if (isset($this->factories[$id])) {
            /** @var T */
            return $this->instances[$id] = ($this->factories[$id])($this);
        }

        /** @var T */
        return $this->instances[$id] = $this->autowire($id);
    }

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