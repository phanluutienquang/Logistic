# 18. Debt refactor gợi ý

File này tổng hợp các debt kỹ thuật và hướng refactor nên làm nếu muốn hệ thống bền hơn.

Mục tiêu:
- không phải để refactor ngay toàn bộ
- mà để biết nên đầu tư công sức vào đâu trước
- ưu tiên những debt có nguy cơ gây bug production cao nhất

## 1. Ưu tiên refactor tổng quan

### Nhóm P0 - rủi ro rất cao
1. normalize quan hệ `inpack <-> package`
2. gom status thành enum/constant rõ ràng
3. dọn sạch `dump()/die()` khỏi business path
4. chuẩn hóa auth provider identity
5. bảo vệ tenant scope tốt hơn

### Nhóm P1 - rủi ro cao nhưng có thể làm sau
6. tách controller quá lớn, đặc biệt `api/controller/Package.php`
7. giảm drift logic giữa web/api
8. tăng transaction boundary consistency
9. chuẩn hóa naming/domain vocabulary

### Nhóm P2 - nâng chất lượng dài hạn
10. thêm test regression cho flows xương sống
11. thêm service layer rõ ràng cho logistics aggregates
12. tách read-model/report-model khỏi write-model

-----------------------------------
## 2. Debt 1 - `pack_ids` dạng csv/text

### Vấn đề
`yoshop_inpack.pack_ids` hiện lưu danh sách package bằng csv/text.

### Hậu quả
- không có FK chuẩn
- khó query/filter/index
- append/remove package dễ bug
- reporting khó chính xác
- dễ phát sinh state mismatch giữa inpack và package

### Hướng refactor đề xuất
Tạo junction table mới, ví dụ:
- `yoshop_inpack_package`
  - `id`
  - `inpack_id`
  - `package_id`
  - `wxapp_id`
  - timestamps

### Chiến lược migration an toàn
1. tạo bảng junction mới
2. backfill dữ liệu từ `pack_ids`
3. viết dual-write một thời gian
   - update cả junction table và `pack_ids`
4. chuyển read-path sang junction table
5. sau cùng mới bỏ phụ thuộc `pack_ids`

### Mức ưu tiên
P0

-----------------------------------
## 3. Debt 2 - status hardcode khắp nơi

### Vấn đề
- `package.status`
- `inpack.status`
- `is_pay`
- `order_status`, `pay_status`, `delivery_status`, `receipt_status`
đang xuất hiện ở rất nhiều controller/model dưới dạng số cứng.

### Hậu quả
- khó đọc
- dễ nhầm aggregate
- sửa transition dễ làm gãy flow khác
- report và UI text có thể lệch nhau

### Hướng refactor đề xuất
Tạo enum/constant class rõ ràng cho từng aggregate:
- `PackageStatus`
- `InpackStatus`
- `InpackPayStatus`
- `CommerceOrderStatus`
- `CommercePayStatus`
...

### Cách làm an toàn
1. không đổi số trước
2. introduce constants trước
3. thay dần literal numbers bằng constants
4. cuối cùng gom helper transition nếu cần

### Mức ưu tiên
P0

-----------------------------------
## 4. Debt 3 - debug code còn sót trong runtime path

### Vấn đề
Tìm thấy khá nhiều:
- `dump($e); die;`
- `dump(...);die;`
- hard stop trong controller/model/library/provider

### Hậu quả
- request chết đột ngột
- webhook thất bại im lặng
- lộ dữ liệu debug
- trải nghiệm production rất xấu

### Hướng refactor đề xuất
1. sweep toàn repo để loại bỏ/debug-guard
2. thay bằng:
   - structured logging
   - exception throwing
   - error response chuẩn
3. tách debug-only path bằng flag môi trường nếu thực sự cần

### Mức ưu tiên
P0

-----------------------------------
## 5. Debt 4 - auth identity chưa chuẩn hóa

### Vấn đề
`open_id` đang bị overload:
- WeChat openid
- Zalo user id
- `LINE_<id>`
- mobile/email surrogate id

### Hậu quả
- semantics field không rõ
- dễ sinh duplicate user hoặc binding lỗi
- khó mở rộng provider mới

### Hướng refactor đề xuất
Tạo lớp identity provider rõ ràng:
- giữ `user` là system identity
- tạo/chuẩn hóa bảng bindings, ví dụ:
  - `user_identity`
    - `user_id`
    - `provider` (`wechat`, `zalo`, `line`, `mobile`, `email`)
    - `provider_user_id`
    - `wxapp_id`
    - metadata

### Chiến lược an toàn
- giới thiệu bảng binding mới
- backfill từ data cũ
- đọc ưu tiên binding mới, fallback dữ liệu cũ

### Mức ưu tiên
P0-P1

-----------------------------------
## 6. Debt 5 - tenant scope phụ thuộc quá nhiều vào convention ẩn

### Vấn đề
Tenant isolation chủ yếu nhờ `BaseModel::base()` và convention module binding.

