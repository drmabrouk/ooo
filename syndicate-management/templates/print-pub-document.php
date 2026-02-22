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
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Cairo:wght@400;700&family=Lateef&family=Aref+Ruqaa&display=swap" rel="stylesheet">
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
            padding: 20px 40px;
            box-sizing: border-box;
            border-bottom: 2px solid <?php echo $primary_color; ?>;
            visibility: hidden; /* Hidden by default, shown in print */
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

        /* Border frame */
        <?php if (!empty($options['frame'])): ?>
        .doc-container::after {
            content: ''; position: fixed; top: 10mm; left: 10mm; right: 10mm; bottom: 10mm;
            border: <?php echo $border_style; ?>; pointer-events: none; z-index: 999;
            visibility: hidden;
        }
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
            <?php if (!empty($options['frame'])): ?>
            .doc-container::after { visibility: visible; }
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
    <div style="text-align: right;">
        <h2 style="margin: 0; font-size: 18px; color: <?php echo $primary_color; ?>;"><?php echo esc_html($syndicate['syndicate_name']); ?></h2>
        <p style="margin: 3px 0 0 0; font-size: 12px; font-weight: bold;"><?php echo esc_html($syndicate['authority_name']); ?></p>
    </div>
    <img src="<?php echo esc_url($syndicate['syndicate_logo']); ?>" style="height: 70px;" alt="Logo">
</div>

<!-- Footer for Printing (repeated) -->
<div class="page-footer">
    <div style="font-size: 10px; color: #666;">
        <p style="margin: 0;"><?php echo esc_html($syndicate['address']); ?></p>
        <p style="margin: 0;">هاتف: <?php echo esc_html($syndicate['phone']); ?> | بريد: <?php echo esc_html($syndicate['email']); ?></p>
    </div>
    <div class="codes-block">
        <?php if (!empty($options['barcode'])): ?>
            <div style="text-align: center;">
                <div style="font-family: monospace; font-size: 9px;"><?php echo $doc->serial_number; ?></div>
                <div style="height: 25px; width: 100px; background: #000; margin-top: 2px;"></div>
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
                            <div style="text-align: right;">
                                <h2 style="margin: 0; font-size: 18px; color: <?php echo $primary_color; ?>;"><?php echo esc_html($syndicate['syndicate_name']); ?></h2>
                                <p style="margin: 3px 0 0 0; font-size: 12px;"><?php echo esc_html($syndicate['authority_name']); ?></p>
                            </div>
                            <img src="<?php echo esc_url($syndicate['syndicate_logo']); ?>" style="height: 70px;" alt="Logo">
                        </div>

                        <div class="doc-title">
                            <h1><?php echo esc_html($doc->title); ?></h1>
                            <div style="font-size: 12px; color: #999; margin-top: 5px;">رقم مرجعي: <?php echo $doc->serial_number; ?></div>
                        </div>

                        <div class="main-content">
                            <?php echo $doc->content; ?>
                        </div>

                        <!-- Preview Footer -->
                        <div class="preview-footer no-print">
                            <div style="font-size: 10px; color: #666;">
                                <p style="margin: 0;"><?php echo esc_html($syndicate['address']); ?></p>
                                <p style="margin: 0;">هاتف: <?php echo esc_html($syndicate['phone']); ?></p>
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
