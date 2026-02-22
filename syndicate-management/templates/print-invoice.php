<?php
if (!defined('ABSPATH')) exit;

if (!current_user_can('sm_manage_finance')) wp_die('Unauthorized');

$payment_id = intval($_GET['payment_id']);
global $wpdb;
$payment = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_payments WHERE id = %d", $payment_id));
if (!$payment) wp_die('Payment not found');

$member = SM_DB::get_member_by_id($payment->member_id);
$syndicate = SM_Settings::get_syndicate_info();
$appearance = SM_Settings::get_appearance();
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>فاتورة سداد رقم #<?php echo $payment->id; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        @page { size: A4; margin: 0; }
        body { font-family: 'Rubik', sans-serif; margin: 0; padding: 0; background: #f0f2f5; color: #333; }
        .invoice-box { width: 210mm; min-height: 297mm; padding: 20mm; margin: 0 auto; background: #fff; box-sizing: border-box; position: relative; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 4px solid <?php echo $appearance['primary_color']; ?>; padding-bottom: 20px; margin-bottom: 40px; }
        .logo { max-height: 80px; margin-bottom: 10px; }
        .syndicate-info h1 { margin: 0; font-size: 24px; color: <?php echo $appearance['dark_color']; ?>; }
        .invoice-title { text-align: left; }
        .invoice-title h2 { margin: 0; color: <?php echo $appearance['primary_color']; ?>; font-size: 32px; text-transform: uppercase; }
        .details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 50px; }
        .info-block h3 { font-size: 14px; text-transform: uppercase; color: #777; border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 15px; }
        .info-block p { margin: 5px 0; font-weight: 700; }
        .invoice-table { width: 100%; border-collapse: collapse; margin-bottom: 50px; }
        .invoice-table th { background: #f8f9fa; padding: 15px; text-align: right; border-bottom: 2px solid #dee2e6; color: #555; }
        .invoice-table td { padding: 15px; border-bottom: 1px solid #eee; }
        .total-section { display: flex; justify-content: flex-end; }
        .total-box { width: 250px; }
        .total-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
        .total-row.grand-total { border-bottom: none; color: <?php echo $appearance['primary_color']; ?>; font-size: 20px; font-weight: 900; }
        .footer { position: absolute; bottom: 20mm; left: 20mm; right: 20mm; text-align: center; border-top: 1px solid #eee; padding-top: 20px; font-size: 12px; color: #777; }
        @media print {
            body { background: none; }
            .invoice-box { box-shadow: none; margin: 0; width: 100%; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="position: fixed; top: 20px; right: 20px; z-index: 1000;">
        <button onclick="window.print()" style="padding: 12px 25px; background: <?php echo $appearance['primary_color']; ?>; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: 700; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">طباعة الفاتورة</button>
    </div>

    <div class="invoice-box">
        <div class="header">
            <div class="syndicate-info">
                <?php if ($syndicate['syndicate_logo']): ?>
                    <img src="<?php echo esc_url($syndicate['syndicate_logo']); ?>" class="logo">
                <?php endif; ?>
                <h1><?php echo esc_html($syndicate['syndicate_name']); ?></h1>
                <p><?php echo esc_html($syndicate['address']); ?></p>
                <p><?php echo esc_html($syndicate['phone']); ?></p>
            </div>
            <div class="invoice-title">
                <h2>فاتورة سداد رسمية</h2>
                <div style="background: #f8fafc; padding: 10px 20px; border-radius: 8px; border: 1px solid #e2e8f0; margin-top: 10px;">
                    <p style="margin: 0; font-weight: 900; color: #111F35; font-size: 18px;">رقم الفاتورة: <?php echo esc_html($payment->digital_invoice_code); ?></p>
                    <p style="margin: 5px 0 0 0; color: #718096; font-size: 12px;">تاريخ الإصدار: <?php echo date('Y-m-d', strtotime($payment->payment_date)); ?></p>
                </div>
            </div>
        </div>

        <div class="details-grid">
            <div class="info-block">
                <h3>بيانات العميل (العضو):</h3>
                <p><?php echo esc_html($member->name); ?></p>
                <p>الرقم القومي: <?php echo esc_html($member->national_id); ?></p>
                <p>رقم العضوية: <?php echo esc_html($member->membership_number); ?></p>
            </div>
            <div class="info-block">
                <h3>طريقة السداد:</h3>
                <p><?php
                    $types = ['membership' => 'اشتراك عضوية سنوي', 'license' => 'ترخيص مزاولة مهنة', 'facility' => 'ترخيص منشأة رياضية', 'penalty' => 'غرامة تأخير', 'other' => 'أخرى'];
                    echo $types[$payment->payment_type] ?? $payment->payment_type;
                ?></p>
                <p>حالة الدفع: <span style="color: #27ae60;">تم التحصيل بنجاح</span></p>
            </div>
        </div>

        <table class="invoice-table">
            <thead>
                <tr>
                    <th style="width: 50%;">بيان الخدمة / سبب المعاملة</th>
                    <th style="text-align: center;">السنة المالية</th>
                    <th style="text-align: center;">الكود المرجعي</th>
                    <th style="text-align: left;">القيمة المالية</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="font-weight: 700; color: var(--sm-dark-color);">
                        <?php
                        if (!empty($payment->details_ar)) {
                            echo esc_html($payment->details_ar);
                        } else {
                            echo $types[$payment->payment_type] ?? 'خدمات نقابية';
                        }
                        ?>
                    </td>
                    <td style="text-align: center; font-weight: 600;"><?php echo $payment->target_year ?: '-'; ?></td>
                    <td style="text-align: center; font-family: monospace; font-size: 11px;"><?php echo esc_html($payment->digital_invoice_code); ?></td>
                    <td style="text-align: left; font-weight: 900; color: #27ae60;"><?php echo number_format($payment->amount, 2); ?> ج.م</td>
                </tr>
            </tbody>
        </table>

        <div class="total-section">
            <div class="total-box">
                <div class="total-row">
                    <span>المبلغ الصافي:</span>
                    <span><?php echo number_format($payment->amount, 2); ?> ج.م</span>
                </div>
                <div class="total-row">
                    <span>الضريبة (0%):</span>
                    <span>0.00 ج.م</span>
                </div>
                <div class="total-row grand-total">
                    <span>الإجمالي الكلي:</span>
                    <span><?php echo number_format($payment->amount, 2); ?> ج.م</span>
                </div>
            </div>
        </div>

        <div style="margin-top: 60px; display: flex; justify-content: space-between;">
            <div style="text-align: center;">
                <p>توقيع المحاسب</p>
                <div style="height: 60px;"></div>
                <p>___________________</p>
            </div>
            <div style="text-align: center;">
                <p>ختم النقابة الرسمي</p>
                <div style="width: 100px; height: 100px; border: 2px dashed #eee; border-radius: 50%; margin: 10px auto;"></div>
            </div>
        </div>

        <div class="footer">
            <p>هذه فاتورة إلكترونية معتمدة صادرة عن نظام <?php echo esc_html($syndicate['syndicate_name']); ?>.</p>
            <p>شكراً لالتزامكم بمسؤولياتكم النقابية.</p>
        </div>
    </div>
</body>
</html>