### Hậu quả
- dev mới khó nhận ra scope đang áp ở đâu
- dùng `useGlobalScope(false)` rất nguy hiểm
- query đặc biệt/report/export dễ quên tenant scope

### Hướng refactor đề xuất
1. tạo helper/query builders rõ ràng cho tenant-scoped access
2. audit tất cả `useGlobalScope(false)`
3. thêm guardrails/logging khi bypass scope
4. nếu có thể, viết static analysis rule hoặc code review checklist riêng cho tenant safety

### Mức ưu tiên
P0-P1

-----------------------------------
## 7. Debt 6 - controller quá lớn, đặc biệt logistics API

### Vấn đề
`api/controller/Package.php` cực lớn, ôm quá nhiều use case.

### Hậu quả
- khó maintain
- khó test
- bug nhỏ dễ regression flow khác
- mental load rất lớn

### Hướng refactor đề xuất
Tách theo bounded use case, ví dụ:
- `PackageReportController`
- `PackageQueryController`
- `InpackController`
- `InpackPaymentController`
- `TrackingController`
- `PackageLookupController`

Hoặc giữ route cũ nhưng chuyển business vào service classes nhỏ hơn.

### Mức ưu tiên
P1

-----------------------------------
## 8. Debt 7 - drift logic giữa web và api

### Vấn đề
Cùng một business flow có ở:
- `web/controller/Package.php`
- `api/controller/Package.php`

Tương tự với order/login ở một số phần.

### Hậu quả
- fix ở web quên fix ở api
- behavior không đồng nhất
- QA khó cover

### Hướng refactor đề xuất
1. đẩy business logic xuống service layer chung
2. controller web/api chỉ làm:
   - parse input
   - gọi service
   - format response
3. viết contract test cho shared service

### Mức ưu tiên
P1

-----------------------------------
## 9. Debt 8 - transaction boundary chưa nhất quán

### Vấn đề
Một số flow có transaction, nhưng chưa chắc mọi side-effect đều nằm trong boundary rõ ràng.

Flow nhạy cảm:
- tạo inpack
- thanh toán inpack
- buyer order payment
- package -> batch -> shipment updates

### Hướng refactor đề xuất
- xác định aggregate root rõ ràng
- mọi write liên quan aggregate nằm trong application service với transaction rõ ràng
- side effects kiểu message/notification nên tách thành post-commit event

### Mức ưu tiên
P1

-----------------------------------
## 10. Debt 9 - thiếu read model/report model tách biệt

### Vấn đề
Schema lớn và logic reporting đang lẫn khá nhiều với write model/status bucket.

### Hậu quả
- query report khó đọc
- hiệu năng/reporting correctness dễ lệch

### Hướng refactor đề xuất
- tách query service/report service
- define canonical status bucket cho dashboard
- nếu cần, tạo summary/materialized table cho dashboard nặng

### Mức ưu tiên
P2

-----------------------------------
## 11. Debt 10 - naming không nhất quán

### Ví dụ
- `wxapp` nhưng dùng như tenant
- `store` vừa là backend vừa là warehouse/shop
- `order` nhiều nghĩa
- `pack_services_id`, `pack_service`, `pack_ids` dễ gây nhầm

### Hướng refactor đề xuất
- chưa cần đổi DB/table ngay
- nhưng nên đổi ở tầng docs + service names + comments + DTO names trước
- tạo canonical vocabulary trong team

### Mức ưu tiên
P2

-----------------------------------
## 12. Đề xuất roadmap refactor thực dụng

### Sprint A - safety first
- quét và loại bỏ `dump/die`
- audit `useGlobalScope(false)`
- thêm documentation/checklist tenant safety
- bắt đầu thay status literal bằng constant ở flow nóng nhất

### Sprint B - logistics core hardening
- introduce `PackageStatus` / `InpackStatus`
- refactor payment/inpack transitions vào service rõ ràng
- chuẩn hóa error handling logistics/payment

### Sprint C - relation normalization
- thêm `yoshop_inpack_package`
- backfill + dual-write
- chuyển read path dần

### Sprint D - auth/provider cleanup
- thêm bảng identity binding chuẩn hóa
- migrate LINE/Zalo/WeChat/mobile/email mapping

### Sprint E - controller slimming
- tách `api/controller/Package.php`
- gom shared logic web/api vào service

-----------------------------------
## 13. Đề xuất test cần có trước/sau refactor

### P0 tests
- tenant isolation tests
- inpack/package sync tests
- payment success/failure/rollback tests
- social login binding tests

### P1 tests
- role access tests
- warehouse scope tests
- line pricing tests
- webhook idempotency tests

### P2 tests
- report bucket consistency tests
- dashboard count consistency tests

-----------------------------------
## 14. Kết luận

Nếu chỉ làm 3 việc refactor đầu tiên, mình khuyên:
1. dọn `dump/die`
2. enum hóa status nóng nhất
3. chuẩn bị normalize `inpack.pack_ids`

Ba việc này cho tỷ lệ giảm rủi ro production cao nhất trên công sức bỏ ra.