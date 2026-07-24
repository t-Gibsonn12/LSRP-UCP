# UCP GTAWORLD V4.5

## Registration
- Tạo Master Account xong đăng nhập tự động và chuyển thẳng về Dashboard.
- Không còn popup đăng ký #TWOYEARS.
- Tài khoản mới vẫn nhận notification #TWOYEARS trong Notification Center.

## Notification Center
- Gửi hồ sơ tạo nhân vật -> tạo notification `Đã gửi yêu cầu tạo nhân vật`.
- Admin phê duyệt nhân vật -> tạo notification phê duyệt.
- Không còn popup tự bật sau khi nhân vật được duyệt.
- Click notification trong chuông hoặc danh sách -> mở `notification.php?id=...` để xem chi tiết trước.
- Notification được đánh dấu đã đọc khi mở trang chi tiết và vẫn lưu trong lịch sử.

## #TWOYEARS
- Thêm icon `#2Y` cạnh chuông và icon tài khoản.
- Thêm trang `twoyears.php` xem đầy đủ quyền lợi và trạng thái package.
- Starter Faggio chỉ áp dụng một lần cho nhân vật đầu tiên được phê duyệt.

## Vehicle reward
- Faggio model 462 được INSERT thật vào `player_vehicles` khi nhân vật đầu tiên của early account được phê duyệt.
- Reward tracking cập nhật `vehicle_granted`, `vehicle_id`, `vehicle_granted_at`.
- Migration `015_ucp_v45_notifications_rewards.sql` backfill reward V4.4 chưa nhận xe.
