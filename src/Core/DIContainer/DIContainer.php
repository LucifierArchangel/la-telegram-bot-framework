<?php

    namespace Lucifier\Framework\Core\DIContainer;

    use Exception;
    use ReflectionClass;
    use ReflectionException;
    use ReflectionMethod;
    use ReflectionNamedType;

    class DIContainer {
        /**
         * @var DIContainer|null current instance
         */
        protected static $instance;

        /**
         * @var string class name for calling
         */
        protected string $callbackClass;

        /**
         * @var string class method for calling
         */
        protected string $callbackMethod;

        /**
         * @var string separator for callable string
         */
        protected string $methodSeparator = "@";

        /**
         * @var string namespace for class finder
         */
        protected string $namespace = "App\\BotRouter\\";

        /**
         * Get current singleton instance
         *
         * @return DIContainer
         */
        public static function instance(): DIContainer {
            if(is_null(self::$instance)) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        /**
         * Set namespace
         *
         * @param string $namespace namespace
         * @return void
         */
        public function setNamespace(string $namespace): void {
            $this->namespace = $namespace;
        }

        /**
         * Call method by "ClassName@methodName" string
         *
         * @param string $callable   callable string
         * @param array $parameters  method parameters array
         * @return mixed
         * @throws ReflectionException
         */
        public function call(string $callable, array $parameters = []): mixed {
            $this->resolveCallback($callable);

            $methodReflection = new ReflectionMethod($this->callbackClass, $this->callbackMethod);
            $methodParams = $methodReflection->getParameters();

            $dependencies = [];

            foreach ($methodParams as $param) {
                $type = $param->getType();
                $name = $param->getName();

                if ($type && $type instanceof ReflectionNamedType) {
                    $reflectionInstance = new ReflectionClass($name);
                    $instance = $reflectionInstance->newInstance();

                    $dependencies[] = $instance;
                } else {
                    if (array_key_exists($name, $parameters)) {
                        $dependencies[] = $parameters[$name];
                    } else {
                        if (!$param->isOptional()) {
                            throw new Exception("Can not resolve parameter");
                        }
                    }
                }
            }

            $initClass = $this->make($this->callbackClass, $parameters);
            return $methodReflection->invoke($initClass, ...$dependencies);
        }

        /**
         * Resolve callable string
         *
         * @param string $callable callable string
         * @return void
         */
        public function resolveCallback(string $callable): void {
            $segments = explode($this->methodSeparator, $callable);

            $this->callbackClass = $this->namespace.$segments[0];
            $this->callbackMethod = $segments[1] ?? "__invoke";
        }

        /**
         * Make class instance for next method apply
         *
         * @param mixed $class        class for making instance
         * @param array $parameters   parameters for class constructor
         * @return object
         * @throws ReflectionException
         */
        public function make(mixed $class, array $parameters = []): object {
            $classReflection = new ReflectionClass($class);
            $constructorParams = $classReflection->getConstructor()->getParameters();

            $dependencies = [];

            foreach ($constructorParams as $param) {
                $type = $param->getType();

                $paramName = $param->getName();

                if ($type && $type instanceof ReflectionNamedType) {
                    $paramReflection = new ReflectionClass($paramName);
                    $paramInstance = $paramReflection->newInstance();

                    $dependencies[] = $paramInstance;
                } else {
                    if (array_key_exists($paramName, $parameters)) {
                        $dependencies[] = $parameters[$paramName];
                    } else {
                        if (!$param->isOptional()) {
                            throw new Exception("Can not resolve parametr");
                        }
                    }
                }
            }

            return $classReflection->newInstance(...$dependencies);
        }
    }

?>