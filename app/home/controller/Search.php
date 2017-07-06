<?php

namespace app\home\controller;

use app\common\behavior\Xss;

class Search extends Base
{
    public function index()
    {
        $k = input("keyword", '');
        $k = Xss::filter($k);
        $pageSize = 16;
        if (empty($k)) {
            $this->error(lang('Keywords was required'));
        }

        $lists = get_news('order:news_time desc', 1, $pageSize, 'keyword', $k);
        $page_html = $lists['page'];
        $this->assign('page_html', $page_html);

        $this->assign('lists', $lists);
        $this->assign("keyword", $k);

        $sideTitle = ['menu_name' => lang('Search Result')];
        $this->assign('sideTitle', $sideTitle);

        return $this->fetch(':search');
    }
}