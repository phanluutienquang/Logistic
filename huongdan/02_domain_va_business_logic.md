# 02. Domain và business logic

## 1. Domain lõi của dự án

Từ SQL và code, có thể chia domain thành 7 cụm:

1. Tenant / SaaS domain
2. Identity / user / staff domain
3. Logistics inbound / warehouse domain
4. Consolidation & shipping domain
5. E-commerce order domain
6. Finance / wallet / recharge / commission domain
7. Growth / marketing / social commerce domain

Phần nặng nhất về logic thực tế là cụm 3 + 4.

-----------------------------------
## 2. Tenant / SaaS domain

### Thực thể chính
- `yoshop_wxapp`
- `yoshop_setting`
- `yoshop_wxapp_page`
- `yoshop_h5_setting`
- `yoshop_line_app`
- `yoshop_store_shop`

### Ý nghĩa business
Mỗi tenant là một “đơn vị kinh doanh logistics/e-commerce” độc lập:
- có branding riêng
- có cấu hình thanh toán riêng
- có route/line riêng
- có user/store staff riêng
- có hạn sử dụng tenant (`end_time`)

### Logic thể hiện trong code
- `admin/model/Wxapp.php` khi tạo tenant sẽ:
  - tạo record tenant
  - insert default setting
  - tạo super store user
  - tạo help/page mặc định
- `api/controller/Controller.php` và `web/controller/Controller.php` đều validate tenant tồn tại / chưa xóa
- `api/controller/Controller.php` còn check `end_time` hết hạn

=> Business result: tenant mới được provision gần như tự động.

-----------------------------------
## 3. Identity / user / staff domain

### 3.1 End-user
Bảng chính:
- `yoshop_user`
- `yoshop_user_address`
- `yoshop_user_coupon`
- `yoshop_user_binding`
- `yoshop_user_setting`

Vai trò:
- khách gửi hàng
- khách mua hàng
- người dùng ví điện tử nội bộ
- người tham gia giới thiệu / group-buy / bargain

Các trường business quan trọng:
- `user_code`: mã người dùng dùng như suffix nhận hàng/kho nhận diện
- `balance`: ví tiền
- `pay_money`, `expend_money`: tích lũy chi tiêu
- `service_id`: có thể gắn với clerk / CSKH phụ trách
- `wxapp_id`: tenant ownership

### 3.2 Store/backoffice user
Bảng chính:
- `yoshop_store_user`
- `yoshop_store_role`
- `yoshop_store_access`
- `yoshop_store_role_access`
- `yoshop_store_user_role`
- `yoshop_store_user_binding`

Vai trò nghiệp vụ:
- quản trị tenant
- quản lý kho
- nhân viên nhập kho
- nhân viên xuất kho
- CSKH
- tài chính
- vận hành

Điểm đặc biệt:
- staff không chỉ thuộc tenant, mà còn có thể bị giới hạn theo:
  - `shop_id` (kho nào)
  - `line_id` (tuyến nào)
  - `country_id` (quốc gia nào)
  - `clerk_id` (nhóm nhân sự/service nào)

=> Đây là RBAC + data-scope, không chỉ là RBAC thuần.

### 3.3 Super admin
Bảng:
- `yoshop_admin_user`

Chức năng:
- quản tenant
- đăng nhập `admin`
- impersonate / enter vào `store` của tenant

-----------------------------------
## 4. Logistics inbound / warehouse domain

Đây là xương sống thật sự của hệ thống.

### Thực thể chính
- `yoshop_package`: từng kiện hàng / parcel
- `yoshop_package_item`: mô tả item bên trong package
- `yoshop_package_image`
- `yoshop_shelf`
- `yoshop_shelf_unit`
- `yoshop_shelf_unit_item`
- `yoshop_store_shop`: kho
- `yoshop_express`
- `yoshop_logistics`

### Package là gì?
Một `package` là 1 kiện inbound trước khi gom quốc tế.
Nó chứa:
- mã đơn nội bộ
- mã vận đơn nội địa `express_num`
- chủ sở hữu `member_id`
- kho nhận `storage_id`
- route dự kiến `line_id`
- tình trạng nhận/đóng gói/thanh toán/xuất hàng
- thông tin kích thước/cân nặng
- source: package đến từ đâu

### Các source package quan trọng trong schema
`yoshop_package.source`:
- 1: miniapp/user tự khai báo
- 2: platform/backoffice nhập
- 3: đồng bộ đại mua
- 4: import hàng loạt
- 5: PC pre-alert
- 6: pre-alert từ sharing
- 7: pickup appointment
- 8: warehouse staff nhập
- 9: API nhập
- 10: từ blindbox plan

=> Điều này cho thấy package là “nút hội tụ” của nhiều luồng business.

### Status package quan trọng
Theo schema `yoshop_package.status`:
- 1: chờ nhập kho
- 2: đã nhập kho
- 3: đã lên kệ / chuẩn bị submit pack
- 4: chờ đóng gói
- 5: chờ thanh toán
- 6: đã thanh toán
- 7: vào batch / scan vào batch
- 8: đã đóng gói
- 9: đã phát hàng
- 10: đã nhận
- 11: hoàn tất

Theo `web/model/Package.php`, hệ thống hiển thị gần tương tự:
- 1 未入库
- 2 已入库
- 3 已拣货上架
- 4 待打包
- 5 待支付
- 6 已支付
- 7 已分拣下架
- 8 已打包
- 9 已发货
- 10 已收货
- 11 已完成

=> Khi debug, luôn so status trong code với status comment trong SQL vì ngôn ngữ diễn đạt hơi khác nhau nhưng bản chất giống nhau.

