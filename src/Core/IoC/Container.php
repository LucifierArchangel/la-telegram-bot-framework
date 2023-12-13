<?php

    namespace Lucifier\Framework\Core\IoC;

    use _PHPStan_79aa371a9\Nette\Neon\Exception;

    class Container implements IContainer {
        private static $instance;
        protected array $bindings = [];

        public static function instance(): self {
            if (!isset(self::$instance)) {
                self::$instance = new self;
            }

            return self::$instance;
        }

        public function bind(string $id, string $namespace): Container {
            $this->bindings[$id] = $namespace;

            return $this;
        }

        public function singleton(string $id, object $instance) {
            $this->bindings[$id] = $instance;

            return $this;
        }

        /**
         * @throws Exception
         */
        public function get($id){
            if ($this->has($id)) {
                return $this->bindings[$id];
            }

            throw new Exception("Container entry not found for: {$id}");
        }

        public function has($id): bool {
            return array_key_exists($id, $this->bindings);
        }

        /**
         * @throws \ReflectionException
         */
        public function resolve(string $namespace, array $args = []): object {
            return (new ClassResolver($this, $namespace,$args))->getInstance();
        }

        public function resolveMethod(object $instance, string $method, array $args = []) {
            return (new MethodResolver($this, $instance, $method, $args))->getValue();
        }
    }

?>