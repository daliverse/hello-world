<?php
abstract class aDatabase extends \PDO{

	private $connectionInfo;
	public $errorMessage;

	public static function create($connectionInfo){
		$conn = ConnectionFactory::build($connectionInfo);

		return $conn;
	}

}



?>