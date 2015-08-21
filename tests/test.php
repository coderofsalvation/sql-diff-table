<?php 

require_once __DIR__ . '/../src/SQLTableDiff.php'; // Autoload files using Composer autoload
require_once __DIR__ . '/vendor/autoload.php'; // Autoload files using Composer autoload

use coderofsalvation\SQLTableDiff;
use Zend\Db\Adapter\Adapter;

if( !getenv("MYSQL_PASS") ) die("Usage: MYSQL_PASS='sdfsdf' php tests/php");

/*
 * fake data
 */
$productsMagento = array(
  array( "stock"=> 2, "sku" => "1lk3", "title" => "foo 1", "color" => "#F00" ),
  array( "stock"=> 2, "sku" => "2lk3", "title" => "foo 2", "color" => "#F10" ),
  array( "stock"=> 2, "sku" => "3lk3", "title" => "foo 3", "color" => "#F20" ),
  array( "stock"=> 2, "sku" => "4lk3", "title" => "foo 4", "color" => "#F30" ),
  array( "stock"=> 2, "sku" => "5lk3", "title" => "foo 5", "color" => "#F40" )
);

$productsImport = $productsMagento;
$productsImport[1]['stock']++;
$productsImport[3]['stock']++;
$productsImport[4]['title']="Im changed";
$productsImport[2]['color']="#000";

/*
 * create db 
 */

$dbConn = new Zend\Db\Adapter\Adapter(array(
  'driver'   => 'pdo_mysql',
  'database' => "test",
  'username' => "root",
  'password' => getenv("MYSQL_PASS")
));

/*
 * create two tables in mysql 
 */

// 2 db handlers since some frameworks use a separate read- and write-handler (magento)
$sm = new SQLTableDiff( "sync_products_magento", $dbConn, $dbConn );
$si = new SQLTableDiff( "sync_products_import",  $dbConn, $dbConn );

$sm->createTableFromFields( array_keys($productsImport[0]) );
$si->createTableFromFields( array_keys($productsImport[0]) );
foreach( $productsMagento as $p ) $sm->addRow( $p );
foreach( $productsImport  as $p ) $si->addRow( $p );

/*
 * get products which have similar ids but different stock
 */

$filter = (object)array(
  'tableA' => "sync_products_magento",
  'tableB' => "sync_products_import",
  'id'     => "sku",
  'fields' => array("stock")
);

$result = $sm->diff( $filter);
foreach ( $result as $k => $row ){
  if( getenv("DEBUG") ) print_r($row);
  if( $k == 0 &&  $row['sku'] != '2lk3' ) throw new Exception("TEST_FAIL");
  if( $k == 1 &&  $row['sku'] != '4lk3' ) throw new Exception("TEST_FAIL");
} 

/*
 * get products which have similar ids but different details
 */

$filter = (object)array(
  'tableA' => "sync_products_magento",
  'tableB' => "sync_products_import",
  'id'     => "sku",
  'fields' => array("title","color")
);

$result = $sm->diff( $filter);
foreach ( $result as $k => $row ){
  if( getenv("DEBUG") ) print_r($row);
  if( $k == 0 &&  $row['sku'] != '3lk3' ) throw new Exception("TEST_FAIL");
  if( $k == 1 &&  $row['sku'] != '5lk3' ) throw new Exception("TEST_FAIL");
} 

?>
