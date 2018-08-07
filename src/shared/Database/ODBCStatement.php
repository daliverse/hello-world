<?php

/**
 * User: spwashi2
 * Date: 7/19/2016
 * Time: 4:59 PM
 */
class ODBCStatement extends aPDOStatement {
    /** @var  int fetch_mode used for the fetch* functions */
    private $fetch_mode;
    /** @var  mixed|string $fetch_receptacle fetch receptacle used for the fetch_mode */
    private $fetch_receptacle;
    /** @var  mixed ctor_args used for the fetch mode */
    private $ctor_args;
    /** @var  int column number used for the fetch_mode */
    private $colno;
    /** @var  bool Whether or not the query was successful */
    private $success;
    /** @var array A cache array of the results that we've already fetched */
    private $results = null;
    /** @var bool Whether or not we've iterated through all of the results */
    private       $has_iterated_to_end = false;
    public static $pdo_fetch_constants = [
      PDO::FETCH_ASSOC,
      PDO::FETCH_BOTH,
      PDO::FETCH_BOUND,
      PDO::FETCH_CLASS,
      PDO::FETCH_INTO,
      PDO::FETCH_LAZY,
      PDO::FETCH_NAMED,
      PDO::FETCH_NUM,
      PDO::FETCH_OBJ,
      null,
    ];
    public static $pdo_param_constants = [
      PDO::PARAM_BOOL,
      PDO::PARAM_NULL,
      PDO::PARAM_INT,
      PDO::PARAM_STR,
      PDO::PARAM_LOB,
      PDO::PARAM_STMT,
      PDO::PARAM_INPUT_OUTPUT,
    ];
    /**
     * I'm assuming that this is the result_id returned by odbc_exec as well
     * @var resource $stmt
     */
    private $stmt;
    /**@var resource $connection_id */
    private $connection_id;
    /** @var  mixed attributes set by the (get|set)Attribute function */
    private $attributes;
    /** @var string The string being used for the query */
    private $query_string = "";
    /** @var array Array of variables indexed by name with the format [value, type] */
    private $bound_variables = [];
    private $bound_columns   = [];
    /** @var array Variables that were marked as bound by the query string */
    private $query_bound_variables = [];
    private $cursor_offset         = 0;
    
    public function __construct($query_string, $odbc_connection_id) {
        #$this->stmt                  = $odbc_stmt;              #I believe this should be here, only not because of hiccups with prepared
        $this->query_string  = $query_string;
        $this->connection_id = $odbc_connection_id;
        #$this->query_bound_variables = $query_bound_variables;     #This should also be here if we iterate through the variables
    }
    public function get_stmt() {
        return $this->stmt;
    }
    public function set_stmt($stmt) {
        $this->stmt = $stmt;
        return $stmt;
    }
    
    #Not sure what the length attribute does
    public function bindParam($parameter, &$variable, $data_type = PDO::PARAM_STR, $length = null, $driver_options = null) {
        #Check to make sure that the data is formatted in the way that we expect it to be
        if (!isset($parameter) || !(is_string($parameter) || is_numeric($parameter)) || (!in_array($data_type, static::$pdo_param_constants))) return false;
        //Cast the variable to a string, I believe this is what the PDO class does by default
        $variable                          = (string)$variable;
        $this->bound_variables[$parameter] = [&$variable, $data_type];
        return true;
    }
    public function bindValue($parameter, $value, $data_type = PDO::PARAM_STR) {
        if (!isset($parameter) || !(is_string($parameter) || is_numeric($parameter)) || (!in_array($data_type, static::$pdo_param_constants))) return false;
        #remove colons
        $parameter = str_replace(':', '', $parameter);
        #Add to an array indexed by parameter name in the format [value, type]
        $this->bound_variables[$parameter] = [(string)$value, $data_type];
        return true;
    }
    
