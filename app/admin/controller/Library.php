<?php
// +----------------------------------------------------------------------
// | AiCMS
// +----------------------------------------------------------------------
// | Copyright (c) 2017
// +----------------------------------------------------------------------
// | Author: Ai Ye
// +----------------------------------------------------------------------
namespace app\admin\controller;

use think\Db;
use app\admin\model\Options;
use app\admin\model\Logo as LogoModel;
use app\admin\model\Category as CategoryModel;
use app\admin\model\Mod as ModModel;
use app\admin\model\Product as ProductModel;
use app\admin\model\Circuit as CircuitModel;
use app\admin\model\CateCircuit as CateCircuitModel;
use app\admin\model\Repair as RepairModel;
use app\admin\model\CateRepair as CateRepairModel;
use think\Image;

class Library extends Base
{

    public function logo_index()
    {
        //栏目数据
        $menu_text = menu_text($this->lang);
        $this->assign('menu', $menu_text);

        $model = Db::name('lib_logo');
        $logos = $model->order('sorting asc')->paginate(config('paginate.list_rows'), false, ['query' => get_query()]);

        $show = $logos->render();
        $this->assign('page', $show);

        $this->assign('logos', $logos);
        return $this->fetch();
    }

    public function logo_add()
    {
        $id = input('id', 0);
        $this->assign('logo', false);
        if ($id) {
            $logo = Db::name('lib_logo')->where(['logo_id' => $id])->find();
            if (is_null($logo)) {
                $this->error('无效的厂商信息', url('admin/Library/logo_add'));
            }
            $this->assign('logo', $logo);
        }
        $menu_text = menu_text($this->lang);
        $this->assign('menu', $menu_text);
        return $this->fetch();
    }

