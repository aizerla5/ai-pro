<?php
// +----------------------------------------------------------------------
// | AiCMS
// +----------------------------------------------------------------------
// | Copyright (c) 2017
// +----------------------------------------------------------------------
// | Author: Ai Ye
// +----------------------------------------------------------------------
namespace app\home\controller;

use think\Db;
use think\captcha\Captcha;
use think\Validate;

class Login extends Base
{
    public function index()
    {
        $this->assign('site_title', '登录');
        if ($this->user && isset($this->user['member_list_id'])) {
            if (trim($this->user['member_list_username']) != '') {
                $this->redirect('home/Account/index');
            } else {
                return $this->fetch('account:login');
            }
        } else {
            return $this->fetch('account:login');
        }
    }

    //登录验证
    public function login()
    {
        $member_list_username = input('member_list_username');
        $member_list_pwd = input('member_list_pwd');
        $remember = input('remember', 0, 'intval');
        $rule = [
            ['member_list_username', 'require', '{%Username was required}'],
            ['member_list_pwd', 'require', '{%Password was required}'],
        ];
        $validate = new Validate($rule);
        $rst = $validate->check(array('member_list_username' => $member_list_username, 'member_list_pwd' => $member_list_pwd));
        if (true !== $rst) {
            $this->error($validate->getError());
        }
        //邮箱登陆
        if (strpos($member_list_username, "@") > 0) {
            $where['member_list_email'] = $member_list_username;
        } else {
            $where['member_list_username'] = $member_list_username;
        }
        $member = Db::name("member_list")->where($where)->find();
        if (!$member || encrypt_password($member_list_pwd, $member['member_list_salt']) !== $member['member_list_pwd']) {
            $this->error(lang('username or pwd incorrect'));
        } else {
            if ($member['member_list_open'] == 0) {
                $this->error(lang('user disabled'));
            }
            //更新字段
            $data = array(
                'last_login_time' => time(),
                'last_login_ip' => request()->ip(),
            );
            Db::name("member_list")->where(array('member_list_id' => $member["member_list_id"]))->update($data);
            session('hid', $member['member_list_id']);
            session('user', $member);
            if ($remember && $member['user_status']) {
                //更新cookie
                cookie('ai_logged_user', jiami("{$member['member_list_id']}.{$data['last_login_time']}"));
            }

            //根据需要决定是否同步后台登录状态
            $admin = Db::name('admin')->where('member_id', $member['member_list_id'])->find();
            if ($admin) {
                // 记录登录
                $auth = array(
                    'aid' => $admin['admin_id'],
                    'admin_avatar' => $admin['admin_avatar'],
                    'admin_last_change_pwd_time' => $admin['admin_changepwd'],
                    'admin_realname' => $admin['admin_realname'],
                    'admin_username' => $admin['admin_username'],
                    'member_id' => $admin['member_id'],
                    'admin_last_ip' => $admin['admin_last_ip'],
                    'admin_last_time' => $admin['admin_last_time']
                );
                session('admin_auth', $auth);
                session('admin_auth_sign', data_signature($auth));
            }

            $this->success(lang('login success'), url('home/Login/check_active'));
        }
    }

