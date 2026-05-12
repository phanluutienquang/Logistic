# 17. Kịch bản debug thực tế

File này tổng hợp các tình huống debug rất dễ gặp trong dự án.

Mỗi tình huống gồm:
- triệu chứng
- giả thuyết nguyên nhân
- file cần mở trước
- query SQL nên chạy

## 1. User không thấy package của mình

### Triệu chứng
- user báo đã khai báo hoặc đã nhận hàng vào kho nhưng app/web không thấy package

### Giả thuyết
1. package sai `wxapp_id`
2. package `is_delete = 1`
3. package không đúng `member_id`
4. query/filter status đang loại nó ra
5. package chưa được claim (`is_take`)

### Mở file trước
- `web/model/Package.php`
- `api/model/Package.php`
- `common/model/BaseModel.php`

### Query
```sql
SELECT id, express_num, member_id, status, is_take, is_delete, wxapp_id
FROM yoshop_package
WHERE express_num = 'TRACKING_NO';
```

-----------------------------------
## 2. Store staff thấy thiếu package so với admin

### Triệu chứng
- admin thấy đủ package
- staff kho hoặc CSKH chỉ thấy một phần

### Giả thuyết
1. bị scope theo `shop_id`
2. bị scope theo `clerk_id`
3. role/permission chặn route hoặc data
4. query dùng global scope khác nhau

### Mở file trước
- `common/model/BaseModel.php`
- `store/service/Auth.php`
- `store/model/store/User.php`

### Query
```sql
SELECT store_user_id, user_name, shop_id, line_id, country_id, clerk_id, is_super, wxapp_id
FROM yoshop_store_user
WHERE store_user_id = 10001;
```

-----------------------------------
## 3. Admin vào được tenant, store user thì không login được

### Triệu chứng
- super admin enter được store
- nhưng user tenant login trực tiếp fail

### Giả thuyết
1. tenant hết hạn `end_time`
2. `wxapp` recycle/delete
3. store user `is_delete = 1`
4. password hash không đúng

### Mở file trước
- `store/model/store/User.php`
- `admin/model/store/User.php`
- `common/model/Wxapp.php`

### Query
```sql
SELECT wxapp_id, end_time, is_recycle, is_delete
FROM yoshop_wxapp
WHERE wxapp_id = 10001;
```

-----------------------------------
## 4. Inpack đã tạo nhưng package chưa chuyển đúng trạng thái

### Triệu chứng
- user submit gom hàng thành công
- nhưng package con chưa sang trạng thái chờ thanh toán / chưa bind đúng

### Giả thuyết
1. update batch package fail giữa chừng
2. `pack_ids` format lỗi
3. package list không đầy đủ
4. transaction boundary thiếu chặt

### Mở file trước
- `web/controller/Package.php::inpack()`
- `api/controller/Package.php` flow tương đương
- `web/model/Inpack.php`

### Query
```sql
SELECT id, order_sn, pack_ids, status, is_pay
FROM yoshop_inpack
WHERE id = 99999;
```

Sau đó:
```sql
SELECT id, express_num, status, line_id, address_id
FROM yoshop_package
WHERE id IN (...);
```

-----------------------------------
## 5. Inpack đã thanh toán nhưng package con chưa sync pay status

### Triệu chứng
- inpack `is_pay = 1`
- nhưng package con vẫn là `status = 5` hoặc thấp hơn

### Giả thuyết
1. payment update package fail
2. rollback không chạy đúng
3. `pack_ids` bị thiếu/malformed
4. callback/provider flow chỉ update inpack, không update package

### Mở file trước
- `web/controller/Package.php::doPay()`
- `web/controller/Package.php::batchdopay()`
- `api/controller/Package.php::doPay()`
- `api/controller/Zalopay.php`

### Query
```sql
SELECT id, order_sn, status, is_pay, free, pack_free, other_free, pack_ids
FROM yoshop_inpack
WHERE id = 99999;
```

-----------------------------------
## 6. ZaloPay đã thanh toán nhưng hệ thống không cập nhật

### Triệu chứng
- người dùng thanh toán thành công ở ZaloPay
- hệ thống vẫn hiện chưa thanh toán

### Giả thuyết
1. webhook không tới server
2. signature verify fail
3. parse `app_trans_id` fail
4. callback die giữa chừng
5. không tìm thấy orderType/orderNo

