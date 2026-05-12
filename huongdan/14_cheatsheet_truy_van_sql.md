# 14. Cheatsheet truy vấn SQL để debug dự án

Mục tiêu file này:
- cho bạn các query mẫu để debug nhanh
- giúp QA/dev/BA kiểm tra data trực tiếp từ DB
- tập trung vào các thực thể xương sống

Lưu ý:
- DB prefix mặc định là `yoshop_`
- đa số query phải nhớ filter `wxapp_id`
- nhiều bảng dùng soft delete hoặc status
- `pack_ids` là csv/text, không phải junction table chuẩn

-----------------------------------
## 1. Xác định tenant

### 1.1 Danh sách tenant
```sql
SELECT wxapp_id, end_time, is_recycle, is_delete
FROM yoshop_wxapp
ORDER BY wxapp_id DESC;
```

### 1.2 Kiểm tra tenant còn hạn không
```sql
SELECT wxapp_id, end_time,
       FROM_UNIXTIME(end_time) AS end_time_readable,
       is_recycle, is_delete
FROM yoshop_wxapp
WHERE wxapp_id = 10001;
```

### 1.3 Xem setting của tenant
```sql
SELECT `key`, describe, values, wxapp_id
FROM yoshop_setting
WHERE wxapp_id = 10001
ORDER BY setting_id DESC;
```

-----------------------------------
## 2. Debug store user / RBAC

### 2.1 Danh sách store user của tenant
```sql
SELECT store_user_id, user_name, real_name, is_super, shop_id, line_id, country_id, clerk_id, wxapp_id, is_delete
FROM yoshop_store_user
WHERE wxapp_id = 10001
ORDER BY store_user_id DESC;
```

### 2.2 Role của một store user
```sql
SELECT sur.store_user_id, sur.role_id, sr.role_name, sur.wxapp_id
FROM yoshop_store_user_role sur
JOIN yoshop_store_role sr ON sr.role_id = sur.role_id
WHERE sur.store_user_id = 10001;
```

### 2.3 Access URLs của role
```sql
SELECT sra.role_id, sa.access_id, sa.name, sa.url
FROM yoshop_store_role_access sra
JOIN yoshop_store_access sa ON sa.access_id = sra.access_id
WHERE sra.role_id = 1
ORDER BY sa.access_id;
```

### 2.4 Full permission map của một store user
```sql
SELECT su.store_user_id, su.user_name, sr.role_name, sa.url
FROM yoshop_store_user su
JOIN yoshop_store_user_role sur ON sur.store_user_id = su.store_user_id
JOIN yoshop_store_role sr ON sr.role_id = sur.role_id
JOIN yoshop_store_role_access sra ON sra.role_id = sr.role_id
JOIN yoshop_store_access sa ON sa.access_id = sra.access_id
WHERE su.store_user_id = 10001
ORDER BY sa.url;
```

-----------------------------------
## 3. Debug end-user

### 3.1 Tìm user theo mobile/email/user_code
```sql
SELECT user_id, nickName, mobile, email, user_code, balance, wxapp_id, is_delete
FROM yoshop_user
WHERE wxapp_id = 10001
  AND (mobile = '0900000000' OR email = 'test@example.com' OR user_code = 'AA6683');
```

### 3.2 Xem address của user
```sql
SELECT address_id, user_id, name, phone, country, province_id, city_id, region_id, detail, wxapp_id
FROM yoshop_user_address
WHERE user_id = 12345
ORDER BY address_id DESC;
```

-----------------------------------
## 4. Debug package

### 4.1 Danh sách package theo tenant
```sql
SELECT id, order_sn, express_num, member_id, status, storage_id, line_id, wxapp_id, created_time, updated_time
FROM yoshop_package
WHERE wxapp_id = 10001
  AND is_delete = 0
ORDER BY id DESC
LIMIT 100;
```

### 4.2 Tìm package theo express_num
```sql
SELECT id, order_sn, express_num, member_id, status, storage_id, inpack_id, wxapp_id
FROM yoshop_package
WHERE express_num = 'YT7468318021032';
```

### 4.3 Package của một user
```sql
SELECT id, order_sn, express_num, status, is_take, is_verify, is_scan, is_shelf, storage_id, line_id, created_time
FROM yoshop_package
WHERE wxapp_id = 10001
  AND member_id = 12345
  AND is_delete = 0
ORDER BY id DESC;
```

### 4.4 Package theo status bucket
```sql
SELECT status, COUNT(*) AS total
FROM yoshop_package
WHERE wxapp_id = 10001
  AND is_delete = 0
GROUP BY status
ORDER BY status;
```

### 4.5 Package item của package
```sql
SELECT *
FROM yoshop_package_item
WHERE order_id = 123456
ORDER BY id;
```

### 4.6 Package image
```sql
SELECT *
FROM yoshop_package_image
WHERE order_id = 123456
ORDER BY image_id DESC;
```

-----------------------------------
## 5. Debug inpack

