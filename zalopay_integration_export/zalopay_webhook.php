<?php
/**
 * ZaloPay Webhook Callback
 * File này nhận kết quả thanh toán từ ZaloPay Server trả về
 */

// Key2 được cấp bởi ZaloPay khi đăng ký Merchant
$key2 = "YOUR_ZALOPAY_KEY2_HERE"; 

// Đọc dữ liệu post từ ZaloPay Server
$postdata = file_get_contents('php://input');
$postdatajson = json_decode($postdata, true);

$result = [];

try {
    if (!isset($postdatajson["data"]) || !isset($postdatajson["mac"])) {
        throw new Exception("Invalid request");
    }

    $mac = hash_hmac("sha256", $postdatajson["data"], $key2);
    $requestmac = $postdatajson["mac"];

    // Kiểm tra MAC xem dữ liệu có hợp lệ và đúng là từ ZaloPay gửi đến không
    if (strcmp($mac, $requestmac) != 0) {
        $result["return_code"] = -1;
        $result["return_message"] = "mac not equal";
    } else {
        // Thanh toán thành công
        $datajson = json_decode($postdatajson["data"], true);
        
        // Lấy thông tin đơn hàng
        $app_trans_id = $datajson["app_trans_id"]; // Mã giao dịch của ứng dụng
        $amount = $datajson["amount"];             // Số tiền thanh toán
        
        // Thông tin item hoặc description để biết là nạp tiền hay thanh toán kiện hàng
        // $item = json_decode($datajson["item"], true);

        // ==============================================================
        // TODO: Xử lý logic cập nhật trạng thái đơn hàng vào Database
        // ==============================================================
        // Ví dụ (Pseudo code):
        // $orderModel = new OrderModel();
        // $order = $orderModel->findByAppTransId($app_trans_id);
        // if ($order) {
        //     $order->is_pay = 1;
        //     $order->status = 6; // Đã thanh toán
        //     $order->save();
        // }

        $result["return_code"] = 1;
        $result["return_message"] = "success";
    }
} catch (Exception $e) {
    $result["return_code"] = 0; // ZaloPay sẽ gọi lại webhook nếu return_code = 0
    $result["return_message"] = $e->getMessage();
}

// Phản hồi lại cho ZaloPay
header('Content-Type: application/json');
echo json_encode($result);