### Mở file trước
- `api/controller/Zalopay.php`
- `api/controller/Recharge.php`
- log/error log runtime

### Kiểm tra thêm
- runtime logs trong `source/runtime/log/*`
- payment config trong tenant setting

-----------------------------------
## 7. LINE login thành công ở frontend nhưng backend không tạo user đúng

### Triệu chứng
- frontend nói lấy được LINE identity
- backend trả lỗi hoặc tạo user kỳ lạ

### Giả thuyết
1. `line_user_id` rỗng
2. không tìm thấy / không tạo được binding `yoshop_line_user`
3. `open_id` prefix logic gây mismatch
4. `wxapp_id` không đúng tenant

### Mở file trước
- `api/controller/Passport.php`
- `api/service/passport/Login.php::loginMpLine()`
- `api/model/line/User.php`

### Query
```sql
SELECT *
FROM yoshop_line_user
WHERE wxapp_id = 10001
ORDER BY id DESC;
```

-----------------------------------
## 8. Zalo login tạo trùng user hoặc không tìm thấy user cũ

### Triệu chứng
- cùng một user Zalo đăng nhập nhiều lần nhưng sinh nhiều record
- hoặc login không map về user cũ

### Giả thuyết
1. `open_id` không nhất quán
2. tenant khác nhau nhưng frontend dùng sai `wxapp_id`
3. user cũ bị soft delete

### Mở file trước
- `api/service/passport/Login.php::loginMpZalo()`
- `common/model/User.php`

### Query
```sql
SELECT user_id, open_id, nickName, wxapp_id, is_delete
FROM yoshop_user
WHERE open_id = 'ZALO_USER_ID';
```

-----------------------------------
## 9. User balance bị trừ nhưng order/inpack chưa đổi trạng thái

### Triệu chứng
- số dư giảm
- nhưng UI vẫn báo chưa thanh toán hoặc order chưa hoàn thành

### Giả thuyết
1. transaction commit/rollback có vấn đề
2. log ghi xong nhưng aggregate update fail
3. callback bị chạy lại nửa chừng

### Mở file trước
- `web/controller/Package.php::doPay()`
- `web/model/BuyerOrder.php::toPay()`
- recharge/order pay success service tương ứng

### Query
- kiểm tra aggregate
- kiểm tra balance log
- kiểm tra user current balance

-----------------------------------
## 10. Tính giá line sai

### Triệu chứng
- giá logistics không đúng kỳ vọng
- cùng package nhưng giá khác tenant khác hoặc khác flow

### Giả thuyết
1. line config `free_mode` / `free_rule` sai
2. `volumeweight` / `bubble_weight` / `weight_min` sai
3. package dimensions/weight sai
4. route line không đúng tenant

### Mở file trước
- `web/model/Line.php`
- `store/controller/setting/Line.php`
- nơi gọi `computeLinePrice`
- SQL `yoshop_line`

### Query
```sql
SELECT id, name, free_mode, free_rule, weight_min, max_weight, volumeweight, volumeweight_type, bubble_weight
FROM yoshop_line
WHERE id = 148;
```

-----------------------------------
## 11. Package bị “mồ côi” - chưa gắn user

### Triệu chứng
- package vào kho nhưng không có owner hoặc nằm ở nouser/unclaimed

### Giả thuyết
1. claim flow chưa chạy
2. `user_code`/tracking parsing không match
3. OCR/auto report sai

### Mở file trước
- `store/controller/package/Index.php`
- `api/controller/ApiPost.php`
- package claim/unclaimed related actions

### Query
```sql
SELECT id, express_num, member_id, is_take, status, wxapp_id
FROM yoshop_package
WHERE wxapp_id = 10001
  AND (member_id = 0 OR member_id IS NULL OR is_take = 1)
ORDER BY id DESC;
```

-----------------------------------
## 12. Package/inpack count trên dashboard lệch với số liệu thô

### Triệu chứng
- dashboard count không bằng query SQL đơn giản

### Giả thuyết
1. dashboard dùng status bucket riêng
2. soft delete bị bỏ qua ở một bên
3. query scope theo staff/warehouse
4. `querycount()` đang gom nhiều status thành một nhóm

