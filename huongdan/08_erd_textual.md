# 08. ERD textual - mô tả quan hệ dữ liệu bằng text

File này không phải ERD vẽ hình, mà là ERD dạng chữ để bạn hiểu quan hệ nhanh khi chưa có diagram.

Ký hiệu:
- `1 -> n`: một - nhiều
- `n -> 1`: nhiều - một
- `[weak]`: quan hệ lỏng / không enforced mạnh trong code hoặc DB
- `[denormalized]`: quan hệ lưu bằng text/csv thay vì FK chuẩn

## 1. SaaS / tenant layer

### `yoshop_wxapp`
Là root tenant.

Quan hệ chính:
- `yoshop_wxapp 1 -> n yoshop_setting`
- `yoshop_wxapp 1 -> n yoshop_store_user`
- `yoshop_wxapp 1 -> n yoshop_store_role`
- `yoshop_wxapp 1 -> n yoshop_store_shop`
- `yoshop_wxapp 1 -> n yoshop_user`
- `yoshop_wxapp 1 -> n yoshop_package`
- `yoshop_wxapp 1 -> n yoshop_inpack`
- `yoshop_wxapp 1 -> n yoshop_line`
- `yoshop_wxapp 1 -> n yoshop_order`
- `yoshop_wxapp 1 -> n yoshop_goods`

Business interpretation:
- gần như mọi dữ liệu business quan trọng đều là “con” của tenant.

-----------------------------------
## 2. Identity & RBAC layer

### 2.1 Platform admin

`yoshop_admin_user`
- gần như đứng riêng
- không phụ thuộc `wxapp_id`
- dùng cho platform control plane

### 2.2 Store/backoffice user

`yoshop_store_user`
- PK: `store_user_id`
- FK business: `wxapp_id`

Quan hệ:
- `yoshop_store_user n -> 1 yoshop_wxapp`
- `yoshop_store_user n -> n yoshop_store_role` qua `yoshop_store_user_role`

Join table:
- `yoshop_store_user_role`
  - `store_user_id`
  - `role_id`
  - `wxapp_id`

Role model:
- `yoshop_store_role 1 -> n yoshop_store_user_role`
- `yoshop_store_role n -> n yoshop_store_access` qua `yoshop_store_role_access`

Access catalog:
- `yoshop_store_access`
  - bản chất là permission URL tree

Business interpretation:
- staff login -> có role -> role map tới URL permission
- nhưng còn thêm data scope ở code, không chỉ ở bảng

### 2.3 End-user

`yoshop_user`
- PK: `user_id`
- tenant owner: `wxapp_id`
- domain fields: `balance`, `user_code`, `service_id`, ...

Quan hệ:
- `yoshop_user n -> 1 yoshop_wxapp`
- `yoshop_user 1 -> n yoshop_user_address`
- `yoshop_user 1 -> n yoshop_package`
- `yoshop_user 1 -> n yoshop_inpack` qua `member_id`
- `yoshop_user 1 -> n yoshop_order`
- `yoshop_user 1 -> n yoshop_buyer_order`
- `yoshop_user 1 -> n recharge / balance / coupon / referral records`

### 2.4 Address

`yoshop_user_address`
- PK: `address_id`
- owner: `user_id`
- tenant: `wxapp_id`

Quan hệ:
- `yoshop_user_address n -> 1 yoshop_user`
- `yoshop_inpack n -> 1 yoshop_user_address` qua `address_id`
- `yoshop_package n -> 1 yoshop_user_address` qua `address_id` hoặc `jaddress_id`
- `yoshop_order_address` là snapshot riêng của commerce order, không phải FK reuse trực tiếp

-----------------------------------
## 3. Warehouse / logistics inbound layer

### 3.1 Warehouse

`yoshop_store_shop`
- PK: `shop_id`
- tenant owner: `wxapp_id`

Quan hệ:
- `yoshop_store_shop n -> 1 yoshop_wxapp`
- `yoshop_package n -> 1 yoshop_store_shop` qua `storage_id`
- `yoshop_inpack n -> 1 yoshop_store_shop` qua `storage_id`
- `yoshop_shelf n -> 1 yoshop_store_shop` [business-level]

Business meaning:
- kho là node vận hành vật lý
- package vào kho
- inpack thường đi ra từ kho

