# 04. Các flow nghiệp vụ chính

Mục tiêu file này là mô tả flow theo business, không theo controller trước.

## 1. Flow A - Tenant onboarding

Nguồn code chính:
- `source/application/admin/controller/Store.php`
- `source/application/admin/model/Wxapp.php`
- `source/application/admin/model/store/User.php`

### Bước nghiệp vụ
1. Super admin tạo tenant mới
2. Hệ thống tạo record `wxapp`
3. Hệ thống clone setting mặc định
4. Hệ thống tạo super store user cho tenant
5. Hệ thống tạo help/page mặc định
6. Tenant có thể đăng nhập store backend

### Ý nghĩa business
Đây là flow “provisioning tenant” của SaaS.

-----------------------------------
## 2. Flow B - User đăng ký / đăng nhập

Nguồn code chính:
- `api/controller/Passport.php`
- `api/service/passport/Login.php`
- `web/controller/Passport.php`

### Nhánh 1: user thường bằng mobile/email
1. user submit mobile/email/password
2. validate uniqueness
3. hệ thống sinh `user_code` nếu tenant bật mode này
4. tạo record `yoshop_user`
5. login và sinh token/session

### Nhánh 2: WeChat
1. nhận code / encryptedData / iv
2. gọi decrypt / verify openid
3. nếu user tồn tại -> update login info
4. nếu chưa tồn tại -> auto create user
5. set session/token

### Nhánh 3: Zalo
1. nhận `zalo_user_id`
2. lookup theo `open_id`
3. nếu chưa có -> auto-register user Zalo
4. set session/token

### Nhánh 4: LINE
1. nhận `line_user_id`
2. lookup `yoshop_line_user`
3. nếu chưa có binding:
   - tạo system user
   - tạo line binding
4. nếu đã có binding:
   - update display/avatar
5. set session/token

### Nhánh 5: Clerk / warehouse staff
1. nhân sự kho đăng nhập
2. verify account nội bộ
3. tải thông tin user/kho liên quan
4. cấp quyền vận hành theo scope

-----------------------------------
## 3. Flow C - Package pre-alert / khai báo kiện hàng

Nguồn code chính:
- `web/controller/Package.php`
- `web/model/Package.php`
- `store/controller/package/Index.php`
- `store/controller/package/Newpack.php`

### Kịch bản user-side
1. user tạo package / pre-alert
2. nhập tracking number `express_num`
3. nhập loại hàng / item trong package
4. chọn quốc gia / kho / route nếu cần
5. lưu package vào `yoshop_package`
6. package có status đầu vào ban đầu

### Kịch bản system-side
- package có thể vào hệ thống từ nhiều source:
  - miniapp/web
  - backoffice nhập tay
  - import excel
  - API ingest
  - buyer order sync
  - warehouse nhập trực tiếp

### Business note
Package là inbound asset. Nó chưa phải đơn logistics cuối cùng.

-----------------------------------
## 4. Flow D - Package inbound vào kho

Nguồn code chính:
- `store/controller/package/Index.php`
- `store/controller/package/Report.php`
- `web/model/Package.php`
- `xinsuju.sql`: `yoshop_package`, `yoshop_shelf*`

### Ý nghĩa
Khi kiện thực tế đến kho:
1. nhân viên scan hoặc nhập tracking
2. xác định user/claim nếu cần
3. package chuyển từ “chờ nhập kho” sang “đã nhập kho”
4. package có thể được đưa lên shelf
5. package chờ người dùng submit để gom hàng

### Các điểm nghiệp vụ thường gặp
- package chưa nhận chủ (`is_take`, claim flow)
- package lỗi / không xác định user
- package có category/item cần sửa tay
- package có ảnh / chứng từ / ghi chú kho

-----------------------------------
## 5. Flow E - User chọn nhiều package để tạo inpack

Nguồn code chính:
- `web/controller/Package.php::inpack()`
- `web/model/Inpack.php`
- `web/model/Line.php`

Đây là flow logistics quan trọng nhất.

### Business flow thực tế
1. user chọn nhiều package
2. hệ thống check tất cả package hợp lệ
3. tất cả package phải cùng `member_id`
4. user chọn địa chỉ nhận quốc tế
5. user chọn line (tuyến vận chuyển)
6. hệ thống tính cước line
7. tạo `yoshop_inpack`
8. update các `package` liên quan sang trạng thái chờ thanh toán
9. cập nhật logistics history / notice

### Các kiểm tra quan trọng trong code
`web/controller/Package.php::inpack()` cho thấy:
- package list phải tồn tại đủ
- không được chọn package ở trạng thái không còn packable
- tất cả package phải cùng user
- address phải tồn tại
- line phải tồn tại
- phải tính được giá line

