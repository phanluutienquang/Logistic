<?php

namespace app\api\controller;

use app\common\enum\order\PayType as PayTypeEnum;
use app\common\enum\OrderType as OrderTypeEnum;
use app\api\model\Setting as SettingModel;

/**
 * ZaloPay Callback Controller
 */
class Zalopay
{
    /**
     * @OA\Post(
     *     path="/zalopay/webhook",
     *     tags={"ZaloPay"},
     *     summary="Webhook nhận kết quả thanh toán từ ZaloPay",
     *     description="ZaloPay Server sẽ gọi đến URL này để thông báo kết quả thanh toán",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="string", description="Dữ liệu JSON được encode thành string"),
     *             @OA\Property(property="mac", type="string", description="Mã xác thực dữ liệu")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Kết quả xử lý",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="return_code", type="integer", example=1),
     *             @OA\Property(property="return_message", type="string", example="success")
     *         )
     *     )
     * )
     */
    public function webhook()
    {
        $paytype = SettingModel::getItem('paytype');
        $key2 = isset($paytype['zalopay']['key2']) ? $paytype['zalopay']['key2'] : 'YOUR_ZALOPAY_KEY2_HERE';
        $postdata = file_get_contents('php://input');
        $postdatajson = json_decode($postdata, true);

        $result = [];

        try {
            if (!isset($postdatajson["data"]) || !isset($postdatajson["mac"])) {
                throw new \Exception("Invalid request");
            }

            $mac = hash_hmac("sha256", $postdatajson["data"], $key2);
            $requestmac = $postdatajson["mac"];

            // Kiểm tra MAC
            if (strcmp($mac, $requestmac) != 0) {
                $result["return_code"] = -1;
                $result["return_message"] = "mac not equal";
            } else {
                // Thanh toán thành công
                $datajson = json_decode($postdatajson["data"], true);
                
                $app_trans_id = $datajson["app_trans_id"];
                $amount = $datajson["amount"];
                $item = isset($datajson["item"]) ? json_decode($datajson["item"], true) : [];
                $description = isset($datajson["description"]) ? $datajson["description"] : '';

                $orderType = null;
                $orderNo = null;

                // 1. Cố gắng lấy từ item
                if (is_array($item) && !empty($item)) {
                    $firstItem = $item[0];
                    if (isset($firstItem['order_type'])) {
                        $orderType = $firstItem['order_type'];
                    }
                    if (isset($firstItem['order_no'])) {
                        $orderNo = $firstItem['order_no'];
                    }
                }

                // 2. Nếu không có trong item, cố gắng parse từ description 
                // (cho trường hợp frontend không gửi item mà chỉ gửi desc "Thanh toán đơn hàng ABC")
                if (!$orderType || !$orderNo) {
                    if (preg_match('/(?:đơn hàng|order)\s+([A-Za-z0-9_-]+)/i', $description, $matches)) {
                        $orderNo = $matches[1];
                        $orderType = OrderTypeEnum::TRAN; // Mặc định là thanh toán kiện hàng
                    } else if (strpos(mb_strtolower($description), 'nạp tiền') !== false) {
                        // Nạp tiền nhưng không có orderNo thì không thể tự cộng tiền được
                        // Frontend cần phải gửi thêm item hoặc mã nạp tiền
                        $orderType = OrderTypeEnum::RECHARGE;
                    }
                }

                if ($orderType && $orderNo) {
                    $modelClass = [
                        OrderTypeEnum::MASTER => 'app\api\service\order\PaySuccess',
                        OrderTypeEnum::SHARING => 'app\api\service\sharing\order\PaySuccess',
                        OrderTypeEnum::RECHARGE => 'app\api\service\recharge\PaySuccess',
                        OrderTypeEnum::TRAN => 'app\api\service\package\PaySuccess',
                        OrderTypeEnum::GRADE => 'app\api\service\grade\PaySuccess',
                    ];

                    if (isset($modelClass[$orderType])) {
                        $modelName = $modelClass[$orderType];
                        $model = new $modelName($orderNo);
                        
                        // Gọi service xử lý thanh toán thành công
                        $status = $model->onPaySuccess(PayTypeEnum::ZALOPAY, $datajson);
                        if (!$status) {
                            throw new \Exception($model->getError());
                        }
                    } else {
                        throw new \Exception("Unsupported order type");
                    }
                } else {
                    // Không xác định được đơn hàng, ghi log để xử lý tay
                    trace("ZaloPay Webhook: Cannot determine orderType or orderNo from app_trans_id " . $app_trans_id, 'error');
                }

                $result["return_code"] = 1;
                $result["return_message"] = "success";
            }
        } catch (\Exception $e) {
            $result["return_code"] = 0; // ZaloPay sẽ gọi lại webhook nếu return_code = 0
            $result["return_message"] = $e->getMessage();
        }

        return json($result);
    }
}
