<?php
/**
 * WUYI 帮助信息接口
 * ============================================================================
 * * 版权所有 2005-2012 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.51wuyi.com；
 * ----------------------------------------------------------------------------


 * ============================================================================
 * $Author: liubo $
 * $Id: respond.php 16220 2009-06-12 02:08:59Z liubo $
 */

define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');

$get_keyword = trim($_GET['al']); // 获取关键字
header("location:http://help.51wuyi.com/do.php?k=".$get_keyword."&v=".$_CFG['ecs_version']."&l=".$_CFG['lang']."&c=".EC_CHARSET);
?>