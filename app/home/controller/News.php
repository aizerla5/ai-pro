<?php
// +----------------------------------------------------------------------
// | AiCMS
// +----------------------------------------------------------------------
// | Copyright (c) 2017
// +----------------------------------------------------------------------
// | Author: Ai Ye
// +----------------------------------------------------------------------
namespace app\home\controller;

use think\Cache;
use think\Config;
use think\Db;

class News extends Base
{
    //文章内页
    public function index()
    {
        $nId = input('id');
        $page = input('page', 1);
        $news = Db::name('news')->alias("a")->join(config('database.prefix') . 'member_list b', 'a.news_auto =b.member_list_id')->where(['n_id' => $nId, 'news_open' => 1, 'news_back' => 0])->find();
        if (empty($news)) {
            $this->error(lang('operation not valid'));
        }
        $news_data = explode('_ueditor_page_break_tag_', $news['news_content']);
        $total = count($news_data);
        $news['content'] = $news_data[$page - 1];
        $news['page'] = '';
        if ($total > 1) {
            $prevbtn = ($page <= 1) ? '<li class="disabled"><span>&laquo;</span></li>' : '<li><a href="' . url('home/News/index', ['id' => input('id'), 'page' => ($page - 1)]) . '">&laquo;</a></li>';
            $nextbtn = ($page >= $total) ? '<li class="disabled"><span>&raquo;</span></li>' : '<li><a href="' . url('home/News/index', ['id' => input('id'), 'page' => ($page + 1)]) . '">&raquo;</a></li>';
            $link = $this->getLinks($page, $total, input('id'));
            $news['page'] = sprintf(
                '<ul class="pagination">%s %s %s</ul>',
                $prevbtn,
                $link,
                $nextbtn
            );
        }
        $menu = Db::name('menu')->find($news['news_columnid']);
        if (empty($menu)) {
            $this->error(lang('operation not valid'));
        }

        $parent = $menu;
        if ($menu['parentid'] != 0) {
            $parent = Db::name('menu')->where('id', $menu['parentid'])->find();
        }

        $layoutName = trim($menu['menu_layouttpl']) != '' ? $menu['menu_layouttpl'] : $parent['menu_layouttpl'];
        $layoutName = 'layout:' . $layoutName ?: 'base';

        $tplName = trim($menu['menu_newstpl']) != '' ? $menu['menu_newstpl'] : $parent['menu_newstpl'];
        $tplName = $tplName ?: 'article';

        //自行根据网站需要考虑，是否需要判断
        /*$can_do = check_user_action('news' . input('id'), 0, false, 60);
        if ($can_do) {
            //更新点击数
            Db::name('news')->where('n_id', $nId)->setInc('news_hits');
            $news['news_hits'] += 1;
        }*/
        $next = Db::name('news')->where(["news_time" => ["egt", $news['news_time']], "n_id" => ['neq', input('id')], "news_open" => 1, 'news_back' => 0, 'news_columnid' => $news['news_columnid']])->order("news_time asc")->find();
        $prev = Db::name('news')->where(["news_time" => ["elt", $news['news_time']], "n_id" => ['neq', input('id')], "news_open" => 1, 'news_back' => 0, 'news_columnid' => $news['news_columnid']])->order("news_time desc")->find();
        $t_open = config('comment.t_open');
        if ($t_open) {
            //获取评论数据
            $comments = Db::name('comments')->alias("a")->join(config('database.prefix') . 'member_list b', 'a.uid =b.member_list_id')->where(["a.t_name" => 'news', "a.t_id" => input('id'), "a.c_status" => 1])->order("a.createtime ASC")->select();
            $count = count($comments);
            $new_comments = [];
            $parent_comments = [];
            if (!empty($comments)) {
                foreach ($comments as $m) {
                    if ($m['parentid'] == 0) {
                        $new_comments[$m['c_id']] = $m;
                    } else {
                        $path = explode("-", $m['path']);
                        $new_comments[$path[1]]['children'][] = $m;
                    }
                    $parent_comments[$m['c_id']] = $m;
                }
            }
            $this->assign("count", $count);
            $this->assign("comments", $new_comments);
            $this->assign("parent_comments", $parent_comments);
        }
        $this->assign("t_open", $t_open);
        $this->assign($news);
        $this->assign("next", $next);
        $this->assign("prev", $prev);

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

    //分页中间部分链接
    protected function getLinks($page, $total, $id)
    {
        $block = [
            'first' => null,
            'slider' => null,
            'last' => null
        ];

        $side = 3;
        $window = $side * 2;

        if ($total < $window + 6) {
            $block['first'] = $this->getUrlRange(1, $total, $id);
        } elseif ($page <= $window) {
            $block['first'] = $this->getUrlRange(1, $window + 2, $id);
            $block['last'] = $this->getUrlRange($total - 1, $total, $id);
        } elseif ($page > ($total - $window)) {
            $block['first'] = $this->getUrlRange(1, 2, $id);
            $block['last'] = $this->getUrlRange($total - ($window + 2), $total, $id);
        } else {
            $block['first'] = $this->getUrlRange(1, 2, $id);
            $block['slider'] = $this->getUrlRange($page - $side, $page + $side, $id);
            $block['last'] = $this->getUrlRange($total - 1, $total, $id);
        }
        $html = '';
        if (is_array($block['first'])) {
            $html .= $this->getUrlLinks($block['first'], $page);
        }
        if (is_array($block['slider'])) {
            $html .= '<li class="disabled"><span>...</span></li>';
            $html .= $this->getUrlLinks($block['slider'], $page);
        }
        if (is_array($block['last'])) {
            $html .= '<li class="disabled"><span>...</span></li>';
            $html .= $this->getUrlLinks($block['last'], $page);
        }
        return $html;
    }

    protected function getUrlLinks(array $urls, $page)
    {
        $html = '';
        foreach ($urls as $text => $url) {
            $html .= ($text == $page) ? '<li class="active"><span>' . $text . '</span></li>' : '<li><a href="' . htmlentities($url) . '">' . $text . '</a></li>';
        }
        return $html;
    }

    protected function getUrlRange($start, $end, $id)
    {
        $urls = [];
        for ($page = $start; $page <= $end; $page++) {
            $urls[$page] = url('home/News/index', ['id' => $id, 'page' => $page]);
        }
        return $urls;
    }

    public function dolike()
    {
        $this->check_login();
        $id = input('id', 0, 'intval');
        $can_like = check_user_action('news' . $id, 1);
        if ($can_like) {
            Db::name("news")->where('n_id', $id)->setInc('news_like');;
            $this->success(lang('dolike success'));
        } else {
            $this->error(lang('dolike already'));
        }
    }

    public function dofavorite()
    {
        $this->check_login();
        $key = input('key');
        if ($key) {
            $id = input('id');
            if ($key == encrypt_password('news-' . $id, 'news')) {
                $uid = session('hid');
                $favorites_model = Db::name("favorites");
                $find_favorite = $favorites_model->where(['t_name' => 'news', 't_id' => $id, 'uid' => $uid])->find();
                if ($find_favorite) {
                    $this->error(lang('favorited already'));
                } else {
                    $data = [
                        'uid' => $uid,
                        't_name' => 'news',
                        't_id' => $id,
                        'createtime' => time()
                    ];
                    $result = $favorites_model->insert($data);
                    if ($result) {
                        $this->success(lang('favorite success'));
                    } else {
                        $this->error(lang('favorite failed'));
                    }
                }
            } else {
                $this->error(lang('favorite failed'));
            }
        } else {
            $this->error(lang('favorite failed'));
        }
    }
}
