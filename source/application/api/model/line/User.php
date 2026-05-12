<?php

namespace app\api\model\line;

use app\common\model\BaseModel;

/**
 * LINE用户模型
 * Class User
 * @package app\api\model\line
 */
class User extends BaseModel
{
    protected $name = 'line_user';
    protected $pk = 'id';
    protected $autoWriteTimestamp = false;

    /**
     * 获取用户信息
     * @param $line_user_id
     * @param $wxapp_id
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public static function detail($line_user_id, $wxapp_id)
    {
        return self::where([
            'line_user_id' => $line_user_id,
            'wxapp_id' => $wxapp_id
        ])->find();
    }
}
