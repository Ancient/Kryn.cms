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
 * Internal functions
 * 
 * @author MArc Schmidt <marc@kryn.org>
 * @internal
 */

$errorHandlerInside = false;



function kryn_shutdown(){
    global $user;
    $user->syncStore();
}



/**
 * Deactivate magic quotes
 */
if (get_magic_quotes_gpc()) {
    function magicQuotes_awStripslashes(&$value, $key) {$value = stripslashes($value);}
    $gpc = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);
    array_walk_recursive($gpc, 'magicQuotes_awStripslashes');
}






function errorHandler( $pCode, $pMsg, $pFile = false, $pLine = false ){
    global $errorHandlerInside, $user, $cfg, $client;

    if( $errorHandlerInside ) return;
    
    $errorHandlerInside = true;
    
    if( ($pCode+0) > 0 ){
  

    	if( $pCode != 8 && $pCode != 2 ){
	    	//only if administrator want to see
	    	if( $cfg['display_errors']+0 == 1 && !kryn::$admin ){
	    		print $user->user['username']." - $pCode: $pMsg in $pFile on $pLine<br />\n";
	    	}
	    	if( array_key_exists('log_errors', $cfg) && array_key_exists('log_errors_file', $cfg) &&
	    	    $cfg['log_errors']+0 == 1 && $cfg['log_errors_file'] != "" ){
	    	    	
	        	@error_log($user->user['username']." - $pCode: $pMsg in $pFile on $pLine\n", 3, $cfg['log_errors_file']);
	    	}
    	}
        
        //When we are not in kryn-installer go out
        $errorHandlerInside = false;
        if( !array_key_exists('krynInstaller', $GLOBALS) )
        	return; 
    }
    
    if( !class_exists('database') )
    	die('Error before class initialisations: '.$pCode.': '.$pMsg.' '.$pFile.':'.$pLine);
    
    if(! database::isActive() ) return;
    
    $sid = esc($client->token);
    $ip = $_SERVER['REMOTE_ADDR'];
    $username = $user->user['username'];
    $pCode = preg_replace('/\W/', '-', $pCode);
    $msg = htmlspecialchars($pMsg);
    
    if( $pFile )
        $msg = "$msg in $pFile on $pLine";
        
    if( array_key_exists('krynInstaller', $GLOBALS) && $GLOBALS['krynInstaller'] == true ){
        $f = @fopen('install.log');
        if( $f ){
            @fwrite( $f, $msg );
            @fclose($f);
        }
    } else {
        database::$hideSql = true;
        $qry = dbInsert('system_log', array(
            'date' => time(),
            'ip' => $ip,
            'username' => $username,
            'code' => $pCode,
            'message' => $msg
        ));
        database::$hideSql = true;
        
        if( $qry === false )
            die( str_replace('%s', $msg, _l('Failed to insert log entry: %s')) );
    }
    
    $errorHandlerInside = false;

}



/**
 * Kryn exception handler
 * @internal
 */
function kExceptionHandler( $pException ){
	if( $pException )
		klog('php exception', $pException );
}


?>
