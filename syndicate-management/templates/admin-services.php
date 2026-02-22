<?php if (!defined('ABSPATH')) exit; ?>
<?php
$user = wp_get_current_user();
$roles = (array)$user->roles;
$is_official = in_array('sm_syndicate_admin', $roles) || in_array('sm_system_admin', $roles) || in_array('administrator', $roles);

$member_id = 0;
global $wpdb;
$member_by_wp = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$wpdb->prefix}sm_members WHERE wp_user_id = %d", $user->ID));
if ($member_by_wp) $member_id = $member_by_wp->id;

// Fetch services
$services = SM_DB::get_services();
$my_requests = $member_id ? SM_DB::get_service_requests(['member_id' => $member_id]) : [];
$all_requests = $is_official ? SM_DB::get_service_requests() : [];
?>

<div class="sm-services-container" dir="rtl">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h2 style="margin:0; font-weight: 800; color: var(--sm-dark-color);">الخدمات الرقمية</h2>
        <?php if ($is_official): ?>
            <button onclick="smOpenAddServiceModal()" class="sm-btn" style="width:auto;">+ إضافة خدمة جديدة</button>
        <?php endif; ?>
    </div>

    <div class="sm-tabs-wrapper" style="display: flex; gap: 10px; margin-bottom: 25px; border-bottom: 2px solid #eee; padding-bottom: 10px;">
        <button class="sm-tab-btn sm-active" onclick="smOpenInternalTab('available-services', this)">الخدمات المتاحة</button>
        <button class="sm-tab-btn" onclick="smOpenInternalTab('requests-history', this)"><?php echo $is_official ? 'طلبات الأعضاء' : 'طلباتي السابقة'; ?></button>
    </div>

    <!-- TAB: Available Services -->
    <div id="available-services" class="sm-internal-tab">
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
            <?php if (empty($services)): ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 50px; color: #94a3b8;">لا توجد خدمات متاحة حالياً.</div>
            <?php else: ?>
                <?php foreach ($services as $s): ?>
                    <div class="sm-service-card" style="background: #fff; border: 1px solid var(--sm-border-color); border-radius: 15px; padding: 25px; display: flex; flex-direction: column; transition: 0.3s; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
                        <div style="width: 50px; height: 50px; background: var(--sm-primary-color); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #fff; margin-bottom: 20px;">
                            <span class="dashicons dashicons-cloud" style="font-size: 24px; width: 24px; height: 24px;"></span>
                        </div>
                        <h3 style="margin: 0 0 10px 0; font-weight: 800; color: var(--sm-dark-color);"><?php echo esc_html($s->name); ?></h3>
                        <p style="font-size: 13px; color: #64748b; line-height: 1.6; margin-bottom: 20px; flex: 1;"><?php echo esc_html($s->description); ?></p>

                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: auto; padding-top: 15px; border-top: 1px solid #f1f5f9;">
                            <div style="font-weight: 700; color: var(--sm-primary-color);"><?php echo $s->fees > 0 ? number_format($s->fees, 2) . ' ج.م' : 'خدمة مجانية'; ?></div>
                            <?php if ($is_official): ?>
                                <div style="display: flex; gap: 5px;">
                                    <button class="sm-btn sm-btn-outline" style="padding: 5px 10px; font-size: 11px;" onclick='editService(<?php echo json_encode($s); ?>)'>تعديل</button>
                                    <button class="sm-btn" style="padding: 5px 10px; font-size: 11px; background: #e53e3e;" onclick="deleteService(<?php echo $s->id; ?>)">حذف</button>
                                </div>
                            <?php else: ?>
                                <button class="sm-btn" style="width: auto; padding: 8px 20px;" onclick='requestService(<?php echo json_encode($s); ?>)'>طلب الخدمة</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- TAB: Requests History -->
    <div id="requests-history" class="sm-internal-tab" style="display: none;">
        <div class="sm-table-container">
            <table class="sm-table">
                <thead>
                    <tr>
                        <th>رقم الطلب</th>
                        <?php if ($is_official): ?><th>العضو</th><th>المحافظة</th><?php endif; ?>
                        <th>الخدمة</th>
                        <th>التاريخ</th>
                        <th>الحالة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $target_requests = $is_official ? $all_requests : $my_requests;
                    if (empty($target_requests)): ?>
                        <tr><td colspan="<?php echo $is_official ? 6 : 4; ?>" style="text-align: center; padding: 40px;">لا توجد طلبات سابقة.</td></tr>
                    <?php else:
                        foreach ($target_requests as $r):
                            $status_label = ['pending'=>'قيد الانتظار', 'processing'=>'جاري التنفيذ', 'approved'=>'مكتمل', 'rejected'=>'مرفوض'][$r->status];
                            $status_class = ['pending'=>'sm-badge-low', 'processing'=>'sm-badge-mid', 'approved'=>'sm-badge-high', 'rejected'=>'sm-badge-urgent'][$r->status] ?? 'sm-badge-low';
                        ?>
                            <tr>
                                <td>#<?php echo $r->id; ?></td>
                                <?php if ($is_official): ?>
                                    <td style="font-weight: 700;"><?php echo esc_html($r->member_name); ?></td>
                                    <td><?php echo esc_html(SM_Settings::get_governorates()[$r->governorate] ?? $r->governorate); ?></td>
                                <?php endif; ?>
                                <td><?php echo esc_html($r->service_name); ?></td>
                                <td><?php echo date('Y-m-d', strtotime($r->created_at)); ?></td>
                                <td><span class="sm-badge <?php echo $status_class; ?>"><?php echo $status_label; ?></span></td>
                                <td>
                                    <div style="display: flex; gap: 5px;">
                                        <button class="sm-btn sm-btn-outline" style="padding: 5px 10px; font-size: 11px;" onclick='viewRequest(<?php echo json_encode($r); ?>)'>تفاصيل</button>
                                        <?php if ($r->status == 'approved'): ?>
                                            <a href="<?php echo admin_url('admin-ajax.php?action=sm_print_service_request&id=' . $r->id); ?>" target="_blank" class="sm-btn" style="padding: 5px 10px; font-size: 11px; background: #27ae60; text-decoration: none;">تحميل PDF</a>
                                        <?php endif; ?>
                                        <?php if ($is_official && in_array($r->status, ['pending', 'processing'])): ?>
                                            <button class="sm-btn" style="padding: 5px 10px; font-size: 11px;" onclick="processRequest(<?php echo $r->id; ?>, 'approved')">اعتماد</button>
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
</div>

