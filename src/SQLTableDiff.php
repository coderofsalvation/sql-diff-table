<?php
/*
 *
 * Copyright 2015 Leon van Kammen / Coder of Salvation. All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without modification, are
 * permitted provided that the following conditions are met:
 * 
 *    1. Redistributions of source code must retain the above copyright notice, this list of
 *       conditions and the following disclaimer.
 * 
 *    2. Redistributions in binary form must reproduce the above copyright notice, this list
 *       of conditions and the following disclaimer in the documentation and/or other materials
 *       provided with the distribution.
 * 
 * THIS SOFTWARE IS PROVIDED BY Leon van Kammen / Coder of Salvation AS IS'' AND ANY EXPRESS OR IMPLIED
 * WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND
 * FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL Leon van Kammen / Coder of Salvation OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
 * ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF
 * ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * 
 * The views and conclusions contained in the software and documentation are those of the
 * authors and should not be interpreted as representing official policies, either expressed
 * or implied, of Leon van Kammen / Coder of Salvation 
 */

namespace coderofsalvation;

class SQLTableDiff {

    private $tableName = "";
    private $readDb = false;
    private $writeDb = false;
    private $columns = false;
    public static $onProgress = false;

    public static $sql = array(
        'table'     => "CREATE TABLE IF NOT EXISTS %s ( %s );",
        'field'     => "%s varchar(255)",
        'drop'      => "DROP TABLE IF EXISTS %s;",
        'insert'    => "INSERT INTO %s VALUES(%s);",
        'insertbulk'=> "INSERT INTO %s (%s) VALUES %s;"
    );    

    /**
     * update pushes realtime info to listeners (Closure defined at self::$onProgress)
     * 
     * @param mixed $type 
     * @param mixed $msg 
     * @access private
     * @return void
     */
    private function update($type, $msg){
        if( self::$onProgress ){
            $f = self::$onProgress;
            $f($type,$msg);
        }	
    }

    public function __construct( $tableName, $readDb, $writeDb ){
        $this->writeDb = $writeDb;
        $this->readDb  = $readDb;
        $this->tableName = $tableName;
    }

    public function createTableFromFields( $fieldNames, $dropBeforeCreate = true ){
        if( !$this->writeDb ) throw new Exception("DBHANDLE_NOT_SET");
        $sqlQuery = ""; $this->columns = $fieldNames;
        array_walk( $fieldNames, function(&$item,$key){
            $item = sprintf( SQLTableDiff::$sql['field'], $item);
        });
        $sqlQuery .= sprintf( 	
            SQLTableDiff::$sql['table'],
            $this->tableName,
            implode( $fieldNames, ',')
        );
        if( $dropBeforeCreate ){
            $sqlDropQuery =	sprintf(self::$sql['drop'], $this->tableName );
            $this->update("DEBUG", "executing:  ".$sqlDropQuery );
            $this->writeDb->query( $sqlDropQuery )->execute();
        }
        $this->update( "DEBUG", "executing:  ".$sqlQuery);
        $result = $this->writeDb->query( $sqlQuery )->execute();
    }

    /**
     * addRow adds a row to a table based on fieldorder
     * 
     * @param mixed $fields 
     * @access public
     * @return void
     */
    public function addRow( $fields, $getValues = false ){
        $fields = (array)$fields;
        $fieldsArr = [];
        foreach( $this->columns as $column ) 
            $fieldsArr[] = isset($fields[$column]) ? "'".(string)$fields[$column]."'" : "''";
        $sqlFields = implode( $fieldsArr, "," );
        if( $getValues ) return $sqlFields;
        $sqlQuery = sprintf( self::$sql['insert'] , $this->tableName, $sqlFields );
        $this->update( "DEBUG", "executing:  ".$sqlQuery );
        return $this->writeDb->query( $sqlQuery )->execute();
    }

    public function addRows( $arr, $chunksize = 200 ){
        $result = [];
        // walk array until empty
        while( count($arr) ){                    
            $values = []; 
            // pop n items (we dont want to risk exceeding the maximum sql query length)
            for( $i = 0; $i < $chunksize; $i++ ){
                $item = array_pop($arr);
                if( $item == NULL ) break;
                $values[] = "(" . $this->addRow( (array) $item, true ) . ")";
            }
            // perform bulk sql insert query
            $sqlValues = implode($values,',');
            $sqlQuery = sprintf( self::$sql['insertbulk'] , $this->tableName, implode($this->columns,','), $sqlValues );
            $this->update( "DEBUG", "executing:  ".$sqlQuery );
            $this->update( "PROGRESS", "." );
            $result[] = $this->writeDb->query( $sqlQuery );
        }
        $this->update( "PROGRESS", "OK\n" );
        return $result;
    }


    /**
     * diff compares 2 similar tables and matches on id but return rows when they 
     * differ (according to the given keys)
     *
     * <example>
     * 	$filter = (object)array(
     * 	  'tableA' => "sync_products_magento",
     * 	  'tableB' => "sync_products_import",
     * 	  'id'     => "sku",
     * 	  'fields' => array("title","color")
     * 	);
     * 	$result = $f->diff( $filter );
     * 	// returns products which have different title and/or color
     * </example>
     * 
     * @param mixed $filter 
     * @access public
     * @return void
     */
    public function diff( $filter ){
        $sqlSelect = sprintf( "SELECT * FROM %s as a, %s as b where a.%s = b.%s AND ",
            $filter->tableA,
            $filter->tableB,
            $filter->id,
            $filter->id );
        foreach( $filter->fields as $field ) 
            $diffFields[] = sprintf("a.%s != b.%s", $field, $field );
        $sqlQuery = $sqlSelect . " ( " . implode( $diffFields, " or ") . " ) ";
        $this->update( "DEBUG", "executing:  ".$sqlQuery );
        return $this->readDb->query( $sqlQuery )->execute();
    }

}

