<?php

require_once 'PHPUnit/Framework.php';

ini_set('error_reporting', E_ALL);

abstract class AbstractReposeTest extends PHPUnit_Framework_TestCase {
    
    public function loadClass($clazz) {
        if ( ! class_exists($clazz) ) {
            $filename = dirname(__FILE__) . '/model/' . $clazz . '.php';
            require_once($filename);
        }
    }

}

?>
