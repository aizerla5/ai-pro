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
use think\Exception;

class Account extends Base
{
    protected function _initialize()
    {
        parent::_initialize();
    }

    public function index()
    {
        // time
        $times = Db::name('time')->order('time_order ASC')->select();
        $this->assign('times', $times);

        $this->assign('sideA', 1);
        $this->assign('breadTitle', lang('Online booking'));
        return $this->fetch();
    }

    public function my()
    {
        $this->check_login();
        $this->assign('sideA', 2);
        $this->assign('breadTitle', lang('Booking query'));
        return $this->fetch();
    }

    public function notice()
    {
        $this->check_login();
        $this->assign('sideA', 3);
        $this->assign('breadTitle', lang('Message notification'));
        return $this->fetch();
    }

    public function add_order()
    {
        if (count($this->user) == 0 || !isset($this->user['member_list_id'])) {
            $this->error(lang('You must be login.'), url('home/Login/index'));
        }
        if (request()->isPost()) {
            $data = input('data', false);
            $date = input('date', false);
            if ($data && $date) {
                $post = [
                    'order_member_id' => $this->user['member_list_id'],
                    'order_date' => $date,
                    'order_data' => $data,
                    'order_order' => 1
                ];
                try {
                    $insertId = Db::name('order')->insert($post, false, true);
                    $this->success(lang('Booking success!'), url('home/Login/index'));
                } catch (Exception $e) {
                    exit(lang('The time you have chosen is reserved.'));
                }
            }
            exit(lang('Sorry, your reservation failed!'));
        }
        die('Hope you die. Thank you.');
    }

    /**
     * for events data xml
     */
    public function orders()
    {
        $start = input('start', false);
        $end = input('end', false);
        $scripts[] = '<events>';
        if ($start && $end) {
            $events = Db::name('order')
                ->field('*, COUNT(order_id) AS os')
                ->where('order_date', '>=', date('Y-m-d', $start))
                ->where('order_date', '<=', date('Y-m-d', $end))
                ->where('order_allow', 1)
                ->group('order_date')
                ->select();

            if (count($events)) {
                foreach ($events as $event) {
                    list($date, $time) = explode(' ', $event['order_date']);
                    $os = intval($event['os']);
                    $scripts[] = sprintf(
                        '<event title="%s" start="%s" class="%s"/>',
                        $event['os'] . ' ' . lang('bookings'),
                        $date,
                        ($os == 3 ? 'label-warning' : 'label-info')
                    );
                    if ($os === 3) {
                        $scripts[] = sprintf(
                            '<event title="%s" start="%s" class="%s"/>',
                            lang('Not to make an appointment'),
                            $date,
                            'label-gray'
                        );
                    }
                }
            }
        }
        $scripts[] = '</events>';
        echo implode("\n", $scripts);
        exit;
    }

    public function day_book()
    {
        if (request()->isPost()) {
            $date = input('d', false);
            if ($date) {
                $items = Db::name('order')->alias('o')->field('o.order_data, b.member_list_nickname')
                    ->join(config('database.prefix') . 'member_list b', 'o.order_member_id =b.member_list_id')
                    ->where('o.order_date', $date . ' 00:00:00')
                    ->where('o.order_allow', 1)
                    ->select();

                if (count($items)) {
                    exit(json_encode(['items' => $items]));
                }
            }
            exit(json_encode(['flag' => 1]));
        }
        die('Hope you die. Thank you.');
    }

    /**
     * 保存用户资料
     */
    public function save()
    {
        $this->check_login();
        if (request()->isAjax()) {
            $userId = session('hid');
            $result = Db::name('member_list')->update([
                'member_list_id' => $userId,
                'member_list_tel' => input('telephone'),
                'realname' => input('name'),
                'shipping_address' => input('address')
            ]);

            if ($result != 0) {
                $this->success('成功保存！', url('home/Account/index'));
            }
        }
        $this->error('没有什么可修改的，你要干嘛呢？', url('home/Account/index'));
    }
}
