<?php if (!defined('ABSPATH')) exit; ?>
<script>
/**
 * SYNDICATE MANAGEMENT - CORE UI ENGINE (ULTRA HARDENED V5)
 * Standard linking and routing fix.
 */
(function(window) {
    const SM_UI = {
        showNotification: function(message, isError = false) {
            const toast = document.createElement('div');
            toast.className = 'sm-toast';
            toast.style.cssText = "position:fixed; top:20px; left:50%; transform:translateX(-50%); background:white; padding:15px 30px; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,0.15); z-index:10001; display:flex; align-items:center; gap:10px; border-right:5px solid " + (isError ? '#e53e3e' : '#38a169');
            toast.innerHTML = `<strong>${isError ? '✖' : '✓'}</strong> <span>${message}</span>`;
            document.body.appendChild(toast);
            setTimeout(() => { toast.style.opacity = '0'; toast.style.transition = '0.5s'; setTimeout(() => toast.remove(), 500); }, 3000);
        },

        openInternalTab: function(tabId, element) {
            console.log('Opening tab:', tabId);
            const target = document.getElementById(tabId);
            if (!target || !element) {
                console.error('Target or element not found:', tabId, element);
                return;
            }
            const container = target.parentElement;
            container.querySelectorAll('.sm-internal-tab').forEach(p => p.style.setProperty('display', 'none', 'important'));
            target.style.setProperty('display', 'block', 'important');
            element.parentElement.querySelectorAll('.sm-tab-btn').forEach(b => b.classList.remove('sm-active'));
            element.classList.add('sm-active');
        }
    };

    window.smShowNotification = SM_UI.showNotification;
    window.smOpenInternalTab = SM_UI.openInternalTab;

    window.smViewLogDetails = function(log) {
        const detailsBody = document.getElementById('log-details-body');
        let detailsText = log.details;

        if (log.details.startsWith('ROLLBACK_DATA:')) {
            try {
                const data = JSON.parse(log.details.replace('ROLLBACK_DATA:', ''));
                detailsText = `<pre style="background:#f4f4f4; padding:10px; border-radius:5px; font-size:11px; overflow-x:auto;">${JSON.stringify(data, null, 2)}</pre>`;
            } catch(e) {
                detailsText = log.details;
            }
        }

        detailsBody.innerHTML = `
            <div style="display:grid; gap:15px;">
                <div><strong>المشغل:</strong> ${log.display_name || 'نظام'}</div>
                <div><strong>الوقت:</strong> ${log.created_at}</div>
                <div><strong>الإجراء:</strong> <span class="sm-badge sm-badge-low">${log.action}</span></div>
                <div><strong>بيانات العملية:</strong><br>${detailsText}</div>
            </div>
        `;
        document.getElementById('log-details-modal').style.display = 'flex';
    };

    window.smRollbackLog = function(logId) {
        if (!confirm('هل أنت متأكد من رغبتك في استعادة هذه البيانات؟ سيتم محاولة عكس العملية.')) return;

        const fd = new FormData();
        fd.append('action', 'sm_rollback_log_ajax');
        fd.append('log_id', logId);
        fd.append('nonce', '<?php echo wp_create_nonce("sm_admin_action"); ?>');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>?_=' + Date.now(), { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                smShowNotification('تمت الاستعادة بنجاح');
                setTimeout(() => location.reload(), 500);
            } else {
                alert('خطأ: ' + res.data);
            }
        });
    };

    window.smSubmitPayment = function(btn) {
        const form = document.getElementById('record-payment-form');
        const formData = new FormData(form);
        formData.append('action', 'sm_record_payment_ajax');
        formData.append('nonce', '<?php echo wp_create_nonce("sm_finance_action"); ?>');

        btn.disabled = true;
        btn.innerText = 'جاري المعالجة...';

        fetch('<?php echo admin_url('admin-ajax.php'); ?>?_=' + Date.now(), { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                smShowNotification('تم تسجيل الدفعة بنجاح');
                if (typeof smOpenFinanceModal === 'function') {
                    smOpenFinanceModal(form.querySelector('[name="member_id"]').value);
                } else {
                    location.reload();
                }
            } else {
                smShowNotification('خطأ: ' + res.data, true);
                btn.disabled = false;
                btn.innerText = 'تأكيد استلام المبلغ';
            }
        });
    };

    // MEDIA UPLOADER FOR LOGO
    window.smDeleteGovData = function() {
        const gov = document.getElementById('sm_gov_action_target').value;
        if (!gov) return alert('يرجى اختيار المحافظة أولاً');
        if (!confirm('هل أنت متأكد من حذف كافة بيانات محافظة ' + gov + '؟ لا يمكن التراجع عن هذا الإجراء.')) return;

        const fd = new FormData();
        fd.append('action', 'sm_delete_gov_data_ajax');
        fd.append('governorate', gov);
        fd.append('nonce', '<?php echo wp_create_nonce("sm_admin_action"); ?>');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>?_=' + Date.now(), { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                alert('تم حذف بيانات المحافظة بنجاح.');
                location.reload();
            } else alert('خطأ: ' + res.data);
        });
    };

    window.smMergeGovData = function(input) {
        const gov = document.getElementById('sm_gov_action_target').value;
        if (!gov) return alert('يرجى اختيار المحافظة أولاً لدمج البيانات إليها');
        if (!input.files.length) return;

        const fd = new FormData();
        fd.append('action', 'sm_merge_gov_data_ajax');
        fd.append('governorate', gov);
        fd.append('backup_file', input.files[0]);
        fd.append('nonce', '<?php echo wp_create_nonce("sm_admin_action"); ?>');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                alert('تم دمج البيانات بنجاح. التفاصيل: ' + res.data);
                location.reload();
            } else alert('خطأ: ' + res.data);
        });
    };

    window.smResetSystem = function() {
        const password = prompt('تحذير نهائي: سيتم مسح كافة بيانات النظام بالكامل. يرجى إدخال كلمة مرور مدير النظام للتأكيد:');
        if (!password) return;

        if (!confirm('هل أنت متأكد تماماً؟ لا يمكن التراجع عن هذا الإجراء.')) return;

        const fd = new FormData();
        fd.append('action', 'sm_reset_system_ajax');
        fd.append('admin_password', password);
        fd.append('nonce', '<?php echo wp_create_nonce("sm_admin_action"); ?>');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                alert('تمت إعادة تهيئة النظام بنجاح.');
                location.reload();
            } else {
                alert('خطأ: ' + res.data);
            }
        });
    };


    window.smDeleteLog = function(logId) {
        if (!confirm('هل أنت متأكد من حذف هذا السجل؟')) return;
        const fd = new FormData();
        fd.append('action', 'sm_delete_log');
        fd.append('log_id', logId);
        fd.append('nonce', '<?php echo wp_create_nonce("sm_admin_action"); ?>');
        fetch('<?php echo admin_url('admin-ajax.php'); ?>?_=' + Date.now(), { method: 'POST', body: fd })
        .then(r => r.json()).then(res => { if (res.success) location.reload(); });
    };

    window.smDeleteAllLogs = function() {
        if (!confirm('هل أنت متأكد من مسح كافة السجلات؟')) return;
        const fd = new FormData();
        fd.append('action', 'sm_clear_all_logs');
        fd.append('nonce', '<?php echo wp_create_nonce("sm_admin_action"); ?>');
        fetch('<?php echo admin_url('admin-ajax.php'); ?>?_=' + Date.now(), { method: 'POST', body: fd })
        .then(r => r.json()).then(res => { if (res.success) location.reload(); });
    };

    window.smOpenMediaUploader = function(inputId) {
        const frame = wp.media({
            title: 'اختر شعار النقابة',
            button: { text: 'استخدام هذا الشعار' },
            multiple: false
        });
        frame.on('select', function() {
            const attachment = frame.state().get('selection').first().toJSON();
            document.getElementById(inputId).value = attachment.url;
        });
        frame.open();
    };

    window.smToggleUserDropdown = function() {
        const menu = document.getElementById('sm-user-dropdown-menu');
        if (menu.style.display === 'none') {
            menu.style.display = 'block';
            document.getElementById('sm-profile-view').style.display = 'block';
            document.getElementById('sm-profile-edit').style.display = 'none';
            const notif = document.getElementById('sm-notifications-menu');
            if (notif) notif.style.display = 'none';
        } else {
            menu.style.display = 'none';
        }
    };

    window.smToggleNotifications = function() {
        const menu = document.getElementById('sm-notifications-menu');
        if (menu.style.display === 'none') {
            menu.style.display = 'block';
            const userMenu = document.getElementById('sm-user-dropdown-menu');
            if (userMenu) userMenu.style.display = 'none';
        } else {
            menu.style.display = 'none';
        }
    };

    window.smOpenFinanceModal = function(memberId) {
        const modal = document.getElementById('sm-finance-member-modal');
        const body = document.getElementById('sm-finance-modal-body');
        if (!modal || !body) return;
        modal.style.display = 'flex';
        body.innerHTML = '<div style="text-align:center; padding: 40px;">جاري تحميل البيانات...</div>';

        fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=sm_get_member_finance_html&member_id=' + memberId)
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                body.innerHTML = res.data.html;
            } else {
                body.innerHTML = '<div style="color:red; text-align:center; padding:20px;">' + res.data + '</div>';
            }
        });
    };

    window.smEditProfile = function() {
        document.getElementById('sm-profile-view').style.display = 'none';
        document.getElementById('sm-profile-edit').style.display = 'block';
    };

    window.smSaveProfile = function() {
        const name = document.getElementById('sm_edit_display_name').value;
        const email = document.getElementById('sm_edit_user_email').value;
        const pass = document.getElementById('sm_edit_user_pass').value;
        const nonce = '<?php echo wp_create_nonce("sm_profile_action"); ?>';

        const formData = new FormData();
        formData.append('action', 'sm_update_profile_ajax');
        formData.append('display_name', name);
        formData.append('user_email', email);
        formData.append('user_pass', pass);
        formData.append('nonce', nonce);

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                smShowNotification('تم تحديث الملف الشخصي بنجاح');
                setTimeout(() => location.reload(), 500);
            } else {
                smShowNotification('خطأ: ' + res.data, true);
            }
        });
    };

    document.addEventListener('click', function(e) {
        const dropdown = document.querySelector('.sm-user-dropdown');
        const menu = document.getElementById('sm-user-dropdown-menu');
        if (dropdown && !dropdown.contains(e.target)) {
            if (menu) menu.style.display = 'none';
        }
    });

})(window);
</script>

