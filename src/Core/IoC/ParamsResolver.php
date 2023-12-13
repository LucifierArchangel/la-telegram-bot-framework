<?php

namespace Lucifier\Framework\Core\IoC;


use Lucifier\Framework\Utils\Logger\FileLogger;
use ReflectionException;
use ReflectionNamedType;
use ReflectionParameter;

class ParamsResolver {
    public function __construct(
        protected IContainer $container,
        protected array $parameters,
        protected array $args = []
    ) { }

    public function getArguments(): array {
        return array_map(
        /**
         * @throws ReflectionException
         */ function (ReflectionParameter $param) {
                $type = $param->getType();
                $name = $param->getName();

                if ($type && $type instanceof ReflectionNamedType) {
                    $reflectionInstance = $this->getClassInstance($type);

                    return $reflectionInstance;
                } else {
                    if (array_key_exists($name, $this->args)) {
                        return $this->args[$name];
                    } else {
                        return $param->getDefaultValue();
                    }
                }
            },
            $this->parameters
        );
    }

    protected function getClassInstance(string $namespace): object {
        return (new ClassResolver($this->container, $namespace))->getInstance();
    }
}

?>