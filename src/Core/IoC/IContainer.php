<?php

    namespace Lucifier\Framework\Core\IoC;

    interface IContainer {
        public function get($id);
        public function has($id): bool;
    }

?>