<?php
global $wpdb;
$user = wp_get_current_user();
$roles = (array)$user->roles;
$is_admin = in_array('administrator', $roles) || current_user_can('manage_options');
$is_sys_admin = in_array('sm_system_admin', $roles);
$is_syndicate_admin = in_array('sm_syndicate_admin', $roles);
$is_syndicate_member = in_array('sm_syndicate_member', $roles);
$is_member = in_array('sm_member', $roles);
$is_officer = $is_syndicate_admin || $is_syndicate_member;

$active_tab = isset($_GET['sm_tab']) ? sanitize_text_field($_GET['sm_tab']) : 'summary';
$is_restricted = $is_member || $is_syndicate_member;
if ($is_restricted && !in_array($active_tab, ['my-profile', 'member-profile', 'messaging', 'surveys'])) {
    $active_tab = 'my-profile';
}

$syndicate = SM_Settings::get_syndicate_info();
$labels = SM_Settings::get_labels();
$appearance = SM_Settings::get_appearance();
$stats = array();

if ($active_tab === 'summary') {
    $stats = SM_DB::get_statistics();
}

// Dynamic Greeting logic
$hour = (int)current_time('G');
$greeting = ($hour >= 5 && $hour < 12) ? 'صباح الخير' : 'مساء الخير';
?>

