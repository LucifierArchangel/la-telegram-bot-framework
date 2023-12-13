<?php

    namespace Lucifier\Framework\Core\IoC;


    use ReflectionMethod;
    use ReflectionParameter;

    class MethodResolver {
        public function __construct(
            protected IContainer $container,
            protected object $instance,
            protected string $method,
            protected array $args = []
        ) { }

        /**
         * @throws \ReflectionException
         */
        public function getValue() {
            $method = new ReflectionMethod($this->instance, $this->method);

            $argumentResolver = new ParamsResolver($this->container, $method->getParameters(), $this->args);

            return $method->invokeArgs($this->instance, $argumentResolver->getArguments());
        }
    }

?>