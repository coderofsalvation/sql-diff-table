SQL Table Diff 
==============

<p align="center">
  <img alt="" width="250" src="http://www.gifbin.com/bin/042014/1396457382_peeling_apples_with_power_drill.gif"/>
</p>
Find differences between 2 similar SQL tables. Compare large collections of data fast and stylish.

## Usage 

    $ composer require coderofsalvation/sql-diff-table

And then 

    // 2 db handlers since some frameworks use a separate read- and write-handler 
    $a = new SQLTableDiff( "sync_products_A", $dbConn, $dbConn );
    $b = new SQLTableDiff( "sync_products_B", $dbConn, $dbConn );

    // fill the tables
    $a->createTableFromFields( array_keys($productsA[0]) );
    $b->createTableFromFields( array_keys($productsB[0]) );
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
    // there you go!

## Notes

Multiple-insertions-per-query takes place using smart-buffering, which makes importing large datasets ultrafast.

## License

BSD
