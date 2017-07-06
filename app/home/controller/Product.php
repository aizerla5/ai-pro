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

class Product extends Base
{
    protected function _initialize()
    {
        parent::_initialize();
        $this->_get_page_image('product');
    }

    public function index()
    {
        // page id
        $pageId = input('page', false, 'intval');
        if ($pageId) {
            $productModel = Db::name('lib_product');
            $products = $productModel->order('sorting asc')->paginate(config('paginate.list_rows'), false, ['query' => get_query()]);
            if ($products->total()) {
                echo $this->view->fetch("product:ajax_load", ['products' => $products]);
            } else {
                echo 'null';
            }
            exit;
        }

        $this->assign('url', url('home/Product/index'));
        return $this->fetch();
    }

    public function detail()
    {
        $pId = input('p', 0, 'intval');
        $product = Db::name('lib_product')->where('product_id', $pId)->find();
        if (!$pId || !$product) {
            $this->error('错误的产品参数', url('home/Login/index'));
        }

        Db::name('lib_product')->where('product_id', $pId)->setInc('visitor');

        $this->assign('product', $product);
        return $this->fetch();
    }
}