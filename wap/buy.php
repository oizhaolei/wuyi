<?php

/**
 * WUYI 租品页
 * ============================================================================
 * * 版权所有 2005-2012 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.51wuyi.com；
 * ----------------------------------------------------------------------------


 * ============================================================================
 * $Author: liubo $
 * $Id: buy.php 17217 2011-01-19 06:29:08Z liubo $
*/

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
$smarty->assign('footer', get_footer());
$smarty->display('buy.wml');

?>