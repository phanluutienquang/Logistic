# Hướng dẫn đọc hiểu dự án Logistic / Cross-border SaaS

Thư mục này là “bản đồ đọc dự án” được sinh ra từ:
- source code hiện có trong `source/application/*`
- schema SQL chính trong `xinsuju.sql`
- các cấu hình và hành vi runtime của dự án ThinkPHP
- tài liệu có sẵn `comprehensive_project_spec.md`

Mục tiêu:
- giúp bạn hiểu đúng domain
- tách bạch business logic logistics với e-commerce
- nhìn được auth/authz/SaaS/multi-tenant
- biết bắt đầu đọc code từ đâu
- biết bảng nào quan trọng trong DB
- biết flow nào là flow “xương sống” của hệ thống

Lưu ý rất quan trọng
- Dự án này không phải chỉ là shop e-commerce thông thường.
- Nó là hệ thống lai giữa:
  1) cross-border logistics / forwarding / parcel consolidation
  2) social commerce / mini app commerce
  3) SaaS multi-tenant cho nhiều cửa hàng / tenant khác nhau
- Trong code thực tế, “order” có nhiều nghĩa. Bạn phải phân biệt kỹ:
  - `yoshop_order`: order mua hàng e-commerce truyền thống
  - `yoshop_buyer_order`: order đại mua / order mua hộ
  - `yoshop_inpack`: đơn gom hàng / đơn tập kết / đơn logistics chính
  - `yoshop_package`: từng kiện hàng / từng parcel trước khi gom

Khuyến nghị thứ tự đọc
1. `01_tong_quan_kien_truc.md`
2. `02_domain_va_business_logic.md`
3. `03_auth_authz_saas.md`
4. `04_flow_nghiep_vu_chinh.md`
5. `05_database_map.md`
6. `06_codebase_map.md`
7. `07_checklist_doc_hieu_du_an.md`
8. `08_erd_textual.md`
9. `09_dictionary_thuat_ngu.md`
10. `10_bug_risk_va_diem_can_than.md`
11. `11_reading_path_cho_dev_moi.md`
12. `12_file_quan_trong_nhat_theo_domain.md`
13. `13_cac_status_quan_trong_toan_he_thong.md`
14. `14_cheatsheet_truy_van_sql.md`
15. `15_api_map.md`
16. `16_store_backend_map.md`
17. `17_kich_ban_debug_thuc_te.md`
18. `18_debt_refactor_goi_y.md`

Nếu chỉ có 15 phút
- đọc file 01 + 02 + 03

Nếu muốn hiểu để code ngay
- đọc file 04 + 05 + 06 + 13

Nếu muốn onboarding cho dev mới
- đọc file 11 + 12 rồi đi tiếp toàn bộ theo thứ tự trên

Nếu muốn debug trực tiếp bằng DB
- đọc file 14 + 17

Nếu muốn chuẩn bị refactor/hardening hệ thống
- đọc file 10 + 13 + 18
