<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/admin_layout.php';
send_security_headers();
require_admin();

$conn        = get_db();
$enrollments = $conn->query("SELECT * FROM enroll ORDER BY id DESC");
log_activity($conn, 'VIEW_ENROLLMENTS', 'Viewed enrollments list');
$conn->close();

$csrf = csrf_token();

admin_head('Enrollments', 'enrollments');
?>

<div class="pl-page-header">
    <div>
        <h1>Enrollments</h1>
        <div class="pl-breadcrumb">Manage → <span>Course Enrollments</span></div>
    </div>
    <div style="display:flex;align-items:center;gap:10px;">
        <?php if($enrollments && $enrollments->num_rows > 0): ?>
        <div class="pl-badge pl-badge-green" style="padding:8px 16px;font-size:13px;">
            <i class="fas fa-user-graduate me-1"></i>
            <?= $enrollments->num_rows ?> Student<?= $enrollments->num_rows !== 1 ? 's' : '' ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="pl-card">
    <div class="pl-card-header">
        <h5><i class="fas fa-user-graduate me-2" style="color:#10b981"></i>All Enrollments</h5>
    </div>
    <div class="pl-card-body">
        <?php if ($enrollments && $enrollments->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="pl-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Course</th>
                        <th>Mode</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $enrollments->fetch_assoc()): ?>
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#10b981,#059669);display:flex;align-items:center;justify-content:center;color:#fff;font-size:12px;font-weight:700;flex-shrink:0;">
                                <?= strtoupper(substr(h($row['full_name']), 0, 1)) ?>
                            </div>
                            <strong><?= h($row['full_name']) ?></strong>
                        </div>
                    </td>
                    <td style="color:#6b7280;"><?= h($row['email']) ?></td>
                    <td style="color:#6b7280;"><?= h($row['phone']) ?></td>
                    <td>
                        <span class="pl-badge pl-badge-green">
                            <?= h(mb_substr($row['course_interest'], 0, 25)) ?>
                        </span>
                    </td>
                    <td>
                        <?php if($row['study_mode']): ?>
                        <span class="pl-badge <?= $row['study_mode'] === 'Online' ? 'pl-badge-blue' : 'pl-badge-purple' ?>">
                            <i class="fas <?= $row['study_mode'] === 'Online' ? 'fa-laptop' : 'fa-building' ?> me-1"></i>
                            <?= h($row['study_mode']) ?>
                        </span>
                        <?php else: ?>
                        <span style="color:#d1d5db;">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="color:#9ca3af;font-size:12px;white-space:nowrap;">
                        <i class="fas fa-calendar-alt me-1"></i>
                        <?= h(date('d M Y', strtotime($row['created_at']))) ?>
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;">
                            <button class="pl-btn-icon pl-btn-view" onclick="viewEnrollment(<?= (int)$row['id'] ?>)" title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="pl-btn-icon pl-btn-del" onclick="deleteEnrollment(<?= (int)$row['id'] ?>, this)" title="Delete">
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
            <i class="fas fa-user-graduate"></i>
            <p>No enrollments yet</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Enrollment Detail Modal -->
<div class="modal fade" id="enrollmentModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-graduate me-2" style="color:#10b981"></i>Enrollment Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="enrollmentContent">
                <div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x" style="color:#10b981"></i></div>
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

function viewEnrollment(id) {
    const modal = new bootstrap.Modal(document.getElementById('enrollmentModal'));
    document.getElementById('enrollmentContent').innerHTML =
        '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x" style="color:#10b981"></i></div>';
    modal.show();

    fetch(`get_enrollment.php?id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) { alert(data.message); return; }
            const e = data.enrollment;

            const row = (label, val, badge = '') => val
                ? `<div class="detail-row">
                    <span class="detail-label">${label}</span>
                    <span class="detail-value">${badge ? `<span class="pl-badge ${badge}">${esc(val)}</span>` : esc(val)}</span>
                   </div>`
                : '';

            document.getElementById('enrollmentContent').innerHTML = `
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:0;">
                    <div>
                        ${row('Full Name', e.full_name)}
                        ${row('Father Name', e.father_name)}
                        ${row('Email', e.email)}
                        ${row('Phone', e.phone)}
                        ${row('Gender', e.gender)}
                    </div>
                    <div>
                        ${row('Course', e.course_interest, 'pl-badge-green')}
                        ${row('Study Mode', e.study_mode, e.study_mode === 'Online' ? 'pl-badge-blue' : 'pl-badge-purple')}
                        <div class="detail-row"><span class="detail-label">Enrolled On</span><span class="detail-value" style="color:#9ca3af">${esc(e.created_at)}</span></div>
                    </div>
                </div>
                ${e.previous_experience ? `<div class="detail-row" style="flex-direction:column;gap:8px;margin-top:4px;"><span class="detail-label">Previous Experience</span><div style="background:#f9fafb;border-radius:10px;padding:14px;color:#374151;font-size:13.5px;line-height:1.6;">${esc(e.previous_experience)}</div></div>` : ''}
                ${e.reason_for_joining  ? `<div class="detail-row" style="flex-direction:column;gap:8px;"><span class="detail-label">Reason for Joining</span><div style="background:#f9fafb;border-radius:10px;padding:14px;color:#374151;font-size:13.5px;line-height:1.6;">${esc(e.reason_for_joining)}</div></div>` : ''}
                ${e.qualification_file  ? `<div class="detail-row"><span class="detail-label">Qualification</span><span class="detail-value"><a href="../uploads/${esc(e.qualification_file)}" target="_blank" rel="noopener" style="color:#f07b14;font-weight:600;"><i class="fas fa-file me-1"></i>View File</a></span></div>` : ''}
                ${e.passport_photo      ? `<div class="detail-row"><span class="detail-label">Passport Photo</span><span class="detail-value"><a href="../uploads/${esc(e.passport_photo)}" target="_blank" rel="noopener" style="color:#f07b14;font-weight:600;"><i class="fas fa-image me-1"></i>View Photo</a></span></div>` : ''}
                ${e.cnic_file           ? `<div class="detail-row"><span class="detail-label">CNIC</span><span class="detail-value"><a href="../uploads/${esc(e.cnic_file)}" target="_blank" rel="noopener" style="color:#f07b14;font-weight:600;"><i class="fas fa-id-card me-1"></i>View CNIC</a></span></div>` : ''}
            `;
        });
}

function deleteEnrollment(id, btn) {
    if (!confirm('Delete this enrollment?')) return;
    fetch('delete_enrollment.php', {
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