    /*public function forgot_pwd()
    {
        $this->assign('site_title', '忘记密码？');
        return $this->fetch('account:forgot_pwd');
    }

    public function run_forgot()
    {
        if (request()->isPost()) {
            $member_list_email = input('member_list_email');
            $verify = new Captcha ();
            if (!$verify->check(input('verify'), 'reg')) {
                $this->error(lang('verifiy incorrect'));
            }
            $rule = [
                ['member_list_email', 'require|email', '{%email empty}|{%email format incorrect}']
            ];
            $validate = new Validate($rule);
            $rst = $validate->check(['member_list_email' => $member_list_email]);
            if (true !== $rst) {
                $this->error(join('|', $validate->getError()));
            }
            $find_user = Db::name("member_list")->where(["member_list_email" => $member_list_email])->find();
            if ($find_user) {
                //发送重置密码邮件
                $activekey = md5($find_user['member_list_id'] . time() . uniqid());//激活码
                $result = Db::name("member_list")->where(array("member_list_id" => $find_user['member_list_id']))->update(["user_activation_key" => $activekey]);
                if (!$result) {
                    $this->error(lang('activation code generation failed'));
                }
                //生成重置链接
                $url = url('home/Login/pwd_reset', ["hash" => $activekey], "", true);
                $template = lang('emal text') .
                    <<<hello
								<a href="http://#link#">http://#link#</a>
hello;
                $content = str_replace(array('http://#link#', '#username#'), [$url, $find_user['member_list_username']], $template);
                $send_result = sendMail($member_list_email, lang('pwd reset'), $content);
                if ($send_result['error']) {
                    $this->error(lang('send pwd reset email failed'));
                } else {
                    $this->success(lang('send pwd reset email success'), url('home/Index/index'));
                }
            } else {
                $this->error(lang('member not exist'));
            }
        }
    }

    public function pwd_reset()
    {
        $this->assign('site_title', '忘记密码？');
        $hash = input("hash");
        $find_user = Db::name("member_list")->where(array("user_activation_key" => $hash))->find();
        if (empty($find_user)) {
            $this->error(lang('pwd reset hash incorrect'), url('home/Index/index'));
        } else {
            $this->assign("hash", $hash);
            return $this->fetch('account:pwd_reset');
        }
    }

    public function update_password()
    {
        if (request()->isPost()) {
            $verify = new Captcha();
            if (!$verify->check(input('verify'), 'reg')) {
                $this->error(lang('verifiy incorrect'));
            }
            $rule = [
                ['password', 'require|length:5,20', '{%pwd empty}|{%pwd length}'],
                ['repassword', 'require|confirm:password', '{%repassword empty}|{%repassword incorrect}'],
                ['hash', 'require', '{%pwd reset hash empty}'],
            ];
            $validate = new Validate($rule);
            $rst = $validate->check(array('password' => input('password'), 'hash' => input('hash'), 'repassword' => input('repassword')));
            if (true !== $rst) {
                $this->error(join('|', $validate->getError()));
            } else {
                $password = input('password');
                $hash = input('hash');
                $member_list_salt = random(10);
                $member_list_pwd = encrypt_password($password, $member_list_salt);
                $result = Db::name("member_list")->where(array("user_activation_key" => $hash))->update(array('member_list_pwd' => $member_list_pwd, 'user_activation_key' => '', 'member_list_salt' => $member_list_salt));
                if ($result) {
                    $this->success(lang('pwd reset success'), url("home/Login/index"));
                } else {
                    $this->error(lang('pwd reset failed'));
                }
            }
        }
    }

    public function check_active()
    {
        $this->check_login();
        if ($this->user['user_status']) {
            $this->redirect(__ROOT__ . "/");
        } else {
            //判断是否激活
            return $this->fetch('user:active');
        }
    }

    //重发激活邮件
    public function resend()
    {
        $this->check_login();
        $current_user = $this->user;
        if ($current_user['user_status'] == 0) {
            if ($current_user['member_list_email']) {
                $active_options = get_active_options();
                $activekey = md5($current_user['member_list_id'] . time() . uniqid());//激活码
                $result = Db::name('member_list')->where(array("member_list_id" => $current_user['member_list_id']))->update(array("user_activation_key" => $activekey));
                if (!$result) {
                    $this->error(lang('activation code generation failed'));
                }
                //生成激活链接
                $url = url('home/Register/active', array("hash" => $activekey), "", true);
                $template = $active_options['email_tpl'];
                $content = str_replace(array('http://#link#', '#username#'), array($url, $current_user['member_list_username']), $template);
                $send_result = sendMail($current_user['member_list_email'], $active_options['email_title'], $content);
                if ($send_result['error']) {
                    $this->error(lang('send active email failed'));
                } else {
                    $this->success(lang('send active email success'), url('home/Login/index'));
                }
            } else {
                $this->error(lang('no registered email'), url('home/Login/index'));
            }
        } else {
            $this->error(lang('activated'), url('home/Index/index'));
        }
    }*/
}