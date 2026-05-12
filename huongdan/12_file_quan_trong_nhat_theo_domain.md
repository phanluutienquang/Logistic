# 12. File quan trọng nhất theo domain

File này trả lời câu hỏi:
- nếu tôi muốn hiểu một domain cụ thể, tôi nên mở file nào trước?

Mỗi domain sẽ có:
- file bắt đầu
- file đọc tiếp
- mục tiêu đọc
- kết quả phải hiểu được

## 1. Domain tenant / SaaS

### Mở trước
1. `source/application/admin/controller/Store.php`
2. `source/application/admin/model/Wxapp.php`
3. `source/application/common/model/Wxapp.php`
4. `source/application/common/model/BaseModel.php`

### Vì sao
- `Store.php` = thao tác lifecycle tenant
- `admin/model/Wxapp.php` = provision tenant mặc định
- `common/model/Wxapp.php` = runtime tenant config/cache
- `BaseModel.php` = tenant data isolation

### Sau khi đọc xong phải hiểu
- tenant mới được tạo như nào
- tenant bị recycle/delete như nào
- tenant enter store như nào
- tenant scope được áp vào query ở đâu

-----------------------------------
## 2. Domain auth / authorization

### Mở trước
1. `source/application/admin/controller/Passport.php`
2. `source/application/store/controller/Passport.php`
3. `source/application/web/controller/Passport.php`
4. `source/application/api/controller/Passport.php`
5. `source/application/store/service/Auth.php`
6. `source/application/store/model/store/User.php`
7. `source/application/admin/model/admin/User.php`

### Vì sao
- mỗi module có auth riêng
- `Auth.php` là permission checker chính của store
- model user store/admin cho thấy session và business restriction

### Sau khi đọc xong phải hiểu
- admin login khác store login ra sao
- session nào dùng cho module nào
- URL permission check hoạt động thế nào
- `is_super` ảnh hưởng thế nào

-----------------------------------
## 3. Domain multi-platform login

### Mở trước
1. `source/application/api/controller/Passport.php`
2. `source/application/api/service/passport/Login.php`
3. `source/application/api/service/passport/Party.php`
4. `source/application/api/model/line/User.php`

### Focus thêm
- search `loginMpZalo`
- search `loginMpLine`
- search `loginMpWxMobile`
- search `loginClerk`

### Sau khi đọc xong phải hiểu
- mobile/email register/login flow
- WeChat flow
- Zalo flow
- LINE flow
- clerk flow
- `open_id` đang được dùng linh hoạt ra sao

-----------------------------------
## 4. Domain end-user profile / wallet

### Mở trước
1. `source/application/common/model/User.php`
2. `source/application/web/model/User.php`
3. `source/application/web/controller/User.php`
4. `source/application/web/model/UserAddress.php`

### Vì sao
- user aggregate và balance behavior nằm nhiều ở model
- web side phản ánh use case người dùng cuối rõ hơn

### Sau khi đọc xong phải hiểu
- user có những field business gì
- balance update/log xảy ra thế nào
- user_code được sinh thế nào
- address được quản lý thế nào

-----------------------------------
## 5. Domain logistics inbound - package

### Mở trước
1. `source/application/web/model/Package.php`
2. `source/application/api/model/Package.php`
3. `source/application/store/model/Package.php`
4. `source/application/web/controller/Package.php`
5. `source/application/store/controller/package/Index.php`
6. `source/application/store/controller/package/Newpack.php`

### Vì sao
- cùng aggregate package nhưng ở 3 góc nhìn:
  - web user
  - api client
  - store warehouse ops

### Sau khi đọc xong phải hiểu
- package được sinh từ đâu
- package được inbound ra sao
- package query/filter/status map ra sao
- package item/category/image liên hệ thế nào

-----------------------------------
## 6. Domain consolidation/outbound - inpack

### Mở trước
1. `source/application/web/model/Inpack.php`
2. `source/application/api/model/Inpack.php`
3. `source/application/store/model/Inpack.php`
4. `source/application/web/controller/Package.php` (các hàm `inpack`, `doPay`, `details_pack`)
5. `source/application/api/controller/Package.php`

### Vì sao
- inpack là aggregate logistics outbound chính
- status và payment của nó quyết định package lifecycle downstream

### Sau khi đọc xong phải hiểu
- inpack được tạo từ package như nào
- `pack_ids` hoạt động ra sao
- inpack status đổi ra sao
- payment update gì
- shipment/tracking update gì

-----------------------------------
## 7. Domain pricing / line

### Mở trước
1. `source/application/web/model/Line.php`
2. `source/application/common/model/Line.php` nếu có logic sâu hơn
3. `source/application/store/view/setting/line/*`
4. `xinsuju.sql` phần `yoshop_line*`
5. chỗ gọi `computeLinePrice` trong `web/controller/Package.php` / `store/controller/package/Index.php`

