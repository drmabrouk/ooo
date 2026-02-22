<?php if (!defined('ABSPATH')) exit; ?>
<?php
$request_id = intval($_GET['id']);
global $wpdb;
$req = $wpdb->get_row($wpdb->prepare("
    SELECT r.*, s.name as service_name, s.description as service_desc, s.selected_profile_fields, m.name as member_name, m.national_id, m.membership_number, m.governorate, m.professional_grade, m.specialization, m.phone, m.email, m.facility_name
    FROM {$wpdb->prefix}sm_service_requests r
    JOIN {$wpdb->prefix}sm_services s ON r.service_id = s.id
    JOIN {$wpdb->prefix}sm_members m ON r.member_id = m.id
    WHERE r.id = %d", $request_id));

if (!$req) wp_die('Request not found');

$syndicate = SM_Settings::get_syndicate_info();
$data = json_decode($req->request_data, true);
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>مستند رقمي - <?php echo esc_html($req->service_name); ?></title>
    <style>
        body { font-family: 'Arial', sans-serif; padding: 50px; color: #333; line-height: 1.6; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #111F35; padding-bottom: 20px; margin-bottom: 40px; }
        .syndicate-info { text-align: right; }
        .logo { max-height: 100px; }
        .title-box { text-align: center; background: #f8fafc; padding: 20px; border-radius: 12px; margin-bottom: 40px; border: 1px solid #e2e8f0; }
        .title-box h1 { margin: 0; font-size: 24px; color: #111F35; }
        .content-table { width: 100%; border-collapse: collapse; margin-bottom: 40px; }
        .content-table td { padding: 12px; border-bottom: 1px solid #eee; }
        .content-table td:first-child { font-weight: bold; width: 30%; color: #64748b; }
        .footer { margin-top: 60px; display: flex; justify-content: space-between; }
        .stamp-box { width: 150px; height: 150px; border: 2px dashed #cbd5e0; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #cbd5e0; font-size: 12px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print" style="position:fixed; top:20px; left:20px;">
        <button onclick="window.print()" style="padding:10px 20px; background:#111F35; color:#fff; border:none; border-radius:5px; cursor:pointer;">طباعة المستند</button>
    </div>

    <div class="header">
        <div class="syndicate-info">
            <h2 style="margin:0;"><?php echo esc_html($syndicate['syndicate_name']); ?></h2>
            <p style="margin:5px 0;"><?php echo esc_html(SM_Settings::get_governorates()[$req->governorate] ?? $req->governorate); ?></p>
        </div>
        <?php if (!empty($syndicate['syndicate_logo'])): ?>
            <img src="<?php echo esc_url($syndicate['syndicate_logo']); ?>" class="logo">
        <?php endif; ?>
    </div>

    <div class="title-box">
        <h1><?php echo esc_html($req->service_name); ?></h1>
        <p>رقم المرجع: <?php echo $req->id . '-' . date('Y'); ?></p>
    </div>

    <p>تشهد النقابة بأن السيد/ <strong><?php echo esc_html($req->member_name); ?></strong></p>
    <p>المقيد بالنقابة برقم عضوية: (<?php echo esc_html($req->membership_number); ?>) وحامل الرقم القومي: (<?php echo esc_html($req->national_id); ?>)</p>

    <table class="content-table">
        <tr><td>تاريخ تقديم الطلب:</td><td><?php echo date('Y-m-d', strtotime($req->created_at)); ?></td></tr>
        <?php
        $pFields = json_decode($req->selected_profile_fields, true) ?: [];
        $profile_map = [
            'name' => ['label' => 'الاسم الكامل', 'value' => $req->member_name],
            'national_id' => ['label' => 'الرقم القومي', 'value' => $req->national_id],
            'membership_number' => ['label' => 'رقم العضوية', 'value' => $req->membership_number],
            'professional_grade' => ['label' => 'الدرجة الوظيفية', 'value' => SM_Settings::get_professional_grades()[$req->professional_grade] ?? $req->professional_grade],
            'specialization' => ['label' => 'التخصص', 'value' => SM_Settings::get_specializations()[$req->specialization] ?? $req->specialization],
            'phone' => ['label' => 'رقم الهاتف', 'value' => $req->phone],
            'email' => ['label' => 'البريد الإلكتروني', 'value' => $req->email],
            'governorate' => ['label' => 'المحافظة', 'value' => SM_Settings::get_governorates()[$req->governorate] ?? $req->governorate],
            'facility_name' => ['label' => 'اسم المنشأة', 'value' => $req->facility_name]
        ];

        foreach ($pFields as $fKey) {
            if (isset($profile_map[$fKey])) {
                echo "<tr><td>{$profile_map[$fKey]['label']}:</td><td>{$profile_map[$fKey]['value']}</td></tr>";
            }
        }
        ?>
        <?php foreach ($data as $label => $val): ?>
            <tr><td><?php echo esc_html($label); ?>:</td><td><?php echo esc_html($val); ?></td></tr>
        <?php endforeach; ?>
    </table>

    <div style="background: #f1f5f9; padding: 20px; border-radius: 8px; font-size: 13px;">
        يعتبر هذا المستند رسمياً وصادراً من المنصة الرقمية للنقابة.
    </div>

    <div class="footer">
        <div style="text-align: center;">
            <p>توقيع المسؤول</p>
            <br><br>
            <p>..........................</p>
        </div>
        <div class="stamp-box">ختم النقابة</div>
    </div>

    <script>window.onload = () => { /* window.print(); */ }</script>
</body>
</html>
