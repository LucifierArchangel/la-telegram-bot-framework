<?php

    namespace Lucifier\Framework\Core\IoC;

    use Lucifier\Framework\Utils\Logger\FileLogger;
    use ReflectionClass;

    class ClassResolver {
        public function __construct(
            protected IContainer $container,
            protected string $namespace,
            protected array $args = []
        ) { }

        /**
         * @throws \ReflectionException
         */
        public function getInstance(): object {
            if ($this->container->has($this->namespace)) {
                $binding = $this->container->get($this->namespace);

                if (is_object($binding)) {
                    return $binding;
                }

                $this->namespace = $binding;
            }

            $refClass = new ReflectionClass($this->namespace);

            $constructor = $refClass->getConstructor();

            if ($constructor && $constructor->isPublic()) {
                if (count($constructor->getParameters()) > 0) {
                    $argumentResolver = new ParamsResolver(
                        $this->container,
                        $constructor->getParameters(),
                        $this->args
                    );

                    $this->args = $argumentResolver->getArguments();
                }

                return $refClass->newInstanceArgs($this->args);
            }

            return $refClass->newInstanceWithoutConstructor();
        }
    }

?>