<?php

/**
 * WUYI 管理中心地区管理
 * ============================================================================
 * * 
 * 网站地址: http://www.51wuyi.com；
 * ----------------------------------------------------------------------------


 * ============================================================================
 * $Author: liubo $
 * $Id: area.php 17217 2011-01-19 06:29:08Z liubo $
*/

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
include_once(ROOT_PATH . 'includes/cls_image.php');
$image = new cls_image($_CFG['bgcolor']);

$exc = new exchange($ecs->table("area"), $db, 'area_id', 'area_name');

/*------------------------------------------------------ */
//-- 地区列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    $smarty->assign('ur_here',      $_LANG['06_goods_area_list']);
    $smarty->assign('action_link',  array('text' => $_LANG['07_area_add'], 'href' => 'area.php?act=add'));
    $smarty->assign('full_page',    1);

    $area_list = get_arealist();

    $smarty->assign('area_list',   $area_list['area']);
    $smarty->assign('filter',       $area_list['filter']);
    $smarty->assign('record_count', $area_list['record_count']);
    $smarty->assign('page_count',   $area_list['page_count']);

    assign_query_info();
    $smarty->display('area_list.htm');
}

/*------------------------------------------------------ */
//-- 添加地区
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'add')
{
    /* 权限判断 */
    admin_priv('area_manage');

    $smarty->assign('ur_here',     $_LANG['07_area_add']);
    $smarty->assign('action_link', array('text' => $_LANG['06_goods_area_list'], 'href' => 'area.php?act=list'));
    $smarty->assign('form_action', 'insert');

    assign_query_info();
    $smarty->assign('area', array('sort_order'=>50, 'is_show'=>1));
    $smarty->display('area_info.htm');
}
elseif ($_REQUEST['act'] == 'insert')
{
    /*检查地区名是否重复*/
    admin_priv('area_manage');

    $is_show = isset($_REQUEST['is_show']) ? intval($_REQUEST['is_show']) : 0;

    $is_only = $exc->is_only('area_name', $_POST['area_name']);

    if (!$is_only)
    {
        sys_msg(sprintf($_LANG['areaname_exist'], stripslashes($_POST['area_name'])), 1);
    }

    /*对描述处理*/
    if (!empty($_POST['area_desc']))
    {
        $_POST['area_desc'] = $_POST['area_desc'];
    }

     /*处理图片*/
    $img_name = basename($image->upload_image($_FILES['area_logo'],'arealogo'));

     /*处理URL*/
    $site_url = sanitize_url( $_POST['site_url'] );

    /*插入数据*/

    $sql = "INSERT INTO ".$ecs->table('area')."(area_name, site_url, area_desc, area_logo, is_show, sort_order) ".
           "VALUES ('$_POST[area_name]', '$site_url', '$_POST[area_desc]', '$img_name', '$is_show', '$_POST[sort_order]')";
    $db->query($sql);

    admin_log($_POST['area_name'],'add','area');

    /* 清除缓存 */
    clear_cache_files();

    $link[0]['text'] = $_LANG['continue_add'];
    $link[0]['href'] = 'area.php?act=add';

    $link[1]['text'] = $_LANG['back_list'];
    $link[1]['href'] = 'area.php?act=list';

    sys_msg($_LANG['areaadd_succed'], 0, $link);
}

