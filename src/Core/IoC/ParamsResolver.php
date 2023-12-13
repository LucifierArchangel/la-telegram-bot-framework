<?php

namespace Lucifier\Framework\Core\IoC;


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
         * @throws \ReflectionException
         */ function (ReflectionParameter $param) {
                if (array_key_exists($param->getName(), $this->args)) {
                    return $this->args[$param->getName()];
                }

    //            return $param->getType() && $param->getType()->isBuiltin()
    //                ? $this->getClassInstance($param->getType()->getName())
    //                : $param->getDefaultValue();

                if ($param->getType() && $param->getType()->isBuiltin()) {
                    return $this->getClassInstance($param->getType()->getName());
                } else {
                    var_dump($param->getType()->isBuiltin());
                    die;
                    return $param->getDefaultValue();
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