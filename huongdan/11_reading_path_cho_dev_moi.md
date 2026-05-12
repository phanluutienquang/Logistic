# 11. Reading path cho dev mới

File này là lộ trình onboarding thực dụng.

Mục tiêu:
- nếu bạn là dev mới, biết nên đọc gì trước
- nếu bạn là lead, có thể gửi file này cho người mới vào dự án
- tránh kiểu đọc repo từ trên xuống dưới rồi bị rối ngay ngày đầu

## 1. Có 3 kiểu onboarding

### Kiểu A - Cần hiểu nhanh để fix bug
Thời gian: 2-4 giờ

### Kiểu B - Cần hiểu đủ để bắt đầu code an toàn
Thời gian: 1-2 ngày

### Kiểu C - Cần hiểu sâu để ownership module
Thời gian: 5-7 ngày

-----------------------------------
## 2. Lộ trình 30 phút đầu tiên

Mục tiêu:
- biết dự án này thực chất là gì
- biết domain xương sống là gì
- biết không được nhầm `package` với `inpack`

Đọc theo thứ tự:
1. `huongdan/README.md`
2. `huongdan/01_tong_quan_kien_truc.md`
3. `huongdan/02_domain_va_business_logic.md`

Sau 30 phút bạn phải trả lời được:
- tenant là gì?
- `wxapp_id` dùng để làm gì?
- `package` khác `inpack` chỗ nào?
- dự án này logistics là lõi hay e-commerce là lõi?

-----------------------------------
## 3. Lộ trình 2 giờ đầu tiên

Mục tiêu:
- hiểu nền tảng kỹ thuật và auth/data scope

Đọc theo thứ tự:
1. `huongdan/03_auth_authz_saas.md`
2. `huongdan/06_codebase_map.md`
3. `source/application/common/model/BaseModel.php`
4. `source/application/api/controller/Controller.php`
5. `source/application/store/controller/Controller.php`
6. `source/application/web/controller/Controller.php`

Bạn cần rút ra được:
- auth có mấy lớp?
- data scope nằm ở đâu?
- vì sao cùng query mà staff khác nhau thấy data khác nhau?
- module nào là platform, module nào là business backend, module nào là client-facing?

-----------------------------------
## 4. Lộ trình 1 buổi - dành cho người cần fix bug logistics

Mục tiêu:
- hiểu đủ flow package/inpack để debug

Thứ tự đọc:
1. `huongdan/04_flow_nghiep_vu_chinh.md`
2. `huongdan/05_database_map.md`
3. `source/application/web/model/Package.php`
4. `source/application/web/model/Inpack.php`
5. `source/application/web/model/Line.php`
6. `source/application/web/controller/Package.php`
7. `source/application/store/controller/package/Index.php`
8. `xinsuju.sql` các bảng:
   - `yoshop_package`
   - `yoshop_inpack`
   - `yoshop_line`
   - `yoshop_store_shop`
   - `yoshop_user`

Sau buổi này bạn phải hiểu:
- package vào kho như nào
- package được gom thành inpack như nào
- line ảnh hưởng tính cước như nào
- thanh toán inpack update những gì

-----------------------------------
## 5. Lộ trình 1 ngày - dành cho dev bắt đầu implement feature

Buổi sáng:
1. `README.md`
2. `01_tong_quan_kien_truc.md`
3. `02_domain_va_business_logic.md`
4. `03_auth_authz_saas.md`
5. `05_database_map.md`

Buổi chiều:
6. `06_codebase_map.md`
7. `08_erd_textual.md`
8. `09_dictionary_thuat_ngu.md`
9. `10_bug_risk_va_diem_can_than.md`
10. mở code:
   - `BaseModel.php`
   - `admin/controller/Store.php`
   - `store/service/Auth.php`
   - `web/controller/Package.php`
   - `api/service/passport/Login.php`

Kết quả kỳ vọng:
- hiểu tổng thể
- không nhầm domain
- bắt đầu sửa feature nhỏ an toàn hơn