/*------------------------------------------------------ */
//-- 编辑地区
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit')
{
    /* 权限判断 */
    admin_priv('area_manage');
    $sql = "SELECT area_id, area_name, site_url, area_logo, area_desc, area_logo, is_show, sort_order ".
            "FROM " .$ecs->table('area'). " WHERE area_id='$_REQUEST[id]'";
    $area = $db->GetRow($sql);

    $smarty->assign('ur_here',     $_LANG['area_edit']);
    $smarty->assign('action_link', array('text' => $_LANG['06_goods_area_list'], 'href' => 'area.php?act=list&' . list_link_postfix()));
    $smarty->assign('area',       $area);
    $smarty->assign('form_action', 'updata');

    assign_query_info();
    $smarty->display('area_info.htm');
}
elseif ($_REQUEST['act'] == 'updata')
{
    admin_priv('area_manage');
    if ($_POST['area_name'] != $_POST['old_areaname'])
    {
        /*检查地区名是否相同*/
        $is_only = $exc->is_only('area_name', $_POST['area_name'], $_POST['id']);

        if (!$is_only)
        {
            sys_msg(sprintf($_LANG['areaname_exist'], stripslashes($_POST['area_name'])), 1);
        }
    }

    /*对描述处理*/
    if (!empty($_POST['area_desc']))
    {
        $_POST['area_desc'] = $_POST['area_desc'];
    }

    $is_show = isset($_REQUEST['is_show']) ? intval($_REQUEST['is_show']) : 0;
     /*处理URL*/
    $site_url = sanitize_url( $_POST['site_url'] );

    /* 处理图片 */
    $img_name = basename($image->upload_image($_FILES['area_logo'],'arealogo'));
    $param = "area_name = '$_POST[area_name]',  site_url='$site_url', area_desc='$_POST[area_desc]', is_show='$is_show', sort_order='$_POST[sort_order]' ";
    if (!empty($img_name))
    {
        //有图片上传
        $param .= " ,area_logo = '$img_name' ";
    }

    if ($exc->edit($param,  $_POST['id']))
    {
        /* 清除缓存 */
        clear_cache_files();

        admin_log($_POST['area_name'], 'edit', 'area');

        $link[0]['text'] = $_LANG['back_list'];
        $link[0]['href'] = 'area.php?act=list&' . list_link_postfix();
        $note = vsprintf($_LANG['areaedit_succed'], $_POST['area_name']);
        sys_msg($note, 0, $link);
    }
    else
    {
        die($db->error());
    }
}

/*------------------------------------------------------ */
//-- 编辑地区名称
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_area_name')
{
    check_authz_json('area_manage');

    $id     = intval($_POST['id']);
    $name   = json_str_iconv(trim($_POST['val']));

    /* 检查名称是否重复 */
    if ($exc->num("area_name",$name, $id) != 0)
    {
        make_json_error(sprintf($_LANG['areaname_exist'], $name));
    }
    else
    {
        if ($exc->edit("area_name = '$name'", $id))
        {
            admin_log($name,'edit','area');
            make_json_result(stripslashes($name));
        }
        else
        {
            make_json_result(sprintf($_LANG['areaedit_fail'], $name));
        }
    }
}

elseif($_REQUEST['act'] == 'add_area')
{
    $area = empty($_REQUEST['area']) ? '' : json_str_iconv(trim($_REQUEST['area']));

    if(area_exists($area))
    {
        make_json_error($_LANG['area_name_exist']);
    }
    else
    {
        $sql = "INSERT INTO " . $ecs->table('area') . "(area_name)" .
               "VALUES ( '$area')";

        $db->query($sql);
        $area_id = $db->insert_id();

        $arr = array("id"=>$area_id, "area"=>$area);

        make_json_result($arr);
    }
}
/*------------------------------------------------------ */
//-- 编辑排序序号
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_sort_order')
{
    check_authz_json('area_manage');

    $id     = intval($_POST['id']);
    $order  = intval($_POST['val']);
    $name   = $exc->get_name($id);

    if ($exc->edit("sort_order = '$order'", $id))
    {
        admin_log(addslashes($name),'edit','area');

        make_json_result($order);
    }
    else
    {
        make_json_error(sprintf($_LANG['areaedit_fail'], $name));
    }
}

/*------------------------------------------------------ */
//-- 切换是否显示
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'toggle_show')
{
    check_authz_json('area_manage');

    $id     = intval($_POST['id']);
    $val    = intval($_POST['val']);

    $exc->edit("is_show='$val'", $id);

    make_json_result($val);
}

