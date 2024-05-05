<?php

return [
    'exceptions' => [
        'no_new_default_allocation' => 'คุณกำลังพยายามลบการจัดสรรเริ่มต้นสำหรับเซิร์ฟเวอร์นี้ แต่ไม่มีการจัดสรรทางเลือกที่จะใช้',
        'marked_as_failed' => 'เซิร์ฟเวอร์นี้ถูกทำเครื่องหมายว่าล้มเหลวในการติดตั้งก่อนหน้านี้ สถานะปัจจุบันไม่สามารถสลับได้ในสถานะนี้',
        'bad_variable' => 'มีข้อผิดพลาดในการตรวจสอบกับตัวแปร :name',
        'daemon_exception' => 'มีข้อผิดพลาดขณะพยายามสื่อสารกับเดมอน ซึ่งส่งผลให้มีโค้ดตอบกลับ HTTP/:code ข้อผิดพลาดนี้ถูกบันทึกไว้แล้ว (รหัสคำขอ: :request_id)',
        'default_allocation_not_found' => 'ไม่พบการจัดสรรเริ่มต้นในการจัดสรรของเซิร์ฟเวอร์นี้',
    ],
    'alerts' => [
        'startup_changed' => 'การกำหนดค่าการเริ่มต้นสำหรับเซิร์ฟเวอร์นี้ได้รับการอัปเดตแล้ว หาก Egg ของเซิร์ฟเวอร์นี้มีการเปลี่ยนแปลง การติดตั้งใหม่จะเกิดขึ้นทันที',
        'server_deleted' => 'เซิร์ฟเวอร์ถูกลบออกจากระบบเรียบร้อยแล้ว',
        'server_created' => 'สร้างเซิร์ฟเวอร์บนแผงควบคุมสำเร็จแล้ว โปรดให้ daemon สักครู่เพื่อติดตั้งเซิร์ฟเวอร์นี้ให้เสร็จสมบูรณ์',
        'build_updated' => 'รายละเอียดการกำหนดค่าบิลด์สำหรับเซิร์ฟเวอร์นี้ได้รับการอัปเดตแล้ว การเปลี่ยนแปลงบางอย่างอาจต้องรีสตาร์ทจึงจะมีผล',
        'suspension_toggled' => 'สถานะการระงับเซิร์ฟเวอร์ถูกเปลี่ยนเป็น :status',
        'rebuild_on_boot' => 'เซิร์ฟเวอร์นี้ถูกทำเครื่องหมายว่าต้องมีการสร้าง Docker Container ใหม่ สิ่งนี้จะเกิดขึ้นในครั้งถัดไปที่เซิร์ฟเวอร์เริ่มทำงาน',
        'install_toggled' => 'สถานะการติดตั้งสำหรับเซิร์ฟเวอร์นี้ถูกสลับแล้ว',
        'server_reinstalled' => 'เซิร์ฟเวอร์นี้อยู่ในคิวสำหรับการติดตั้งใหม่ซึ่งกำลังเริ่มต้นแล้ว',
        'details_updated' => 'รายละเอียดเซิร์ฟเวอร์ได้รับการอัพเดตเรียบร้อยแล้ว',
        'docker_image_updated' => 'เปลี่ยนอิมเมจ Docker เริ่มต้นเพื่อใช้สำหรับเซิร์ฟเวอร์นี้สำเร็จแล้ว จำเป็นต้องรีบูตเพื่อใช้การเปลี่ยนแปลงนี้',
        'node_required' => 'คุณต้องมีการกำหนดค่า Node อย่างน้อยหนึ่งรายการก่อนจึงจะสามารถเพิ่มเซิร์ฟเวอร์ลงในแผงนี้ได้',
        'transfer_nodes_required' => 'คุณต้องมีการกำหนดค่าอย่างน้อยสองโหนดก่อนจึงจะสามารถถ่ายโอนเซิร์ฟเวอร์ได้',
        'transfer_started' => 'เริ่มการถ่ายโอนเซิร์ฟเวอร์แล้ว',
        'transfer_not_viable' => 'Node ที่คุณเลือกไม่มีพื้นที่ดิสก์หรือหน่วยความจำที่จำเป็นเพื่อรองรับเซิร์ฟเวอร์นี้',
    ],
];
