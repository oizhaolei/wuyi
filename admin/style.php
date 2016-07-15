<?php

/**
 * WUYI 管理中心款式管理
 * ============================================================================
 * * 
 * 网站地址: http://www.51wuyi.com；
 * ----------------------------------------------------------------------------


 * ============================================================================
 * $Author: liubo $
 * $Id: style.php 17217 2011-01-19 06:29:08Z liubo $
*/

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
include_once(ROOT_PATH . 'includes/cls_image.php');
$image = new cls_image($_CFG['bgstyle']);

$exc = new exchange($ecs->table("style"), $db, 'style_id', 'style_name');

/*------------------------------------------------------ */
//-- 款式列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    $smarty->assign('ur_here',      $_LANG['07_style_list']);
    $smarty->assign('action_link',  array('text' => $_LANG['07_style_add'], 'href' => 'style.php?act=add'));
    $smarty->assign('full_page',    1);

    $style_list = get_stylelist();

    $smarty->assign('style_list',   $style_list['style']);
    $smarty->assign('filter',       $style_list['filter']);
    $smarty->assign('record_count', $style_list['record_count']);
    $smarty->assign('page_count',   $style_list['page_count']);

    assign_query_info();
    $smarty->display('style_list.htm');
}

/*------------------------------------------------------ */
//-- 添加款式
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'add')
{
    /* 权限判断 */
    admin_priv('style_manage');

    $smarty->assign('ur_here',     $_LANG['07_style_add']);
    $smarty->assign('action_link', array('text' => $_LANG['07_style_list'], 'href' => 'style.php?act=list'));
    $smarty->assign('form_action', 'insert');

    assign_query_info();
    $smarty->assign('style', array('sort_order'=>50, 'is_show'=>1));
    $smarty->display('style_info.htm');
}
elseif ($_REQUEST['act'] == 'insert')
{
    /*检查款式名是否重复*/
    admin_priv('style_manage');

    $is_show = isset($_REQUEST['is_show']) ? intval($_REQUEST['is_show']) : 0;

    $is_only = $exc->is_only('style_name', $_POST['style_name']);

    if (!$is_only)
    {
        sys_msg(sprintf($_LANG['stylename_exist'], stripslashes($_POST['style_name'])), 1);
    }

    $param1 = $_POST['style_alias'];
    $param2 = $_POST['style_type'];
    $param3 = $_POST['id'];
    $is_only = is_only_alias($ecs, $db, $param1, $param2);
 
    if (!$is_only)
    {
        sys_msg(sprintf($_LANG['stylealias_exist'], stripslashes($_POST['style_type']), stripslashes($_POST['style_alias'])), 1);
    }

    /*插入数据*/

    $sql = "INSERT INTO ".$ecs->table('style')."(style_name, style_alias, style_type, is_show, sort_order) ".
           "VALUES ('$_POST[style_name]', '$_POST[style_alias]', '$_POST[style_type]', '$is_show', '$_POST[sort_order]')";
    $db->query($sql);

    admin_log($_POST['style_name'],'add','style');

    /* 清除缓存 */
    clear_cache_files();

    $link[0]['text'] = $_LANG['continue_add'];
    $link[0]['href'] = 'style.php?act=add';

    $link[1]['text'] = $_LANG['back_list'];
    $link[1]['href'] = 'style.php?act=list';

    sys_msg($_LANG['styleadd_succeed'], 0, $link);
}

/*------------------------------------------------------ */
//-- 编辑款式
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit')
{
    /* 权限判断 */
    admin_priv('style_manage');
    $sql = "SELECT style_id, style_name, style_alias, style_type, is_show, sort_order ".
            "FROM " .$ecs->table('style'). " WHERE style_id='$_REQUEST[id]'";
    $style = $db->GetRow($sql);

    $smarty->assign('ur_here',     $_LANG['style_edit']);
    $smarty->assign('action_link', array('text' => $_LANG['07_style_list'], 'href' => 'style.php?act=list&' . list_link_postfix()));
    $smarty->assign('style',       $style);
    $smarty->assign('form_action', 'updata');

    assign_query_info();
    $smarty->display('style_info.htm');
}
elseif ($_REQUEST['act'] == 'updata')
{
    admin_priv('style_manage');
    if ($_POST['style_name'] != $_POST['old_stylename'])
    {
        /*检查款式名是否相同*/
        $is_only = $exc->is_only('style_name', $_POST['style_name'], $_POST['id']);

        if (!$is_only)
        {
            sys_msg(sprintf($_LANG['stylename_exist'], stripslashes($_POST['style_name'])), 1);
        }
    }

    if ($_POST['style_type'] != $_POST['old_style_type'] || $_POST['style_alias'] != $_POST['old_style_alias'])
    {
        /*检查款式别名是否相同*/
        $param1 = $_POST['style_alias'];
        $param2 = $_POST['style_type'];
        $param3 = $_POST['id'];
        $is_only = is_only_alias($ecs, $db, $param1, $param2, $param3);

        if (!$is_only)
        {
            sys_msg(sprintf($_LANG['stylealias_exist'], stripslashes($_POST['style_type']), stripslashes($_POST['style_alias'])), 1);
        }
    }
    $is_show = isset($_REQUEST['is_show']) ? intval($_REQUEST['is_show']) : 0;


    $param = "style_name = '$_POST[style_name]', style_alias = '$_POST[style_alias]', style_type='$_POST[style_type]', is_show='$is_show', sort_order='$_POST[sort_order]' ";

    if ($exc->edit($param,  $_POST['id']))
    {
        /* 清除缓存 */
        clear_cache_files();

        admin_log($_POST['style_name'], 'edit', 'style');

        $link[0]['text'] = $_LANG['back_list'];
        $link[0]['href'] = 'style.php?act=list&' . list_link_postfix();
        $note = vsprintf($_LANG['styleedit_succeed'], $_POST['style_name']);
        sys_msg($note, 0, $link);
    }
    else
    {
        die($db->error());
    }
}

