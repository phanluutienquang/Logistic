# 15. API map

File này là bản đồ mức business của module `api`.

Mục tiêu:
- biết controller nào đại diện cho use case gì
- không cần đọc toàn bộ 76 API controller ngay từ đầu
- biết endpoint/business quan trọng nào cần mở trước khi debug

Lưu ý:
- repo dùng routing convention của ThinkPHP
- URL thực tế thường gần dạng `index.php?s=/api/<controller>/<action>` hoặc biến thể tương đương
- nhiều controller có rất nhiều action; file này tập trung vào business quan trọng nhất

-----------------------------------
## 1. API nền tảng / base layer

### `api/controller/Controller.php`
Vai trò:
- base controller cho phần lớn API
- lấy `wxapp_id`
- check tenant validity
- resolve current user qua token
- chuẩn hóa response JSON

Đây là file bắt buộc mở đầu tiên nếu debug API.

### `api/controller/Swagger.php`
Vai trò:
- sinh / expose tài liệu API swagger/openapi
- hữu ích để xem contract đang public ra client

-----------------------------------
## 2. API auth / identity

### `api/controller/Passport.php`
Đây là controller auth quan trọng nhất.

Use case chính:
- register
- login thường
- find/reset password
- WeChat login
- Zalo login
- LINE login
- clerk login

Action đáng chú ý:
- `register`
- `login`
- `loginMpWx`
- `loginMpWxMobile`
- `loginWxOfficial`
- `loginMpZalo`
- `loginMpLine`
- `loginClerk`

File service đi kèm phải mở cùng:
- `api/service/passport/Login.php`

### `api/service/passport/Login.php`
Đây là auth business engine.

Những gì file này xử lý:
- validate login/register data
- generate user_code
- auto-register social login user
- `setSession()` / token cache flow
- multi-provider login

Khi debug “không login được”, “token lỗi”, “Zalo/LINE user không bind đúng”, phải mở file này.

-----------------------------------
## 3. API user / hồ sơ / cá nhân

### `api/controller/User.php`
Vai trò thường gặp:
- hồ sơ user
- địa chỉ / thông tin cá nhân
- ví / cấp độ / user-related info

### `api/controller/Address.php`
Vai trò:
- CRUD địa chỉ user
- chọn default address
- hỗ trợ flow package/inpack/order

### `api/controller/Grade.php`
Vai trò:
- membership grade
- grade detail
- point/upgrade related endpoints

### `api/controller/Coupon.php`
Vai trò:
- coupon list
- coupon detail
- available coupon

-----------------------------------
## 4. API package / logistics core

### `api/controller/Package.php`
Đây là controller business lớn nhất và quan trọng nhất của domain logistics.

Nhóm chức năng chính:
1. báo hàng / report package
2. package list / package detail
3. verify / warehouse lifecycle
4. create inpack / fast pack / quick package-it
5. payment for inpack
6. tracking / logistics / signed in
7. line / country / storage lookup
8. service / coupon / pricing helper

Một số action cực quan trọng:
- `report`, `newreport`, `appreport`
- `postPack`, `fastPack`, `quickPackageItAll`
- `packageList`, `packageListPlus`, `packageForTaker`, `getTakePackage`
- `doPay`, `newdoPay`, `payType`
- `details`, `details_pack`, `packdetails`
- `addressUpdate`
- `logicist`, `logicistplus`, `getlogistics`
- `line`, `lineplus`, `lineCategoryList`, `lineForShop`
- `storage`, `country`, `express`

Nếu bạn đang debug logistics API, gần như chắc chắn sẽ đi qua controller này.

### `api/model/Package.php`
Đọc cùng với controller trên để hiểu:
- query/filter bucket
- count logic theo status
- relation package -> item/address/storage/inpack

### `api/model/Inpack.php`
Đọc cùng để hiểu:
- inpack list
- status map
- update shop / pay callback lookup
- relation inpack -> package qua `pack_ids`

-----------------------------------
## 5. API external ingest / webhook / automation

### `api/controller/ApiPost.php`
Vai trò:
- inbound webhook / external ingest / report package automation
- 17Track webhook
- report pack variants
- warehouse out package helper
- shelf/unit support logic

Use case rất thực chiến:
- hệ thống bên ngoài đẩy package vào
- tracking webhook cập nhật trạng thái
- OCR/auto report flows

