<?php
if (!defined('ABSPATH')) exit;

$id = intval($_GET['id']);
global $wpdb;
$doc = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_pub_documents WHERE id = %d", $id));

if (!$doc) wp_die('المستند غير موجود');

$options = json_decode($doc->options, true);
$syndicate = SM_Settings::get_syndicate_info();
$doc_type = $options['doc_type'] ?? 'report';

// Determine styling based on type
$primary_color = '#111F35';
$border_style = '2px solid #111F35';

if ($doc_type === 'certificate') {
    $primary_color = '#b45309';
    $border_style = '8px double #b45309';
} elseif ($doc_type === 'statement') {
    $primary_color = '#047857';
    $border_style = '2px solid #047857';
}
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title><?php echo esc_html($doc->title); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Cairo:wght@400;700&family=Lateef&family=Aref+Ruqaa&family=Libre+Barcode+39&display=swap" rel="stylesheet">
    <style>
        @page {
            size: A4;
            margin: 15mm;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: 'Cairo', sans-serif;
            background: #f0f0f0;
            color: #333;
            line-height: 1.6;
        }

        /* Table Layout for Pagination */
        .doc-container {
            width: 210mm;
            margin: 10mm auto;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: relative;
            box-sizing: border-box;
        }

        .page-table {
            width: 100%;
            border-collapse: collapse;
        }

        .page-header-space { height: 120px; }
        .page-footer-space { height: 100px; }

        .page-header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 120px;
            background: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 40px;
            box-sizing: border-box;
            border-bottom: 2px solid <?php echo $primary_color; ?>;
            visibility: hidden; /* Hidden by default, shown in print */
            z-index: 1000;
        }

        .page-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 100px;
            background: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 40px;
            box-sizing: border-box;
            border-top: 1px solid #eee;
            visibility: hidden;
        }
        .page-number::after { content: "صفحة " counter(page); }

        /* Certificate specific styling */
        <?php if ($doc_type === 'certificate'): ?>
        .doc-title h1 { font-family: 'Amiri', serif; font-size: 55px; text-shadow: 2px 2px 4px rgba(0,0,0,0.1); margin-top: 20px; }
        .main-content { font-family: 'Amiri', serif; font-size: 24px; text-align: center; line-height: 2.2; margin-top: 50px; }
        .doc-container { background: #fffcf5; border: none; }
        .page::before {
            content: ''; position: absolute; top: 15mm; left: 15mm; right: 15mm; bottom: 15mm;
            border: 2px solid #b45309; pointer-events: none;
        }
        .certificate-seal {
            position: absolute; bottom: 100px; left: 100px; width: 120px; height: 120px;
            background: rgba(180, 83, 9, 0.1); border: 4px double #b45309; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; transform: rotate(-15deg);
            font-weight: bold; color: #b45309; font-size: 14px; text-align: center;
        }
        <?php endif; ?>

        /* Statement specific styling */
        <?php if ($doc_type === 'statement'): ?>
        .doc-title { text-align: right; border-bottom: 2px solid <?php echo $primary_color; ?>; padding-bottom: 10px; }
        .doc-title h1 { font-size: 24px; }
        .main-content { margin-top: 30px; }
        <?php endif; ?>

        /* Border frame styling */
        <?php
        $frame_type = $options['frame_type'] ?? 'none';
        if ($frame_type !== 'none'):
            $f_border = '2px solid #ccc';
            if ($frame_type === 'simple') $f_border = '2px solid ' . $primary_color;
            if ($frame_type === 'double') $f_border = '6px double ' . $primary_color;
            if ($frame_type === 'ornate') $f_border = '10px double ' . $primary_color;
        ?>
        .doc-container::after {
            content: ''; position: fixed; top: 8mm; left: 8mm; right: 8mm; bottom: 8mm;
            border: <?php echo $f_border; ?>; pointer-events: none; z-index: 999;
            visibility: hidden;
        }
        <?php if ($frame_type === 'ornate'): ?>
        .doc-container::before {
            content: ''; position: fixed; top: 12mm; left: 12mm; right: 12mm; bottom: 12mm;
            border: 1px solid <?php echo $primary_color; ?>; pointer-events: none; z-index: 999;
            visibility: hidden;
        }
        <?php endif; ?>
        <?php endif; ?>

        .content-body { padding: 20px 40px; }
        .doc-title { text-align: center; margin-bottom: 40px; color: <?php echo $primary_color; ?>; }
        .doc-title h1 { margin: 0; font-size: 32px; font-weight: 900; }

        .main-content { font-size: 17px; text-align: justify; }

        .codes-block { display: flex; gap: 15px; align-items: center; }

        @media print {
            body { background: none; }
            .doc-container { margin: 0; width: 100%; box-shadow: none; }
            .page-header, .page-footer { visibility: visible; }
            <?php if ($frame_type !== 'none'): ?>
            .doc-container::after { visibility: visible; }
            <?php if ($frame_type === 'ornate'): ?>
            .doc-container::before { visibility: visible; }
            <?php endif; ?>
            <?php endif; ?>
            .no-print { display: none; }

            thead { display: table-header-group; }
            tfoot { display: table-footer-group; }

            button { display: none; }
        }

        /* Screen Preview */
        .preview-header {
            display: flex; justify-content: space-between; align-items: center;
            padding: 20px 40px; border-bottom: 2px solid <?php echo $primary_color; ?>;
            margin-bottom: 20px;
        }
        .preview-footer {
            display: flex; justify-content: space-between; align-items: center;
            padding: 10px 40px; border-top: 1px solid #eee; margin-top: 20px;
        }
    </style>
</head>
<body>

<div class="no-print" style="position: fixed; top: 20px; right: 20px; z-index: 10000; display: flex; gap: 10px;">
    <button onclick="window.print()" style="padding: 12px 25px; background: #111F35; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; font-family: 'Cairo';">طباعة المستند الرسمي</button>
    <button onclick="window.close()" style="padding: 12px 25px; background: white; color: #111F35; border: 1px solid #111F35; border-radius: 8px; cursor: pointer; font-weight: bold; font-family: 'Cairo';">إغلاق</button>
</div>

<!-- Header for Printing (repeated) -->
<div class="page-header">
    <?php if (!empty($syndicate['authority_logo'])): ?>
        <img src="<?php echo esc_url($syndicate['authority_logo']); ?>" style="height: 60px;" alt="Authority Logo">
    <?php else: ?>
        <div></div>
    <?php endif; ?>

    <div style="text-align: center; flex: 1;">
        <h2 style="margin: 0; font-size: 18px; color: <?php echo $primary_color; ?>; font-weight: 900;"><?php echo esc_html($syndicate['syndicate_name']); ?></h2>
        <p style="margin: 3px 0 0 0; font-size: 11px; font-weight: bold; color: #666;"><?php echo esc_html($syndicate['authority_name']); ?></p>
    </div>

    <?php if (!empty($syndicate['syndicate_logo'])): ?>
        <img src="<?php echo esc_url($syndicate['syndicate_logo']); ?>" style="height: 70px;" alt="Syndicate Logo">
    <?php else: ?>
        <div></div>
    <?php endif; ?>
</div>

<!-- Footer for Printing (repeated) -->
<div class="page-footer">
    <div style="font-size: 10px; color: #666;">
        <p style="margin: 0;"><?php echo esc_html($syndicate['address']); ?></p>
        <p style="margin: 0;">هاتف: <?php echo esc_html($syndicate['phone']); ?> | بريد: <?php echo esc_html($syndicate['email']); ?></p>
        <?php if (!empty($syndicate['website_url'])): ?>
            <p style="margin: 0;">الموقع الإلكتروني: <?php echo esc_url($syndicate['website_url']); ?></p>
        <?php endif; ?>
        <p class="page-number" style="margin-top: 5px; font-weight: bold;"></p>
    </div>
    <div class="codes-block">
        <?php if (!empty($options['barcode'])): ?>
            <div style="text-align: center;">
                    <div style="font-family: 'Libre Barcode 39', cursive; font-size: 40px; line-height: 1;"><?php echo $doc->serial_number; ?></div>
                    <div style="font-size: 8px; font-family: monospace;"><?php echo $doc->serial_number; ?></div>
            </div>
        <?php endif; ?>
        <?php if (!empty($options['qr'])): ?>
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=<?php echo urlencode(home_url('/verify/?serial=' . $doc->serial_number)); ?>" style="width: 60px; height: 60px;" alt="QR">
        <?php endif; ?>
    </div>
</div>

<div class="doc-container">
    <table class="page-table">
        <thead>
            <tr><td><div class="page-header-space">&nbsp;</div></td></tr>
        </thead>

        <tbody>
            <tr>
                <td>
                    <div class="content-body">
                        <!-- Preview Header (Only visible on screen) -->
                        <div class="preview-header no-print">
                            <?php if (!empty($syndicate['authority_logo'])): ?>
                                <img src="<?php echo esc_url($syndicate['authority_logo']); ?>" style="height: 60px;" alt="Authority Logo">
                            <?php endif; ?>
                            <div style="text-align: center; flex: 1;">
                                <h2 style="margin: 0; font-size: 18px; color: <?php echo $primary_color; ?>; font-weight: 900;"><?php echo esc_html($syndicate['syndicate_name']); ?></h2>
                                <p style="margin: 3px 0 0 0; font-size: 11px; color: #666;"><?php echo esc_html($syndicate['authority_name']); ?></p>
                            </div>
                            <?php if (!empty($syndicate['syndicate_logo'])): ?>
                                <img src="<?php echo esc_url($syndicate['syndicate_logo']); ?>" style="height: 70px;" alt="Syndicate Logo">
                            <?php endif; ?>
                        </div>

                        <div class="doc-title">
                            <h1><?php echo esc_html($doc->title); ?></h1>
                            <div style="font-size: 12px; color: #999; margin-top: 5px;">رقم مرجعي: <?php echo $doc->serial_number; ?></div>
                        </div>

                        <div class="main-content">
                            <?php echo $doc->content; ?>
                        </div>

                        <?php if (!empty($options['fees']) && floatval($options['fees']) > 0): ?>
                            <div style="margin-top: 30px; padding: 15px; border: 1px dashed #ccc; border-radius: 8px; width: fit-content;">
                                <strong>الرسوم المسددة: </strong> <?php echo number_format($options['fees'], 2); ?> ج.م
                            </div>
                        <?php endif; ?>

                        <?php if ($doc_type === 'certificate'): ?>
                            <div class="certificate-seal">ختم النقابة<br>المعتمد</div>
                        <?php endif; ?>

                        <!-- Preview Footer -->
                        <div class="preview-footer no-print">
                            <div style="font-size: 10px; color: #666;">
                                <p style="margin: 0;"><?php echo esc_html($syndicate['address']); ?></p>
                                <p style="margin: 0;">هاتف: <?php echo esc_html($syndicate['phone']); ?></p>
                                <?php if (!empty($syndicate['website_url'])): ?>
                                    <p style="margin: 0;">الموقع: <?php echo esc_url($syndicate['website_url']); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="codes-block">
                                <?php if (!empty($options['qr'])): ?>
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=<?php echo urlencode(home_url('/verify/?serial=' . $doc->serial_number)); ?>" style="width: 50px; height: 50px;" alt="QR">
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
        </tbody>

        <tfoot>
            <tr><td><div class="page-footer-space">&nbsp;</div></td></tr>
        </tfoot>
    </table>
</div>

</body>
</html>
