<?php
require __DIR__ . '/app/bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('dashboard.php');
}

verify_csrf();

$rewardId = (int)($_POST['reward_id'] ?? 0);
$characterId = (int)($_POST['character_id'] ?? 0);

if ($rewardId <= 0 || $characterId <= 0) {
    flash('danger', 'Phần thưởng #TWOYEARS không hợp lệ.');
    redirect('dashboard.php');
}

$reward = twoyears_mark_character_notice_seen((int)current_account_id(), $rewardId);
if (!$reward || (int)$reward['character_id'] !== $characterId) {
    flash('danger', 'Không tìm thấy phần thưởng #TWOYEARS của nhân vật này.');
    redirect('dashboard.php');
}

$_SESSION['_skip_twoyears_notice_once'] = true;
redirect('character.php?id=' . $characterId);
