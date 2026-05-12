# 16. Store backend map

File này là bản đồ riêng cho module `store`.

Thực tế đây là module lớn nhất về vận hành business.
Nó không chỉ là “admin panel”, mà là backoffice cho:
- warehouse ops
- package/inpack ops
- staff/RBAC
- settings/platform config của tenant
- statistics
- apps/marketing extensions

## 1. Base layer của store backend

### `store/controller/Controller.php`
Vai trò:
- load session `yoshop_store`
- parse route info
- check login
- check privilege qua `store/service/Auth.php`
- inject layout/menu/store info

Bất kỳ bug nào kiểu:
- vào trang bị đá ra login
- có session mà vẫn 403
- menu sai
- staff thấy sai layout/menu
=> mở file này đầu tiên

### `store/service/Auth.php`
Vai trò:
- URL-level RBAC checker
- whitelist actions
- role -> access URL resolution
- super user bypass

### `store/service/Menus.php`
Vai trò:
- build menu theo route/group/permission

-----------------------------------
## 2. Nhóm quản trị nội bộ tenant

### `store/controller/store/User.php`
Use case:
- list store users
- add/edit/delete store user
- renew profile/account

### `store/controller/store/Role.php`
Use case:
- role management
- role permission assignment

### `store/controller/store/Access.php`
Use case:
- access catalog / permission tree

Đây là nhóm controller phải đọc khi:
- staff không có quyền đúng
- role map lỗi
- user bị giới hạn sai phạm vi

-----------------------------------
## 3. Nhóm package / warehouse vận hành lõi

### `store/controller/package/Index.php`
Đây là controller vận hành kho quan trọng nhất.

Nhóm use case lớn:
- package list theo nhiều trạng thái
- import package
- claim package / unclaimed package
- change user / change shelf
- set error/problem package
- inbound package
- inpack package
- scan in / scan out
- bulk operation
- comment / log / delete

Nếu bạn cần hiểu warehouse ops ở backend, đây là file số 1 phải mở.

### `store/controller/package/Newpack.php`
Use case:
- tạo package mới từ backend
- save package nhanh
- hỗ trợ nghiệp vụ nhập tay / tạo package mới trong kho

### `store/controller/package/Report.php`
Use case:
- report package
- depot / update status
- item level handling
- shelf-related actions phụ

### `store/model/Package.php`
Business model cho store-side package ops.
Đọc file này khi debug:
- warehouse logic
- package update/side effect
- message/notice after package actions

-----------------------------------
## 4. Nhóm inpack / shipment / batch

### `store/model/Inpack.php`
Đây là model rất quan trọng của outbound logistics trên store side.

Dùng để hiểu:
- update inpack state
- shipment related transitions
- append/remove package from inpack
- pay/shipping/warehouse side-effects

### `store/controller/Batch.php`
Use case:
- batch management
- add package to batch
- shipment by batch
- batch status change
- compare batch vs inpack / package

### `store/controller/SendOrder.php`
Use case:
- send order / pre-send order handling
- package grouping cho shipment

Khi warehouse dùng lô hoặc shipment aggregate phụ trợ, nhóm này là vùng phải đọc.

-----------------------------------
## 5. Nhóm commerce order backend

### `store/controller/Order.php`
Use case:
- delivery list
- receipt list
- pay list
- complete list
- cancel list
- all list
- detail
- delivery
- update price

### `store/controller/order/Operate.php`
Use case:
- export
- batch delivery
- delivery template
- confirm cancel
- extract

### `store/controller/order/Refund.php`
Use case:
- refund queue
- refund detail
- refund audit
- refund receipt

### `store/model/Order.php`
Đọc để hiểu:
- order filtering by dataType
- delivery transitions
- complete/cancel semantics
- commerce order status machine

-----------------------------------
## 6. Nhóm user/customer operation backend

### `store/controller/User.php`
Use case:
- customer/user list
- customer-side operational tools
- balance/grade/supportive operations tùy flow

### `store/controller/user/*`
Các sub-controller như:
- `user/Order.php`
- `user/Wallet.php`
- `user/Dealer.php`
- `user/Coupon.php`

Dùng để quản customer lifecycle từ backend.

-----------------------------------
## 7. Nhóm setting - cấu hình tenant

### `store/controller/Setting.php`
Đây là “settings hub” của tenant.

Use case lớn:
- store basic config
- batch config
- user client config
- ai identify
- keeper
- admin style
- trade
- paytype
- sms
- notice
- email
- tpl msg
- storage config
- service config
- printer

