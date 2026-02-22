<?php if (!defined('ABSPATH')) exit; ?>
<?php
$id = intval($_GET['id']);
global $wpdb;
$doc = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_pub_documents WHERE id = %d", $id));
if (!$doc) wp_die('Document not found');

$options = json_decode($doc->options, true);
$syndicate = SM_Settings::get_syndicate_info();
$stamp_url = get_option('sm_pub_stamp_url');
$footer_text = get_option('sm_pub_footer_statement');
$primary_color = get_option('sm_pub_color_primary', '#111F35');
?>
<!DOCTYPE html>
<html dir="rtl">
<head>
    <meta charset="UTF-8">
    <title><?php echo esc_html($doc->title); ?> - <?php echo $doc->serial_number; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Cairo', sans-serif; margin: 0; padding: 0; background: #f0f2f5; color: #333; }
        .page {
            width: 210mm;
            min-height: 297mm;
            padding: 25mm;
            margin: 10mm auto;
            background: #fff;
            position: relative;
            box-sizing: border-box;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }

        <?php if (!empty($options['frame'])): ?>
        .page::before {
            content: '';
            position: absolute;
            top: 10mm; left: 10mm; right: 10mm; bottom: 10mm;
            border: 8px double <?php echo $primary_color; ?>;
            pointer-events: none;
        }
        <?php endif; ?>

        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid <?php echo $primary_color; ?>; padding-bottom: 15px; margin-bottom: 40px; }
        .header-logo { height: 90px; }
        .header-text { text-align: center; flex: 1; }
        .header-text h2 { margin: 0; font-family: 'Amiri', serif; font-size: 24px; color: <?php echo $primary_color; ?>; }
        .header-info { text-align: left; font-size: 11px; color: #666; width: 150px; }

        .doc-title { text-align: center; margin-bottom: 50px; }
        .doc-title h1 { font-family: 'Amiri', serif; font-size: 32px; color: <?php echo $primary_color; ?>; margin: 0; position: relative; display: inline-block; }
        .doc-title h1::after { content: ''; display: block; width: 60%; height: 3px; background: <?php echo $primary_color; ?>; margin: 10px auto; opacity: 0.3; }

        .content {
            line-height: 2;
            font-size: 17px;
            min-height: 160mm;
            text-align: justify;
            color: #2d3748;
        }
        .content h1, .content h2, .content h3 { font-family: 'Amiri', serif; color: <?php echo $primary_color; ?>; }

        .footer {
            border-top: 1px solid #edf2f7;
            padding-top: 25px;
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            position: relative;
        }
        .footer-text { font-size: 12px; color: #718096; flex: 1; padding-left: 20px; }
        .footer-stamp-area { width: 180px; text-align: center; position: relative; }
        .stamp-img {
            width: 140px;
            opacity: 0.85;
            transform: rotate(-10deg);
            position: absolute;
            bottom: 10px;
            right: 20px;
            z-index: 5;
        }
        .signature-line { font-weight: 800; border-top: 1px solid #333; display: inline-block; padding-top: 5px; margin-top: 60px; min-width: 120px; }

        .qr-area { text-align: center; }
        .qr-placeholder {
            width: 80px; height: 80px;
            border: 1px solid #e2e8f0;
            display: flex; align-items: center; justify-content: center;
            font-size: 9px; color: #a0aec0; background: #f8fafc;
        }

        .watermark {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 100px;
            color: rgba(0,0,0,0.03);
            font-weight: 900;
            white-space: nowrap;
            pointer-events: none;
            z-index: 0;
            font-family: 'Amiri', serif;
        }

        @media print {
            body { background: none; }
            .page { margin: 0; border: none; box-shadow: none; width: 100%; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="background: #111F35; padding: 12px; text-align: center; position: sticky; top: 0; z-index: 100;">
        <button onclick="window.print()" style="padding: 10px 30px; cursor: pointer; background: #27ae60; color: #fff; border: none; border-radius: 6px; font-weight: 800;">طباعة المستند الرسمي (PDF)</button>
        <button onclick="window.close()" style="padding: 10px 20px; cursor: pointer; background: #fff; color: #111F35; border: none; border-radius: 6px; margin-right: 10px;">إغلاق</button>
    </div>

    <div class="page">
        <div class="watermark"><?php echo esc_html($syndicate['syndicate_name']); ?></div>

        <?php if (!empty($options['header'])): ?>
        <div class="header">
            <div class="header-right">
                <img src="<?php echo esc_url($syndicate['syndicate_logo']); ?>" class="header-logo">
            </div>
            <div class="header-text">
                <h2><?php echo esc_html($syndicate['syndicate_name']); ?></h2>
                <div style="font-size: 14px; color: #718096; margin-top: 5px;">إدارة الشؤون الإدارية والرقمنة</div>
            </div>
            <div class="header-info">
                <div>الرقم المرجعي: <strong><?php echo $doc->serial_number; ?></strong></div>
                <div>تاريخ التحرير: <?php echo date('Y-m-d', strtotime($doc->created_at)); ?></div>
                <div>الصفحة: 1 من 1</div>
            </div>
        </div>
        <?php endif; ?>

        <div class="doc-title">
            <h1><?php echo esc_html($doc->title); ?></h1>
        </div>

        <div class="content">
            <?php echo $doc->content; ?>
        </div>

        <div class="footer">
            <div class="footer-text">
                <?php if (!empty($options['footer'])): ?>
                    <p style="margin-bottom: 20px;"><?php echo nl2br(esc_html($footer_text)); ?></p>
                <?php endif; ?>

                <?php if (!empty($options['qr'])): ?>
                <div class="qr-area">
                    <div class="qr-placeholder">كود التحقق الرقمي<br>QR SECURE</div>
                    <div style="font-size: 9px; margin-top: 4px;"><?php echo $doc->serial_number; ?></div>
                </div>
                <?php endif; ?>
            </div>

            <div class="footer-stamp-area">
                <div style="font-weight: 800; font-size: 14px; margin-bottom: 10px;">يعتمد،،</div>
                <div class="signature-line">توقيع المسؤول</div>

                <?php if (!empty($options['footer']) && $stamp_url): ?>
                    <img src="<?php echo esc_url($stamp_url); ?>" class="stamp-img">
                <?php endif; ?>
            </div>
        </div>

        <div style="position: absolute; bottom: 15mm; left: 0; right: 0; text-align: center; font-size: 10px; color: #cbd5e0;">
            تم توليد هذا المستند آلياً عبر نظام إدارة النقابة الذكي - البوابة الرقمية
        </div>
    </div>

    <script>
        if (window.location.search.indexOf('autoprint=1') > -1) {
            window.onload = function() { window.print(); }
        }
    </script>
</body>
</html>