### 5.1 Danh sách inpack theo tenant
```sql
SELECT id, order_sn, member_id, status, is_pay, line_id, storage_id, address_id,
       free, pack_free, other_free, real_payment, pack_ids, created_time, updated_time
FROM yoshop_inpack
WHERE wxapp_id = 10001
  AND is_delete = 0
ORDER BY id DESC
LIMIT 100;
```

### 5.2 Tìm inpack theo order_sn
```sql
SELECT *
FROM yoshop_inpack
WHERE order_sn = '2024102131912';
```

### 5.3 Inpack của một user
```sql
SELECT id, order_sn, status, is_pay, line_id, free, pack_free, other_free, real_payment, pack_ids
FROM yoshop_inpack
WHERE wxapp_id = 10001
  AND member_id = 12345
  AND is_delete = 0
ORDER BY id DESC;
```

### 5.4 Phân bố status inpack
```sql
SELECT status, is_pay, COUNT(*) AS total
FROM yoshop_inpack
WHERE wxapp_id = 10001
  AND is_delete = 0
GROUP BY status, is_pay
ORDER BY status, is_pay;
```

### 5.5 Xem package con của một inpack
Do `pack_ids` là csv/text, cách nhanh nhất là đọc `pack_ids` trước:
```sql
SELECT id, order_sn, pack_ids
FROM yoshop_inpack
WHERE id = 99999;
```

Sau đó thay danh sách ID vào query:
```sql
SELECT id, express_num, member_id, status, line_id, storage_id
FROM yoshop_package
WHERE id IN (1,2,3,4,5);
```

### 5.6 Tìm mọi inpack chứa package cụ thể
Lưu ý: query này không tối ưu vì `pack_ids` là text.
```sql
SELECT id, order_sn, pack_ids, status, is_pay
FROM yoshop_inpack
WHERE wxapp_id = 10001
  AND (
    pack_ids = '12345'
    OR pack_ids LIKE '12345,%'
    OR pack_ids LIKE '%,12345,%'
    OR pack_ids LIKE '%,12345'
  );
```

-----------------------------------
## 6. Debug line / pricing route

### 6.1 Danh sách line của tenant
```sql
SELECT id, name, free_mode, line_category, status, line_position, weight_min, max_weight, volumeweight, wxapp_id
FROM yoshop_line
WHERE wxapp_id = 10001
ORDER BY id DESC;
```

### 6.2 Xem line detail để debug pricing
```sql
SELECT id, name, free_mode, free_rule, tariff,
       weight_min, max_weight,
       volumeweight_type, volumeweight, bubble_weight,
       countrys, categorys, shop_id, status, line_position
FROM yoshop_line
WHERE id = 148;
```

### 6.3 Tìm package/inpack theo line
```sql
SELECT id, express_num, member_id, status
FROM yoshop_package
WHERE wxapp_id = 10001 AND line_id = 148
ORDER BY id DESC;
```

```sql
SELECT id, order_sn, member_id, status, is_pay
FROM yoshop_inpack
WHERE wxapp_id = 10001 AND line_id = 148
ORDER BY id DESC;
```

-----------------------------------
## 7. Debug warehouse / store_shop

### 7.1 Danh sách warehouse của tenant
```sql
SELECT shop_id, shop_name, type, country_id, status, wxapp_id
FROM yoshop_store_shop
WHERE wxapp_id = 10001
ORDER BY shop_id DESC;
```

### 7.2 Package theo warehouse
```sql
SELECT id, express_num, member_id, status, storage_id
FROM yoshop_package
WHERE wxapp_id = 10001
  AND storage_id = 58
  AND is_delete = 0
ORDER BY id DESC;
```

### 7.3 Inpack theo warehouse
```sql
SELECT id, order_sn, member_id, status, storage_id, shop_id
FROM yoshop_inpack
WHERE wxapp_id = 10001
  AND storage_id = 58
  AND is_delete = 0
ORDER BY id DESC;
```

-----------------------------------
## 8. Debug commerce order

### 8.1 Danh sách commerce order
```sql
SELECT order_id, order_no, user_id, pay_status, delivery_status, receipt_status, order_status, pay_price, wxapp_id
FROM yoshop_order
WHERE wxapp_id = 10001
  AND is_delete = 0
ORDER BY order_id DESC
LIMIT 100;
```

### 8.2 Order của một user
```sql
SELECT order_id, order_no, pay_status, delivery_status, receipt_status, order_status, pay_price
FROM yoshop_order
WHERE wxapp_id = 10001
  AND user_id = 12345
ORDER BY order_id DESC;
```

### 8.3 Order goods
```sql
SELECT order_goods_id, order_id, goods_id, goods_name, total_num, total_price, total_pay_price
FROM yoshop_order_goods
WHERE order_id = 99999;
```

### 8.4 Order address snapshot
```sql
SELECT *
FROM yoshop_order_address
WHERE order_id = 99999;
```

-----------------------------------
## 9. Debug buyer order / mua hộ

### 9.1 Danh sách buyer order theo user
```sql
SELECT b_order_id, order_sn, member_id, status, price, num, free, real_payment, storage_id, address_id, wxapp_id
FROM yoshop_buyer_order
WHERE wxapp_id = 10001
  AND member_id = 12345
ORDER BY b_order_id DESC;
```

