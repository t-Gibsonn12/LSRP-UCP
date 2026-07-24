# UCP GTAWORLD V4.3 — #TWOYEARS Rewards

## Account registration
- Sau khi tạo Master Account, UCP hiển thị bảng #TWOYEARS Early Registration Package.
- Hiển thị Thẻ #TWOYEARS Account và toàn bộ quyền lợi đăng ký sớm.
- Tài khoản mới được ghi vào `ucp_twoyears_accounts`.
- Khi cài migration V4.3, Master Account đang tồn tại cũng được backfill thành thành viên đăng ký sớm.

## Character reward
- Khi Admin duyệt một hồ sơ nhân vật của Master Account #TWOYEARS, UCP tạo một phần thưởng một lần cho character đó.
- Phần thưởng: 01 Faggio (model 462).
- Lần đăng nhập UCP tiếp theo sẽ xuất hiện modal thông báo character đã được duyệt + nhận Faggio.
- Nút OK xác nhận thông báo và chuyển thẳng sang trang thông tin character.
- Trang character hiển thị trạng thái reward #TWOYEARS và Faggio trong danh sách phương tiện khi đã đồng bộ thành công.

## Vehicle database
- `013_twoyears_rewards.sql` tạo bảng tracking reward.
- Nếu server chưa có `player_vehicles`, migration tạo một bảng character-owned vehicle tương thích với UCP.
- Nếu gamemode đã có `player_vehicles`, UCP không thay bảng đó và thử thích nghi với các tên cột phổ biến.
- Nếu schema phương tiện custom có cột bắt buộc mà UCP không biết, reward vẫn được giữ lại và sẽ retry thay vì ghi dữ liệu hỏng.

## Required
Nếu cập nhật từ V4.2, import:

`database/013_twoyears_rewards.sql`

Cài mới có thể import:

`database/INSTALL_UCP_V4.3.sql`
