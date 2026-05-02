<?php

namespace app\api\controller;

use app\api\model\Cart as CartModel;
use app\api\model\Order as OrderModel;
use app\api\service\order\Checkout as CheckoutModel;
use app\api\validate\order\Checkout as CheckoutValidate;

/**
 * 订单控制器
 * Class Order
 * @package app\api\controller
 */
class Order extends Controller
{
    /* @var \app\api\model\User $user */
    private $user;

    /* @var CheckoutValidate $validate */
    private $validate;

    /**
     * 构造方法
     * @throws \app\common\exception\BaseException
     * @throws \think\exception\DbException
     */
    public function _initialize()
    {
        parent::_initialize();
        // 用户信息
        $this->user = $this->getUser();
        // 验证类
        $this->validate = new CheckoutValidate;
    }

    /**
     * @OA\Post(
     *     path="/order/buyNow",
     *     tags={"Order"},
     *     summary="Mua ngay (Tạo đơn hàng)",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="goods_id", type="integer"),
     *             @OA\Property(property="goods_num", type="integer"),
     *             @OA\Property(property="goods_sku_id", type="string"),
     *             @OA\Property(property="address_id", type="integer"),
     *             @OA\Property(property="pay_type", type="integer", description="10: Wechat, 20: Balance, 30: ZaloPay")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Đơn hàng đã tạo")
     * )
     * @OA\Get(
     *     path="/order/buyNow",
     *     tags={"Order"},
     *     summary="Xác nhận đơn hàng mua ngay",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="goods_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="goods_num", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="goods_sku_id", in="query", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Thông tin đơn hàng")
     * )
     */
    public function buyNow()
    {
        // 实例化结算台服务
        $Checkout = new CheckoutModel;
        // 订单结算api参数
        $params = $Checkout->setParam($this->getParam([
            'goods_id' => 0,
            'goods_num' => 0,
            'goods_sku_id' => '',
            'address_id' => 0
        ]));
      
        // 表单验证
        if (!$this->validate->scene('buyNow')->check($params)) {
            return $this->renderError($this->validate->getError());
        }
        // 立即购买：获取订单商品列表
        $model = new OrderModel;
        $goodsList = $model->getOrderGoodsListByNow(
            $params['goods_id'],
            $params['goods_sku_id'],
            $params['goods_num']
        );
        // 获取订单确认信息
        $orderInfo = $Checkout->onCheckout($this->user, $goodsList);
  
        if ($this->request->isGet()) {
            return $this->renderSuccess($orderInfo);
        }
      
        // 订单结算提交
        if ($Checkout->hasError()) {
            return $this->renderError($Checkout->getError());
        }
        
        // 创建订单
        if (!$Checkout->createOrder($orderInfo)) {
            return $this->renderError($Checkout->getError() ?: '订单创建失败');
        }
             
        // 构建微信支付请求
        $payment = $model->onOrderPayment($this->user, $Checkout->model, $params['pay_type']);
        // 返回结算信息
        return $this->renderSuccess([
            'order_id' => $Checkout->model['order_id'],   // 订单id
            'pay_type' => $params['pay_type'],  // 支付方式
            'payment' => $payment               // 微信支付参数
        ], ['success' => '支付成功', 'error' => '订单未支付']);
    }

    /**
     * @OA\Post(
     *     path="/order/cart",
     *     tags={"Order"},
     *     summary="Thanh toán từ giỏ hàng",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="cart_ids", type="string"),
     *             @OA\Property(property="pay_type", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Đơn hàng đã tạo")
     * )
     * @OA\Get(
     *     path="/order/cart",
     *     tags={"Order"},
     *     summary="Xác nhận đơn hàng từ giỏ hàng",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="cart_ids", in="query", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Thông tin đơn hàng")
     * )
     */
    public function cart()
    {
        // 实例化结算台服务
        $Checkout = new CheckoutModel;
        // 订单结算api参数
        $params = $Checkout->setParam($this->getParam([
            'cart_ids' => '',
        ]));
        
        // 商品结算信息
        $CartModel = new CartModel($this->user);
        // 购物车商品列表
        $goodsList = $CartModel->getList($params['cart_ids']);
       
        // 获取订单结算信息
        $orderInfo = $Checkout->onCheckout($this->user, $goodsList);
        // dump($orderInfo);die;
        if ($this->request->isGet()) {
            return $this->renderSuccess($orderInfo);
        }
        // 创建订单
        if (!$Checkout->createOrder($orderInfo)) {
            return $this->renderError($Checkout->getError() ?: '订单创建失败');
        }
        // 移出购物车中已下单的商品
        $CartModel->clearAll($params['cart_ids']);
        // 构建微信支付请求
        $payment = $Checkout->onOrderPayment();
        // 返回状态
        return $this->renderSuccess([
            'order_id' => $Checkout->model['order_id'],   // 订单id
            'pay_type' => $params['pay_type'],  // 支付方式
            'payment' => $payment               // 微信支付参数
        ], ['success' => '支付成功', 'error' => '订单未支付']);
    }

    /**
     * 订单结算提交的参数
     * @param array $define
     * @return array
     */
    private function getParam($define = [])
    {
        return array_merge($define, $this->request->param());
    }

}
