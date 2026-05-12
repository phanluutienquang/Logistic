# 13. Các status quan trọng toàn hệ thống

File này gom các status quan trọng về một chỗ để tránh nhầm lẫn.

Cảnh báo cực quan trọng:
- cùng số nhưng khác bảng thì nghĩa có thể khác
- đừng tái sử dụng status number giữa các aggregate nếu chưa kiểm tra kỹ

## 1. Package status

Nguồn tham chiếu chính:
- SQL `yoshop_package`
- `source/application/web/model/Package.php::getStatusAttr()`
- một số API count logic trong `source/application/api/model/Package.php`

### Map chính
- `1` = 未入库 / Chưa nhập kho
- `2` = 已入库 / Đã nhập kho
- `3` = 已拣货上架 / Đã lên kệ / chuẩn bị submit pack
- `4` = 待打包 / Chờ đóng gói
- `5` = 待支付 / Chờ thanh toán
- `6` = 已支付 / Đã thanh toán
- `7` = 已分拣下架 / Đã xuống kệ / vào batch / ready warehouse handling
- `8` = 已打包 / Đã đóng gói
- `9` = 已发货 / Đã phát hàng
- `10` = 已收货 / Đã nhận hàng
- `11` = 已完成 / Hoàn tất
- `-1` = 问题件 / Hàng lỗi / problem package

### Field liên quan khác trong package
- `is_take`
  - `1` = chờ nhận chủ
  - `2` = đã nhận chủ
  - `3` = bỏ kiện / discarded
  - `4` = trả hàng / return
- `is_verify`
  - `1` = đã kiểm
  - `2` = chờ kiểm
- `is_scan`
  - `1` = chưa scan xuất kho
  - `2` = đã scan xuất kho
- `is_shelf`
  - `0` = chưa lên kệ
  - `1` = đã lên kệ
  - `2` = đã xuống kệ

### Ghi chú debug
Trong `api/model/Package.php::querycount()` có một số nhóm status logic:
- status filter `0` = tất cả package active `[1..11]`
- status filter `2` = nhóm `[2,3,4]`
- status filter `4` = nhóm `[2,3,4]` nhưng `is_take = 2`
- status filter `8` = nhóm `[6,8]`

=> count/reporting có thể dùng status bucket riêng, không phải raw status 1-1.

-----------------------------------
## 2. Inpack status

Nguồn tham chiếu chính:
- SQL `yoshop_inpack`
- `source/application/web/model/Inpack.php::getStatusAttr()`
- `source/application/api/model/Inpack.php::getStatusAttr()`

### Comment trong SQL `yoshop_inpack`
- `1` = 待查验 / Chờ kiểm
- `2` = 待支付 / Chờ thanh toán
- `3` = 待发货 / chờ phát hàng
- `4` = 拣货中 / đang pick
- `5` = 已打包 / đã đóng gói
- `6` = 已发货 / đã phát hàng
- `7` = 已到货 / đến nơi
- `8` = 已完成 / hoàn tất
- `9` = 已取消 / hủy
- `10` = 草稿 / draft

### Text map trong model web/api
- `1` = 待查验
- `2` = 待支付
- `3` = 已支付
- `4` = 已拣货
- `5` = 已打包
- `6` = 已发货
- `7` = 已收货
- `8` = 已完成
- `-1` = 问题件

### Cách hiểu thực dụng
Vì comment SQL và text UI có lệch nhẹ về diễn đạt ở status 3/7:
- `2` chắc chắn là chờ thanh toán
- `3` thực tế là sau thanh toán, trước hoặc trong bước xuất hàng
- `4` là bước pick/warehouse handling
- `5` là đã đóng gói
- `6` là đã ship
- `7` là delivered/received phía đích
- `8` là complete business

### Field payment liên quan trong inpack
- `is_pay`
  - `1` = đã thanh toán
  - `2` = chưa thanh toán
  - `3` = chờ duyệt chứng từ / pending audit
- `is_pay_type`
  - comment SQL cho thấy có nhiều loại: backend, wechat, balance, cash, ...
- `pay_type`
  - `0` = mặc định phát ngay
  - `1` = COD
  - `2` = month-end / monthly settlement

-----------------------------------
## 3. Commerce order status

Nguồn tham chiếu chính:
- SQL `yoshop_order`
- `source/application/common/model/Order.php`
- `source/application/store/model/Order.php`

Commerce order có 4 nhóm status, không gói vào một field duy nhất.

### 3.1 `pay_status`
- `10` = 未付款 / pending payment
- `20` = 已付款 / paid

### 3.2 `delivery_status`
- `10` = 未发货 / pending shipment
- `20` = 已发货 / shipped

### 3.3 `receipt_status`
- `10` = 未收货 / pending receipt
- `20` = 已收货 / received

