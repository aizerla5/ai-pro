<?php
// +----------------------------------------------------------------------
// | AiCMS
// +----------------------------------------------------------------------
// | Copyright (c) 2017
// +----------------------------------------------------------------------
// | Author: Ai Ye
// +----------------------------------------------------------------------
namespace app\common\controller;

use think\Controller;
use think\Lang;
use think\captcha\Captcha;

class Common extends Controller
{
    /**
     * @var string
     */
    protected $lang;

    /**
     * @var array
     */
    protected $beforeActionList = [
        'metaOptions'
    ];

    /**
     * Initialize
     */
    protected function _initialize()
    {
        parent::_initialize();
        if (!defined('__ROOT__')) {
            $_root = rtrim(dirname(rtrim($_SERVER['SCRIPT_NAME'], '/')), '/');
            define('__ROOT__', (('/' == $_root || '\\' == $_root) ? '' : $_root));
        }
        if (!file_exists(ROOT_PATH . 'data/install.lock')) {
            //不存在，则进入安装
            header('Location: ' . url('install/Index/index'));
            exit();
        }
        if (!defined('MODULE_NAME')) {
            define('MODULE_NAME', $this->request->module());
        }
        if (!defined('CONTROLLER_NAME')) {
            define('CONTROLLER_NAME', $this->request->controller());
        }
        if (!defined('ACTION_NAME')) {
            define('ACTION_NAME', $this->request->action());
        }

        /**
         * Switch Languages
         */
        if (config('lang_switch_on')) {
            $this->lang = Lang::detect();
        } else {
            $this->lang = config('default_lang');
        }
        $this->assign('lang', $this->lang);
        $this->assign('web', __ROOT__ . '/app/admin/view/web');
    }

    /**
     *
     */
    public function metaOptions()
    {
        $this->assign('title', '');
        $this->assign('meta_keywords', '');
        $this->assign('meta_description', '');
    }

    /**
     * Empty Action Forward.
     */
    public function _empty()
    {
        $this->error(lang('Invalid operation ...'));
    }

    /**
     * @param $id
     * @return \think\Response
     */
    protected function verify_build($id)
    {
        ob_end_clean();
        $verify = new Captcha (config('verify'));
        return $verify->entry($id);
    }

    /**
     * @param $id
     */
    protected function verify_check($id)
    {
        $verify = new Captcha ();
        if (!$verify->check(input('verify'), $id)) {
            $this->error(lang('The captcha code is incorrect!'), url(MODULE_NAME . '/Login/login'));
        }
    }

    /**
     * @return mixed
     */
    protected function check_admin_login()
    {
        return model('admin/Admin')->is_login();
    }

    protected function _addAssign($key, $value)
    {
        $this->assign($key, $value);
    }
}