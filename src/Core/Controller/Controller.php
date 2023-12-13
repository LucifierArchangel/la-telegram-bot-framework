<?php

    namespace Lucifier\Framework\Core\Controller;

    use Lucifier\Framework\Core\IoC\Container;
    use Lucifier\Framework\Utils\Logger\FileLogger;
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
            $instance = Container::instance();

            $viewInstance = $instance->resolve($view, $parametes);

            $instance->resolveMethod($viewInstance, 'show', $parametes);
        }
    }

?>