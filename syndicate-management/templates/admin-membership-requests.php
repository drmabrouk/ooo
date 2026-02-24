<?php if (!defined('ABSPATH')) exit; ?>
<?php
global $wpdb;
// Fetch all non-approved/non-rejected requests
$requests = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}sm_membership_requests WHERE status NOT IN ('approved', 'rejected') ORDER BY created_at DESC");
$govs = SM_Settings::get_governorates();
$univs = SM_Settings::get_universities();
$facs = SM_Settings::get_faculties();
$depts = SM_Settings::get_departments();
?>
<div class="sm-content-wrapper" dir="rtl">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <div>
            <h2 style="margin:0; font-weight: 800; color: var(--sm-dark-color);">طلبات العضوية والالتحاق</h2>
            <p style="margin:5px 0 0 0; color:#64748b; font-size:14px;">مراجعة طلبات الانضمام الجديدة، التحقق من الدفع، وفحص الوثائق الرقمية.</p>
        </div>
        <div style="background: var(--sm-primary-color); color: #fff; padding: 5px 15px; border-radius: 20px; font-size: 12px; font-weight: 700;">
            بانتظار الإجراء: <?php echo count($requests); ?>
        </div>
    </div>

    <div class="sm-table-container">
        <table class="sm-table">
            <thead>
                <tr>
                    <th>المتقدم والبيانات الأساسية</th>
                    <th>البيانات الأكاديمية</th>
                    <th>العنوان والتواصل</th>
                    <th>حالة الطلب ومرحلته</th>
                    <th>التحقق من الدفع والوثائق</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requests)): ?>
                    <tr><td colspan="6" style="text-align: center; padding: 50px; color: #94a3b8;">لا توجد طلبات معلقة حالياً.</td></tr>
                <?php else: ?>
                    <?php foreach ($requests as $r): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 800; color:var(--sm-dark-color);"><?php echo esc_html($r->name); ?></div>
                                <div style="font-size: 12px; font-weight: 700; color: var(--sm-primary-color); margin-top:4px;">
                                    <?php echo esc_html($r->national_id); ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-size: 12px; font-weight: 600;"><?php echo esc_html($univs[$r->university] ?? $r->university); ?></div>
                                <div style="font-size: 11px; color: #64748b;"><?php echo esc_html($facs[$r->faculty] ?? $r->faculty); ?> - <?php echo esc_html($depts[$r->department] ?? $r->department); ?></div>
                                <div style="font-size: 11px; color: #94a3b8;"><?php echo esc_html($r->graduation_date); ?></div>
                            </td>
                            <td>
                                <div style="font-size: 12px;"><?php echo esc_html($govs[$r->residence_governorate] ?? $r->residence_governorate); ?></div>
                                <div style="font-size: 11px; color: #64748b;"><?php echo esc_html($r->residence_city); ?>, <?php echo esc_html($r->residence_street); ?></div>
                                <div style="font-size: 12px; margin-top:5px; font-weight:600;"><?php echo esc_html($r->phone); ?></div>
                            </td>
                            <td>
                                <?php
                                $status_labels = [
                                    'Payment Under Review' => ['label' => 'مراجعة الدفع', 'color' => '#f59e0b'],
                                    'Payment Approved' => ['label' => 'تم قبول الدفع - انتظار الوثائق', 'color' => '#3b82f6'],
                                    'Awaiting Physical Documents' => ['label' => 'بانتظار الأصول (بعد الرقمية)', 'color' => '#8b5cf6'],
                                    'Under Final Review' => ['label' => 'قيد المراجعة النهائية', 'color' => '#10b981'],
                                    'pending' => ['label' => 'قيد المراجعة الأولية', 'color' => '#64748b']
                                ];
                                $s = $status_labels[$r->status] ?? ['label' => $r->status, 'color' => '#64748b'];
                                ?>
                                <span style="display:inline-block; padding:4px 10px; border-radius:6px; background:<?php echo $s['color']; ?>15; color:<?php echo $s['color']; ?>; font-size:11px; font-weight:700;">
                                    <?php echo $s['label']; ?>
                                </span>
                                <div style="font-size:10px; color:#94a3b8; margin-top:4px;">المرحلة: <?php echo $r->current_stage; ?> من 3</div>
                            </td>
                            <td>
                                <?php if($r->current_stage >= 2): ?>
                                    <div style="font-size:11px; margin-bottom:5px;">
                                        <strong>الدفع:</strong> <?php echo esc_html($r->payment_method); ?>
                                        <?php if($r->payment_screenshot_url): ?>
                                            <a href="<?php echo esc_url($r->payment_screenshot_url); ?>" target="_blank" title="إيصال الدفع">📸</a>
                                        <?php endif; ?><br>
                                        <strong>المرجع:</strong> <code style="background:#f1f5f9; padding:2px 4px;"><?php echo esc_html($r->payment_reference); ?></code>
                                    </div>
                                <?php endif; ?>
                                <?php if($r->current_stage == 3): ?>
                                    <div style="display:flex; gap:5px; margin-top:5px;">
                                        <?php if($r->doc_qualification_url): ?><a href="<?php echo esc_url($r->doc_qualification_url); ?>" target="_blank" title="شهادة التخرج" style="text-decoration:none; font-size:14px;">🎓</a><?php endif; ?>
                                        <?php if($r->doc_id_url): ?><a href="<?php echo esc_url($r->doc_id_url); ?>" target="_blank" title="البطاقة الشخصية" style="text-decoration:none; font-size:14px;">🪪</a><?php endif; ?>
                                        <?php if($r->doc_military_url): ?><a href="<?php echo esc_url($r->doc_military_url); ?>" target="_blank" title="شهادة العسكرية" style="text-decoration:none; font-size:14px;">🎖️</a><?php endif; ?>
                                        <?php if($r->doc_criminal_url): ?><a href="<?php echo esc_url($r->doc_criminal_url); ?>" target="_blank" title="فيش جنائي" style="text-decoration:none; font-size:14px;">📄</a><?php endif; ?>
                                        <?php if($r->doc_photo_url): ?><a href="<?php echo esc_url($r->doc_photo_url); ?>" target="_blank" title="الصورة الشخصية" style="text-decoration:none; font-size:14px;">👤</a><?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; flex-direction:column; gap: 5px;">
                                    <?php if($r->status === 'Payment Under Review'): ?>
                                        <button class="sm-btn" style="padding: 5px 10px; font-size: 11px; background: #27ae60;" onclick="processMembership(<?php echo $r->id; ?>, 'Payment Approved')">قبول الدفع</button>
                                    <?php elseif($r->status === 'Awaiting Physical Documents'): ?>
                                        <button class="sm-btn" style="padding: 5px 10px; font-size: 11px; background: #3b82f6;" onclick="processMembership(<?php echo $r->id; ?>, 'Under Final Review')">استلام الأصول</button>
                                    <?php elseif($r->status === 'Under Final Review'): ?>
                                        <button class="sm-btn" style="padding: 5px 10px; font-size: 11px; background: #27ae60;" onclick="processMembership(<?php echo $r->id; ?>, 'approved')">اعتماد نهائي</button>
                                    <?php endif; ?>

                                    <button class="sm-btn" style="padding: 5px 10px; font-size: 11px; background: #e53e3e;" onclick="rejectMembership(<?php echo $r->id; ?>)">رفض الطلب</button>
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
    let msg = "هل أنت متأكد من تغيير حالة الطلب؟";
    if(status === 'approved') msg = "هل أنت متأكد من القبول النهائي؟ سيتم إنشاء حساب عضو وتفعيل دخوله للنظام.";

    if (!confirm(msg)) return;

    const fd = new FormData();
    fd.append('action', 'sm_process_membership_request');
    fd.append('request_id', requestId);
    fd.append('status', status);
    fd.append('nonce', '<?php echo wp_create_nonce("sm_admin_action"); ?>');

    fetch(ajaxurl, { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            alert('تم تحديث حالة الطلب بنجاح');
            location.reload();
        } else {
            alert('خطأ: ' + res.data);
        }
    });
}

function rejectMembership(requestId) {
    const reason = prompt("يرجى إدخال سبب الرفض ليتمكن المتقدم من رؤيته:");
    if (reason === null) return;
    if (!reason) return alert("يجب إدخال سبب الرفض.");

    const fd = new FormData();
    fd.append('action', 'sm_process_membership_request');
    fd.append('request_id', requestId);
    fd.append('status', 'rejected');
    fd.append('reason', reason);
    fd.append('nonce', '<?php echo wp_create_nonce("sm_admin_action"); ?>');

    fetch(ajaxurl, { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            alert('تم رفض الطلب بنجاح');
            location.reload();
        } else {
            alert('خطأ: ' + res.data);
        }
    });
}
</script>
