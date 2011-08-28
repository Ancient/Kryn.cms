<?php


/*
 * This file is part of Kryn.cms.
 *
 * (c) Kryn.labs, MArc Schmidt <marc@kryn.org>
 *
 * To get the full copyright and license informations, please view the
 * LICENSE file, that was distributed with this source code.
 *
 */


/**
 * Global framework functions
 * 
 * @author Kryn.labs <info@krynlabs.com>
 * @package Kryn
 * @subpackage FrameworkDatabase
 */


/**
 * Escape a string for usage in SQL.
 * Depending on the current database this functions choose the proper escape
 * function.
 * @param string $p
 * @return string Escaped string 
 */
function esc( $p, $pEscape = false ){
	global $kdb, $cfg;
	
	if( is_array($p) ) {
	   foreach( $p as $k => $v){
	       $p2[$k] = esc($v);
	   }
	   return $p2;
	}
	
    if( $pEscape == 2 ){
        return preg_replace("/\W/", "", $p);
    } else if( $pEscape == 1 || $pEscape == true){
        return esc( $p );
    }
    
	if( $cfg['db_pdo']+0 == 1 || $cfg['db_pdo'] === '' ){
	    return substr( substr( $kdb->pdo->quote($p), 1 ), 0, -1 );
	} else {
	    switch( $cfg['db_type'] ){
            case 'sqlite':
                return sqlite_escape_string ( $p  );
            case 'mysql':
                return mysql_real_escape_string( $p, $kdb->connection );
            case 'mysqli':
                return mysqli_real_escape_string( $kdb->connection, $p  );
            case 'postgresql':
                return pg_escape_string  ( $p );
        }
	}
}


/**
 * Execute a query and return the items
 * @param string $pSql The SQL
 * @param integer $pRowCount How much rows you want. Use -1 for all, with 1 you'll get direct the array without a list.
 * @param type $pMode Obsolete
 * @return array
 */
function dbExfetch( $pSql, $pRowCount = 1, $pMode = PDO::FETCH_ASSOC ){
    global $kdb, $cfg;
    $pSql = str_replace( '%pfx%', $cfg['db_prefix'], $pSql );
    return $kdb->exfetch( $pSql, $pRowCount, $pMode );
}


/**
 * Execute a query and return the resultset
 * 
 * @param string $pSql
 * @return array 
 */
function dbExec( $pSql ){
    global $kdb;
    $pSql = str_replace( '%pfx%', pfx, $pSql );
    return $kdb->exec( $pSql );
}


function dbTableLang( $pTable, $pCount = -1, $pWhere = false ){
    if( $_REQUEST['lang'] )
        $lang = $_REQUEST['lang'];
    else
        $lang = kryn::$language;
    if( $pWhere )
        $pWhere = " lang = '$lang' AND ".$pWhere;
    else
        $pWhere = "lang = '$lang'";
    return dbTableFetch( $pTable, $pCount, $pWhere );
}


/**
 * Select items based on pWhere on table pTable and returns pCount items.
 * 
 * @param string $pTable The table name based on your extension table definition.
 * @param integer $pCount How many items it will returns, with 1 you'll get direct the array without a list.
 * @param string $pWhere 
 * @param type $pMode obsolete
 * @return type 
 */
function dbTableFetch( $pTable, $pCount = -1, $pWhere = false, $pMode = PDO::FETCH_ASSOC ){
    
    //to change pCount <-> pWhere
    if( gettype($pCount) == 'string' ) $pNewWhere = $pCount;
    if( gettype($pWhere) == 'integer' ) $pNewCount = $pWhere;

    if( $pNewWhere ) $pWhere = $pNewWhere;
    if( $pNewCount ) $pCount = $pNewCount;
    
    $table = database::getTable( $pTable );
    $sql = "SELECT * FROM $table";
    if( $pWhere != false)
        $sql .= " WHERE $pWhere";
    return dbExfetch( $sql, $pCount, $pMode );
}


/**
 * Inserts the values based on pFields into the table pTable.
 * 
 * @param string $pTable The table name based on your extension table definition
 * @param array $pFields Array as a key-value pair. key is the column name and the value is the value. More infos under http://www.kryn.org/docu/developer/framework-database
 * @return integer The last_insert_id() (if you use auto_increment/sequences) 
 */
