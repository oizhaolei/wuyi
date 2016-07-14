<?php

/**
 * WUYI 管理中心库存位置管理
 * ============================================================================
 * * 
 * 网站地址: http://www.51wuyi.com；
 * ----------------------------------------------------------------------------


 * ============================================================================
 * $Author: liubo $
 * $Id: storage_location.php 17217 2011-01-19 06:29:08Z liubo $
*/

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
include_once(ROOT_PATH . 'includes/cls_image.php');
$image = new cls_image($_CFG['bgcolor']);

$exc = new exchange($ecs->table("storage_location"), $db, 'storage_location_id', 'storage_location_name');

/*------------------------------------------------------ */
//-- 库存位置列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    $smarty->assign('ur_here',      $_LANG['19_storage_location_list']);
    $smarty->assign('action_link',  array('text' => $_LANG['07_storage_location_add'], 'href' => 'storage_location.php?act=add'));
    $smarty->assign('full_page',    1);

    $storage_location_list = get_storage_locationlist();

    $smarty->assign('storage_location_list',   $storage_location_list['storage_location']);
    $smarty->assign('filter',       $storage_location_list['filter']);
    $smarty->assign('record_count', $storage_location_list['record_count']);
    $smarty->assign('page_count',   $storage_location_list['page_count']);

    assign_query_info();
    $smarty->display('storage_location_list.htm');
}

/*------------------------------------------------------ */
//-- 添加库存位置
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'add')
{
    /* 权限判断 */
    admin_priv('storage_location_manage');

    $smarty->assign('ur_here',     $_LANG['07_storage_location_add']);
    $smarty->assign('action_link', array('text' => $_LANG['19_storage_location_list'], 'href' => 'storage_location.php?act=list'));
    $smarty->assign('form_action', 'insert');

    assign_query_info();
    $smarty->assign('storage_location', array('sort_order'=>50, 'is_show'=>1));
    $smarty->display('storage_location_info.htm');
}
elseif ($_REQUEST['act'] == 'insert')
{
    /*检查库存位置名是否重复*/
    admin_priv('storage_location_manage');

    $is_show = isset($_REQUEST['is_show']) ? intval($_REQUEST['is_show']) : 0;

    $is_only = $exc->is_only('storage_location_name', $_POST['storage_location_name']);

    if (!$is_only)
    {
        sys_msg(sprintf($_LANG['storage_location_name_exist'], stripslashes($_POST['storage_location_name'])), 1);
    }

    /*对地址处理*/
    if (!empty($_POST['storage_location_address']))
    {
        $_POST['storage_location_address'] = $_POST['storage_location_address'];
    }

    /*插入数据*/

    $sql = "INSERT INTO ".$ecs->table('storage_location')."(storage_location_name, storage_location_address, is_show, sort_order) ".
           "VALUES ('$_POST[storage_location_name]', '$_POST[storage_location_address]', '$is_show', '$_POST[sort_order]')";
    $db->query($sql);

    admin_log($_POST['storage_location_name'],'add','storage_location');

    /* 清除缓存 */
    clear_cache_files();

    $link[0]['text'] = $_LANG['continue_add'];
    $link[0]['href'] = 'storage_location.php?act=add';

    $link[1]['text'] = $_LANG['back_list'];
    $link[1]['href'] = 'storage_location.php?act=list';

    sys_msg($_LANG['storage_location_add_succeed'], 0, $link);
}

/*------------------------------------------------------ */
//-- 编辑库存位置
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit')
{
    /* 权限判断 */
    admin_priv('storage_location_manage');
    $sql = "SELECT storage_location_id, storage_location_name, storage_location_address, is_show, sort_order ".
            "FROM " .$ecs->table('storage_location'). " WHERE storage_location_id='$_REQUEST[id]'";
    $storage_location = $db->GetRow($sql);

    $smarty->assign('ur_here',     $_LANG['storage_location_edit']);
    $smarty->assign('action_link', array('text' => $_LANG['19_storage_location_list'], 'href' => 'storage_location.php?act=list&' . list_link_postfix()));
    $smarty->assign('storage_location',       $storage_location);
    $smarty->assign('form_action', 'updata');

    assign_query_info();
    $smarty->display('storage_location_info.htm');
}
elseif ($_REQUEST['act'] == 'updata')
{
    admin_priv('storage_location_manage');
    if ($_POST['storage_location_name'] != $_POST['old_storage_location_name'])
    {
        /*检查库存位置名是否相同*/
        $is_only = $exc->is_only('storage_location_name', $_POST['storage_location_name'], $_POST['id']);

        if (!$is_only)
        {
            sys_msg(sprintf($_LANG['storage_location_name_exist'], stripslashes($_POST['storage_location_name'])), 1);
        }
    }

    /*对地址处理*/
    if (!empty($_POST['storage_location_address']))
    {
        $_POST['storage_location_address'] = $_POST['storage_location_address'];
    }

    $is_show = isset($_REQUEST['is_show']) ? intval($_REQUEST['is_show']) : 0;

    $param = "storage_location_name = '$_POST[storage_location_name]', storage_location_address = '$_POST[storage_location_address]', is_show='$is_show', sort_order='$_POST[sort_order]' ";

    if ($exc->edit($param,  $_POST['id']))
    {
        /* 清除缓存 */
        clear_cache_files();

        admin_log($_POST['storage_location_name'], 'edit', 'storage_location');

        $link[0]['text'] = $_LANG['back_list'];
        $link[0]['href'] = 'storage_location.php?act=list&' . list_link_postfix();
        $note = vsprintf($_LANG['storage_location_edit_succeed'], $_POST['storage_location_name']);
        sys_msg($note, 0, $link);
    }
    else
    {
        die($db->error());
    }
}

