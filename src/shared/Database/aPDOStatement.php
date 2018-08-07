<?php

/**
 * Created by PhpStorm.
 * User: spwashi2
 * Date: 7/19/2016
 * Time: 4:35 PM
 *
 * Meant to abstract the PDOStatement class
 *
 * @property-read string $queryString
 */
abstract class  aPDOStatement implements Iterator {

    /**
     * @param mixed $column
     * @param mixed $param
     * @param int   $type
     * @param int   $maxlen
     * @param mixed $driverdata
     * @return bool
     */
    abstract public function bindColumn($column, &$param, $type = null, $maxlen = null, $driverdata = null);

    /**
     * @param mixed $parameter
     * @param mixed $variable
     * @param int   $data_type
     * @param null  $length
     * @param null  $driver_options
     * @return bool
     */
    abstract public function bindParam($parameter, &$variable, $data_type = PDO::PARAM_STR, $length = null, $driver_options = null);

    /**
     * @param mixed $parameter
     * @param mixed $value
     * @param int   $data_type
     * @return bool
     */
    abstract public function bindValue($parameter, $value, $data_type = PDO::PARAM_STR);

    /**
     * @return bool
     */
    abstract public function closeCursor();

    /**
     * @return int
     */
    abstract public function columnCount();

    /**
     * @return void
     */
    abstract public function debugDumpParams();

    /**
     * @return string
     */
    abstract public function errorCode();

    /**
     * @return array
     */
    abstract public function errorInfo();

    /**
     * @param array $input_parameters
     * @return bool
     */
    abstract public function execute(array $input_parameters = null);

    /**
     * @param int $fetch_style
     * @param int $cursor_orientation
     * @param int $cursor_offset
     * @return mixed
     */
    abstract public function fetch($fetch_style = null, $cursor_orientation = PDO::FETCH_ORI_NEXT, $cursor_offset = 0);

    /**
     * @param int   $fetch_style
     * @param mixed $fetch_argument
     * @param array $ctor_args
     * @return array
     */
    abstract public function fetchAll($fetch_style, $fetch_argument, $ctor_args = []);

    /**
     * @param int $column_number
     * @return mixed
     */
    abstract public function fetchColumn($column_number = 0);

    /**
     * @param string     $class_name
     * @param array|null $ctor_args
     * @return mixed
     */
    abstract public function fetchObject($class_name = "stdClass", $ctor_args = null);

    /**
     * @param int $attribute
     * @return mixed
     */
    abstract public function getAttribute($attribute);

    /**
     * @param int $column
     * @return array
     */
    abstract public function getColumnMeta($column);

    /**
     * @return bool
     */
    abstract public function nextRowset();

    /**
     * @return int
     */
    abstract public function rowCount();

    /**
     * @param int   $attribute
     * @param mixed $value
     * @return bool
     */
    abstract public function setAttribute($attribute, $value);

    /**
     * @param int  $mode
     * @param null $colno_classname_or_object
     * @param null $ctor_args
     * @return bool
     */
    abstract public function setFetchMode($mode,$colno_classname_or_object = null, $ctor_args = null);

}