<div class="sm-admin-dashboard" dir="rtl" style="font-family: 'Rubik', sans-serif; background: <?php echo $appearance['bg_color']; ?>; border: 1px solid var(--sm-border-color); border-radius: 12px; overflow: hidden; color: <?php echo $appearance['font_color']; ?>; font-size: <?php echo $appearance['font_size']; ?>; font-weight: <?php echo $appearance['font_weight']; ?>; line-height: <?php echo $appearance['line_spacing']; ?>;">
    <!-- OFFICIAL SYSTEM HEADER -->
    <div class="sm-main-header">
        <div style="display: flex; align-items: center; gap: 20px;">
            <?php if (!empty($syndicate['syndicate_logo'])): ?>
                <div style="background: white; padding: 5px; border: 1px solid var(--sm-border-color); border-radius: 10px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                    <img src="<?php echo esc_url($syndicate['syndicate_logo']); ?>" style="height: 45px; width: auto; object-fit: contain; display: block;">
                </div>
            <?php else: ?>
                <div style="background: #f1f5f9; padding: 5px; border: 1px solid var(--sm-border-color); border-radius: 10px; height: 45px; width: 45px; display: flex; align-items: center; justify-content: center; color: #94a3b8;">
                    <span class="dashicons dashicons-building" style="font-size: 24px; width: 24px; height: 24px;"></span>
                </div>
            <?php endif; ?>
            <div>
                <h1 style="margin:0; border: none; padding: 0; color: var(--sm-dark-color); font-weight: 800; font-size: 1.3em; text-decoration: none; line-height: 1;">
                    <?php echo esc_html($syndicate['syndicate_name']); ?>
                </h1>
                <div style="display: inline-flex; flex-direction: column; align-items: center; padding: 5px 15px; background: #f0f4f8; color: #111F35; border-radius: 12px; font-size: 11px; font-weight: 700; margin-top: 6px; border: 1px solid #cbd5e0; line-height: 1.4;">
                    <div>
                        <?php
                        if ($is_admin || $is_sys_admin) echo 'مدير النظام';
                        elseif ($is_syndicate_admin) echo 'مسؤول نقابة';
                        elseif ($is_syndicate_member) echo 'عضو نقابة';
                        elseif ($is_member) echo 'عضو';
                        else echo 'مستخدم النظام';
                        ?>
                    </div>
                    <?php
                    $my_gov_key = get_user_meta($user->ID, 'sm_governorate', true);
                    $govs = SM_Settings::get_governorates();
                    $my_gov_label = $govs[$my_gov_key] ?? '';
                    if ($my_gov_label): ?>
                        <div style="width: 100%; height: 1px; background: #cbd5e0; margin: 3px 0;"></div>
                        <div style="color: var(--sm-primary-color); font-size: 10px;"><?php echo esc_html($my_gov_label); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 20px;">
            <div class="sm-header-info-box" style="text-align: right; border-left: 1px solid var(--sm-border-color); padding-left: 15px;">
                <div style="font-size: 0.85em; font-weight: 700; color: var(--sm-dark-color);"><?php echo date_i18n('l j F Y'); ?></div>
            </div>

            <?php if (current_user_can('sm_manage_licenses')): ?>
                <div style="display: flex; gap: 10px;">
                    <?php if ($is_sys_admin || $is_admin): ?>
                        <button onclick="window.location.href='<?php echo add_query_arg('sm_tab', 'global-archive'); ?>&sub_tab=finance'" class="sm-btn" style="background: #e67e22; height: 38px; font-size: 11px; color: white !important; width: auto;"><span class="dashicons dashicons-portfolio" style="font-size: 16px; margin-top: 4px;"></span> الأرشيف الرقمي</button>
                    <?php endif; ?>
                    <button onclick="window.location.href='<?php echo add_query_arg('sm_tab', 'practice-licenses'); ?>&action=new'" class="sm-btn" style="background: #2c3e50; height: 38px; font-size: 11px; color: white !important; width: auto;">+ إصدار ترخيص مزاولة</button>
                    <button onclick="window.location.href='<?php echo add_query_arg('sm_tab', 'facility-licenses'); ?>&action=new'" class="sm-btn" style="background: #27ae60; height: 38px; font-size: 11px; color: white !important; width: auto;">+ تسجيل منشأة جديدة</button>
                </div>
            <?php endif; ?>

            <div style="display: flex; gap: 15px; align-items: center; border-left: 1px solid var(--sm-border-color); padding-left: 20px;">
                <!-- Messages Icon -->
                <a href="<?php echo add_query_arg('sm_tab', 'messaging'); ?>" class="sm-header-circle-icon" title="المراسلات والشكاوى">
                    <span class="dashicons dashicons-email"></span>
                    <?php
                    $unread_msgs = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}sm_messages WHERE receiver_id = %d AND is_read = 0", $user->ID));
                    if ($unread_msgs > 0): ?>
                        <span class="sm-icon-badge" style="background: #e53e3e;"><?php echo $unread_msgs; ?></span>
                    <?php endif; ?>
                </a>

                <!-- Notifications Icon -->
                <div class="sm-notifications-dropdown" style="position: relative;">
                    <a href="javascript:void(0)" onclick="smToggleNotifications()" class="sm-header-circle-icon" title="التنبيهات">
                        <span class="dashicons dashicons-bell"></span>
                        <?php
                        // Simple dynamic alerts for notifications
                        $alerts = [];
                        if ($is_restricted) {
                            $member_by_wp = $wpdb->get_row($wpdb->prepare("SELECT id, last_paid_membership_year FROM {$wpdb->prefix}sm_members WHERE wp_user_id = %d", $user->ID));
                            if ($member_by_wp) {
                                if ($member_by_wp->last_paid_membership_year < date('Y')) {
                                    $alerts[] = 'يوجد متأخرات في تجديد العضوية السنوية';
                                }
                            }
                        }
                        if (current_user_can('sm_manage_members')) {
                            $pending_updates = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sm_update_requests WHERE status = 'pending'");
                            if ($pending_updates > 0) {
                                $alerts[] = 'يوجد ' . $pending_updates . ' طلبات تحديث بيانات بانتظار المراجعة';
                            }
                        }
                        if (count($alerts) > 0): ?>
                            <span class="sm-icon-dot" style="background: #f6ad55;"></span>
                        <?php endif; ?>
                    </a>
                    <div id="sm-notifications-menu" style="display: none; position: absolute; top: 150%; left: 0; background: white; border: 1px solid var(--sm-border-color); border-radius: 8px; width: 300px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); z-index: 1000; padding: 15px;">
                        <h4 style="margin: 0 0 10px 0; font-size: 14px; border-bottom: 1px solid #eee; padding-bottom: 8px;">التنبيهات والإشعارات</h4>
                        <?php if (empty($alerts)): ?>
                            <div style="font-size: 12px; color: #94a3b8; text-align: center; padding: 10px;">لا توجد تنبيهات جديدة حالياً</div>
                        <?php else: ?>
                            <?php foreach ($alerts as $a): ?>
                                <div style="font-size: 12px; padding: 8px; border-bottom: 1px solid #f9fafb; color: #4a5568; display: flex; gap: 8px; align-items: flex-start;">
                                    <span class="dashicons dashicons-warning" style="font-size: 16px; color: #d69e2e;"></span>
                                    <span><?php echo $a; ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="sm-user-dropdown" style="position: relative;">
                <div class="sm-user-profile-nav" onclick="smToggleUserDropdown()" style="display: flex; align-items: center; gap: 12px; background: white; padding: 6px 12px; border-radius: 50px; border: 1px solid var(--sm-border-color); cursor: pointer;">
                    <div style="text-align: right;">
                        <div style="font-size: 0.85em; font-weight: 700; color: var(--sm-dark-color);"><?php echo $greeting . '، ' . $user->display_name; ?></div>
                        <div style="font-size: 0.7em; color: #38a169;">متصل الآن <span class="dashicons dashicons-arrow-down-alt2" style="font-size: 10px; width: 10px; height: 10px;"></span></div>
                    </div>
                    <?php echo get_avatar($user->ID, 32, '', '', array('style' => 'border-radius: 50%; border: 2px solid var(--sm-primary-color);')); ?>
                </div>
                <div id="sm-user-dropdown-menu" style="display: none; position: absolute; top: 110%; left: 0; background: white; border: 1px solid var(--sm-border-color); border-radius: 8px; width: 260px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); z-index: 1000; animation: smFadeIn 0.2s ease-out; padding: 10px 0;">
                    <div id="sm-profile-view">
                        <div style="padding: 10px 20px; border-bottom: 1px solid #f0f0f0; margin-bottom: 5px;">
                            <div style="font-weight: 800; color: var(--sm-dark-color);"><?php echo $user->display_name; ?></div>
                            <div style="font-size: 11px; color: var(--sm-text-gray);"><?php echo $user->user_email; ?></div>
                        </div>
                        <?php if (!$is_member): ?>
                            <a href="javascript:smEditProfile()" class="sm-dropdown-item"><span class="dashicons dashicons-edit"></span> تعديل البيانات الشخصية</a>
                        <?php endif; ?>
                        <?php if ($is_member): ?>
                            <a href="javascript:smEditProfile()" class="sm-dropdown-item"><span class="dashicons dashicons-lock"></span> تغيير كلمة المرور</a>
                        <?php endif; ?>
                        <?php if ($is_admin): ?>
                            <a href="<?php echo add_query_arg('sm_tab', 'global-settings'); ?>" class="sm-dropdown-item"><span class="dashicons dashicons-admin-generic"></span> إعدادات النظام</a>
                        <?php endif; ?>
                        <a href="javascript:location.reload()" class="sm-dropdown-item"><span class="dashicons dashicons-update"></span> تحديث الصفحة</a>
                    </div>

                    <div id="sm-profile-edit" style="display: none; padding: 15px;">
                        <div style="font-weight: 800; margin-bottom: 15px; font-size: 13px; border-bottom: 1px solid #eee; padding-bottom: 10px;">تعديل الملف الشخصي</div>
                        <div class="sm-form-group" style="margin-bottom: 10px;">
                            <label class="sm-label" style="font-size: 11px;">الاسم المفضل:</label>
                            <input type="text" id="sm_edit_display_name" class="sm-input" style="padding: 8px; font-size: 12px;" value="<?php echo esc_attr($user->display_name); ?>" <?php if ($is_member) echo 'disabled style="background:#f1f5f9; cursor:not-allowed;"'; ?>>
                        </div>
                        <div class="sm-form-group" style="margin-bottom: 10px;">
                            <label class="sm-label" style="font-size: 11px;">البريد الإلكتروني:</label>
                            <input type="email" id="sm_edit_user_email" class="sm-input" style="padding: 8px; font-size: 12px;" value="<?php echo esc_attr($user->user_email); ?>" <?php if ($is_member) echo 'disabled style="background:#f1f5f9; cursor:not-allowed;"'; ?>>
                        </div>
                        <div class="sm-form-group" style="margin-bottom: 15px;">
                            <label class="sm-label" style="font-size: 11px;">كلمة مرور جديدة (اختياري):</label>
                            <input type="password" id="sm_edit_user_pass" class="sm-input" style="padding: 8px; font-size: 12px;" placeholder="********">
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <button onclick="smSaveProfile()" class="sm-btn" style="flex: 1; height: 32px; font-size: 11px; padding: 0;">حفظ</button>
                            <button onclick="document.getElementById('sm-profile-edit').style.display='none'; document.getElementById('sm-profile-view').style.display='block';" class="sm-btn sm-btn-outline" style="flex: 1; height: 32px; font-size: 11px; padding: 0;">إلغاء</button>
                        </div>
                    </div>

                    <hr style="margin: 5px 0; border: none; border-top: 1px solid #eee;">
                    <a href="<?php echo wp_logout_url(home_url('/sm-login')); ?>" class="sm-dropdown-item" style="color: #e53e3e;"><span class="dashicons dashicons-logout"></span> تسجيل الخروج</a>
                </div>
            </div>
        </div>
    </div>

    <div class="sm-admin-layout" style="display: flex; min-height: 800px;">
        <!-- SIDEBAR -->
        <?php $is_restricted = $is_member || $is_syndicate_member; ?>
        <div class="sm-sidebar" style="width: 280px; flex-shrink: 0; background: <?php echo $appearance['sidebar_bg_color']; ?>; border-left: 1px solid var(--sm-border-color); padding: 20px 0;">
            <ul style="list-style: none; padding: 0; margin: 0;">

                <?php if (!$is_restricted): ?>
                <li class="sm-sidebar-item <?php echo $active_tab == 'summary' ? 'sm-active' : ''; ?>">
                    <a href="<?php echo add_query_arg('sm_tab', 'summary'); ?>" class="sm-sidebar-link"><span class="dashicons dashicons-dashboard"></span> <?php echo $labels['tab_summary']; ?></a>
                </li>
                <?php endif; ?>

                <?php if ($is_restricted): ?>
                    <li class="sm-sidebar-item <?php echo in_array($active_tab, ['my-profile', 'member-profile']) ? 'sm-active' : ''; ?>">
                        <a href="<?php echo add_query_arg('sm_tab', 'my-profile'); ?>" class="sm-sidebar-link"><span class="dashicons dashicons-admin-users"></span> <?php echo $labels['tab_my_profile']; ?></a>
                    </li>
                <?php endif; ?>

                <?php if (!$is_restricted && ($is_admin || $is_sys_admin || $is_syndicate_admin)): ?>
                    <li class="sm-sidebar-item <?php echo in_array($active_tab, ['members', 'update-requests', 'membership-requests']) ? 'sm-active' : ''; ?>">
                        <a href="<?php echo add_query_arg('sm_tab', 'members'); ?>" class="sm-sidebar-link"><span class="dashicons dashicons-groups"></span> <?php echo $labels['tab_members']; ?></a>
                        <ul class="sm-sidebar-dropdown" style="display: <?php echo in_array($active_tab, ['members', 'update-requests', 'membership-requests']) ? 'block' : 'none'; ?>;">
                            <li><a href="<?php echo add_query_arg('sm_tab', 'members'); ?>" class="<?php echo $active_tab == 'members' ? 'sm-sub-active' : ''; ?>"><span class="dashicons dashicons-list-view"></span> قائمة الأعضاء</a></li>
                            <li><a href="<?php echo add_query_arg('sm_tab', 'membership-requests'); ?>" class="<?php echo $active_tab == 'membership-requests' ? 'sm-sub-active' : ''; ?>"><span class="dashicons dashicons-id-alt"></span> طلبات العضوية</a></li>
                            <li><a href="<?php echo add_query_arg('sm_tab', 'update-requests'); ?>" class="<?php echo $active_tab == 'update-requests' ? 'sm-sub-active' : ''; ?>"><span class="dashicons dashicons-edit"></span> طلبات التحديث</a></li>
                        </ul>
                    </li>
                <?php endif; ?>

                <?php if (!$is_restricted && ($is_admin || $is_sys_admin || $is_syndicate_admin)): ?>
                    <li class="sm-sidebar-item <?php echo $active_tab == 'finance' ? 'sm-active' : ''; ?>">
                        <a href="<?php echo add_query_arg('sm_tab', 'finance'); ?>" class="sm-sidebar-link"><span class="dashicons dashicons-money-alt"></span> المحاسبة والمالية</a>
                    </li>
                    <li class="sm-sidebar-item <?php echo in_array($active_tab, ['practice-licenses', 'facility-licenses']) ? 'sm-active' : ''; ?>">
                        <a href="<?php echo add_query_arg('sm_tab', 'practice-licenses'); ?>" class="sm-sidebar-link"><span class="dashicons dashicons-id-alt"></span> التراخيص والمنشآت</a>
                        <ul class="sm-sidebar-dropdown" style="display: <?php echo in_array($active_tab, ['practice-licenses', 'facility-licenses']) ? 'block' : 'none'; ?>;">
                            <li><a href="<?php echo add_query_arg('sm_tab', 'practice-licenses'); ?>" class="<?php echo $active_tab == 'practice-licenses' ? 'sm-sub-active' : ''; ?>"><span class="dashicons dashicons-id-alt"></span> <?php echo $labels['tab_practice_licenses']; ?></a></li>
                            <li><a href="<?php echo add_query_arg('sm_tab', 'facility-licenses'); ?>" class="<?php echo $active_tab == 'facility-licenses' ? 'sm-sub-active' : ''; ?>"><span class="dashicons dashicons-building"></span> <?php echo $labels['tab_facility_licenses']; ?></a></li>
                        </ul>
                    </li>
                <?php endif; ?>


                <?php if ($is_admin || $is_sys_admin || $is_syndicate_admin): ?>
                    <li class="sm-sidebar-item <?php echo $active_tab == 'printing' ? 'sm-active' : ''; ?>">
                        <a href="<?php echo add_query_arg('sm_tab', 'printing'); ?>" class="sm-sidebar-link"><span class="dashicons dashicons-printer"></span> مركز الطباعة والنشر</a>
                    </li>
                <?php endif; ?>

                <?php if ($is_admin || $is_sys_admin || $is_syndicate_admin || $is_syndicate_member || $is_member): ?>
                    <li class="sm-sidebar-item <?php echo $active_tab == 'digital-services' ? 'sm-active' : ''; ?>">
                        <a href="<?php echo add_query_arg('sm_tab', 'digital-services'); ?>" class="sm-sidebar-link"><span class="dashicons dashicons-cloud"></span> الخدمات الرقمية</a>
                    </li>
                    <?php if ($is_admin || $is_sys_admin || $is_syndicate_admin): ?>
                        <li class="sm-sidebar-item <?php echo $active_tab == 'global-archive' ? 'sm-active' : ''; ?>">
                            <a href="<?php echo add_query_arg('sm_tab', 'global-archive'); ?>" class="sm-sidebar-link"><span class="dashicons dashicons-portfolio"></span> الأرشيف الرقمي</a>
                        </li>
                    <?php endif; ?>
                    <li class="sm-sidebar-item <?php echo $active_tab == 'surveys' ? 'sm-active' : ''; ?>">
                        <a href="<?php echo add_query_arg('sm_tab', 'surveys'); ?>" class="sm-sidebar-link"><span class="dashicons dashicons-clipboard"></span> <?php echo $labels['tab_surveys']; ?></a>
                    </li>
                <?php endif; ?>

                <?php if ($is_admin || $is_sys_admin || $is_syndicate_admin): ?>
                    <li class="sm-sidebar-item <?php echo $active_tab == 'global-settings' ? 'sm-active' : ''; ?>">
                        <a href="<?php echo add_query_arg('sm_tab', 'global-settings'); ?>" class="sm-sidebar-link"><span class="dashicons dashicons-admin-generic"></span> إعدادات النظام</a>
                        <ul class="sm-sidebar-dropdown" style="display: <?php echo $active_tab == 'global-settings' ? 'block' : 'none'; ?>;">
                            <li><a href="<?php echo add_query_arg('sm_tab', 'global-settings'); ?>&sub=init" class="<?php echo (!isset($_GET['sub']) || $_GET['sub'] == 'init') ? 'sm-sub-active' : ''; ?>"><span class="dashicons dashicons-admin-tools"></span> تهيئة النظام</a></li>
                            <li><a href="<?php echo add_query_arg('sm_tab', 'global-settings'); ?>&sub=professional" class="<?php echo ($_GET['sub'] ?? '') == 'professional' ? 'sm-sub-active' : ''; ?>"><span class="dashicons dashicons-businessman"></span> الدرجات والتخصصات</a></li>
                            <li><a href="<?php echo add_query_arg('sm_tab', 'global-settings'); ?>&sub=finance" class="<?php echo ($_GET['sub'] ?? '') == 'finance' ? 'sm-sub-active' : ''; ?>"><span class="dashicons dashicons-money-alt"></span> الرسوم والغرامات</a></li>
                            <li><a href="<?php echo add_query_arg('sm_tab', 'global-settings'); ?>&sub=notifications" class="<?php echo ($_GET['sub'] ?? '') == 'notifications' ? 'sm-sub-active' : ''; ?>"><span class="dashicons dashicons-email"></span> التنبيهات والبريد</a></li>
                            <li><a href="<?php echo add_query_arg('sm_tab', 'global-settings'); ?>&sub=design" class="<?php echo ($_GET['sub'] ?? '') == 'design' ? 'sm-sub-active' : ''; ?>"><span class="dashicons dashicons-art"></span> التصميم والمظهر</a></li>
                        </ul>
                    </li>
                <?php endif; ?>

                <?php if ($is_admin || $is_sys_admin): ?>
                    <li class="sm-sidebar-item <?php echo $active_tab == 'advanced-settings' ? 'sm-active' : ''; ?>">
                        <a href="<?php echo add_query_arg('sm_tab', 'advanced-settings'); ?>" class="sm-sidebar-link" style="color: #c53030 !important;"><span class="dashicons dashicons-shield-alt"></span> الإعدادات المتقدمة</a>
                        <ul class="sm-sidebar-dropdown" style="display: <?php echo $active_tab == 'advanced-settings' ? 'block' : 'none'; ?>;">
                            <li><a href="<?php echo add_query_arg('sm_tab', 'advanced-settings'); ?>&sub=staff" class="<?php echo (!isset($_GET['sub']) || $_GET['sub'] == 'staff') ? 'sm-sub-active' : ''; ?>"><span class="dashicons dashicons-admin-users"></span> إدارة مستخدمي النظام</a></li>
                            <li><a href="<?php echo add_query_arg('sm_tab', 'advanced-settings'); ?>&sub=backup" class="<?php echo ($_GET['sub'] ?? '') == 'backup' ? 'sm-sub-active' : ''; ?>"><span class="dashicons dashicons-database-export"></span> مركز النسخ الاحتياطي</a></li>
                            <li><a href="<?php echo add_query_arg('sm_tab', 'advanced-settings'); ?>&sub=logs" class="<?php echo ($_GET['sub'] ?? '') == 'logs' ? 'sm-sub-active' : ''; ?>"><span class="dashicons dashicons-list-view"></span> سجل النشاطات (Activity Log)</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- CONTENT AREA -->
        <div class="sm-main-panel" style="flex: 1; min-width: 0; padding: 40px; background: #fff;">

            <?php
            switch ($active_tab) {
                case 'summary':
                    include SM_PLUGIN_DIR . 'templates/public-dashboard-summary.php';
                    break;

                case 'members':
                    if ($is_admin || current_user_can('sm_manage_members')) {
                        echo '<h3 style="margin-top:0;">إدارة الأعضاء</h3>';
                        include SM_PLUGIN_DIR . 'templates/admin-members.php';
                    }
                    break;

                case 'finance':
                    if ($is_admin || $is_sys_admin || $is_syndicate_admin) {
                        include SM_PLUGIN_DIR . 'templates/admin-finance.php';
                    }
                    break;

                case 'financial-logs':
                    if ($is_admin || $is_sys_admin || $is_syndicate_admin) {
                        include SM_PLUGIN_DIR . 'templates/admin-financial-logs.php';
                    }
                    break;

                case 'practice-licenses':
                    if ($is_admin || $is_sys_admin || $is_syndicate_admin) {
                        include SM_PLUGIN_DIR . 'templates/admin-practice-licenses.php';
                    }
                    break;

                case 'facility-licenses':
                    if ($is_admin || $is_sys_admin || $is_syndicate_admin) {
                        include SM_PLUGIN_DIR . 'templates/admin-facility-licenses.php';
                    }
                    break;


                case 'messaging':
                    include SM_PLUGIN_DIR . 'templates/messaging-center.php';
                    break;


                case 'member-profile':
                case 'my-profile':
                    if ($active_tab === 'my-profile') {
                        $member_by_wp = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$wpdb->prefix}sm_members WHERE wp_user_id = %d", get_current_user_id()));
                        if ($member_by_wp) $_GET['member_id'] = $member_by_wp->id;
                    }
                    include SM_PLUGIN_DIR . 'templates/admin-member-profile.php';
                    break;


                case 'printing':
                    if ($is_admin || current_user_can('sm_print_reports')) {
                        include SM_PLUGIN_DIR . 'templates/admin-publishing-center.php';
                    }
                    break;



                case 'surveys':
                    if ($is_admin || $is_sys_admin || $is_syndicate_admin) {
                        include SM_PLUGIN_DIR . 'templates/admin-surveys.php';
                    } elseif ($is_syndicate_member || $is_member) {
                        // Members see only active surveys to participate
                        include SM_PLUGIN_DIR . 'templates/public-dashboard-summary.php';
                    }
                    break;

                case 'digital-services':
                    include SM_PLUGIN_DIR . 'templates/admin-services.php';
                    break;

                case 'global-archive':
                    if ($is_admin || $is_sys_admin || $is_syndicate_admin) {
                        include SM_PLUGIN_DIR . 'templates/admin-global-archive.php';
                    }
                    break;

                case 'advanced-settings':
                    if ($is_admin || $is_sys_admin) {
                        $sub = $_GET['sub'] ?? 'staff';
                        ?>
                        <div class="sm-tabs-wrapper" style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #eee; overflow-x: auto; white-space: nowrap; padding-bottom: 10px;">
                            <button class="sm-tab-btn <?php echo ($sub == 'staff') ? 'sm-active' : ''; ?>" onclick="smOpenInternalTab('system-users-settings', this)">إدارة مستخدمي النظام</button>
                            <button class="sm-tab-btn <?php echo ($sub == 'backup') ? 'sm-active' : ''; ?>" onclick="smOpenInternalTab('backup-settings', this)">مركز النسخ الاحتياطي</button>
                            <button class="sm-tab-btn <?php echo ($sub == 'logs') ? 'sm-active' : ''; ?>" onclick="smOpenInternalTab('activity-logs', this)">سجل النشاطات</button>
                        </div>

                        <div id="system-users-settings" class="sm-internal-tab" style="display: <?php echo ($sub == 'staff') ? 'block' : 'none'; ?>;">
                            <?php include SM_PLUGIN_DIR . 'templates/admin-staff.php'; ?>
                        </div>

                        <div id="backup-settings" class="sm-internal-tab" style="display: <?php echo ($sub == 'backup') ? 'block' : 'none'; ?>;">
                            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:30px;">
                                <h4 style="margin-top:0;">مركز النسخ الاحتياطي وإدارة البيانات</h4>
                                <?php $backup_info = SM_Settings::get_last_backup_info(); ?>
                                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:30px;">
                                    <div style="background:white; padding:15px; border-radius:8px; border:1px solid #eee;">
                                        <div style="font-size:12px; color:#718096;">آخر تصدير ناجح:</div>
                                        <div style="font-weight:700; color:var(--sm-primary-color);"><?php echo $backup_info['export']; ?></div>
                                    </div>
                                    <div style="background:white; padding:15px; border-radius:8px; border:1px solid #eee;">
                                        <div style="font-size:12px; color:#718096;">آخر استيراد ناجح:</div>
                                        <div style="font-weight:700; color:var(--sm-secondary-color);"><?php echo $backup_info['import']; ?></div>
                                    </div>
                                </div>
                                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:20px;">
                                    <div style="background:white; padding:20px; border-radius:8px; border:1px solid #eee;">
                                        <h5 style="margin-top:0;">تصدير البيانات الشاملة</h5>
                                        <p style="font-size:12px; color:#666; margin-bottom:15px;">قم بتحميل نسخة كاملة من بيانات الأعضاء بصيغة JSON.</p>
                                        <div style="display:flex; gap:10px;">
                                            <form method="post">
                                                <?php wp_nonce_field('sm_admin_action', 'sm_admin_nonce'); ?>
                                                <button type="submit" name="sm_download_backup" class="sm-btn" style="background:#27ae60; width:auto;">تصدير الآن (JSON)</button>
                                            </form>
                                        </div>
                                    </div>
                                    <div style="background:white; padding:20px; border-radius:8px; border:1px solid #eee;">
                                        <h5 style="margin-top:0;">استيراد البيانات</h5>
                                        <p style="font-size:12px; color:#e53e3e; margin-bottom:15px;">تحذير: سيقوم الاستيراد بمسح البيانات الحالية واستبدالها بالنسخة المرفوعة.</p>
                                        <form method="post" enctype="multipart/form-data">
                                            <?php wp_nonce_field('sm_admin_action', 'sm_admin_nonce'); ?>
                                            <input type="file" name="backup_file" required style="margin-bottom:10px; font-size:11px;">
                                            <button type="submit" name="sm_restore_backup" class="sm-btn" style="background:#2980b9; width:auto;">بدء الاستيراد</button>
                                        </form>
                                    </div>

                                    <div style="background:white; padding:20px; border-radius:8px; border:1px solid #eee;">
                                        <h5 style="margin-top:0;">إدارة بيانات محافظة محددة</h5>
                                        <p style="font-size:12px; color:#666; margin-bottom:15px;">حذف أو دمج بيانات محافظة واحدة فقط دون المساس ببقية المحافظات.</p>
                                        <div style="display:flex; flex-direction:column; gap:10px;">
                                            <select id="sm_gov_action_target" class="sm-select" style="font-size:12px;">
                                                <option value="">-- اختر المحافظة --</option>
                                                <?php foreach(SM_Settings::get_governorates() as $k => $v) echo "<option value='$k'>$v</option>"; ?>
                                            </select>
                                            <div style="display:flex; gap:8px;">
                                                <button onclick="smDeleteGovData()" class="sm-btn" style="background:#e53e3e; width:auto; font-size:11px;">حذف بيانات المحافظة</button>
                                                <button onclick="document.getElementById('sm_gov_merge_file').click()" class="sm-btn" style="background:#805ad5; width:auto; font-size:11px;">دمج بيانات (JSON)</button>
                                                <input type="file" id="sm_gov_merge_file" style="display:none;" onchange="smMergeGovData(this)">
                                            </div>
                                        </div>
                                    </div>

                                    <div style="background:#fff5f5; padding:20px; border-radius:8px; border:1px solid #feb2b2; grid-column: 1 / -1;">
                                        <h5 style="margin-top:0; color:#c53030;">منطقة الخطر: إعادة تهيئة النظام</h5>
                                        <p style="font-size:12px; color:#c53030; margin-bottom:15px;">سيقوم هذا الإجراء بمسح كافة بيانات الأعضاء، الحسابات، المدفوعات، والنشاطات بشكل نهائي ولا يمكن التراجع عنه.</p>
                                        <button onclick="smResetSystem()" class="sm-btn" style="background:#e53e3e; width:auto; font-weight:800;">إعادة تهيئة النظام بالكامل (Reset)</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="activity-logs" class="sm-internal-tab" style="display: <?php echo ($sub == 'logs') ? 'block' : 'none'; ?>;">
                            <div style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:15px;">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                                    <div>
                                        <h4 style="margin:0; font-size:16px;">سجل نشاطات النظام الشامل</h4>
                                        <div style="font-size:11px; color:#718096;">آخر 200 نشاط مسجل في النظام.</div>
                                    </div>
                                    <div style="display:flex; gap:10px;">
                                        <form method="get" style="display:flex; gap:5px;">
                                            <input type="hidden" name="sm_tab" value="advanced-settings">
                                            <input type="hidden" name="sub" value="logs">
                                            <input type="text" name="log_search" value="<?php echo esc_attr($_GET['log_search'] ?? ''); ?>" placeholder="بحث في السجلات..." class="sm-input" style="width:200px; padding:5px 10px; font-size:12px;">
                                            <button type="submit" class="sm-btn" style="width:auto; padding:5px 15px; font-size:12px;">بحث</button>
                                        </form>
                                        <button onclick="smDeleteAllLogs()" class="sm-btn" style="background:#e53e3e; width:auto; font-size:12px; padding:5px 15px;">تفريغ السجل</button>
                                    </div>
                                </div>
                                <div class="sm-table-container" style="margin:0; overflow-x:auto;">
                                    <table class="sm-table" style="font-size:12px; width:100%;">
                                        <thead>
                                            <tr style="background:#f8fafc;">
                                                <th style="padding:8px; width:140px;">الوقت</th>
                                                <th style="padding:8px; width:120px;">المستخدم</th>
                                                <th style="padding:8px; width:120px;">الإجراء</th>
                                                <th style="padding:8px;">التفاصيل</th>
                                                <th style="padding:8px; width:100px;">إجراءات</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $limit = 25;
                                            $page_num = isset($_GET['log_page']) ? max(1, intval($_GET['log_page'])) : 1;
                                            $offset = ($page_num - 1) * $limit;
                                            $search = sanitize_text_field($_GET['log_search'] ?? '');
                                            $all_logs = SM_Logger::get_logs($limit, $offset, $search);
                                            $total_logs = SM_Logger::get_total_logs($search);
                                            $total_pages = ceil($total_logs / $limit);

                                            if (empty($all_logs)): ?>
                                                <tr><td colspan="5" style="text-align:center; padding:20px; color:#94a3b8;">لا توجد سجلات تطابق البحث</td></tr>
                                            <?php endif;

                                            foreach ($all_logs as $log):
                                                $can_rollback = strpos($log->details, 'ROLLBACK_DATA:') === 0;
                                                $details_display = $can_rollback ? 'عملية تتضمن بيانات للاستعادة' : esc_html($log->details);
                                            ?>
                                                <tr style="border-bottom: 1px solid #f1f5f9;">
                                                    <td style="padding:6px 8px; color: #718096;"><?php echo esc_html($log->created_at); ?></td>
                                                    <td style="padding:6px 8px; font-weight: 600;"><?php echo esc_html($log->display_name ?: 'نظام'); ?></td>
                                                    <td style="padding:6px 8px;"><span style="background:<?php echo $appearance['primary_color']; ?>15; color:<?php echo $appearance['primary_color']; ?>; padding:2px 6px; border-radius:4px; font-weight:700;"><?php echo esc_html($log->action); ?></span></td>
                                                    <td style="padding:6px 8px; color:#4a5568; line-height:1.4;"><?php echo mb_strimwidth($details_display, 0, 100, "..."); ?></td>
                                                    <td style="padding:6px 8px;">
                                                        <div style="display:flex; gap:5px;">
                                                            <button onclick='smViewLogDetails(<?php echo json_encode($log); ?>)' class="sm-btn sm-btn-outline" style="padding:2px 8px; font-size:10px;">التفاصيل</button>
                                                            <?php if ($can_rollback): ?>
                                                                <button onclick="smRollbackLog(<?php echo $log->id; ?>)" class="sm-btn" style="padding:2px 8px; font-size:10px; background:#38a169;">استعادة</button>
                                                            <?php endif; ?>
                                                            <button onclick="smDeleteLog(<?php echo $log->id; ?>)" class="sm-btn" style="padding:2px 8px; font-size:10px; background:#e53e3e;">حذف</button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php if ($total_pages > 1): ?>
                                    <div style="display:flex; justify-content:center; gap:10px; margin-top:20px;">
                                        <?php if ($page_num > 1): ?>
                                            <a href="<?php echo add_query_arg('log_page', $page_num - 1); ?>" class="sm-btn sm-btn-outline" style="width:auto; padding:5px 15px; text-decoration:none;">السابق</a>
                                        <?php endif; ?>
                                        <span style="align-self:center; font-size:13px;">صفحة <?php echo $page_num; ?> من <?php echo $total_pages; ?></span>
                                        <?php if ($page_num < $total_pages): ?>
                                            <a href="<?php echo add_query_arg('log_page', $page_num + 1); ?>" class="sm-btn sm-btn-outline" style="width:auto; padding:5px 15px; text-decoration:none;">التالي</a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php
                    }
                    break;

                case 'membership-requests':
                    if ($is_admin || $is_sys_admin || $is_syndicate_admin) {
                        include SM_PLUGIN_DIR . 'templates/admin-membership-requests.php';
                    }
                    break;

                case 'update-requests':
                    if ($is_admin || $is_sys_admin || $is_syndicate_admin) {
                        include SM_PLUGIN_DIR . 'templates/admin-update-requests.php';
                    }
                    break;

                case 'global-settings':
                    if ($is_admin || $is_sys_admin || $is_syndicate_admin) {
                        $sub = $_GET['sub'] ?? 'init';
                        ?>
                        <div class="sm-tabs-wrapper" style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #eee; overflow-x: auto; white-space: nowrap; padding-bottom: 10px;">
                            <button class="sm-tab-btn <?php echo $sub == 'init' ? 'sm-active' : ''; ?>" onclick="smOpenInternalTab('syndicate-settings', this)">تهيئة النظام</button>
                            <button class="sm-tab-btn <?php echo $sub == 'professional' ? 'sm-active' : ''; ?>" onclick="smOpenInternalTab('professional-settings', this)">الدرجات والتخصصات</button>
                            <button class="sm-tab-btn <?php echo $sub == 'finance' ? 'sm-active' : ''; ?>" onclick="smOpenInternalTab('finance-settings', this)">الرسوم والغرامات</button>
                            <button class="sm-tab-btn <?php echo $sub == 'notifications' ? 'sm-active' : ''; ?>" onclick="smOpenInternalTab('notification-settings', this)">التنبيهات والبريد</button>
                            <button class="sm-tab-btn <?php echo $sub == 'design' ? 'sm-active' : ''; ?>" onclick="smOpenInternalTab('design-settings', this)">التصميم والمظهر</button>
                        </div>

                        <div id="syndicate-settings" class="sm-internal-tab" style="display: <?php echo ($sub == 'init') ? 'block' : 'none'; ?>;">
                            <form method="post">
                                <?php wp_nonce_field('sm_admin_action', 'sm_admin_nonce'); ?>

                                <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 25px; margin-bottom: 25px; box-shadow: var(--sm-shadow);">
                                    <h4 style="margin-top:0; border-bottom:2px solid #f1f5f9; padding-bottom:12px; color: var(--sm-dark-color); display: flex; align-items: center; gap: 10px;">
                                        <span class="dashicons dashicons-building"></span> بيانات السلطة العليا (Authority Data)
                                    </h4>
                                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-top:15px;">
                                        <div class="sm-form-group">
                                            <label class="sm-label">اسم السلطة المشرفة:</label>
                                            <input type="text" name="authority_name" value="<?php echo esc_attr($syndicate['authority_name'] ?? ''); ?>" class="sm-input" placeholder="مثال: وزارة الشباب والرياضة">
                                        </div>
                                        <div class="sm-form-group">
                                            <label class="sm-label">شعار السلطة:</label>
                                            <div style="display:flex; gap:10px;">
                                                <input type="text" name="authority_logo" id="sm_authority_logo_url" value="<?php echo esc_attr($syndicate['authority_logo'] ?? ''); ?>" class="sm-input">
                                                <button type="button" onclick="smOpenMediaUploader('sm_authority_logo_url')" class="sm-btn" style="width:auto; font-size:12px; background:#4a5568;">اختيار</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 25px; margin-bottom: 25px; box-shadow: var(--sm-shadow);">
                                    <h4 style="margin-top:0; border-bottom:2px solid #f1f5f9; padding-bottom:12px; color: var(--sm-dark-color); display: flex; align-items: center; gap: 10px;">
                                        <span class="dashicons dashicons-groups"></span> بيانات النقابة (Union Data)
                                    </h4>
                                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-top:15px;">
                                        <div class="sm-form-group"><label class="sm-label">اسم النقابة كاملاً:</label><input type="text" name="syndicate_name" value="<?php echo esc_attr($syndicate['syndicate_name']); ?>" class="sm-input"></div>
                                        <div class="sm-form-group"><label class="sm-label">اسم رئيس النقابة / المسؤول:</label><input type="text" name="syndicate_officer_name" value="<?php echo esc_attr($syndicate['syndicate_officer_name'] ?? ''); ?>" class="sm-input"></div>
                                        <div class="sm-form-group"><label class="sm-label">رقم التواصل الموحد:</label><input type="text" name="syndicate_phone" value="<?php echo esc_attr($syndicate['phone']); ?>" class="sm-input"></div>
                                        <div class="sm-form-group"><label class="sm-label">البريد الإلكتروني الرسمي:</label><input type="email" name="syndicate_email" value="<?php echo esc_attr($syndicate['email']); ?>" class="sm-input"></div>
                                        <div class="sm-form-group" style="grid-column: span 2;"><label class="sm-label">العنوان الجغرافي للمقر الرئيسي:</label><input type="text" name="syndicate_address" value="<?php echo esc_attr($syndicate['address']); ?>" class="sm-input"></div>
                                        <div class="sm-form-group" style="grid-column: span 2;">
                                            <label class="sm-label">شعار النقابة الرسمي (Official Logo):</label>
                                            <div style="display:flex; gap:10px;">
                                                <input type="text" name="syndicate_logo" id="sm_syndicate_logo_url" value="<?php echo esc_attr($syndicate['syndicate_logo']); ?>" class="sm-input">
                                                <button type="button" onclick="smOpenMediaUploader('sm_syndicate_logo_url')" class="sm-btn" style="width:auto; font-size:12px; background:#4a5568;">اختيار الشعار</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div style="background: #f8fafc; border: 1px solid #cbd5e0; border-radius: 12px; padding: 25px; margin-bottom: 25px;">
                                    <h4 style="margin-top:0; border-bottom:2px solid #cbd5e0; padding-bottom:12px; color: var(--sm-dark-color); display: flex; align-items: center; gap: 10px;">
                                        <span class="dashicons dashicons-admin-settings"></span> مسميات أقسام النظام (Section Labels)
                                    </h4>
                                    <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:15px; margin-top:15px;">
                                        <?php foreach($labels as $key => $val): ?>
                                            <div class="sm-form-group">
                                                <label class="sm-label" style="font-size:11px;"><?php echo str_replace('tab_', '', $key); ?>:</label>
                                                <input type="text" name="<?php echo $key; ?>" value="<?php echo esc_attr($val); ?>" class="sm-input" style="padding:10px; font-size:13px; border-color: #cbd5e0;">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div style="position: sticky; bottom: 0; background: rgba(255,255,255,0.9); padding: 15px 0; border-top: 1px solid #eee; z-index: 10;">
                                    <button type="submit" name="sm_save_settings_unified" class="sm-btn" style="width:auto; height:50px; padding: 0 50px; font-size: 1.1em; font-weight: 800; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">حفظ كافة الإعدادات والتهيئة</button>
                                </div>
                            </form>
                        </div>

                        <div id="professional-settings" class="sm-internal-tab" style="display: <?php echo ($sub == 'professional') ? 'block' : 'none'; ?>;">
                            <form method="post">
                                <?php wp_nonce_field('sm_admin_action', 'sm_admin_nonce'); ?>
                                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:30px;">
                                    <div class="sm-form-group">
                                        <label class="sm-label">الدرجات الوظيفية (درجة واحدة في كل سطر):</label>
                                        <textarea name="professional_grades" class="sm-textarea" rows="8"><?php
                                            foreach (SM_Settings::get_professional_grades() as $k => $v) echo "$k|$v\n";
                                        ?></textarea>
                                        <p style="font-size:11px; color:#666; margin-top:5px;">التنسيق: key|Label (مثال: expert|خبير)</p>
                                    </div>
                                    <div class="sm-form-group">
                                        <label class="sm-label">التخصصات المهنية (تخصص واحد في كل سطر):</label>
                                        <textarea name="specializations" class="sm-textarea" rows="8"><?php
                                            foreach (SM_Settings::get_specializations() as $k => $v) echo "$k|$v\n";
                                        ?></textarea>
                                        <p style="font-size:11px; color:#666; margin-top:5px;">التنسيق: key|Label (مثال: massage|تدليك رياضي)</p>
                                    </div>
                                </div>
                                <button type="submit" name="sm_save_professional_options" class="sm-btn" style="width:auto; margin-top:10px;">حفظ الخيارات المهنية</button>
                            </form>
                        </div>

                        <div id="finance-settings" class="sm-internal-tab" style="display: <?php echo $sub == 'finance' ? 'block' : 'none'; ?>;">
                            <?php
                            $fin = SM_Settings::get_finance_settings();
                            ?>
                            <form method="post" style="max-width: 800px;">
                                <?php wp_nonce_field('sm_admin_action', 'sm_admin_nonce'); ?>
                                <h4 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px;">تخصيص رسوم الخدمات والغرامات</h4>

                                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:25px; margin-top:20px;">
                                    <div style="grid-column: span 2; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0;">
                                        <h5 style="margin:0 0 10px 0;">💳 اشتراكات العضوية السنوية</h5>
                                        <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:15px;">
                                            <div class="sm-form-group"><label class="sm-label">قيد جديد:</label><input type="number" name="membership_new" value="<?php echo $fin['membership_new']; ?>" class="sm-input"></div>
                                            <div class="sm-form-group"><label class="sm-label">تجديد سنوي:</label><input type="number" name="membership_renewal" value="<?php echo $fin['membership_renewal']; ?>" class="sm-input"></div>
                                            <div class="sm-form-group"><label class="sm-label">غرامة تأخير:</label><input type="number" name="membership_penalty" value="<?php echo $fin['membership_penalty']; ?>" class="sm-input"></div>
                                        </div>
                                        <p style="font-size: 11px; color: #e53e3e; margin-top: 5px;">* ملاحظة: تطبق غرامة التأخير تلقائياً بعد انتهاء فترة السماح (1 أبريل من كل عام).</p>
                                    </div>

                                    <div style="background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0;">
                                        <h5 style="margin:0 0 10px 0;">📜 تراخيص مزاولة المهنة</h5>
                                        <div class="sm-form-group"><label class="sm-label">إصدار لأول مرة:</label><input type="number" name="license_new" value="<?php echo $fin['license_new']; ?>" class="sm-input"></div>
                                        <div class="sm-form-group"><label class="sm-label">تجديد (كل سنتين):</label><input type="number" name="license_renewal" value="<?php echo $fin['license_renewal']; ?>" class="sm-input"></div>
                                        <div class="sm-form-group"><label class="sm-label">غرامة تأخير سنوية:</label><input type="number" name="license_penalty" value="<?php echo $fin['license_penalty']; ?>" class="sm-input"></div>
                                    </div>

                                    <div style="background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0;">
                                        <h5 style="margin:0 0 10px 0;">🏢 تراخيص المنشآت (حسب الفئة)</h5>
                                        <div class="sm-form-group"><label class="sm-label">الفئة (A):</label><input type="number" name="facility_a" value="<?php echo $fin['facility_a']; ?>" class="sm-input"></div>
                                        <div class="sm-form-group"><label class="sm-label">الفئة (B):</label><input type="number" name="facility_b" value="<?php echo $fin['facility_b']; ?>" class="sm-input"></div>
                                        <div class="sm-form-group"><label class="sm-label">الفئة (C):</label><input type="number" name="facility_c" value="<?php echo $fin['facility_c']; ?>" class="sm-input"></div>
                                    </div>
                                </div>

                                <button type="submit" name="sm_save_finance_settings" class="sm-btn" style="width:auto; margin-top:25px; padding: 0 40px; height: 45px;">حفظ تسعيرة الخدمات</button>
                            </form>
                        </div>

                        <div id="notification-settings" class="sm-internal-tab" style="display: <?php echo ($sub == 'notifications') ? 'block' : 'none'; ?>;">
                            <?php include SM_PLUGIN_DIR . 'templates/admin-notifications.php'; ?>
                        </div>

                        <div id="design-settings" class="sm-internal-tab" style="display: <?php echo $sub == 'design' ? 'block' : 'none'; ?>;">
                            <form method="post">
                                <?php wp_nonce_field('sm_admin_action', 'sm_admin_nonce'); ?>
                                <h4 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px;">إعدادات الألوان والمظهر الشاملة</h4>
                                <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:15px; margin-top:20px;">
                                    <div class="sm-form-group"><label class="sm-label">الأساسي:</label><input type="color" name="primary_color" value="<?php echo esc_attr($appearance['primary_color']); ?>" class="sm-input" style="height:40px;"></div>
                                    <div class="sm-form-group"><label class="sm-label">الثانوي:</label><input type="color" name="secondary_color" value="<?php echo esc_attr($appearance['secondary_color']); ?>" class="sm-input" style="height:40px;"></div>
                                    <div class="sm-form-group"><label class="sm-label">التمييز:</label><input type="color" name="accent_color" value="<?php echo esc_attr($appearance['accent_color']); ?>" class="sm-input" style="height:40px;"></div>
                                    <div class="sm-form-group"><label class="sm-label">الهيدر:</label><input type="color" name="dark_color" value="<?php echo esc_attr($appearance['dark_color']); ?>" class="sm-input" style="height:40px;"></div>

                                    <div class="sm-form-group"><label class="sm-label">خلفية النظام:</label><input type="color" name="bg_color" value="<?php echo esc_attr($appearance['bg_color']); ?>" class="sm-input" style="height:40px;"></div>
                                    <div class="sm-form-group"><label class="sm-label">خلفية السايدبار:</label><input type="color" name="sidebar_bg_color" value="<?php echo esc_attr($appearance['sidebar_bg_color']); ?>" class="sm-input" style="height:40px;"></div>
                                    <div class="sm-form-group"><label class="sm-label">لون الخط:</label><input type="color" name="font_color" value="<?php echo esc_attr($appearance['font_color']); ?>" class="sm-input" style="height:40px;"></div>
                                    <div class="sm-form-group"><label class="sm-label">لون الحدود:</label><input type="color" name="border_color" value="<?php echo esc_attr($appearance['border_color']); ?>" class="sm-input" style="height:40px;"></div>
                                </div>

                                <h4 style="margin-top:30px; border-bottom:1px solid #eee; padding-bottom:10px;">الخطوط والخطوط المطبعية (Typography)</h4>
                                <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:20px; margin-top:20px;">
                                    <div class="sm-form-group"><label class="sm-label">حجم الخط (مثال: 15px):</label><input type="text" name="font_size" value="<?php echo esc_attr($appearance['font_size']); ?>" class="sm-input"></div>
                                    <div class="sm-form-group"><label class="sm-label">وزن الخط (400, 700...):</label><input type="text" name="font_weight" value="<?php echo esc_attr($appearance['font_weight']); ?>" class="sm-input"></div>
                                    <div class="sm-form-group"><label class="sm-label">تباعد الأسطر (1.5...):</label><input type="text" name="line_spacing" value="<?php echo esc_attr($appearance['line_spacing']); ?>" class="sm-input"></div>
                                </div>

                                <button type="submit" name="sm_save_appearance" class="sm-btn" style="width:auto; margin-top:20px;">حفظ كافة تعديلات التصميم</button>
                            </form>
                        </div>

                        <?php
                    }
                    break;

            }
            ?>

        </div>
    </div>
