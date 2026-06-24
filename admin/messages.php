<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/admin_layout.php';
send_security_headers();
require_admin();

$conn     = get_db();
$messages = $conn->query("SELECT * FROM contact ORDER BY id DESC");

if ($messages === false) {
    error_log('messages.php query error: ' . $conn->error);
    $messages = null;
}

log_activity($conn, 'VIEW_MESSAGES', 'Viewed messages list');
$conn->close();

$csrf = csrf_token();

admin_head('Messages', 'messages');
?>

<div class="pl-page-header">
    <div>
        <h1>Messages</h1>
        <div class="pl-breadcrumb">Manage → <span>Contact Messages</span></div>
    </div>
    <div style="display:flex;align-items:center;gap:10px;">
        <?php if($messages && $messages->num_rows > 0): ?>
        <div class="pl-badge pl-badge-orange" style="padding:8px 16px;font-size:13px;">
            <i class="fas fa-envelope me-1"></i>
            <?= $messages->num_rows ?> Message<?= $messages->num_rows !== 1 ? 's' : '' ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="pl-card">
    <div class="pl-card-header">
        <h5><i class="fas fa-envelope me-2" style="color:#f07b14"></i>All Messages</h5>
    </div>
    <div class="pl-card-body">
        <?php if ($messages === null): ?>
        <div style="padding:24px;">
            <div class="alert alert-danger mb-0" style="border-radius:12px;">
                <i class="fas fa-exclamation-circle me-2"></i>Could not load messages. Please try again.
            </div>
        </div>
        <?php elseif ($messages->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="pl-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Subject</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $messages->fetch_assoc()): ?>
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#f07b14,#d96a00);display:flex;align-items:center;justify-content:center;color:#fff;font-size:12px;font-weight:700;flex-shrink:0;">
                                <?= strtoupper(substr(h($row['name']), 0, 1)) ?>
                            </div>
                            <strong><?= h($row['name']) ?></strong>
                        </div>
                    </td>
                    <td style="color:#6b7280;"><?= h($row['email']) ?></td>
                    <td style="color:#6b7280;"><?= h($row['phone']) ?></td>
                    <td>
                        <span class="pl-badge pl-badge-orange">
                            <?= h(mb_substr($row['subject'], 0, 30)) ?>
                        </span>
                    </td>
                    <td style="color:#9ca3af;font-size:12px;white-space:nowrap;">
                        <i class="fas fa-calendar-alt me-1"></i>
                        <?= h(date('d M Y', strtotime($row['created_at']))) ?>
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;">
                            <button class="pl-btn-icon pl-btn-view" onclick="viewMessage(<?= (int)$row['id'] ?>)" title="View">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="pl-btn-icon pl-btn-del" onclick="deleteMessage(<?= (int)$row['id'] ?>, this)" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="pl-empty">
            <i class="fas fa-envelope"></i>
            <p>No messages yet</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Message Detail Modal -->
<div class="modal fade" id="messageModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-envelope me-2" style="color:#f07b14"></i>Message Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="messageContent">
                <div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x" style="color:#f07b14"></i></div>
            </div>
        </div>
    </div>
</div>

<script>
const CSRF = <?= json_encode($csrf) ?>;

function esc(str) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(str ?? ''));
    return d.innerHTML;
}

function viewMessage(id) {
    const modal = new bootstrap.Modal(document.getElementById('messageModal'));
    document.getElementById('messageContent').innerHTML =
        '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x" style="color:#f07b14"></i></div>';
    modal.show();

    fetch(`get_message.php?id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) { alert(data.message); return; }
            const m = data.message;
            document.getElementById('messageContent').innerHTML = `
                <div class="detail-row"><span class="detail-label">Name</span><span class="detail-value"><strong>${esc(m.name)}</strong></span></div>
                <div class="detail-row"><span class="detail-label">Email</span><span class="detail-value"><a href="mailto:${esc(m.email)}" style="color:#f07b14">${esc(m.email)}</a></span></div>
                <div class="detail-row"><span class="detail-label">Phone</span><span class="detail-value"><a href="tel:${esc(m.phone)}" style="color:#f07b14">${esc(m.phone)}</a></span></div>
                <div class="detail-row"><span class="detail-label">Subject</span><span class="detail-value"><span class="pl-badge pl-badge-orange">${esc(m.subject)}</span></span></div>
                <div class="detail-row"><span class="detail-label">Date</span><span class="detail-value" style="color:#9ca3af">${esc(m.created_at)}</span></div>
                <div class="detail-row" style="flex-direction:column;gap:8px;">
                    <span class="detail-label">Message</span>
                    <div style="background:#f9fafb;border-radius:10px;padding:16px;color:#374151;line-height:1.7;font-size:14px;">${esc(m.message)}</div>
                </div>`;
        });
}

function deleteMessage(id, btn) {
    if (!confirm('Delete this message?')) return;
    fetch('delete_message.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id=${id}&csrf_token=${encodeURIComponent(CSRF)}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const row = btn.closest('tr');
            row.style.transition = 'opacity 0.3s';
            row.style.opacity = '0';
            setTimeout(() => row.remove(), 300);
        } else {
            alert(data.message);
        }
    });
}
</script>

<?php admin_foot(); ?>
