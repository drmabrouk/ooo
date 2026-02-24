<?php if (!defined('ABSPATH')) exit; ?>
<?php
$user = wp_get_current_user();
$generated = SM_DB::get_pub_documents();
$syndicate = SM_Settings::get_syndicate_info();
?>

<!-- Include Google Fonts for Publishing -->
<link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Cairo:wght@400;700&family=Rubik:wght@400;700;900&family=Lateef&family=Aref+Ruqaa&family=Libre+Barcode+39&display=swap" rel="stylesheet">
<!-- HTML2Canvas for Image Export -->
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>

<div class="sm-publishing-center" dir="rtl">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; background: #fff; padding: 25px; border-radius: 15px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
        <div>
            <h2 style="margin:0; font-weight: 900; color: #111F35; font-size: 1.8em;">مركز الطباعة والنشر الرقمي</h2>
            <p style="margin: 5px 0 0 0; color: #718096; font-size: 0.9em;">توليد المستندات الرسمية، التقارير، والشهادات المعتمدة</p>
        </div>
    </div>

    <!-- Main Navigation Tabs -->
    <div class="sm-tabs-wrapper" style="display: flex; gap: 10px; margin-bottom: 30px; border-bottom: 2px solid #edf2f7; padding-bottom: 0;">
        <button class="sm-tab-nav-btn sm-active" onclick="smOpenInternalTab('create-document', this)">
            <span class="dashicons dashicons-edit"></span> إنشاء مستند جديد
        </button>
        <button class="sm-tab-nav-btn" onclick="smOpenInternalTab('identity-settings', this)">
            <span class="dashicons dashicons-admin-generic"></span> إعدادات الهوية الرسمية
        </button>
        <a href="<?php echo add_query_arg(['sm_tab' => 'global-archive', 'sub_tab' => 'issued']); ?>" class="sm-tab-nav-btn" style="text-decoration:none;">
            <span class="dashicons dashicons-media-spreadsheet"></span> سجل المستندات الصادرة <span class="dashicons dashicons-external" style="font-size:12px;"></span>
        </a>
    </div>

    <!-- TAB: CREATE DOCUMENT -->
    <div id="create-document" class="sm-internal-tab">
        <div style="display: grid; grid-template-columns: 1fr 320px; gap: 30px;">

            <!-- EDITOR COLUMN -->
            <div style="background: #fff; padding: 35px; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: var(--sm-shadow);">
                <div style="display: grid; grid-template-columns: 1fr 200px; gap: 20px; margin-bottom: 25px;">
                    <div>
                        <label class="sm-label" style="font-weight: 800; color: #111F35;">عنوان المستند:</label>
                        <input type="text" id="pub_doc_title" class="sm-input" placeholder="مثال: تقرير المعاينة الفنية" style="font-size: 1.1em; border-width: 2px;">
                    </div>
                    <div>
                        <label class="sm-label" style="font-weight: 800; color: #111F35;">نوع التصميم:</label>
                        <select id="pub_doc_type" class="sm-select" style="border-width: 2px; height: 48px;">
                            <option value="report">تقرير رسمي</option>
                            <option value="statement">إفادة رسمية</option>
                            <option value="certificate">شهادة معتمدة</option>
                        </select>
                    </div>
                    <div>
                        <label class="sm-label" style="font-weight: 800; color: #111F35;">الرسوم (اختياري):</label>
                        <input type="number" id="pub_doc_fees" class="sm-input" placeholder="0.00" style="border-width: 2px; height: 48px;">
                    </div>
                </div>

                <!-- TOOLBAR -->
                <div id="pub-editor-toolbar" style="background: #f8fafc; padding: 15px; border: 1px solid #e2e8f0; border-radius: 12px 12px 0 0; display: flex; gap: 10px; flex-wrap: wrap; align-items: center; border-bottom: none;">

                    <!-- FONT SELECT -->
                    <select onchange="smExecCommand('fontName', this.value)" class="sm-select" style="width: 140px; height: 36px; font-size: 12px;">
                        <option value="Cairo">Cairo (عصري)</option>
                        <option value="Amiri">Amiri (كلاسيكي)</option>
                        <option value="Lateef">Lateef (فني)</option>
                        <option value="Aref Ruqaa">Aref Ruqaa (رقعة)</option>
                        <option value="Arial">Arial (عالمي)</option>
                    </select>

                    <select id="editor-font-size" class="sm-select" style="width: 80px; height: 36px; font-size: 12px;" onchange="smSetFontSize(this.value)">
                        <option value="14px">14</option>
                        <option value="16px" selected>16</option>
                        <option value="18px">18</option>
                        <option value="20px">20</option>
                        <option value="24px">24</option>
                        <option value="30px">30</option>
                    </select>

                    <div class="toolbar-divider"></div>

                    <button onclick="smExecCommand('bold')" class="editor-tool-btn" title="عريض"><span class="dashicons dashicons-editor-bold"></span></button>
                    <button onclick="smExecCommand('italic')" class="editor-tool-btn" title="مائل"><span class="dashicons dashicons-editor-italic"></span></button>
                    <button onclick="smExecCommand('underline')" class="editor-tool-btn" title="تحته خط"><span class="dashicons dashicons-editor-underline"></span></button>

                    <div class="toolbar-divider"></div>

                    <button onclick="smExecCommand('justifyRight')" class="editor-tool-btn"><span class="dashicons dashicons-editor-alignright"></span></button>
                    <button onclick="smExecCommand('justifyCenter')" class="editor-tool-btn"><span class="dashicons dashicons-editor-aligncenter"></span></button>
                    <button onclick="smExecCommand('justifyLeft')" class="editor-tool-btn"><span class="dashicons dashicons-editor-alignleft"></span></button>

                    <div class="toolbar-divider"></div>

                    <button onclick="smExecCommand('insertUnorderedList')" class="editor-tool-btn"><span class="dashicons dashicons-editor-ul"></span></button>
                    <button onclick="smSetLineHeight('1.2')" class="editor-tool-btn" title="تباعد ضيق">S</button>
                    <button onclick="smSetLineHeight('1.8')" class="editor-tool-btn" title="تباعد واسع">L</button>

                    <div class="toolbar-divider"></div>

                    <input type="color" onchange="smExecCommand('foreColor', this.value)" style="width:30px; height:30px; padding:0; border:none; background:none; cursor:pointer;" title="لون الخط">
                </div>

                <!-- THE EDITOR CANVAS -->
                <div id="pub-document-editor" contenteditable="true" style="min-height: 700px; padding: 60px; border: 2px solid #e2e8f0; border-radius: 0 0 12px 12px; background: #fff; line-height: 1.6; font-family: 'Cairo', sans-serif; outline: none; font-size: 16px;">
                    <p style="text-align: center;"><br></p>
                </div>

                <div style="margin-top: 30px; display: flex; justify-content: space-between; align-items: center;">
                    <div style="color: #718096; font-size: 12px;">* سيتم دمج بيانات الهوية الرسمية آلياً عند التصدير.</div>
                    <div style="display: flex; gap: 15px;">
                        <button onclick="smGenerateDocument('pdf')" class="sm-btn pub-action-btn" style="width:auto; background: #111F35; padding: 0 35px; border-radius: 10px;"><span class="dashicons dashicons-pdf"></span> توليد وحفظ PDF</button>
                        <button onclick="smGenerateDocument('image')" class="sm-btn pub-action-btn" style="width:auto; background: #27ae60; padding: 0 35px; border-radius: 10px;"><span class="dashicons dashicons-format-image"></span> تصدير صورة (HQ)</button>
                    </div>
                </div>
            </div>

            <!-- SIDEBAR CONTROLS -->
            <div style="display: flex; flex-direction: column; gap: 20px;">

                <!-- DOCUMENT OPTIONS -->
                <div class="sm-sidebar-card">
                    <h4 class="card-title"><span class="dashicons dashicons-admin-tools"></span> خيارات المستند</h4>
                    <div style="display: flex; flex-direction: column; gap: 15px;">
                        <label class="option-check"><input type="checkbox" id="pub_include_header" checked> الترويسة والشعار الرسمي</label>
                        <label class="option-check"><input type="checkbox" id="pub_include_footer" checked> التذييل والبيانات الرسمية</label>
                        <label class="option-check"><input type="checkbox" id="pub_include_qr" checked> رمز الاستجابة السريع (QR)</label>
                        <label class="option-check"><input type="checkbox" id="pub_include_barcode"> الباركود التسلسلي (Barcode)</label>
                        <div style="margin-top: 5px;">
                            <label class="sm-label" style="font-size: 13px;">نمط الإطار:</label>
                            <select id="pub_frame_type" class="sm-select" style="height: 35px; font-size: 12px;">
                                <option value="none">بدون إطار</option>
                                <option value="simple">إطار بسيط</option>
                                <option value="double">إطار مزدوج</option>
                                <option value="ornate">إطار مزخرف</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- QUICK TAGS -->
                <div class="sm-sidebar-card">
                    <h4 class="card-title"><span class="dashicons dashicons-tag"></span> وسوم تلقائية</h4>
                    <p style="font-size: 11px; color: #718096; margin-bottom: 12px;">انقر للإدراج في مكان المؤشر:</p>
                    <div class="placeholder-grid">
                        <button onclick="smInsertPlaceholder('{MEMBER_NAME}')" class="placeholder-tag">اسم العضو</button>
                        <button onclick="smInsertPlaceholder('{NATIONAL_ID}')" class="placeholder-tag">الرقم القومي</button>
                        <button onclick="smInsertPlaceholder('{MEMBERSHIP_NO}')" class="placeholder-tag">رقم القيد</button>
                        <button onclick="smInsertPlaceholder('{SERIAL_NO}')" class="placeholder-tag">رقم المرجع</button>
                        <button onclick="smInsertPlaceholder('{DATE_NOW}')" class="placeholder-tag">تاريخ اليوم</button>
                    </div>
                </div>

                <!-- INSTRUCTIONS CARD -->
                <div class="sm-sidebar-card" style="background: #f0f7ff; border-color: #bee3f8;">
                    <h4 class="card-title" style="color: #2b6cb0; border-bottom-color: #bee3f8;"><span class="dashicons dashicons-info"></span> دليل المركز</h4>
                    <div style="font-size: 12px; color: #2c5282; line-height: 1.7;">
                        <p style="margin-top:0;"><strong>أنواع المستندات:</strong></p>
                        <ul style="padding-right: 15px; margin-bottom: 15px;">
                            <li><strong>التقارير:</strong> تصميم منظم للمعاينة الفنية.</li>
                            <li><strong>الإفادات:</strong> تخطيط رسمي للمخاطبات.</li>
                            <li><strong>الشهادات:</strong> مظهر احترافي للاجتياز.</li>
                        </ul>
                        <p><strong>المميزات المتقدمة:</strong></p>
                        <ul style="padding-right: 15px;">
                            <li>التذييل التلقائي بالبيانات الرسمية.</li>
                            <li>توليد QR للتحقق السريع.</li>
                            <li>التعامل الذكي مع تعدد الصفحات.</li>
                            <li>تصدير الصور بجودة عالية (HQ).</li>
                        </ul>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- TAB: IDENTITY SETTINGS -->
    <div id="identity-settings" class="sm-internal-tab" style="display: none;">
        <div style="background: #fff; padding: 35px; border-radius: 20px; border: 1px solid #e2e8f0; max-width: 800px; margin: 0 auto;">
            <h3 style="margin-top: 0; margin-bottom: 25px; color: #111F35; display: flex; align-items: center; gap: 10px;">
                <span class="dashicons dashicons-admin-appearance" style="font-size: 28px; width: 28px; height: 28px;"></span> إعدادات الهوية الرسمية للنظام
            </h3>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="sm-form-group" style="grid-column: span 2;">
                    <label class="sm-label">اسم النقابة / المنشأة:</label>
                    <input type="text" id="sys_syndicate_name" class="sm-input" value="<?php echo esc_attr($syndicate['syndicate_name']); ?>">
                </div>

                <div class="sm-form-group">
                    <label class="sm-label">الجهة التابع لها (مثلاً: وزارة الصحة):</label>
                    <input type="text" id="sys_authority_name" class="sm-input" value="<?php echo esc_attr($syndicate['authority_name'] ?? ''); ?>">
                </div>

                <div class="sm-form-group">
                    <label class="sm-label">اسم المسؤول المعتمد:</label>
                    <input type="text" id="sys_officer_name" class="sm-input" value="<?php echo esc_attr($syndicate['syndicate_officer_name']); ?>">
                </div>

                <div class="sm-form-group">
                    <label class="sm-label">رقم الهاتف الرسمي:</label>
                    <input type="text" id="sys_phone" class="sm-input" value="<?php echo esc_attr($syndicate['phone']); ?>">
                </div>

                <div class="sm-form-group">
                    <label class="sm-label">البريد الإلكتروني الرسمي:</label>
                    <input type="email" id="sys_email" class="sm-input" value="<?php echo esc_attr($syndicate['email']); ?>">
                </div>

                <div class="sm-form-group">
                    <label class="sm-label">رابط الموقع الإلكتروني:</label>
                    <input type="url" id="sys_website" class="sm-input" value="<?php echo esc_attr($syndicate['website_url'] ?? ''); ?>" placeholder="https://example.com">
                </div>

                <div class="sm-form-group" style="grid-column: span 2;">
                    <label class="sm-label">العنوان الكامل:</label>
                    <input type="text" id="sys_address" class="sm-input" value="<?php echo esc_attr($syndicate['address']); ?>">
                </div>

                <div style="grid-column: span 2; display: grid; grid-template-columns: 1fr 1fr; gap: 20px; background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0;">
                    <div>
                        <label class="sm-label">شعار النقابة (الرئيسي):</label>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <img id="preview_syndicate_logo" src="<?php echo esc_url($syndicate['syndicate_logo']); ?>" style="width: 60px; height: 60px; object-fit: contain; background: #fff; border: 1px solid #ddd; border-radius: 8px;">
                            <button onclick="smUploadIdentityImg('syndicate_logo')" class="sm-btn sm-btn-outline" style="font-size: 11px;">تغيير الشعار</button>
                            <input type="hidden" id="sys_syndicate_logo" value="<?php echo esc_attr($syndicate['syndicate_logo']); ?>">
                        </div>
                    </div>
                    <div>
                        <label class="sm-label">شعار الجهة (مثلاً: شعار الوزارة):</label>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <img id="preview_authority_logo" src="<?php echo esc_url($syndicate['authority_logo'] ?? ''); ?>" style="width: 60px; height: 60px; object-fit: contain; background: #fff; border: 1px solid #ddd; border-radius: 8px;">
                            <button onclick="smUploadIdentityImg('authority_logo')" class="sm-btn sm-btn-outline" style="font-size: 11px;">تغيير الشعار</button>
                            <input type="hidden" id="sys_authority_logo" value="<?php echo esc_attr($syndicate['authority_logo'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div style="margin-top: 30px; border-top: 1px solid #eee; padding-top: 25px; text-align: left;">
                <button onclick="smSaveIdentitySettings()" class="sm-btn pub-action-btn" style="width: auto; padding: 0 40px; background: #27ae60; border-radius: 10px; font-weight: bold;">حفظ إعدادات الهوية</button>
            </div>
        </div>
    </div>

