<?php

    namespace Lucifier\Framework\Core\Controller;

    use Lucifier\Framework\Core\DIContainer\DIContainer;
    use ReflectionException;

    class Controller {
        /**
         * @var string constroller's bot namespace
         */
        protected string $namespace;
        public function __construct($namespace) {
            $this->namespace = $namespace;
        }

        /**
         * Call view class show method
         *
         * @param string $view        view name
         * @param array $parametes    view's parameters for applying
         * @return void
         * @throws ReflectionException
         */
        public function view(string $view, array $parametes=[]): void {
            $instance = DIContainer::instance();
            $instance->setNamespace($this->namespace."\\Views\\");

            $viewCallable = $view."@show";

            $instance->call($viewCallable, $parametes);
        }
    }

?>