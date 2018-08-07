<?php

/**
 * User: spwashi2
 * Date: 7/19/2016
 * Time: 4:33 PM
 */
class ODBC extends aPDO {
    /**
     * @var resource $connection_id
     */
    private $connection_id;
    private $in_transaction = false;
    private $status;
    private $attributes     = [];
    /**
     * The PDO::ATTR_* constants as listed in the PDO::setAttribute documentation
     * The values listed are there if the possible values if they are discrete. Otherwise, 0 to mark that they are
     * generally open
     * @see http://php.net/manual/en/pdo.getattribute.php
     * @see http://php.net/manual/en/pdo.setattribute.php
     * @var array
     */
    protected static $pdo_attr_constants
      = [
        PDO::ATTR_AUTOCOMMIT               => [true, false],
        PDO::ATTR_CASE                     => [
          PDO::CASE_LOWER,
          PDO::CASE_NATURAL,
          PDO::CASE_UPPER
        ],
        PDO::ATTR_CLIENT_VERSION           => 0,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => 0,
        PDO::ATTR_EMULATE_PREPARES         => [true, false],
        PDO::ATTR_CONNECTION_STATUS        => 0,
        PDO::ATTR_DRIVER_NAME              => 0,
        PDO::ATTR_ERRMODE                  => [
          PDO::ERRMODE_SILENT,
          PDO::ERRMODE_WARNING,
          PDO::ERRMODE_EXCEPTION
        ],
        PDO::ATTR_STRINGIFY_FETCHES        => [true, false],
        PDO::ATTR_ORACLE_NULLS             => [
          PDO::NULL_NATURAL,
          PDO::NULL_EMPTY_STRING,
          PDO::NULL_TO_STRING
        ],
        PDO::ATTR_STATEMENT_CLASS          => 0,
        PDO::ATTR_PERSISTENT               => 0,
        PDO::ATTR_PREFETCH                 => 0,
        PDO::ATTR_SERVER_INFO              => 0,
        PDO::ATTR_SERVER_VERSION           => 0,
        PDO::ATTR_TIMEOUT                  => 0
      ];
    
    
    /**
     * Convert a string to the odbc_connect $dsn parameter's preferred format.
     * Not sure what the proper format is, documentation seems scarce.
     * May be some information at https://msdn.microsoft.com/en-us/library/ms811006.aspx
     * @todo - spwashi2-7/19/16
     *       --no lon
     * @param string $dsn_string The DSN string that will be processed
     * @return mixed
     */
    public static function convert_dsn_string($dsn_string) {
        return $dsn_string;
    }
    
    public function __construct($odbc_dsn, $username, $password, $options = null) {
        $this->connection_id = odbc_connect($odbc_dsn, $username, $password);
        if (!$this->connection_id) {
            $this->status = false;
            throw new PDOException("Could not configure ODBC with dsn {$odbc_dsn}");
        }
    }
    
    public function get_connection_id() {
        return $this->connection_id;
    }
    
    public function errorCode() {
        return odbc_error($this->connection_id);
    }
    public function errorInfo() {
        return [
            //todo not sure what this returns
            odbc_error($this->connection_id),
            odbc_error($this->connection_id),
            odbc_errormsg($this->connection_id)
        ];
    }
    ##
    public function exec($statement) {
        $PDOStatement = $this->prepare($statement, []);
        $PDOStatement->execute();
        return odbc_num_rows($PDOStatement->get_stmt());
    }
    public function getAttribute($attribute) {
        return isset($this->attributes[$attribute]) ? $this->attributes[$attribute] : null;
    }
    
    public function throw_error() {
        $error = $this->errorInfo();
        throw new PDOException($error[2], $error[0]);
    }
    
    public function prepare($statement, $driver_options = []) {
        $odbcStmt = new ODBCStatement($statement, $this->connection_id);
        return $odbcStmt;
    }
    /**
     * I believe this is a combination of the PDO::prepare and PDOStatement::execute functions.
     * @param string $statement Properly escaped SQL statement to prepare and execute
     * @param int $fetch_type
     * @param int|string|object $colno_classname_or_object
     * @param array|null $ctor_args
     * @return PDOStatement
     */
    public function query($statement, $fetch_type = null, $colno_classname_or_object = null, $ctor_args = null) {
        $PDOStatement = $this->prepare($statement, []);
        if (isset($fetch_type)) {
            $_fn_args = func_get_args();
            array_shift($_fn_args);
            #additional arguments are meant for the PDOStatement::setFetchMode function.
            call_user_func_array([$PDOStatement, "setFetchMode"], $_fn_args);
        }
        $PDOStatement->execute();
        return $PDOStatement;
    }
    #todo not sure what the implications of this are
    public function setAttribute($attribute, $value) {
        #only set attributes that we are familiar with
        if (isset(static::$pdo_attr_constants[$attribute])) {
            #If there is a discrete set of values the constant can take on, only allow that
            if (is_array(static::$pdo_attr_constants) && !in_array($value, static::$pdo_attr_constants)) return false;
            $this->attributes[$attribute] = $value;
            return true;
        }
        return false;
    }
    public function rollBack() {
        $this->in_transaction = false;
        return odbc_rollback($this->connection_id);
    }
    public function beginTransaction() {
        return $this->in_transaction = odbc_autocommit($this->connection_id, false);
    }
    public function commit() {
        $result               = odbc_commit($this->connection_id);
        $this->in_transaction = !$result;
        return $result;
    }
    public function inTransaction() {
        return $this->in_transaction;
    }
    public function lastInsertId($name = null) {
        #It looks like this is not supported
        return false;
    }
    public function quote($string, $parameter_type = PDO::PARAM_STR) {
        //todo this is incomplete?
        return static::ms_escape_string($string);
    }
    /**
     * Escape the input? Not sure if this is the best solution
     * Based  (snatched) off of a StackOverflow post
     * @link http://stackoverflow.com/a/2526717
     * @param $data
     * @return mixed|string
     */
    public static function ms_escape_string($data) {
        if (!isset($data) or empty($data)) return '';
        if (is_numeric($data)) return $data;
        
        $non_displayables = [
          '/%0[0-8bcef]/',            // url encoded 00-08, 11, 12, 14, 15
          '/%1[0-9a-f]/',             // url encoded 16-31
          '/[\x00-\x08]/',            // 00-08
          '/\x0b/',                   // 11
          '/\x0c/',                   // 12
          '/[\x0e-\x1f]/'             // 14-31
        ];
        foreach ($non_displayables as $regex) {
            $data = preg_replace($regex, '', $data);
        }
        $data = str_replace("'", "''", $data);
        return $data;
    }
}