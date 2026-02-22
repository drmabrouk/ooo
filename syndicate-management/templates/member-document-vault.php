<?php if (!defined('ABSPATH')) exit; ?>
<div class="sm-document-vault" dir="rtl">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <div style="display: flex; gap: 10px;">
            <div style="position: relative;">
                <span class="dashicons dashicons-search" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8;"></span>
                <input type="text" id="sm-doc-search" placeholder="بحث في الأرشيف..." class="sm-input" style="padding-right: 40px; width: 250px;" oninput="smLoadDocuments()">
            </div>
            <select id="sm-doc-category" class="sm-select" style="width: 150px;" onchange="smLoadDocuments()">
                <option value="">كافة التصنيفات</option>
                <option value="licenses">التراخيص</option>
                <option value="certificates">الشهادات</option>
                <option value="receipts">إيصالات السداد</option>
                <option value="other">مستندات أخرى</option>
            </select>
        </div>
        <button onclick="smOpenUploadModal()" class="sm-btn" style="width: auto; background: var(--sm-primary-color);"><span class="dashicons dashicons-upload"></span> رفع مستند جديد</button>
    </div>

    <div id="sm-documents-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px;">
        <!-- Loaded via AJAX -->
        <div style="grid-column: 1/-1; text-align: center; padding: 50px; color: #94a3b8;">جاري تحميل الأرشيف...</div>
    </div>
</div>

<!-- Upload Modal -->
<div id="sm-upload-doc-modal" class="sm-modal-overlay">
    <div class="sm-modal-content" style="max-width: 500px;">
        <div class="sm-modal-header"><h3>رفع مستند للأرشيف الإلكتروني</h3><button class="sm-modal-close" onclick="smCloseUploadModal()">&times;</button></div>
        <form id="sm-upload-doc-form" style="padding: 20px;">
            <input type="hidden" name="member_id" value="<?php echo $member_id; ?>">
            <div class="sm-form-group">
                <label class="sm-label">عنوان المستند:</label>
                <input type="text" name="title" class="sm-input" required placeholder="مثال: شهادة خبرة 2023">
            </div>
            <div class="sm-form-group">
                <label class="sm-label">التصنيف:</label>
                <select name="category" class="sm-select" required>
                    <option value="licenses">تراخيص</option>
                    <option value="certificates">شهادات ومؤهلات</option>
                    <option value="receipts">إيصالات مالية</option>
                    <option value="other">أخرى</option>
                </select>
            </div>
            <div class="sm-form-group">
                <label class="sm-label">اختيار الملف (PDF أو صورة):</label>
                <input type="file" name="document_file" class="sm-input" accept="image/*,application/pdf" required>
            </div>
            <button type="submit" class="sm-btn" style="margin-top: 10px;">بدء الرفع والأرشفة</button>
        </form>
    </div>
</div>

<!-- Viewer Modal -->
<div id="sm-doc-viewer-modal" class="sm-modal-overlay">
    <div class="sm-modal-content" style="max-width: 900px; height: 90vh; display: flex; flex-direction: column;">
        <div class="sm-modal-header">
            <h3 id="sm-viewer-title">عرض المستند</h3>
            <div style="display: flex; gap: 10px; align-items: center;">
                <button onclick="smShowDocLogs()" class="sm-btn sm-btn-outline" style="width:auto; height:32px; font-size:11px;">سجل النشاط</button>
                <a href="" id="sm-viewer-download" target="_blank" class="sm-btn" style="width:auto; height:32px; font-size:11px; background:#27ae60; text-decoration:none; display:flex; align-items:center;">تحميل</a>
                <button class="sm-modal-close" onclick="smCloseViewer()">&times;</button>
            </div>
        </div>
        <div id="sm-viewer-body" style="flex: 1; background: #525659; overflow: hidden; position: relative;">
            <!-- Iframe or Image -->
        </div>
    </div>
</div>

<!-- Logs Modal -->
<div id="sm-doc-logs-modal" class="sm-modal-overlay" style="z-index: 10001;">
    <div class="sm-modal-content" style="max-width: 500px;">
        <div class="sm-modal-header"><h3>سجل نشاط المستند</h3><button class="sm-modal-close" onclick="document.getElementById('sm-doc-logs-modal').style.display='none'">&times;</button></div>
        <div id="sm-doc-logs-body" style="padding: 20px;"></div>
    </div>
</div>

