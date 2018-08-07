<?php

/**
 * Created by PhpStorm.
 * User: spwashi2
 * Date: 7/20/2016
 * Time: 10:54 AM
 *
 * Meant to abstract the PDO class
 */
abstract class aPDO {
	/**
	 * aPDO constructor.
	 * @param string $dsn
	 * @param string $username
	 * @param string $password
	 * @param array  $options
	 */
	abstract public function __construct($dsn, $username, $password, $options = null);
	/**
	 * @return bool
	 */
	abstract public function beginTransaction();
	/**
	 * @return bool
	 */
	abstract public function commit();
	/**
	 * @return mixed
	 */
	abstract public function errorCode();
	/**
	 * @return mixed
	 */
	abstract public function errorInfo();
	/**
	 * @param string $statement
	 * @return mixed
	 */
	abstract public function exec($statement);
	/**
	 * @param int $attribute
	 * @return mixed
	 */
	abstract public function getAttribute($attribute);
	#abstract public static function getAvailableDrivers();
	/**
	 * @return bool
	 */
	abstract public function inTransaction();
	/**
	 * @param string $name
	 * @return mixed
	 */
	abstract public function lastInsertId($name = null);
	/**
	 * @param string $statement
	 * @param array  $driver_options
	 * @return aPDOStatement
	 */
	abstract public function prepare($statement, $driver_options = array());
	/**
	 * @param $statement
	 * @return mixed
	 */
	abstract public function query($statement);
	/**
	 * @param string $string
	 * @param int    $parameter_type
	 * @return mixed
	 */
	abstract public function quote($string, $parameter_type = PDO::PARAM_STR);
	/**
	 * @return bool
	 */
	abstract public function rollBack();
	/**
	 * @param $attribute
	 * @param $value
	 * @return bool
	 */
	abstract public function setAttribute($attribute, $value);
}