/*------------------------------------------------------ */
//-- 删除地区
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove')
{
    check_authz_json('area_manage');

    $id = intval($_GET['id']);

    /* 删除该地区的图标 */
    $sql = "SELECT area_logo FROM " .$ecs->table('area'). " WHERE area_id = '$id'";
    $logo_name = $db->getOne($sql);
    if (!empty($logo_name))
    {
        @unlink(ROOT_PATH . DATA_DIR . '/arealogo/' .$logo_name);
    }

    $exc->drop($id);

    /* 更新租品的地区编号 */
    $sql = "UPDATE " .$ecs->table('goods'). " SET area_id=0 WHERE area_id='$id'";
    $db->query($sql);

    $url = 'area.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

    ecs_header("Location: $url\n");
    exit;
}

/*------------------------------------------------------ */
//-- 删除地区图片
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'drop_logo')
{
    /* 权限判断 */
    admin_priv('area_manage');
    $area_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    /* 取得logo名称 */
    $sql = "SELECT area_logo FROM " .$ecs->table('area'). " WHERE area_id = '$area_id'";
    $logo_name = $db->getOne($sql);

    if (!empty($logo_name))
    {
        @unlink(ROOT_PATH . DATA_DIR . '/arealogo/' .$logo_name);
        $sql = "UPDATE " .$ecs->table('area'). " SET area_logo = '' WHERE area_id = '$area_id'";
        $db->query($sql);
    }
    $link= array(array('text' => $_LANG['area_edit_lnk'], 'href' => 'area.php?act=edit&id=' . $area_id), array('text' => $_LANG['area_list_lnk'], 'href' => 'area.php?act=list'));
    sys_msg($_LANG['drop_area_logo_success'], 0, $link);
}

/*------------------------------------------------------ */
//-- 排序、分页、查询
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    $area_list = get_arealist();
    $smarty->assign('area_list',   $area_list['area']);
    $smarty->assign('filter',       $area_list['filter']);
    $smarty->assign('record_count', $area_list['record_count']);
    $smarty->assign('page_count',   $area_list['page_count']);

    make_json_result($smarty->fetch('area_list.htm'), '',
        array('filter' => $area_list['filter'], 'page_count' => $area_list['page_count']));
}

/**
 * 获取地区列表
 *
 * @access  public
 * @return  array
 */
function get_arealist()
{
    $result = get_filter();
    if ($result === false)
    {
        /* 分页大小 */
        $filter = array();

        /* 记录总数以及页数 */
        if (isset($_POST['area_name']))
        {
            $sql = "SELECT COUNT(*) FROM ".$GLOBALS['ecs']->table('area') .' WHERE area_name = \''.$_POST['area_name'].'\'';
        }
        else
        {
            $sql = "SELECT COUNT(*) FROM ".$GLOBALS['ecs']->table('area');
        }

        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        $filter = page_and_size($filter);

        /* 查询记录 */
        if (isset($_POST['area_name']))
        {
            if(strtoupper(EC_CHARSET) == 'GBK')
            {
                $keyword = iconv("UTF-8", "gb2312", $_POST['area_name']);
            }
            else
            {
                $keyword = $_POST['area_name'];
            }
            $sql = "SELECT * FROM ".$GLOBALS['ecs']->table('area')." WHERE area_name like '%{$keyword}%' ORDER BY sort_order ASC";
        }
        else
        {
            $sql = "SELECT * FROM ".$GLOBALS['ecs']->table('area')." ORDER BY sort_order ASC";
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
        $area_logo = empty($rows['area_logo']) ? '' :
            '<a href="../' . DATA_DIR . '/arealogo/'.$rows['area_logo'].'" target="_brank"><img src="images/picflag.gif" width="16" height="16" border="0" alt='.$GLOBALS['_LANG']['area_logo'].' /></a>';
        $site_url   = empty($rows['site_url']) ? 'N/A' : '<a href="'.$rows['site_url'].'" target="_brank">'.$rows['site_url'].'</a>';

        $rows['area_logo'] = $area_logo;
        $rows['site_url']   = $site_url;

        $arr[] = $rows;
    }

    return array('area' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
}

?>
