# 05. Database map

File này không thay thế ERD đầy đủ, nhưng cho bạn bản đồ tư duy để đọc `xinsuju.sql` nhanh và đúng trọng tâm.

## 1. Tổng quan

Từ schema, có khoảng 239 bảng `CREATE TABLE`.

Phân nhóm heuristic từ schema:
- auth_access: 9 bảng
- logistics_core: 60 bảng
- ecommerce: 77 bảng
- finance_marketing: 23 bảng
- saas_platform: 23 bảng
- monitoring_ops: 10 bảng
- integrations: 4 bảng
- other/supporting: 33 bảng

=> Kết luận: schema rất rộng, nhưng không phải bảng nào cũng là “business backbone”.

-----------------------------------
## 2. 20 bảng quan trọng nhất bạn nên hiểu trước

### Tenant / SaaS
1. `yoshop_wxapp`
2. `yoshop_setting`
3. `yoshop_store_shop`

### Auth / staff
4. `yoshop_admin_user`
5. `yoshop_store_user`
6. `yoshop_store_role`
7. `yoshop_store_access`
8. `yoshop_store_role_access`
9. `yoshop_store_user_role`
10. `yoshop_user`
11. `yoshop_user_address`

### Logistics
12. `yoshop_package`
13. `yoshop_package_item`
14. `yoshop_inpack`
15. `yoshop_line`
16. `yoshop_logistics`
17. `yoshop_logistics_track`
18. `yoshop_shelf`
19. `yoshop_shelf_unit`
20. `yoshop_shelf_unit_item`

Nếu hiểu 20 bảng trên, bạn đã hiểu được phần lớn dự án cốt lõi.

-----------------------------------
## 3. Tenant / SaaS tables

### `yoshop_wxapp`
Vai trò:
- tenant master record

Business fields quan trọng:
- `wxapp_id`
- `end_time`
- `is_recycle`
- `is_delete`
- các app credentials / branding / payment config liên quan

### `yoshop_setting`
Vai trò:
- key-value config theo tenant

Business meaning:
- cấu hình store
- user code mode
- payment
- notice/template
- logistics behavior

### `yoshop_store_shop`
Vai trò:
- warehouse / branch / store node của tenant

Business fields:
- `shop_id`, `shop_name`
- `country_id`
- `type` (domestic/overseas)
- `status`
- `wxapp_id`
- revenue/share fields

-----------------------------------
## 4. Identity / RBAC tables

### `yoshop_admin_user`
- super admin của platform

### `yoshop_store_user`
Các field quan trọng:
- `store_user_id`
- `user_name`, `password`
- `is_super`
- `line_id`, `country_id`, `shop_id`, `clerk_id`
- `wxapp_id`

Business meaning:
- vừa là account, vừa là data-scope descriptor

### `yoshop_store_role`
- role theo tenant

### `yoshop_store_access`
- permission URL catalog

### `yoshop_store_role_access`
- role -> access mapping

### `yoshop_store_user_role`
- store_user -> role mapping

### `yoshop_user`
User/customer table quan trọng nhất ở phía end-user.

Các field business đáng chú ý:
- `user_id`
- `user_code`
- `balance`
- `dealer_id` hoặc referral relation tương đương
- `service_id`
- `wxapp_id`

### `yoshop_user_address`
- địa chỉ nhận hàng / gửi hàng của user

-----------------------------------
## 5. Logistics core tables

## 5.1 `yoshop_package`
Ý nghĩa:
- inbound parcel / từng kiện hàng

Các field rất quan trọng:
- `id`
- `inpack_id`
- `order_sn`
- `member_id`
- `express_num`
- `status`
- `storage_id`
- `shop_id`
- `country_id`
- `line_id`
- `wxapp_id`
- `weight`, `length`, `width`, `height`, `volume`
- `source`
- `is_take`, `is_verify`, `is_scan`, `is_shelf`

Status package nên nhớ:
- 1 chờ nhập kho
- 2 đã nhập kho
- 3 đã lên kệ
- 4 chờ đóng gói
- 5 chờ thanh toán
- 6 đã thanh toán
- 7 vào batch / xuống kệ gom
- 8 đã đóng gói
- 9 đã gửi
- 10 đã nhận
- 11 hoàn tất

### `yoshop_package_item`
Ý nghĩa:
- item breakdown bên trong package
- class/category/customs declaration support

### `yoshop_package_image`
- ảnh package

## 5.2 `yoshop_inpack`
Ý nghĩa:
- đơn gom hàng / outbound logistics order

Field business quan trọng:
- `id`
- `order_sn`
- `pack_ids`
- `pack_services_id`
- `address_id`
- `status`
- `member_id`
- `storage_id`
- `shop_id`
- `line_id`
- `wxapp_id`
- `free`, `pack_free`, `other_free`, `insure_free`, `real_payment`
- `weight`, `cale_weight`, `volume`
- `t_name`, `t_number`, `t_order_sn`
- `is_pay`, `is_pay_type`
- `source`, `inpack_type`
- `pay_time`, `sendout_time`, `receipt_time`

Status inpack nên nhớ:
- 1 chờ kiểm / chờ xác nhận
- 2 chờ thanh toán
- 3 đã thanh toán / chờ phát
- 4 picking
- 5 đã đóng gói
- 6 đã gửi
- 7 đến nơi / ký nhận
- 8 hoàn tất
- 9 hủy
- 10 draft

