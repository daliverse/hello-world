<?php

namespace Uie;
use Symfony\Component\Dotenv\Dotenv;


/************************************************************************
 *
 *    Description:  Base class is generally inherited by all classes,
 *    and has the following.
 *    - environment functionality
 *    - debugging flags/debugging functions
 *    - logging and error functionality
 *    - state (hasError, etc.)
 *    - error catching that is affected by environment, debugging, and logging settings
 *
 *    Many smaller classes need not inherit tbe base class.  They may use its
 *    functionality via another class such an Application class that already
 *    inherits from the base class.  For example, the User class will generally
 *    have an Application property which has the a UIEApplication object in it,
 *    so it might be referenced via User->Application->IsProduction() or
 *    User->Application->EventLog()
 *
 ************************************************************************/
abstract class Base {
    const ENVIRONMENT_DEVELOPMENT = 'DEVELOPMENT';
    const ENVIRONMENT_STAGING     = 'STAGING';
    const ENVIRONMENT_PRODUCTION  = 'PRODUCTION';

    protected $environment;
    protected $debug = false;

    #
    ##
    /** @var  Dotenv $dotEnv */
    protected $dotEnv;


    public function __construct() {
        #$this->initDotEnv();
        $this->setEnvironmentType();
        $this->setErrorHandling();
    }

    #
    ##  Environment Functionality
    public function setEnvironmentType($environment = null) {
        $isValid           = static::environmentTypeIsValid($environment);
        $this->environment = $isValid ? $environment : $this->deriveEnvironmentTypeFromEnvironment();
    }
    public function getEnvironmentType() {
        return $this->environment;
    }
    public function isDevelopment() {
        return $this->environment == $this::ENVIRONMENT_DEVELOPMENT;
    }
    public function isStaging() {
        return $this->environment == $this::ENVIRONMENT_STAGING;
    }
    public function isProduction() {
        return $this->environment == $this::ENVIRONMENT_PRODUCTION;
    }
    
    #
    ##  Error Handling
    private final function setErrorHandling() {
        if ($this->isDevelopment()) {
            ini_set('display_errors', '1');
            error_reporting(E_ALL);
            ini_set('display_startup_errors', 1);
            if (class_exists('FB')) {
                \FB::setEnabled(true);
            }
        } else {
            ini_set('display_errors', '0');
            error_reporting(0);
            ini_set('display_startup_errors', 1);
            if (class_exists('FB')) {
                \FB::setEnabled($this->isDebug());
            }
        }

    }

    #
    ##  Debugging Functionality
    public function setDebug($debug) {
        $this->debug = $debug;
    }
    public function isDebug() { return $this->debug; }

    #
    ##  Secret environment stuff
    private function initDotEnv() {
        $this->DotEnv = new Dotenv();
        $this->DotEnv->load('/home/shared/.env', __DIR__ . '/.env');
    }
    /**
     * Get the Environment Type based on the environment settings/variables
     * @return string
     */
    private function deriveEnvironmentTypeFromEnvironment() {
        if ($this->dotEnv != null && $this->dotEnv->getenv('UieEnvironment')) {  // derive from env settings
            return $this->_deriveEnvTypeFromDotEnv();
        } else {
            return $this->_deriveEnvTypeFromServer();

        }
    }
    /**
     * Check to see if an environment type is valid
     * @param $environment
     * @return bool
     */
    protected static function environmentTypeIsValid($environment) {
        return in_array($environment,
                        [
                          static::ENVIRONMENT_DEVELOPMENT,
                          static::ENVIRONMENT_STAGING,
                          static::ENVIRONMENT_PRODUCTION
                        ]);
    }
    /**
     * @return string
     */
    private function _deriveEnvTypeFromServer() {
// this should be defined in server apache environment variables
        $endPos    = strpos($_SERVER['SERVER_NAME'], ".");
        $envString = substr($_SERVER['SERVER_NAME'], 0, $endPos + 1);

        if ($envString == 'd.' || $envString == 'dev.' || $envString == 'devel.') {
            return Base::ENVIRONMENT_DEVELOPMENT;
        } elseif ($envString == 'stage.' || $envString == 'staging.') {
            return $this::ENVIRONMENT_STAGING;
        } else {
            return $this::ENVIRONMENT_PRODUCTION;
        }
    }
    /**
     * @return string
     */
    private function _deriveEnvTypeFromDotEnv() {
        $env = $this->dotEnv->getenv('UieEnvironment');
        if ($env == Base::ENVIRONMENT_DEVELOPMENT) {
            return $this::ENVIRONMENT_DEVELOPMENT;
        } elseif ($env == Base::ENVIRONMENT_STAGING) {
            return $this::ENVIRONMENT_STAGING;
        } else {
            return $this::ENVIRONMENT_PRODUCTION;
        }
    }
}
?>