</div>

<!-- Global Detailed Finance Modal -->
<div id="log-details-modal" class="sm-modal-overlay">
    <div class="sm-modal-content" style="max-width: 700px;">
        <div class="sm-modal-header">
            <h3>تفاصيل العملية المسجلة</h3>
            <button class="sm-modal-close" onclick="document.getElementById('log-details-modal').style.display='none'">&times;</button>
        </div>
        <div id="log-details-body" style="padding: 20px;"></div>
    </div>
</div>

<div id="sm-finance-member-modal" class="sm-modal-overlay">
    <div class="sm-modal-content" style="max-width: 900px;">
        <div class="sm-modal-header">
            <h3>التفاصيل المالية للعضو</h3>
            <button class="sm-modal-close" onclick="document.getElementById('sm-finance-member-modal').style.display='none'">&times;</button>
        </div>
        <div id="sm-finance-modal-body" style="padding: 20px;">
            <div style="text-align:center; padding: 40px;">جاري تحميل البيانات...</div>
        </div>
    </div>
</div>

<style>
.sm-sidebar-item { border-bottom: 1px solid rgba(0,0,0,0.05); transition: 0.2s; position: relative; }
.sm-sidebar-link {
    padding: 15px 25px;
    cursor: pointer; font-weight: 600; color: #4a5568 !important;
    display: flex; align-items: center; gap: 12px;
    text-decoration: none !important;
    width: 100%;
}
.sm-sidebar-item:hover { background: rgba(0,0,0,0.02); }
.sm-sidebar-item.sm-active {
    background: rgba(0,0,0,0.02) !important;
}
.sm-sidebar-item.sm-active > .sm-sidebar-link {
    color: var(--sm-primary-color) !important;
    font-weight: 700;
}