### 3.2 Package

`yoshop_package`
- PK: `id`
- owner user: `member_id`
- warehouse: `storage_id`
- tenant: `wxapp_id`
- line dự kiến: `line_id`
- inpack cha: `inpack_id` nhưng code thực tế còn dùng `pack_ids` bên inpack nhiều hơn

Quan hệ:
- `yoshop_package n -> 1 yoshop_user` qua `member_id`
- `yoshop_package n -> 1 yoshop_store_shop` qua `storage_id`
- `yoshop_package n -> 1 yoshop_line` qua `line_id`
- `yoshop_package 1 -> n yoshop_package_item`
- `yoshop_package 1 -> n yoshop_package_image`
- `yoshop_package n -> 1 yoshop_user_address` [optional]

### 3.3 Package item

`yoshop_package_item`
Quan hệ:
- `yoshop_package_item n -> 1 yoshop_package`

Business meaning:
- khai báo item trong parcel
- hỗ trợ customs/category/classification

### 3.4 Shelf

Quan hệ vật lý:
- `yoshop_shelf 1 -> n yoshop_shelf_unit`
- `yoshop_shelf_unit 1 -> n yoshop_shelf_unit_item`
- `yoshop_shelf_unit_item [weak] -> package`

Business meaning:
- mô hình vị trí vật lý của package trong kho

-----------------------------------
## 4. Consolidation / outbound layer

### 4.1 Inpack

`yoshop_inpack`
- PK: `id`
- owner: `member_id`
- warehouse: `storage_id`
- tenant: `wxapp_id`
- route: `line_id`
- address nhận: `address_id`
- child packages: `pack_ids`

Quan hệ chuẩn-ish:
- `yoshop_inpack n -> 1 yoshop_user`
- `yoshop_inpack n -> 1 yoshop_store_shop`
- `yoshop_inpack n -> 1 yoshop_user_address`
- `yoshop_inpack n -> 1 yoshop_line`

Quan hệ không chuẩn:
- `yoshop_inpack 1 -> n yoshop_package` là quan hệ `[denormalized]`
- thực tế lưu trong `pack_ids` dạng csv/text
- code thường `explode(',', $pack_ids)` để xử lý

Business impact:
- đây là aggregate logistics outbound chính
- mọi payment / tracking / shipping event cấp đơn đều xoay quanh inpack

### 4.2 Line

`yoshop_line`
Quan hệ:
- `yoshop_line n -> 1 yoshop_wxapp`
- `yoshop_package n -> 1 yoshop_line` [optional / dự kiến]
- `yoshop_inpack n -> 1 yoshop_line`

Supporting tables:
- `yoshop_line_category`
- `yoshop_line_app`
- `yoshop_line_config`
- `yoshop_line_payment`
- `yoshop_line_price_tier`
- `yoshop_line_services`
- `yoshop_line_template`
- `yoshop_line_translation`
- `yoshop_line_user`

Business meaning:
- line là nơi chứa policy pricing / restriction / supported country / shipping mode

### 4.3 Logistics tracking

`yoshop_logistics`
`yoshop_logistics_track`

Quan hệ business:
- `inpack 1 -> n logistics_track` [thường qua order_sn / t_order_sn hơn là FK sạch]
- `package` đôi khi được link qua `express_num`

Business meaning:
- tracking của carrier là lớp event timeline phía sau inpack/package

-----------------------------------
## 5. Commerce layer

### 5.1 Goods catalog

Quan hệ:
- `yoshop_category 1 -> n yoshop_goods`
- `yoshop_goods 1 -> n yoshop_goods_sku`
- `yoshop_goods 1 -> n yoshop_goods_image`
- `yoshop_goods n -> n yoshop_spec` qua `yoshop_goods_spec_rel`

### 5.2 Cart

`yoshop_cart`
Quan hệ:
- `yoshop_cart n -> 1 yoshop_user`
- `yoshop_cart n -> 1 yoshop_goods`
- `yoshop_cart n -> 1 yoshop_goods_sku`

### 5.3 Commerce order

