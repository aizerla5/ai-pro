<?php
// +----------------------------------------------------------------------
// | AiCMS
// +----------------------------------------------------------------------
// | Copyright (c) 2017
// +----------------------------------------------------------------------
// | Author: Ai Ye
// +----------------------------------------------------------------------
namespace app\home\controller;

use app\common\behavior\Xss;
use think\Db;
use think\captcha\Captcha;
use think\Validate;

class Register extends Base
{

    public function index()
    {
        $this->assign('site_title', '注册');
        if ($this->user && isset($this->user['member_list_id']) && trim($this->user['member_list_username']) == '') {
            $this->redirect(__ROOT__ . "/");
        } else {
            return $this->view->fetch('account:register');
        }
    }

    //验证码
    public function verify()
    {
        if ($this->user && isset($this->user['member_list_id']) && trim($this->user['member_list_username']) == '') {
            $this->redirect(__ROOT__ . "/");
        }
        ob_end_clean();
        $verify = new Captcha (config('verify'));
        return $verify->entry('reg');
    }

    public function save()
    {
        if (request()->isPost()) {
            $member_list_username = input('member_list_username');
            $member_list_email = input('member_list_email');
            $password = input('password');
            $repassword = input('repassword');
            $verify = input('verify');
            $verify_obj = new Captcha ();
            if (!$verify_obj->check($verify, 'reg')) {
                $this->error(lang('verifiy incorrect'));
            }
            if (!$this->is_email($member_list_email)) {
                $this->error('不是邮箱格式，无法通过验证！');
            }
            $rule = [
                ['member_list_email', 'require|email', '{%email empty}|{%email format incorrect}'],
                ['password', 'require|length:5,20', '{%pwd empty}|{%pwd length}'],
                ['member_list_username', 'require', '{%username empty}'],
                ['repassword', 'require|confirm:password', '{%repassword empty}|{%repassword incorrect}']
            ];
            $validate = new Validate($rule);
            $rst = $validate->check([
                'member_list_username' => Xss::xssFilter($member_list_username),
                'password' => $password,
                'repassword' => $repassword,
                'member_list_email' => $member_list_email
            ]);
            if (true !== $rst) {
                $errors = $validate->getError();
                if (is_array($errors)) {
                    $errors = join('|', $errors);
                }
                $this->error($errors);
            }
            $users_model = Db::name("member_list");
            //用户名需过滤的字符的正则
            $stripChar = '?<*.>\'"';
            if (preg_match('/[' . $stripChar . ']/is', $member_list_username) == 1) {
                $this->error(lang('username format incorrect', ['stripChar' => $stripChar]));
            }
            //判断是否存在
            $result = $users_model->where('member_list_username', $member_list_username)->whereOr('member_list_email', $member_list_email)->count();
            if ($result) {
                $this->error(lang('username exists'));
            } else {
                $member_list_salt = random(10);
                $active_options = get_active_options();
                $sl_data = [
                    'member_list_username' => $member_list_username,
                    'member_list_nickname' => $member_list_username,
                    'member_list_salt' => $member_list_salt,
                    'member_list_pwd' => encrypt_password($password, $member_list_salt),
                    'member_list_email' => $member_list_email,
                    'member_list_groupid' => 1,
                    'member_list_open' => 1,
                    'member_list_addtime' => time(),
                    'user_status' => empty($active_options['email_active']) ? 1 : 0,//需要激活,则为未激活状态,否则为激活状态
                ];
                if ($this->user && isset($this->user['member_list_id']) && $this->user['member_list_id']) {
                    $rst = $this->user['member_list_id'];
                    $sl_data['member_list_id'] = $rst;
                    $users_model->update($sl_data);
                } else {
                    $rst = $users_model->insertGetId($sl_data);
                }
                if ($rst !== false) {
                    //需要激活-生成激活码
                    if (!empty($active_options['email_active'])) {
                        $activekey = md5($rst . time() . uniqid());
                        $result = $users_model->where(array("member_list_id" => $rst))->update(array("user_activation_key" => $activekey));
                        if (!$result) {
                            $this->error(lang('activation code generation failed'));
                        }
                        //生成激活链接
                        $url = url('home/Register/active', array("hash" => $activekey), "", true);
                        $template = $active_options['email_tpl'];
                        $content = str_replace(array('http://#link#', '#username#'), array($url, $member_list_username), $template);
                        $send_result = sendMail($member_list_email, $active_options['email_title'], $content);
                        if ($send_result['error']) {
                            $this->error(lang('send active email failed'));
                        } else {
                            $this->success(lang('send active email success'), url('home/Login/index'));
                        }
                    } else {
                        //更新字段
                        $data = [
                            'last_login_time' => time(),
                            'last_login_ip' => request()->ip(),
                        ];
                        $sl_data['last_login_time'] = $data['last_login_time'];
                        $sl_data['last_login_ip'] = $data['last_login_ip'];
                        $users_model->where(array('member_list_id' => $rst))->update($data);
                        session('hid', $rst);
                        session('user', $sl_data);
                        $this->success(lang('register success'), url('home/Index/index'));
                    }
                } else {
                    $this->error(lang('register failed'));
                }
            }
        }
    }

