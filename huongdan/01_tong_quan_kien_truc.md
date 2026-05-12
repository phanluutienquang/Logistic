# 01. Tổng quan kiến trúc dự án

## 1. Dự án này là gì?

Đây là một nền tảng SaaS đa tenant phục vụ đồng thời 3 lớp bài toán:

1. Logistics / forwarding / gom hàng quốc tế
- người dùng tạo hoặc khai báo package
- kho nhận hàng
- hàng được nhận diện, nhập kho, lên kệ, chờ gom
- người dùng chọn nhiều package để tạo đơn gom hàng
- hệ thống tính cước theo line
- thanh toán
- kho đóng gói, xuất vận chuyển, theo dõi trạng thái

2. E-commerce / social commerce
- có goods, cart, order, category, coupon, comment
- có bargain, sharing/group-buy, sharp/seckill, blindbox
- có order checkout giống mô hình shop truyền thống

3. SaaS multi-tenant
- nhiều tenant cùng dùng chung codebase và chung schema DB
- tenant được phân tách chủ yếu bằng `wxapp_id`
- mỗi tenant có store setting, line, user, order, package, warehouse, role riêng

## 2. Framework và tổ chức kỹ thuật

Stack chính đọc từ `source/composer.json`:
- PHP >= 5.4
- ThinkPHP 5.0
- MySQL
- PHPUnit có mặt nhưng repo hiện không cho thấy test coverage lớn
- Tích hợp cloud storage / QR / PDF / barcode / Swagger

Các module chính trong `source/application/`:
- `admin`: siêu quản trị nền tảng
- `store`: backoffice của tenant / merchant / warehouse operation
- `api`: API cho app/mobile/miniapp/client integration
- `web`: web portal cho end-user
- `task`: batch job / scheduled behavior
- `common`: model/service/library dùng chung

## 3. Kiến trúc vận hành cấp cao

Mô hình thực tế:
- `admin` tạo tenant mới (`wxapp` mới)
- tenant có 1 super store user mặc định
- store user đăng nhập backend `store`
- end-user đăng nhập qua `api` hoặc `web`
- mọi dữ liệu business chính bị scope bởi `wxapp_id`
- một số user nội bộ còn bị scope tiếp bởi `shop_id`, `line_id`, `country_id`, `clerk_id`

## 4. Các entry point quan trọng

### 4.1 Cấu hình app
- `source/application/config.php`
  - `default_module = store`
  - app trả JSON mặc định
  - bật multi-module

### 4.2 Route
- `source/application/route.php`
  - route khá mỏng
  - phần lớn dự án dựa theo routing convention của ThinkPHP hơn là route tùy biến phức tạp

### 4.3 DB config
- `source/application/database.php`
  - prefix bảng: `yoshop_`
  - DB hiện cấu hình local dev

## 5. Nhìn hệ thống theo “vai trò” thay vì theo “module code”

Đây là cách hiểu nhanh nhất:

1. Super Admin
- module: `admin`
- quản tenant
- tạo/xóa/recycle tenant
- jump vào store tenant

2. Store Admin / Warehouse Backoffice
- module: `store`
- quản users, roles, warehouses, lines, packages, inpacks, vận hành kho
- là phần business nặng nhất

3. End User
- module: `web` + `api`
- khai báo kiện hàng, xem package, tạo inpack, thanh toán, tracking, address, wallet

4. Scheduler / Background jobs
- module: `task`
- auto close order, coupon expiry, inpack/batch-related background behavior

## 6. Điều quan trọng nhất để không hiểu sai dự án

### 6.1 `package` != `inpack`
- `package` = một kiện/parcel đơn lẻ, thường là đầu vào kho
- `inpack` = đơn logistics được tạo từ một hoặc nhiều package để gom gửi quốc tế

### 6.2 `order` có 2 họ lớn
- họ e-commerce: `yoshop_order`, `yoshop_order_goods`, `yoshop_cart`
- họ logistics: `yoshop_package`, `yoshop_inpack`, `yoshop_logistics`

### 6.3 `store` không chỉ là “merchant storefront”
Trong repo này, `store` thực chất là backoffice vận hành của tenant, rất gần với:
- warehouse ops
- logistics ops
- backoffice staff management
- role/permission center

## 7. Mối quan hệ khái niệm cấp cao

1. Tenant (`wxapp`)
-> có settings, line, warehouse, store_user, end_user, package, inpack, goods

2. End User
-> có address, balance, package, inpack, ecommerce order, buyer order

3. Warehouse / shop
-> tiếp nhận package, liên quan inbound/outbound, có clerk và phạm vi vận hành

4. Line
-> quyết định pricing, rule, weight-volume formula, country support, service support

5. Package
-> đi qua inbound -> shelf -> wait pack -> pay -> shipped -> received

6. Inpack
-> gom nhiều package, tính tiền, thanh toán, phát vận đơn quốc tế

## 8. Các file nên mở đầu tiên nếu đọc code

Đọc từ nền tảng kỹ thuật:
- `source/application/common/model/BaseModel.php`
- `source/application/common/model/Wxapp.php`
- `source/application/api/controller/Controller.php`
- `source/application/web/controller/Controller.php`
- `source/application/store/controller/Controller.php`
- `source/application/admin/controller/Controller.php`

Đọc từ business cốt lõi:
- `source/application/web/controller/Package.php`
- `source/application/web/model/Package.php`
- `source/application/web/model/Inpack.php`
- `source/application/web/model/Line.php`
- `source/application/store/service/Auth.php`
- `source/application/api/service/passport/Login.php`

Đọc từ SaaS/tenant:
- `source/application/admin/controller/Store.php`
- `source/application/admin/model/Wxapp.php`
- `source/application/admin/model/store/User.php`

## 9. Kết luận ngắn

Nếu tóm lại bằng 1 câu:

Đây là một nền tảng SaaS logistics xuyên biên giới, lấy `package -> inpack -> shipping` làm xương sống nghiệp vụ, nhưng đồng thời nhúng một hệ e-commerce/social-commerce hoàn chỉnh cho từng tenant.
