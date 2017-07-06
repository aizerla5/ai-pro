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
use think\Db;
use think\Lang;
use app\admin\model\News as NewsModel;

class Index extends Base
{
    public function index()
    {
        // ad.
        $ads = Db::name('plug_ad')->where([
            'plug_ad_adtypeid' => 2,
            'plug_ad_l' => $this->lang
        ])->find();
        if (!is_null($ads)) {
            $this->assign('ads', $ads);
        }
        // banners
        $banners = Db::name('plug_ad')->where([
            'plug_ad_adtypeid' => 1,
            'plug_ad_l' => $this->lang
        ])->order('plug_ad_order ASC')->select();
        if (count($banners)) {
            $this->assign('banners', $banners);
        }

        if ($this->lang == 'zh-cn') {
            // 活动预告 = 51
            $activitiesPid = 51;
            $activities = Db::name('news')->where(['news_open' => 1, 'news_back' => 0, 'news_l' => $this->lang])
                ->whereIn('news_columnid', $activitiesPid)
                ->field('n_id, news_title, news_extra')
                ->order('news_time DESC')->limit(4)->select();
            $this->_getShowdate($activities);
            $this->assign('activitiesPid', $activitiesPid);
            $this->assign('activities', $activities);


            //本科生 学术型硕士 教育硕士 教育博士 国际研究生 菜单
            $xw = Db::name('menu')->where(['parentid' => 5, 'menu_open' => 1, 'menu_l' => $this->lang])->order('listorder ASC')->select();
            $this->assign('xw', $xw);

            // 最新
            $latestNews = $this->_getNews();
            $this->assign('latestNews', $latestNews);

            // 推荐
            $recommendNews = $this->_getNews('c', 12);
            $this->_getShowdate($recommendNews);
            $arrNews = [];
            $i = 0;
            foreach ($recommendNews as $item) {
                if (isset($arrNews[$i]) && count($arrNews[$i]) == 3) {
                    $i++;
                }
                if (!isset($arrNews[$i])) {
                    $arrNews[$i] = [];
                }
                $arrNews[$i][] = $item;
            }

            $this->assign('reNews', $arrNews);

            // 图文
//            $imageNews = $this->_getNews('p', 6, true);
            // 图文 = 52
            $picPid = 52;
            $imageNews = Db::name('news')->where(['news_open' => 1, 'news_back' => 0, 'news_l' => $this->lang])
                ->whereIn('news_columnid', $picPid)
                ->field('n_id, news_title, news_img, news_extra')
                ->order('news_time DESC')->limit(6)->select();
            foreach ($imageNews as $k => $item) {
                $this->assign('in' . $k, $item);
            }
            // 友情菜单
            $links = Db::name('plug_link')->where(['plug_link_open' => 1, 'plug_link_l' => $this->lang])->order('plug_link_order ASC')->select();
            $this->assign('links', $links);
        }

        return $this->fetch(':index');
    }

    /**
     * @param $news
     */
    protected function _getShowdate(&$news)
    {
        if (count($news)) {
            foreach ($news as $k => $item) {
                if (isset($item['news_extra'])) {
                    $showdate = json_decode($item['news_extra'])->showdate;
                    $news[$k]['showdate'] = $showdate;
                }
            }
        }
    }

    /**
     * @param string $flag
     * @param int $count
     * @param bool $isArray
     * @return false|\PDOStatement|string|\think\Collection
     */
    protected function _getNews($flag = 'h', $count = 3, $isArray = false)
    {
        $newsModel = new NewsModel;
        $news = $newsModel->alias("a")->field('a.n_id, a.news_columnid, a.news_title, a.news_img, a.news_scontent, a.news_extra, c.menu_name')
            ->join(config('database.prefix') . 'menu c', 'a.news_columnid =c.id')
            ->where(['a.news_open' => 1, 'a.news_back' => 0, 'a.news_l' => $this->lang, 'a.news_flag' => $flag])
            ->order('news_time desc')->limit($count)->select();

        if ($isArray) {
            $array = [];
            if (count($news)) {
                foreach ($news as $item) {
                    $array[] = $item->getData();
                }
                $news = $array;
            }
        }
        return $news;
    }

    public function swatch()
    {
        $lang = input('lang');
        // 3600 * 24 * 30 = 259200
        switch ($lang) {
            case 'cn':
            case 'zh':
            case 'zh-cn':
                cookie('think_var', 'zh-cn', 259200);
                Lang::setLangDetectVar($lang);
                break;
            case 'us':
            case 'en':
            case 'en-us':
                cookie('think_var', 'en-us', 259200);
                break;
            //其它语言
            default:
                cookie('think_var', 'zh-cn', 259200);
        }
        Cache::clear();
        $this->redirect(url('home/Index/index'));
    }
}