### Vì sao
- line là policy engine của logistics pricing

### Sau khi đọc xong phải hiểu
- line có các mode tính cước nào
- free_rule/weight/volumeweight ảnh hưởng gì
- country/category/shop support dùng ra sao

-----------------------------------
## 8. Domain warehouse / shelf / batch

### Mở trước
1. `source/application/store/controller/package/Index.php`
2. `source/application/store/view/shop/shelf/*`
3. `source/application/web/model/Shelf.php`
4. `source/application/web/model/ShelfUnit.php`
5. `source/application/web/model/ShelfUnitItem.php`
6. `source/application/task/behavior/Batch.php`

### Sau khi đọc xong phải hiểu
- package được lên kệ và xuống kệ ra sao
- kho quản vị trí vật lý thế nào
- batch dùng cho xử lý hàng loạt gì

-----------------------------------
## 9. Domain commerce order

### Mở trước
1. `source/application/common/model/Order.php`
2. `source/application/api/controller/Order.php`
3. `source/application/web/controller/Order.php`
4. `source/application/store/model/Order.php`
5. `service/order/Checkout.php` ở api/web

### Vì sao
- đây là flow commerce thuần có status/pay/delivery/receipt riêng

### Sau khi đọc xong phải hiểu
- buy-now và cart khác nhau thế nào
- order status text map thế nào
- delivery/receipt/complete flow thế nào

-----------------------------------
## 10. Domain buyer order / mua hộ

### Mở trước
1. `source/application/web/model/BuyerOrder.php`
2. `source/application/api/model/BuyerOrder.php`
3. các controller/user operation nào gọi buyer flow

### Sau khi đọc xong phải hiểu
- URL external marketplace được parse thế nào
- amount được tính như nào
- balance bị trừ ở đâu

-----------------------------------
## 11. Domain recharge / payment

### Mở trước
1. `source/application/api/controller/Recharge.php`
2. `source/application/api/controller/Zalopay.php`
3. `source/application/common/enum/order/PayType.php`
4. `source/application/common/model/Setting.php`
5. `source/application/web/service/recharge/PaySuccess.php`
6. `source/application/web/service/order/PaySuccess.php`

### Sau khi đọc xong phải hiểu
- loại pay type nào đang có
- callback nào xử lý payment success
- recharge khác payment inpack/order thế nào

-----------------------------------
## 12. Domain dealer / referral / commission

### Mở trước
1. `source/application/common/model/dealer/*`
2. `source/application/store/controller/apps/dealer/*`
3. `source/application/task/behavior/DealerOrder.php`
4. SQL `yoshop_dealer_*`, `yoshop_referral_*`

### Sau khi đọc xong phải hiểu
- ai là distributor/dealer
- commission sinh khi nào
- settlement/withdraw flow nằm ở đâu

-----------------------------------
## 13. Domain social commerce

### Mở trước
1. `source/application/api/controller/sharp/*`
2. `source/application/store/controller/apps/sharing/*`
3. `source/application/common/model/sharing*`
4. `source/application/common/model/bargain*`

### Sau khi đọc xong phải hiểu
- module nào thực sự đang active
- social module có giao với logistics thế nào

-----------------------------------
## 14. Domain background jobs

### Mở trước
1. `source/application/task/service/Order.php`
2. `source/application/task/behavior/Order.php`
3. `source/application/task/behavior/Inpack.php`
4. `source/application/task/behavior/Batch.php`
5. `docs/database/async_task_queue.sql`

### Sau khi đọc xong phải hiểu
- job nào auto close order
- job nào sync trạng thái / nhắc việc / hậu xử lý
- có queue schema gì cho async task

-----------------------------------
## 15. Domain observability / maintenance risk

### Mở trước
1. `huongdan/10_bug_risk_va_diem_can_than.md`
2. grep toàn repo cho `dump(`, `die;`, `TODO`, `FIXME`
3. `xinsuju.sql` phần monitoring tables

### Sau khi đọc xong phải hiểu
- vùng nào dễ bug nhất
- debug statements còn ở đâu
- external integration nào nhạy cảm

-----------------------------------
## 16. 5 file quan trọng nhất toàn repo

Nếu chỉ được mở 5 file, hãy mở:
1. `source/application/common/model/BaseModel.php`
2. `source/application/admin/controller/Store.php`
3. `source/application/store/service/Auth.php`
4. `source/application/web/controller/Package.php`
5. `source/application/common/model/Order.php`

Lý do:
- file 1: tenant + data scope
- file 2: SaaS provisioning
- file 3: RBAC
- file 4: logistics core flow
- file 5: commerce order status model

-----------------------------------
## 17. Kết luận

Muốn hiểu đúng domain nào, đừng mở theo thư mục trước.
Hãy mở theo aggregate root trước, rồi mới mở controller/service xung quanh aggregate đó.