/*------------------------------------------------------ */
//-- 编辑库存位置名称
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_storage_location_name')
{
    check_authz_json('storage_location_manage');

    $id     = intval($_POST['id']);
    $name   = json_str_iconv(trim($_POST['val']));

    /* 检查名称是否重复 */
    if ($exc->num("storage_location_name",$name, $id) != 0)
    {
        make_json_error(sprintf($_LANG['storage_location_name_exist'], $name));
    }
    else
    {
        if ($exc->edit("storage_location_name = '$name'", $id))
        {
            admin_log($name,'edit','storage_location');
            make_json_result(stripslashes($name));
        }
        else
        {
            make_json_result(sprintf($_LANG['storage_location_edit_fail'], $name));
        }
    }
}

elseif($_REQUEST['act'] == 'add_storage_location')
{
    $storage_location = empty($_REQUEST['storage_location']) ? '' : json_str_iconv(trim($_REQUEST['storage_location']));

    if(storage_location_exists($storage_location))
    {
        make_json_error($_LANG['storage_location_name_exist']);
    }
    else
    {
        $sql = "INSERT INTO " . $ecs->table('storage_location') . "(storage_location_name)" .
               "VALUES ( '$storage_location')";

        $db->query($sql);
        $storage_location_id = $db->insert_id();

        $arr = array("id"=>$storage_location_id, "storage_location"=>$storage_location);

        make_json_result($arr);
    }
}
/*------------------------------------------------------ */
//-- 编辑排序序号
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_sort_order')
{
    check_authz_json('storage_location_manage');

    $id     = intval($_POST['id']);
    $order  = intval($_POST['val']);
    $name   = $exc->get_name($id);

    if ($exc->edit("sort_order = '$order'", $id))
    {
        admin_log(addslashes($name),'edit','storage_location');

        make_json_result($order);
    }
    else
    {
        make_json_error(sprintf($_LANG['storage_location_edit_fail'], $name));
    }
}

/*------------------------------------------------------ */
//-- 切换是否显示
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'toggle_show')
{
    check_authz_json('storage_location_manage');

    $id     = intval($_POST['id']);
    $val    = intval($_POST['val']);

    $exc->edit("is_show='$val'", $id);

    make_json_result($val);
}

/*------------------------------------------------------ */
//-- 删除库存位置
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove')
{
    check_authz_json('storage_location_manage');

    $id = intval($_GET['id']);

    $exc->drop($id);

    /* 更新租品的库存位置编号 */
    $sql = "UPDATE " .$ecs->table('goods'). " SET storage_location_id=0 WHERE storage_location_id='$id'";
    $db->query($sql);

    $url = 'storage_location.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

    ecs_header("Location: $url\n");
    exit;
}

/*------------------------------------------------------ */
//-- 排序、分页、查询
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    $storage_location_list = get_storage_locationlist();
    $smarty->assign('storage_location_list',   $storage_location_list['storage_location']);
    $smarty->assign('filter',       $storage_location_list['filter']);
    $smarty->assign('record_count', $storage_location_list['record_count']);
    $smarty->assign('page_count',   $storage_location_list['page_count']);

    make_json_result($smarty->fetch('storage_location_list.htm'), '',
        array('filter' => $storage_location_list['filter'], 'page_count' => $storage_location_list['page_count']));
}

/**
 * 获取库存位置列表
 *
 * @access  public
 * @return  array
 */
function get_storage_locationlist()
{
    $result = get_filter();
    if ($result === false)
    {
        /* 分页大小 */
        $filter = array();

        /* 记录总数以及页数 */
        if (isset($_POST['storage_location_name']))
        {
            $sql = "SELECT COUNT(*) FROM ".$GLOBALS['ecs']->table('storage_location') .' WHERE storage_location_name = \''.$_POST['storage_location_name'].'\'';
        }
        else
        {
            $sql = "SELECT COUNT(*) FROM ".$GLOBALS['ecs']->table('storage_location');
        }

        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        $filter = page_and_size($filter);

        /* 查询记录 */
        if (isset($_POST['storage_location_name']))
        {
            if(strtoupper(EC_CHARSET) == 'GBK')
            {
                $keyword = iconv("UTF-8", "gb2312", $_POST['storage_location_name']);
            }
            else
            {
                $keyword = $_POST['storage_location_name'];
            }
            $sql = "SELECT * FROM ".$GLOBALS['ecs']->table('storage_location')." WHERE storage_location_name like '%{$keyword}%' ORDER BY sort_order ASC";
        }
        else
        {
            $sql = "SELECT * FROM ".$GLOBALS['ecs']->table('storage_location')." ORDER BY sort_order ASC";
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

    return array('storage_location' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
}

?>