### Dữ liệu tạo ra
Record `inpack` chứa:
- `pack_ids`
- `address_id`
- `line_id`
- `free`
- `weight`, `cale_weight`, `volume`
- `pack_free`, `other_free`
- `member_id`
- `source = 1`

### Bản chất business
Đây là bước “hợp nhất nhu cầu gửi hàng” từ nhiều inbound package thành 1 shipment logic.

-----------------------------------
## 6. Flow F - Thanh toán phí logistics cho inpack

Nguồn code chính:
- `web/controller/Package.php::doPay()`
- `web/controller/Package.php::batchdopay()`
- `api/controller/Zalopay.php`
- `api/controller/Recharge.php`
- `common/enum/order/PayType.php`

### Nhánh thanh toán balance
1. lấy inpack
2. check `status = 2` và `is_pay = 2` theo logic hiện tại
3. tính amount = `free + pack_free`
4. check balance user
5. update `inpack`:
   - `real_payment`
   - `is_pay = 1`
   - `status = 3`
   - `pay_time`
6. update toàn bộ package con sang trạng thái đã thanh toán
7. trừ balance user
8. ghi balance log

### Nhánh third-party payment
Schema + controller cho thấy support:
- WeChat Pay
- ZaloPay
- có dấu hiệu LINE Pay config trong `yoshop_line_app`

### Business note
- logistics payment và e-commerce payment là hai flow khác nhau
- cùng dùng chung khái niệm wallet/log nhưng aggregate khác nhau

-----------------------------------
## 7. Flow G - Warehouse pick/pack/ship

Nguồn SQL chính:
- `yoshop_inpack.status`
- `yoshop_package.status`
- `yoshop_logistics`
- `yoshop_send_order`
- `yoshop_batch*`

### Flow business điển hình
1. inpack đã được thanh toán
2. kho bắt đầu pick package tương ứng
3. package xuống kệ / scan vào batch / đóng gói
4. tạo waybill của carrier
5. ghi `t_order_sn`, `t_name`, `t_number`
6. inpack chuyển sang shipped
7. logistics track tiếp tục cập nhật

### Khái niệm quan trọng
- package status và inpack status không giống nhau nhưng phải đồng bộ tương đối
- package là thành phần con của inpack
- carrier/waybill nằm ở tầng inpack/logistics hơn là package

-----------------------------------
## 8. Flow H - Tracking / receipt / complete

Nguồn code/bảng:
- `web/controller/Package.php::logicist()`
- `yoshop_logistics`
- `yoshop_logistics_track`
- `yoshop_inpack.receipt_time`

### Business flow
1. đơn đã có vận đơn carrier
2. hệ thống sync/ghi logistics
3. user xem trajectory / tracking
4. khi nhận hàng thành công:
   - cập nhật receipt status/time
5. đơn chuyển complete

-----------------------------------
## 9. Flow I - E-commerce checkout

Nguồn code chính:
- `api/controller/Order.php`
- `web/controller/Order.php`
- `service/order/Checkout.php`

### Buy now
1. client gửi goods_id / goods_num / sku
2. load goods list
3. build checkout data
4. GET trả preview
5. POST tạo order
6. build payment request

### Cart checkout
1. client gửi cart_ids
2. load cart goods
3. preview checkout
4. create order
5. clear cart
6. build payment request

### Tại sao flow này quan trọng nhưng không phải lõi?
Vì nó là domain commerce chuẩn, trong khi dự án nổi bật nhất ở logistics forwarding.

-----------------------------------
## 10. Flow J - Buyer order / mua hộ

Nguồn code chính:
- `web/model/BuyerOrder.php`

### Flow
1. user dán URL Taobao/Tmall/JD/1688
2. hệ thống parse platform
3. tạo buyer order
4. tính amount = `price * num + free`
5. trừ balance
6. ghi balance log

### Ý nghĩa
Đây là một flow “dịch vụ giá trị gia tăng” bên cạnh logistics.

-----------------------------------
## 11. Flow K - Background jobs

Nguồn code chính:
- `task/service/Order.php`
- `task/behavior/*`
- `docs/database/async_task_queue.sql`

### Ví dụ job
- auto close unpaid e-commerce order
- batch behavior
- user coupon / birthday / grade behavior
- sharing/sharp behavior

### Ý nghĩa
Repo có cả task layer đồng bộ và dấu vết cho queue bất đồng bộ (`yoshop_async_task_queue`).

-----------------------------------
## 12. Flow xương sống cần nhớ nhất

Nếu chỉ chọn 1 flow để hiểu dự án, hãy nhớ flow này:

1. user có nhiều `package`
2. package được kho nhận và xử lý inbound
3. user chọn package để tạo `inpack`
4. hệ thống tính phí theo `line`
5. user thanh toán
6. kho pick/pack/ship
7. tracking và hoàn tất

Tất cả phần còn lại của dự án xoay quanh hoặc hỗ trợ flow này.
