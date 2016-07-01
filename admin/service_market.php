<?php

/**
 * WUYI 程序说明
 * ===========================================================
 * * 
 * 网站地址: http://www.51wuyi.com；
 * ----------------------------------------------------------


 * ==========================================================
 * $Author: yangyichao $
 * $Id: service_market.php 2016-04-25 yangyichao$
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');



$smarty->assign('ur_here', $_LANG['service_market_here']);
$smarty->assign('iframe_url', YUNQI_SERVICE_URL . 'cid=38&source='.iframe_source_encode('wuyi'));
$smarty->display('yq_iframe.htm');




?>