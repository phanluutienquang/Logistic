<?php

namespace app\api\controller;

use app\api\model\Goods as GoodsModel;
use app\api\model\Cart as CartModel;
use app\common\service\qrcode\Goods as GoodsPoster;

/**
 * 商品控制器
 * Class Goods
 * @package app\api\controller
 */
class Goods extends Controller
{
    /**
     * @OA\Get(
     *     path="/goods/lists",
     *     tags={"Goods"},
     *     summary="Lấy danh sách sản phẩm",
     *     @OA\Parameter(name="category_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="sortType", in="query", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Danh sách sản phẩm")
     * )
     */
    public function lists()
    {
        // 整理请求的参数
        $param = array_merge($this->request->param(), [
            'status' => 10
        ]);
        // 获取列表数据
        $model = new GoodsModel;
        $list = $model->getList($param, $this->getUser(false));
        return $this->renderSuccess(compact('list'));
    }

    /**
     * @OA\Get(
     *     path="/goods/detail",
     *     tags={"Goods"},
     *     summary="Lấy chi tiết sản phẩm",
     *     @OA\Parameter(name="goods_id", in="query", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Chi tiết sản phẩm")
     * )
     */
    public function detail($goods_id)
    {
        // 用户信息
        $user = $this->getUser(false);
        // 商品详情
        $model = new GoodsModel;
        $goods = $model->getDetails($goods_id, $this->getUser(false));
        if ($goods === false) {
            return $this->renderError($model->getError() ?: '商品信息不存在');
        }
        // 多规格商品sku信息, todo: 已废弃 v1.1.25
        $specData = $goods['spec_type'] == 20 ? $model->getManySpecData($goods['spec_rel'], $goods['sku']) : null;
        return $this->renderSuccess([
            // 商品详情
            'detail' => $goods,
            // 购物车商品总数量
            'cart_total_num' => $user ? (new CartModel($user))->getGoodsNum() : 0,
            // 多规格商品sku信息
            'specData' => $specData,
        ]);
    }

    /**
     * 生成商品海报
     * @param $goods_id
     * @return array
     * @throws \app\common\exception\BaseException
     * @throws \think\exception\DbException
     * @throws \Exception
     */
    public function poster($goods_id)
    {
        // 商品详情
        $detail = GoodsModel::detail($goods_id);
        $Qrcode = new GoodsPoster($detail, $this->getUser(false));
        return $this->renderSuccess([
            'qrcode' => $Qrcode->getImage(),
        ]);
    }

}
