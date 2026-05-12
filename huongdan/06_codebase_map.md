# 06. Codebase map

File này mô tả codebase theo module, giúp bạn biết mở file nào trước và vì sao.

## 1. Số lượng PHP file theo module

Tổng hợp từ codebase hiện tại:

- `admin`: 45 file PHP
- `api`: 263 file PHP
- `common`: 393 file PHP
- `store`: 673 file PHP
- `task`: 56 file PHP
- `web`: 175 file PHP

Chi tiết top-level:

### admin
- controller: 11
- model: 9
- view: 23

### api
- controller: 76
- model: 141
- service: 39
- validate: 4

### common
- model: 170
- service: 66
- library: 129
- enum: 24

### store
- controller: 139
- model: 152
- service: 17
- view: 362

### task
- behavior: 12
- model: 42
- service: 1

### web
- controller: 35
- model: 56
- service: 27
- view: 49

=> `store` + `common` + `api` là ba vùng lớn nhất cần đọc.

-----------------------------------
## 2. Bản đồ module theo trách nhiệm

## 2.1 `common/`
Đây là nền tảng domain dùng chung.

Bạn nên coi `common` là:
- core model layer
- shared service layer
- utility/library/integration layer

File bắt buộc đọc:
- `source/application/common/model/BaseModel.php`
- `source/application/common/model/Wxapp.php`
- `source/application/common/model/User.php`
- `source/application/common/model/Setting.php`

Vì sao?
- `BaseModel` giải thích tenant scope và data scope
- `Wxapp` giải thích tenant config lookup
- `User` giải thích user aggregate và balance behavior
- `Setting` giải thích key-value config toàn hệ thống

## 2.2 `admin/`
Đây là platform control plane.

Mục tiêu:
- tạo tenant
- quản tenant
- impersonate vào tenant backend

File chính:
- `admin/controller/Controller.php`
- `admin/controller/Passport.php`
- `admin/controller/Store.php`
- `admin/model/Wxapp.php`
- `admin/model/store/User.php`

## 2.3 `store/`
Đây là backoffice business nặng nhất.

Nó bao phủ:
- role/permission
- warehouse operations
- package operations
- order operations
- line/setting/config
- statistics
- clerk/shop flows

File nền tảng:
- `store/controller/Controller.php`
- `store/service/Auth.php`
- `store/service/Menus.php`

Các cụm đáng đọc:

### package operations
- `store/controller/package/Index.php`
- `store/controller/package/Newpack.php`
- `store/controller/package/Report.php`

### order ops
- `store/controller/order/Operate.php`
- `store/controller/order/Refund.php`

### settings / line / payment / navigation
- `store/view/setting/*`
- các model/controller line trong store

## 2.4 `api/`
Đây là machine-facing / app-facing entrypoint.

Chức năng lớn:
- login/register
- user API
- cart/order checkout
- package/inpack API
- payment callback
- external miniapp/web client consumption

File nên đọc trước:
- `api/controller/Controller.php`
- `api/controller/Passport.php`
- `api/controller/Order.php`
- `api/controller/Zalopay.php`
- `api/service/passport/Login.php`

## 2.5 `web/`
Đây là portal cho người dùng cuối.

Nó thể hiện rõ business flow hơn API ở nhiều nơi vì có cả page/controller truyền thống.

File nên đọc trước:
- `web/controller/Controller.php`
- `web/controller/Passport.php`
- `web/controller/Package.php`
- `web/controller/Order.php`
- `web/model/Package.php`
- `web/model/Inpack.php`
- `web/model/BuyerOrder.php`

## 2.6 `task/`
Đây là background behavior.

File nên đọc:
- `task/service/Order.php`
- `task/behavior/Order.php`
- `task/behavior/Inpack.php`
- `task/behavior/Batch.php`

-----------------------------------
## 3. Các file nền tảng mà bạn phải hiểu

## 3.1 `common/model/BaseModel.php`
Tại sao cực quan trọng:
- đây là nơi tenant scope được inject
- đây là nơi store data scope được inject
- đây là câu trả lời cho câu hỏi: “vì sao query cùng model nhưng mỗi user thấy data khác nhau?”

Những gì file này làm:
- detect module hiện tại (`admin/api/store/web/task`)
- bind `wxapp_id`
- global scope `table.wxapp_id = current_tenant`
- scope thêm `shop_id` / `clerk_id` cho một số bảng

## 3.2 `api/controller/Controller.php`
Tại sao quan trọng:
- mọi API request đi qua đây
- lấy `wxapp_id`
- check tenant validity
- resolve current user qua token

