<?php
/********************************************************************
 * Description: This is the primary database interface class. Provides a connection
 *                and basic database interaction functions.
 * Parameters:    $connectionInfo - array of strings:
 *                    This parameter is built as an array allowing for an override of all or
 *                    any subset of variables. Usage looks like this:
 *                    new Database(array("db"=>$db, "server"=>xxx.xx.xxx));
 * Returns:        An object class.
 ********************************************************************/

date_default_timezone_set('UTC');

class mysql extends aDatabase {

    private $dsn;
    private $connectionInfo
                             = array(
            'db'     => '',
            'server' => 'localhost',
            'dbUser' => '',
            'dbPass' => ''
        );
    private $parameters      = array();
    private $fetchMode       = PDO::FETCH_BOTH;
    private $objToLoad       = null;
    private $ctor_args;
    private $columnToFetch;
    public  $recordCount     = 0;
    public  $lastInsertID;
    public  $statement;
    public  $results;
    public  $columnNames     = array();
    public  $columnTypes     = array();
    public  $transactionMode = false;
    private $errorMode       = PDO::ERRMODE_SILENT;
    private $execution_status;


    /**
     *  The fetch types listed are not an exhaustive list,
     *  See PHP man pages for all fetch types
     *
     *  PDO::FETCH_ASSOC
     *      return queries indexed by column name
     *  PDO::FETCH_NUM
     *      return queries indexed by 0-indexed column number
     *  PDO::FETCH_BOTH
     *      return queries indexed by column name and 0-indexed column number
     */
    public function setFetchMode($fetch_mode) {
        $this->fetchMode = $fetch_mode;
    }

    public function getFetchMode() {
        return $this->fetchMode;
    }

    public function setObjToLoad($object) {
        $this->objToLoad = $object;
    }

    public function getObjToLoad() {
        return $this->objToLoad;
    }

    public function setCtor_args($ctor_args) {
        $this->ctor_args = $ctor_args;
    }

    public function getCtor_args() {
        return $this->ctor_args;
    }

    public function setColumnToFetch($column_to_fetch) {
        $this->columnToFetch = $column_to_fetch;
    }

    public function getColumnToFetch() {
        return $this->columnToFetch;
    }

    public function setErrorMode($errorMode) {
        $this->errorMode = $errorMode;
        $this->dsn->setAttribute(PDO::ATTR_ERRMODE, $this->errorMode);
    }

    public function getErrorMode() {
        return $this->errorMode;
    }

    private function setStatement($value) {
        $this->statement = $value;
    }

    public function getStatement() {
        return $this->statement;
    }

    public function setResults($value) {
        $this->results = $value;
    }

    public function getResults() {
        return $this->results;
    }

    public function setLastInsertID($lastInsertID) {
        $this->LastInsertID = $lastInsertID;
    }

    public function getLastInsertID() {
        return $this->LastInsertID;
    }

    private function setExecutionStatus($value) {
        $this->execution_status = $value;
    }

    public function getExecutionStatus() {
        return $this->execution_status;
    }

    function __construct() {
        $args = func_get_args();

        if (count($args) > 0 && !is_null($args[0])) {
            foreach (array_shift($args) as $key => $value) {
                if (array_key_exists($key, $this->connectionInfo)) {
                    $this->connectionInfo[$key] = $value;
                }
            }
        }
        $args = $this->connectionInfo;
        FB::log($this, "database connection:mysql");
        return call_user_func_array(array($this, '__constructDB'), $args);
    }

    function __constructDB($db, $server, $username, $password) {
        $this->connectionInfo['server'] = $server;
        $this->connectionInfo['db']     = $db;
        $this->connectionInfo['dbUser'] = $username;
        $this->connectionInfo['dbPass'] = $password;
        try {
            $this->dsn = new PDO("mysql:host=" . $this->connectionInfo['server'] . ";dbname=" . $this->connectionInfo['db'], $this->connectionInfo['dbUser'], $this->connectionInfo['dbPass']);
            $this->dsn->setAttribute(PDO::ATTR_ERRMODE, $this->errorMode);
        } catch (PDOException $e) {
            die('Connection to MySQL failed: ' . $e->getMessage());
        }
    }

    function query($query, $error_stop_flag = null) {
        try {
            $statement = $this->dsn->prepare($query);
            if (count($this->parameters) > 0) {
                for ($i = 0; $i < count($this->parameters); $i++) {
                    $statement->bindValue(
                        $this->parameters[$i]['name'],
                        $this->parameters[$i]['value'],
                        $this->parameters[$i]['type']);
                }
            }
            $this->setExecutionStatus($statement->execute());
        } catch (PDOException $e) {
            class_exists('FB') ? FB::error($e) : "";
            $error = array("error" => $e->getMessage(), "error_code" => $e->getCode());

            return $error;
        }
        $this->setStatement($statement);

        switch ($this->fetchMode) {
            case PDO::FETCH_OBJ:
                $this->statement->setFetchMode($this->fetchMode);
                break;

            case PDO::FETCH_INTO:
                $this->statement->setFetchMode($this->fetchMode, $this->objToLoad);
                break;

            case PDO::FETCH_COLUMN:
                $this->statement->setFetchMode($this->fetchMode, $this->columnToFetch);
                break;

            case PDO::FETCH_CLASS:
                $this->statement->setFetchMode($this->fetchMode, $this->objToLoad, $this->ctor_args);
                break;
            case PDO::FETCH_ASSOC:
                $this->statement->setFetchMode($this->fetchMode);
            default:
                $this->statement->setFetchMode($this->fetchMode);
                break;
        }

        /* if ((strpos(strtolower($query), 'insert') !== false) || (strpos(strtolower($query), 'update') !== false)) {
            $this->lastInsertID = $this->dsn->lastInsertId();
            $this->results = $this->lastInsertID;
        } else {
            $this->results = $statement->fetchAll();
        } */
        try {
            $this->results = $statement->fetchAll();
        } catch (Exception $e) {
            $this->lastInsertID = $this->dsn->lastInsertId();
            $this->results      = $this->lastInsertID;
        }
        $this->recordCount = count($this->results);
        //method_exists("fb", "log")?fb::log($this, "MySQLConn2"):"";
        return $this->results;
    }