-----------------------------------
## 6. Lộ trình 2 ngày - dành cho dev phải code ở logistics core

### Ngày 1 - hiểu nền tảng
- đọc toàn bộ bộ `huongdan/01` đến `10`
- đặc biệt focus:
  - auth/authz/saas
  - package/inpack flow
  - bug risk

### Ngày 2 - đọc code chiều sâu
1. `source/application/common/model/BaseModel.php`
2. `source/application/common/model/Wxapp.php`
3. `source/application/common/model/User.php`
4. `source/application/web/model/Package.php`
5. `source/application/web/model/Inpack.php`
6. `source/application/store/model/Package.php`
7. `source/application/store/model/Inpack.php`
8. `source/application/web/controller/Package.php`
9. `source/application/store/controller/package/Index.php`
10. `source/application/api/controller/Package.php`

Mục tiêu cuối ngày 2:
- trace được package lifecycle end-to-end
- trace được inpack lifecycle end-to-end
- biết chỗ nào user-side, API-side, warehouse-side

-----------------------------------
## 7. Lộ trình 5-7 ngày - dành cho người sẽ ownership module

### Giai đoạn 1 - nền tảng
- đọc toàn bộ `huongdan`
- note lại toàn bộ aggregate chính

### Giai đoạn 2 - logistics core
- package
- inpack
- line
- logistics tracking
- warehouse/shelf/batch

### Giai đoạn 3 - auth/platform
- tenant provisioning
- store RBAC
- multi-platform login

### Giai đoạn 4 - commerce sidecar
- goods/cart/order
- buyer order
- recharge/payment
- sharing/bargain/sharp/blindbox

### Giai đoạn 5 - hardening
- đọc `10_bug_risk_va_diem_can_than.md`
- grep debug/deadly statements
- map vùng legacy cần tránh

-----------------------------------
## 8. Nếu bạn là backend dev, nên đọc theo domain nào trước?

### Nếu bạn làm logistics
Ưu tiên:
- package
- inpack
- line
- warehouse
- tracking

### Nếu bạn làm auth/platform
Ưu tiên:
- wxapp / tenant
- BaseModel
- store user / role / access
- passport login service

### Nếu bạn làm commerce
Ưu tiên:
- goods
- cart
- order
- coupon
- recharge/payment

### Nếu bạn làm data/reporting
Ưu tiên:
- database map
- ERD textual
- status map
- bug risk về denormalized relation

-----------------------------------
## 9. 10 câu hỏi phải tự trả lời sau onboarding

1. Tenant mới được sinh ra như thế nào?
2. `wxapp_id` được bind ở đâu?
3. Ai chịu trách nhiệm check permission URL?
4. Vì sao staff khác nhau thấy package khác nhau?
5. `package` và `inpack` liên kết bằng FK chuẩn hay bằng csv?
6. package status nào nghĩa là chờ thanh toán?
7. inpack status nào nghĩa là đã ship?
8. order commerce dùng bảng nào?
9. buyer order khác order commerce ra sao?
10. file nào nguy hiểm nhất khi sửa query toàn hệ thống?

Nếu chưa trả lời được 10 câu này, chưa nên sửa flow lớn.

-----------------------------------
## 10. Lộ trình cực ngắn cho PM/BA/QA

Nếu không code nhưng cần hiểu hệ thống:
- `README.md`
- `01_tong_quan_kien_truc.md`
- `02_domain_va_business_logic.md`
- `04_flow_nghiep_vu_chinh.md`
- `09_dictionary_thuat_ngu.md`
- `10_bug_risk_va_diem_can_than.md`

-----------------------------------
## 11. Kết luận

Đọc đúng thứ tự quan trọng hơn đọc nhiều.

Thứ tự tốt nhất luôn là:
- domain trước
- auth/saas sau
- database map sau nữa
- rồi mới vào code

Nếu đọc code trước khi hiểu `package/inpack/wxapp_id`, gần như chắc chắn sẽ hiểu sai dự án.