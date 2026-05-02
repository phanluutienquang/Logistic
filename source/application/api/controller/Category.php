<?php

namespace app\api\controller;

use app\api\model\Category as CategoryModel;
use app\api\model\WxappCategory as WxappCategoryModel;

/**
 * 商品分类控制器
 * Class Goods
 * @package app\api\controller
 */
class Category extends Controller
{
    /**
     * @OA\Get(
     *     path="/category/index",
     *     tags={"Category"},
     *     summary="Lấy danh sách phân loại hàng hóa",
     *     @OA\Response(response=200, description="Danh sách phân loại")
     * )
     */
    public function index()
    {
        // 分类模板
        $templet = WxappCategoryModel::detail();
        // 商品分类列表
        $list = array_values(CategoryModel::getShopCacheTree());
        return $this->renderSuccess(compact('templet', 'list'));
    }
    
    
    /**
     * @OA\Get(
     *     path="/category/getParentCategory",
     *     tags={"Category"},
     *     summary="Lấy phân loại cha",
     *     @OA\Response(response=200, description="Danh sách phân loại cha")
     * )
     */
    public function getParentCategory()
    {
        // 商品分类列表
        $list = (new CategoryModel())->getParentCategory();
        return $this->renderSuccess(compact('list'));
    }

}
