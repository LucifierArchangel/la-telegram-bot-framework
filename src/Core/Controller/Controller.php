<?php

    namespace Lucifier\Framework\Core\Controller;

    use Lucifier\Framework\Core\DIContainer\DIContainer;

    class Controller {
        protected $namespace;
        public function __construct($namespace) {
            $this->namespace = $namespace;
        }



        public function view($view, $parametes=[]) {
            $instance = DIContainer::instance();
            $instance->setNamespace($this->namespace."\\Views\\");

            $viewCallable = $view."@show";

            $instance->call($viewCallable, $parametes);
        }
    }

?>