</div>

<style>
.sm-tab-nav-btn {
    padding: 15px 30px; border: none; background: none; cursor: pointer; font-weight: 700; color: #718096;
    border-bottom: 3px solid transparent; transition: 0.3s; font-size: 15px; display: flex; align-items: center; gap: 10px;
}
.sm-tab-nav-btn:hover { color: #111F35; background: #f8fafc; }
.sm-tab-nav-btn.sm-active { color: #111F35; border-bottom-color: #111F35; background: #f8fafc; }

.sm-sidebar-card { background: #fff; padding: 25px; border-radius: 15px; border: 1px solid #e2e8f0; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
.card-title { margin: 0 0 20px 0; font-size: 1.1em; font-weight: 800; color: #111F35; border-bottom: 1px solid #f0f4f8; padding-bottom: 12px; display: flex; align-items: center; gap: 10px; }

.pub-action-btn {
    transition: all 0.3s ease !important;
    transform: translateY(0);
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}
.pub-action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.15);
    opacity: 0.9;
}

.editor-tool-btn {
    width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;
    background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; cursor: pointer; color: #4a5568;
    transition: 0.2s; font-weight: bold;
}
.editor-tool-btn:hover { background: #edf2f7; color: #111F35; border-color: #111F35; }

.toolbar-divider { height: 24px; width: 1px; background: #cbd5e0; margin: 0 5px; }

.placeholder-tag {
    font-size: 11px; background: #edf2f7; border: 1px solid #cbd5e0; border-radius: 6px;
    padding: 8px 10px; cursor: pointer; transition: 0.2s; font-weight: 700; color: #2d3748; text-align: center;
}
.placeholder-tag:hover { background: #111F35; color: #fff; border-color: #111F35; }
.placeholder-grid { display: grid; grid-template-columns: 1fr; gap: 8px; }

.option-check { display: flex; align-items: center; gap: 10px; font-size: 13px; font-weight: 600; color: #4a5568; cursor: pointer; }
.option-check input { width: 18px; height: 18px; cursor: pointer; }
</style>

<script>
function smExecCommand(cmd, val = null) {
    document.execCommand(cmd, false, val);
    document.getElementById('pub-document-editor').focus();
}

function smSetFontSize(size) {
    const sel = window.getSelection();
    if (sel.rangeCount) {
        const range = sel.getRangeAt(0);
        const span = document.createElement('span');
        span.style.fontSize = size;
        range.surroundContents(span);
    }
}

function smSetLineHeight(height) {
    const editor = document.getElementById('pub-document-editor');
    editor.style.lineHeight = height;
}

function smInsertPlaceholder(text) {
    document.execCommand('insertText', false, text);
}

async function smGenerateDocument(format) {
    const title = document.getElementById('pub_doc_title').value;
    const content = document.getElementById('pub-document-editor').innerHTML;
    const docType = document.getElementById('pub_doc_type').value;

    if (!title) {
        smShowNotification('يرجى إدخال عنوان للمستند', true);
        return;
    }

    smShowNotification('جاري توليد المستند...');

    const fd = new FormData();
    fd.append('action', 'sm_generate_pub_doc');
    fd.append('title', title);
    fd.append('content', content);
    fd.append('format', format);
    fd.append('doc_type', docType);
    fd.append('fees', document.getElementById('pub_doc_fees').value);
    fd.append('header', document.getElementById('pub_include_header').checked ? 1 : 0);
    fd.append('footer', document.getElementById('pub_include_footer').checked ? 1 : 0);
    fd.append('qr', document.getElementById('pub_include_qr').checked ? 1 : 0);
    fd.append('barcode', document.getElementById('pub_include_barcode').checked ? 1 : 0);
    fd.append('frame_type', document.getElementById('pub_frame_type').value);
    fd.append('nonce', '<?php echo wp_create_nonce("sm_pub_action"); ?>');

    if (format === 'image') {
        const editor = document.getElementById('pub-document-editor');
        const canvas = await html2canvas(editor, {
            scale: 2,
            useCORS: true,
            backgroundColor: '#ffffff'
        });
        const link = document.createElement('a');
        link.download = title + '.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
        smShowNotification('تم تحميل الصورة بنجاح');
    }

    fetch(ajaxurl, { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            if (format === 'pdf') {
                window.open(res.data.url, '_blank');
            }
            smShowNotification('تم حفظ المستند في السجلات بنجاح');
            setTimeout(() => location.reload(), 1500);
        } else {
            alert('خطأ: ' + res.data);
        }
    });
}

function smDownloadGenerated(id, format) {
    window.open(ajaxurl + '?action=sm_print_pub_doc&id=' + id + '&format=' + format, '_blank');
}

function smUploadIdentityImg(type) {
    const frame = wp.media({
        title: 'اختر الشعار الرسمي',
        button: { text: 'استخدام كشعار' },
        multiple: false
    });
    frame.on('select', function() {
        const attachment = frame.state().get('selection').first().toJSON();
        document.getElementById('sys_' + type).value = attachment.url;
        document.getElementById('preview_' + type).src = attachment.url;
    });
    frame.open();
}

function smSaveIdentitySettings() {
    const btn = event.currentTarget;
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<span class="dashicons dashicons-update spin"></span> جاري الحفظ...';
    btn.disabled = true;

    const fd = new FormData();
    fd.append('action', 'sm_save_pub_identity');
    fd.append('syndicate_name', document.getElementById('sys_syndicate_name').value);
    fd.append('authority_name', document.getElementById('sys_authority_name').value);
    fd.append('syndicate_officer_name', document.getElementById('sys_officer_name').value);
    fd.append('phone', document.getElementById('sys_phone').value);
    fd.append('email', document.getElementById('sys_email').value);
    fd.append('website_url', document.getElementById('sys_website').value);
    fd.append('address', document.getElementById('sys_address').value);
    fd.append('syndicate_logo', document.getElementById('sys_syndicate_logo').value);
    fd.append('authority_logo', document.getElementById('sys_authority_logo').value);
    fd.append('nonce', '<?php echo wp_create_nonce("sm_pub_action"); ?>');

    fetch(ajaxurl, { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            smShowNotification('تم حفظ إعدادات الهوية بنجاح');
            btn.innerHTML = '<span class="dashicons dashicons-yes"></span> تم الحفظ';
            setTimeout(() => location.reload(), 1000);
        } else {
            smShowNotification(res.data, true);
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        }
    });
}

function smFilterLogs() {
    const val = document.getElementById('pub_log_search').value.toLowerCase();
    const rows = document.querySelectorAll('#pub-logs-table tbody tr');
    rows.forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(val) ? '' : 'none';
    });
}

window.smOpenInternalTab = function(tabId, element) {
    document.querySelectorAll('.sm-internal-tab').forEach(t => t.style.display = 'none');
    const target = document.getElementById(tabId);
    if (target) target.style.display = 'block';

    if (element) {
        element.parentElement.querySelectorAll('.sm-tab-nav-btn').forEach(b => b.classList.remove('sm-active'));
        element.classList.add('sm-active');
    }
}
</script>
