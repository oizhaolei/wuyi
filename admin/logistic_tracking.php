<?php

/**
 * WUYI 程序说明
 * ===========================================================
 * * 
 * 网站地址: http://www.51wuyi.com；
 * ----------------------------------------------------------


 * ==========================================================
 * $Author: yangyichao $
 * $Id: logistic_tracking.php 2016-04-25 yangyichao$
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');



$smarty->assign('ur_here', $_LANG['logistic_tracking_here']);
$smarty->assign('iframe_url', YUNQI_LOGISTIC_URL . '?ctl=exp&act=index&source='.iframe_source_encode('wuyi'));
$smarty->display('yq_iframe.htm');




?>