    /**
     * Get the bound variables of the statement in the way that the executor can understand it
     * Iterate through the bound variables and puts them in the order of the parameters in the actual query
     * @return array
     */
    public function get_bound_variables() {
        $bound_variables = [];
        //odbc doesn't support named parameters, only numerically indexed ones
        //We go through the query string, find the named parameters
        $query_bound_variables = $this->query_bound_variables;
#			FB::log($query_bound_variables, 'named_parameters');
        foreach ($query_bound_variables as $index => $parameter) {
            $_bv = isset($this->bound_variables[$parameter]) ? $this->bound_variables[$parameter] : false;
            if (!$_bv) {
                $parameter_name = (string)$parameter;
                throw new PDOException("SQLSTATE[HY093]: Invalid parameter number: parameter {$parameter_name} was not defined", (int)'HY093');
            }
            
            $data_type = $_bv[1];
            $_bv_value = $_bv[0];
            switch ($data_type) {
                case PDO::PARAM_BOOL:
                    $value = !isset($_bv_value) ? null : (bool)$_bv_value ? 1 : 0;
                    break;
                case PDO::PARAM_NULL:
                    $value = null;
                    break;
                case PDO::PARAM_INT:
                    $value = (int)$_bv_value;
                    break;
                case PDO::PARAM_STR:
                    #todo todo todo todo todo todo todo: This probably is not adequate enough to prevent SQL injection
                    $_bv_value = ODBC::ms_escape_string($_bv_value);
                    $value     = "'{$_bv_value}'";
                    break;
                case PDO::PARAM_LOB:
                case PDO::PARAM_STMT:
                case PDO::PARAM_INPUT_OUTPUT:
                default:
                    //Not sure what the default value should be
                    $value = null;
                    break;
            }
            
            $bound_variables[] = $value;
        }
        return $bound_variables;
    }
    
    public function columnCount() {
        return odbc_num_fields($this->stmt);
    }
    public function errorCode() {
        return odbc_error($this->connection_id);
    }
    public function errorInfo() {
        return [
            //todo not sure what this returns
            odbc_error($this->connection_id),
            odbc_error($this->connection_id),
            odbc_errormsg($this->connection_id),
        ];
    }
    public function throw_error($extended_error_message = '') {
        $error = $this->errorInfo();
        if (!strlen($error[2])) {
            if (strlen($extended_error_message))
                FB::log([$error, $extended_error_message], ' - exception, odbcstatement');
            return;
        }
        $code        = (string)$error[0];
        $msg         = $error[2] . " - $extended_error_message ;";
        $error_msg   = "SQL Error (code {$code}): {$msg}";
        $error_class = substr($code, 0, 2);
        if (strcmp($error_class, '01') >= 0) {
            return true;
        } else {
            throw new PDOException($error_msg, (int)$error[0]);
        }
    }
    
