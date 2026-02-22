<?php if (!defined('ABSPATH')) exit; ?>
<?php
global $wpdb;

// Handle Form Submissions
if (isset($_POST['sm_save_notification_design'])) {
    check_admin_referer('sm_admin_action', 'sm_admin_nonce');
    $design = [
        'header_bg' => sanitize_text_field($_POST['header_bg']),
        'header_text' => sanitize_text_field($_POST['header_text']),
        'footer_text' => sanitize_text_field($_POST['footer_text']),
        'accent_color' => sanitize_text_field($_POST['accent_color'])
    ];
    update_option('sm_email_design_settings', $design);
    echo '<div class="sm-alert sm-alert-success">تم حفظ إعدادات التصميم بنجاح.</div>';
}

if (isset($_POST['sm_save_template'])) {
    check_admin_referer('sm_admin_action', 'sm_admin_nonce');
    SM_Notifications::save_template($_POST);
    echo '<div class="sm-alert sm-alert-success">تم تحديث القالب بنجاح.</div>';
}

$design = get_option('sm_email_design_settings', [
    'header_bg' => '#111F35',
    'header_text' => '#ffffff',
    'footer_text' => '#64748b',
    'accent_color' => '#F63049'
]);

$templates = [
    'membership_renewal' => 'تجديد العضوية السنوية',
    'license_practice' => 'تجديد تصريح المزاولة',
    'license_facility' => 'تجديد ترخيص المنشأة',
    'payment_reminder' => 'تنبيه المتأخرات المالية',
    'welcome_activation' => 'رسالة الترحيب والتفعيل',
    'admin_alert' => 'التنبيهات الإدارية العامة'
];
?>

