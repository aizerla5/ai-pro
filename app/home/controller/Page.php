<?php
// +----------------------------------------------------------------------
// | AiCMS
// +----------------------------------------------------------------------
// | Copyright (c) 2017
// +----------------------------------------------------------------------
// | Author: Ai Ye
// +----------------------------------------------------------------------
namespace app\home\controller;

use think\Config;
use think\Db;

class Page extends Base
{
    public function index()
    {
        $list_id = input('id');
        $page = input('page');
        $pageSize = 16;
        $menu = Db::name('menu')->find(input('id'));
        if (empty($menu)) {
            $this->error(lang('Invalid Operation'));
        }

        $parent = $menu;
        if ($menu['parentid'] != 0) {
            $parent = Db::name('menu')->where('id', $menu['parentid'])->find();
        }

        $layoutName = trim($menu['menu_layouttpl']) != '' ? $menu['menu_layouttpl'] : $parent['menu_layouttpl'];
        $layoutName = 'layout:' . $layoutName ?: 'base';

        $tplName = trim($menu['menu_listtpl']) != '' ? $menu['menu_listtpl'] : $parent['menu_listtpl'];
        $tplName = $tplName ?: 'list';

        $model = Db::name('model')->find($menu['menu_modelid']);
        if ($model) {
            //判断ajax模板是否存在
            if (is_file($this->theme . 'ajax_' . $tplName) && request()->isAjax()) {
                $data = Db::name($model['model_name'])->where($model['model_cid'], $list_id)->order($model['model_order'])->paginate($pageSize, false, ['page' => $page]);
                $tplName = ":ajax_" . $tplName;
                $lists['page'] = $data->render();
                //替换成带ajax的class
                $page_html = $lists['page'];
                $page_html = preg_replace("(<a[^>]*page[=|/](\d+).+?>(.+?)<\/a>)", "<a href='javascript:ajax_page($1);'>$2</a>", $page_html);
            } else {
                $data = Db::name($model['model_name'])->where($model['model_cid'], $list_id)->order($model['model_order'])->paginate($pageSize, false);
                $lists['page'] = $data->render();
                $page_html = $lists['page'];
            }
            $lists['news'] = $data;
            $this->assign('page_html', $page_html);
            $this->assign('lists', $lists);
        } else {
            if ($menu['menu_type'] == 4) {

            } else {
                //news
                if (request()->isAjax()) {
                    $lists = get_news('cid:' . $list_id . ';order:news_time desc', 1, $pageSize, null, null, [], $page);
                    $tplName = ":ajax_" . $tplName;
                } else {
                    $lists = get_news('cid:' . $list_id . ';order:news_time desc', 1, $pageSize);
                }
                $this->assign('lists', $lists);
            }
        }

        $this->assign('menu', $menu);

        if (strpos($layoutName, '2') !== false) {
            if (!is_null($parent)) {
                $child = Db::name('menu')->where(['parentid' => $parent['id'], 'menu_open' => 1])->order('listorder ASC')->select();
                $this->assign('sideNav', $child);
            }
        }
        $this->assign('sideTitle', $parent);

        $this->assign('layoutName', $layoutName);
        $cache = [
            'cache_prefix' => $layoutName
        ];
        return $this->fetch(":$tplName", [], [], $cache);
    }
}