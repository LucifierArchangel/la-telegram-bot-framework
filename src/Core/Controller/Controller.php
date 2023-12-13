<?php

    namespace Lucifier\Framework\Core\Controller;

    use Lucifier\Framework\Core\IoC\Container;
    use Lucifier\Framework\Utils\Logger\FileLogger;
    use ReflectionException;

    class Controller {
        /**
         * Call view class show method
         *
         * @param string $view        view name
         * @param array $parametes    view's parameters for applying
         * @return void
         * @throws ReflectionException
         */
        public function view(string $view, array $parameters=[]): void {
            $instance = Container::instance();

            $viewInstance = $instance->resolve($view, $parameters);

            $instance->resolveMethod($viewInstance, 'show', $parameters);
        }
    }

?>