<div class="sm-notifications-settings" dir="rtl">

    <div class="sm-tabs-wrapper" style="display: flex; gap: 10px; margin-bottom: 25px; border-bottom: 2px solid #eee; padding-bottom: 10px;">
        <button class="sm-tab-btn sm-active" onclick="smOpenSubTab('email-templates', this)">قوالب البريد</button>
        <button class="sm-tab-btn" onclick="smOpenSubTab('email-design', this)">تصميم الرسائل</button>
        <button class="sm-tab-btn" onclick="smOpenSubTab('email-logs', this)">سجل الرسائل المرسلة</button>
    </div>

    <!-- SubTab: Templates -->
    <div id="email-templates" class="sm-sub-tab">
        <div style="display: grid; grid-template-columns: 300px 1fr; gap: 30px;">
            <div style="background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0;">
                <h4 style="margin-top:0;">اختر القالب للتعديل:</h4>
                <ul style="list-style: none; padding: 0; margin: 0;">
                    <?php foreach ($templates as $type => $label): ?>
                        <li style="margin-bottom: 10px;">
                            <button onclick="smLoadTemplate('<?php echo $type; ?>')" class="sm-btn sm-btn-outline" style="width: 100%; text-align: right; justify-content: flex-start;">
                                <span class="dashicons dashicons-email-alt" style="margin-left: 10px;"></span> <?php echo $label; ?>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div style="margin-top: 20px; padding: 15px; background: #fff; border-radius: 8px; border: 1px solid #edf2f7; font-size: 11px; color: #718096;">
                    <strong>ملاحظة:</strong> يتم إرسال التنبيهات تلقائياً عبر نظام الجدولة (Cron Job) بشكل يومي.
                </div>
            </div>

            <div id="template-editor-container" style="background: #fff; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0;">
                <div id="template-empty-state" style="text-align: center; padding: 60px; color: #94a3b8;">
                    <span class="dashicons dashicons-edit" style="font-size: 48px; width: 48px; height: 48px;"></span>
                    <p>يرجى اختيار قالب من القائمة الجانبية للبدء في تخصيص محتواه.</p>
                </div>

                <form id="template-editor-form" method="post" style="display: none;">
                    <?php wp_nonce_field('sm_admin_action', 'sm_admin_nonce'); ?>
                    <input type="hidden" name="template_type" id="edit_template_type">

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px;">
                        <h4 id="edit_template_label" style="margin: 0; color: var(--sm-primary-color);"></h4>
                        <label class="sm-toggle" style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                            <input type="checkbox" name="is_enabled" id="edit_is_enabled" value="1">
                            <span style="font-size: 13px; font-weight: 700;">تفعيل التنبيه</span>
                        </label>
                    </div>

                    <div class="sm-form-group">
                        <label class="sm-label">عنوان الرسالة (Subject):</label>
                        <input type="text" name="subject" id="edit_subject" class="sm-input" required>
                    </div>

                    <div class="sm-form-group">
                        <label class="sm-label">نص الرسالة (Body):</label>
                        <textarea name="body" id="edit_body" class="sm-textarea" rows="10" required></textarea>
                        <div style="margin-top: 10px; display: flex; flex-wrap: wrap; gap: 5px;">
                            <span style="font-size: 11px; color: #666;">الرموز المتاحة: </span>
                            <code style="font-size: 10px; background: #eee; padding: 2px 5px; border-radius: 3px;">{member_name}</code>
                            <code style="font-size: 10px; background: #eee; padding: 2px 5px; border-radius: 3px;">{national_id}</code>
                            <code style="font-size: 10px; background: #eee; padding: 2px 5px; border-radius: 3px;">{membership_number}</code>
                            <code style="font-size: 10px; background: #eee; padding: 2px 5px; border-radius: 3px;">{expiry_date}</code>
                            <code style="font-size: 10px; background: #eee; padding: 2px 5px; border-radius: 3px;">{balance}</code>
                        </div>
                    </div>

                    <div class="sm-form-group" style="width: 200px;">
                        <label class="sm-label">إرسال قبل (يوم):</label>
                        <input type="number" name="days_before" id="edit_days_before" class="sm-input" min="0">
                        <p style="font-size: 10px; color: #999;">ضع 0 للإرسال الفوري عند الحدث.</p>
                    </div>

                    <div style="margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px;">
                        <button type="submit" name="sm_save_template" class="sm-btn" style="width: auto; padding: 12px 40px;">حفظ التغييرات</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- SubTab: Design -->
    <div id="email-design" class="sm-sub-tab" style="display: none;">
        <form method="post" style="max-width: 800px; background: #fff; padding: 30px; border-radius: 12px; border: 1px solid #e2e8f0;">
            <?php wp_nonce_field('sm_admin_action', 'sm_admin_nonce'); ?>
            <h4 style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 25px;">تخصيص مظهر رسائل البريد الإلكتروني</h4>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="sm-form-group">
                    <label class="sm-label">لون خلفية الهيدر:</label>
                    <input type="color" name="header_bg" value="<?php echo esc_attr($design['header_bg']); ?>" class="sm-input" style="height: 45px;">
                </div>
                <div class="sm-form-group">
                    <label class="sm-label">لون نص الهيدر:</label>
                    <input type="color" name="header_text" value="<?php echo esc_attr($design['header_text']); ?>" class="sm-input" style="height: 45px;">
                </div>
                <div class="sm-form-group">
                    <label class="sm-label">لون الخط المميز (Accent):</label>
                    <input type="color" name="accent_color" value="<?php echo esc_attr($design['accent_color']); ?>" class="sm-input" style="height: 45px;">
                </div>
                <div class="sm-form-group">
                    <label class="sm-label">لون نص الفوتر:</label>
                    <input type="color" name="footer_text" value="<?php echo esc_attr($design['footer_text']); ?>" class="sm-input" style="height: 45px;">
                </div>
            </div>

            <div style="margin-top: 20px; background: #f1f5f9; padding: 20px; border-radius: 8px;">
                <p style="font-size: 12px; margin: 0; color: #475569;">
                    * يتم استخدام شعار النقابة المرفوع في "تهيئة النظام" تلقائياً في أعلى الرسائل.
                    <br>* التصميم الحالي يدعم العرض المتجاوب على كافة الأجهزة (Responsive Design).
                </p>
            </div>

            <button type="submit" name="sm_save_notification_design" class="sm-btn" style="width: auto; margin-top: 25px; padding: 12px 40px;">حفظ إعدادات المظهر</button>
        </form>
    </div>

    <!-- SubTab: Logs -->
    <div id="email-logs" class="sm-sub-tab" style="display: none;">
        <div class="sm-table-container">
            <table class="sm-table">
                <thead>
                    <tr>
                        <th>التاريخ والوقت</th>
                        <th>العضو</th>
                        <th>البريد الإلكتروني</th>
                        <th>نوع التنبيه</th>
                        <th>العنوان</th>
                        <th>الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $logs = SM_Notifications::get_logs(50);
                    if (empty($logs)): ?>
                        <tr><td colspan="6" style="text-align: center; padding: 40px;">لا توجد سجلات بريد مرسلة حالياً.</td></tr>
                    <?php else:
                        foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo $log->sent_at; ?></td>
                                <td style="font-weight: 700;"><?php echo esc_html($log->member_name ?: '---'); ?></td>
                                <td><?php echo esc_html($log->recipient_email); ?></td>
                                <td><span class="sm-badge sm-badge-low"><?php echo $templates[$log->notification_type] ?? $log->notification_type; ?></span></td>
                                <td style="font-size: 12px;"><?php echo esc_html($log->subject); ?></td>
                                <td>
                                    <?php if ($log->status === 'success'): ?>
                                        <span style="color: #38a169; font-weight: 800;">✓ تم الإرسال</span>
                                    <?php else: ?>
                                        <span style="color: #e53e3e; font-weight: 800;">✖ فشل</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach;
                    endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
function smOpenSubTab(tabId, btn) {
    document.querySelectorAll('.sm-sub-tab').forEach(t => t.style.display = 'none');
    document.getElementById(tabId).style.display = 'block';
    btn.parentElement.querySelectorAll('.sm-tab-btn').forEach(b => b.classList.remove('sm-active'));
    btn.classList.add('sm-active');
}

function smLoadTemplate(type) {
    const labels = <?php echo json_encode($templates); ?>;
    const formData = new FormData();
    formData.append('action', 'sm_get_template_ajax');
    formData.append('type', type);

    fetch(ajaxurl, { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            const t = res.data;
            document.getElementById('template-empty-state').style.display = 'none';
            document.getElementById('template-editor-form').style.display = 'block';

            document.getElementById('edit_template_type').value = t.template_type;
            document.getElementById('edit_template_label').innerText = labels[t.template_type];
            document.getElementById('edit_subject').value = t.subject;
            document.getElementById('edit_body').value = t.body;
            document.getElementById('edit_days_before').value = t.days_before;
            document.getElementById('edit_is_enabled').checked = (t.is_enabled == 1);
        }
    });
}
</script>
