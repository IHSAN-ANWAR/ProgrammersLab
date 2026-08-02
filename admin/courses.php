<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/admin_layout.php';
send_security_headers();
require_admin();

$conn = get_db();

// Handle AJAX actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    switch ($_POST['action']) {
        case 'add':
            $name     = trim($_POST['name']     ?? '');
            $category = trim($_POST['category'] ?? '');
            $active   = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 0;
            $order    = (int)($_POST['sort_order']       ?? 0);
            $price    = (int)($_POST['price']            ?? 0);
            $orig     = (int)($_POST['original_price']   ?? 0);
            $install  = (int)($_POST['installment_price']?? 0);
            $duration = trim($_POST['duration']          ?? '');
            if (!$name || !$category) {
                echo json_encode(['success' => false, 'message' => 'Name and category required.']);
                exit;
            }
            $stmt = $conn->prepare("INSERT INTO courses (name, category, is_active, sort_order, price, original_price, installment_price, duration) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            if (!$stmt) {
                $stmt = $conn->prepare("INSERT INTO courses (name, category, is_active, sort_order) VALUES (?, ?, ?, ?)");
                $stmt->bind_param('ssii', $name, $category, $active, $order);
            } else {
                $stmt->bind_param('ssiiiis', $name, $category, $active, $order, $price, $orig, $install, $duration);
            }
            $ok = $stmt->execute();
            echo json_encode(['success' => $ok, 'id' => $conn->insert_id, 'message' => $ok ? 'Course added.' : $stmt->error]);
            exit;

        case 'edit':
            $id       = (int)($_POST['id']               ?? 0);
            $name     = trim($_POST['name']              ?? '');
            $category = trim($_POST['category']          ?? '');
            $active   = (int)($_POST['is_active']        ?? 0);
            $order    = (int)($_POST['sort_order']       ?? 0);
            $price    = (int)($_POST['price']            ?? 0);
            $orig     = (int)($_POST['original_price']   ?? 0);
            $install  = (int)($_POST['installment_price']?? 0);
            $duration = trim($_POST['duration']          ?? '');
            if (!$id || !$name || !$category) {
                echo json_encode(['success' => false, 'message' => 'Invalid input.']);
                exit;
            }
            $stmt = $conn->prepare("UPDATE courses SET name=?, category=?, is_active=?, sort_order=?, price=?, original_price=?, installment_price=?, duration=? WHERE id=?");
            if (!$stmt) {
                $stmt = $conn->prepare("UPDATE courses SET name=?, category=?, is_active=?, sort_order=? WHERE id=?");
                $stmt->bind_param('ssiii', $name, $category, $active, $order, $id);
            } else {
                $stmt->bind_param('ssiiiiisi', $name, $category, $active, $order, $price, $orig, $install, $duration, $id);
            }
            $ok = $stmt->execute();
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Course updated.' : $stmt->error]);
            exit;

        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'Invalid ID.']); exit; }
            $stmt = $conn->prepare("DELETE FROM courses WHERE id = ?");
            $stmt->bind_param('i', $id);
            $ok = $stmt->execute();
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Deleted.' : $stmt->error]);
            exit;

        case 'toggle':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'Invalid ID.']); exit; }
            $stmt = $conn->prepare("UPDATE courses SET is_active = 1 - is_active WHERE id = ?");
            $stmt->bind_param('i', $id);
            $ok = $stmt->execute();
            $row = $conn->query("SELECT is_active FROM courses WHERE id = " . (int)$id)->fetch_assoc();
            echo json_encode(['success' => $ok, 'is_active' => (int)$row['is_active']]);
            exit;
    }
}

// Auto-add missing columns on live server
$conn->query("ALTER TABLE courses ADD COLUMN IF NOT EXISTS price             INT(11) NOT NULL DEFAULT 0 AFTER is_active");
$conn->query("ALTER TABLE courses ADD COLUMN IF NOT EXISTS original_price    INT(11) NOT NULL DEFAULT 0 AFTER price");
$conn->query("ALTER TABLE courses ADD COLUMN IF NOT EXISTS installment_price INT(11) NOT NULL DEFAULT 0 AFTER original_price");
$conn->query("ALTER TABLE courses ADD COLUMN IF NOT EXISTS duration          VARCHAR(50) NOT NULL DEFAULT '' AFTER installment_price");
$conn->query("ALTER TABLE courses ADD COLUMN IF NOT EXISTS sort_order        INT(11) NOT NULL DEFAULT 0 AFTER duration");

