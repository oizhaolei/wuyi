<?php

/**
 * WUYI 颜色列表
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

if ((DEBUG_MODE & 2) != 2)
{
    $smarty->caching = true;
}

/*------------------------------------------------------ */
//-- INPUT
/*------------------------------------------------------ */

/* 获得请求的分类 ID */
if (!empty($_REQUEST['id']))
{
    $color_id = intval($_REQUEST['id']);
}
if (!empty($_REQUEST['color']))
{
    $color_id = intval($_REQUEST['color']);
}
if (empty($color_id))
{
    /* 缓存编号 */
    $cache_id = sprintf('%X', crc32($_CFG['lang']));
    if (!$smarty->is_cached('color_list.dwt', $cache_id))
    {
        assign_template();
        $position = assign_ur_here('', $_LANG['all_color']);
        $smarty->assign('page_title',      $position['title']);    // 页面标题
        $smarty->assign('ur_here',         $position['ur_here']);  // 当前位置

        $smarty->assign('categories',      get_categories_tree()); // 分类树
        $smarty->assign('helps',           get_shop_help());       // 网店帮助
        $smarty->assign('top_goods',       get_top10());           // 出租排行

        $smarty->assign('color_list', get_colors());
    }
    $smarty->display('color_list.dwt', $cache_id);
    exit();
}

/* 初始化分页信息 */
$page = !empty($_REQUEST['page'])  && intval($_REQUEST['page'])  > 0 ? intval($_REQUEST['page'])  : 1;
$size = !empty($_CFG['page_size']) && intval($_CFG['page_size']) > 0 ? intval($_CFG['page_size']) : 10;
$cate = !empty($_REQUEST['cat'])   && intval($_REQUEST['cat'])   > 0 ? intval($_REQUEST['cat'])   : 0;

/* 排序、显示方式以及类型 */
$default_display_type = $_CFG['show_order_type'] == '0' ? 'list' : ($_CFG['show_order_type'] == '1' ? 'grid' : 'text');
$default_sort_order_method = $_CFG['sort_order_method'] == '0' ? 'DESC' : 'ASC';
$default_sort_order_type   = $_CFG['sort_order_type'] == '0' ? 'goods_id' : ($_CFG['sort_order_type'] == '1' ? 'shop_price' : 'last_update');

$sort  = (isset($_REQUEST['sort'])  && in_array(trim(strtolower($_REQUEST['sort'])), array('goods_id', 'shop_price', 'last_update'))) ? trim($_REQUEST['sort'])  : $default_sort_order_type;
$order = (isset($_REQUEST['order']) && in_array(trim(strtoupper($_REQUEST['order'])), array('ASC', 'DESC')))                              ? trim($_REQUEST['order']) : $default_sort_order_method;
$display  = (isset($_REQUEST['display']) && in_array(trim(strtolower($_REQUEST['display'])), array('list', 'grid', 'text'))) ? trim($_REQUEST['display'])  : (isset($_COOKIE['ECS']['display']) ? $_COOKIE['ECS']['display'] : $default_display_type);
$display  = in_array($display, array('list', 'grid', 'text')) ? $display : 'text';
setcookie('ECS[display]', $display, gmtime() + 86400 * 7);

/*------------------------------------------------------ */
//-- PROCESSOR
/*------------------------------------------------------ */

/* 页面的缓存ID */
$cache_id = sprintf('%X', crc32($color_id . '-' . $display . '-' . $sort . '-' . $order . '-' . $page . '-' . $size . '-' . $_SESSION['user_rank'] . '-' . $_CFG['lang'] . '-' . $cate));

