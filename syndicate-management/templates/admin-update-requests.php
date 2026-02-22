<?php
if (!defined('ABSPATH')) exit;

$status_filter = $_GET['status'] ?? 'pending';
$requests = SM_DB::get_update_requests($status_filter);
$specs = SM_Settings::get_specializations();
$govs = SM_Settings::get_governorates();
$grades = SM_Settings::get_professional_grades();
?>

<div class="sm-admin-dashboard" dir="rtl">
    <div class="sm-header">
        <div class="sm-header-title">
            <span class="dashicons dashicons-update"></span>
            <div>
                <h1>طلبات تحديث البيانات</h1>
                <p>مراجعة واعتماد طلبات التعديل المقدمة من الأعضاء</p>
            </div>
        </div>
    </div>

    <div class="sm-filters-bar" style="margin-bottom: 20px;">
        <a href="?sm_tab=update_requests&status=pending" class="sm-btn <?php echo $status_filter === 'pending' ? '' : 'sm-btn-outline'; ?>">قيد الانتظار</a>
        <a href="?sm_tab=update_requests&status=approved" class="sm-btn <?php echo $status_filter === 'approved' ? '' : 'sm-btn-outline'; ?>" style="margin-right: 10px;">تم الاعتماد</a>
        <a href="?sm_tab=update_requests&status=rejected" class="sm-btn <?php echo $status_filter === 'rejected' ? '' : 'sm-btn-outline'; ?>" style="margin-right: 10px;">مرفوضة</a>
    </div>

    <div class="sm-card">
        <table class="sm-table">
            <thead>
                <tr>
                    <th>تاريخ الطلب</th>
                    <th>العضو</th>
                    <th>البيانات المطلوبة</th>
                    <th>ملاحظات</th>
                    <?php if ($status_filter === 'pending'): ?>
                        <th>الإجراءات</th>
                    <?php else: ?>
                        <th>الحالة</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requests)): ?>
                    <tr><td colspan="5" style="text-align: center; padding: 40px; color: #64748b;">لا توجد طلبات حالياً</td></tr>
                <?php else: foreach ($requests as $req):
                    $data = json_decode($req->requested_data, true);
                    $member = SM_DB::get_member_by_id($req->member_id);
                ?>
                    <tr>
                        <td><?php echo date('Y-m-d H:i', strtotime($req->created_at)); ?></td>
                        <td>
                            <strong><?php echo esc_html($req->member_name); ?></strong><br>
                            <small style="color: #64748b;"><?php echo esc_html($req->national_id); ?></small>
                        </td>
                        <td style="font-size: 0.85em;">
                            <?php
                            foreach ($data as $k => $v) {
                                if ($k === 'notes') continue;
                                $old_val = $member->$k ?? '';
                                if ($old_val != $v) {
                                    $label = '';
                                    switch($k) {
                                        case 'name': $label = 'الاسم'; break;
                                        case 'national_id': $label = 'الرقم القومي'; break;
                                        case 'phone': $label = 'الهاتف'; break;
                                        case 'email': $label = 'البريد'; break;
                                        case 'governorate': $label = 'المحافظة'; $v = $govs[$v] ?? $v; $old_val = $govs[$old_val] ?? $old_val; break;
                                        case 'specialization': $label = 'التخصص'; $v = $specs[$v] ?? $v; $old_val = $specs[$old_val] ?? $old_val; break;
                                        case 'professional_grade': $label = 'الدرجة'; $v = $grades[$v] ?? $v; $old_val = $grades[$old_val] ?? $old_val; break;
                                    }
                                    if ($label) echo "<div><strong>$label:</strong> <span style='color: #c53030; text-decoration: line-through;'>$old_val</span> &larr; <span style='color: #2f855a;'>$v</span></div>";
                                }
                            }
                            ?>
                        </td>
                        <td><?php echo esc_html($data['notes'] ?? ''); ?></td>
                        <td>
                            <?php if ($req->status === 'pending'): ?>
                                <button onclick="processRequest(<?php echo $req->id; ?>, 'approved')" class="sm-btn sm-btn-sm" style="background: #2f855a;">اعتماد</button>
                                <button onclick="processRequest(<?php echo $req->id; ?>, 'rejected')" class="sm-btn sm-btn-sm sm-btn-outline" style="color: #c53030; border-color: #c53030;">رفض</button>
                            <?php else: ?>
                                <span class="sm-status-badge <?php echo $req->status === 'approved' ? 'status-active' : 'status-expired'; ?>">
                                    <?php echo $req->status === 'approved' ? 'تم الاعتماد' : 'مرفوض'; ?>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function processRequest(id, status) {
    if (!confirm('هل أنت متأكد من ' + (status === 'approved' ? 'اعتماد' : 'رفض') + ' هذا الطلب؟')) return;

    const formData = new FormData();
    formData.append('action', 'sm_process_update_request_ajax');
    formData.append('request_id', id);
    formData.append('status', status);
    formData.append('nonce', '<?php echo wp_create_nonce("sm_update_request"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            location.reload();
        } else {
            alert('خطأ: ' + res.data);
        }
    });
}
</script>