## 5.3 `yoshop_line`
Ý nghĩa:
- rule pricing + rule constraint của logistics line

Field trọng tâm:
- `name`
- `line_category`
- `free_mode`
- `free_rule`
- `weight_min`, `max_weight`
- `volumeweight`, `volumeweight_type`, `bubble_weight`
- `countrys`, `categorys`, `shop_id`
- `status`, `line_position`
- `wxapp_id`

## 5.4 `yoshop_logistics` và `yoshop_logistics_track`
- lưu trạng thái vận chuyển / timeline / carrier tracking

## 5.5 Shelf/warehouse tables
- `yoshop_shelf`
- `yoshop_shelf_unit`
- `yoshop_shelf_unit_item`

Ý nghĩa:
- mô hình vị trí vật lý trong kho
- phục vụ inbound shelving / outbound picking

-----------------------------------
## 6. E-commerce tables

### `yoshop_goods`
- catalog sản phẩm

### `yoshop_goods_sku`
- biến thể SKU

### `yoshop_cart`
- giỏ hàng

### `yoshop_order`
- order e-commerce truyền thống

Field đáng nhớ:
- `order_id`, `order_no`
- `total_price`, `order_price`, `pay_price`
- `coupon_id`, `coupon_money`
- `pay_type`, `pay_status`
- `delivery_status`, `receipt_status`, `order_status`
- `order_source`, `order_source_id`
- `user_id`, `wxapp_id`

### `yoshop_order_goods`
- snapshot hàng hóa lúc chốt order

### `yoshop_order_address`
- address snapshot theo order

### `yoshop_order_refund*`
- refund flow cho commerce

-----------------------------------
## 7. Buyer / purchase-on-behalf tables

### `yoshop_buyer_order`
Ý nghĩa:
- order mua hộ / đại mua
- thường lưu url mua hàng, spec, price, qty, fee
- có liên hệ với ví user

### `yoshop_buyer_cart`
- cart cho buyer flow

-----------------------------------
## 8. Finance / wallet / marketing tables

### Recharge
- `yoshop_recharge_order`
- `yoshop_recharge_plan`
- `yoshop_recharge_order_plan`

### Dealer / commission
- `yoshop_dealer_apply`
- `yoshop_dealer_capital`
- `yoshop_dealer_order`
- `yoshop_dealer_referee`
- `yoshop_dealer_setting`
- `yoshop_dealer_withdraw`

### Referral
- `yoshop_referral_relation`
- `yoshop_referral_reward`
- `yoshop_referral_system_config`

### Payment / finance infra
- `yoshop_pay`
- `yoshop_finance_config`
- `yoshop_bank`
- `yoshop_currency`

-----------------------------------
## 9. Social commerce tables

### Bargain
- `yoshop_bargain_*`

### Sharing / group-buy
- `yoshop_sharing_*`

### Sharp / seckill
- `yoshop_sharp_*`

### Blindbox
- `yoshop_blindbox*`

Business message:
- schema cho thấy hệ thống có module tăng trưởng đầy đủ, không chỉ logistics.

-----------------------------------
## 10. Monitoring / operations tables

Các bảng đáng chú ý:
- `yoshop_async_task_queue`
- `yoshop_alert_config`
- `yoshop_alert_history`
- `yoshop_system_performance_log`
- `yoshop_task_execution_log`
- `yoshop_data_monitoring_log`
- `yoshop_ailog`

Ý nghĩa:
- hệ thống có dấu vết mở rộng về monitoring, queue, AI log, performance tracking

-----------------------------------
## 11. 6 quan hệ cần nhớ bằng đầu

1. `wxapp` 1 - n `store_user`
2. `wxapp` 1 - n `user`
3. `user` 1 - n `package`
4. `inpack` 1 - n `package` (thực tế qua `pack_ids` dạng denormalized list)
5. `line` 1 - n `inpack` / `package`
6. `store_user` n - n `access` qua `role`

Lưu ý quan trọng:
- quan hệ `inpack -> package` hiện được lưu qua `pack_ids` dạng text/csv, không phải junction table chuẩn
- đây là đặc điểm rất đáng nhớ khi debug hoặc viết query

-----------------------------------
## 12. Khi đọc SQL, nên ưu tiên cụm nào?

Ưu tiên 1:
- `yoshop_user`
- `yoshop_user_address`
- `yoshop_package`
- `yoshop_package_item`
- `yoshop_inpack`
- `yoshop_line`

Ưu tiên 2:
- `yoshop_store_user`
- `yoshop_store_role*`
- `yoshop_store_access`
- `yoshop_store_shop`

Ưu tiên 3:
- `yoshop_order*`
- `yoshop_goods*`
- `yoshop_buyer_order`

Ưu tiên 4:
- dealer/referral/sharing/bargain/sharp/blindbox

-----------------------------------
## 13. Kết luận ngắn

Database của dự án này là database của một platform lớn, không phải một app đơn năng.

Muốn hiểu đúng:
- đừng bắt đầu từ toàn bộ 239 bảng
- hãy bắt đầu từ 6 bảng xương sống:
  - `yoshop_wxapp`
  - `yoshop_user`
  - `yoshop_store_user`
  - `yoshop_package`
  - `yoshop_inpack`
  - `yoshop_line`