    function getColumns() {
        if (count($this->columnNames) == 0) {
            for ($i = 0; $i < $this->statement->columnCount(); $i++) {
                $col                             = $this->statement->getColumnMeta($i);
                $this->columnNames[]             = $col['name'];
                $this->columnTypes[$col['name']] = $col['native_type'];
            }
        }
        return $this->columnNames;
    }

    public function setTransactionON() {
        $this->transactionMode = true;
        $this->dsn->beginTransaction();
    }

    public function commit() {
        return $this->dsn->commit();
    }

    public function rollback() {
        return $this->dsn->rollback();
    }

    function insertParam($name, $value, $type) {
        switch ($type) {
            case 'varchar':
                $type = PDO::PARAM_STR;
                break;
            case 'int':
                $type = PDO::PARAM_INT;
                break;
            case 'bool':
                $type = PDO::PARAM_BOOL;
                break;
            case 'null':
                $type = PDO::PARAM_NULL;
                break;
        }
        $array = array(
            'name'  => $name,
            'value' => $value,
            'type'  => $type
        );
        array_push($this->parameters, $array);
    }

    function insertParamList($array, $type) {
        switch ($type) {
            case 'varchar':
                $type = PDO::PARAM_STR;
                break;
            case 'int':
                $type = PDO::PARAM_INT;
                break;
            case 'bool':
                $type = PDO::PARAM_BOOL;
                break;
            case 'null':
                $type = PDO::PARAM_NULL;
                break;
        }
        foreach ($array as $k => $value) {
            array_push($this->parameters, array(
                    'name'  => ($k + 1),
                    'value' => $value,
                    'type'  => $type
                )
            );
        }
        return implode(',', array_fill(0, count($array), '?'));
    }

    /*
     *  pass in array of the form
     *  [
     *      [
     *          'name' => SQL field name
     *          'value' => SQL field value
     *          'type' => SQL field type
     *      ],
     *      .
     *      .
     *      .
     *  ]
     */
    function insertParamArray($array) {
        foreach ($array as $key => $param) {
            $name  = $param['name'];
            $value = $param['value'];
            $type  = $param['type'];

            $this->insertParam($name, $value, $type);
        }
    }

    function getAll() {
        return $this->getResults();
    }

    function getRow($style = NULL) {
        //NOT WORKING
        //AFTER FETCHALL() POINTER IS NOT RESET FOR FETCH()
        //NEED TO TAKE RESULTS AND GET FROM ARRAY
        switch ($style) {
            case 'varchar':
                $type = PDO::FETCH_ASSOC;
                break;
            case 'int':
                $type = PDO::PARAM_INT;
                break;
            case 'bool':
                $type = PDO::PARAM_BOOL;
                break;
            case 'null':
                $type = PDO::PARAM_NULL;
                break;
            default:
                $type = PDO::FETCH_BOTH;
                break;
        }

        $results = $this->statement->fetch($type);
        return $results;
    }

    function __toString() {
        $startrow   = 0;
        $endrow     = -1;
        $columns    = 0;
        $showHeader = true;
        $dateformat = 'm/d/y';
        $output     = "";
        if ($startrow > $endrow) {
            $endrow = $this->recordCount;
        }
        $this->getColumns();
        if ($columns == 0) {
            $columns = $this->columnNames;
        }
        $output .= '<table>';

        if ($showHeader) {
            $output .= '<tr>';
            foreach ($columns as $innerValue) {
                $output .= '<th>';
                $output .= $innerValue;
                $output .= '</th>';
            }
            $output .= '</tr>';
        }
        foreach ($this->results as $key => $value) {
            if ($key < $startrow || $key > $endrow) continue;
            $output .= '<tr>';
            foreach ($columns as $innerValue) {
                $output .= '<td>';
                if ($this->columnTypes[$innerValue] == 'datetime' && $value[$innerValue] != '') {
                    $output .= date($dateformat, strtotime($value[$innerValue]));
                } else {
                    $output .= htmlspecialchars($value[$innerValue]);
                }
                $output .= '</td>';
            }
            $output .= '</tr>';
        }
        $output .= '</table>';

        return $output;
    }

    function cleanup() {
        unset($this->parameters);
        $this->statement->nextRowset();
        $this->parameters       = array();
        $this->recordCount      = 0;
        $this->results          = null;
        $this->statement        = null;
        $this->objToLoad        = null;
        $this->fetchMode        = PDO::FETCH_BOTH;
        $this->execution_status = null;
        unset($this->columnNames);
        $this->columnNames = array();
        unset($this->columnTypes);
        $this->columnTypes = array();
    }

    function close() {
        unset($this->dbSource);
    }

}

?>
