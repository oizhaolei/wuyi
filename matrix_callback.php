<?php

/**
 * WUYI 绑定矩阵callback文件
 * ============================================================================
 * * 
 * 网站地址: http://www.51wuyi.com；
 * ----------------------------------------------------------------------------


 * ============================================================================
 * $Author: wangleisvn $
 * $Id: certificate.php 16075 2009-05-22 02:19:40Z wangleisvn $
*/

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

/*------------------------------------------------------ */
//-- 申请绑定矩阵callback
/*------------------------------------------------------ */
$data = $_POST;
if(!empty($data)){
    include_once(ROOT_PATH . 'includes/cls_matrix.php');
    include_once(ROOT_PATH."includes/cls_certificate.php");
    $cert = new certificate();
    $matrix = new matrix();
    $sign = $data["certi_ac"];
    $my_sign = $cert->make_shopex_ac($data);
    if( $sign != $my_sign ){
        die('{"res":"fail","msg":"error:000002","info":"sign error"}');
    }else{
        $node_type = trim($data['node_type']);
        if($data['status'] == 'bind'){
            $data['name'] = $data['shop_name'];
            unset($data['shop_name']);
            //同一种node_type只能绑定一个
            if($cert->is_bind_sn($node_type,'bind_type')){
                die('{"res":"fail","msg":"error:000002","info":"node_type is exists"}');
            }
            //保存绑定关系
            $matrix->save_shop_bind($data);
        }else{
            $matrix->delete_shop_bind($node_type);
        }
    }
}else{
    exit('{"res":"fail","msg":"error:000001","info":""}');
}

?>