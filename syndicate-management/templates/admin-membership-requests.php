<?php if (!defined('ABSPATH')) exit; ?>
<?php
global $wpdb;
$requests = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}sm_membership_requests WHERE status = 'pending' ORDER BY created_at DESC");
$govs = SM_Settings::get_governorates();
$grades = SM_Settings::get_professional_grades();
$specs = SM_Settings::get_specializations();
?>
<div class="sm-content-wrapper" dir="rtl">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h2 style="margin:0; font-weight: 800; color: var(--sm-dark-color);">طلبات العضوية الجديدة</h2>
        <div style="background: var(--sm-primary-color); color: #fff; padding: 5px 15px; border-radius: 20px; font-size: 12px; font-weight: 700;">
            بانتظار المراجعة: <?php echo count($requests); ?>
        </div>
    </div>

    <div class="sm-table-container">
        <table class="sm-table">
            <thead>
                <tr>
                    <th>الاسم الكامل</th>
                    <th>الرقم القومي</th>
                    <th>المحافظة</th>
                    <th>الدرجة / التخصص</th>
                    <th>بيانات التواصل</th>
                    <th>التاريخ</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requests)): ?>
                    <tr><td colspan="7" style="text-align: center; padding: 50px; color: #94a3b8;">لا توجد طلبات عضوية جديدة حالياً.</td></tr>
                <?php else: ?>
                    <?php foreach ($requests as $r): ?>
                        <tr>
                            <td style="font-weight: 800;"><?php echo esc_html($r->name); ?></td>
                            <td style="font-weight: 700; color: var(--sm-primary-color);"><?php echo esc_html($r->national_id); ?></td>
                            <td><?php echo esc_html($govs[$r->governorate] ?? $r->governorate); ?></td>
                            <td>
                                <div style="font-size: 12px; font-weight: 600;"><?php echo esc_html($grades[$r->professional_grade] ?? $r->professional_grade); ?></div>
                                <div style="font-size: 11px; color: #64748b;"><?php echo esc_html($specs[$r->specialization] ?? $r->specialization); ?></div>
                            </td>
                            <td>
                                <div style="font-size: 12px;"><?php echo esc_html($r->phone); ?></div>
                                <div style="font-size: 11px; color: #64748b;"><?php echo esc_html($r->email); ?></div>
                            </td>
                            <td><?php echo date('Y-m-d', strtotime($r->created_at)); ?></td>
                            <td>
                                <div style="display: flex; gap: 8px;">
                                    <button class="sm-btn" style="padding: 5px 15px; font-size: 11px; background: #27ae60;" onclick="processMembership(<?php echo $r->id; ?>, 'approved')">قبول</button>
                                    <button class="sm-btn" style="padding: 5px 15px; font-size: 11px; background: #e53e3e;" onclick="processMembership(<?php echo $r->id; ?>, 'rejected')">رفض</button>
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
function processMembership(requestId, status) {
    const label = status === 'approved' ? 'قبول' : 'رفض';
    if (!confirm(`هل أنت متأكد من ${label} هذا الطلب؟`)) return;

    const fd = new FormData();
    fd.append('action', 'sm_process_membership_request');
    fd.append('request_id', requestId);
    fd.append('status', status);
    fd.append('nonce', '<?php echo wp_create_nonce("sm_admin_action"); ?>');

    fetch(ajaxurl, { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            alert('تمت معالجة الطلب بنجاح');
            location.reload();
        } else {
            alert('خطأ: ' + res.data);
        }
    });
}
</script>
