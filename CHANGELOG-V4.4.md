# UCP V4.4 — Notification Center + #TWOYEARS flow fix

## Fixed
- Nút OK của popup đăng ký sớm không còn phụ thuộc JavaScript để đóng modal.
- Tạo Master Account mới luôn ghi nhận gói #TWOYEARS và lưu một thông báo persistent.
- Khi Admin duyệt nhân vật, UCP tạo thông báo duyệt nhân vật + quyền nhận Faggio #TWOYEARS.
- Thông báo được lưu lại sau khi đọc.

## Added
- Icon chuông cạnh icon tài khoản.
- Badge số thông báo chưa đọc.
- Dropdown thông báo gần nhất.
- Trang `notifications.php` xem lịch sử thông báo.
- Nút đánh dấu tất cả đã đọc.
- Migration `database/014_ucp_notifications.sql`.

## Important
V4.4 **chưa cấp Faggio vào `player_vehicles`**. UCP chỉ ghi nhận quyền nhận 01 Faggio model 462 cho nhân vật đủ điều kiện. Việc cấp xe thật sẽ được nối với module vehicle của gamemode sau.
