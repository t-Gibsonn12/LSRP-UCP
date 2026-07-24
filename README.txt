LSRP UCP GTAWORLD V4.5.3 - APPROVAL + NOTIFICATION + FAGGIO HOTFIX
===================================================================

Sua 3 loi:
1. Admin duyet bao: There is no active transaction.
2. Duyet xong player khong co notification.
3. #TWOYEARS khong nhan Faggio.

CAI DAT
-------
1. Copy de 2 file:
   app/functions.php
   admin/application.php

2. Import SQL:
   database/017_ucp_v453_repair_approval_reward.sql

3. Logout/login lai UCP va test.

Luu y:
- SQL 017 an toan de chay lai nhieu lan.
- SQL se backfill notification cho character DA DUYET bi thieu thong bao.
- SQL se thu cap lai Faggio cho reward dang vehicle_granted = 0.
- PHP moi khong de helper CREATE TABLE nam trong transaction nua, nen khong con loi
  "There is no active transaction".
- Approval duoc commit truoc; reward va notification duoc xu ly sau do.
- Notification duoc tao ke ca khi xe cap loi, de player van nhan duoc thong bao duyet.

Neu sau SQL 017 cot last_error cua ucp_twoyears_character_rewards van co noi dung,
noi dung do la loi schema player_vehicles thuc te can xu ly tiep.
