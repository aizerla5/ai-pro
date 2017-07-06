<?php
// +----------------------------------------------------------------------
// | AiCMS
// +----------------------------------------------------------------------
// | Copyright (c) 2017
// +----------------------------------------------------------------------
// | Author: Ai Ye
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\behavior\VideoInfo;
use think\Db;
use app\admin\model\Video as VideoModel;

class Video extends Base
{

    public function index()
    {
        //栏目数据
        $menu_text = menu_text($this->lang);
        $this->assign('menu', $menu_text);

        $videoModel = Db::name('plug_video');
        $videos = $videoModel->where(['deleted' => 0])->order('sorting asc')->paginate(config('paginate.list_rows'), false, ['query' => get_query()]);

        $show = $videos->render();
        $this->assign('page', $show);

        $this->assign('videos', $videos);
        return $this->fetch();
    }

    public function add()
    {
        $id = input('id', 0);
        $this->assign('video', false);
        if ($id) {
            $video = Db::name('plug_video')->where(['video_id' => $id])->find();
            if (is_null($video)) {
                $this->error('无效的视频信息', url('admin/Video/add'));
            }
            $this->assign('video', $video);
        }
        $menu_text = menu_text($this->lang);
        $this->assign('menu', $menu_text);
        return $this->fetch();
    }

    public function save()
    {
        $vid = input('video_id', 0);
        $video = null;
        if ($vid) {
            $video = Db::name('plug_video')->where(['video_id' => $vid])->find();
        }
        //上传图片部分
        $img_one = '';
        $file = request()->file('pic');
        if ($file) {
            $validate = config('upload_validate');
            //单图
            if ($file) {
                $info = $file->validate($validate)->rule('uniqid')->move(ROOT_PATH . config('upload_path') . DS . date('Y-m-d'));
                if ($info) {
                    $img_url = config('upload_path') . '/' . date('Y-m-d') . '/' . $info->getFilename();
                    //写入数据库
                    $data['uptime'] = time();
                    $data['filesize'] = $info->getSize();
                    $data['path'] = $img_url;
                    Db::name('plug_files')->insert($data);
                    $img_one = $img_url;
                } else {
                    $this->error($file->getError(), url('admin/Video/add'));//否则就是上传错误，显示错误原因
                }
            }
        } else {
            $img_one = $video['video_pic'];
        }

        if (trim($img_one) != '' && $video && $video['video_pic'] != $img_one) {
            $origFile = dirname(APP_PATH) . str_replace('/', DIRECTORY_SEPARATOR, $video['video_pic']);
            if (file_exists($origFile)) {
                Db::name('plug_files')->delete(['path' => $video['video_pic']]);
                @unlink($origFile);
            }
        }

        $videoSource = input('video_src', '');

        $url = '';

        $theData = [
            'title' => input('title'),
            'visitor' => input('visitor'),
            'video_pic' => $img_one,
            'video_src' => $videoSource,
            'video_source' => $url,
            'bodytext' => input('bodytext', ''),
            'sorting' => input('sorting', 1),
        ];

        $continue = input('continue', 0, 'intval');
        if (!is_null($video) && $vid) {
            $theData['video_id'] = $vid;
            Db::name('plug_video')->update($theData);
            $this->success('视频信息修改成功,返回列表页', url('admin/Video/index'));
        } else {
            Db::name('plug_video')->insert($theData);
            if ($continue) {
                $this->success('视频添加成功,继续发布', url('admin/Video/add'));
            } else {
                $this->success('视频添加成功,返回列表页', url('admin/Video/index'));
            }
        }
    }

    public function edit()
    {
        $this->redirect('add', ['id' => input('id')]);
    }

    public function sorting()
    {
        if (!request()->isAjax()) {
            $this->error('提交方式不正确', url('admin/Video/index'));
        } else {
            $list = [];
            foreach (input('post.') as $vid => $sorting) {
                $list[] = ['video_id' => $vid, 'sorting' => $sorting];
            }
            $vm = new VideoModel;
            $vm->saveAll($list);
            $this->success('排序更新成功', url('admin/Video/index'));
        }
    }

    public function alldelete()
    {
        $p = input('p');
        $ids = input('video/a');
        if (empty($ids)) {
            $this->error("请选择删除文章", url('admin/Video/index', ['p' => $p]));
        }
        if (is_array($ids)) {//判断获取文章ID的形式是否数组
            $where = 'video_id in(' . implode(',', $ids) . ')';
        } else {
            $where = 'video_id=' . $ids;
        }
        $vm = new VideoModel;
        $rst = $vm->where($where)->setField('deleted', 1);
        if ($rst !== false) {
            $this->success("成功删除视频信息！", url('admin/Video/index', ['p' => $p]));
        } else {
            $this->error("删除视频信息失败！", url('admin/Video/index', ['p' => $p]));
        }
    }
}