## 3.3 `store/controller/Controller.php`
Tại sao quan trọng:
- load session store
- check login
- check privilege
- load menu/layout
- expose current tenant context cho backend

## 3.4 `web/controller/Controller.php`
Tại sao quan trọng:
- load tenant từ `wxappid`
- check login cho web user
- attach portal menu/layout

-----------------------------------
## 4. Nơi business logistics sống rõ nhất

Nếu mục tiêu của bạn là hiểu forwarding/logistics, hãy tập trung các file sau:

1. `web/controller/Package.php`
- flow package list
- pre-alert
- create order draft
- create inpack
- pay inpack
- details tracking

2. `web/model/Package.php`
- package aggregate behavior
- package query scopes
- relations
- status text mapping

3. `web/model/Inpack.php`
- inpack aggregate behavior
- list/query/status mapping

4. `web/model/Line.php`
- line lookup
- route recommendation

5. `store/controller/package/Index.php`
- kho nhìn và xử lý package ở quy mô vận hành thật
- bulk import, claim, inbound, scan, shelf, assign user

-----------------------------------
## 5. Nơi auth/authz sống rõ nhất

1. `admin/model/admin/User.php`
- admin login

2. `store/model/store/User.php`
- store login
- tenant expiry check
- create/edit internal user

3. `store/service/Auth.php`
- privilege by URL
- role -> access resolution

4. `api/service/passport/Login.php`
- multi-platform auth logic

-----------------------------------
## 6. Nơi SaaS logic sống rõ nhất

1. `admin/controller/Store.php`
- create/edit/delete/recycle tenant
- enter tenant

2. `admin/model/Wxapp.php`
- provision defaults khi tạo tenant

3. `common/model/Wxapp.php`
- cached tenant config

4. `common/model/BaseModel.php`
- tenant data isolation

-----------------------------------
## 7. Nơi e-commerce logic sống rõ nhất

1. `api/controller/Order.php`
2. `web/controller/Order.php`
3. `service/order/Checkout.php` ở api/web
4. `common/model/Order*`
5. `Goods/Cart/Coupon/Comment` models

-----------------------------------
## 8. Nơi payment/integration sống rõ nhất

- `api/controller/Zalopay.php`
- `api/controller/Recharge.php`
- `common/enum/order/PayType.php`
- `common/model/Setting.php`
- `yoshop_line_app` trong SQL cho LINE app / LINE pay related config

-----------------------------------
## 9. Cách đọc code để ít lạc nhất

### Hướng đọc khuyến nghị A: hiểu business logistics
1. `common/model/BaseModel.php`
2. `web/controller/Controller.php`
3. `web/model/Package.php`
4. `web/model/Inpack.php`
5. `web/controller/Package.php`
6. `store/controller/package/Index.php`
7. `xinsuju.sql` các bảng package/inpack/line/shelf

### Hướng đọc khuyến nghị B: hiểu auth/rbac/saas
1. `admin/controller/Controller.php`
2. `admin/controller/Store.php`
3. `admin/model/Wxapp.php`
4. `store/controller/Controller.php`
5. `store/model/store/User.php`
6. `store/service/Auth.php`
7. `common/model/BaseModel.php`
8. SQL của `store_user`, `store_role`, `store_access`, `wxapp`

### Hướng đọc khuyến nghị C: hiểu commerce
1. `api/controller/Order.php`
2. `web/controller/Order.php`
3. `Checkout service`
4. `common/order/goods/cart/coupon models`
5. SQL của `yoshop_order*`, `yoshop_goods*`, `yoshop_cart`

-----------------------------------
## 10. Các code smell / điểm nên cảnh giác khi bảo trì

1. Nhiều domain nằm chung repo
- logistics + ecommerce + social commerce + SaaS
- rất dễ sửa lan

2. Có nhiều status map bằng hardcoded number
- cần đối chiếu code + SQL

3. Có quan hệ denormalized bằng csv/text
- ví dụ `inpack.pack_ids`
- query/reporting/debug khó hơn bình thường

4. Auth đa kiểu
- session + cache token + multi-platform login
- refactor cẩn thận

5. Có khá nhiều legacy surface area
- tên file/controller/model không phải lúc nào cũng nhất quán 100%

-----------------------------------
## 11. Kết luận ngắn

Nếu chỉ nhớ 1 điều về codebase:

`common` giải thích “hệ thống vận hành như thế nào”,
`store` giải thích “nhân viên vận hành làm gì”,
`web/api` giải thích “người dùng cuối làm gì”.
