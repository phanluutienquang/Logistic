<?php

namespace app\api\controller;

use app\api\model\Cart as CartModel;
use app\api\service\order\Checkout as CheckoutModel;

/**
 * 购物车管理
 * Class Cart
 * @package app\api\controller
 */
class Cart extends Controller
{
    /* @var \app\api\model\User $user */
    private $user;

    /* @var \app\api\model\Cart $model */
    private $model;

    /**
     * 构造方法
     * @throws \app\common\exception\BaseException
     * @throws \think\exception\DbException
     */
    public function _initialize()
    {
        parent::_initialize();
        $this->user = $this->getUser();
        $this->model = new CartModel($this->user);
    }

    /**
     * @OA\Get(
     *     path="/cart/lists",
     *     tags={"Cart"},
     *     summary="Lấy danh sách giỏ hàng",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="cart_ids", in="query", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Danh sách giỏ hàng")
     * )
     */
    public function lists()
    {
        // 请求参数
        $param = $this->request->param();
        $cartIds = isset($param['cart_ids']) ? $param['cart_ids'] : '';
        // 购物车商品列表
        $goodsList = $this->model->getList($cartIds);
        // 获取订单结算信息
        $Checkout = new CheckoutModel;
        $orderInfo = $Checkout->onCheckout($this->user, $goodsList);
        return $this->renderSuccess($orderInfo);
    }

    /**
     * @OA\Post(
     *     path="/cart/add",
     *     tags={"Cart"},
     *     summary="Thêm vào giỏ hàng",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="goods_id", type="integer"),
     *             @OA\Property(property="goods_num", type="integer"),
     *             @OA\Property(property="goods_sku_id", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Thành công")
     * )
     */
    public function add($goods_id, $goods_num, $goods_sku_id)
    {
        if (!$this->model->add($goods_id, $goods_num, $goods_sku_id)) {
            return $this->renderError($this->model->getError() ?: '加入购物车失败');
        }
        // 购物车商品总数量
        $totalNum = $this->model->getGoodsNum();
        return $this->renderSuccess(['cart_total_num' => $totalNum], '加入购物车成功');
    }

    /**
     * @OA\Post(
     *     path="/cart/sub",
     *     tags={"Cart"},
     *     summary="Giảm số lượng trong giỏ hàng",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="goods_id", type="integer"),
     *             @OA\Property(property="goods_sku_id", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Thành công")
     * )
     */
    public function sub($goods_id, $goods_sku_id)
    {
        $this->model->sub($goods_id, $goods_sku_id);
        return $this->renderSuccess();
    }

    /**
     * @OA\Post(
     *     path="/cart/delete",
     *     tags={"Cart"},
     *     summary="Xóa sản phẩm khỏi giỏ hàng",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="goods_sku_id", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Thành công")
     * )
     */
    public function delete($goods_sku_id)
    {
        // 删除指定商品
        $this->model->delete($goods_sku_id);
        return $this->renderSuccess('删除成功');
    }
    
    /**
     * 获取购物车数据
     * @param int $user_id 用户ID
     * @return array
     */
    private function getCartData($user_id)
    {
        // 获取购物车商品列表
        $cartList = $this->model->getList($user_id);
        
        // 初始化统计数据
        $cart_total_num = 0;      // 购物车商品总数量
        $order_total_price = 0;   // 购物车总金额
        
        // 统计数据
        foreach ($cartList as $item) {
            $cart_total_num += $item['total_num'];
            $order_total_price += $item['total_price'];
        }
        
        return [
            'cart_total_num' => $cart_total_num,
            'order_total_price' => number_format($order_total_price, 2, '.', ''),
            'goods_list' => $cartList  // 可选：返回商品列表
        ];
    }

}
