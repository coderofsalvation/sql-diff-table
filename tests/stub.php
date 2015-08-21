<? 
# fake it until you make it
# this file is a stub to present fake magento calls
# to easify development
include_once( dirname(__FILE__)."/vendor/autoload.php" );

use Zend\Db\Adapter\Adapter;

class Mage {

	static $res = false; 
	public static function getSingleton($i){
		if( self::$res == false) self::$res = new Connection();
		return self::$res;
	}
}
		

class Connection {
	public function getConnection($str){
		$pass = getenv('MYSQL_PW');
		if( ! strlen($pass) ) 						
						die("please pass MYSQL_PW=xxxxxxx as environment variable");
		
		$dbConn = new Zend\Db\Adapter\Adapter(array(
			'driver'   => 'pdo_mysql',
			'database' => "admin_ibs",
			'username' => "root",
			'password' => $pass 
		));
		return $dbConn; 
	} 
}




?>