function dbInsert( $pTable, $pFields ){
    $table = database::getTable( $pTable );
    $sql .= "INSERT INTO $table (";
    $options = database::getOptions( $table );
    
    $fields = array();
    foreach( $pFields as $key => $field ){
        if( $options[$key])
            $fields[$key] = $field;
        else if( $options[$field])
            $fields[] = $field;
    }
    

    foreach( $fields as $key => $field ){

        if( is_numeric($key) ){
            $fieldName = $field;
            $val = getArgv( $field );
        } else {
            $fieldName = $key;
            $val = $field;
        }

        if( !$options[$fieldName] ) continue;

        $sqlFields .= "$fieldName,";

        if( $options[$fieldName]['escape'] == 'int' ){
            $sqlInsert .= ($val+0) . ",";
        } else {
            $sqlInsert .= "'" . esc($val) . "',";
        }
    }

    $sqlInsert = substr( $sqlInsert, 0, -1 );
    $sqlFields = substr( $sqlFields, 0, -1 );

    $sql .= " $sqlFields ) VALUES( $sqlInsert )";
    if( dbExec( $sql ) )
        return database::last_id();
    else
        return false;
}


function dbToKeyIndex( &$pItems, $pIndex ){
    $res = array();
    if( count($pItems) > 0)
    foreach( $pItems as $item ){
        $res[ $item[$pIndex] ] = $item;
    }
    return $res;
}

function dbError(){
    global $kdb;
    if(! $kdb->error )
        return false;
    return $kdb->errorMessage;
}


/**
 * Update a row or rows with the values based on pFields into the table pTable.
 * 
 * @param string $pTable The table name based on your extension table definition
 * @param string|array $pPrimary Define the limitation as a SQL or as a array ('field' => 'value')
 * @param array $pFields Array as a key-value pair. key is the column name and the value is the value. More infos under http://www.kryn.org/docu/developer/framework-database
 * @return type 
 */
function dbUpdate( $pTable, $pPrimary, $pFields ){
    $table = database::getTable( $pTable );
    
    $sql = "UPDATE $table SET ";
    $options = database::getOptions( $table );

    if( is_array($pPrimary) ){
        $where = ' ';
        foreach( $pPrimary as $fieldName => $fieldValue) {
            if( !$options[$fieldName] ) continue;
            
            $where .= '' . $fieldName . ' ';
            if( $options[$fieldName]['escape'] == 'int' ){
                $where .= ' = ' . ($fieldValue+0) . " AND ";
            } else {
                $where .= " = '" . esc($fieldValue) . "' AND ";
            }
        }
        
        $where = substr( $where, 0, -4 );
    } else {
        $where = $pPrimary;
    }

    foreach( $pFields as $key => $field ){    
            
        if( is_numeric($key) ){
            $fieldName = $field;
            $val = getArgv( $field );
        } else {
            $fieldName = $key;
            $val = $field;
        }
        
        if( !$options[$fieldName] ) continue;

        $sqlInsert .= "$fieldName";

        if( $options[$fieldName]['escape'] == 'int' ){
            $sqlInsert .= ' = ' . ($val+0) . ",";
        } else {
            $sqlInsert .= " = '" . esc($val) . "',";
        }
    }

    $sqlInsert = substr( $sqlInsert, 0, -1 );
    
    $sql .= " $sqlInsert WHERE $where ";
    return dbExec( $sql );
}

/**
 * Deletes rows from the table based on the pWhere
 * @param type $pTable The table name based on your extension table definition
 * @param type $pWhere Do not forget this, otherwise the table will be truncated.
 */
function dbDelete( $pTable, $pWhere = false){
    $sql = "DELETE FROM ".database::getTable( $pTable )."";
    if( $pWhere != false )
        $sql .= " WHERE $pWhere ";
    dbExec( $sql );
}

function dbCount( $pTable, $pWhere = false){
    $sql = "SELECT count(*) as count FROM %pfx%$pTable";
    if( $pWhere != false )
        $sql .= " WHERE $pWhere ";
    $row = dbExfetch( $sql );
    return $row['count'];
}

/**
 * Fetch a row based on the specified Resultset from dbExec()
 * 
 * @param type $pRes The result of dbExec()
 * @param type $pCount Defines how many items the function returns
 * @param type $pMode Obsolete
 * @return type 
 */
function dbFetch( $pRes, $pCount = 1, $pMode = PDO::FETCH_ASSOC ){
    global $kdb;
    return $kdb->fetch( $pRes, $pCount, $pMode );
}

?>