    public function execute(array $input_parameters = null) {
        ob_start(); #Start output buffer to catch warnings, throw them as exceptions
        
        
        ######### EMULATES FUNCTIONALITY OF PDO::prepare() AND PDOStatement::execute()
        ## Because there is an issue with the SQL driver, parameters aren't supported. We have to get parameters sanitized and put in the string for security
        ## However, this fudges up the process a bit. We want to change the query before it's prepared, but it is prepared in a different class.
        ## So we moved that functionality here to maintain the flow of code. In most cases, this should be okay, but there might be situations where we need an odbc resource handle
        ## that doesn't exist because the statement has not yet been executed.
        
        #odbc doesn't support named parameters, only numerically indexed ones. We go through the query string, find the named parameters
        $matches = [];
        #Find all of the named parameters in the query
        preg_match_all('/[:]([a-zA-Z0-9_]+)/', $this->query_string, $matches);
        #An array of the named parameters sans-:
        $this->query_bound_variables = isset($matches[1]) ? $matches[1] : (isset($matches[0]) ? $matches[0] : []);
        #These are parameters to bind to the query. merged with bound variables
        $input_parameters = isset($input_parameters) ? $input_parameters : [];
        //combine the parameters of the function with the parameters that were previously bound
        $parameters = array_merge($input_parameters, $this->get_bound_variables());
        #Replace named parameters with question marks
        $statement = preg_replace('/[:][a-zA-Z0-9_]+/i', '?', $this->query_string);
        $count     = 0;    #Increment a counter for the bound_value replacement
        #replace the question marks with the bound variables. Not sure why two preg_replaces are used
        $statement = preg_replace_callback('/(\?)/', function ($match) use (&$parameters, &$count) {
            $replace = isset($parameters[$count]) ? $parameters[$count] : 'NULL';
            ++$count;
            return $replace;
        }, $statement);
        
        #
        $odbc_stmt = odbc_prepare($this->connection_id, $statement);
    
    
    
        if ($odbc_stmt) {
            $this->set_stmt($odbc_stmt);
            $this->success = odbc_execute($this->stmt, $parameters);
        }
        
        
        #If there was a warning, catch it a nd put it in a string
        $error = ob_get_clean();
        if (!$odbc_stmt) {
            $this->throw_error($error);
        }
        
        
        if (!$this->success) $this->results = false;
        #Throw error in the case od no success
        if (!$this->success) $this->throw_error('Not successful');
        return $this->success;
    }
    // todo yet to implement cursors
    public function fetch($fetch_style = PDO::FETCH_BOTH, $cursor_orientation = PDO::FETCH_ORI_NEXT, $cursor_offset = 0) {
        //not sure how to resolve this functionality.
        //This function relies on us having called PDOStatement::execute which has functionality more similar to odbc_execute,
        //but we are interacting with the results from what seems to be odbc_exec. The key thing here being the bound parameters
        //UPDATE::Looks like this has been resolved ^^. Still testing
        if (!isset($fetch_style)) $fetch_style = isset($this->fetch_mode) ? $this->fetch_mode : PDO::FETCH_BOTH;
        $result = false;
        if (!$this->success) return false;
        ob_start();
        switch ($fetch_style) {
            case (PDO::FETCH_NAMED):
                //todo
                //not sure how to emulate 1:1
            case (PDO::FETCH_ASSOC):
                $result = odbc_fetch_array($this->stmt);
                break;
            case (PDO::FETCH_BOTH):
                $result = odbc_fetch_array($this->stmt) ?: [];
                if (is_array($result))
                    $result = array_merge($result, array_values($result ?: []));
                break;
            case (PDO::FETCH_BOUND):
                //todo link with the bindColumn function
                return true;
                break;
            case (PDO::FETCH_CLASS):
                //todo use the splat operator to unpack the arguments instead of using a reflection class
                $reflect          = new ReflectionClass($this->fetch_receptacle);
                $fetch_receptacle = $reflect->newInstanceArgs($this->ctor_args ?: []);
            case (PDO::FETCH_INTO):
                $fetch_receptacle = isset($fetch_receptacle) ? $fetch_receptacle : $this->fetch_receptacle ?: new stdClass();
                $result           = [];
                odbc_fetch_into($this->stmt, $result);
                #Assume that the fetch_receptacle is either an object or an array
                $r_is_array = is_array($fetch_receptacle);
                foreach ($result as $index => $item) {
                    if ($r_is_array) $fetch_receptacle[$index] = $item;
                    else $fetch_receptacle->{$index} = $item;
                }
                $result = $fetch_receptacle;
                break;
            case (PDO::FETCH_LAZY):
                $obj    = new PDORow;
                $result = odbc_fetch_array($this->stmt);
                foreach ($result as $index => $item) {
                    $obj->{$index} = $item;
                }
                $result = $obj;
                break;
            case (PDO::FETCH_NUM):
                $result = odbc_fetch_array($this->stmt);
                if ($result)
                    $result = array_values($result);
                break;
            case (PDO::FETCH_OBJ):
                #todo, not 1:1
                $result = odbc_fetch_object($this->stmt);
                break;
        }
        $error = ob_get_clean();
        if (!$result) $this->throw_error($error);
        return $result;
    }
    /**
     * todo Can't use the bitwise OR operator yet. No grouping
     * @param int $fetch_style
     * @param null $fetch_argument
     * @param array $ctor_args
     * @return array|void
     */
    public function fetchAll($fetch_style = null, $fetch_argument = null, $ctor_args = []) {
        $result = [];
        if (!$this->success) return false;
        $num_rows = odbc_num_rows($this->stmt);
        if ($this->fetch_mode === false || !$num_rows) return [];
        
        if ($fetch_style === PDO::FETCH_COLUMN) {
            while ($_fetch_response = $this->fetchColumn($fetch_argument ?: 0)) {
                $result[] = $_fetch_response;
            }
            return $result;
        } else {
            $current_fetch = $this->_get_fetch_mode_complete();
            //Set the fetch mode temporarily in this case
            if ($fetch_style === PDO::FETCH_OBJ || $fetch_style === PDO::FETCH_CLASS) {
                $this->setFetchMode($fetch_style, $fetch_argument, $ctor_args);
            }
            $exception         = false;
            $count             = 0;
            $continue_fetching = true;
            while ($continue_fetching) {
                try {
                    $_fetch_response = $this->fetch($fetch_style);
                } catch (Exception $e) {
                    $exception         = $e;
                    $continue_fetching = false;
                    break;
                }
                if (!$_fetch_response) break;
                $result[] = $_fetch_response;
                $count++;
            }
            if (($num_rows != $count) && $exception) throw  $exception;
            $this->_set_fetch_mode_complete($current_fetch);
        }
        
        return $result;
    }
    
