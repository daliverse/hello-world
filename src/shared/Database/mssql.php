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

class mssql {
	/**
	 * @var aPDO
	 */
	public $dsn;
	private $connectionInfo
		                 = [
			'db'     => 'UIExtension',
			'server' => 'd-db-w.itcs.uiuc.edu',
			'dbUser' => 'ExtensionWebUser',
			'dbPass' => 'Ca?R&st9sp#6',
		];
	private $parameters  = [];
	private $fetchMode   = PDO::FETCH_BOTH;
	private $objToLoad   = null;
	private $ctor_args;
	private $columnToFetch;
	public  $recordCount = 0;
	/**
	 * @var aPDOStatement $statement
	 */
	private $statement;
	public  $results;
	public  $columnNames = [];
	public  $columnTypes = [];
	private $execution_status;


	/**
	 *  The fetch types listed are not an exhaustive list,
	 *  See PHP man pages for all fetch types
	 *
	 ************* SET THIS TO FALSE IF YOU NEED TO DO A QUERY THAT ISN'T A SELECT
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
		return call_user_func_array([$this, '__constructDB'], $args);
	}

	function __constructDB($db, $server, $username, $password) {
		$this->connectionInfo['server'] = $server;
		$this->connectionInfo['db']     = $db;
		$this->connectionInfo['dbUser'] = $username;
		$this->connectionInfo['dbPass'] = $password;
    $dsn_string = "dblib:host=" . $this->connectionInfo['server'] . ";dbname=" . $this->connectionInfo['db'] . ";";
		$dsn_string = "Driver={ODBC Driver 13 for SQL Server};Server=$server;Database=$db;Client_CSet=UTF-8;";
		try {
			$this->dsn = new ODBC($dsn_string, $username, $password);
		} catch (Exception $e) {
			die('Connection to MSSQL failed: ' . $e->getMessage());
		}
		$this->dsn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}

	function insert($query, $error_stop_flag = null) {
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
	}

	/**
	 * @NOTE       !!!!!!   IF YOU ARE HERE TO ADDRESS THE ISSUE "NO TUPLES AVAILABLE...",
	 *                      CHECK TO SEE IF THE FETCH MODE HAS BEEN SET TO "FALSE".
	 *                      THE FETCH MODE MUST BE SET TO FALSE IN ORDER FOR THIS CLASS TO WORK
	 *                      WITH STATEMENTS THAT ARE NOT "SELECT" STATEMENTS
	 *
	 * @param      $query
	 * @param null $error_stop_flag
	 * @return array|void
	 */
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
            
            $execution_status = $statement->execute();
            
            
            $this->setExecutionStatus($execution_status);
		} catch (PDOException $e) {
			$error = ["error" => $e->getMessage()];
			return $error;
			if ($error_stop_flag === 1) {
				die();
			}
		}
        
        
        
        if ($this->fetchMode === false ||
		    strpos(strtolower($query), 'update') === 0 ||
		    strpos(strtolower($query), 'insert into') === 0 ||
		    strpos(strtolower($query), 'delete from') === 0) return null;
		
		
		$this->setStatement($statement);

		switch ($this->fetchMode) {
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
			case PDO::FETCH_OBJ:
			default:
				$this->statement->setFetchMode($this->fetchMode);
				break;
		}
		$this->results     = $statement->fetchAll();
		$this->recordCount = count($this->results);

		return [$this->results];
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
		$array = [
			'name'  => $name,
			'value' => $value,
			'type'  => $type,
		];
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
			array_push($this->parameters, [
				                            'name'  => ($k + 1),
				                            'value' => $value,
				                            'type'  => $type,
			                            ]
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

	function getRow($style = null) {
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

	function toTable($startrow = 0, $endrow = -1, $columns = 0, $showHeader = true, $dateformat = 'm/d/y') {
		if ($startrow > $endrow) {
			$endrow = $this->recordCount;
		}
		$this->getColumns();
		if ($columns == 0) {
			$columns = $this->columnNames;
		}
		echo '<table>';

		if ($showHeader) {
			echo '<tr>';
			foreach ($columns as $innerValue) {
				echo '<th>';
				echo $innerValue;
				echo '</th>';
			}
			echo '</tr>';
		}
		foreach ($this->results as $key => $value) {
			if ($key < $startrow || $key > $endrow) continue;
			echo '<tr>';
			foreach ($columns as $innerValue) {
				echo '<td>';
				if ($this->columnTypes[$innerValue] == 'datetime' && $value[$innerValue] != '') {
					echo date($dateformat, strtotime($value[$innerValue]));
				} else {
					echo htmlspecialchars($value[$innerValue]);
				}
				echo '</td>';
			}
			echo '</tr>';
		}
		echo '</table>';
	}

	function cleanup() {
		unset($this->parameters);
		$this->parameters       = [];
		$this->recordCount      = 0;
		$this->results          = null;
		$this->statement        = null;
		$this->objToLoad        = null;
		$this->fetchMode        = PDO::FETCH_BOTH;
		$this->execution_status = null;
		unset($this->columnNames);
		$this->columnNames = [];
		unset($this->columnTypes);
		$this->columnTypes = [];
	}

	function close() {
		unset($this->dbSource);
	}

}

?>
