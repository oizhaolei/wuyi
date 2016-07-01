<?php

/**
 * WUYI 短消息文件
 * ============================================================================
 * * 版权所有 2005-2012 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.51wuyi.com；
 * ----------------------------------------------------------------------------


 * ============================================================================
 * $Author: liubo $
 * $Id: pm.php 17217 2011-01-19 06:29:08Z liubo $
*/

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
if (empty($_SESSION['user_id'])||$_CFG['integrate_code']=='wuyi')
{
    ecs_header('Location:./');
}

uc_call("uc_pm_location", array($_SESSION['user_id']));
//$ucnewpm = uc_pm_checknew($_SESSION['user_id']);
//setcookie('checkpm', '');

?>