    /**
     * Get an array that represents the current fetch mode. Useful to dave the current fetch state then reset it later
     * @return array
     */
    private function _get_fetch_mode_complete() {
        $current_fetch = $this->fetch_mode;
        $current_cl    = $this->fetch_receptacle;
        $current_ctor  = $this->ctor_args;
        return [
          $current_fetch,
          $current_cl,
          $current_ctor,
        ];
    }
    /**
     * Set the fetch mode based on an array that captures what it previously was
     * @param $current_fetch
     */
    private function _set_fetch_mode_complete($current_fetch) {
        $this->setFetchMode($current_fetch[0], $current_fetch[1], $current_fetch[2]);
    }
    public function fetchObject($class_name = "stdClass", $ctor_args = null) {
        $current_fetch = $this->_get_fetch_mode_complete();
        $this->setFetchMode(PDO::FETCH_OBJ, $class_name, $ctor_args);
        $result = $this->fetch();
        $this->_set_fetch_mode_complete($current_fetch);
        
        return $result;
    }
    public function fetchColumn($column_number = 0) {
        $result = $this->fetch(PDO::FETCH_ASSOC);
        if (is_array($result)) {
            $keys = array_keys($result);
            if (isset($keys[$column_number])) return $result[$keys[$column_number]];
        }
        return false;
    }
    
    ##
    public function getAttribute($attribute) {
        return isset($this->attributes[$attribute]) ? $this->attributes[$attribute] : null;
    }
    public function getColumnMeta($column = 0) {
        #odbc_columns function?
        #odbc_field_type
        #todo pdo_type, flags, table
        return [
          'name'        => odbc_field_name($this->stmt, $column),
          'native_type' => odbc_field_type($this->stmt, $column),
          'precision'   => odbc_field_len($this->stmt, $column),
          'flags'       => null,
          'table'       => null,
          #What is the difference between this and precision
          'len'         => odbc_field_len($this->stmt, 0),
          'pdo_type'    => null,
        ];
    }
    
    #todo Not sure what the implications of this are
    public function setAttribute($attribute, $value) {
        #Seems a bit too straightforward, no?
        $this->attributes[$attribute] = $value;
        return true;
    }
    /**
     * @param int $mode
     * @param null $colno_classname_or_object
     * @param null $ctor_args
     * @return bool
     */
    public function setFetchMode($mode = null, $colno_classname_or_object = null, $ctor_args = null) {
        if (in_array($mode, static::$pdo_fetch_constants)) {
            $this->fetch_mode = isset($mode) ? $mode : PDO::FETCH_BOTH;
            if (is_string($colno_classname_or_object)) {
                $this->ctor_args        = is_array($ctor_args) ? $ctor_args : [$ctor_args];
                $this->fetch_receptacle = $colno_classname_or_object;
            } else if (is_object($colno_classname_or_object)) {
                $this->fetch_receptacle = $colno_classname_or_object;
            } else if ($mode === PDO::FETCH_COLUMN && is_numeric($colno_classname_or_object)) {
                $this->colno = $colno_classname_or_object;
            }
            return true;
        } else {
            FB::log($mode, "Cannot set Fetch Mode");
        }
        return false;
    }
    #Not sure if this is 1:1
    public function nextRowset() {
        return odbc_next_result($this->stmt);
    }
    public function rowCount() {
        return odbc_num_rows($this->stmt);
    }
/////////////////////////////////////////
/////////////////////////////////////////
    public function closeCursor() {
        // TODO: Implement closeCursor() method.
    }
    public function debugDumpParams() {
        // TODO: Implement debugDumpParams() method.
    }
    public function bindColumn($column, &$param, $type = null, $maxlen = null, $driverdata = null) {
        $this->bound_columns[$column] = &$param;
    }
/////////////////////////////////////////
/////////////////////////////////////////
//note: At the moment, some iterative behavior may not work the way you expect it to.
    public function current() {
        if (isset($this->results[$this->cursor_offset])) {
            return $this->results[$this->cursor_offset];
        }
        return $this->results[$this->cursor_offset] = $this->fetch();
    }
    public function next() {
        ++$this->cursor_offset;
    }
    public function key() {
        return $this->cursor_offset;
    }
    public function valid() {
        if ($this->has_iterated_to_end) return isset($this->results[$this->cursor_offset]);
        $rowCount   = $this->rowCount();
        $is_not_end = $this->cursor_offset < $rowCount;
        if (is_array($this->results) && count($this->results) == $rowCount) {
            $this->has_iterated_to_end = true;
        }
        return $is_not_end;
    }
    public function rewind() {
        $this->cursor_offset = 0;
    }
}
