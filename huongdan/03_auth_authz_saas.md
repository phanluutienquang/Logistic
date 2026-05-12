# 03. Auth, AuthZ, SaaS và multi-tenant

## 1. Tổng quan

Dự án này có 4 lớp identity khác nhau:

1. Super admin session
- module `admin`
- session `yoshop_admin`

2. Store/backoffice user session
- module `store`
- session `yoshop_store`

3. Web end-user session
- module `web`
- session `yoshop_user`

4. API token-based user auth
- module `api`
- token gửi từ client
- token map vào cache rồi lookup user

=> Đây không phải một auth system thống nhất kiểu JWT-centric. Nó là auth đa kiểu theo ngữ cảnh module.

-----------------------------------
## 2. Authentication theo từng module

### 2.1 Admin auth
File chính:
- `source/application/admin/controller/Controller.php`
- `source/application/admin/controller/Passport.php`
- `source/application/admin/model/admin/User.php`

Cách chạy:
- login bằng `user_name + password`
- lưu session `yoshop_admin`
- không gắn `wxapp_id`
- admin có thể vào danh sách tenant và “enter” store tenant

Flow:
1. POST login admin
2. verify bảng `yoshop_admin_user`
3. set session `yoshop_admin`
4. dùng `admin/controller/Store::enter($wxapp_id)` để nhảy vào tenant backend

### 2.2 Store auth
File chính:
- `source/application/store/controller/Controller.php`
- `source/application/store/controller/Passport.php`
- `source/application/store/model/store/User.php`
- `source/application/store/service/Auth.php`

Cách chạy:
- login bằng `user_name + password`
- lookup `yoshop_store_user`
- bắt buộc user phải có `wxapp`
- check tenant chưa recycle / chưa expired
- set session `yoshop_store`

Business rules đáng chú ý:
- tenant hết hạn (`wxapp.end_time < now`) thì không cho login
- có `is_super` cho store user
- user nội bộ có thể bị scope theo shop/line/country/clerk

### 2.3 Web end-user auth
File chính:
- `source/application/web/controller/Controller.php`
- `source/application/web/controller/Passport.php`
- `source/application/web/service/passport/Login.php`

Cách chạy:
- session-based cho portal web
- login/register qua form, kèm `wxappid`
- session `yoshop_user`

Lưu ý:
- `web/controller/Controller.php` lấy tenant bằng query `wxappid`
- có whitelist route public
- các route ngoài whitelist sẽ redirect login nếu chưa có session

### 2.4 API auth
File chính:
- `source/application/api/controller/Controller.php`
- `source/application/api/controller/Passport.php`
- `source/application/api/service/passport/Login.php`
- `source/application/web/model/User.php::getUser()` phản ánh pattern token-cache tương tự

Cách chạy:
- client truyền `wxapp_id` và `token`
- `getUser()` trong API controller sẽ lấy token từ request
- token được map vào cache, từ đó tìm `openid`/`open_id` rồi query user

Điểm quan trọng:
- đây không phải JWT stateless
- token phụ thuộc cache/session state
- nếu cache mất, token mất hiệu lực

-----------------------------------
## 3. Multi-platform authentication

Từ `api/controller/Passport.php` và `api/service/passport/Login.php`, hệ thống hỗ trợ nhiều kiểu login:

### 3.1 Mobile/Email/password
- register bằng mobile/email
- reset password
- login thường
- tạo `open_id` bằng mobile/email trong một số luồng

### 3.2 WeChat Mini App / Official Account
- `loginMpWx`
- `loginMpWxMobile`
- `loginWxOfficial`
- decrypt data / openid / session flow

### 3.3 Zalo Mini App
- `loginMpZalo`
- dùng `zalo_user_id`
- nếu chưa có user thì auto-register
- `open_id` được dùng để định danh user Zalo

### 3.4 LINE
- `loginMpLine`
- lookup bảng `yoshop_line_user`
- nếu chưa có thì tạo system user + binding LINE user
- user hệ thống dùng `open_id = 'LINE_' + line_user_id` để tránh collision

### 3.5 Clerk / warehouse staff login
- `loginClerk` / `loginMpWxMobileClerk`
- dùng tài khoản staff/kho
- đây là auth riêng cho nhân sự vận hành

=> Business implication: dự án được thiết kế để phục vụ đa nền tảng client trong cùng một tenant.

-----------------------------------
## 4. Authorization: role-based + data scope

