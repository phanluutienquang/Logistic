# 10. Bug risk, legacy map và các điểm cần cực kỳ cẩn thận

File này tổng hợp các vùng rủi ro cao khi bảo trì dự án.

Mục tiêu:
- giúp bạn biết chỗ nào dễ nổ bug
- biết chỗ nào mang tính legacy
- biết chỗ nào phải test kỹ trước khi sửa

## 1. Rủi ro cấp hệ thống

### 1.1 Multi-tenant leak risk
Mọi sửa đổi liên quan query đều có nguy cơ leak dữ liệu cross-tenant nếu:
- quên `wxapp_id`
- bypass `BaseModel` global scope
- dùng `useGlobalScope(false)` không kiểm soát

Các file cần nhớ:
- `source/application/common/model/BaseModel.php`
- mọi model/query có `useGlobalScope(false)`

Bug có thể xảy ra:
- tenant A thấy data tenant B
- report sai số
- payment/recharge đụng nhầm tenant

Mức độ rủi ro: rất cao

-----------------------------------
## 2. Rủi ro do denormalized relationship

### 2.1 `yoshop_inpack.pack_ids` lưu csv/text
Đây là một trong những điểm rủi ro cao nhất.

Dấu hiệu trong code:
- rất nhiều chỗ `explode(',', $pack_ids)`
- nhiều chỗ append/remove package thủ công
- có create flow dùng `implode(',',$ids)`
- có nơi draft tạm từng dùng `json_encode($ids)`

Điều này nguy hiểm vì:
- không có referential integrity chuẩn
- query bằng SQL khó tối ưu và dễ sai
- remove 1 package khỏi inpack rất dễ lỗi logic
- duplicate ID / empty string / format mismatch dễ xảy ra
- reporting/count package trong inpack dễ lệch

Mức độ rủi ro: rất cao

Khuyến nghị khi sửa:
- trace toàn bộ nơi đọc/ghi `pack_ids`
- test add/remove/merge/split package kỹ
- không refactor field này nếu chưa map hết call sites

-----------------------------------
## 3. Rủi ro do status hardcode

Repo này dùng rất nhiều numeric status.

### 3.1 `package.status`
- có nhiều meaning theo lifecycle kho

### 3.2 `inpack.status`
- khác `package.status`

### 3.3 commerce order status
- `pay_status`
- `delivery_status`
- `receipt_status`
- `order_status`

Vấn đề:
- cùng số nhưng khác nghĩa giữa bảng
- comment trong SQL và text map trong model/controller có chỗ diễn đạt hơi khác
- dễ update sai status vì nhầm aggregate

Dấu hiệu thực tế trong code:
- nhiều nơi dùng trực tiếp `'status' => 1/4/5/6/10/11`
- rất ít enum hóa xuyên suốt cho logistics package/inpack

Mức độ rủi ro: rất cao

Khuyến nghị khi sửa:
- luôn kiểm tra cả 3 nơi:
  1. schema SQL comment
  2. model status map
  3. controller/business transition
- không đổi số status nếu chưa audit toàn repo

-----------------------------------
## 4. Rủi ro do auth đa kiểu

Hệ thống có nhiều auth mode:
- admin session
- store session
- web session
- api token cache
- WeChat/Zalo/LINE login
- clerk login

Nguy cơ:
- sửa 1 flow làm gãy flow khác
- bug session/token inconsistency
- binding provider identity không đồng nhất
- `open_id` đang dùng linh hoạt, không thuần 1 provider

Ví dụ:
- LINE login dùng `open_id = 'LINE_' . line_user_id`
- Zalo login dùng `open_id = zalo_user_id`
- mobile/email flow cũng có thể dùng `open_id` surrogate

Mức độ rủi ro: cao

Khuyến nghị:
- test matrix theo platform sau mọi thay đổi auth
- đừng giả định `open_id` luôn là wechat openid thật

-----------------------------------
## 5. Rủi ro do data scope ẩn ở BaseModel

Nhiều bug khó trace xuất hiện vì data scope không nằm trong controller mà ở `BaseModel::base()`.

Scope đang thấy:
- `wxapp_id`
- `shop_id` cho `yoshop_inpack`, `yoshop_package`
- `clerk_id` cho `yoshop_user.service_id`
- có dấu vết chuẩn bị scope `line_id`, `country_id`

Nguy hiểm ở chỗ:
- cùng câu query nhưng user khác trả data khác
- debug local bằng admin account có thể không tái hiện bug của staff account
- thêm report/export raw query rất dễ bỏ quên scope

Mức độ rủi ro: cao

-----------------------------------
## 6. Rủi ro do debug code còn sót trong production path

Tìm thấy khá nhiều pattern kiểu:
- `dump($e); die;`
- `dump(...);die;`
- debug statements trong controller/model/library

Ví dụ rõ ràng:
- `source/application/web/model/Package.php`
- `source/application/web/model/BuyerOrder.php`
- `source/application/web/controller/Package.php`
- `source/application/web/controller/Address.php`
- `source/application/common/library/Analysis.php`
- nhiều file integration library khác

Nguy cơ:
- request chết cứng trong runtime
- rò dữ liệu debug ra người dùng
- callback payment / external integration fail vì die giữa chừng

Mức độ rủi ro: cao