    public function logo_save()
    {
        $vid = input('logo_id', 0);
        $logo = null;
        if ($vid) {
            $logo = Db::name('lib_logo')->where(['logo_id' => $vid])->find();
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
                    $this->error($file->getError(), url('admin/Library/logo_add'));//否则就是上传错误，显示错误原因
                }
            }
        } else {
            $img_one = $logo['logo_pic'];
        }

        if (trim($img_one) != '' && $logo && $logo['logo_pic'] != $img_one) {
            $origFile = dirname(APP_PATH) . str_replace('/', DIRECTORY_SEPARATOR, $logo['logo_pic']);
            if (file_exists($origFile)) {
                Db::name('plug_files')->delete(['path' => $logo['logo_pic']]);
                @unlink($origFile);
            }
        }
        $theData = [
            'title' => input('title'),
            'logo_pic' => $img_one,
            'remark' => input('remark', ''),
            'sorting' => input('sorting', 1)
        ];

        $continue = input('continue', 0, 'intval');
        if (!is_null($logo) && $vid) {
            $theData['logo_id'] = $vid;
            Db::name('lib_logo')->update($theData);
            $this->success('厂商修改成功,返回列表页', url('admin/Library/logo_index'));
        } else {
            Db::name('lib_logo')->insert($theData);
            if ($continue) {
                $this->success('厂商添加成功,继续发布', url('admin/Library/logo_add'));
            } else {
                $this->success('厂商添加成功,返回列表页', url('admin/Library/logo_index'));
            }
        }
    }

    public function logo_edit()
    {
        $this->redirect('logo_add', ['id' => input('id')]);
    }

    public function logo_sorting()
    {
        if (!request()->isAjax()) {
            $this->error('提交方式不正确', url('admin/Library/logo_index'));
        } else {
            $list = [];
            foreach (input('post.') as $vid => $sorting) {
                $list[] = ['logo_id' => $vid, 'sorting' => $sorting];
            }
            $vm = new LogoModel();
            $vm->saveAll($list);
            $this->success('排序更新成功', url('admin/Library/logo_index'));
        }
    }

    public function logo_delete()
    {
        $p = input('p');
        $ids = input('logo/a');
        if (empty($ids)) {
            $this->error("请选择删除故障码厂商", url('admin/Library/logo_index', ['p' => $p]));
        }
        if (is_array($ids)) {//判断获取文章ID的形式是否数组
            $where = 'logo_id in(' . implode(',', $ids) . ')';
        } else {
            $where = 'logo_id=' . $ids;
        }

        $needDeleteFiles = Db::name('lib_logo')->where($where . ' AND logo_pic != ""')->select();
        if ($needDeleteFiles) {
            foreach ($needDeleteFiles as $item) {
                $files = explode(',', $item['logo_pic']);
                foreach ($files as $file) {
                    if (file_exists(ROOT_PATH . ltrim($file, '/'))) {
                        @unlink(ROOT_PATH . ltrim($file, '/'));
                    }
                }
            }
        }

        $delLogos = new LogoModel();
        $rst = $delLogos->where($where)->delete();
        if ($rst !== false) {
            $this->success("成功删除故障码厂商！", url('admin/Library/logo_index', ['p' => $p]));
        } else {
            $this->error("删除故障码厂商失败！", url('admin/Library/logo_index', ['p' => $p]));
        }
    }


    /********************
     * Category
     *******************/


    public function category_index()
    {
        //栏目数据
        $menu_text = menu_text($this->lang);
        $this->assign('menu', $menu_text);

        $model = Db::name('lib_category');
        $categories = $model->order('sorting asc')->paginate(config('paginate.list_rows'), false, ['query' => get_query()]);

        $logos = Db::name('lib_logo')->column('logo_id, title');
        $this->assign('logos', $logos);

        $show = $categories->render();
        $this->assign('page', $show);

        $this->assign('categories', $categories);
        return $this->fetch();
    }

    public function category_add()
    {
        $id = input('id', 0);
        $this->assign('category', false);
        if ($id) {
            $category = Db::name('lib_category')->where(['cate_id' => $id])->find();
            if (is_null($category)) {
                $this->error('无效的故障码分类', url('admin/Library/category_add'));
            }
            $this->assign('category', $category);
        }
        $menu_text = menu_text($this->lang);
        $this->assign('menu', $menu_text);

        $logos = Db::name('lib_logo')->column('logo_id, title');
        $this->assign('logos', $logos);
        return $this->fetch();
    }

    public function category_save()
    {
        $vid = input('cate_id', 0);
        $category = null;
        if ($vid) {
            $category = Db::name('lib_category')->where(['cate_id' => $vid])->find();
        }
        $theData = [
            'title' => input('title'),
            'parent' => input('pid', ''),
            'sorting' => input('sorting', 1)
        ];

        $continue = input('continue', 0, 'intval');
        if (!is_null($category) && $vid) {
            $theData['cate_id'] = $vid;
            Db::name('lib_category')->update($theData);
            $this->success('故障码分类修改成功,返回列表页', url('admin/Library/category_index'));
        } else {
            Db::name('lib_category')->insert($theData);
            if ($continue) {
                $this->success('故障码分类添加成功,继续发布', url('admin/Library/category_add'));
            } else {
                $this->success('故障码分类添加成功,返回列表页', url('admin/Library/category_index'));
            }
        }
    }

    public function category_edit()
    {
        session('modPid', null);
        $this->redirect('category_add', ['id' => input('id')]);
    }

    public function category_sorting()
    {
        if (!request()->isAjax()) {
            $this->error('提交方式不正确', url('admin/Library/category_index'));
        } else {
            $list = [];
            foreach (input('post.') as $vid => $sorting) {
                $list[] = ['cate_id' => $vid, 'sorting' => $sorting];
            }
            $vm = new LogoModel();
            $vm->saveAll($list);
            $this->success('排序更新成功', url('admin/Library/category_index'));
        }
    }

    public function category_delete()
    {
        $p = input('p');
        $ids = input('cate/a');
        if (empty($ids)) {
            $this->error("请选择删除故障码分类", url('admin/Library/category_index', ['p' => $p]));
        }
        if (is_array($ids)) {//判断获取文章ID的形式是否数组
            $where = 'cate_id in(' . implode(',', $ids) . ')';
        } else {
            $where = 'cate_id =' . $ids;
        }

        $delCategory = new CategoryModel();
        $rst = $delCategory->where($where)->delete();
        if ($rst !== false) {
            $this->success("成功删除故障码分类！", url('admin/Library/category_index', ['p' => $p]));
        } else {
            $this->error("删除故障码分类失败！", url('admin/Library/category_index', ['p' => $p]));
        }
    }

    /********************
     * Model
     *******************/
    public function mod_index()
    {
        //栏目数据
        $menu_text = menu_text($this->lang);
        $this->assign('menu', $menu_text);

        $pId = input('parent', false);
        if ($pId) {
            $this->assign('pId', $pId);
        }

        $model = Db::name('lib_model');
        $model->order('model_code asc');
        if ($pId) {
            $model->where('parent', $pId);
        }
        if ($code = input('code', false)) {
            $model->whereLike('model_code', "%" . trim($code) . "%");
            $this->assign('searchCode', $code);
        }
        $models = $model->paginate(config('paginate.list_rows'), false, ['query' => get_query()]);

        $categories = $this->_getCategoryOptions();
        $this->assign('categories', $categories);

        $show = $models->render();
        $this->assign('page', $show);

        $this->assign('models', $models);

        return $this->fetch();
    }

    protected function _getCategoryOptions()
    {
        $cateModel = new CategoryModel();
        $categories = [];
        $cateCollection = $cateModel->alias("c")->field('c.cate_id, c.title, l.title AS logoTitle')
            ->join(config('database.prefix') . 'lib_logo l', 'l.logo_id=c.parent')
            ->order('l.sorting ASC')->select();
        foreach ($cateCollection as $c) {
            $categories[] = $c->getData();
        }
        return $categories;
    }


    public function mod_add()
    {
        $id = input('id', 0);
        $this->assign('files', false);
        $this->assign('model', false);
        if ($id) {
            session('modPid', null);
            $model = Db::name('lib_model')->where(['model_id' => $id])->find();
            if (is_null($model)) {
                $this->error('无效的故障码型号', url('admin/Library/mod_add'));
            }
            if ($model['model_files'] != '') {
                $files = [];
                $filesName = explode(',', $model['model_files']);
                foreach ($filesName as $name) {
                    $files[] = [
                        'caption' => $name,
                        'size' => filesize(ROOT_PATH . $name)
                    ];
                }
                $this->assign('files', $files);
            }
            $this->assign('model', $model);
        }
        $menu_text = menu_text($this->lang);
        $this->assign('menu', $menu_text);

        $categories = $this->_getCategoryOptions();
        $this->assign('categories', $categories);

        if (!is_null($modPid = session('modPid'))) {
            $this->assign('modPid', $modPid);
        }
        return $this->fetch();
    }

    public function mod_edit()
    {
        $this->redirect('mod_add', ['id' => input('id')]);
    }

    public function mod_save()
    {
        $params = [];
        $mid = input('model_id', 0);
        $model = null;

        if ($mid) {
            $model = Db::name('lib_model')->where(['model_id' => $mid])->find();
            $params['model_id'] = $mid;
        }

        $useWater = input('use_water', 0);

        $allFiles = [];
        // files handle
        $files = request()->file('pic_all');
        if ($files) {
            $validate = config('upload_validate');
            //多图
            if ($files) {
                $currentDay = date('Y-m-d');
                foreach ($files as $file) {
                    $info = $file->validate($validate)->rule('uniqid')->move(ROOT_PATH . config('upload_path') . DS . $currentDay);
                    if ($info) {
                        $thePath = config('upload_path') . '/' . $currentDay . '/';
                        $imgSrc = $thePath . $info->getSaveName();
                        if ($useWater) {
                            $imgSource = ROOT_PATH . ltrim($imgSrc, '/');
                            $sys = Options::get_options('site_options', $this->lang);
                            if ($sys['site_water'] && file_exists(ROOT_PATH . $sys['site_water'])) {
                                $water = ROOT_PATH . ltrim($sys['site_water'], '/');
                                $image = Image::open($imgSource);
                                $image->water($water, 5)->save($imgSource);
                                $imgSrc = $thePath . $info->getSaveName();
                            }
                        }
                        //写入数据库
                        $data['uptime'] = time();
                        $data['filesize'] = $info->getSize();
                        $data['path'] = $imgSrc;
                        Db::name('plug_files')->insert($data);
                        $allFiles[] = $imgSrc;
                    } else {
                        $this->error($file->getError(), url('admin/Library/mod_add', $params));//否则就是上传错误，显示错误原因
                    }
                }
            }
        }
        // 检测图片
        if ($model) {
            $oldFiles = [];
            if(trim($model['model_files']) != '') {
                $oldFiles = explode(',', $model['model_files']);
            }
            $allFiles = array_merge($allFiles, $oldFiles);
        }

        $theData = [
            'parent' => input('pid'),
            'model_code' => input('model_code'),
            'model_title' => input('model_title'),
            'created_at' => input('created_at', date('Y-m-d')),
            'model_files' => implode(',', $allFiles),
            'captions' => input('captions'),
            'visitor' => input('visitor')
        ];

        // 设置添加的记录
        if (!$mid && $modPid = input('pid', 0)) {
            session('modPid', $modPid);
        }

        $continue = input('continue', 0, 'intval');
        if ($model && $mid) {
            $theData['model_id'] = $mid;
            Db::name('lib_model')->update($theData);
            if ($continue) {
                $this->success('故障码型号修改成功, 继续编辑', url('admin/Library/mod_add', ['id' => $mid]));
            } else {
                $this->success('故障码型号修改成功,返回列表页', url('admin/Library/mod_index'));
            }
        } else {
            Db::name('lib_model')->insert($theData);
            if ($continue) {
                $this->success('故障码型号添加成功,继续发布', url('admin/Library/mod_add'));
            } else {
                $this->success('故障码型号添加成功,返回列表页', url('admin/Library/mod_index'));
            }
        }
    }

    public function mod_delete()
    {
        $p = input('p');
        $ids = input('mod/a');
        if (empty($ids)) {
            $this->error("请选择删除故障码型号", url('admin/Library/mod_index', ['p' => $p]));
        }
        if (is_array($ids)) {//判断获取文章ID的形式是否数组
            $where = 'model_id in(' . implode(',', $ids) . ')';
        } else {
            $where = 'model_id =' . $ids;
        }

        $needDeleteFiles = Db::name('lib_model')->where($where . ' AND model_files != ""')->select();
        if ($needDeleteFiles) {
            foreach ($needDeleteFiles as $item) {
                $files = explode(',', $item['model_files']);
                foreach ($files as $file) {
                    if (file_exists(ROOT_PATH . ltrim($file, '/'))) {
                        @unlink(ROOT_PATH . ltrim($file, '/'));
                    }
                }
            }
        }

        $mod = new ModModel();
        $rst = $mod->where($where)->delete();
        if ($rst !== false) {
            $this->success("成功删除故障码型号！", url('admin/Library/mod_index', ['p' => $p]));
        } else {
            $this->error("删除故障码型号失败！", url('admin/Library/mod_index', ['p' => $p]));
        }
    }

    public function mod_image_sort()
    {
        $id = input('id');
        $model = Db::name('lib_model')->where('model_id', $id)->find();
        if ($model) {
            $params = request()->post('obj/a');
            $images = [];
            foreach ($params as $img) {
                $images[] = $img['caption'];
            }
            Db::name('lib_model')->update([
                'model_id' => $id,
                'model_files' => implode(',', $images)
            ]);
        }
        exit;
    }

    public function mod_image_del()
    {
        $id = input('id');
        $model = Db::name('lib_model')->where('model_id', $id)->find();
        $k = request()->post('key', false);
        $result = false;
        if ($model && $k) {
            $k--;
            $images = explode(',', $model['model_files']);
            if (isset($images[$k]) && file_exists(ROOT_PATH . ltrim('/', $images[$k]))) {
                unset($images[$k]);
                @unlink(ROOT_PATH . ltrim('/', $images[$k]));
            }
            $images = array_merge($images, []);
            Db::name('lib_model')->update([
                'model_id' => $id,
                'model_files' => implode(',', $images)
            ]);
            $result = true;
        }
        echo $result;
        exit;
    }


    /********************
     * Product
     *******************/

    public function product_index()
    {
        //栏目数据
        $menu_text = menu_text($this->lang);
        $this->assign('menu', $menu_text);

        $model = Db::name('lib_product');
        $products = $model->order('sorting asc')->paginate(config('paginate.list_rows'), false, ['query' => get_query()]);

        $show = $products->render();
        $this->assign('page', $show);

        $this->assign('products', $products);
        return $this->fetch();
    }

    public function product_add()
    {
        $id = input('id', 0);
        $this->assign('product', false);
        if ($id) {
            $product = Db::name('lib_product')->where(['product_id' => $id])->find();
            if (is_null($product)) {
                $this->error('无效的产品信息', url('admin/Library/product_add'));
            }
            $this->assign('product', $product);
        }
        $menu_text = menu_text($this->lang);
        $this->assign('menu', $menu_text);

        return $this->fetch();
    }

    public function product_save()
    {
        $pid = input('product_id', 0);
        $product = null;
        if ($pid) {
            $product = Db::name('lib_product')->where(['product_id' => $pid])->find();
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
                    $this->error($file->getError(), url('admin/Library/product_add'));//否则就是上传错误，显示错误原因
                }
            }
        } else {
            $img_one = $product['product_pic'];
        }

        if (trim($img_one) != '' && $product && $product['product_pic'] != $img_one) {
            $origFile = dirname(APP_PATH) . str_replace('/', DIRECTORY_SEPARATOR, $product['product_pic']);
            if (file_exists($origFile)) {
                Db::name('plug_files')->delete(['path' => $product['product_pic']]);
                @unlink($origFile);
            }
        }

        $theData = [
            'title' => input('title'),
            'product_pic' => $img_one,
            'bodytext' => input('bodytext'),
            'created_at' => date('Y-m-d H:i:s'),
            'visitor' => input('visitor', 1),
            'sorting' => input('sorting', 1)
        ];

        $continue = input('continue', 0, 'intval');
        if (!is_null($product) && $pid) {
            $theData['product_id'] = $pid;
            Db::name('lib_product')->update($theData);
            $this->success('产品信息修改成功,返回列表页', url('admin/Library/product_index'));
        } else {
            Db::name('lib_product')->insert($theData);
            if ($continue) {
                $this->success('产品信息添加成功,继续发布', url('admin/Library/product_add'));
            } else {
                $this->success('产品信息添加成功,返回列表页', url('admin/Library/product_index'));
            }
        }
    }

    public function product_edit()
    {
        $this->redirect('product_add', ['id' => input('id')]);
    }

    public function product_sorting()
    {
        if (!request()->isAjax()) {
            $this->error('提交方式不正确', url('admin/Library/product_index'));
        } else {
            $list = [];
            foreach (input('post.') as $pid => $sorting) {
                $list[] = ['product_id' => $pid, 'sorting' => $sorting];
            }
            $vm = new ProductModel();
            $vm->saveAll($list);
            $this->success('排序更新成功', url('admin/Library/product_index'));
        }
    }

    public function product_delete()
    {
        $p = input('p');
        $ids = input('product/a');
        if (empty($ids)) {
            $this->error("请选择删除产品信息", url('admin/Library/product_index', ['p' => $p]));
        }
        if (is_array($ids)) {//判断获取文章ID的形式是否数组
            $where = 'product_id in(' . implode(',', $ids) . ')';
        } else {
            $where = 'product_id =' . $ids;
        }

        $needDeleteFiles = Db::name('lib_product')->where($where . ' AND product_pic != ""')->select();
        if ($needDeleteFiles) {
            foreach ($needDeleteFiles as $item) {
                $files = explode(',', $item['product_pic']);
                foreach ($files as $file) {
                    if (file_exists(ROOT_PATH . ltrim($file, '/'))) {
                        @unlink(ROOT_PATH . ltrim($file, '/'));
                    }
                }
            }
        }

        $delProduct = new ProductModel();
        $rst = $delProduct->where($where)->delete();
        if ($rst !== false) {
            $this->success("成功删除产品信息！", url('admin/Library/product_index', ['p' => $p]));
        } else {
            $this->error("删除产品信息失败！", url('admin/Library/product_index', ['p' => $p]));
        }
    }

    /********************
     * Circuit
     *******************/
    public function catecircuit_index()
    {
        //栏目数据
        $menu_text = menu_text($this->lang);
        $this->assign('menu', $menu_text);

        $model = Db::name('plug_catecircuit');
        $categories = $model->order('sorting asc')->paginate(config('paginate.list_rows'), false, ['query' => get_query()]);

        $show = $categories->render();
        $this->assign('page', $show);

        $this->assign('categories', $categories);
        return $this->fetch();
    }

    public function circuit_index()
    {
        //栏目数据
        $menu_text = menu_text($this->lang);
        $this->assign('menu', $menu_text);

        $model = Db::name('plug_circuit');
        $circuits = $model->order('sorting asc')->paginate(config('paginate.list_rows'), false, ['query' => get_query()]);

        $show = $circuits->render();
        $this->assign('page', $show);

        $this->assign('circuits', $circuits);
        return $this->fetch();
    }

    public function circuit_add()
    {
        $id = input('id', 0);
        $this->assign('files', false);
        $this->assign('circuit', false);
        if ($id) {
            session('modPid', null);
            $circuit = Db::name('plug_circuit')->where(['circuit_id' => $id])->find();
            if (is_null($circuit)) {
                $this->error('无效的电路图', url('admin/Library/circuit_add'));
            }

            if ($circuit['circuit_pic'] != '') {
                $files = [];
                $filesName = explode(',', $circuit['circuit_pic']);
                foreach ($filesName as $name) {
                    $files[] = [
                        'caption' => $name,
                        'size' => filesize(ROOT_PATH . $name)
                    ];
                }
                $this->assign('files', $files);
            }
            $this->assign('circuit', $circuit);
        }

        if (!is_null($modPid = session('modPid'))) {
            $this->assign('modPid', $modPid);
        }

        $categories = Db::name('plug_catecircuit')->order('sorting ASC')->select();
        $this->assign('categories', $categories);

        $menu_text = menu_text($this->lang);
        $this->assign('menu', $menu_text);

        return $this->fetch();
    }

    public function catecircuit_add()
    {
        $id = input('id', 0);
        $this->assign('category', false);
        if ($id) {
            $category = Db::name('plug_catecircuit')->where(['cate_id' => $id])->find();
            if (is_null($category)) {
                $this->error('无效的电路图', url('admin/Library/catecircuit_add'));
            }
            $this->assign('category', $category);
        }
        $menu_text = menu_text($this->lang);
        $this->assign('menu', $menu_text);
        return $this->fetch();
    }

    public function circuit_save()
    {
        $params = [];
        $pid = input('circuit_id', 0);
        $circuit = null;
        if ($pid) {
            $circuit = Db::name('plug_circuit')->where(['circuit_id' => $pid])->find();
            $params['circuit_id'] = $pid;
        }

        $useWater = input('use_water', 0);

        //多图
        $allFiles = [];

        $files = request()->file('pic_all');
        if ($files) {
            $validate = config('upload_validate');
            if ($files) {
                $currentDay = date('Y-m-d');
                foreach ($files as $file) {
                    $info = $file->validate($validate)->rule('uniqid')->move(ROOT_PATH . config('upload_path') . DS . $currentDay);
                    if ($info) {
                        $thePath = config('upload_path') . '/' . $currentDay . '/';
                        $imgSrc = $thePath . $info->getSaveName();
                        if ($useWater) {
                            $imgSource = ROOT_PATH . ltrim($imgSrc, '/');
                            $sys = Options::get_options('site_options', $this->lang);
                            if ($sys['site_water'] && file_exists(ROOT_PATH . $sys['site_water'])) {
                                $water = ROOT_PATH . ltrim($sys['site_water'], '/');
                                $image = Image::open($imgSource);
                                $image->water($water, 5)->save($imgSource);
                                $imgSrc = $thePath . $info->getSaveName();
                            }
                        }
                        //写入数据库
                        $data['uptime'] = time();
                        $data['filesize'] = $info->getSize();
                        $data['path'] = $imgSrc;
                        Db::name('plug_files')->insert($data);
                        $allFiles[] = $imgSrc;
                    } else {
                        $this->error($file->getError(), url('admin/Library/mod_add', $params));//否则就是上传错误，显示错误原因
                    }
                }
            }
        }
        // 检测图片
        if ($circuit) {
            $oldFiles = [];
            if(trim($circuit['circuit_pic']) != '') {
                $oldFiles = explode(',', $circuit['circuit_pic']);
            }
            $allFiles = array_merge($allFiles, $oldFiles);
        }

        //单图
        $img_one = '';
        $theData = [
            'title' => input('title'),
            'parent' => input('parent', ''),
            'circuit_logo' => $img_one,
            'circuit_pic' => implode(',', $allFiles),
            'bodytext' => input('bodytext'),
            'tags' => input('tags'),
            'created_at' => date('Y-m-d H:i:s'),
            'visitor' => input('visitor', 1),
            'sorting' => input('sorting', 1)
        ];

        // 设置添加的记录
        if (!$pid && $modPid = input('parent', 0)) {
            session('modPid', $modPid);
        }

        $continue = input('continue', 0, 'intval');
        if (!is_null($circuit) && $pid) {
            $theData['circuit_id'] = $pid;
            Db::name('plug_circuit')->update($theData);
            if ($continue) {
                $this->success('电路图集修改成功, 继续编辑', url('admin/Library/circuit_add', ['id' => $pid]));
            } else {
                $this->success('电路图集修改成功,返回列表页', url('admin/Library/circuit_index'));
            }
            $this->success('电路图集修改成功,返回列表页', url('admin/Library/circuit_index'));
        } else {
            Db::name('plug_circuit')->insert($theData);
            if ($continue) {
                $this->success('电路图集添加成功,继续发布', url('admin/Library/circuit_add'));
            } else {
                $this->success('电路图集添加成功,返回列表页', url('admin/Library/circuit_index'));
            }
        }
    }

    public function circuit_image_sort()
    {
        $id = input('id');
        $model = Db::name('plug_circuit')->where('circuit_id', $id)->find();
        if ($model) {
            $params = request()->post('obj/a');
            $images = [];
            foreach ($params as $img) {
                $images[] = $img['caption'];
            }
            Db::name('plug_circuit')->update([
                'circuit_id' => $id,
                'circuit_pic' => implode(',', $images)
            ]);
        }
        exit;
    }

    public function circuit_image_del()
    {
        $id = input('id');
        $model = Db::name('plug_circuit')->where('circuit_id', $id)->find();
        $k = request()->post('key', false);
        $result = false;
        if ($model && $k) {
            $k--;
            $images = explode(',', $model['circuit_pic']);
            if (isset($images[$k]) && file_exists(ROOT_PATH . ltrim('/', $images[$k]))) {
                unset($images[$k]);
                @unlink(ROOT_PATH . ltrim('/', $images[$k]));
            }
            $images = array_merge($images, []);
            Db::name('plug_circuit')->update([
                'circuit_id' => $id,
                'circuit_pic' => implode(',', $images)
            ]);
            $result = true;
        }
        echo $result;
        exit;
    }

    public function catecircuit_save()
    {
        $params = [];
        $pid = input('cate_id', 0);
        $category = null;
        if ($pid) {
            $category = Db::name('plug_catecircuit')->where(['cate_id' => $pid])->find();
            $params['cate_id'] = $pid;
        }
        //单图
        $img_one = $img_one1 = '';
        $file = request()->file('pic');
        $file1 = request()->file('file1');
        if ($file) {
            $validate = config('upload_validate');
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
                    $this->error($file->getError(), url('admin/Library/catecircuit_add', $params));//否则就是上传错误，显示错误原因
                }
            }
        } else {
            $img_one = $category['cate_pic'];
        }

        if ($file1) {
            $validate = config('upload_validate');
            $info = $file1->validate($validate)->rule('uniqid')->move(ROOT_PATH . config('upload_path') . DS . date('Y-m-d'));
            if ($info) {
                $img_url1 = config('upload_path') . '/' . date('Y-m-d') . '/' . $info->getFilename();
                //写入数据库
                $data['uptime'] = time();
                $data['filesize'] = $info->getSize();
                $data['path'] = $img_url1;
                Db::name('plug_files')->insert($data);
                $img_one1 = $img_url1;
            } else {
                $this->error($file->getError(), url('admin/Library/catecircuit_add', $params));//否则就是上传错误，显示错误原因
            }
        } else {
            $img_one1 = $category['logo_pic'];
        }

        if (trim($img_one) != '' && $category && $category['cate_pic'] != $img_one) {
            $origFile = dirname(APP_PATH) . str_replace('/', DIRECTORY_SEPARATOR, $category['cate_pic']);
            if (file_exists($origFile)) {
                Db::name('plug_files')->delete(['path' => $category['cate_pic']]);
                @unlink($origFile);
            }
        }

        if (trim($img_one1) != '' && $category && $category['logo_pic'] != $img_one1) {
            $origFile1 = dirname(APP_PATH) . str_replace('/', DIRECTORY_SEPARATOR, $category['logo_pic']);
            if (file_exists($origFile1)) {
                Db::name('plug_files')->delete(['path' => $category['logo_pic']]);
                @unlink($origFile1);
            }
        }

        $theData = [
            'title' => input('title'),
            'cate_pic' => $img_one,
            'logo_pic' => $img_one1,
            'sorting' => input('sorting', 1)
        ];

        $continue = input('continue', 0, 'intval');
        if (!is_null($category) && $pid) {
            $theData['cate_id'] = $pid;
            Db::name('plug_catecircuit')->update($theData);
            $this->success('电路图厂商修改成功,返回列表页', url('admin/Library/catecircuit_index'));
        } else {
            Db::name('plug_catecircuit')->insert($theData);
            if ($continue) {
                $this->success('电路图厂商添加成功,继续发布', url('admin/Library/catecircuit_add'));
            } else {
                $this->success('电路图厂商添加成功,返回列表页', url('admin/Library/catecircuit_index'));
            }
        }
    }

    public function circuit_edit()
    {
        $this->redirect('circuit_add', ['id' => input('id')]);
    }

    public function catecircuit_edit()
    {
        $this->redirect('catecircuit_add', ['id' => input('id')]);
    }

    public function circuit_sorting()
    {
        if (!request()->isAjax()) {
            $this->error('提交方式不正确', url('admin/Library/circuit_index'));
        } else {
            $list = [];
            foreach (input('post.') as $pid => $sorting) {
                $list[] = ['circuit_id' => $pid, 'sorting' => $sorting];
            }
            $vm = new CircuitModel();
            $vm->saveAll($list);
            $this->success('排序更新成功', url('admin/Library/circuit_index'));
        }
    }

    public function catecircuit_sorting()
    {
        if (!request()->isAjax()) {
            $this->error('提交方式不正确', url('admin/Library/catecircuit_index'));
        } else {
            $list = [];
            foreach (input('post.') as $pid => $sorting) {
                $list[] = ['cate_id' => $pid, 'sorting' => $sorting];
            }
            $vm = new CateCircuitModel();
            $vm->saveAll($list);
            $this->success('排序更新成功', url('admin/Library/catecircuit_index'));
        }
    }

    public function circuit_delete()
    {
        $p = input('p');
        $ids = input('circuit/a');
        if (empty($ids)) {
            $this->error("请选择删除产品信息", url('admin/Library/circuit_index', ['p' => $p]));
        }
        if (is_array($ids)) {//判断获取文章ID的形式是否数组
            $where = 'circuit_id in(' . implode(',', $ids) . ')';
        } else {
            $where = 'circuit_id =' . $ids;
        }

        $needDeleteFiles = Db::name('plug_circuit')->where($where . ' AND circuit_pic != ""')->select();
        if ($needDeleteFiles) {
            foreach ($needDeleteFiles as $item) {
                $files = explode(',', $item['circuit_pic']);
                foreach ($files as $file) {
                    if (file_exists(ROOT_PATH . ltrim($file, '/'))) {
                        @unlink(ROOT_PATH . ltrim($file, '/'));
                    }
                }
            }
        }

        $needDeleteFiles = Db::name('plug_circuit')->where($where . ' AND circuit_logo != ""')->select();
        if ($needDeleteFiles) {
            foreach ($needDeleteFiles as $file) {
                if (file_exists(ROOT_PATH . ltrim($file['circuit_logo'], '/'))) {
                    @unlink(ROOT_PATH . ltrim($file['circuit_logo'], '/'));
                }
            }
        }

        $delProduct = new CircuitModel();
        $rst = $delProduct->where($where)->delete();
        if ($rst !== false) {
            $this->success("成功删除电路图集！", url('admin/Library/circuit_index', ['p' => $p]));
        } else {
            $this->error("删除电路图集失败！", url('admin/Library/circuit_index', ['p' => $p]));
        }
    }

    public function catecircuit_delete()
    {
        $p = input('p');
        $ids = input('cate/a');
        if (empty($ids)) {
            $this->error("请选择删除电路图厂商信息", url('admin/Library/catecircuit_index', ['p' => $p]));
        }
        if (is_array($ids)) {//判断获取文章ID的形式是否数组
            $where = 'cate_id in(' . implode(',', $ids) . ')';
        } else {
            $where = 'cate_id =' . $ids;
        }

        $needDeleteFiles = Db::name('plug_catecircuit')->where($where . ' AND cate_pic != ""')->select();
        if ($needDeleteFiles) {
            foreach ($needDeleteFiles as $file) {
                if (file_exists(ROOT_PATH . ltrim($file['cate_pic'], '/'))) {
                    @unlink(ROOT_PATH . ltrim($file['cate_pic'], '/'));
                }
            }
        }

        $delProduct = new CateCircuitModel();
        $rst = $delProduct->where($where)->delete();
        if ($rst !== false) {
            $this->success("成功删除电路图厂商！", url('admin/Library/catecircuit_index', ['p' => $p]));
        } else {
            $this->error("删除电路图厂商失败！", url('admin/Library/catecircuit_index', ['p' => $p]));
        }
    }


    /********************
     * Repair
     *******************/
    public function caterepair_index()
    {
        //栏目数据
        $menu_text = menu_text($this->lang);
        $this->assign('menu', $menu_text);

        $model = Db::name('plug_caterepair');
        $categories = $model->order('sorting asc')->paginate(config('paginate.list_rows'), false, ['query' => get_query()]);

        $show = $categories->render();
        $this->assign('page', $show);

        $this->assign('categories', $categories);
        return $this->fetch();
    }

    public function repair_index()
    {
        //栏目数据
        $menu_text = menu_text($this->lang);
        $this->assign('menu', $menu_text);

        $model = Db::name('plug_repair');
        $repairs = $model->order('sorting asc')->paginate(config('paginate.list_rows'), false, ['query' => get_query()]);

        $show = $repairs->render();
        $this->assign('page', $show);

        $this->assign('circuits', $repairs);
        return $this->fetch();
    }

    public function repair_add()
    {
        $id = input('id', 0);
        $this->assign('files', false);
        $this->assign('repair', false);
        if ($id) {
            session('modPid', null);
            $repair = Db::name('plug_repair')->where(['circuit_id' => $id])->find();
            if (is_null($repair)) {
                $this->error('无效的维修资料', url('admin/Library/repair_add'));
            }

            if ($repair['circuit_pic'] != '') {
                $files = [];
                $filesName = explode(',', $repair['circuit_pic']);
                foreach ($filesName as $name) {
                    $files[] = [
                        'caption' => $name,
                        'size' => filesize(ROOT_PATH . $name)
                    ];
                }
                $this->assign('files', $files);
            }
            $this->assign('repair', $repair);
        }

        $categories = Db::name('plug_caterepair')->order('sorting ASC')->select();
        $this->assign('categories', $categories);

        $menu_text = menu_text($this->lang);
        $this->assign('menu', $menu_text);

        if (!is_null($modPid = session('modPid'))) {
            $this->assign('modPid', $modPid);
        }
        return $this->fetch();
    }

    public function caterepair_add()
    {
        $id = input('id', 0);
        $this->assign('category', false);
        if ($id) {
            $category = Db::name('plug_caterepair')->where(['cate_id' => $id])->find();
            if (is_null($category)) {
                $this->error('无效的维修资料厂商', url('admin/Library/caterepair_add'));
            }
            $this->assign('category', $category);
        }
        $menu_text = menu_text($this->lang);
        $this->assign('menu', $menu_text);
        return $this->fetch();
    }

    public function repair_save()
    {
        $params = [];
        $pid = input('circuit_id', 0);
        $repair = null;
        if ($pid) {
            $repair = Db::name('plug_repair')->where(['circuit_id' => $pid])->find();
            $params['circuit_id'] = $pid;
        }
        $useWater = input('use_water', 0);

        //多图
        $allFiles = [];
        $files = request()->file('pic_all');
        if ($files) {
            $validate = config('upload_validate');
            if ($files) {
                $currentDay = date('Y-m-d');
                foreach ($files as $file) {
                    $info = $file->validate($validate)->rule('uniqid')->move(ROOT_PATH . config('upload_path') . DS . $currentDay);
                    if ($info) {
                        $thePath = config('upload_path') . '/' . $currentDay . '/';
                        $imgSrc = $thePath . $info->getSaveName();
                        if ($useWater) {
                            $imgSource = ROOT_PATH . ltrim($imgSrc, '/');
                            $sys = Options::get_options('site_options', $this->lang);
                            if ($sys['site_water'] && file_exists(ROOT_PATH . $sys['site_water'])) {
                                $water = ROOT_PATH . ltrim($sys['site_water'], '/');
                                $image = Image::open($imgSource);
                                $image->water($water, 5)->save($imgSource);
                                $imgSrc = $thePath . $info->getSaveName();
                            }
                        }
                        //写入数据库
                        $data['uptime'] = time();
                        $data['filesize'] = $info->getSize();
                        $data['path'] = $imgSrc;
                        Db::name('plug_files')->insert($data);
                        $allFiles[] = $imgSrc;
                    } else {
                        $this->error($file->getError(), url('admin/Library/mod_add', $params));//否则就是上传错误，显示错误原因
                    }
                }
            }
        }
        // 检测图片
        if ($repair) {
            $oldFiles = [];
            if(trim($repair['circuit_pic']) != '') {
                $oldFiles = explode(',', $repair['circuit_pic']);
            }
            $allFiles = array_merge($allFiles, $oldFiles);
        }

        $theData = [
            'title' => input('title'),
            'parent' => input('parent', ''),
            'circuit_pic' => implode(',', $allFiles),
            'bodytext' => input('bodytext'),
            'tags' => input('tags'),
            'created_at' => date('Y-m-d H:i:s'),
            'visitor' => input('visitor', 1),
            'sorting' => input('sorting', 1)
        ];

        // 设置添加的记录
        if (!$pid && $modPid = input('parent', 0)) {
            session('modPid', $modPid);
        }

        $continue = input('continue', 0, 'intval');
        if (!is_null($repair) && $pid) {
            $theData['circuit_id'] = $pid;
            Db::name('plug_repair')->update($theData);
            if ($continue) {
                $this->success('维修资料修改成功, 继续编辑', url('admin/Library/repair_add', ['id' => $pid]));
            } else {
                $this->success('维修资料修改成功,返回列表页', url('admin/Library/repair_index'));
            }
            $this->success('维修资料修改成功,返回列表页', url('admin/Library/repair_index'));
        } else {
            Db::name('plug_repair')->insert($theData);
            if ($continue) {
                $this->success('维修资料添加成功,继续发布', url('admin/Library/repair_add'));
            } else {
                $this->success('维修资料添加成功,返回列表页', url('admin/Library/repair_index'));
            }
        }
    }

    public function repair_image_sort()
    {
        $id = input('id');
        $model = Db::name('plug_repair')->where('circuit_id', $id)->find();
        if ($model) {
            $params = request()->post('obj/a');
            $images = [];
            foreach ($params as $img) {
                $images[] = $img['caption'];
            }
            Db::name('plug_repair')->update([
                'circuit_id' => $id,
                'circuit_pic' => implode(',', $images)
            ]);
        }
        exit;
    }

    public function repair_image_del()
    {
        $id = input('id');
        $model = Db::name('plug_repair')->where('circuit_id', $id)->find();
        $k = request()->post('key', false);
        $result = false;
        if ($model && $k) {
            $k--;
            $images = explode(',', $model['circuit_pic']);
            if (isset($images[$k]) && file_exists(ROOT_PATH . ltrim('/', $images[$k]))) {
                unset($images[$k]);
                @unlink(ROOT_PATH . ltrim('/', $images[$k]));
            }
            $images = array_merge($images, []);
            Db::name('plug_repair')->update([
                'circuit_id' => $id,
                'circuit_pic' => implode(',', $images)
            ]);
            $result = true;
        }
        echo $result;
        exit;
    }

    public function caterepair_save()
    {
        $params = [];
        $pid = input('cate_id', 0);
        $category = null;
        if ($pid) {
            $category = Db::name('plug_caterepair')->where(['cate_id' => $pid])->find();
            $params['cate_id'] = $pid;
        }
        //单图
        $img_one = $img_one1 = '';
        $file = request()->file('pic');
        $file1 = request()->file('file1');
        if ($file) {
            $validate = config('upload_validate');
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
                    $this->error($file->getError(), url('admin/Library/caterepair_add', $params));//否则就是上传错误，显示错误原因
                }
            }
        } else {
            $img_one = $category['cate_pic'];
        }

        if ($file1) {
            $validate = config('upload_validate');
            $info = $file1->validate($validate)->rule('uniqid')->move(ROOT_PATH . config('upload_path') . DS . date('Y-m-d'));
            if ($info) {
                $img_url1 = config('upload_path') . '/' . date('Y-m-d') . '/' . $info->getFilename();
                //写入数据库
                $data['uptime'] = time();
                $data['filesize'] = $info->getSize();
                $data['path'] = $img_url1;
                Db::name('plug_files')->insert($data);
                $img_one1 = $img_url1;
            } else {
                $this->error($file->getError(), url('admin/Library/catecircuit_add', $params));//否则就是上传错误，显示错误原因
            }
        } else {
            $img_one1 = $category['logo_pic'];
        }

        if (trim($img_one) != '' && $category && $category['cate_pic'] != $img_one) {
            $origFile = dirname(APP_PATH) . str_replace('/', DIRECTORY_SEPARATOR, $category['cate_pic']);
            if (file_exists($origFile)) {
                Db::name('plug_files')->delete(['path' => $category['cate_pic']]);
                @unlink($origFile);
            }
        }

        if (trim($img_one1) != '' && $category && $category['logo_pic'] != $img_one1) {
            $origFile1 = dirname(APP_PATH) . str_replace('/', DIRECTORY_SEPARATOR, $category['logo_pic']);
            if (file_exists($origFile1)) {
                Db::name('plug_files')->delete(['path' => $category['logo_pic']]);
                @unlink($origFile1);
            }
        }

        $theData = [
            'title' => input('title'),
            'cate_pic' => $img_one,
            'logo_pic' => $img_one1,
            'sorting' => input('sorting', 1)
        ];

        $continue = input('continue', 0, 'intval');
        if (!is_null($category) && $pid) {
            $theData['cate_id'] = $pid;
            Db::name('plug_caterepair')->update($theData);
            $this->success('维修资料厂商修改成功,返回列表页', url('admin/Library/caterepair_index'));
        } else {
            Db::name('plug_caterepair')->insert($theData);
            if ($continue) {
                $this->success('维修资料厂商添加成功,继续发布', url('admin/Library/caterepair_add'));
            } else {
                $this->success('维修资料厂商添加成功,返回列表页', url('admin/Library/caterepair_index'));
            }
        }
    }

    public function repair_edit()
    {
        $this->redirect('repair_add', ['id' => input('id')]);
    }

    public function caterepair_edit()
    {
        $this->redirect('caterepair_add', ['id' => input('id')]);
    }

    public function repair_sorting()
    {
        if (!request()->isAjax()) {
            $this->error('提交方式不正确', url('admin/Library/repair_index'));
        } else {
            $list = [];
            foreach (input('post.') as $pid => $sorting) {
                $list[] = ['circuit_id' => $pid, 'sorting' => $sorting];
            }
            $vm = new RepairModel();
            $vm->saveAll($list);
            $this->success('排序更新成功', url('admin/Library/repair_index'));
        }
    }

    public function caterepair_sorting()
    {
        if (!request()->isAjax()) {
            $this->error('提交方式不正确', url('admin/Library/caterepair_index'));
        } else {
            $list = [];
            foreach (input('post.') as $pid => $sorting) {
                $list[] = ['cate_id' => $pid, 'sorting' => $sorting];
            }
            $vm = new CateRepairModel();
            $vm->saveAll($list);
            $this->success('排序更新成功', url('admin/Library/caterepair_index'));
        }
    }

    public function repair_delete()
    {
        $p = input('p');
        $ids = input('circuit/a');
        if (empty($ids)) {
            $this->error("请选择删除维修资料", url('admin/Library/repair_index', ['p' => $p]));
        }
        if (is_array($ids)) {//判断获取文章ID的形式是否数组
            $where = 'circuit_id in(' . implode(',', $ids) . ')';
        } else {
            $where = 'circuit_id =' . $ids;
        }

        $needDeleteFiles = Db::name('plug_repair')->where($where . ' AND circuit_pic != ""')->select();
        if ($needDeleteFiles) {
            foreach ($needDeleteFiles as $item) {
                $files = explode(',', $item['circuit_pic']);
                foreach ($files as $file) {
                    if (file_exists(ROOT_PATH . ltrim($file, '/'))) {
                        @unlink(ROOT_PATH . ltrim($file, '/'));
                    }
                }
            }
        }

        $needDeleteFiles = Db::name('plug_repair')->where($where . ' AND circuit_logo != ""')->select();
        if ($needDeleteFiles) {
            foreach ($needDeleteFiles as $file) {
                if (file_exists(ROOT_PATH . ltrim($file['circuit_logo'], '/'))) {
                    @unlink(ROOT_PATH . ltrim($file['circuit_logo'], '/'));
                }
            }
        }

        $delProduct = new RepairModel();
        $rst = $delProduct->where($where)->delete();
        if ($rst !== false) {
            $this->success("成功删除维修资料！", url('admin/Library/repair_index', ['p' => $p]));
        } else {
            $this->error("删除维修资料失败！", url('admin/Library/repair_index', ['p' => $p]));
        }
    }

    public function caterepair_delete()
    {
        $p = input('p');
        $ids = input('cate/a');
        if (empty($ids)) {
            $this->error("请选择删除维修资料厂商信息", url('admin/Library/caterepair_index', ['p' => $p]));
        }
        if (is_array($ids)) {//判断获取文章ID的形式是否数组
            $where = 'cate_id in(' . implode(',', $ids) . ')';
        } else {
            $where = 'cate_id =' . $ids;
        }

        $needDeleteFiles = Db::name('plug_caterepair')->where($where . ' AND cate_pic != ""')->select();
        if ($needDeleteFiles) {
            foreach ($needDeleteFiles as $file) {
                if (file_exists(ROOT_PATH . ltrim($file['cate_pic'], '/'))) {
                    @unlink(ROOT_PATH . ltrim($file['cate_pic'], '/'));
                }
            }
        }

        $delProduct = new CateRepairModel();
        $rst = $delProduct->where($where)->delete();
        if ($rst !== false) {
            $this->success("成功删除维修资料厂商！", url('admin/Library/caterepair_index', ['p' => $p]));
        } else {
            $this->error("删除维修资料厂商失败！", url('admin/Library/caterepair_index', ['p' => $p]));
        }
    }
}