.sm-sidebar-badge {
    position: absolute; left: 15px; top: 15px;
    background: #e53e3e; color: white; border-radius: 20px; padding: 2px 8px; font-size: 10px; font-weight: 800;
}

.sm-sidebar-dropdown {
    list-style: none; padding: 0; margin: 0; background: rgba(0,0,0,0.04); display: none;
}
.sm-sidebar-dropdown li a {
    display: flex; align-items: center; gap: 12px; padding: 10px 25px;
    font-size: 13px; color: #4a5568 !important; text-decoration: none !important;
    transition: 0.2s;
}
.sm-sidebar-dropdown li a:hover {
    background: rgba(255,255,255,0.3);
}
.sm-sidebar-dropdown li a.sm-sub-active {
    background: var(--sm-dark-color) !important; color: #fff !important; font-weight: 600;
}
.sm-sidebar-dropdown li a .dashicons { font-size: 16px; width: 16px; height: 16px; }

.sm-dropdown-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 20px;
    text-decoration: none !important;
    color: var(--sm-dark-color) !important;
    font-size: 13px;
    font-weight: 600;
    transition: 0.2s;
}
.sm-dropdown-item:hover { background: var(--sm-bg-light); color: var(--sm-primary-color) !important; }

@keyframes smFadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* FORCE VISIBILITY FOR PANELS */
.sm-admin-dashboard .sm-main-tab-panel {
    width: 100% !important;
}
.sm-tab-btn { padding: 10px 20px; border: 1px solid #e2e8f0; background: #f8f9fa; cursor: pointer; border-radius: 5px 5px 0 0; }
.sm-tab-btn.sm-active { background: var(--sm-primary-color) !important; color: #fff !important; border-bottom: none; }
.sm-quick-btn { background: #48bb78 !important; color: white !important; padding: 8px 15px; border-radius: 6px; font-size: 13px; font-weight: 700; border: none; cursor: pointer; display: inline-block; }
.sm-refresh-btn { background: #718096; color: white; padding: 8px 15px; border-radius: 6px; font-size: 13px; border: none; cursor: pointer; }
.sm-logout-btn { background: #e53e3e; color: white; padding: 8px 15px; border-radius: 6px; font-size: 13px; text-decoration: none; font-weight: 700; display: inline-block; }

.sm-header-circle-icon {
    width: 40px; height: 40px; background: #ffffff; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    color: var(--sm-dark-color); text-decoration: none !important; position: relative;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;
    transition: 0.3s;
}
.sm-header-circle-icon:hover { background: #edf2f7; color: var(--sm-primary-color); }
.sm-header-circle-icon .dashicons { font-size: 20px; width: 20px; height: 20px; }

.sm-admin-dashboard .sm-btn { background-color: <?php echo $appearance['btn_color']; ?>; }
.sm-admin-dashboard .sm-table th { border-color: <?php echo $appearance['border_color']; ?>; }
.sm-admin-dashboard .sm-input, .sm-admin-dashboard .sm-select, .sm-admin-dashboard .sm-textarea { border-color: <?php echo $appearance['border_color']; ?>; }

.sm-icon-badge {
    position: absolute; top: -5px; right: -5px; color: white; border-radius: 50%;
    width: 18px; height: 18px; font-size: 10px; display: flex; align-items: center;
    justify-content: center; font-weight: 800; border: 2px solid white;
}
.sm-icon-dot {
    position: absolute; top: 0; right: 0; width: 10px; height: 10px;
    border-radius: 50%; border: 2px solid white;
}

@media (max-width: 992px) {
    .sm-hide-mobile { display: none; }
}
</style>
