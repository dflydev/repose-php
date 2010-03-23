<?php
require_once 'PHPUnit/Framework.php';
class AllTests {
    public static $testClassNames = array(
        'ReposeBasicTest'
    );
    public static function suite() {
        $suite = new PHPUnit_Framework_TestSuite('Repose');
        foreach ( self::$testClassNames as $testClassName ) {
            require_once($testClassName . '.php');
            $suite->addTestSuite($testClassName);
        }
        return $suite;
    }
}
?>