/*------------------------------------------------------ */
//-- 编辑款式名称
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_style_name')
{
    check_authz_json('style_manage');

    $id     = intval($_POST['id']);
    $name   = json_str_iconv(trim($_POST['val']));

    /* 检查名称是否重复 */
    if ($exc->num("style_name",$name, $id) != 0)
    {
        make_json_error(sprintf($_LANG['stylename_exist'], $name));
    }
    else
    {
        if ($exc->edit("style_name = '$name'", $id))
        {
            admin_log($name,'edit','style');
            make_json_result(stripslashes($name));
        }
        else
        {
            make_json_result(sprintf($_LANG['styleedit_fail'], $name));
        }
    }
}
elseif($_REQUEST['act'] == 'add_style')
{
    $style = empty($_REQUEST['style']) ? '' : json_str_iconv(trim($_REQUEST['style']));

    if(style_exists($style))
    {
        make_json_error($_LANG['style_name_exist']);
    }
    else
    {
        $sql = "INSERT INTO " . $ecs->table('style') . "(style_name)" .
               "VALUES ( '$style')";

        $db->query($sql);
        $style_id = $db->insert_id();

        $arr = array("id"=>$style_id, "style"=>$style);

        make_json_result($arr);
    }
}
/*------------------------------------------------------ */
//-- 编辑排序序号
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_sort_order')
{
    check_authz_json('style_manage');

    $id     = intval($_POST['id']);
    $order  = intval($_POST['val']);
    $name   = $exc->get_name($id);

    if ($exc->edit("sort_order = '$order'", $id))
    {
        admin_log(addslashes($name),'edit','style');

        make_json_result($order);
    }
    else
    {
        make_json_error(sprintf($_LANG['styleedit_fail'], $name));
    }
}

/*------------------------------------------------------ */
//-- 切换是否显示
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'toggle_show')
{
    check_authz_json('style_manage');

    $id     = intval($_POST['id']);
    $val    = intval($_POST['val']);

    $exc->edit("is_show='$val'", $id);

    make_json_result($val);
}

/*------------------------------------------------------ */
//-- 删除款式
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove')
{
    check_authz_json('style_manage');

    $id = intval($_GET['id']);

    $exc->drop($id);

    /* 更新租品的款式编号 */
    $sql = "UPDATE " .$ecs->table('goods'). " SET style_id=0 WHERE style_id='$id'";
    $db->query($sql);

    $url = 'style.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

    ecs_header("Location: $url\n");
    exit;
}

/*------------------------------------------------------ */
//-- 排序、分页、查询
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    $style_list = get_stylelist();
    $smarty->assign('style_list',   $style_list['style']);
    $smarty->assign('filter',       $style_list['filter']);
    $smarty->assign('record_count', $style_list['record_count']);
    $smarty->assign('page_count',   $style_list['page_count']);

    make_json_result($smarty->fetch('style_list.htm'), '',
        array('filter' => $style_list['filter'], 'page_count' => $style_list['page_count']));
}

/**
 * 获取款式列表
 *
 * @access  public
 * @return  array
 */
function get_stylelist()
{
    $result = get_filter();
    if ($result === false)
    {
        /* 分页大小 */
        $filter = array();

        /* 记录总数以及页数 */
        if (isset($_POST['style_name']))
        {
            $sql = "SELECT COUNT(*) FROM ".$GLOBALS['ecs']->table('style') .' WHERE style_name = \''.$_POST['style_name'].'\'';
        }
        else
        {
            $sql = "SELECT COUNT(*) FROM ".$GLOBALS['ecs']->table('style');
        }

        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        $filter = page_and_size($filter);

        /* 查询记录 */
        if (isset($_POST['style_name']))
        {
            if(strtoupper(EC_CHARSET) == 'GBK')
            {
                $keyword = iconv("UTF-8", "gb2312", $_POST['style_name']);
            }
            else
            {
                $keyword = $_POST['style_name'];
            }
            $sql = "SELECT * FROM ".$GLOBALS['ecs']->table('style')." WHERE style_name like '%{$keyword}%' or style_alias like '%{$keyword}%' ORDER BY sort_order ASC";
        }
        else
        {
            $sql = "SELECT * FROM ".$GLOBALS['ecs']->table('style')." ORDER BY sort_order ASC";
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

    return array('style' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
}

function is_only_alias($ecs, $db, $alias, $type, $id='')
{
    $sql = 'SELECT COUNT(*) FROM ' .$ecs->table('style'). " WHERE style_alias = '$alias' AND style_type = '$type'";
    $sql .= empty($_POST['id']) ? '' : ' AND style_id'  . " <> '$id'";
    return ($db->getOne($sql) == 0);
}
?>