Nếu bạn nghi ngờ behavior tenant-specific khác nhau giữa tenant A/B, phải xem setting group này.

### Sub-settings controllers rất quan trọng

#### `store/controller/setting/Line.php`
- line list
- add/edit/delete/copy line
- change line status
- batch status change

#### `store/controller/setting/Express.php`
- express company config

#### `store/controller/setting/Ditch.php`
- carrier/ditch/channel config
- number import

#### `store/controller/setting/Package.php`
- package service/configuration related

#### `store/controller/setting/PaymentFlow.php`
- payment flow configuration

#### `store/controller/setting/Track.php`
- tracking configuration

#### `store/controller/setting/Bank.php`
- bank config

#### `store/controller/setting/Certificate.php`
- certificate management/audit

#### `store/controller/setting/Sms.php`
- SMS config

#### `store/controller/setting/Barcode.php`
- barcode import/management

-----------------------------------
## 8. Nhóm market / apps / extension

### `store/controller/market/Coupon.php`
- coupon CRUD
- receive records
- coupon setting

### `store/controller/market/recharge/Plan.php`
- recharge plan CRUD

### `store/controller/market/Blindbox.php`
- blindbox CRUD
- blindbox wall
- blindbox setting

### `store/controller/market/Push.php`
- push/SMS/email sending tools

### `store/controller/apps/dealer/*`
- dealer/referral backend management

### `store/controller/apps/sharing/*`
- sharing/group logic backend management

### `store/controller/apps/sharp/*` nếu có
- sharp/social extension management

-----------------------------------
## 9. Nhóm statistics / tools / maintenance

### `store/controller/statistics/Data.php`
Use case:
- dashboard/survey
- category/country/ditch/inpackorder report
- data screen

### `store/controller/Tools.php`
Use case:
- tools index
- seachfree
- updatelog
- apipost
- guide

### `store/controller/SfCacheAdmin.php`
Use case:
- cache admin cho SF integration
- dashboard/clear/warmup

### `store/controller/Upload.php`
Use case:
- backend image upload

-----------------------------------
## 10. Nhóm view đáng chú ý

Store có rất nhiều view, nhưng nếu đọc theo business thì ưu tiên:
- `store/view/package/index/*`
- `store/view/package/report/*`
- `store/view/batch/*`
- `store/view/store/user/*`
- `store/view/store/role/*`
- `store/view/setting/line/*`
- `store/view/order/*`
- `store/view/tr_order/*`
- `store/view/statistics/data/*`

Các view này giúp bạn hiểu user workflow thực tế ở backend nhanh hơn là chỉ đọc controller.

-----------------------------------
## 11. Reading order tốt nhất cho store module

### Nếu mục tiêu là warehouse ops
1. `store/controller/Controller.php`
2. `store/service/Auth.php`
3. `store/controller/package/Index.php`
4. `store/controller/package/Newpack.php`
5. `store/model/Package.php`
6. `store/model/Inpack.php`
7. `store/controller/Batch.php`

### Nếu mục tiêu là tenant configuration
1. `store/controller/Setting.php`
2. `store/controller/setting/Line.php`
3. `store/controller/setting/Ditch.php`
4. `store/controller/setting/Express.php`
5. `common/model/Setting.php`

### Nếu mục tiêu là staff/permission
1. `store/controller/store/User.php`
2. `store/controller/store/Role.php`
3. `store/controller/store/Access.php`
4. `store/service/Auth.php`

### Nếu mục tiêu là commerce backend
1. `store/controller/Order.php`
2. `store/controller/order/Operate.php`
3. `store/controller/order/Refund.php`
4. `store/model/Order.php`

-----------------------------------
## 12. Những vùng store backend nguy hiểm nhất

1. `package/Index.php`
- controller rất lớn, dễ regression

2. `model/Inpack.php`
- outbound logic phức tạp

3. `service/Auth.php`
- chạm permission toàn backend

4. `controller/Setting.php` + setting subcontrollers
- thay config có thể ảnh hưởng nhiều tenant behavior

5. batch/send-order flows
- dễ lệch package/inpack state

-----------------------------------
## 13. Kết luận

Nếu API là “entrypoint cho client”, thì `store` là “entrypoint cho operation team”.

Muốn hiểu dự án vận hành ngoài đời thực ra sao, hãy đọc `store`.
Muốn hiểu kho làm việc ra sao, hãy bắt đầu từ `store/controller/package/Index.php`.