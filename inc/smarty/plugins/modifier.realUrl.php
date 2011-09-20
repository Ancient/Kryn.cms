<?php
function smarty_modifier_realUrl($params){
    global $kryn;
    

    if(! is_array( $params ) ){
        $t = (int)($params)+0;
        $params = array('rsn' => $t);
        $rsn = $t;
    } else {
        $rsn = $params['rsn'];
        if( $params['realUrl'] )
            return $params['realUrl'];
    }
    if( is_array($params) && $params['type'] == 1  && $params['link']+0 > 0 )
        $rsn = $params['link'];

    return kryn::pageUrl( $rsn );
}
?>
