<?php

/**
 * WUYI 管理中心颜色管理
 * ============================================================================
 * * 
 * 网站地址: http://www.51wuyi.com；
 * ----------------------------------------------------------------------------


 * ============================================================================
 * $Author: liubo $
 * $Id: color.php 17217 2011-01-19 06:29:08Z liubo $
*/

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
include_once(ROOT_PATH . 'includes/cls_image.php');
$image = new cls_image($_CFG['bgcolor']);

$exc = new exchange($ecs->table("color"), $db, 'color_id', 'color_name');

/*------------------------------------------------------ */
//-- 颜色列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    $smarty->assign('ur_here',      $_LANG['06_goods_color_list']);
    $smarty->assign('action_link',  array('text' => $_LANG['07_color_add'], 'href' => 'color.php?act=add'));
    $smarty->assign('full_page',    1);

    $color_list = get_colorlist();

    $smarty->assign('color_list',   $color_list['color']);
    $smarty->assign('filter',       $color_list['filter']);
    $smarty->assign('record_count', $color_list['record_count']);
    $smarty->assign('page_count',   $color_list['page_count']);

    assign_query_info();
    $smarty->display('color_list.htm');
}

/*------------------------------------------------------ */
//-- 添加颜色
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'add')
{
    /* 权限判断 */
    admin_priv('color_manage');

    $smarty->assign('ur_here',     $_LANG['07_color_add']);
    $smarty->assign('action_link', array('text' => $_LANG['06_goods_color_list'], 'href' => 'color.php?act=list'));
    $smarty->assign('form_action', 'insert');

    assign_query_info();
    $smarty->assign('color', array('sort_order'=>50, 'is_show'=>1));
    $smarty->display('color_info.htm');
}
elseif ($_REQUEST['act'] == 'insert')
{
    /*检查颜色名是否重复*/
    admin_priv('color_manage');

    $is_show = isset($_REQUEST['is_show']) ? intval($_REQUEST['is_show']) : 0;

    $is_only = $exc->is_only('color_name', $_POST['color_name']);

    if (!$is_only)
    {
        sys_msg(sprintf($_LANG['colorname_exist'], stripslashes($_POST['color_name'])), 1);
    }

    /*插入数据*/

    $sql = "INSERT INTO ".$ecs->table('color')."(color_name, color_r, color_g, color_b, is_show, sort_order) ".
           "VALUES ('$_POST[color_name]', '$_POST[color_r]', '$_POST[color_g]', '$_POST[color_b]', '$is_show', '$_POST[sort_order]')";
    $db->query($sql);

    admin_log($_POST['color_name'],'add','color');

    /* 清除缓存 */
    clear_cache_files();

    $link[0]['text'] = $_LANG['continue_add'];
    $link[0]['href'] = 'color.php?act=add';

    $link[1]['text'] = $_LANG['back_list'];
    $link[1]['href'] = 'color.php?act=list';

    sys_msg($_LANG['coloradd_succed'], 0, $link);
}

/*------------------------------------------------------ */
//-- 编辑颜色
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit')
{
    /* 权限判断 */
    admin_priv('color_manage');
    $sql = "SELECT color_id, color_name, color_r, color_g, color_b, is_show, sort_order ".
            "FROM " .$ecs->table('color'). " WHERE color_id='$_REQUEST[id]'";
    $color = $db->GetRow($sql);

    $smarty->assign('ur_here',     $_LANG['color_edit']);
    $smarty->assign('action_link', array('text' => $_LANG['06_goods_color_list'], 'href' => 'color.php?act=list&' . list_link_postfix()));
    $smarty->assign('color',       $color);
    $smarty->assign('form_action', 'updata');

    assign_query_info();
    $smarty->display('color_info.htm');
}
elseif ($_REQUEST['act'] == 'updata')
{
    admin_priv('color_manage');
    if ($_POST['color_name'] != $_POST['old_colorname'])
    {
        /*检查颜色名是否相同*/
        $is_only = $exc->is_only('color_name', $_POST['color_name'], $_POST['id']);

        if (!$is_only)
        {
            sys_msg(sprintf($_LANG['colorname_exist'], stripslashes($_POST['color_name'])), 1);
        }
    }

    $is_show = isset($_REQUEST['is_show']) ? intval($_REQUEST['is_show']) : 0;


    $param = "color_name = '$_POST[color_name]', color_r='$_POST[color_r]', color_g='$_POST[color_g]', color_b='$_POST[color_b]', is_show='$is_show', sort_order='$_POST[sort_order]' ";

    if ($exc->edit($param,  $_POST['id']))
    {
        /* 清除缓存 */
        clear_cache_files();

        admin_log($_POST['color_name'], 'edit', 'color');

        $link[0]['text'] = $_LANG['back_list'];
        $link[0]['href'] = 'color.php?act=list&' . list_link_postfix();
        $note = vsprintf($_LANG['coloredit_succed'], $_POST['color_name']);
        sys_msg($note, 0, $link);
    }
    else
    {
        die($db->error());
    }
}