// Ensure MERN Stack exists
$conn->query("INSERT IGNORE INTO courses (name, category, is_active, price, original_price, installment_price, duration, sort_order)
              SELECT 'MERN Stack','Web Development',1,0,0,0,'6 Months',10
              FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM courses WHERE name='MERN Stack')");

// Get all courses
$result = $conn->query("SELECT id, name, category, sort_order, is_active,
    IFNULL(price, 0) AS price,
    IFNULL(original_price, 0) AS original_price,
    IFNULL(installment_price, 0) AS installment_price,
    IFNULL(duration, '') AS duration
    FROM courses ORDER BY category, sort_order, name");

// Group courses by category
$grouped    = [];
$total      = 0;
while ($c = $result->fetch_assoc()) {
    $grouped[$c['category']][] = $c;
    $total++;
}

// Get categories for dropdown
$cats_res   = $conn->query("SELECT DISTINCT category FROM courses ORDER BY category");
$categories = [];
while ($c = $cats_res->fetch_assoc()) $categories[] = $c['category'];

log_activity($conn, 'VIEW_COURSES', 'Viewed courses management');
$conn->close();

admin_head('Courses', 'courses');
?>

<div class="pl-page-header">
    <div>
        <h1>Courses Management</h1>
        <div class="pl-breadcrumb">Manage enroll form course dropdown</div>
    </div>
    <button class="btn btn-primary" style="background:#f07b14;border:none;border-radius:10px;padding:10px 22px;font-weight:700;" onclick="openAddModal()">
        <i class="fas fa-plus me-2"></i>Add Course
    </button>
</div>

<!-- Add/Edit Modal -->
<div id="courseModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;padding:32px;width:100%;max-width:480px;margin:20px;">
        <h3 id="modalTitle" style="margin:0 0 20px;font-weight:800;color:#0d1b2a;">Add Course</h3>
        <input type="hidden" id="modalId">
        <div class="mb-3">
            <label style="font-size:13px;font-weight:600;color:#374151;display:block;margin-bottom:6px;">Course Name *</label>
            <input type="text" id="modalName" class="form-control" placeholder="e.g. React.js Course">
        </div>
        <div class="mb-3">
            <label style="font-size:13px;font-weight:600;color:#374151;display:block;margin-bottom:6px;">Category *</label>
            <select id="modalCategory" class="form-control">
                <?php foreach ($categories as $cat): ?>
                <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                <?php endforeach; ?>
                <option value="__custom__">+ New Category</option>
            </select>
            <input type="text" id="modalCategoryCustom" class="form-control mt-2" placeholder="Type new category name" style="display:none;">
        </div>
        <div class="mb-3 d-flex gap-3 align-items-center">
            <div>
                <label style="font-size:13px;font-weight:600;color:#374151;display:block;margin-bottom:6px;">Sort Order</label>
                <input type="number" id="modalOrder" class="form-control" value="0" style="width:100px;">
            </div>
            <div style="margin-top:20px;">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px;font-weight:600;">
                    <input type="checkbox" id="modalActive" checked style="width:18px;height:18px;accent-color:#f07b14;"> Active
                </label>
            </div>
        </div>

        <!-- Price Fields -->
        <div style="background:#f9fafb;border-radius:12px;padding:16px;margin-bottom:16px;">
            <p style="font-size:12px;font-weight:700;text-transform:uppercase;color:#9ca3af;margin:0 0 12px;">
                <i class="fas fa-tag" style="color:#f07b14;margin-right:6px;"></i> Pricing (PKR)
            </p>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
                <div>
                    <label style="font-size:13px;font-weight:600;color:#374151;display:block;margin-bottom:6px;">One-Time Fee</label>
                    <input type="number" id="modalPrice" class="form-control" placeholder="e.g. 22000" min="0">
                </div>
                <div>
                    <label style="font-size:13px;font-weight:600;color:#374151;display:block;margin-bottom:6px;">Installment Fee</label>
                    <input type="number" id="modalInstallment" class="form-control" placeholder="e.g. 25000" min="0">
                </div>
                <div>
                    <label style="font-size:13px;font-weight:600;color:#374151;display:block;margin-bottom:6px;">Duration</label>
                    <input type="text" id="modalDuration" class="form-control" placeholder="e.g. 3 Months">
                </div>
            </div>
            <input type="hidden" id="modalOrigPrice" value="0">
        </div>

        <div style="display:flex;gap:10px;">
            <button onclick="saveCourse()" style="flex:1;padding:12px;background:#f07b14;color:#fff;border:none;border-radius:10px;font-weight:700;cursor:pointer;">Save</button>
            <button onclick="closeModal()" style="flex:1;padding:12px;background:#f0f0f0;color:#374151;border:none;border-radius:10px;font-weight:700;cursor:pointer;">Cancel</button>
        </div>
        <div id="modalMsg" style="margin-top:12px;font-size:13px;display:none;"></div>
    </div>
</div>

<!-- Courses Table -->
<div class="pl-card">
    <div class="pl-card-header">
        <h5><i class="fas fa-graduation-cap me-2" style="color:#f07b14"></i>All Courses</h5>
        <span style="font-size:13px;color:#9ca3af;" id="totalCount"><?= $total ?> courses</span>
    </div>
    <div class="pl-card-body p-0">
        <table class="pl-table" id="coursesTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Course Name</th>
                    <th>Duration</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($grouped as $catName => $courses): ?>
            <!-- Category Header Row -->
            <tr>
                <td colspan="6" style="background:#f0f4ff;padding:10px 16px;border-bottom:2px solid #e0e7ff;">
                    <span style="font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:1px;color:#4f46e5;">
                        <i class="fas fa-folder me-2" style="color:#818cf8;"></i><?= htmlspecialchars($catName) ?>
                    </span>
                    <span style="font-size:11px;color:#9ca3af;margin-left:8px;"><?= count($courses) ?> courses</span>
                </td>
            </tr>
            <?php foreach ($courses as $c): ?>
            <tr id="row-<?= $c['id'] ?>">
                <td style="color:#9ca3af;font-size:12px;"><?= $c['id'] ?></td>
                <td><strong><?= htmlspecialchars($c['name']) ?></strong></td>
                <td style="color:#6b7280;font-size:13px;"><?= $c['duration'] ? htmlspecialchars($c['duration']) : '<span style="color:#d1d5db;">—</span>' ?></td>
                <td>
                    <?php if ($c['price'] > 0): ?>
                    <strong style="color:#f07b14;">Rs. <?= number_format($c['price']) ?></strong>
                    <?php if (($c['installment_price'] ?? 0) > 0): ?>
                    <span style="color:#9ca3af;font-size:11px;display:block;">Install: Rs. <?= number_format($c['installment_price']) ?></span>
                    <?php endif; ?>
                    <?php else: ?><span style="color:#d1d5db;">—</span><?php endif; ?>
                </td>
                <td>
                    <button onclick="toggleStatus(<?= $c['id'] ?>, this)"
                        style="padding:4px 14px;border:none;border-radius:20px;font-size:12px;font-weight:700;cursor:pointer;
                               background:<?= $c['is_active'] ? 'rgba(16,185,129,0.1)' : 'rgba(239,68,68,0.1)' ?>;
                               color:<?= $c['is_active'] ? '#10b981' : '#ef4444' ?>;"
                        data-active="<?= $c['is_active'] ?>">
                        <?= $c['is_active'] ? 'Active' : 'Inactive' ?>
                    </button>
                </td>
                <td>
                    <button onclick='editCourse(<?= json_encode(['id' => $c['id'], 'name' => $c['name'], 'category' => $c['category'], 'sort_order' => $c['sort_order'], 'is_active' => $c['is_active'], 'price' => (int)($c['price'] ?? 0), 'installment_price' => (int)($c['installment_price'] ?? 0), 'duration' => $c['duration'] ?? '']) ?>)'
                        style="padding:5px 12px;background:rgba(0,148,217,0.1);color:#0094d9;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;margin-right:6px;">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button onclick="deleteCourse(<?= $c['id'] ?>, '<?= htmlspecialchars(addslashes($c['name'])) ?>')"
                        style="padding:5px 12px;background:rgba(239,68,68,0.1);color:#ef4444;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
const categories = <?= json_encode($categories) ?>;

function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Course';
    document.getElementById('modalId').value = '';
    document.getElementById('modalName').value = '';
    const catSel = document.getElementById('modalCategory');
    if (catSel.options.length > 0) catSel.value = catSel.options[0].value;
    document.getElementById('modalCategoryCustom').style.display = 'none';
    document.getElementById('modalOrder').value = 0;
    document.getElementById('modalActive').checked = true;
    document.getElementById('modalPrice').value    = '';
    document.getElementById('modalInstallment').value = '';
    document.getElementById('modalDuration').value  = '';
    document.getElementById('modalMsg').style.display = 'none';
    document.getElementById('courseModal').style.display = 'flex';
}

