<?php if (!defined('ABSPATH')) exit; ?>
<?php
$syndicate = SM_Settings::get_syndicate_info();
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title><?php echo esc_html($title); ?></title>
    <style>
        @page { size: A4; margin: 1cm; }
        body { font-family: 'Arial', sans-serif; margin: 0; padding: 20px; color: #333; line-height: 1.4; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #111F35; padding-bottom: 15px; margin-bottom: 30px; }
        .logo { max-height: 80px; }
        .report-title { text-align: center; font-size: 1.5em; font-weight: bold; margin-bottom: 25px; color: #111F35; text-decoration: underline; }
        .data-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .data-table th { background: #f1f5f9; border: 1px solid #cbd5e0; padding: 10px; text-align: right; }
        .data-table td { border: 1px solid #cbd5e0; padding: 10px; }
        .data-table tr:nth-child(even) { background: #f8fafc; }
        .footer { position: fixed; bottom: 0; width: 100%; font-size: 10px; border-top: 1px solid #eee; padding-top: 5px; text-align: left; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #111F35; color: #fff; border: none; border-radius: 5px; cursor: pointer;">طباعة التقرير</button>
    </div>

    <div class="header">
        <div style="text-align: right;">
            <h2 style="margin: 0;"><?php echo esc_html($syndicate['syndicate_name']); ?></h2>
            <p style="margin: 5px 0 0 0; font-size: 12px;"><?php echo esc_html($syndicate['address']); ?></p>
            <p style="margin: 2px 0 0 0; font-size: 12px;"><?php echo esc_html($syndicate['phone']); ?></p>
        </div>
        <?php if (!empty($syndicate['syndicate_logo'])): ?>
            <img src="<?php echo esc_url($syndicate['syndicate_logo']); ?>" class="logo">
        <?php endif; ?>
    </div>

    <div class="report-title"><?php echo esc_html($title); ?></div>
    <div style="margin-bottom: 15px; font-size: 12px;">تاريخ الاستخراج: <?php echo date_i18n('l j F Y - H:i'); ?></div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 40px;">#</th>
                <th>اسم العضو</th>
                <th>الرقم القومي</th>
                <th>المبلغ المستحق</th>
                <th>تفاصيل الاستحقاق</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $total_amount = 0;
            if (empty($data)): ?>
                <tr><td colspan="5" style="text-align: center; padding: 30px;">لا توجد بيانات متاحة لهذا التقرير.</td></tr>
            <?php else:
                foreach ($data as $index => $row):
                    $total_amount += $row['amount'];
                ?>
                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td style="font-weight: bold;"><?php echo esc_html($row['name']); ?></td>
                    <td style="font-family: monospace;"><?php echo esc_html($row['nid']); ?></td>
                    <td style="font-weight: bold;"><?php echo number_format($row['amount'], 2); ?> ج.م</td>
                    <td><?php echo esc_html($row['details']); ?></td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
        <tfoot>
            <tr style="background: #edf2f7; font-weight: bold;">
                <td colspan="3" style="text-align: left; padding: 10px;">الإجمالي الكلي:</td>
                <td colspan="2" style="padding: 10px; font-size: 1.2em; color: #e53e3e;"><?php echo number_format($total_amount, 2); ?> ج.م</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        تم استخراج هذا التقرير آلياً من نظام إدارة النقابة الرقمي - <?php echo home_url(); ?>
    </div>
</body>
</html>