### 9.2 Tìm buyer order theo URL/platform
```sql
SELECT b_order_id, order_sn, url, palform, status, member_id
FROM yoshop_buyer_order
WHERE wxapp_id = 10001
  AND url LIKE '%taobao%'
ORDER BY b_order_id DESC;
```

-----------------------------------
## 10. Debug recharge / payment

### 10.1 Recharge order của user
```sql
SELECT order_id, order_no, user_id, pay_status, actual_money, wxapp_id, create_time
FROM yoshop_recharge_order
WHERE wxapp_id = 10001
  AND user_id = 12345
ORDER BY order_id DESC;
```

### 10.2 Tìm payment record theo transaction/app_trans_id
Tùy implementation, có thể tra ở bảng pay hoặc order provider-specific record.
```sql
SELECT *
FROM yoshop_pay
ORDER BY id DESC
LIMIT 100;
```

-----------------------------------
## 11. Debug package <-> inpack inconsistency

### 11.1 Package đã có inpack nhưng status không khớp
Ví dụ tìm package đang ở trạng thái quá sớm nhưng đã gắn `inpack_id`:
```sql
SELECT id, express_num, inpack_id, status, member_id, wxapp_id
FROM yoshop_package
WHERE wxapp_id = 10001
  AND inpack_id > 0
  AND status IN (1,2,3);
```

### 11.2 Inpack đã pay nhưng package con chưa pay/ship đúng kỳ vọng
Bước 1: lấy inpack
```sql
SELECT id, order_sn, status, is_pay, pack_ids
FROM yoshop_inpack
WHERE id = 99999;
```

Bước 2: query package con
```sql
SELECT id, express_num, status, line_id, address_id, pack_service
FROM yoshop_package
WHERE id IN (1,2,3,4,5);
```

### 11.3 Inpack không có package con hợp lệ
```sql
SELECT id, order_sn, pack_ids, status
FROM yoshop_inpack
WHERE wxapp_id = 10001
  AND (pack_ids IS NULL OR pack_ids = '' OR pack_ids = '0')
ORDER BY id DESC;
```

-----------------------------------
## 12. Debug soft delete / recycle

### 12.1 Tenant recycle/delete
```sql
SELECT wxapp_id, is_recycle, is_delete, FROM_UNIXTIME(end_time) AS end_time_readable
FROM yoshop_wxapp
WHERE wxapp_id = 10001;
```

### 12.2 Store user delete
```sql
SELECT store_user_id, user_name, is_delete, wxapp_id
FROM yoshop_store_user
WHERE wxapp_id = 10001
ORDER BY store_user_id DESC;
```

### 12.3 Package soft delete
```sql
SELECT id, express_num, is_delete, wxapp_id
FROM yoshop_package
WHERE wxapp_id = 10001
  AND is_delete = 1
ORDER BY id DESC
LIMIT 100;
```

-----------------------------------
## 13. Query hỗ trợ điều tra nhanh

### 13.1 Top users nhiều package nhất
```sql
SELECT member_id, COUNT(*) AS total_packages
FROM yoshop_package
WHERE wxapp_id = 10001
  AND is_delete = 0
GROUP BY member_id
ORDER BY total_packages DESC
LIMIT 20;
```

### 13.2 Top users nhiều inpack nhất
```sql
SELECT member_id, COUNT(*) AS total_inpacks
FROM yoshop_inpack
WHERE wxapp_id = 10001
  AND is_delete = 0
GROUP BY member_id
ORDER BY total_inpacks DESC
LIMIT 20;
```

### 13.3 Package chưa nhận chủ
```sql
SELECT id, express_num, member_id, is_take, status, storage_id
FROM yoshop_package
WHERE wxapp_id = 10001
  AND is_take = 1
  AND is_delete = 0
ORDER BY id DESC;
```

### 13.4 Inpack chờ thanh toán
```sql
SELECT id, order_sn, member_id, status, is_pay, free, pack_free, other_free
FROM yoshop_inpack
WHERE wxapp_id = 10001
  AND status = 2
  AND is_pay = 2
ORDER BY id DESC;
```

-----------------------------------
## 14. Quy tắc khi tự viết query mới

1. Hầu như luôn thêm `wxapp_id`
2. Luôn nhớ soft delete (`is_delete`) nếu có
3. Xác định rõ đang query:
- package
- inpack
- commerce order
- buyer order
4. Với `pack_ids`, không dùng `IN (...)` trực tiếp trên field text
5. Khi số liệu lạ, đối chiếu thêm status bucket trong code chứ đừng chỉ nhìn raw SQL

-----------------------------------
## 15. Kết luận

Để debug nhanh nhất, thứ tự query thường là:
1. xác định tenant
2. xác định user/store user
3. xem package
4. xem inpack
5. xem line
6. nếu có payment thì xem recharge/pay/callback data

Đó là đường đi ngắn nhất để không bị lạc trong schema lớn này.