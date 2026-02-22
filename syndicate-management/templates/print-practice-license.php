<?php
if (!defined('ABSPATH')) exit;
$user = wp_get_current_user();
if (!current_user_can('sm_print_reports') && !in_array('sm_member', (array)$user->roles)) wp_die('Unauthorized');

$member_id = intval($_GET['member_id']);
$member = SM_DB::get_member_by_id($member_id);
if (!$member || empty($member->license_number)) wp_die('License data not found');

$syndicate = SM_Settings::get_syndicate_info();
$appearance = SM_Settings::get_appearance();
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>تصريح مزاولة المهنة - <?php echo esc_html($member->name); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        @page { size: A4 portrait; margin: 0; }
        body { font-family: 'Rubik', sans-serif; margin: 0; padding: 0; background: #f4f4f4; color: #333; }
        .license-page { width: 210mm; height: 297mm; padding: 15mm; margin: 20px auto; box-sizing: border-box; border: 12px double <?php echo $appearance['primary_color']; ?>; position: relative; background: #fff; }
        .header { text-align: center; margin-bottom: 40px; border-bottom: 1px solid #eee; padding-bottom: 20px; }
        .logo { max-height: 110px; margin-bottom: 15px; }
        .syndicate-name { font-size: 26px; font-weight: 900; color: <?php echo $appearance['dark_color']; ?>; }
        .title { font-size: 38px; font-weight: 900; color: <?php echo $appearance['primary_color']; ?>; margin-top: 25px; }
        .content { margin-top: 50px; line-height: 2.3; font-size: 22px; text-align: center; }
        .field { font-weight: 900; color: #000; border-bottom: 1px solid #ccc; padding: 0 10px; }
        .footer { margin-top: 80px; display: flex; justify-content: space-between; align-items: flex-end; }
        .signature-box { text-align: center; width: 280px; }
        .qr-code { width: 120px; height: 120px; border: 1px solid #eee; padding: 5px; }
        @media print {
            .no-print { display: none; }
            .license-page { border-color: #000 !important; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="position: fixed; top: 20px; right: 20px; z-index: 1000;">
        <button onclick="window.print()" style="padding: 15px 30px; background: #27ae60; color: white; border: none; cursor: pointer; border-radius: 8px; font-weight: bold; font-family: 'Rubik';">طباعة الترخيص</button>
    </div>

    <div class="license-page">
        <div class="header">
            <?php if ($syndicate['syndicate_logo']): ?>
                <img src="<?php echo esc_url($syndicate['syndicate_logo']); ?>" class="logo">
            <?php endif; ?>
            <div class="syndicate-name"><?php echo esc_html($syndicate['syndicate_name']); ?></div>
            <div style="font-size: 18px;"><?php echo esc_html($syndicate['address']); ?></div>
            <div class="title">تصريح مزاولة المهنة</div>
        </div>

        <div class="content">
            تشهد إدارة <?php echo esc_html($syndicate['syndicate_name']); ?> بأن السيد/ <span class="field"><?php echo esc_html($member->name); ?></span><br>
            الحامل للرقم القومي: <span class="field"><?php echo esc_html($member->national_id); ?></span><br>
            والمسجل بالنقابة برقم عضوية: <span class="field"><?php echo esc_html($member->membership_number); ?></span><br>
            قد استوفى كافة الشروط والضوابط القانونية لمزاولة مهنة الرياضة بصفة: <span class="field"><?php echo esc_html(SM_Settings::get_professional_grades()[$member->professional_grade] ?? $member->professional_grade); ?></span><br>
            تخصص: <span class="field"><?php echo esc_html(SM_Settings::get_specializations()[$member->specialization] ?? $member->specialization); ?></span>.<br><br>

            وبناءً عليه تم منح هذا الترخيص برقم: <span class="field"><?php echo esc_html($member->license_number); ?></span><br>
            تاريخ الإصدار: <span class="field"><?php echo esc_html($member->license_issue_date); ?></span><br>
            تاريخ الانتهاء: <span class="field"><?php echo esc_html($member->license_expiration_date); ?></span><br>
        </div>

        <div class="footer">
            <div class="qr-code-box">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?php echo urlencode(admin_url('admin-ajax.php?action=sm_print_license&member_id='.$member->id)); ?>" class="qr-code">
                <p style="font-size: 10px; text-align: center; margin-top: 5px;">تحقق من صحة الترخيص</p>
            </div>

            <div class="signature-box">
                <p>يعتمد،،،</p>
                <p style="font-weight: 900; margin-top: 20px;"><?php echo esc_html($syndicate['syndicate_officer_name']); ?></p>
                <p style="font-size: 14px;">مسؤول النقابة العام</p>
                <div style="height: 80px;"></div>
            </div>
        </div>

        <div style="position: absolute; bottom: 20mm; left: 0; right: 0; text-align: center; font-size: 12px; color: #777; border-top: 1px solid #eee; padding-top: 10px; margin: 0 20mm;">
            هذا الترخيص ملك للنقابة ويجب إبرازه عند الطلب. أي تلاعب في بياناته يعرض صاحبه للمساءلة القانونية.
        </div>
    </div>
</body>
</html>
