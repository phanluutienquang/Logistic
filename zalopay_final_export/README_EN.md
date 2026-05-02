# ZaloPay Integration Documentation

This document describes the ZaloPay payment integration for the Zalo Mini App and the PHP Backend (ThinkPHP).

## 1. Overview
The integration allows users to pay for parcel shipping orders and recharge their wallet balances using ZaloPay directly within the Zalo Mini App.

## 2. System Architecture
- **Frontend**: React-based Zalo Mini App using `zmp-sdk`.
- **Backend**: PHP ThinkPHP framework.
- **Payment Gateway**: ZaloPay (Mini App Integrated Flow).

## 3. Workflow
1. **Initiation**: 
   - For **Recharge**: The frontend calls the backend API `recharge/zalopaySubmit` to create a pending recharge order and get an `order_no`.
   - For **Orders**: The frontend already has the `order_sn`.
2. **Payment**: The frontend calls `Payment.createOrder` from `zmp-sdk`, passing the `order_no` and `order_type` (30 for Orders, 100 for Recharge) in the `item` field.
3. **Webhook**: Once the payment is successful, ZaloPay sends a POST request to the backend webhook (`/api/zalopay/webhook`).
4. **Verification**: The backend verifies the ZaloPay signature (MAC) using `Key2`.
5. **Fulfillment**: The backend parses the `item` data to identify the order and calls the corresponding `PaySuccess` service to update the database and user balance.

## 4. Configuration
Administrators must configure the ZaloPay **Key2** in the system settings.
- **Key2**: Provided by ZaloPay Merchant Portal.
- **Webhook URL**: `https://your-domain.com/api/zalopay/webhook`

## 5. File Changes
### Backend (PHP)
- `application/api/controller/Zalopay.php`: Webhook handler.
- `application/api/controller/Recharge.php`: Added `zalopaySubmit` method.
- `application/common/enum/order/PayType.php`: Added `ZALOPAY` constant (50).
- `application/common/model/Setting.php`: Added ZaloPay default configuration.
- `application/api/service/*/PaySuccess.php`: Added ZaloPay success logic.

### Frontend (React)
- `src/pages/Mine/Recharge.jsx`: Updated to initiate backend order before ZaloPay payment.
- `src/pages/Order/OrderDetail.jsx`: Updated to include order metadata in ZaloPay request.
