# ZaloPay 集成文档

本文档介绍了 Zalo Mini App 与 PHP 后端（ThinkPHP）的 ZaloPay 支付集成方案。

## 1. 概述
该集成方案允许用户在 Zalo Mini App 内直接使用 ZaloPay 支付集运订单费用以及进行账户余额充值。

## 2. 系统架构
- **前端**: 基于 React 的 Zalo Mini App，使用 `zmp-sdk`。
- **后端**: PHP ThinkPHP 框架。
- **支付网关**: ZaloPay (Mini App 集成流程)。

## 3. 业务流程
1. **初始化**: 
   - **余额充值**: 前端先调用后端接口 `recharge/zalopaySubmit` 创建待支付充值订单，并获取 `order_no`。
   - **订单支付**: 前端已拥有订单号 `order_sn`。
2. **发起支付**: 前端调用 `zmp-sdk` 的 `Payment.createOrder`，并在 `item` 字段中以 JSON 字符串形式传入 `order_no` 和 `order_type`（30 代表订单，100 代表充值）。
3. **回调通知 (Webhook)**: 支付成功后，ZaloPay 服务器向后端异步通知地址 (`/api/zalopay/webhook`) 发送 POST 请求。
4. **验证签名**: 后端使用 `Key2` 验证 ZaloPay 发送的数据签名 (MAC)，确保请求合法性。
5. **业务处理**: 后端解析 `item` 数据识别订单，并调用相应的 `PaySuccess` 服务更新数据库状态及用户余额。

## 4. 配置要求
管理员需要在系统后台或数据库中配置 ZaloPay 的 **Key2**。
- **Key2**: 由 ZaloPay 商户平台提供。
- **回调地址 (Webhook URL)**: `https://您的域名/api/zalopay/webhook`

## 5. 文件变更说明
### 后端 (PHP)
- `application/api/controller/Zalopay.php`: 处理 Webhook 回调。
- `application/api/controller/Recharge.php`: 新增 `zalopaySubmit` 方法用于创建充值订单。
- `application/common/enum/order/PayType.php`: 新增 `ZALOPAY` 常量 (50)。
- `application/common/model/Setting.php`: 新增 ZaloPay 默认配置项。
- `application/api/service/*/PaySuccess.php`: 增加 ZaloPay 支付成功的业务逻辑处理。

### 前端 (React)
- `src/pages/Mine/Recharge.jsx`: 更新逻辑，在发起 ZaloPay 支付前先通过后端创建订单。
- `src/pages/Order/OrderDetail.jsx`: 更新逻辑，在 ZaloPay 请求中包含订单元数据。