if (!$smarty->is_cached('color.dwt', $cache_id))
{
    $color_info = get_color_info($color_id);

    if (empty($color_info))
    {
        ecs_header("Location: ./\n");
        exit;
    }

    $smarty->assign('data_dir',    DATA_DIR);
    $smarty->assign('keywords',    htmlspecialchars($color_info['color_desc']));
    $smarty->assign('description', htmlspecialchars($color_info['color_desc']));

    /* 赋值固定内容 */
    assign_template();
    $position = assign_ur_here($cate, $color_info['color_name']);
    $smarty->assign('page_title',     $position['title']);   // 页面标题
    $smarty->assign('ur_here',        $position['ur_here']); // 当前位置
    $smarty->assign('color_id',       $color_id);
    $smarty->assign('category',       $cate);

    $smarty->assign('categories',     get_categories_tree());        // 分类树
    $smarty->assign('helps',          get_shop_help());              // 网店帮助
    $smarty->assign('top_goods',      get_top10());                  // 出租排行
    $smarty->assign('show_marketprice', $_CFG['show_marketprice']);
    $smarty->assign('color_cat_list', color_related_cat($color_id)); // 相关分类
    $smarty->assign('feed_url',       ($_CFG['rewrite'] == 1) ? "feed-b$color_id.xml" : 'feed.php?color=' . $color_id);

    /* 调查 */
    $vote = get_vote();
    if (!empty($vote))
    {
        $smarty->assign('vote_id',     $vote['id']);
        $smarty->assign('vote',        $vote['content']);
    }

    $smarty->assign('best_goods',      color_recommend_goods('best', $color_id, $cate));
    $smarty->assign('promotion_goods', color_recommend_goods('promote', $color_id, $cate));
    $smarty->assign('color',           $color_info);
    $smarty->assign('promotion_info', get_promotion_info());

    $count = goods_count_by_color($color_id, $cate);

    $goodslist = color_get_goods($color_id, $cate, $size, $page, $sort, $order);

    if($display == 'grid')
    {
        if(count($goodslist) % 2 != 0)
        {
            $goodslist[] = array();
        }
    }
    $smarty->assign('goods_list',      $goodslist);
    $smarty->assign('script_name', 'color');

    assign_pager('color',              $cate, $count, $size, $sort, $order, $page, '', $color_id, 0, 0, $display); // 分页
    assign_dynamic('color'); // 动态内容
}

$smarty->display('color.dwt', $cache_id);

/*------------------------------------------------------ */
//-- PRIVATE FUNCTION
/*------------------------------------------------------ */

/**
 * 获得指定颜色的详细信息
 *
 * @access  private
 * @param   integer $id
 * @return  void
 */
function get_color_info($id)
{
    $sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('color') . " WHERE color_id = '$id'";

    return $GLOBALS['db']->getRow($sql);
}

/**
 * 获得指定颜色下的推荐和促销租品
 *
 * @access  private
 * @param   string  $type
 * @param   integer $color
 * @return  array
 */
function color_recommend_goods($type, $color, $cat = 0)
{
    static $result = NULL;

    $time = gmtime();

    if ($result === NULL)
    {
        if ($cat > 0)
        {
            $cat_where = "AND " . get_children($cat);
        }
        else
        {
            $cat_where = '';
        }

        $sql = 'SELECT g.goods_id, g.goods_name, g.market_price, g.shop_price AS org_price, g.promote_price, ' .
                    "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, ".
                    'promote_start_date, promote_end_date, g.goods_brief, g.goods_thumb, goods_img, ' .
                    'b.color_name, g.is_best, g.is_new, g.is_hot, g.is_promote ' .
                'FROM ' . $GLOBALS['ecs']->table('goods') . ' AS g ' .
                'LEFT JOIN ' . $GLOBALS['ecs']->table('color') . ' AS b ON b.color_id = g.color_id ' .
                'LEFT JOIN ' . $GLOBALS['ecs']->table('member_price') . ' AS mp '.
                    "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' ".
                "WHERE g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 AND g.color_id = '$color' AND " .
                    "(g.is_best = 1 OR (g.is_promote = 1 AND promote_start_date <= '$time' AND ".
                    "promote_end_date >= '$time')) $cat_where" .
               'ORDER BY g.sort_order, g.last_update DESC';
        $result = $GLOBALS['db']->getAll($sql);
    }

    /* 取得每一项的数量限制 */
    $num = 0;
    $type2lib = array('best'=>'recommend_best', 'new'=>'recommend_new', 'hot'=>'recommend_hot', 'promote'=>'recommend_promotion');
    $num = get_library_number($type2lib[$type]);

    $idx = 0;
    $goods = array();
    foreach ($result AS $row)
    {
        if ($idx >= $num)
        {
            break;
        }

        if (($type == 'best' && $row['is_best'] == 1) ||
            ($type == 'promote' && $row['is_promote'] == 1 &&
            $row['promote_start_date'] <= $time && $row['promote_end_date'] >= $time))
        {
            if ($row['promote_price'] > 0)
            {
                $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                $goods[$idx]['promote_price'] = $promote_price > 0 ? price_format($promote_price) : '';
            }
            else
            {
                $goods[$idx]['promote_price'] = '';
            }

            $goods[$idx]['id']           = $row['goods_id'];
            $goods[$idx]['name']         = $row['goods_name'];
            $goods[$idx]['brief']        = $row['goods_brief'];
            $goods[$idx]['color_name']   = $row['color_name'];
            $goods[$idx]['short_style_name']   = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
                                               sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
            $goods[$idx]['market_price'] = price_format($row['market_price']);
            $goods[$idx]['shop_price']   = price_format($row['shop_price']);
            $goods[$idx]['thumb']        = get_image_path($row['goods_id'], $row['goods_thumb'], true);
            $goods[$idx]['goods_img']    = get_image_path($row['goods_id'], $row['goods_img']);
            $goods[$idx]['url']          = build_uri('goods', array('gid' => $row['goods_id']), $row['goods_name']);

            $idx++;
        }
    }

    return $goods;
}

