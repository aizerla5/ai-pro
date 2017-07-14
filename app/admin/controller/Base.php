<?php
// +----------------------------------------------------------------------
// | AiCMS
// +----------------------------------------------------------------------
// | Copyright (c) 2017
// +----------------------------------------------------------------------
// | Author: Ai Ye
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\controller\Common;
use app\admin\model\AuthRule;

class Base extends Common
{
    /** @var  array|null Backend User */
    protected $user;

    public function _initialize()
    {
        parent::_initialize();
        // check backend user login ?!
        if (!$this->check_admin_login()) $this->redirect('admin/Login/login');

        $auth = new AuthRule();
        $id_curr = $auth->get_url_id();
        if (!$auth->check_auth($id_curr)) $this->error('没有权限', url('admin/Index/index'));
        //获取有权限的菜单tree
        $menus = $auth->get_admin_menus();
        $this->assign('menus', $menus);
        //当前方法倒推到顶级菜单ids数组
        $menus_curr = $auth->get_admin_parents($id_curr);
        $this->assign('menus_curr', $menus_curr);
        //取当前操作菜单父节点下菜单 当前菜单id(仅显示状态)
        $menus_child = $auth->get_admin_parent_menus($id_curr);
        $this->assign('menus_child', $menus_child);
        $this->assign('id_curr', $id_curr);
        $this->assign('avatar', session('backend_user.admin_avatar'));
    }
}

/**
 * Get client ip.
 *
 * @return string
 */
function get_client_ip()
{
    if (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    } else {
        // for php-cli(phpunit etc.)
        $ip = defined('PHPUNIT_RUNNING') ? '127.0.0.1' : gethostbyname(gethostname());
    }

    return filter_var($ip, FILTER_VALIDATE_IP) ?: '127.0.0.1';
}