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

    // create 2 SQL tables based on the object fields 
    $a->createTableFromFields( array_keys($productsA[0]) );
    $b->createTableFromFields( array_keys($productsB[0]) );
    // fill the 2 SQL tables
    foreach( $productsA as $p ) $sm->addRow( $p );
    foreach( $productsB as $p ) $si->addRow( $p );

    /*
     * get products which have similar id-value but different stock-value
     */

    $filter = (object)array(
      'tableA' => "sync_products_A",
      'tableB' => "sync_products_B",
      'id'     => "sku",              // match on id field
      'fields' => array("stock")      // compare these fields
    );

    // SQL query is generated and the differences are returned as a result
    $result = $sm->diff( $filter);
    // there you go!

## Notes

Multiple-insertions-per-query takes place using smart-buffering, which makes importing large datasets ultrafast.

## Hint

If you want to compare large collections with items, sometimes the items are not compatible.
For example, if you want to compare products from an csv export with products in your database.
In that case you might want to transform the collections to a middleware-format.
[DataMapper](https://github.com/coderofsalvation/datamapper-minimal) allows you to do that.
After you mapped it to your middleware-format, then you can proceed inserting it into the db using `createTableFormFields()`

## License

BSD
