<?php

    namespace Lucifier\Framework\Core\DIContainer;

    use Exception;
    use ReflectionClass;
    use ReflectionException;
    use ReflectionMethod;
    use ReflectionNamedType;

    class DIContainer {
        protected static $instance;
        protected string $callbackClass;

        protected string $callbackMethod;

        protected string $methodSeparator = "@";
        protected string $namespace = "App\\BotRouter\\";

        public static function instance(): DIContainer {
            if(is_null(self::$instance)) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        public function setNamespace($namespace) {
            $this->namespace = $namespace;
        }

        /**
         * @throws ReflectionException
         */
        public function call($callable, $parameters = []) {
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

        public function resolveCallback($callable): void {
            $segments = explode($this->methodSeparator, $callable);

            $this->callbackClass = $this->namespace.$segments[0];
            $this->callbackMethod = $segments[1] ?? "__invoke";
        }

        /**
         * @throws ReflectionException
         */
        public function make($class, $parameters = []) {
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