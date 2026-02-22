<?php if (!defined('ABSPATH')) exit; ?>
<?php
$user = wp_get_current_user();
$has_full_access = current_user_can('sm_full_access') || current_user_can('manage_options');
$my_gov = get_user_meta($user->ID, 'sm_governorate', true);

global $wpdb;

$active_sub_tab = $_GET['sub_tab'] ?? 'documents';
?>

<div class="sm-global-archive" dir="rtl">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h2 style="margin:0; font-weight: 800; color: var(--sm-dark-color);">الأرشيف الرقمي الشامل</h2>
        <div style="display: flex; gap: 10px;">
            <button onclick="location.reload()" class="sm-btn sm-btn-outline" style="width:auto;"><span class="dashicons dashicons-update"></span> تحديث الأرشيف</button>
        </div>
    </div>

    <!-- Sub Tabs -->
    <div class="sm-tabs-wrapper" style="display: flex; gap: 10px; margin-bottom: 30px; border-bottom: 2px solid #edf2f7; padding-bottom: 0;">
        <a href="<?php echo add_query_arg('sub_tab', 'documents'); ?>" class="sm-tab-btn <?php echo $active_sub_tab == 'documents' ? 'sm-active' : ''; ?>" style="text-decoration:none;">
            <span class="dashicons dashicons-portfolio" style="vertical-align: middle; margin-left: 5px;"></span> مستندات الأعضاء
        </a>
        <a href="<?php echo add_query_arg('sub_tab', 'finance'); ?>" class="sm-tab-btn <?php echo $active_sub_tab == 'finance' ? 'sm-active' : ''; ?>" style="text-decoration:none;">
            <span class="dashicons dashicons-money-alt" style="vertical-align: middle; margin-left: 5px;"></span> العمليات المالية
        </a>
    </div>

    <?php if ($active_sub_tab == 'documents'): ?>
        <?php
        $search = sanitize_text_field($_GET['doc_search'] ?? '');
        $category = sanitize_text_field($_GET['doc_category'] ?? '');
        $where = "1=1";
        $params = [];
        if (!empty($search)) {
            $where .= " AND (d.title LIKE %s OR m.name LIKE %s OR m.national_id LIKE %s)";
            $params[] = '%' . $wpdb->esc_like($search) . '%';
            $params[] = '%' . $wpdb->esc_like($search) . '%';
            $params[] = '%' . $wpdb->esc_like($search) . '%';
        }
        if (!empty($category)) {
            $where .= " AND d.category = %s";
            $params[] = $category;
        }
        if (!$has_full_access && $my_gov) {
            $where .= " AND m.governorate = %s";
            $params[] = $my_gov;
        }
        $query = "SELECT d.*, m.name as member_name, m.national_id as member_nid FROM {$wpdb->prefix}sm_documents d JOIN {$wpdb->prefix}sm_members m ON d.member_id = m.id WHERE $where ORDER BY d.created_at DESC";
        $documents = !empty($params) ? $wpdb->get_results($wpdb->prepare($query, $params)) : $wpdb->get_results($query);
        ?>
        <div style="background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 25px;">
            <form method="get" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
                <input type="hidden" name="sm_tab" value="global-archive">
                <input type="hidden" name="sub_tab" value="documents">
                <div style="flex: 1; min-width: 250px;">
                    <label class="sm-label">بحث في المستندات:</label>
                    <input type="text" name="doc_search" class="sm-input" value="<?php echo esc_attr($search); ?>" placeholder="العنوان، الاسم، أو الرقم القومي...">
                </div>
                <div style="width: 150px;">
                    <label class="sm-label">التصنيف:</label>
                    <select name="doc_category" class="sm-select">
                        <option value="">الكل</option>
                        <option value="licenses" <?php selected($category, 'licenses'); ?>>التراخيص</option>
                        <option value="certificates" <?php selected($category, 'certificates'); ?>>الشهادات</option>
                        <option value="receipts" <?php selected($category, 'receipts'); ?>>إيصالات السداد</option>
                        <option value="other" <?php selected($category, 'other'); ?>>أخرى</option>
                    </select>
                </div>
                <button type="submit" class="sm-btn" style="width: auto; height: 42px;">فلترة النتائج</button>
                <a href="<?php echo remove_query_arg(['doc_search', 'doc_category']); ?>" class="sm-btn sm-btn-outline" style="width: auto; height: 42px; text-decoration:none; display:flex; align-items:center;">إعادة ضبط</a>
            </form>
        </div>
        <div class="sm-table-container">
            <table class="sm-table">
                <thead>
                    <tr>
                        <th>تاريخ الأرشفة</th>
                        <th>العضو</th>
                        <th>عنوان المستند</th>
                        <th>التصنيف</th>
                        <th>النوع</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($documents)): ?>
                        <tr><td colspan="6" style="text-align:center; padding: 40px; color: #718096;">لا توجد مستندات تطابق معايير البحث.</td></tr>
                    <?php else: ?>
                        <?php
                        $catNames = ['licenses' => 'ترخيص', 'certificates' => 'شهادة', 'receipts' => 'إيصال', 'other' => 'أخرى'];
                        foreach ($documents as $d): ?>
                            <tr>
                                <td style="font-size: 11px; color: #718096;"><?php echo $d->created_at; ?></td>
                                <td>
                                    <div style="font-weight: 700;"><?php echo esc_html($d->member_name); ?></div>
                                    <div style="font-size: 10px; color: #94a3b8;"><?php echo esc_html($d->member_nid); ?></div>
                                </td>
                                <td style="font-weight: 600;"><?php echo esc_html($d->title); ?></td>
                                <td><span class="sm-badge sm-badge-low"><?php echo $catNames[$d->category] ?? $d->category; ?></span></td>
                                <td style="font-size: 11px;"><?php echo $d->file_type; ?></td>
                                <td>
                                    <div style="display: flex; gap: 8px;">
                                        <button onclick="smGlobalViewDoc('<?php echo $d->file_url; ?>', '<?php echo esc_js($d->title); ?>', <?php echo $d->id; ?>)" class="sm-btn" style="height:28px; font-size:11px; width:auto; background:#111F35; padding: 0 10px;">عرض</button>
                                        <?php if ($has_full_access): ?>
                                            <button onclick="smDeleteArchiveDoc(<?php echo $d->id; ?>)" class="sm-btn sm-btn-outline" style="height:28px; font-size:11px; width:auto; color:#e53e3e; border-color:#feb2b2; padding: 0 10px;">حذف</button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <?php
        $where = "1=1";
        if (!$has_full_access && $my_gov) {
            $where .= $wpdb->prepare(" AND EXISTS (SELECT 1 FROM {$wpdb->prefix}sm_members m WHERE m.id = p.member_id AND m.governorate = %s)", $my_gov);
        }
        $search = sanitize_text_field($_GET['fin_search'] ?? '');
        if ($search) {
            $where .= $wpdb->prepare(" AND EXISTS (SELECT 1 FROM {$wpdb->prefix}sm_members m WHERE m.id = p.member_id AND (m.name LIKE %s OR m.national_id LIKE %s))", '%' . $wpdb->esc_like($search) . '%', '%' . $wpdb->esc_like($search) . '%');
        }
        $payments = $wpdb->get_results("SELECT p.*, u.display_name as staff_name FROM {$wpdb->prefix}sm_payments p LEFT JOIN {$wpdb->base_prefix}users u ON p.created_by = u.ID WHERE $where ORDER BY p.created_at DESC LIMIT 500");
        ?>
        <div style="background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 25px;">
            <form method="get" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
                <input type="hidden" name="sm_tab" value="global-archive">
                <input type="hidden" name="sub_tab" value="finance">
                <div style="flex: 1; min-width: 250px;">
                    <label class="sm-label">بحث في العمليات المالية:</label>
                    <input type="text" name="fin_search" class="sm-input" value="<?php echo esc_attr($search); ?>" placeholder="اسم العضو أو الرقم القومي...">
                </div>
                <button type="submit" class="sm-btn" style="width: auto; height: 42px;">تطبيق البحث</button>
                <a href="<?php echo remove_query_arg(['fin_search']); ?>" class="sm-btn sm-btn-outline" style="width: auto; height: 42px; text-decoration:none; display:flex; align-items:center;">إعادة ضبط</a>
            </form>
        </div>
        <div class="sm-table-container">
            <table class="sm-table">
                <thead>
                    <tr>
                        <th>التاريخ</th>
                        <th>العضو</th>
                        <th>التفاصيل</th>
                        <th>الفاتورة الرقمية</th>
                        <th>المبلغ</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payments)): ?>
                        <tr><td colspan="6" style="text-align:center; padding: 40px; color: #718096;">لا توجد عمليات مالية مسجلة.</td></tr>
                    <?php else: ?>
                        <?php foreach ($payments as $p):
                             $m = SM_DB::get_member_by_id($p->member_id);
                        ?>
                            <tr>
                                <td style="font-size: 11px; color: #718096;"><?php echo $p->payment_date; ?></td>
                                <td>
                                    <div style="font-weight: 700;"><?php echo esc_html($m->name ?? 'عضو محذوف'); ?></div>
                                    <div style="font-size: 10px; color: #94a3b8;"><?php echo esc_html($m->national_id ?? ''); ?></div>
                                </td>
                                <td style="font-size: 13px;"><?php echo esc_html($p->details_ar ?: $p->payment_type); ?></td>
                                <td style="font-family: monospace; font-size: 11px; color: #3182ce;"><?php echo $p->digital_invoice_code; ?></td>
                                <td style="font-weight: 800; color: #38a169;"><?php echo number_format($p->amount, 2); ?></td>
                                <td>
                                    <div style="display: flex; gap: 5px;">
                                        <a href="<?php echo admin_url('admin-ajax.php?action=sm_print_invoice&payment_id='.$p->id); ?>" target="_blank" class="sm-btn" style="height:25px; padding:0 8px; font-size:10px; width:auto; background:#2c3e50; text-decoration:none; display:flex; align-items:center;">فاتورة</a>
                                        <?php if ($has_full_access): ?>
                                            <button onclick="smDeleteArchivePayment(<?php echo $p->id; ?>)" class="sm-btn sm-btn-outline" style="height:25px; font-size:10px; width:auto; color:#e53e3e; border-color:#feb2b2; padding: 0 8px;">حذف</button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div id="sm-global-viewer-modal" class="sm-modal-overlay">
    <div class="sm-modal-content" style="max-width: 950px; height: 90vh; display: flex; flex-direction: column;">
        <div class="sm-modal-header">
            <h3 id="sm-global-viewer-title">عرض المستند</h3>
            <div style="display: flex; gap: 10px; align-items: center;">
                <a href="" id="sm-global-viewer-download" target="_blank" class="sm-btn" style="width:auto; height:32px; font-size:11px; background:#27ae60; text-decoration:none; display:flex; align-items:center;">تحميل</a>
                <button class="sm-modal-close" onclick="document.getElementById('sm-global-viewer-modal').style.display='none'">&times;</button>
            </div>
        </div>
        <div id="sm-global-viewer-body" style="flex: 1; background: #525659; overflow: hidden; position: relative;"></div>
    </div>
