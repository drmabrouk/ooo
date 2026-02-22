<?php if (!defined('ABSPATH')) exit;

global $wpdb;

$user = wp_get_current_user();
$is_sys_manager = current_user_can('sm_full_access') || current_user_can('manage_options');

if (!$is_sys_manager) {
    echo '<div class="sm-alert sm-alert-danger">عذراً، هذا القسم مخصص لمدير النظام فقط ولا يمكن الوصول إليه.</div>';
    return;
}

$where = "1=1";

// Governorate filtering for non-global admins
if (!current_user_can('sm_full_access') && !current_user_can('manage_options')) {
    $my_gov = get_user_meta($user->ID, 'sm_governorate', true);
    if ($my_gov) {
        $where .= $wpdb->prepare(" AND EXISTS (SELECT 1 FROM {$wpdb->prefix}sm_members m WHERE m.id = p.member_id AND m.governorate = %s)", $my_gov);
    }
}

$day = isset($_GET['log_day']) ? intval($_GET['log_day']) : '';
$month = isset($_GET['log_month']) ? intval($_GET['log_month']) : '';
$year = isset($_GET['log_year']) ? intval($_GET['log_year']) : '';

if ($day) $where .= $wpdb->prepare(" AND DAY(p.payment_date) = %d", $day);
if ($month) $where .= $wpdb->prepare(" AND MONTH(p.payment_date) = %d", $month);
if ($year) $where .= $wpdb->prepare(" AND YEAR(p.payment_date) = %d", $year);

$search = isset($_GET['member_search']) ? sanitize_text_field($_GET['member_search']) : '';
if ($search) {
    $where .= $wpdb->prepare(" AND EXISTS (SELECT 1 FROM {$wpdb->prefix}sm_members m WHERE m.id = p.member_id AND (m.name LIKE %s OR m.national_id LIKE %s))", '%' . $wpdb->esc_like($search) . '%', '%' . $wpdb->esc_like($search) . '%');
}

$payments = $wpdb->get_results("SELECT p.*, u.display_name as staff_name FROM {$wpdb->prefix}sm_payments p LEFT JOIN {$wpdb->base_prefix}users u ON p.created_by = u.ID WHERE $where ORDER BY p.created_at DESC LIMIT 500");
$total_period_amount = array_reduce($payments, function($carry, $item) { return $carry + $item->amount; }, 0);
?>