<!-- Modals -->
<div id="add-service-modal" class="sm-modal-overlay">
    <div class="sm-modal-content" style="max-width: 600px;">
        <div class="sm-modal-header"><h3>إضافة خدمة رقمية جديدة</h3><button class="sm-modal-close" onclick="document.getElementById('add-service-modal').style.display='none'">&times;</button></div>
        <form id="add-service-form" style="padding: 20px;">
            <div class="sm-form-group"><label class="sm-label">اسم الخدمة:</label><input name="name" type="text" class="sm-input" required></div>
            <div class="sm-form-group"><label class="sm-label">وصف الخدمة:</label><textarea name="description" class="sm-textarea" rows="3"></textarea></div>
            <div class="sm-form-group"><label class="sm-label">الرسوم (0 للمجانية):</label><input name="fees" type="number" step="0.01" class="sm-input" value="0"></div>

            <div class="sm-form-group">
                <label class="sm-label">البيانات الشخصية المطلوبة من ملف العضو:</label>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <?php
                    $profile_fields = [
                        'name' => 'الاسم الكامل',
                        'national_id' => 'الرقم القومي',
                        'membership_number' => 'رقم العضوية',
                        'professional_grade' => 'الدرجة الوظيفية',
                        'specialization' => 'التخصص',
                        'phone' => 'رقم الهاتف',
                        'email' => 'البريد الإلكتروني',
                        'governorate' => 'المحافظة',
                        'facility_name' => 'اسم المنشأة'
                    ];
                    foreach ($profile_fields as $key => $label): ?>
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer;">
                            <input type="checkbox" name="profile_fields[]" value="<?php echo $key; ?>"> <?php echo $label; ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit" class="sm-btn" style="width: 100%; height: 45px; font-weight: 700; margin-top: 10px;">إضافة الخدمة وتفعيلها</button>
        </form>
    </div>
