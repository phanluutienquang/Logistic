# 07. Checklist để đọc hiểu dự án chính xác và sâu

Đây là checklist onboarding mà bạn có thể dùng để tự học hoặc giao cho dev mới.

## Phần A - Hiểu đúng bản chất dự án

- [ ] Biết dự án này là SaaS multi-tenant
- [ ] Biết tenant key là `wxapp_id`
- [ ] Biết repo có 2 xương sống song song:
  - [ ] logistics/forwarding
  - [ ] e-commerce/social commerce
- [ ] Biết `package` và `inpack` là hai aggregate khác nhau
- [ ] Biết `yoshop_order` không phải `yoshop_inpack`

## Phần B - Hiểu tenant isolation

- [ ] Đọc `common/model/BaseModel.php`
- [ ] Hiểu `wxapp_id` được bind theo module
- [ ] Hiểu global scope tự động gắn `wxapp_id`
- [ ] Hiểu store user còn có `shop_id/line_id/country_id/clerk_id`
- [ ] Hiểu tenant hết hạn sẽ bị chặn login/api

## Phần C - Hiểu auth

- [ ] Đọc `admin/controller/Passport.php`
- [ ] Đọc `store/controller/Passport.php`
- [ ] Đọc `web/controller/Passport.php`
- [ ] Đọc `api/controller/Passport.php`
- [ ] Biết session nào dùng cho module nào
- [ ] Biết API auth dùng token + cache, không phải JWT thuần
- [ ] Biết các login platform:
  - [ ] mobile/email
  - [ ] WeChat
  - [ ] Zalo
  - [ ] LINE
  - [ ] clerk/staff

## Phần D - Hiểu authz/RBAC

- [ ] Đọc `store/service/Auth.php`
- [ ] Hiểu URL-level permission
- [ ] Hiểu `is_super` bypass permission
- [ ] Hiểu role-access mapping tables
- [ ] Hiểu data-scope nằm ngoài RBAC, trong BaseModel/query scope

## Phần E - Hiểu logistics domain

- [ ] Đọc schema `yoshop_package`
- [ ] Đọc schema `yoshop_inpack`
- [ ] Đọc schema `yoshop_line`
- [ ] Đọc schema `yoshop_shelf*`
- [ ] Đọc `web/model/Package.php`
- [ ] Đọc `web/model/Inpack.php`
- [ ] Đọc `web/model/Line.php`
- [ ] Đọc `web/controller/Package.php::inpack()`
- [ ] Đọc `web/controller/Package.php::doPay()`

## Phần F - Hiểu package lifecycle

- [ ] package được tạo từ source nào
- [ ] package inbound vào kho ra sao
- [ ] package được claim thế nào
- [ ] package lên kệ thế nào
- [ ] package được chọn vào inpack thế nào
- [ ] package status đổi ra sao trước và sau payment

## Phần G - Hiểu inpack lifecycle

- [ ] inpack được tạo từ nhiều package
- [ ] line ảnh hưởng pricing thế nào
- [ ] address được xác định thế nào
- [ ] `pack_ids` đang lưu kiểu csv/text
- [ ] inpack payment ảnh hưởng package status thế nào
- [ ] inpack shipping/tracking lưu ở đâu

## Phần H - Hiểu e-commerce domain

- [ ] Đọc `api/controller/Order.php`
- [ ] Đọc `web/controller/Order.php`
- [ ] Hiểu buy-now flow
- [ ] Hiểu cart checkout flow
- [ ] Biết bảng `yoshop_order*` là commerce order

## Phần I - Hiểu buyer/mua hộ

- [ ] Đọc `web/model/BuyerOrder.php`
- [ ] Biết URL supported platform: Taobao/Tmall/JD/1688
- [ ] Hiểu amount được trừ trực tiếp từ balance

## Phần J - Hiểu platform provisioning

- [ ] Đọc `admin/controller/Store.php`
- [ ] Đọc `admin/model/Wxapp.php`
- [ ] Biết tạo tenant sẽ sinh default setting/help/page/user
- [ ] Biết admin có thể enter store tenant

## Phần K - Hiểu nơi dễ gây bug nhất

- [ ] status hardcode
- [ ] route/url permission hardcode
- [ ] csv fields như `pack_ids`
- [ ] auth đa kiểu
- [ ] `wxapp_id` param name khác nhau giữa web và api
- [ ] logic domain rộng và trộn nhiều module

## Phần L - Thứ tự đọc tối ưu trong 1 ngày

### Buổi 1
- [ ] 01_tong_quan_kien_truc.md
- [ ] 02_domain_va_business_logic.md
- [ ] 03_auth_authz_saas.md

### Buổi 2
- [ ] `BaseModel.php`
- [ ] `admin/controller/Store.php`
- [ ] `store/service/Auth.php`
- [ ] `api/service/passport/Login.php`

### Buổi 3
- [ ] `web/controller/Package.php`
- [ ] `web/model/Package.php`
- [ ] `web/model/Inpack.php`
- [ ] SQL package/inpack/line

### Buổi 4
- [ ] `api/controller/Order.php`
- [ ] `web/controller/Order.php`
- [ ] `web/model/BuyerOrder.php`
- [ ] recharge/payment callback related files

## Phần M - Câu hỏi tự kiểm tra

Nếu bạn trả lời được các câu sau thì đã hiểu khá sâu:

1. Tenant được tạo như thế nào?
2. Vì sao cùng một model mà mỗi store user thấy dữ liệu khác nhau?
3. `package` khác `inpack` ở đâu?
4. Payment của logistics khác payment của commerce ở đâu?
5. Vì sao `wxapp_id` là xương sống của SaaS isolation?
6. Vì sao `store/service/Auth.php` chưa đủ để giải thích toàn bộ quyền truy cập?
7. Khi user login bằng LINE/Zalo, record nào được tạo/cập nhật?
8. Nếu cần trace lỗi “user thấy package của tenant khác”, bạn sẽ kiểm tra file nào trước?
9. Nếu cần thay đổi công thức tính cước, bạn sẽ đi từ bảng/file nào?
10. Nếu cần onboarding dev mới, 5 file đầu tiên bạn đưa là gì?

## Phần N - 5 file đầu tiên nên đưa cho dev mới

1. `source/application/common/model/BaseModel.php`
2. `source/application/admin/controller/Store.php`
3. `source/application/store/service/Auth.php`
4. `source/application/web/controller/Package.php`
5. `xinsuju.sql` (nhóm bảng package/inpack/line/user/store_user)

## Kết luận

Muốn hiểu dự án này sâu và đúng, đừng đọc theo thứ tự thư mục.
Hãy đọc theo thứ tự domain:
- tenant
- auth
- package
- inpack
- line
- payment
- warehouse ops
- rồi mới đến commerce/social modules.