### Mở file trước
- `api/model/Package.php::querycount()`
- `statistics/Data.php`
- `common/model/BaseModel.php`

-----------------------------------
## 13. Staff có menu nhưng click vào bị 403

### Triệu chứng
- nhìn thấy menu item
- nhưng vào route bị báo không có quyền

### Giả thuyết
1. menu render và privilege check không cùng logic
2. role không có access URL đó
3. routeUri khác URL trong access table

### Mở file trước
- `store/controller/Controller.php`
- `store/service/Auth.php`
- bảng `yoshop_store_access`

-----------------------------------
## 14. Web và API cho cùng use case nhưng behavior khác nhau

### Triệu chứng
- web submit được, api submit fail
- hoặc số liệu/response khác nhau

### Giả thuyết
1. logic drift giữa `web/controller/Package.php` và `api/controller/Package.php`
2. validation khác nhau
3. param name khác nhau
4. one side đã update feature, side kia chưa

### Mở file trước
- `web/controller/Package.php`
- `api/controller/Package.php`
- compare cùng use case action

-----------------------------------
## 15. Tracking không ra dữ liệu

### Triệu chứng
- user mở trajectory/logistics nhưng trống

### Giả thuyết
1. carrier order_sn/t_order_sn chưa set
2. logistics track chưa sync
3. provider integration fail
4. transfer mode khác nhau (`17track` vs self logistics)

### Mở file trước
- `web/controller/Track.php`
- `web/controller/Home.php`
- `api/controller/Package.php::logicist*`
- provider library trong `common/library/Ditch/*`

-----------------------------------
## 16. Tenant A hoạt động bình thường, tenant B cùng flow lại lỗi

### Triệu chứng
- cùng use case nhưng chỉ lỗi trên một tenant

### Giả thuyết
1. setting tenant khác nhau
2. line khác nhau
3. payment config khác nhau
4. tenant hết hạn/recycle/lock

### Mở file trước
- `common/model/Wxapp.php`
- `common/model/Setting.php`
- `store/controller/Setting.php`
- `store/controller/setting/Line.php`

-----------------------------------
## 17. OCR / auto-report package nhập sai dữ liệu

### Triệu chứng
- tracking/user/address parse sai
- package report tự động gắn nhầm user hoặc nhầm shelf

### Giả thuyết
1. OCR text quality kém
2. parsing rule không ổn định
3. auto assignment heuristic không tốt

### Mở file trước
- `api/controller/ApiPost.php`
- AI/OCR libs trong `common/library/AITool/*`
- `yoshop_ailog`

-----------------------------------
## 18. Batch shipment lệch package/inpack

### Triệu chứng
- batch đã xử lý nhưng package hoặc inpack không đổi như kỳ vọng

### Giả thuyết
1. batch update không đồng bộ hết aggregate
2. append/remove package ở inpack sai
3. send order / pre-order flow lệch

### Mở file trước
- `store/controller/Batch.php`
- `store/model/Inpack.php`
- `store/controller/SendOrder.php`

-----------------------------------
## 19. Có lỗi random 500 ở production nhưng local không tái hiện

### Giả thuyết
1. debug code `dump/die` còn sót ở path hiếm
2. data scope khác do account khác
3. tenant config khác local
4. webhook/provider data shape khác local

### Nên làm gì trước
- grep `dump(`, `die;`
- đối chiếu account/tenant thật
- lấy exact payload input
- check runtime logs

-----------------------------------
## 20. Khi bí không biết debug từ đâu

Đi theo thứ tự này:
1. xác định aggregate chính là gì
   - package?
   - inpack?
   - commerce order?
   - buyer order?
2. xác định tenant `wxapp_id`
3. xác định actor
   - end-user?
   - store user?
   - admin?
   - webhook/provider?
4. xác định status hiện tại
5. xác định expected transition
6. mở file controller entrypoint
7. mở model/service aggregate
8. đối chiếu SQL thật

-----------------------------------
## Kết luận

Trong dự án này, hầu hết bug production thật đều quy về 1 trong 5 nhóm:
- tenant scope
- auth/provider identity
- package/inpack relation
- status transition
- drift giữa web và api

Nếu bạn phân loại bug đúng vào 1 trong 5 nhóm này, việc debug sẽ nhanh hơn rất nhiều.