<style>
.sm-doc-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 15px; text-align: center; transition: 0.3s; cursor: pointer; position: relative; }
.sm-doc-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); border-color: var(--sm-primary-color); }
.sm-doc-icon { font-size: 40px; color: #cbd5e0; margin-bottom: 10px; display: block; }
.sm-doc-title { font-weight: 700; font-size: 13px; color: var(--sm-dark-color); display: block; margin-bottom: 5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sm-doc-meta { font-size: 10px; color: #94a3b8; }
.sm-doc-category-tag { position: absolute; top: 10px; right: 10px; font-size: 9px; padding: 2px 6px; border-radius: 4px; background: #f1f5f9; color: #64748b; }
.sm-doc-delete { position: absolute; top: 10px; left: 10px; color: #e53e3e; cursor: pointer; opacity: 0; transition: 0.2s; }
.sm-doc-card:hover .sm-doc-delete { opacity: 1; }
</style>

<script>
let currentViewingDocId = null;

function smOpenUploadModal() { document.getElementById('sm-upload-doc-modal').style.display = 'flex'; }
function smCloseUploadModal() { document.getElementById('sm-upload-doc-modal').style.display = 'none'; }

function smLoadDocuments() {
    const search = document.getElementById('sm-doc-search').value;
    const category = document.getElementById('sm-doc-category').value;
    const grid = document.getElementById('sm-documents-grid');

    fetch(`<?php echo admin_url('admin-ajax.php'); ?>?action=sm_get_documents&member_id=<?php echo $member_id; ?>&search=${search}&category=${category}`)
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            if (res.data.length === 0) {
                grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 50px; color: #94a3b8;">لا توجد مستندات في هذا القسم حالياً.</div>';
                return;
            }
            let html = '';
            const catNames = { licenses: 'ترخيص', certificates: 'شهادة', receipts: 'إيصال', other: 'أخرى' };
            res.data.forEach(doc => {
                const isPdf = doc.file_type.includes('pdf');
                const icon = isPdf ? 'dashicons-pdf' : 'dashicons-format-image';
                html += `
                    <div class="sm-doc-card" onclick="smViewDocument('${doc.file_url}', '${doc.title}', ${doc.id})">
                        <span class="sm-doc-category-tag">${catNames[doc.category]}</span>
                        <?php if (current_user_can('sm_manage_members') || $member->wp_user_id == get_current_user_id()): ?>
                        <span class="sm-doc-delete dashicons dashicons-trash" onclick="event.stopPropagation(); smDeleteDocument(${doc.id})"></span>
                        <?php endif; ?>
                        <span class="sm-doc-icon dashicons ${icon}"></span>
                        <span class="sm-doc-title" title="${doc.title}">${doc.title}</span>
                        <span class="sm-doc-meta">${doc.created_at.split(' ')[0]}</span>
                    </div>
                `;
            });
            grid.innerHTML = html;
        }
    });
}

function smViewDocument(url, title, id) {
    currentViewingDocId = id;
    document.getElementById('sm-viewer-title').innerText = title;
    document.getElementById('sm-viewer-download').href = url;

    const body = document.getElementById('sm-viewer-body');
    const isPdf = url.toLowerCase().endsWith('.pdf');

    if (isPdf) {
        body.innerHTML = `<iframe src="${url}" style="width:100%; height:100%; border:none;"></iframe>`;
    } else {
        body.innerHTML = `<div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; padding:20px;"><img src="${url}" style="max-width:100%; max-height:100%; object-fit:contain; box-shadow:0 0 50px rgba(0,0,0,0.5);"></div>`;
    }

    document.getElementById('sm-doc-viewer-modal').style.display = 'flex';
    smLogAction(id, 'view');
}

function smCloseViewer() {
    document.getElementById('sm-doc-viewer-modal').style.display = 'none';
    document.getElementById('sm-viewer-body').innerHTML = '';
}

function smDeleteDocument(id) {
    if (!confirm('هل أنت متأكد من حذف هذا المستند نهائياً؟')) return;
    const fd = new FormData();
    fd.append('action', 'sm_delete_document');
    fd.append('doc_id', id);
    fd.append('nonce', '<?php echo wp_create_nonce("sm_document_action"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: fd })
    .then(r => r.json()).then(res => {
        if (res.success) { smShowNotification('تم حذف المستند'); smLoadDocuments(); }
    });
}

function smShowDocLogs() {
    const body = document.getElementById('sm-doc-logs-body');
    body.innerHTML = 'جاري التحميل...';
    document.getElementById('sm-doc-logs-modal').style.display = 'flex';

    fetch(`<?php echo admin_url('admin-ajax.php'); ?>?action=sm_get_document_logs&doc_id=${currentViewingDocId}`)
    .then(r => r.json()).then(res => {
        if (res.success) {
            let html = '<div style="display:grid; gap:10px;">';
            const actionLabels = { upload: 'رفع المستند', view: 'عرض المستند', delete: 'حذف المستند' };
            res.data.forEach(log => {
                html += `
                    <div style="font-size:12px; padding:10px; border-bottom:1px solid #eee;">
                        <div style="font-weight:700;">${actionLabels[log.action] || log.action}</div>
                        <div style="color:#64748b;">بواسطة: ${log.user_name}</div>
                        <div style="color:#94a3b8; font-size:10px;">${log.created_at}</div>
                    </div>
                `;
            });
            html += '</div>';
            body.innerHTML = html;
        }
    });
}

function smLogAction(docId, action) {
    if (action !== 'view') return; // upload/delete are handled server-side
    const fd = new FormData();
    fd.append('action', 'sm_log_document_view');
    fd.append('doc_id', docId);
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: fd });
}

document.getElementById('sm-upload-doc-form').onsubmit = function(e) {
    e.preventDefault();
    const btn = this.querySelector('button');
    btn.disabled = true; btn.innerText = 'جاري الرفع...';

    const formData = new FormData(this);
    formData.append('action', 'sm_upload_document');
    formData.append('nonce', '<?php echo wp_create_nonce("sm_document_action"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json()).then(res => {
        btn.disabled = false; btn.innerText = 'بدء الرفع والأرشفة';
        if (res.success) {
            smShowNotification('تم أرشفة المستند بنجاح');
            smCloseUploadModal();
            smLoadDocuments();
            this.reset();
        } else {
            alert(res.data);
        }
    });
};

smLoadDocuments();
</script>