`yoshop_order`
Quan hệ:
- `yoshop_order n -> 1 yoshop_user`
- `yoshop_order n -> 1 yoshop_wxapp`
- `yoshop_order 1 -> n yoshop_order_goods`
- `yoshop_order 1 -> 1 yoshop_order_address` [snapshot]
- `yoshop_order 1 -> 0..1 yoshop_order_extract`
- `yoshop_order 1 -> n yoshop_order_refund`

`yoshop_order_goods`
- snapshot line items của order

Business meaning:
- đây là aggregate commerce thuần, khác với logistics inpack

-----------------------------------
## 6. Buyer / purchase-on-behalf layer

### `yoshop_buyer_order`
Quan hệ:
- `yoshop_buyer_order n -> 1 yoshop_user`
- `yoshop_buyer_order n -> 1 yoshop_store_shop`
- `yoshop_buyer_order n -> 1 yoshop_user_address`
- `yoshop_buyer_order n -> 1 yoshop_wxapp`

Business meaning:
- user yêu cầu mua hộ từ external marketplace
- hệ thống ghi nhận và trừ balance

-----------------------------------
## 7. Finance & recharge layer

### Recharge

`yoshop_recharge_plan 1 -> n yoshop_recharge_order_plan`
`yoshop_recharge_order 1 -> n yoshop_recharge_order_plan`
`yoshop_user 1 -> n yoshop_recharge_order`

### Dealer / referral

`yoshop_dealer_user`
- thường là extension/distributor profile của user

Các quan hệ business:
- `user 1 -> 0..1 dealer_user`
- `dealer_user 1 -> n dealer_order`
- `dealer_user 1 -> n dealer_withdraw`
- `dealer_user 1 -> n dealer_referee`

### Balance / logs

Không phải file nào cũng đọc ở SQL mẫu ở đây, nhưng business relation là:
- `user 1 -> n balance logs`
- recharge/payment/order/inpack/buyer_order đều có thể phát sinh log

-----------------------------------
## 8. Social commerce layer

### Sharing / group-buy
Quan hệ khái quát:
- `sharing_goods`
- `sharing_order`
- `sharing_order_goods`
- `sharing_active`
- `sharing_active_users`

### Bargain
- `bargain_active`
- `bargain_task`
- `bargain_task_help`

### Sharp / flash sale
- `sharp_active`
- `sharp_goods`
- `sharp_active_goods`

### Blindbox
- `blindbox`
- `blindbox_wall`
- `blindbox_wall_image`

Business meaning:
- đều là module growth/engagement cho tenant
- không phải xương sống logistics nhưng có thể đẻ ra order/package flow phụ

-----------------------------------
## 9. Những quan hệ “nguy hiểm” hoặc đáng chú ý

### 9.1 `inpack.pack_ids`
- không phải junction table chuẩn
- mọi xử lý phải `explode(',')`
- dễ gây bug khi:
  - query filter
  - append/remove package
  - thống kê
  - migration schema

### 9.2 Nhiều quan hệ dựa trên business key hơn FK
Ví dụ:
- `express_num`
- `order_sn`
- `t_order_sn`

Điều này làm:
- trace dữ liệu thực tế linh hoạt
- nhưng integrity khó đảm bảo hơn

### 9.3 Một số bảng snapshot thay vì normalize
Ví dụ:
- `order_address`
- `order_goods`

Đây là thiết kế phổ biến để giữ nguyên dữ liệu tại thời điểm checkout.

-----------------------------------
## 10. ERD text tối giản cho nghiệp vụ xương sống

Tenant
- `wxapp`
  -> users
  -> store_users
  -> warehouses
  -> lines
  -> packages
  -> inpacks

User-side logistics
- `user`
  -> `user_address`
  -> `package`
  -> `inpack`

Warehouse-side logistics
- `store_shop`
  -> `shelf`
  -> `shelf_unit`
  -> `shelf_unit_item`
  -> `package`
  -> `inpack`

Outbound aggregation
- `line`
  -> `inpack`
- `inpack`
  -> `[denormalized] packages`
  -> `logistics_track`

-----------------------------------
## 11. Kết luận ngắn

Nếu bạn cần vẽ ERD thật sau này, hãy vẽ theo 4 cluster trước:
1. tenant & RBAC
2. user & address
3. package & warehouse
4. inpack & line & logistics

Vẽ xong 4 cluster này là bạn đã có sơ đồ quan trọng nhất của dự án.