/*------------------------------------------------------ */
//-- 编辑颜色名称
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_color_name')
{
    check_authz_json('color_manage');

    $id     = intval($_POST['id']);
    $name   = json_str_iconv(trim($_POST['val']));

    /* 检查名称是否重复 */
    if ($exc->num("color_name",$name, $id) != 0)
    {
        make_json_error(sprintf($_LANG['colorname_exist'], $name));
    }
    else
    {
        if ($exc->edit("color_name = '$name'", $id))
        {
            admin_log($name,'edit','color');
            make_json_result(stripslashes($name));
        }
        else
        {
            make_json_result(sprintf($_LANG['coloredit_fail'], $name));
        }
    }
}

elseif($_REQUEST['act'] == 'add_color')
{
    $color = empty($_REQUEST['color']) ? '' : json_str_iconv(trim($_REQUEST['color']));

    if(color_exists($color))
    {
        make_json_error($_LANG['color_name_exist']);
    }
    else
    {
        $sql = "INSERT INTO " . $ecs->table('color') . "(color_name)" .
               "VALUES ( '$color')";

        $db->query($sql);
        $color_id = $db->insert_id();

        $arr = array("id"=>$color_id, "color"=>$color);

        make_json_result($arr);
    }
}
/*------------------------------------------------------ */
//-- 编辑排序序号
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_sort_order')
{
    check_authz_json('color_manage');

    $id     = intval($_POST['id']);
    $order  = intval($_POST['val']);
    $name   = $exc->get_name($id);

    if ($exc->edit("sort_order = '$order'", $id))
    {
        admin_log(addslashes($name),'edit','color');

        make_json_result($order);
    }
    else
    {
        make_json_error(sprintf($_LANG['coloredit_fail'], $name));
    }
}

/*------------------------------------------------------ */
//-- 切换是否显示
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'toggle_show')
{
    check_authz_json('color_manage');

    $id     = intval($_POST['id']);
    $val    = intval($_POST['val']);

    $exc->edit("is_show='$val'", $id);

    make_json_result($val);
}

/*------------------------------------------------------ */
//-- 删除颜色
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove')
{
    check_authz_json('color_manage');

    $id = intval($_GET['id']);

    $exc->drop($id);

    /* 更新租品的颜色编号 */
    $sql = "UPDATE " .$ecs->table('goods'). " SET color_id=0 WHERE color_id='$id'";
    $db->query($sql);

    $url = 'color.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

    ecs_header("Location: $url\n");
    exit;
}

/*------------------------------------------------------ */
//-- 排序、分页、查询
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    $color_list = get_colorlist();
    $smarty->assign('color_list',   $color_list['color']);
    $smarty->assign('filter',       $color_list['filter']);
    $smarty->assign('record_count', $color_list['record_count']);
    $smarty->assign('page_count',   $color_list['page_count']);

    make_json_result($smarty->fetch('color_list.htm'), '',
        array('filter' => $color_list['filter'], 'page_count' => $color_list['page_count']));
}

/**
 * 获取颜色列表
 *
 * @access  public
 * @return  array
 */
function get_colorlist()
{
    $result = get_filter();
    if ($result === false)
    {
        /* 分页大小 */
        $filter = array();

        /* 记录总数以及页数 */
        if (isset($_POST['color_name']))
        {
            $sql = "SELECT COUNT(*) FROM ".$GLOBALS['ecs']->table('color') .' WHERE color_name = \''.$_POST['color_name'].'\'';
        }
        else
        {
            $sql = "SELECT COUNT(*) FROM ".$GLOBALS['ecs']->table('color');
        }

        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        $filter = page_and_size($filter);

        /* 查询记录 */
        if (isset($_POST['color_name']))
        {
            if(strtoupper(EC_CHARSET) == 'GBK')
            {
                $keyword = iconv("UTF-8", "gb2312", $_POST['color_name']);
            }
            else
            {
                $keyword = $_POST['color_name'];
            }
            $sql = "SELECT * FROM ".$GLOBALS['ecs']->table('color')." WHERE color_name like '%{$keyword}%' ORDER BY sort_order ASC";
        }
        else
        {
            $sql = "SELECT * FROM ".$GLOBALS['ecs']->table('color')." ORDER BY sort_order ASC";
        }

        set_filter($filter, $sql);
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }
    $res = $GLOBALS['db']->selectLimit($sql, $filter['page_size'], $filter['start']);

    $arr = array();
    while ($rows = $GLOBALS['db']->fetchRow($res))
    {
        $arr[] = $rows;
    }

    return array('color' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
}

?>
