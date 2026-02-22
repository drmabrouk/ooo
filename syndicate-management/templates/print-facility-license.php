<?php
if (!defined('ABSPATH')) exit;
$user = wp_get_current_user();
if (!current_user_can('sm_print_reports')) wp_die('Unauthorized');

$member_id = intval($_GET['member_id']);
$member = SM_DB::get_member_by_id($member_id);
if (!$member || empty($member->facility_number)) wp_die('Facility data not found');

$syndicate = SM_Settings::get_syndicate_info();
$appearance = SM_Settings::get_appearance();
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>ترخيص منشأة رياضية - <?php echo esc_html($member->facility_name); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        @page { size: A4 portrait; margin: 0; }
        body { font-family: 'Rubik', sans-serif; margin: 0; padding: 0; background: #f0f0f0; }
        .license-page { width: 210mm; height: 297mm; padding: 15mm; margin: 20px auto; box-sizing: border-box; border: 15px solid <?php echo $appearance['dark_color']; ?>; position: relative; background: #fff; }
        .inner-border { border: 5px double <?php echo $appearance['primary_color']; ?>; height: 100%; padding: 15mm; box-sizing: border-box; display: flex; flex-direction: column; justify-content: space-between; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .title-box { text-align: center; flex: 1; }
        .title { font-size: 48px; font-weight: 900; color: <?php echo $appearance['primary_color']; ?>; margin: 10px 0; }
        .content { font-size: 24px; line-height: 1.8; text-align: center; margin-top: 20px; }
        .field { font-weight: 900; border-bottom: 2px solid #ccc; padding: 0 15px; color: #000; }
        .footer { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 20px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print" style="position: fixed; top: 20px; right: 20px; z-index: 1000;">
        <button onclick="window.print()" style="padding: 15px 30px; background: #27ae60; color: white; border: none; cursor: pointer; border-radius: 8px; font-weight: bold;">طباعة الشهادة</button>
    </div>

    <div class="license-page">
        <div class="inner-border">
            <div class="header">
                <div style="text-align: center; width: 100%;">
                    <?php if ($syndicate['syndicate_logo']): ?>
                        <img src="<?php echo esc_url($syndicate['syndicate_logo']); ?>" style="max-height: 100px; margin-bottom: 20px;">
                    <?php endif; ?>
                    <div style="font-weight: 700; font-size: 22px; color: <?php echo $appearance['dark_color']; ?>;"><?php echo esc_html($syndicate['syndicate_name']); ?></div>
                    <p style="font-size: 16px; margin: 5px 0; color: #666;">قسم شؤون تراخيص المنشآت</p>

                    <div class="title" style="margin-top: 40px;">شهادة ترخيص منشأة</div>
                    <div style="font-size: 24px; font-weight: 900; color: #444; margin-top: 10px;">فئة ( <?php echo esc_html($member->facility_category); ?> )</div>
                </div>
            </div>

            <div class="content" style="margin-top: 50px; line-height: 2.2;">
                بناءً على اللوائح التنظيمية المنصوص عليها، تشهد النقابة بأن المنشأة:<br>
                <span class="field" style="font-size: 28px;"><?php echo esc_html($member->facility_name); ?></span><br>
                المملوكة للسيد/ <span class="field"><?php echo esc_html($member->name); ?></span><br>
                والواقعة في: <span class="field"><?php echo esc_html($member->facility_address ?: '---'); ?></span><br>
                قد تم منحها الترخيص القانوني لمزاولة النشاط الرياضي.<br><br>

                رقم الترخيص الرسمي: <span class="field" style="color: <?php echo $appearance['primary_color']; ?>;"><?php echo esc_html($member->facility_number); ?></span><br>
                تاريخ الإصدار: <span class="field"><?php echo esc_html($member->facility_license_issue_date); ?></span><br>
                تاريخ الانتهاء: <span class="field"><?php echo esc_html($member->facility_license_expiration_date); ?></span>
            </div>

            <div class="footer" style="margin-bottom: 30px;">
                <div style="text-align: center;">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=<?php echo urlencode(admin_url('admin-ajax.php?action=sm_print_facility&member_id='.$member_id)); ?>" style="border: 1px solid #eee; padding: 5px;">
                    <p style="font-size: 11px; margin-top: 8px;">رمز التحقق الرقمي</p>
                </div>

                <div style="text-align: center; width: 300px;">
                    <p style="font-weight: 700; margin-bottom: 40px;">يعتمد،، مسؤول النقابة العام</p>
                    <p style="font-size: 22px; font-weight: 900;"><?php echo esc_html($syndicate['syndicate_officer_name']); ?></p>
                    <div style="height: 60px;"></div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