    /**
     * @param $email
     * @return int
     */
    protected function is_email($email)
    {
        return (preg_match("/^[-_.[:alnum:]]+@((([[:alnum:]]|[[:alnum:]][[:alnum:]-]*[[:alnum:]])\.)+(ad|ae|aero|af|ag|ai|al|am|an|ao|aq|ar|arpa|as|at|au|aw|az|ba|bb|bd|be|bf|bg|bh|bi|biz|bj|bm|bn|bo|br|bs|bt|bv|bw|by|bz|ca|cc|cd|cf|cg|ch|ci|ck|cl|cm|cn|co|com|coop|cr|cs|cu|cv|cx|cy|cz|de|dj|dk|dm|do|dz|ec|edu|ee|eg|eh|er|es|et|eu|fi|fj|fk|fm|fo|fr|ga|gb|gd|ge|gf|gh|gi|gl|gm|gn|gov|gp|gq|gr|gs|gt|gu|gw|gy|hk|hm|hn|hr|ht|hu|id|ie|il|in|info|int|io|iq|ir|is|it|jm|jo|jp|ke|kg|kh|ki|km|kn|kp|kr|kw|ky|kz|la|lb|lc|li|lk|lr|ls|lt|lu|lv|ly|ma|mc|md|mg|mh|mil|mk|ml|mm|mn|mo|mp|mq|mr|ms|mt|mu|museum|mv|mw|mx|my|mz|na|name|nc|ne|net|nf|ng|ni|nl|no|np|nr|nt|nu|nz|om|org|pa|pe|pf|pg|ph|pk|pl|pm|pn|pr|pro|ps|pt|pw|py|qa|re|ro|ru|rw|sa|sb|sc|sd|se|sg|sh|si|sj|sk|sl|sm|sn|so|sr|st|su|sv|sy|sz|tc|td|tf|tg|th|tj|tk|tm|tn|to|tp|tr|tt|tv|tw|tz|ua|ug|uk|um|us|uy|uz|va|vc|ve|vg|vi|vn|vu|wf|ws|ye|yt|yu|za|zm|zw)|(([0-9][0-9]?|[0-1][0-9][0-9]|[2][0-4][0-9]|[2][5][0-5])\.){3}([0-9][0-9]?|[0-1][0-9][0-9]|[2][0-4][0-9]|[2][5][0-5]))$/i", $email));
    }

    //激活
    public function active()
    {
        $hash = input('hash', '');
        if (empty($hash)) {
            $this->error(lang('pwd reset hash incorrect'));
        }
        $users_model = Db::name("member_list");
        $find_user = $users_model->where(array("user_activation_key" => $hash))->find();
        if ($find_user) {
            $result = $users_model->where(array("user_activation_key" => $hash))->update(array("user_activation_key" => "", "user_status" => 1));
            if ($result) {
                $find_user['user_status'] = 1;
                //更新字段
                $data = array(
                    'last_login_time' => time(),
                    'last_login_ip' => request()->ip(),
                );
                $find_user['last_login_time'] = $data['last_login_time'];
                $find_user['last_login_ip'] = $data['last_login_ip'];
                $users_model->where(array('member_list_id' => $find_user["member_list_id"]))->update($data);
                session('hid', $find_user['member_list_id']);
                session('user', $find_user);
                $this->success(lang('active success'), url('home/Index/index'));
            } else {
                $this->error(lang('active failed'), url("home/Login/index"));
            }
        } else {
            $this->error(lang('pwd reset hash incorrect'), url("home/Login/index"));
        }
    }
}