Đây là controller nên mở khi nghi ngờ tích hợp hoặc automation ingest có vấn đề.

### `api/controller/Zalopay.php`
Vai trò:
- webhook callback từ ZaloPay
- xác định orderType/orderNo
- đánh dấu payment success/failure

Nếu “đã thanh toán Zalo nhưng hệ thống không cập nhật”, mở file này đầu tiên.

### `api/controller/Wechat.php`
Vai trò:
- callback/event từ WeChat
- thông tin user subscribe/unsubscribe hoặc account-side integration

-----------------------------------
## 6. API commerce / order checkout

### `api/controller/Order.php`
Vai trò:
- buy-now checkout
- cart checkout
- create commerce order
- build payment request

Action chính:
- `buyNow`
- `cart`

File đi kèm:
- `api/service/order/Checkout.php`
- `common/model/Order.php`

### `api/controller/Cart.php`
Vai trò:
- cart item operations
- add/remove/list cart

### `api/controller/Goods.php`
Vai trò:
- goods list
- goods detail
- goods poster/share assets

### `api/controller/Category.php`
Vai trò:
- commerce category tree
- parent category / category lookup

-----------------------------------
## 7. API article / CMS / content

### `api/controller/Article.php`
Vai trò:
- article list/detail
- about/help/note content
- note pages như insurance/report note

### `api/controller/Nav.php`
Vai trò:
- nav/menu data cho client

### `api/controller/AppUpdate.php`
Vai trò:
- app version check/update metadata

-----------------------------------
## 8. API recharge / payment support

### `api/controller/Recharge.php`
Vai trò:
- tạo recharge order
- chuẩn bị payment cho nạp tiền
- provider-specific recharge logic

### `common/enum/order/PayType.php`
Đọc kèm để hiểu payment channel map.

-----------------------------------
## 9. API sharp / sharing / bargain / social

### `api/controller/sharp/Order.php`
Vai trò:
- flow social/group-like order theo module sharp hiện tại
- có create, join, quit, apply pack, verify, address update
- liên quan package/inpack ở một số nhánh

### `api/controller/sharp/Index.php`
Vai trò:
- setting/apply/banner/sharing page cho module sharp

### `api/controller/bargain/Active.php`
### `api/controller/bargain/Task.php`
### `api/controller/bargain/Order.php`
Vai trò:
- bargain campaign, task, checkout

Lưu ý:
- social modules có thể chạm logistics ở downstream packing/shipping

-----------------------------------
## 10. API shop / location / live / upload

### `api/controller/Shop.php`
Vai trò:
- store/shop location list/detail
- location aware queries

### `api/controller/Upload.php`
Vai trò:
- upload image/avatar
- hỗ trợ user/package/business image flow

### `api/controller/live/Room.php`
Vai trò:
- live room listing (nếu tenant dùng)

-----------------------------------
## 11. Controller ưu tiên đọc theo mục tiêu

### Muốn debug login
1. `api/controller/Passport.php`
2. `api/service/passport/Login.php`
3. `api/controller/Controller.php`

### Muốn debug package/inpack
1. `api/controller/Package.php`
2. `api/model/Package.php`
3. `api/model/Inpack.php`
4. `common/model/BaseModel.php`

### Muốn debug webhook/integration
1. `api/controller/ApiPost.php`
2. `api/controller/Zalopay.php`
3. provider library trong `common/library/*`

### Muốn debug checkout/payment commerce
1. `api/controller/Order.php`
2. `service/order/Checkout.php`
3. `common/model/Order.php`

-----------------------------------
## 12. Rủi ro lớn của module API

1. `Package.php` quá lớn
- một controller ôm quá nhiều responsibility
- dễ drift logic và regression

2. nhiều flow trùng ý nghĩa với `web/controller/Package.php`
- dễ lệch behavior giữa web và api

3. social module controllers có thể chồng use case với logistics
- debug phải xác định aggregate gốc trước

4. webhook/integration path nhạy cảm với debug code `dump/die`

-----------------------------------
## 13. Kết luận

Nếu chỉ chọn 3 file để hiểu API của repo này, hãy mở:
1. `api/controller/Controller.php`
2. `api/controller/Passport.php`
3. `api/controller/Package.php`

Ba file này đại diện cho:
- tenant scoping
- auth
- logistics core