# 09. Từ điển thuật ngữ domain

File này giúp thống nhất ngôn ngữ khi đọc code, SQL và trao đổi với team.

## 1. Thuật ngữ lõi nhất

### Tenant
Ý nghĩa:
- một khách hàng SaaS / một đơn vị vận hành dùng chung platform

Dấu hiệu trong hệ thống:
- `wxapp_id`
- `yoshop_wxapp`

### wxapp
Trong repo này, `wxapp` không chỉ nghĩa “WeChat mini app” theo nghĩa hẹp.
Nó đang đóng vai trò tenant/app container.

Hiểu đúng hơn là:
- app instance / tenant instance

### Store
Tùy ngữ cảnh có thể là:
1. tenant backend
2. warehouse/shop node
3. merchant context

Nơi dễ gây nhầm:
- `store` module là backoffice module
- `store_shop` là kho/điểm vận hành vật lý
- store user là tài khoản backoffice

### User
Thường là end-user / customer.
Không phải store user.

### Store user
Nhân sự nội bộ của tenant:
- admin
- kho
- CSKH
- tài chính
- vận hành

-----------------------------------
## 2. Logistics thuật ngữ

### Package
Ý nghĩa business:
- một kiện hàng / một parcel inbound riêng lẻ
- hàng được nhận vào kho theo từng package

Bảng:
- `yoshop_package`

Đừng hiểu nhầm là “gói cước” hay “software package”.

### Package item
Ý nghĩa:
- item/component khai báo bên trong package
- phục vụ phân loại / customs / thống kê

### Inpack
Đây là thuật ngữ quan trọng nhất của domain logistics trong repo.

Ý nghĩa gần đúng:
- đơn gom hàng
- đơn tập kết
- đơn outbound logistics được tạo từ nhiều package

Bảng:
- `yoshop_inpack`

Cách nhớ:
- `package` = từng kiện
- `inpack` = đơn gom nhiều kiện

### Line
Ý nghĩa:
- tuyến vận chuyển / sản phẩm logistics / rule pricing channel

Bảng:
- `yoshop_line`

Line quyết định:
- cách tính cước
- giới hạn hàng hóa
- giới hạn cân nặng/kích thước
- quốc gia hỗ trợ
- kiểu vận tải

### Line category
Phân loại tuyến:
- sea/air/land/rail hoặc biến thể tương tự

### Shelf
Kệ hàng trong kho

### Shelf unit
Ô/khoang trong kệ

### Shelf unit item
Record chi tiết gắn package vào vị trí shelf cụ thể

### Inbound
Giai đoạn hàng đi vào kho

### Outbound
Giai đoạn hàng được đóng gói, phát đi khỏi kho

### Pick / Picking
Lấy hàng khỏi vị trí lưu kho để chuẩn bị đóng gói / xuất đi

### Pack / Packing
Đóng gói các package để chuẩn bị shipment

### Claim / Renling
Nhận diện chủ sở hữu package, đặc biệt với package chưa rõ user

### Unclaimed package
Package chưa xác định chủ sở hữu

### Batch
Lô xử lý hàng loạt trong kho / in / vận hành

### Tracking / Trajectory / Logistics track
Chuỗi trạng thái vận chuyển theo thời gian

### Carrier
Đơn vị vận chuyển thực tế / logistics provider

### t_order_sn
Thường là mã đơn vận chuyển bên carrier / mã vận đơn logistics

### express_num
Mã vận đơn nội địa / mã tracking gốc của package inbound

### order_sn
Mã đơn nội bộ của package hoặc inpack tùy context

-----------------------------------
## 3. Commerce thuật ngữ

### Goods
Sản phẩm bán trong e-commerce flow

### SKU
Biến thể sản phẩm

### Cart
Giỏ hàng commerce

### Order
Trong commerce context:
- order mua hàng bình thường
- bảng `yoshop_order`

Cảnh báo:
- đừng dùng từ “order” chung chung khi trao đổi, hãy nói rõ:
  - commerce order
  - buyer order
  - inpack order

### Order goods
Snapshot line items của order commerce

