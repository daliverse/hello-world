<?php

namespace Uie\Database;

class ConnectionFactory {

	/**
	 * @param $connectionInfo
	 * @return Database|mssql|mysql|mysqli
	 * @throws Exception
	 */
	public static function build($connectionInfo){
		if(class_exists($connectionInfo['dbType'])){
			return new $connectionInfo['dbType']($connectionInfo);
		} else {
			$db_type = $connectionInfo['dbType'];
			throw new Exception("Invalid database type - {$db_type} -  given.");
		}
	}
}
?>