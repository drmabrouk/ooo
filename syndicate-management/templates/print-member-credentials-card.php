<?php if (!defined('ABSPATH')) exit;
$syndicate = SM_Settings::get_syndicate_info();

if (!empty($_GET['member_id'])) {
    $members_to_print = array(SM_DB::get_member_by_id(intval($_GET['member_id'])));
} else {
    $filters = array();
    if (!empty($_GET['grade_filter'])) {
        $filters['professional_grade'] = sanitize_text_field($_GET['grade_filter']);
    }
    $members_to_print = SM_DB::get_members($filters);
}

if (empty($members_to_print) || !$members_to_print[0]) wp_die('Member(s) not found');
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>بطاقات بيانات الدخول</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;700;900&display=swap');
        body { font-family: 'Rubik', sans-serif; margin: 0; padding: 20px; background: #f0f2f5; }
        .cards-wrapper { display: flex; flex-direction: column; align-items: center; gap: 40px; }
        .card { width: 450px; background: #fff; border-radius: 20px; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); border: 1px solid #e2e8f0; page-break-inside: avoid; }
        .card-header { background: #111F35; padding: 25px; text-align: center; color: #fff; }
        .card-body { padding: 35px; text-align: center; }
        .syndicate-logo { height: 50px; margin-bottom: 10px; }
        .member-name { font-size: 20px; font-weight: 900; color: #111F35; margin-bottom: 5px; }
        .member-class { font-size: 14px; color: #718096; margin-bottom: 30px; }
        .cred-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 20px; text-align: right; }
        .cred-label { font-size: 11px; color: #718096; font-weight: 700; margin-bottom: 5px; }
        .cred-value { font-family: monospace; font-size: 22px; font-weight: 900; color: #F63049; letter-spacing: 2px; }
        .note { font-size: 11px; color: #718096; line-height: 1.5; margin-top: 20px; }
        @media print {
            body { background: #fff; padding: 0; }
            .no-print { display: none; }
            .card { box-shadow: none; border: 1px solid #eee; margin-bottom: 20px; }
        }
    </style>
</head>
<body>
    <div class="cards-wrapper">
        <?php foreach ($members_to_print as $member):
            if (!$member) continue;
            $pass = get_user_meta($member->wp_user_id, 'sm_temp_pass', true);
            if (empty($pass)) $pass = '********';
        ?>
        <div class="card">
            <div class="card-header">
                <?php if ($syndicate['syndicate_logo']): ?>
                    <img src="<?php echo esc_url($syndicate['syndicate_logo']); ?>" class="syndicate-logo">
                <?php endif; ?>
                <div style="font-size: 14px; font-weight: 700;"><?php echo esc_html($syndicate['syndicate_name']); ?></div>
            </div>
            <div class="card-body">
                <div class="member-name"><?php echo esc_html($member->name); ?></div>
                <div class="member-class"><?php echo esc_html(SM_Settings::get_professional_grades()[$member->professional_grade] ?? $member->professional_grade); ?></div>

                <div class="cred-box">
                    <div class="cred-label">اسم المستخدم (القومي):</div>
                    <div class="cred-value"><?php echo esc_html($member->national_id); ?></div>
                </div>

                <div class="cred-box" style="margin-bottom: 0;">
                    <div class="cred-label">كلمة المرور (Password):</div>
                    <div class="cred-value" style="color: #111F35;"><?php echo esc_html($pass); ?></div>
                </div>

                <div class="note">
                    * يستخدم هذا الحساب للدخول لنظام إدارة النقابة (العضو وولي الأمر).<br>
                    * يرجى الحفاظ على سرية هذه البيانات وتغيير كلمة المرور بعد أول دخول.
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="no-print" style="position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%);">
        <button onclick="window.print()" style="padding: 12px 40px; background: #F63049; color: #fff; border: none; border-radius: 12px; cursor: pointer; font-family: 'Rubik'; font-weight: 900; box-shadow: 0 4px 14px 0 rgba(246, 48, 73, 0.39);">طباعة البطاقات الآن</button>
    </div>
</body>
</html>