</div>

<script>
function smGlobalViewDoc(url, title, id) {
    document.getElementById('sm-global-viewer-title').innerText = title;
    document.getElementById('sm-global-viewer-download').href = url;
    const body = document.getElementById('sm-global-viewer-body');
    const isPdf = url.toLowerCase().endsWith('.pdf') || url.includes('action=sm_print');
    if (isPdf) {
        body.innerHTML = `<iframe src="${url}" style="width:100%; height:100%; border:none;"></iframe>`;
    } else {
        body.innerHTML = `<div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; padding:20px;"><img src="${url}" style="max-width:100%; max-height:100%; object-fit:contain; box-shadow:0 0 50px rgba(0,0,0,0.5);"></div>`;
    }
    document.getElementById('sm-global-viewer-modal').style.display = 'flex';
}

function smDeleteArchiveDoc(id) {
    if (!confirm('هل أنت متأكد من حذف هذا المستند نهائياً؟')) return;
    const fd = new FormData();
    fd.append('action', 'sm_delete_document');
    fd.append('doc_id', id);
    fd.append('nonce', '<?php echo wp_create_nonce("sm_document_action"); ?>');
    fetch(ajaxurl, { method: 'POST', body: fd }).then(r => r.json()).then(res => { if (res.success) location.reload(); });
}

function smDeleteArchivePayment(id) {
    if (!confirm('هل أنت متأكد من حذف هذه العملية المالية نهائياً؟')) return;
    const fd = new FormData();
    fd.append('action', 'sm_delete_transaction_ajax');
    fd.append('transaction_id', id);
    fd.append('nonce', '<?php echo wp_create_nonce("sm_admin_action"); ?>');
    fetch(ajaxurl, { method: 'POST', body: fd }).then(r => r.json()).then(res => { if (res.success) location.reload(); });
}
</script>
