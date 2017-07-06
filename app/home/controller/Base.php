<?php
// +----------------------------------------------------------------------
// | AiCMS
// +----------------------------------------------------------------------
// | Copyright (c) 2017
// +----------------------------------------------------------------------
// | Author: Ai Ye
// +----------------------------------------------------------------------
namespace app\home\controller;

use app\common\controller\Common;
use app\admin\model\Options;
use think\Db;

class Base extends Common
{
    protected $view;
    protected $user;
    protected $themePath;

    protected function _initialize()
    {
        parent::_initialize();
        //主题
        $site_options = Options::get_options('site_options', $this->lang);
        $site_options['site_tongji'] = htmlspecialchars_decode($site_options['site_tongji']);
        $site_options['site_copyright'] = htmlspecialchars_decode($site_options['site_copyright']);
        if (request()->isMobile()) {
            $theme = $site_options['site_tpl_m'] ?: $site_options['site_tpl'];
        } else {
            $theme = $site_options['site_tpl'];
        }
        $this->view = $this->view->config('view_path', APP_PATH . request()->module() . '/view/' . $theme . '/');
        $themePath = __ROOT__ . '/app/home/view/' . $theme . '/';
        $this->assign($site_options);
        $this->assign('theme', $themePath);
        $address = '';
        $this->user = [];
        $uid = session('hid');
        if (empty($uid)) {
            //检测cookies
            $cookie = cookie('ai_logged_user');//'id'.'时间'
            $cookie = explode(".", jiemi($cookie));
            $uid = empty($cookie[0]) ? 0 : $cookie[0];
            if ($uid && !empty($cookie[1])) {
                //判断是否存在此用户
                $member = Db::name("member_list")->find($uid);
                if ($member && (time() - intval($cookie[1])) < config('cookie.expire')) {
                    //更新字段
                    $data = [
                        'last_login_time' => time(),
                        'last_login_ip' => request()->ip()
                    ];
                    Db::name("member_list")->where(['member_list_id' => $member["member_list_id"]])->update($data);
                    $member['last_login_time'] = $data['last_login_time'];
                    $member['last_login_ip'] = $data['last_login_ip'];
                    //设置session
                    session('hid', $member['member_list_id']);
                    session('user', $member);
                }
            }
        }
        $is_admin = false;
        if (session('hid')) {
            $this->user = Db::name('member_list')->find(session('hid'));
            if (!empty($this->user['member_list_province'])) {
                $rst = Db::name('region')->field('name')->find($this->user['member_list_province']);
                $address .= $rst ? $rst['name'] . lang('province') : '';
            }
            if (!empty($this->user['member_list_city'])) {
                $rst = Db::name('region')->field('name')->find($this->user['member_list_city']);
                $address .= $rst ? $rst['name'] . lang('city') : '';
            }
            if (!empty($this->user['member_list_town'])) {
                $rst = Db::name('region')->field('name')->find($this->user['member_list_town']);
                $address .= $rst ? $rst['name'] : '';
            }
            //判断是否为管理员
            $admin = Db::name('admin')->where('member_id', $this->user['member_list_id'])->find();
            if ($admin) {
                $is_admin = true;
            }
            $this->user['address'] = $address;
        }
        $this->assign("user", $this->user);
        $this->assign("is_admin", $is_admin);
    }

    /**
     * 检查用户登录
     */
    protected function check_login()
    {
        if (!session('hid')) {
//            $this->redirect(url('home/Oauth/login', ['type' => 'weixin']));
            $this->redirect(url('home/Login/index'));
        }
    }

    /**
     * 检查操作频率
     * @param int $t_check 距离最后一次操作的时长
     */
    protected function check_last_action($t_check)
    {
        $action = MODULE_NAME . "-" . CONTROLLER_NAME . "-" . ACTION_NAME;
        $time = time();
        $action_s = session('last_action.action');
        if (!empty($action_s) && $action = $action_s) {
            $t = $time - session('last_action.time');
            if ($t_check > $t) {
                $this->error(lang('frequent operation'));
            } else {
                session('last_action.time', $time);
            }
        } else {
            session('last_action.action', $action);
            session('last_action.time', $time);
        }
    }

    /**
     * @param $enName
     * @return string
     */
    protected function _get_page_image($enName)
    {
        if (trim($enName) == '') return '';
        $page = Db::name('menu')->field(['menu_img', 'menu_name'])->where('menu_enname', $enName)->find();
        if (isset($page['menu_img']) && trim($page['menu_img']) != '') {
            $this->assign('bgImage', $page['menu_img']);
        }
        if (isset($page['menu_name']) && trim($page['menu_name']) != '') {
            $this->assign('page_title', $page['menu_name']);
        }
    }

    protected function getMonthLastDate()
    {
        $firstDate = date('Y-m-01', strtotime(date('Y-m-d')));
        $lastDate = date('Y-m-d 23:59:59', strtotime("$firstDate +1 month -1 day"));
        return $lastDate;
    }
}