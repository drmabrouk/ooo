<?php if (!defined('ABSPATH')) exit; ?>
<?php
$my_id = get_current_user_id();
$user = wp_get_current_user();
$roles = (array)$user->roles;
$is_official = in_array('sm_syndicate_admin', $roles) || in_array('sm_system_admin', $roles) || in_array('administrator', $roles);
$my_gov = get_user_meta($my_id, 'sm_governorate', true);

// Get member_id if current user is a member
$member_id = 0;
global $wpdb;
$member_by_wp = $wpdb->get_row($wpdb->prepare("SELECT id, governorate FROM {$wpdb->prefix}sm_members WHERE wp_user_id = %d", $my_id));
if ($member_by_wp) {
    $member_id = $member_by_wp->id;
    $my_gov = $member_by_wp->governorate;
}

$gov_label = SM_Settings::get_governorates()[$my_gov] ?? $my_gov;
?>

<div class="sm-messaging-wrapper" dir="rtl" style="display: flex; height: calc(100vh - 150px); min-height: 600px; background: #f0f2f5; border-radius: var(--sm-radius); overflow: hidden; border: 1px solid var(--sm-border-color); box-shadow: var(--sm-shadow);">

    <!-- Sidebar -->
    <div class="sm-msg-sidebar" style="width: 350px; background: #fff; border-left: 1px solid var(--sm-border-color); display: flex; flex-direction: column;">
        <div style="padding: 20px; border-bottom: 1px solid #f0f2f5; background: var(--sm-dark-color); color: #fff;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h2 style="margin:0; font-size: 1.2em; font-weight: 800; color:#fff;">المراسلات</h2>
                <div style="background: var(--sm-primary-color); padding: 4px 10px; border-radius: 20px; font-size: 10px; font-weight: 700;"><?php echo esc_html($gov_label); ?></div>
            </div>
            <?php if ($is_official): ?>
                <div style="position: relative;">
                    <span class="dashicons dashicons-search" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 18px;"></span>
                    <input type="text" id="sm-msg-search" placeholder="بحث عن عضو..." style="width: 100%; padding: 10px 40px 10px 15px; border-radius: 8px; border: none; background: rgba(255,255,255,0.1); color: #fff; font-size: 13px;">
                </div>
            <?php endif; ?>
        </div>

        <div id="sm-conversations-list" style="flex: 1; overflow-y: auto;">
            <!-- Loaded via JS -->
            <div style="text-align: center; padding: 40px; color: #94a3b8;">
                <div class="sm-loader-mini"></div>
                <p style="margin-top: 10px; font-size: 13px;">جاري تحميل البيانات...</p>
            </div>
        </div>
    </div>

    <!-- Main Chat -->
    <div class="sm-msg-main" style="flex: 1; display: flex; flex-direction: column; background: #fff; position: relative;">

        <!-- Welcome Screen (if no conversation selected) -->
        <div id="sm-msg-welcome" style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px; text-align: center; color: #94a3b8;">
            <div style="width: 120px; height: 120px; background: #f8fafc; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 20px;">
                <span class="dashicons dashicons-format-chat" style="font-size: 60px; width: 60px; height: 60px; color: #cbd5e0;"></span>
            </div>
            <h3 style="color: var(--sm-dark-color); margin-bottom: 10px;">مركز التواصل النقابي الموحد</h3>
            <p style="max-width: 400px; font-size: 14px; line-height: 1.6;">تواصل بشكل مباشر مع مسؤولي اللجنة الفرعية لمحافظتك. جميع المراسلات مشفرة وآمنة.</p>
        </div>

        <!-- Chat Header -->
        <div id="sm-msg-header" style="display: none; padding: 15px 30px; border-bottom: 1px solid #f0f2f5; background: #fff; z-index: 10; min-height: 80px; align-items: center;">
            <div style="display: flex; align-items: center; gap: 15px; width: 100%;">
                <button id="sm-msg-back" class="sm-mobile-only" style="background:none; border:none; color:var(--sm-primary-color); cursor:pointer;"><span class="dashicons dashicons-arrow-right-alt2"></span></button>
                <div id="sm-header-avatar" style="width: 45px; height: 45px; border-radius: 50%; overflow: hidden; background: #f1f5f9; border: 2px solid var(--sm-primary-color);"></div>
                <div style="flex: 1;">
                    <h3 id="sm-header-name" style="margin:0; font-size: 1.1em; font-weight: 800; color: var(--sm-dark-color);"></h3>
                    <div style="display: flex; align-items: center; gap: 5px; font-size: 11px; color: #38a169; font-weight: 600;">
                        <span style="width: 8px; height: 8px; background: #38a169; border-radius: 50%;"></span>
                        متصل باللجنة النقابية | <span id="sm-header-ticket-id" style="color: #64748b;"></span>
                    </div>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button onclick="location.reload()" title="تحديث" style="background: #f1f5f9; border: none; width: 36px; height: 36px; border-radius: 50%; cursor: pointer; color: #64748b;"><span class="dashicons dashicons-update"></span></button>
                </div>
            </div>
        </div>

        <!-- Messages Area -->
        <div id="sm-msg-body" style="display: none; flex: 1; padding: 30px; overflow-y: auto; background: #f0f2f5; display: flex; flex-direction: column; gap: 10px;">
            <!-- Messages load here -->
        </div>

        <!-- Input Area -->
        <div id="sm-msg-footer" style="display: none; padding: 20px 30px; background: #fff; border-top: 1px solid #f0f2f5;">
            <form id="sm-msg-form" style="display: flex; flex-direction: column; gap: 12px;">
                <input type="hidden" name="member_id" id="sm_chat_member_id">
                <input type="hidden" name="receiver_id" id="sm_chat_receiver_id">

                <div id="sm-file-preview" style="display: none; background: #f8fafc; padding: 10px 15px; border-radius: 8px; border: 1px solid var(--sm-border-color); display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 10px; font-size: 13px; color: var(--sm-primary-color); font-weight: 600;">
                        <span class="dashicons dashicons-paperclip"></span>
                        <span id="sm-file-name"></span>
                    </div>
                    <button type="button" onclick="clearFile()" style="background: none; border: none; color: #ef4444; cursor: pointer;"><span class="dashicons dashicons-no-alt"></span></button>
                </div>

                <div style="display: flex; gap: 15px; align-items: flex-end;">
                    <div style="flex: 1; position: relative;">
                        <textarea id="sm-msg-input" name="message" placeholder="اكتب رسالتك هنا... (Shift + Enter لسطر جديد)" style="width: 100%; min-height: 50px; max-height: 150px; padding: 12px 100px 12px 20px; border-radius: 25px; border: 1px solid var(--sm-border-color); background: #f8fafc; resize: none; font-size: 14px; transition: 0.3s;"></textarea>

                        <div style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); display: flex; gap: 8px;">
                            <label for="sm_msg_file" style="cursor: pointer; width: 36px; height: 36px; background: #fff; border: 1px solid #e2e8f0; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #64748b; transition: 0.2s;">
                                <span class="dashicons dashicons-paperclip"></span>
                            </label>
                            <input type="file" id="sm_msg_file" name="message_file" style="display: none;" onchange="handleFileSelect(this)">
                        </div>
                    </div>
                    <button type="submit" id="sm-msg-submit" style="width: 50px; height: 50px; border-radius: 50%; background: var(--sm-primary-color); border: none; color: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: 0.3s; box-shadow: 0 4px 12px rgba(246, 48, 73, 0.3);">
                        <span class="dashicons dashicons-send" style="transform: rotate(180deg); margin-bottom: 2px;"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.sm-loader-mini { border: 3px solid #f3f3f3; border-top: 3px solid var(--sm-primary-color); border-radius: 50%; width: 24px; height: 24px; animation: sm-spin 1s linear infinite; display: inline-block; }
@keyframes sm-spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

.sm-conv-item { padding: 15px 20px; cursor: pointer; border-bottom: 1px solid #f8fafc; transition: 0.2s; display: flex; align-items: center; gap: 15px; }
.sm-conv-item:hover { background: #f8fafc; }
.sm-conv-item.active { background: #fff1f2; border-right: 4px solid var(--sm-primary-color); }
.sm-conv-avatar { width: 50px; height: 50px; border-radius: 50%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0; }
.sm-conv-info { flex: 1; min-width: 0; }
.sm-conv-name { font-weight: 800; font-size: 14px; color: var(--sm-dark-color); margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sm-conv-last { font-size: 12px; color: #94a3b8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

.sm-msg-bubble { max-width: 80%; padding: 12px 18px; border-radius: 18px; font-size: 14px; line-height: 1.6; position: relative; margin-bottom: 5px; }
.sm-msg-sent { align-self: flex-end; background: var(--sm-primary-color); color: #fff; border-bottom-left-radius: 4px; }
.sm-msg-received { align-self: flex-start; background: #fff; color: var(--sm-dark-color); border-bottom-right-radius: 4px; border: 1px solid #e2e8f0; }
.sm-msg-meta { font-size: 10px; margin-top: 5px; opacity: 0.7; display: flex; align-items: center; gap: 4px; }
.sm-msg-sent .sm-msg-meta { justify-content: flex-end; }
.sm-msg-received .sm-msg-meta { justify-content: flex-start; }

.sm-file-card { display: block; margin-top: 10px; padding: 12px; background: rgba(0,0,0,0.05); border-radius: 10px; border: 1px solid rgba(0,0,0,0.1); text-decoration: none !important; color: inherit !important; }
.sm-msg-sent .sm-file-card { background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.2); }

#sm-msg-input:focus { border-color: var(--sm-primary-color); outline: none; box-shadow: 0 0 0 3px rgba(246, 48, 73, 0.1); background: #fff; }

.sm-msg-bubble {
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    clear: both;
}

.sm-pulse { animation: sm-pulse-red 2s infinite; }
@keyframes sm-pulse-red { 0% { box-shadow: 0 0 0 0 rgba(246, 48, 73, 0.4); } 70% { box-shadow: 0 0 0 10px rgba(246, 48, 73, 0); } 100% { box-shadow: 0 0 0 0 rgba(246, 48, 73, 0); } }

.sm-mobile-only { display: none; }
@media (max-width: 768px) {
    .sm-mobile-only { display: block; }
    .sm-msg-sidebar { width: 100% !important; border-left: none !important; }
    .sm-msg-main { display: none !important; }
    .sm-messaging-wrapper.chat-active .sm-msg-sidebar { display: none !important; }
    .sm-messaging-wrapper.chat-active .sm-msg-main { display: flex !important; width: 100% !important; }
}
</style>

<script>
(function($) {
    let currentActiveMemberId = null;
    let pollInterval = null;
    let fetchingMessages = false;
    const isOfficial = <?php echo $is_official ? 'true' : 'false'; ?>;
    const myId = <?php echo $my_id; ?>;
    const myMemberId = <?php echo $member_id; ?>;
    const myGovLabel = "<?php echo esc_js($gov_label); ?>";

    window.handleFileSelect = function(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif'];
            if (!allowedTypes.includes(file.type)) {
                alert('عذراً، يسمح فقط بملفات PDF والصور (JPG, PNG, GIF).');
                input.value = '';
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                alert('عذراً، الحد الأقصى لحجم الملف هو 5 ميجابايت.');
                input.value = '';
                return;
            }
            $('#sm-file-name').text(file.name);
            $('#sm-file-preview').fadeIn();
        }
    };

    window.clearFile = function() {
        $('#sm_msg_file').val('');
        $('#sm-file-preview').fadeOut();
    };

    window.loadConversations = function() {
        const listContainer = $('#sm-conversations-list');

        if (!isOfficial) {
            // For members, show the Committee and assigned officials
            listContainer.empty();
            const formData = new FormData();
            formData.append('action', 'sm_get_conversations_ajax');
            formData.append('nonce', '<?php echo wp_create_nonce("sm_message_action"); ?>');

            fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                listContainer.empty();
                // Add Committee Item
                const committeeItem = $(`
                    <div class="sm-conv-item active" onclick="selectConversation(${myMemberId}, 'اللجنة النقابية بمحافظة ${myGovLabel}', 0, this)">
                        <div class="sm-conv-avatar" style="background: var(--sm-dark-color); color: #fff;">
                            <span class="dashicons dashicons-building"></span>
                        </div>
                        <div class="sm-conv-info">
                            <div class="sm-conv-name">اللجنة النقابية بمحافظة ${myGovLabel}</div>
                            <div class="sm-conv-last">قناة التواصل الرسمية المباشرة</div>
                        </div>
                    </div>
                `);
                listContainer.append(committeeItem);

                // Add assigned officials
                if (res.success && res.data.officials) {
                    res.data.officials.forEach(o => {
                        const off = o.official;
                        const item = $(`
                            <div class="sm-conv-item" style="border-right: 2px solid #e2e8f0; margin-right: 10px;" title="مسؤول باللجنة" onclick="selectConversation(${myMemberId}, '${off.display_name}', ${off.ID}, this)">
                                <div class="sm-conv-avatar" style="width:35px; height:35px;">
                                    <img src="${off.avatar}" style="width:100%;">
                                </div>
                                <div class="sm-conv-info">
                                    <div class="sm-conv-name" style="font-size:12px;">${off.display_name}</div>
                                    <div class="sm-conv-last" style="font-size:10px;">مسؤول اللجنة الفرعية</div>
                                </div>
                            </div>
                        `);
                        listContainer.append(item);
                    });
                }

                // Auto-load committee for members
                selectConversation(myMemberId, `اللجنة النقابية بمحافظة ${myGovLabel}`, 0);
            });
            return;
        }

        // For officials, load member tickets
        const formData = new FormData();
        formData.append('action', 'sm_get_conversations_ajax');
        formData.append('nonce', '<?php echo wp_create_nonce("sm_message_action"); ?>');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            listContainer.empty();
            if (res.success && res.data.conversations && res.data.conversations.length > 0) {
                res.data.conversations.forEach(c => {
                    const activeClass = (currentActiveMemberId == c.member.id) ? 'active' : '';
                    const avatar = c.member.avatar || 'https://www.gravatar.com/avatar/?d=mp';
                    const item = $(`
                        <div class="sm-conv-item ${activeClass}" data-member-id="${c.member.id}" onclick="selectConversation(${c.member.id}, '${c.member.name}', ${c.member.wp_user_id}, this)">
                            <div class="sm-conv-avatar">
                                <img src="${avatar}" style="width:100%; height:100%; object-fit:cover;">
                            </div>
                            <div class="sm-conv-info">
                                <div class="sm-conv-name">${c.member.name}</div>
                                <div class="sm-conv-last">${c.last_message ? c.last_message.message : 'لا يوجد رسائل'}</div>
                            </div>
                        </div>
                    `);
                    listContainer.append(item);
                });
            } else {
                listContainer.html('<div style="text-align:center; padding:40px; color:#94a3b8; font-size:13px;">لا توجد طلبات أو استفسارات حالياً</div>');
            }
        });
    };

    window.selectConversation = function(memberId, name, wpUserId, element) {
        currentActiveMemberId = memberId;

        $('.sm-conv-item').removeClass('active sm-pulse');
        if (element) $(element).addClass('active');

        $('.sm-messaging-wrapper').addClass('chat-active');
        $('#sm-msg-welcome').hide();
        $('#sm-msg-header, #sm-msg-body, #sm-msg-footer').show();

        $('#sm-header-name').text(name);
        $('#sm-header-ticket-id').text('تذكرة رقم: #' + memberId);

        // Find avatar from element if possible or use gravatar as fallback
        const avatarUrl = $(element).find('img').attr('src') || `https://www.gravatar.com/avatar/${wpUserId}?d=mp`;
        $('#sm-header-avatar').html(`<img src="${avatarUrl}" style="width:100%; height:100%; object-fit:cover;">`);

        $('#sm_chat_member_id').val(memberId);
        $('#sm_chat_receiver_id').val(isOfficial ? wpUserId : 0);

        fetchMessages(memberId);

        if (pollInterval) clearInterval(pollInterval);

        const startPolling = (ms) => {
            if (pollInterval) clearInterval(pollInterval);
            pollInterval = setInterval(() => {
                if (!document.hidden) fetchMessages(memberId, true);
            }, ms);
        };

        startPolling(4000);

        $(window).off('focus blur').on('focus', () => startPolling(4000)).on('blur', () => startPolling(15000));
    };

    $('#sm-msg-back').on('click', () => {
        $('.sm-messaging-wrapper').removeClass('chat-active');
    });

    window.fetchMessages = function(memberId, isPolling = false) {
        if (fetchingMessages) return;
        fetchingMessages = true;

        const body = $('#sm-msg-body');
        if (!isPolling) body.html('<div style="text-align:center; padding:50px;"><div class="sm-loader-mini"></div></div>');

        const formData = new FormData();
        formData.append('action', 'sm_get_conversation_ajax');
        formData.append('member_id', memberId);
        formData.append('nonce', '<?php echo wp_create_nonce("sm_message_action"); ?>');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            fetchingMessages = false;
            if (res.success) {
                let html = '';
                res.data.forEach(m => {
                    const isSent = m.sender_id == myId;
                    let fileHtml = '';
                    if (m.file_url) {
                        const fileName = m.file_url.split('/').pop();
                        const isImg = m.file_url.match(/\.(jpg|jpeg|png|gif|webp)$/i);
                        if (isImg) {
                            fileHtml = `<a href="${m.file_url}" target="_blank" class="sm-file-card"><img src="${m.file_url}" style="max-width:200px; border-radius:8px; display:block; margin-bottom:5px;"><span>${fileName}</span></a>`;
                        } else {
                            fileHtml = `<a href="${m.file_url}" target="_blank" class="sm-file-card"><span class="dashicons dashicons-pdf" style="vertical-align:middle; margin-left:5px;"></span> ${fileName}</a>`;
                        }
                    }

                    html += `
                        <div class="sm-msg-bubble ${isSent ? 'sm-msg-sent' : 'sm-msg-received'}">
                            <div style="font-weight:800; font-size:11px; margin-bottom:5px; opacity:0.9;">${m.sender_name || 'مستخدم'}</div>
                            <div style="word-break: break-word;">${m.message}</div>
                            ${fileHtml}
                            <div class="sm-msg-meta">
                                <span>${m.created_at}</span>
                                ${isSent ? '<span class="dashicons dashicons-yes" style="font-size:14px; width:14px; height:14px;"></span>' : ''}
                            </div>
                        </div>
                    `;
                });

                const oldHtml = body.data('current-html');
                if (oldHtml !== html) {
                    body.html(html).data('current-html', html);
                    body.scrollTop(body[0].scrollHeight);

                    if (isPolling && !$('.sm-conv-item.active').length) {
                         $(`.sm-conv-item[data-member-id="${memberId}"]`).addClass('sm-pulse');
                    }
                }
            }
        });
    }

    $('#sm-msg-form').on('submit', function(e) {
        e.preventDefault();
        const btn = $('#sm-msg-submit');
        const input = $('#sm-msg-input');
        if (!input.val().trim() && !$('#sm_msg_file').val()) return;

        btn.prop('disabled', true).css('opacity', '0.6');

        const formData = new FormData(this);
        formData.append('action', 'sm_send_message_ajax');
        formData.append('nonce', '<?php echo wp_create_nonce("sm_message_action"); ?>');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            btn.prop('disabled', false).css('opacity', '1');
            if (res.success) {
                input.val('').css('height', '50px');
                clearFile();
                fetchingMessages = false;
                fetchMessages($('#sm_chat_member_id').val());
            } else alert('خطأ: ' + res.data);
        })
        .catch(err => {
            btn.prop('disabled', false).css('opacity', '1');
            fetchingMessages = false;
            console.error(err);
        });
    });

    // Enter to Send
    $('#sm-msg-input').on('keydown', function(e) {
        if (e.which == 13 && !e.shiftKey) {
            e.preventDefault();
            $('#sm-msg-form').submit();
        }
    });

    // Sidebar Search
    $('#sm-msg-search').on('input', function() {
        const val = $(this).val().toLowerCase();
        $('.sm-conv-item').each(function() {
            const text = $(this).find('.sm-conv-name').text().toLowerCase();
            $(this).toggle(text.indexOf(val) > -1);
        });
    });

    // Auto-resize textarea
    $('#sm-msg-input').on('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });

    loadConversations();

})(jQuery);
</script>
