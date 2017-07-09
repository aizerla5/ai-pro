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
use app\admin\model\Admin as AdminModel;
use think\captcha\Captcha;

class Login extends Common
{
    const VERIFY_ID = 'login_verify_unique';

    protected function _initialize()
    {
        parent::_initialize();
    }

    public function login()
    {
        if ($this->check_admin_login()) $this->redirect('admin/Index/index');
        return $this->fetch();
    }

    public function verify()
    {
        if ($this->check_admin_login()) $this->redirect('admin/Index/index');
        return $this->verify_build(self::VERIFY_ID);
    }

    public function run_login()
    {
        if (!request()->isAjax()) {
            $this->error(lang('Please use a normal form of submission'), url('admin/Login/login'));
        } else {
            if (config('geetest.geetest_on')) {
                if (!geetest_check(input('post.'))) {
                    $this->error('验证不通过', url('admin/Login/login'));
                };
            } else {
                $verify = new Captcha();
                if (!$verify->check(input('verify'), self::VERIFY_ID)) {
                    $this->error(lang('The captcha code is incorrect!'), url('admin/Login/login'));
                }
            }
            $admin_username = input('admin_username');
            $password = input('admin_pwd');
            $isRemember = input('remember_me');
            $admin = new AdminModel();
            if ($admin->login($admin_username, $password, $isRemember)) {
                $this->success(lang('Congratulations on your successful Login'), url('admin/Index/index'));
            } else {
                $this->error($admin->getError(), url('admin/Login/login'));
            }
        }
    }

    public function logout()
    {
        session('admin_auth', null);
        session('admin_auth_sign', null);
        cookie(self::VERIFY_ID, null);
        $this->redirect('admin/Login/login');
    }
}