Khuyến nghị:
- trước khi deploy/chỉnh flow nhạy cảm, grep lại `dump(` và `die;`
- ưu tiên thay bằng logging/exception handling chuẩn

-----------------------------------
## 7. Rủi ro do legacy / naming inconsistency

Có nhiều tên gọi mang dấu vết phát triển dài hạn:
- `wxapp` nhưng dùng như tenant
- `store` vừa là backend vừa là warehouse context
- `order` có nhiều nghĩa
- `pack_ids` / `pack_service` / `pack_services_id` semantics không thật đồng nhất

Nguy cơ:
- dev mới hiểu sai aggregate
- sửa nhầm module hoặc bảng
- lẫn `order` commerce với `inpack` logistics

Mức độ rủi ro: cao

-----------------------------------
## 8. Rủi ro do transactional boundary chưa chặt

Một số flow có transaction, nhưng codebase rộng nên không phải flow nào cũng đóng transaction thật chặt.

Ví dụ cần cẩn thận:
- package -> inpack creation
- inpack payment -> update package -> deduct balance -> write log
- buyer order -> deduct balance -> write log

Nguy cơ:
- update nửa chừng
- balance bị trừ nhưng order chưa update hoặc ngược lại
- package status và inpack status lệch

Mức độ rủi ro: cao

Khuyến nghị:
- khi sửa payment/inpack flow, audit toàn transaction path
- kiểm tra rollback thật sự ở mọi nhánh lỗi

-----------------------------------
## 9. Rủi ro do external integration

Các integration surface thấy trong repo/schema:
- WeChat Pay
- ZaloPay
- LINE login / LINE app config
- nhiều carrier/logistics providers
- OCR / AI / QR / printer

Nguy cơ:
- mỗi provider có payload/assumption riêng
- callback/webhook có thể bị die vì debug code
- môi trường test/prod cấu hình khác nhau theo tenant

Mức độ rủi ro: trung bình đến cao

Khuyến nghị:
- mọi sửa đổi callback phải test idempotency
- đừng assume mọi tenant cấu hình provider giống nhau

-----------------------------------
## 10. Rủi ro do package/inpack status sync

Nhiều flow update song song:
- inpack status
- package status
- logistics history
- balance/payment fields

Ví dụ trong `web/controller/Package.php`:
- tạo inpack xong update package status sang chờ thanh toán
- pay inpack xong update package status sang đã thanh toán
- complete/comment flow còn có thể đẩy package sang status hoàn tất

Nguy cơ:
- package và inpack lệch trạng thái
- báo cáo dashboard sai
- user thấy UI khác với kho backend

Mức độ rủi ro: rất cao

-----------------------------------
## 11. Rủi ro do query/reporting trên schema lớn

Schema có ~239 bảng.
Một số report/query rất dễ:
- join sai domain
- đếm sai vì soft delete / status / wxapp scope
- nhầm package count với inpack count

Mức độ rủi ro: trung bình đến cao

Khuyến nghị:
- khi viết report, xác định rõ đang đếm:
  - package
  - inpack
  - commerce order
  - buyer order
- luôn thêm điều kiện tenant và soft delete/status phù hợp

-----------------------------------
## 12. Các vùng legacy/backlog nên đánh dấu kỹ

### Vùng A - auth/provider identity normalization
- `open_id` overload nhiều nghĩa
- provider binding chưa hoàn toàn chuẩn hóa theo một model thống nhất

### Vùng B - inpack/package relation normalization
- `pack_ids` csv là debt lớn

### Vùng C - status enum hóa
- logistics flow còn hardcoded nhiều

### Vùng D - debug statements
- cần cleanup có hệ thống

### Vùng E - route/business duplication giữa web/api
- cùng business flow xuất hiện ở cả `web/controller/Package.php` và `api/controller/Package.php`
- nguy cơ drift logic cao

-----------------------------------
## 13. Nơi dễ drift logic nhất

1. `web/controller/Package.php`
2. `api/controller/Package.php`
3. `store/controller/package/Index.php`
4. `api/service/passport/Login.php`
5. `common/model/BaseModel.php`

Lý do:
- đây là các điểm tập trung business rule hoặc scoping rule quan trọng

-----------------------------------
## 14. Checklist trước khi sửa flow nhạy cảm

### Nếu sửa auth
- [ ] test admin login
- [ ] test store login
- [ ] test web login
- [ ] test api token flow
- [ ] test WeChat/Zalo/LINE/clerk nếu liên quan
- [ ] test tenant expiry behavior

### Nếu sửa package/inpack
- [ ] test tạo package
- [ ] test inbound package
- [ ] test create inpack
- [ ] test address change
- [ ] test pay inpack
- [ ] test package status sync
- [ ] test logistics tracking render

### Nếu sửa RBAC/data scope
- [ ] test super store user
- [ ] test normal store user
- [ ] test warehouse-scoped user
- [ ] test cross-tenant leakage

### Nếu sửa payment
- [ ] test balance deduction
- [ ] test rollback
- [ ] test payment callback idempotency
- [ ] test log creation

-----------------------------------
## 15. Kết luận ngắn

Ba vùng nguy hiểm nhất của dự án là:

1. tenant/data scope trong `BaseModel`
2. quan hệ `inpack <-> package` qua `pack_ids`
3. status transition logistics/payment

Nếu sửa một trong ba vùng này mà không test đủ, xác suất phát sinh bug production là rất cao.