function editCourse(data) {
    document.getElementById('modalTitle').textContent = 'Edit Course';
    document.getElementById('modalId').value = data.id;
    document.getElementById('modalName').value = data.name;

    const catSel = document.getElementById('modalCategory');
    const exists = [...catSel.options].some(o => o.value === data.category);
    if (exists) {
        catSel.value = data.category;
        document.getElementById('modalCategoryCustom').style.display = 'none';
    } else {
        catSel.value = '__custom__';
        document.getElementById('modalCategoryCustom').style.display = 'block';
        document.getElementById('modalCategoryCustom').value = data.category;
    }

    document.getElementById('modalOrder').value = data.sort_order;
    document.getElementById('modalActive').checked = data.is_active == 1;
    document.getElementById('modalPrice').value        = data.price || '';
    document.getElementById('modalInstallment').value  = data.installment_price || '';
    document.getElementById('modalDuration').value     = data.duration || '';
    document.getElementById('modalMsg').style.display = 'none';
    document.getElementById('courseModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('courseModal').style.display = 'none';
}

document.getElementById('modalCategory').addEventListener('change', function () {
    document.getElementById('modalCategoryCustom').style.display = this.value === '__custom__' ? 'block' : 'none';
});

function saveCourse() {
    const id       = document.getElementById('modalId').value;
    const name     = document.getElementById('modalName').value.trim();
    let   category = document.getElementById('modalCategory').value;
    if (category === '__custom__') category = document.getElementById('modalCategoryCustom').value.trim();
    const order  = document.getElementById('modalOrder').value;
    const active = document.getElementById('modalActive').checked ? 1 : 0;
    const msg    = document.getElementById('modalMsg');

    if (!name || !category) { showMsg(msg, 'Name and category required.', 'red'); return; }

    const fd = new FormData();
    fd.append('action', id ? 'edit' : 'add');
    if (id) fd.append('id', id);
    fd.append('name', name);
    fd.append('category', category);
    fd.append('sort_order', order);
    fd.append('is_active', active);
    fd.append('price',             document.getElementById('modalPrice').value        || 0);
    fd.append('original_price',    document.getElementById('modalOrigPrice').value     || 0);
    fd.append('installment_price', document.getElementById('modalInstallment').value   || 0);
    fd.append('duration',          document.getElementById('modalDuration').value      || '');

    fetch('courses.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                closeModal();
                location.reload();
            } else {
                showMsg(msg, data.message, 'red');
            }
        })
        .catch(() => showMsg(msg, 'Network error. Please try again.', 'red'));
}

function deleteCourse(id, name) {
    if (!confirm('Delete "' + name + '"?')) return;
    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('id', id);
    fetch('courses.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('row-' + id).remove();
                const counter = document.getElementById('totalCount');
                const match   = counter.textContent.match(/\d+/);
                if (match) counter.textContent = (parseInt(match[0]) - 1) + ' courses';
            } else {
                alert('Error: ' + data.message);
            }
        });
}

function toggleStatus(id, btn) {
    const fd = new FormData();
    fd.append('action', 'toggle');
    fd.append('id', id);
    fetch('courses.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const active = data.is_active;
                btn.textContent       = active ? 'Active' : 'Inactive';
                btn.style.background  = active ? 'rgba(16,185,129,0.1)' : 'rgba(239,68,68,0.1)';
                btn.style.color       = active ? '#10b981' : '#ef4444';
                btn.dataset.active    = active;
            }
        });
}

function showMsg(el, text, color) {
    el.textContent    = text;
    el.style.color    = color;
    el.style.display  = 'block';
}

// Close modal on backdrop click
document.getElementById('courseModal').addEventListener('click', function (e) {
    if (e.target === this) closeModal();
});
</script>

<?php admin_foot(); ?>