### 3.4 `order_status`
- `10` = 进行中 / in progress
- `20` = 已取消 / cancelled
- `21` = 待取消 / cancel requested / pending cancel
- `30` = 已完成 / completed

### State text tổng hợp từ `common/model/Order.php`
- nếu `order_status = 20` => 已取消
- nếu `order_status = 30` => 已完成
- nếu `pay_status = 10` => 待付款
- nếu `delivery_status = 10` => 已付款，待发货
- nếu `receipt_status = 10` => 已发货，待收货

-----------------------------------
## 4. Recharge order status

Nguồn tham chiếu:
- `web/model/recharge/Order.php`
- `store/model/recharge/Order.php`
- enum recharge/order pay status

Do repo dùng enum ở vài nơi, cách hiểu ngắn gọn:
- pending
- success
- failed/cancelled

Khi cần sửa logic recharge, phải mở enum cụ thể thay vì suy đoán từ số cứng.

-----------------------------------
## 5. Refund status

Nguồn tham chiếu:
- `common/model/OrderRefund.php`
- `store/controller/order/Refund.php`
- `store/model/OrderRefund.php`

Refund có state machine riêng, không nên đánh đồng với order status.

Khuyến nghị:
- sửa refund thì đọc model refund trước
- đừng assume `status = 20` ở refund có cùng nghĩa với `order_status = 20`

-----------------------------------
## 6. Sharing / social order status

Nguồn tham chiếu:
- `common/model/sharingGoods/Order.php`
- `store/model/sharing_back/Order.php`
- `task/behavior/sharing/Order.php`

Các sharing order nhìn rất giống commerce order thường:
- `pay_status`
- `delivery_status`
- `receipt_status`
- `order_status`

Nhưng đây là aggregate khác.

=> Khi debug social/group-buy, đừng dùng nhầm model order thường.

-----------------------------------
## 7. Dealer / withdraw / apply status

Các bảng dealer/withdraw thường có `apply_status` riêng.

Ví dụ search cho thấy có các giá trị như:
- `10` = trạng thái pending/apply
- `40` = trạng thái đã xử lý / hoàn tất trong ngữ cảnh withdraw

Khuyến nghị:
- với dealer/withdraw, luôn đọc model tương ứng trước khi sửa

-----------------------------------
## 8. Status rất hay bị nhầm

### Nhầm 1
`package.status = 5`
- nghĩa: chờ thanh toán logistics package-side

`inpack.status = 5`
- nghĩa: đã đóng gói

### Nhầm 2
`order_status = 20`
- commerce order: canceled

`delivery_status = 20`
- commerce order: shipped

`pay_status = 20`
- commerce order: paid

=> cùng số 20 nhưng 3 field, 3 nghĩa khác nhau.

### Nhầm 3
`inpack.status = 7`
- SQL comment nói gần nghĩa đến nơi / arrived
- UI text model lại hiển thị 已收货

=> nếu làm báo cáo hoặc API contract, phải chốt rõ nghĩa business với team.

-----------------------------------
## 9. Bảng tóm tắt nhanh

### Package
- 1 chưa nhập kho
- 2 đã nhập kho
- 3 đã lên kệ
- 4 chờ đóng gói
- 5 chờ thanh toán
- 6 đã thanh toán
- 7 xuống kệ/warehouse handling
- 8 đã đóng gói
- 9 đã ship
- 10 đã nhận
- 11 hoàn tất
- -1 lỗi

### Inpack
- 1 chờ kiểm
- 2 chờ thanh toán
- 3 sau thanh toán / ready ship
- 4 picking
- 5 packed
- 6 shipped
- 7 received/arrived
- 8 complete
- 9 cancelled
- 10 draft
- -1 problem

### Commerce order
- pay_status: 10 unpaid, 20 paid
- delivery_status: 10 not shipped, 20 shipped
- receipt_status: 10 not received, 20 received
- order_status: 10 active, 20 canceled, 21 pending cancel, 30 completed

-----------------------------------
## 10. Quy tắc vàng khi sửa status

1. Luôn xác định aggregate trước
- package?
- inpack?
- commerce order?
- recharge order?
- sharing order?

2. Không nhìn số status đơn lẻ
- luôn nhìn cùng field name và table name

3. Kiểm tra cả:
- SQL comment
- model getter/text map
- controller transition
- reporting/count bucket

4. Nếu sửa status transition
- test full flow đầu-cuối
- vì status thường kéo theo:
  - payment fields
  - balance logs
  - package/inpack sync
  - logistics history

-----------------------------------
## 11. Kết luận

Status là vùng dễ gây bug nhất sau tenant scope.

Nếu bạn chưa chắc mình đang nhìn status của aggregate nào, đừng sửa tiếp.
Hãy dừng lại và đối chiếu lại bảng + model + flow trước.