### Business meaning
`package` đại diện cho lifecycle kho nội địa:
1. user khai báo hoặc hệ thống ingest package
2. package được nhận diện chủ hàng
3. package nhập kho
4. package lên kệ / chờ gom
5. package được chọn vào đơn gom hàng
6. package được thanh toán phí logistics
7. package được đóng gói / xuất đi

-----------------------------------
## 5. Consolidation & shipping domain

### Thực thể trung tâm
- `yoshop_inpack`
- `yoshop_line`
- `yoshop_line_category`
- `yoshop_package_services`
- `yoshop_logistics`
- `yoshop_logistics_track`
- `yoshop_send_order`
- `yoshop_send_pre_order`
- `yoshop_batch*`

### Inpack là gì?
`inpack` là đơn gom hàng quốc tế được tạo từ 1 hoặc nhiều `package`.

Đây là aggregate root của logistics outbound.

Bảng `yoshop_inpack` chứa:
- `pack_ids`: danh sách package bên trong
- `line_id`: tuyến vận chuyển
- `address_id`: địa chỉ nhận quốc tế
- `free`, `pack_free`, `other_free`, `insure_free`: cấu phần phí
- `weight`, `cale_weight`, `volume`: dữ liệu tính cước
- `t_name`, `t_number`, `t_order_sn`: carrier/waybill bên thứ ba
- `is_pay`, `pay_type`, `source`, `inpack_type`

### Status inpack
Trong SQL comment:
- 1 待查验
- 2 待支付
- 3 待发货
- 4 拣货中
- 5 已打包
- 6 已发货
- 7 已到货
- 8 已完成
- 9 已取消
- 10 草稿

Trong `web/model/Inpack.php` hiển thị map hơi khác về text nhưng gần nghĩa:
- 1 待查验
- 2 待支付
- 3 已支付
- 4 已拣货
- 5 已打包
- 6 已发货
- 7 已收货
- 8 已完成
- -1 问题件

=> Kết luận thực dụng:
- `inpack` là thực thể logistics cấp đơn
- `package` là thực thể logistics cấp kiện
- status chuyển dịch của hai bảng có liên hệ chặt với nhau

### Line là gì?
`yoshop_line` là engine rule của cước logistics.

Các thuộc tính quan trọng:
- `free_mode`: mode tính phí
- `free_rule`: rule pricing JSON/text
- `volumeweight`, `volumeweight_type`, `bubble_weight`: quy tắc quy đổi thể tích
- `weight_min`, `max_weight`: giới hạn cân nặng
- `countrys`, `categorys`: quốc gia và category hỗ trợ
- `line_position`: áp dụng cho拼邮/直邮/拼团/通用

Business meaning:
- line là sản phẩm logistics của tenant
- mọi định giá outbound đều xoay quanh line

-----------------------------------
## 6. E-commerce order domain

### Thực thể chính
- `yoshop_goods`
- `yoshop_goods_sku`
- `yoshop_cart`
- `yoshop_order`
- `yoshop_order_goods`
- `yoshop_order_address`
- `yoshop_coupon`
- `yoshop_comment`

### Flow riêng
Đây là flow shop chuẩn:
- add to cart / buy now
- checkout
- create order
- payment
- delivery/receipt
- comment/refund

### Điểm cần nhớ
Phần e-commerce này cùng repo nhưng khác hẳn với logistics package/inpack.
Không được lẫn:
- `yoshop_order` là order mua goods
- `yoshop_inpack` là order logistics/gom hàng

-----------------------------------
## 7. Buyer / purchase-on-behalf domain

Bảng và model liên quan:
- `yoshop_buyer_order`
- `web/model/BuyerOrder.php`

Business logic đọc từ model:
- user submit URL mua hàng từ Taobao/Tmall/JD/1688
- hệ thống ghi nhận order mua hộ
- tính amount = price * num + fee
- trừ trực tiếp balance user
- ghi balance log

=> Đây là domain “đại mua / mua hộ”, nằm giữa e-commerce và logistics.

-----------------------------------
## 8. Finance / wallet / payment / commission

### Bảng chính
- `yoshop_recharge_order`, `yoshop_recharge_plan`
- `yoshop_pay`
- `yoshop_user.balance` + balance logs
- `yoshop_dealer_*`
- `yoshop_referral_*`
- `yoshop_finance_config`

### Logic chính
- user có balance ví
- balance dùng để:
  - thanh toán logistics `inpack`
  - thanh toán buyer order
  - thanh toán e-commerce order (một số flow)
- recharge có thể đi qua third-party payment
- dealer/referral/commission tồn tại song song

-----------------------------------
## 9. Social commerce / marketing

Bảng lớn cho thấy hệ thống có nhiều growth module:
- `yoshop_bargain_*`
- `yoshop_sharing_*`
- `yoshop_sharp_*`
- `yoshop_blindbox*`
- `yoshop_coupon*`
- `yoshop_banner*`

Business meaning:
- tenant không chỉ bán logistics, mà còn có thể vận hành miniapp commerce/social marketing
- đây là lý do repo nhìn “rất rộng” và có cảm giác pha trộn nhiều domain

-----------------------------------
## 10. Câu chốt để nhớ domain

Nếu phải nén vào 3 lớp:

Lớp 1: Hàng hóa đầu vào kho
- `package`

Lớp 2: Gom và gửi quốc tế
- `inpack` + `line` + `logistics`

Lớp 3: Hệ sinh thái xung quanh
- tenant
- auth/rbac
- e-commerce
- finance/wallet
- social marketing

Đọc dự án theo 3 lớp này sẽ bớt rối rất nhiều.
