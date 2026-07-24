# Los Santos Roleplay Vietnamese UCP V4.5

UCP V4.5 giữ giao diện V2-style: Inter + Oswald, đỏ/đen/xám, button gọn, header tối giản và phương tiện chỉ hiển thị trong hồ sơ từng nhân vật.

## Luồng đăng ký

1. Người chơi tạo Master Account với username, email và mật khẩu.
2. Account được ghi nhận package `#TWOYEARS`.
3. UCP lưu notification tham gia sớm.
4. Người chơi được đăng nhập tự động và chuyển thẳng tới Dashboard.
5. Không còn popup đăng ký chặn màn hình.

## Notification Center

Icon chuông lưu toàn bộ thông báo quan trọng.

- Tạo tài khoản mới -> notification tham gia sớm #TWOYEARS.
- Gửi yêu cầu tạo nhân vật -> notification xác nhận đã gửi hồ sơ.
- Admin phê duyệt -> notification nhân vật đã được duyệt.
- Click một notification -> mở trang chi tiết notification, không nhảy thẳng sang trang khác.
- Từ trang chi tiết có nút mở nội dung liên quan như hồ sơ chờ duyệt hoặc thông tin nhân vật.

## #TWOYEARS

Header có icon `#2Y` nằm cạnh chuông và icon tài khoản. Bấm icon để mở `twoyears.php`.

Quyền lợi hiển thị:

- 1 Thẻ #TWOYEARS Account, quyền lợi Tester và các đặc quyền khác.
- 01 Faggio khởi đầu cho nhân vật đầu tiên được phê duyệt.
- Role Discord #TWOYEARS.
- Trực tiếp trao đổi với đội ngũ phát triển.
- Theo dõi liên tục cập nhật mới.
- Các quyền lợi lớn khi máy chủ vận hành.

## Faggio reward

Khi nhân vật đầu tiên của Master Account #TWOYEARS được Admin phê duyệt:

1. Character được tạo trong `player_characters`.
2. Reward được ghi vào `ucp_twoyears_character_rewards`.
3. UCP INSERT Faggio model `462` trực tiếp vào `player_vehicles` theo `character_id`.
4. Plate mặc định: `TWYxxxxxx` theo Character ID.
5. Reward được đánh dấu `vehicle_granted = 1` và lưu `vehicle_id`.
6. Notification phê duyệt ghi rõ Faggio và biển số đã nhận.
7. Xe xuất hiện trong trang thông tin của đúng character đó.

Starter Faggio chỉ cấp một lần cho nhân vật đầu tiên của account, không cấp lại cho character thứ hai hoặc thứ ba.

## Database

### Update từ V4.4

Import:

`database/015_ucp_v45_notifications_rewards.sql`

Migration này cũng backfill reward V4.4 đang ở trạng thái chưa cấp xe.

### Cài mới

Import:

`database/INSTALL_UCP_V4.5.sql`

## Character UI

- Click skin -> xem thông tin nhân vật.
- Click dấu `+` -> gửi hồ sơ cho đúng slot.
- Slot trống hiển thị `TẠO NHÂN VẬT`.
- Không có mục Phương tiện ở header/account dropdown.
- Phương tiện chỉ xuất hiện bên trong trang thông tin character.

## Social

- Facebook: https://www.facebook.com/lsrpvn.official
- YouTube: https://www.youtube.com/@lsrvn
- Discord: `#` cho tới khi có link chính thức.
