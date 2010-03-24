<?php
/**
 * Configuration
 * @package repose
 */

require_once('repose_Mapping.php');
require_once('repose_PdoEngine.php');

/**
 * Configuration
 * @package repose
 */
class repose_Configuration {

    /**
     * Engine
     * @var repose_IEngine
     */
    private $engine;

    /**
     * Mapping
     * @var repose_Mapping
     */
    private $mapping;

    /**
     * Data source configuration
     *
     * Useful for lazy loading data source.
     *
     * @var array
     */
    private $dataSourceConfig = array(
        'dsn' => null,
        'username' => null,
        'password' => null,
        'driver' => null,
    );

    /**
     * Autoload callback
     * @var callback
     */
    protected $autoload;

    /**
     * Constructor
     * @param array $config Configuration
     */
    public function __construct($config) {

        print_r($config);

        $this->mapping = new repose_Mapping();

        foreach ( $config['classes'] as $clazz => $clazzConfig ) {
            $this->mapping->mapClass(
                $clazz,
                $clazzConfig['tableName'],
                $clazzConfig['properties']
            );
        }

        print_r($config);
        if ( isset($config['connection']['dataSource']) ) {

            // Things are very easy if a data source was specified
            // in the configuration.

            $this->engine = new repose_PdoEngine(
                $config['connection']['dataSource']
            );

            // TODO Get config information and store in $this->dataSourceConfig

        } elseif ( isset($config['connection']['dsn']) ) {

            print " [ AAAAA ]\n";

            // 
            // If a DSN is specified, things are also easy, but we still
            // try to populate $this->dataSourceConfig if we can.
            //

            foreach ( array('dsn', 'username', 'password') as $dataSourceConfigKey ) {
                if ( isset($config['connection'][$dataSourceConfigKey]) ) {
                    $this->dataSourceConfig[$dataSourceConfigKey] = $config['connection'][$dataSourceConfigKey];
                }
            }

            $dsnParts = explode(':', $this->dataSourceConfig['dsn']);

            if ( count($dsnParts) ) {
                $this->dataSourceConfig['driver'] = $dsnParts[0];
            }

        } else {

            //
            // Otherwise, we assume that we were passed a driver and the
            // rest of the connection information.
            //

            switch($config['connection']['driver']) {

                case 'sqlite':

                    // sqlite driver has a setup that is a little different
                    // than most(?) of the other drivers.
                    $this->dataSourceConfig['dsn'] = 'sqlite:' . $config['connection']['filename'];
                    $this->dataSourceConfig['driver'] = 'sqlite';

                    break;

                default:

                    // This is the default case, and it works for MySQL
                    // and it is assumed/hoped it will work for other
                    // RDBMS as well. We can add additional case statements
                    // if needed or additional logic in this branch to
                    // account for things as they come up.

                    foreach ( array('username', 'password', 'driver') as $dataSourceConfigKey ) {
                        $this->dataSourceConfig[$dataSourceConfigKey] =
                            isset($config['connection'][$dataSourceConfigKey]) ?
                                $config['connection'][$dataSourceConfigKey] :
                                null;
                    }

                    $this->dataSourceConfig['dsn'] = sprintf(
                        '%s:dbname=%s;host=%s',
                        isset($config['connection']['driver']) ?
                            $config['connection']['driver'] : 'mysql',
                        isset($config['connection']['dbName']) ?
                            $config['connection']['dbName'] : null,
                        isset($config['connection']['hostname']) ?
                            $config['connection']['hostname'] : null
                    );

                    break;

            }

        }

        if ( isset($config['autoload']) ) {
            $this->autoload = $config['autoload'];
        }

    }

    /**
     * Engine
     * @return repose_IEngine
     */
    public function engine() {

        print " [ REQUESTING ENGINE ]\n";

        if ( $this->engine === null ) {

            //
            // Attempt to lazy load a PDO Engine.
            //

            $dataSource = new PDO(
                $this->dataSourceConfig['dsn'],
                $this->dataSourceConfig['username'],
                $this->dataSourceConfig['password']
            );

            $dataSource->setAttribute(
                PDO::ATTR_ERRMODE,
                PDO::ERRMODE_EXCEPTION
            );

            print_r($this->dataSourceConfig);

            $this->engine = new repose_PdoEngine($dataSource);

        }

        return $this->engine;

    }

    /**
     * Mapping
     * @return repose_Mapping
     */
    public function mapping() {
        return $this->mapping;
    }

}

?>
