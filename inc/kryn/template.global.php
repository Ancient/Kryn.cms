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
 * 
 * Global template functions
 * 
 * @author MArc Schmidt <marc@kryn.org>
 */

/**
 * Defines a value to the specified name in the template engine
 * 
 * Accessible in template engine {$<$pName>}
 *  
 * @param type $pName
 * @param type $pVal
 */
function tAssign( $pName, $pVal ){
    global $tpl;
    return $tpl->assign( $pName, $pVal );
}

/**
 * Parse and compiled specified template and return the parsed template.
 * Path pFile is relative to inc/template/
 * 
 * @param type $pFile
 * @return string Parsed template file 
 */
function tFetch( $pFile ){
    if( $pFile == "" ) return;
    global $tpl;
    return preg_replace_callback(
        //'/\[([^\"\\\'\{\#\§\$\&\n\ ].*[^\"\\\'\{\#\§\$\&\n\ ])\]/',
        '/([^\\\\]?)\[\[([^\]]*)\]\]/',
        create_function(
            '$pP',
            '
            return $pP[1]._l( $pP[2] );
            '
        ),
        $tpl->fetch( $pFile )
    );    
}
?>