/**
 * 获得指定的颜色下的租品总数
 *
 * @access  private
 * @param   integer     $color_id
 * @param   integer     $cate
 * @return  integer
 */
function goods_count_by_color($color_id, $cate = 0)
{
    $sql = 'SELECT COUNT(*) FROM ' .$GLOBALS['ecs']->table('goods'). ' AS g '.
            "WHERE color_id = '$color_id' AND g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0";

    if ($cate > 0)
    {
        $sql .= " AND " . get_children($cate);
    }

    return $GLOBALS['db']->getOne($sql);
}

/**
 * 获得颜色下的租品
 *
 * @access  private
 * @param   integer  $color_id
 * @return  array
 */
function color_get_goods($color_id, $cate, $size, $page, $sort, $order)
{
    $cate_where = ($cate > 0) ? 'AND ' . get_children($cate) : '';

    /* 获得租品列表 */
    $sql = 'SELECT g.goods_id, g.goods_name, g.market_price, g.shop_price AS org_price, ' .
                "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, g.promote_price, " .
                'g.promote_start_date, g.promote_end_date, g.goods_brief, g.goods_thumb , g.goods_img ' .
            'FROM ' . $GLOBALS['ecs']->table('goods') . ' AS g ' .
            'LEFT JOIN ' . $GLOBALS['ecs']->table('member_price') . ' AS mp ' .
                "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' " .
            "WHERE g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 AND g.color_id = '$color_id' $cate_where".
            "ORDER BY $sort $order";

    $res = $GLOBALS['db']->selectLimit($sql, $size, ($page - 1) * $size);

    $arr = array();
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        if ($row['promote_price'] > 0)
        {
            $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
        }
        else
        {
            $promote_price = 0;
        }

        $arr[$row['goods_id']]['goods_id']      = $row['goods_id'];
        if($GLOBALS['display'] == 'grid')
        {
            $arr[$row['goods_id']]['goods_name']       = $GLOBALS['_CFG']['goods_name_length'] > 0 ? sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
        }
        else
        {
            $arr[$row['goods_id']]['goods_name']       = $row['goods_name'];
        }
        $arr[$row['goods_id']]['market_price']  = price_format($row['market_price']);
        $arr[$row['goods_id']]['shop_price']    = price_format($row['shop_price']);
        $arr[$row['goods_id']]['promote_price'] = ($promote_price > 0) ? price_format($promote_price) : '';
        $arr[$row['goods_id']]['goods_brief']   = $row['goods_brief'];
        $arr[$row['goods_id']]['goods_thumb']   = get_image_path($row['goods_id'], $row['goods_thumb'], true);
        $arr[$row['goods_id']]['goods_img']     = get_image_path($row['goods_id'], $row['goods_img']);
        $arr[$row['goods_id']]['url']           = build_uri('goods', array('gid' => $row['goods_id']), $row['goods_name']);
    }

    return $arr;
}

/**
 * 获得与指定颜色相关的分类
 *
 * @access  public
 * @param   integer $color
 * @return  array
 */
function color_related_cat($color)
{
    $arr[] = array('cat_id' => 0,
                 'cat_name' => $GLOBALS['_LANG']['all_category'],
                 'url'      => build_uri('color', array('bid' => $color), $GLOBALS['_LANG']['all_category']));

    $sql = "SELECT c.cat_id, c.cat_name, COUNT(g.goods_id) AS goods_count FROM ".
            $GLOBALS['ecs']->table('category'). " AS c, ".
            $GLOBALS['ecs']->table('goods') . " AS g " .
            "WHERE g.color_id = '$color' AND c.cat_id = g.cat_id ".
            "GROUP BY g.cat_id";
    $res = $GLOBALS['db']->query($sql);

    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $row['url'] = build_uri('color', array('cid' => $row['cat_id'], 'bid' => $color), $row['cat_name']);
        $arr[] = $row;
    }

    return $arr;
}

?>