### Order address
Snapshot địa chỉ giao hàng của order commerce

### Refund
Hoàn đơn/hoàn tiền của commerce flow

-----------------------------------
## 4. Buyer / purchase-on-behalf thuật ngữ

### Buyer order
Đơn mua hộ / đại mua

Bảng:
- `yoshop_buyer_order`

Ý nghĩa:
- user không tự mua trực tiếp trên hệ thống
- user dán link external marketplace
- hệ thống/merchant mua hộ

### External marketplace URL
URL từ:
- Taobao
- Tmall
- JD
- 1688

-----------------------------------
## 5. Finance thuật ngữ

### Balance
Số dư ví của user

### Recharge
Nạp tiền vào ví

### Real payment
Số tiền thực trả

### Pack fee / pack_free
Phí đóng gói / phí dịch vụ đóng gói

### Other fee / other_free
Phí khác
- customs
- surcharge
- manual charge
- additional handling

### Insure fee / insure_free
Phí bảo hiểm

### Dealer
Người tham gia phân phối / phân cấp giới thiệu

### Referee / referral
Quan hệ giới thiệu / người giới thiệu

### Commission
Hoa hồng

-----------------------------------
## 6. Auth & SaaS thuật ngữ

### Super admin
Quản trị nền tảng cấp cao nhất
- không thuộc tenant business như store user

### Store admin
Quản trị viên trong tenant

### Clerk
Nhân viên kho / nhân viên vận hành / nhân viên service tùy tenant cấu hình

### RBAC
Role-Based Access Control
- quyền dựa trên role và URL access

### Data scope
Phạm vi dữ liệu được thấy/sửa
- theo tenant
- theo warehouse
- theo line
- theo country
- theo clerk

### Tenant expiration
Tenant hết hạn sử dụng SaaS
- thường check qua `wxapp.end_time`

### Enter store
Super admin “nhảy” vào backend của tenant bằng account super store user

-----------------------------------
## 7. Social commerce thuật ngữ

### Sharing
Group-buy / mua chung / 拼团 style

### Bargain
Kéo giá /砍价 style

### Sharp
Flash sale / seckill / 秒杀

### Blindbox
Hộp mù / bán theo mechanic ngẫu nhiên

-----------------------------------
## 8. Thuật ngữ kỹ thuật dễ gây hiểu sai trong repo

### wxappid vs wxapp_id
- `wxapp_id`: hay thấy ở API / DB / model
- `wxappid`: hay thấy ở web query param

### open_id
Không phải lúc nào cũng chỉ WeChat openid thật.
Trong code có thể được dùng để lưu:
- mobile/email surrogate id
- Zalo id
- `LINE_<line_user_id>`

=> đây là field “identity anchor” khá linh hoạt, không thuần một provider.

### source
Trong package/inpack context, `source` nghĩa là nguồn sinh record.
Không phải source code.

### status
Status number không universal cho mọi bảng.
- `package.status` khác `inpack.status`
- `order_status` commerce lại khác nữa

-----------------------------------
## 9. 10 cụm nên dùng thống nhất khi nói chuyện trong team

1. “tenant” thay cho “app” khi nói tầng SaaS
2. “store backend user” thay cho “admin” khi nói user nội bộ tenant
3. “end-user” thay cho “user” khi cần phân biệt với store user
4. “package inbound” cho kiện vào kho
5. “inpack outbound order” cho đơn gom hàng logistics
6. “commerce order” cho `yoshop_order`
7. “buyer order” cho `yoshop_buyer_order`
8. “line pricing rule” cho logic tính cước
9. “warehouse scope” cho giới hạn data theo `shop_id`
10. “tenant scope” cho giới hạn data theo `wxapp_id`

-----------------------------------
## 10. Kết luận ngắn

Dự án này rối chủ yếu vì cùng một từ bị dùng ở nhiều tầng.

Chìa khóa để hiểu đúng là thống nhất 4 từ:
- tenant
- package
- inpack
- commerce order

Chỉ cần 4 từ này rõ, bạn sẽ giảm nhầm lẫn rất nhiều khi đọc code.