</div>

<div id="request-service-modal" class="sm-modal-overlay">
    <div class="sm-modal-content" style="max-width: 600px;">
        <div class="sm-modal-header"><h3>طلب خدمة: <span id="req-service-name"></span></h3><button class="sm-modal-close" onclick="document.getElementById('request-service-modal').style.display='none'">&times;</button></div>
        <form id="submit-request-form" style="padding: 20px;">
            <input type="hidden" name="service_id" id="req-service-id">
            <input type="hidden" name="member_id" value="<?php echo $member_id; ?>">
            <div id="dynamic-fields-container"></div>
            <div style="background: #fffaf0; padding: 15px; border-radius: 8px; border: 1px solid #feebc8; margin-top: 15px; font-size: 13px;">
                <strong>الرسوم المستحقة: </strong> <span id="req-service-fees"></span>
                <p style="margin: 5px 0 0 0; color: #744210;">* سيتم إضافة الرسوم إلى حسابك المالي عند اعتماد الطلب.</p>
            </div>
            <button type="submit" class="sm-btn" style="margin-top: 20px;">تأكيد وتقديم الطلب</button>
        </form>
    </div>
</div>

<div id="view-request-modal" class="sm-modal-overlay">
    <div class="sm-modal-content" style="max-width: 600px;">
        <div class="sm-modal-header"><h3>تفاصيل الطلب</h3><button class="sm-modal-close" onclick="document.getElementById('view-request-modal').style.display='none'">&times;</button></div>
        <div id="request-details-body" style="padding: 20px;"></div>
    </div>
</div>

