# UCP V4.1 — Character Click + Owned Vehicles

## Character slots
- Dashboard: bấm trực tiếp vào **skin** của nhân vật để mở hồ sơ nhân vật.
- Dashboard: bấm trực tiếp vào dấu **+** của slot trống để mở form tạo/gửi hồ sơ đúng slot.
- Có hover label rõ ràng cho skin và nút +.

## Phương tiện sở hữu
- Thêm menu **Phương tiện** trên header.
- Thêm **Phương tiện sở hữu** trong dropdown Master Account.
- Thêm trang `vehicles.php`.
- Lọc garage theo từng nhân vật.
- Hiển thị model, tên xe, plate, chủ sở hữu, trạng thái, ODO, fuel, health, màu xe và favorite khi database có dữ liệu.
- Trang hồ sơ nhân vật có badge số phương tiện và danh sách xe rút gọn.
- Dashboard có quick button sang Phương tiện.

### Database vehicle compatibility
V4.1 không tạo một bảng xe UCP riêng và không ghi đè schema xe của gamemode.
UCP đọc trực tiếp `player_vehicles` nếu bảng này đã tồn tại.

Các tên cột phổ biến được tự nhận diện, ví dụ:
- owner: `character_id`, `owner_character_id`, `owner_id`
- model: `model_id`, `model`, `vehicle_model`, `modelid`
- plate: `plate`, `number_plate`, `license_plate`
- mileage: `mileage`, `odometer`, `kilometers`, `km`
- favorite: `is_favorite`, `favorite`, `favourite`

Nếu `player_vehicles` chưa tồn tại, trang vẫn chạy bình thường và hiển thị garage trống/chưa có dữ liệu.

## Social
- Facebook: `https://www.facebook.com/lsrpvn.official`
- YouTube: `https://www.youtube.com/@lsrvn`
- Link social mở tab mới.
- Discord giữ nguyên `#` vì chưa có link Discord mới.

## Version
- Footer đổi thành `UCP V4.1`.
