<?php if (!defined('ABSPATH')) exit;
$syndicate = SM_Settings::get_syndicate_info();
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>بيانات دخول الأعضاء</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500;700;900&display=swap');
        body { font-family: 'Rubik', sans-serif; margin: 0; padding: 20px; background: #fff; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #111F35; padding-bottom: 20px; }
        .syndicate-name { font-size: 24px; font-weight: 900; color: #111F35; }
        .report-title { font-size: 18px; color: #F63049; margin-top: 10px; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #111F35; color: #fff; padding: 12px; text-align: right; font-size: 14px; }
        td { border: 1px solid #e2e8f0; padding: 10px; font-size: 13px; }
        tr:nth-child(even) { background: #f8fafc; }
        .code { font-family: monospace; font-weight: 700; color: #D02752; font-size: 1.1em; }
        .footer { margin-top: 40px; text-align: left; font-size: 12px; color: #718096; }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="syndicate-name"><?php echo esc_html($syndicate['syndicate_name']); ?></div>
        <div class="report-title">كشف بيانات دخول الأعضاء (ولي الأمر / العضو)</div>
        <div style="font-size: 12px; margin-top: 5px;">تاريخ التوليد: <?php echo date_i18n('j F Y'); ?></div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 50px;">#</th>
                <th>اسم العضو</th>
                <th>الرقم القومي</th>
                <th>الدرجة الوظيفية</th>
                <th>كلمة المرور المؤقتة</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $count = 1;
            foreach ($members as $s):
                $pass = get_user_meta($s->wp_user_id, 'sm_temp_pass', true);
                if (empty($pass)) $pass = '********';
            ?>
                <tr>
                    <td><?php echo $count++; ?></td>
                    <td style="font-weight: 700;"><?php echo esc_html($s->name); ?></td>
                    <td class="code"><?php echo esc_html($s->national_id); ?></td>
                    <td><?php echo esc_html(SM_Settings::get_professional_grades()[$s->professional_grade] ?? $s->professional_grade); ?></td>
                    <td class="code"><?php echo esc_html($pass); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="footer">
        * يتم استخدام الرقم القومي كاسم مستخدم للدخول للنظام.<br>
        * ينصح بتغيير كلمة المرور فور الدخول الأول للنظام.
    </div>

    <div class="no-print" style="margin-top: 30px; text-align: center;">
        <button onclick="window.print()" style="padding: 10px 30px; background: #111F35; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-family: 'Rubik'; font-weight: 700;">طباعة الكشف الآن</button>
    </div>
</body>
</html>