## 4.1 RBAC ở store module
File chính:
- `source/application/store/service/Auth.php`

Các bảng:
- `yoshop_store_access`
- `yoshop_store_role`
- `yoshop_store_role_access`
- `yoshop_store_user_role`

Cách hoạt động:
1. Controller store gọi `checkPrivilege()`
2. `Auth::checkPrivilege($routeUri)` được gọi
3. nếu `is_super` => bypass
4. nếu route nằm trong whitelist => bypass
5. ngược lại lấy role của user
6. lấy access ids theo role
7. map ra access urls
8. route hiện tại phải nằm trong danh sách đó

=> RBAC thực tế dựa trên URL-level authorization.

## 4.2 Data scope ở BaseModel
File quan trọng nhất của multi-tenant + scope:
- `source/application/common/model/BaseModel.php`

Những gì BaseModel đang làm:
- bind `wxapp_id` theo module hiện tại
- tự động áp global query scope `table.wxapp_id = current_wxapp_id`
- với store user còn có scope thêm theo:
  - `shop_id` cho `yoshop_inpack`, `yoshop_package`
  - `clerk_id` cho `yoshop_user.service_id`
- code còn để lại dấu hiệu chuẩn bị scope theo `line_id`, `country_id` nhưng đang comment

Đây là điểm cực kỳ quan trọng:
- authz của dự án không chỉ nằm ở role
- mà còn nằm ở query scope data

### Hiểu theo business
Một nhân viên có quyền vào module package chưa chắc thấy toàn bộ package.
Họ còn bị giới hạn bởi warehouse hoặc scope khác được gắn trong `store_user`.

-----------------------------------
## 5. SaaS / tenant isolation

## 5.1 Tenant identifier
Tenant key chính là `wxapp_id`.

Nó xuất hiện xuyên suốt ở:
- user
- goods
- package
- inpack
- setting
- role/user store
- order
- line
- warehouse
- payment config

## 5.2 Lifecycle tenant
Đọc từ `admin/controller/Store.php` + `admin/model/Wxapp.php`:

Tạo tenant:
1. tạo `wxapp`
2. tạo setting mặc định
3. tạo super store user
4. tạo page/help mặc định

Vào tenant:
- admin chọn tenant và `enter`
- hệ thống login store super admin của tenant đó

Ngưng tenant:
- recycle
- move out recycle
- soft delete
- API/store login đều check tenant validity

## 5.3 Tenant expiration
`api/controller/Controller.php`:
- check `wxapp.end_time`
- hết hạn thì chặn

`store/model/store/User.php`:
- login store cũng check `end_time`

=> SaaS commercial control nằm ngay ở tầng auth.

-----------------------------------
## 6. Những session/state chính

### Admin session
- `yoshop_admin`

### Store session
- `yoshop_store`
  - thường chứa:
    - user info
    - wxapp info
    - is_login

### Web user session
- `yoshop_user`

### API token state
- cache token -> openid/open_id -> user

-----------------------------------
## 7. Các bảng auth/authz bạn phải thuộc

### Super admin
- `yoshop_admin_user`

### Store RBAC
- `yoshop_store_user`
- `yoshop_store_role`
- `yoshop_store_access`
- `yoshop_store_role_access`
- `yoshop_store_user_role`
- `yoshop_store_user_binding`

### End-user identity
- `yoshop_user`
- `yoshop_user_address`
- `yoshop_user_binding`
- `yoshop_line_user`

-----------------------------------
## 8. Những điểm cần cẩn thận khi sửa auth

1. Không sửa auth mà quên `wxapp_id`
- rất dễ làm lộ dữ liệu cross-tenant

2. Không nhìn mỗi role table
- vì data scope còn nằm ở `BaseModel`

3. Login platform khác nhau có semantics khác nhau
- mobile/email
- wechat
- zalo
- line
- clerk

4. API token không phải JWT
- đổi logic cache/token phải test toàn bộ login flow

5. `wxapp_id` ở web và api không cùng parameter name
- API hay dùng `wxapp_id`
- Web dùng `wxappid`

-----------------------------------
## 9. Kết luận ngắn

Auth của hệ thống này gồm 3 lớp chồng lên nhau:
- authentication theo module và theo platform
- authorization bằng RBAC URL-level
- data authorization bằng tenant scope + warehouse/staff scope

Nếu chỉ nhìn login form hoặc role table, bạn sẽ hiểu thiếu ít nhất một nửa hệ thống.