<div class="sm-financial-logs" dir="rtl">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <div>
            <h3 style="margin:0;">سجل العمليات المالية الشامل</h3>
            <div style="font-size: 14px; color: #38a169; font-weight: 700; margin-top: 5px;">إجمالي المبالغ في هذه الفترة: <?php echo number_format($total_period_amount, 2); ?> ج.م</div>
        </div>
        <button onclick="location.reload()" class="sm-btn sm-btn-outline" style="width:auto;"><span class="dashicons dashicons-update"></span> تحديث السجل</button>
    </div>

    <!-- Filtering Bar -->
    <div style="background: #f1f5f9; padding: 20px; border-radius: 10px; margin-bottom: 25px; border: 1px solid #e2e8f0;">
        <form method="get" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
            <input type="hidden" name="sm_tab" value="financial-logs">
            <div style="flex: 1; min-width: 200px;">
                <label style="display:block; font-size:12px; margin-bottom:5px; font-weight:700;">بحث عن عضو:</label>
                <input type="text" name="member_search" value="<?php echo esc_attr($search); ?>" class="sm-input" placeholder="الاسم أو الرقم القومي...">
            </div>
            <div style="width: 80px;">
                <label style="display:block; font-size:12px; margin-bottom:5px; font-weight:700;">اليوم:</label>
                <select name="log_day" class="sm-select">
                    <option value="">الكل</option>
                    <?php for($i=1; $i<=31; $i++) echo "<option value='$i' ".selected($day, $i, false).">$i</option>"; ?>
                </select>
            </div>
            <div style="width: 120px;">
                <label style="display:block; font-size:12px; margin-bottom:5px; font-weight:700;">الشهر:</label>
                <select name="log_month" class="sm-select">
                    <option value="">الكل</option>
                    <?php
                    $months = ["يناير", "فبراير", "مارس", "أبريل", "مايو", "يونيو", "يوليو", "أغسطس", "سبتمبر", "أكتوبر", "نوفمبر", "ديسمبر"];
                    foreach($months as $i => $m) echo "<option value='".($i+1)."' ".selected($month, $i+1, false).">$m</option>";
                    ?>
                </select>
            </div>
            <div style="width: 100px;">
                <label style="display:block; font-size:12px; margin-bottom:5px; font-weight:700;">السنة:</label>
                <select name="log_year" class="sm-select">
                    <option value="">الكل</option>
                    <?php for($i=date('Y'); $i>=2020; $i--) echo "<option value='$i' ".selected($year, $i, false).">$i</option>"; ?>
                </select>
            </div>
            <button type="submit" class="sm-btn" style="width:auto; height:42px; padding: 0 25px;">تصفية النتائج</button>
            <a href="<?php echo remove_query_arg(['log_day', 'log_month', 'log_year', 'member_search']); ?>" class="sm-btn sm-btn-outline" style="width:auto; height:42px; text-decoration:none; display:flex; align-items:center;">إعادة ضبط</a>
        </form>
    </div>

    <div class="sm-table-container">
        <table class="sm-table">
            <thead>
                <tr>
                    <th>كود العملية</th>
                    <th>التاريخ والوقت</th>
                    <th>المسؤول</th>
                    <th>العضو</th>
                    <th>التفاصيل (بالعربية)</th>
                    <th>فاتورة رقمية</th>
                    <th>فاتورة ورقية</th>
                    <th>المبلغ</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payments)): ?>
                    <tr><td colspan="9" style="text-align:center; padding: 40px; color: #718096;">لا توجد عمليات مالية مسجلة بعد.</td></tr>
                <?php else: ?>
                    <?php foreach ($payments as $p):
                        $member = SM_DB::get_member_by_id($p->member_id);
                    ?>
                        <tr>
                            <td style="font-family: monospace; font-weight: 700; color: #111F35;">#<?php echo $p->id; ?></td>
                            <td style="font-size: 11px; color: #718096;"><?php echo $p->created_at; ?></td>
                            <td style="font-weight: 600; font-size: 12px;"><?php echo esc_html($p->staff_name ?: 'النظام'); ?></td>
                            <td style="font-weight: 700; font-size: 12px;"><?php echo esc_html($member->name ?? 'عضو محذوف'); ?></td>
                            <td style="font-size: 13px;"><?php echo esc_html($p->details_ar ?: $p->payment_type); ?></td>
                            <td style="font-size: 10px; color: #3182ce; font-family: monospace;"><?php echo esc_html($p->digital_invoice_code); ?></td>
                            <td style="font-size: 10px; color: #d69e2e; font-family: monospace; font-weight: 700;"><?php echo esc_html($p->paper_invoice_code ?: '---'); ?></td>
                            <td style="font-weight: 800; color: #38a169;"><?php echo number_format($p->amount, 2); ?></td>
                            <td>
                                <div style="display:flex; gap:5px;">
                                    <a href="<?php echo admin_url('admin-ajax.php?action=sm_print_invoice&payment_id='.$p->id); ?>" target="_blank" class="sm-btn" style="height:25px; padding:0 8px; font-size:10px; width:auto; background:#2c3e50; text-decoration:none; display:flex; align-items:center; gap:4px;"><span class="dashicons dashicons-pdf" style="font-size:14px;"></span> فاتورة</a>
                                    <?php if (current_user_can('sm_full_access') || current_user_can('manage_options')): ?>
                                        <button onclick="smDeleteTransaction(<?php echo $p->id; ?>)" class="sm-btn sm-btn-outline" style="color:#e53e3e; border-color:#feb2b2; padding:2px 8px; font-size:10px; width:auto;">حذف</button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function smDeleteTransaction(id) {
    if (!confirm('هل أنت متأكد من حذف هذه العملية المالية؟ سيتم إزالتها نهائياً من السجل.')) return;

    const formData = new FormData();
    formData.append('action', 'sm_delete_transaction_ajax');
    formData.append('transaction_id', id);
    formData.append('nonce', '<?php echo wp_create_nonce("sm_admin_action"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            smShowNotification('تم حذف العملية بنجاح');
            location.reload();
        } else {
            alert('خطأ: ' + res.data);
        }
    });
}
</script>