<script>
(function($) {
    window.smOpenAddServiceModal = function() {
        const modal = $('#add-service-modal');
        modal.find('h3').text('إضافة خدمة رقمية جديدة');
        const form = $('#add-service-form');
        form[0].reset();
        form.find('input[name="profile_fields[]"]').prop('checked', false);

        form.off('submit').on('submit', function(e) {
            e.preventDefault();
            const fd = new FormData(this);

            const profileFields = [];
            $(this).find('input[name="profile_fields[]"]:checked').each(function() {
                profileFields.push($(this).val());
            });
            fd.append('selected_profile_fields', JSON.stringify(profileFields));

            fd.append('action', 'sm_add_service');
            fd.append('nonce', '<?php echo wp_create_nonce("sm_admin_action"); ?>');
            fetch(ajaxurl, {method: 'POST', body: fd}).then(r=>r.json()).then(res=>{
                if (res.success) {
                    smShowNotification('تم إضافة الخدمة بنجاح');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    alert(res.data);
                }
            });
        });
        modal.fadeIn().css('display', 'flex');
    };

    window.deleteService = function(id) {
        if (!confirm('هل أنت متأكد من حذف هذه الخدمة؟')) return;
        const fd = new FormData();
        fd.append('action', 'sm_delete_service');
        fd.append('id', id);
        fd.append('nonce', '<?php echo wp_create_nonce("sm_admin_action"); ?>');
        fetch(ajaxurl, {method: 'POST', body: fd}).then(r=>r.json()).then(res=>{
            if (res.success) location.reload();
        });
    };

    window.editService = function(s) {
        const modal = $('#add-service-modal');
        modal.find('h3').text('تعديل الخدمة: ' + s.name);
        modal.find('[name="name"]').val(s.name);
        modal.find('[name="description"]').val(s.description);
        modal.find('[name="fees"]').val(s.fees);

        modal.find('input[name="profile_fields[]"]').prop('checked', false);
        if (s.selected_profile_fields) {
            try {
                const fields = JSON.parse(s.selected_profile_fields);
                fields.forEach(f => {
                    modal.find(`input[value="${f}"]`).prop('checked', true);
                });
            } catch(e) {}
        }

        $('#add-service-form').off('submit').on('submit', function(e) {
            e.preventDefault();
            const fd = new FormData(this);
            const profileFields = [];
            $(this).find('input[name="profile_fields[]"]:checked').each(function() {
                profileFields.push($(this).val());
            });
            fd.append('selected_profile_fields', JSON.stringify(profileFields));
            fd.append('id', s.id);
            fd.append('status', s.status);
            fd.append('action', 'sm_update_service');
            fd.append('nonce', '<?php echo wp_create_nonce("sm_admin_action"); ?>');

            fetch(ajaxurl, {method: 'POST', body: fd}).then(r=>r.json()).then(res=>{
                if (res.success) {
                    smShowNotification('تم تحديث الخدمة بنجاح');
                    setTimeout(() => location.reload(), 1000);
                } else alert(res.data);
            });
        });

        modal.fadeIn().css('display', 'flex');
    };

    window.requestService = function(s) {
        $('#req-service-name').text(s.name);
        $('#req-service-id').val(s.id);
        $('#req-service-fees').text(s.fees > 0 ? s.fees + ' ج.م' : 'مجاناً');

        const container = $('#dynamic-fields-container').empty();

        // Add notice about profile fields
        if (s.selected_profile_fields) {
            const pFields = JSON.parse(s.selected_profile_fields);
            if (pFields.length > 0) {
                container.append('<p style="font-size:12px; color:#666; margin-bottom:15px; background:#f0f4f8; padding:10px; border-radius:5px;">سيتم سحب بياناتك الشخصية (الاسم، الرقم القومي، إلخ) تلقائياً من ملفك الشخصي لإدراجها في المستند.</p>');
            }
        }

        try {
            const fields = JSON.parse(s.required_fields);
            fields.forEach(f => {
                container.append(`
                    <div class="sm-form-group">
                        <label class="sm-label">${f.label}:</label>
                        <input name="field_${f.name}" type="${f.type || 'text'}" class="sm-input" required>
                    </div>
                `);
            });
        } catch(e) { console.error(e); }

        $('#request-service-modal').fadeIn().css('display', 'flex');
    };

    $('#submit-request-form').on('submit', function(e) {
        e.preventDefault();
        const data = {};
        $(this).serializeArray().forEach(item => {
            if (item.name.startsWith('field_')) data[item.name.replace('field_', '')] = item.value;
        });

        const fd = new FormData();
        fd.append('action', 'sm_submit_service_request');
        fd.append('service_id', $('#req-service-id').val());
        fd.append('member_id', $(this).find('[name="member_id"]').val());
        fd.append('request_data', JSON.stringify(data));
        fd.append('nonce', '<?php echo wp_create_nonce("sm_service_action"); ?>');

        fetch(ajaxurl, {method: 'POST', body: fd}).then(r=>r.json()).then(res=>{
            if (res.success) { alert('تم تقديم الطلب بنجاح'); location.reload(); } else alert(res.data);
        });
    });

    window.viewRequest = function(r) {
        const body = $('#request-details-body').empty();
        const data = JSON.parse(r.request_data);
        let html = `<div style="margin-bottom:20px;"><strong style="color:var(--sm-primary-color);">الخدمة:</strong> ${r.service_name}</div>`;
        html += `<div style="display:grid; gap:10px;">`;
        for (let k in data) {
            html += `<div><strong>${k}:</strong> ${data[k]}</div>`;
        }
        html += `</div>`;
        body.append(html);
        $('#view-request-modal').fadeIn().css('display', 'flex');
    };

    window.processRequest = function(id, status) {
        if (!confirm('هل أنت متأكد من تغيير حالة الطلب؟')) return;
        const fd = new FormData();
        fd.append('action', 'sm_process_service_request');
        fd.append('id', id);
        fd.append('status', status);
        fd.append('nonce', '<?php echo wp_create_nonce("sm_admin_action"); ?>');
        fetch(ajaxurl, {method: 'POST', body: fd}).then(r=>r.json()).then(res=>{
            if (res.success) location.reload(); else alert(res.data);
        });
    };

})(jQuery);
</script>
