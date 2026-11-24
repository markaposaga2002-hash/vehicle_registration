<?php


require __DIR__ . '/db_connect.php';

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

function e(?string $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect(): never {
    header('Location: index.php');
    exit;
}

function flash(string $message, string $type = 'success'): void {
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function icon(string $name): string {
    $icons = [
        'dashboard' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/></svg>',
        'plus' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>',
        'car' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 17h14l-1.2-5.2a2 2 0 0 0-1.9-1.5H8.1a2 2 0 0 0-1.9 1.5L5 17Z"/><path d="m7.5 10.3 1.3-3h6.4l1.3 3M7 17v2M17 17v2"/><circle cx="8" cy="17" r="1"/><circle cx="16" cy="17" r="1"/></svg>',
        'check' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>',
        'clock' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
        'alert' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10.3 4.3 2.8 17.2A2 2 0 0 0 4.5 20h15a2 2 0 0 0 1.7-2.8L13.7 4.3a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4M12 16.5v.1"/></svg>',
        'calendar' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M8 3v4M16 3v4M3 10h18"/></svg>',
        'search' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6"/><path d="m16 16 5 5"/></svg>',
        'edit' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13.5 6.5 17.5 10.5M4 20l4.5-1 9.8-9.8a2.8 2.8 0 0 0-4-4l-9.8 9.8L4 20Z"/></svg>',
        'trash' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M9 7V4h6v3M7 7l1 13h8l1-13M10 11v5M14 11v5"/></svg>',
    ];
    return $icons[$name] ?? '';
}

function validDate(string $date): bool {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        http_response_code(403);
        exit('Invalid request token. Refresh the page and try again.');
    }

    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = filter_input(INPUT_POST, 'vehicle_id', FILTER_VALIDATE_INT) ?: 0;
        $plate = strtoupper(trim($_POST['plate_number'] ?? ''));
        $owner = trim($_POST['owner_name'] ?? '');
        $type = trim($_POST['vehicle_type'] ?? '');
        $make = trim($_POST['make_model'] ?? '');
        $year = filter_input(INPUT_POST, 'year', FILTER_VALIDATE_INT);
        $color = trim($_POST['color'] ?? '');
        $reg = trim($_POST['registration_date'] ?? '');
        $expiry = trim($_POST['expiry_date'] ?? '');
        $status = trim($_POST['status'] ?? 'Pending');

        $errors = [];
        if ($plate === '' || $owner === '' || $type === '' || $make === '' || $color === '') {
            $errors[] = 'Please complete all required fields.';
        }
        if (!$year || $year < 1886 || $year > ((int)date('Y') + 1)) {
            $errors[] = 'Enter a valid vehicle year.';
        }
        if (!validDate($reg) || !validDate($expiry) || $expiry < $reg) {
            $errors[] = 'Enter valid dates. Expiry date must not be earlier than registration date.';
        }
        if (!in_array($status, ['Active', 'Pending', 'Expired'], true)) {
            $errors[] = 'Invalid registration status.';
        }

        if ($errors) {
            flash(implode(' ', $errors), 'danger');
            $_SESSION['form_data'] = $_POST;
            redirect();
        }

        try {
            if ($id > 0) {
                $stmt = $conn->prepare('UPDATE vehicles SET plate_number=?, owner_name=?, vehicle_type=?, make_model=?, year=?, color=?, registration_date=?, expiry_date=?, status=? WHERE vehicle_id=?');
                $stmt->bind_param('ssssissssi', $plate, $owner, $type, $make, $year, $color, $reg, $expiry, $status, $id);
                $stmt->execute();
                flash('Vehicle registration updated successfully.');
            } else {
                $stmt = $conn->prepare('INSERT INTO vehicles (plate_number, owner_name, vehicle_type, make_model, year, color, registration_date, expiry_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->bind_param('ssssissss', $plate, $owner, $type, $make, $year, $color, $reg, $expiry, $status);
                $stmt->execute();
                flash('Vehicle registered successfully.');
            }
        } catch (mysqli_sql_exception $ex) {
            if ($ex->getCode() === 1062) {
                flash('That plate number is already registered.', 'danger');
            } else {
                flash('Unable to save the record. ' . $ex->getMessage(), 'danger');
            }
        }
        redirect();
    }

    if ($action === 'delete') {
        $id = filter_input(INPUT_POST, 'vehicle_id', FILTER_VALIDATE_INT);
        if ($id) {
            $stmt = $conn->prepare('DELETE FROM vehicles WHERE vehicle_id=?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            flash($stmt->affected_rows ? 'Vehicle record deleted.' : 'Record not found.');
        }
        redirect();
    }
}

$editing = null;
if (isset($_GET['edit'])) {
    $id = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $conn->prepare('SELECT * FROM vehicles WHERE vehicle_id=?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $editing = $stmt->get_result()->fetch_assoc() ?: null;
    }
}

$search = trim($_GET['search'] ?? '');
if ($search !== '') {
    $like = '%' . $search . '%';
    $stmt = $conn->prepare('SELECT * FROM vehicles WHERE plate_number LIKE ? OR owner_name LIKE ? OR make_model LIKE ? ORDER BY vehicle_id DESC');
    $stmt->bind_param('sss', $like, $like, $like);
    $stmt->execute();
    $vehicles = $stmt->get_result();
} else {
    $vehicles = $conn->query('SELECT * FROM vehicles ORDER BY vehicle_id DESC');
}

$stats = $conn->query("SELECT COUNT(*) total, SUM(status='Active' AND expiry_date >= CURDATE()) active_count, SUM(status='Pending') pending_count, SUM(status='Expired' OR expiry_date < CURDATE()) expired_count FROM vehicles")->fetch_assoc();
$notice = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$formData = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);
if ($formData) {
    $editing = array_merge($editing ?? [], $formData);
}
$today = date('Y-m-d');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vehicle Registration System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css?v=<?= (int)filemtime(__DIR__ . '/assets/style.css') ?>">
</head>
<body>
<div class="app-shell">
    <nav class="navbar navbar-expand-lg app-navbar">
        <div class="container-fluid page-container">
            <a class="navbar-brand brand text-decoration-none" href="index.php">
                <span class="brand-icon"><?= icon('car') ?></span>
                <span><strong>VehicleReg</strong><small>Management System</small></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavigation" aria-controls="mainNavigation" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="mainNavigation">
                <div class="navbar-nav align-items-lg-center gap-lg-2 ms-auto">
                    <a class="nav-link top-nav-link active" href="index.php"><span class="nav-symbol"><?= icon('dashboard') ?></span> Dashboard</a>
                    <a class="nav-link top-nav-link" href="#registration-form"><span class="nav-symbol"><?= icon('plus') ?></span> Register Vehicle</a>

                </div>
            </div>
        </div>
    </nav>

    <main class="main-content">
        <header class="topbar">
            <div>
                <div class="eyebrow">OVERVIEW</div>
                <h1>Vehicle Dashboard</h1>
                <p class="text-secondary mb-0">Manage vehicle registrations in one place.</p>
            </div>
            <div class="date-chip"><?= e(date('M d, Y')) ?></div>
        </header>

        <?php if ($notice): ?>
            <div class="alert alert-<?= e($notice['type']) ?> alert-dismissible fade show shadow-sm" role="alert">
                <?= e($notice['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <section class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-xl-3">
                <article class="card stat-card h-100"><div class="card-body">
                    <div class="stat-top"><span>Total Vehicles</span><span class="stat-icon icon-blue"><?= icon('car') ?></span></div>
                    <div class="stat-number"><?= (int)($stats['total'] ?? 0) ?></div>
                    <small class="text-secondary">All recorded vehicles</small>
                </div></article>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <article class="card stat-card h-100"><div class="card-body">
                    <div class="stat-top"><span>Active</span><span class="stat-icon icon-green"><?= icon('check') ?></span></div>
                    <div class="stat-number"><?= (int)($stats['active_count'] ?? 0) ?></div>
                    <small class="text-secondary">Currently valid</small>
                </div></article>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <article class="card stat-card h-100"><div class="card-body">
                    <div class="stat-top"><span>Pending</span><span class="stat-icon icon-orange"><?= icon('clock') ?></span></div>
                    <div class="stat-number"><?= (int)($stats['pending_count'] ?? 0) ?></div>
                    <small class="text-secondary">Awaiting approval</small>
                </div></article>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <article class="card stat-card h-100"><div class="card-body">
                    <div class="stat-top"><span>Expired</span><span class="stat-icon icon-red"><?= icon('alert') ?></span></div>
                    <div class="stat-number"><?= (int)($stats['expired_count'] ?? 0) ?></div>
                    <small class="text-secondary">Expired or marked expired</small>
                </div></article>
            </div>
        </section>

        <section class="card content-card mb-4" id="registration-form">
            <div class="card-header content-card-header">
                <div><div class="eyebrow">VEHICLE INFORMATION</div><h2><?= $editing ? 'Edit Vehicle Registration' : 'Register a Vehicle' ?></h2></div>
                <?php if ($editing): ?><a class="btn btn-outline-secondary btn-sm" href="index.php"><?= icon('plus') ?> New record</a><?php endif; ?>
            </div>
            <div class="card-body">
                <form method="post" class="row g-3">
                    <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="vehicle_id" value="<?= e((string)($editing['vehicle_id'] ?? '')) ?>">
                    <div class="col-12 col-md-6 col-lg-4">
                        <label class="form-label">Plate Number <span class="required">*</span></label>
                        <input class="form-control" name="plate_number" required maxlength="20" placeholder="e.g. ABC 1234" value="<?= e($editing['plate_number'] ?? '') ?>">
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <label class="form-label">Owner Name <span class="required">*</span></label>
                        <input class="form-control" name="owner_name" required maxlength="120" placeholder="Full name" value="<?= e($editing['owner_name'] ?? '') ?>">
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <label class="form-label">Vehicle Type <span class="required">*</span></label>
                        <select class="form-select" name="vehicle_type" required><option value="">Select type</option><?php foreach (['Car','Motorcycle','Truck','Van','Bus','Other'] as $t): ?><option value="<?= e($t) ?>" <?= (($editing['vehicle_type'] ?? '') === $t) ? 'selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?></select>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <label class="form-label">Make / Model <span class="required">*</span></label>
                        <input class="form-control" name="make_model" required maxlength="100" placeholder="e.g. Toyota Vios" value="<?= e($editing['make_model'] ?? '') ?>">
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <label class="form-label">Year <span class="required">*</span></label>
                        <input class="form-control" type="number" name="year" required min="1886" max="<?= (int)date('Y') + 1 ?>" placeholder="2024" value="<?= e((string)($editing['year'] ?? '')) ?>">
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <label class="form-label">Color <span class="required">*</span></label>
                        <input class="form-control" name="color" required maxlength="50" placeholder="e.g. White" value="<?= e($editing['color'] ?? '') ?>">
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <label class="form-label">Registration Date <span class="required">*</span></label>
                        <input class="form-control" type="date" name="registration_date" required value="<?= e($editing['registration_date'] ?? $today) ?>">
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <label class="form-label">Expiry Date <span class="required">*</span></label>
                        <input class="form-control" type="date" name="expiry_date" required value="<?= e($editing['expiry_date'] ?? '') ?>">
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status"><?php foreach (['Active','Pending','Expired'] as $s): ?><option value="<?= e($s) ?>" <?= (($editing['status'] ?? 'Pending') === $s) ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select>
                    </div>
                    <div class="col-12 d-flex flex-wrap gap-2 pt-2">
                        <button class="btn btn-primary px-4" type="submit"><span class="btn-icon"><?= icon($editing ? 'check' : 'plus') ?></span><?= $editing ? 'Save Changes' : 'Save Registration' ?></button>
                        <?php if ($editing): ?><a class="btn btn-light border" href="index.php">Cancel</a><?php endif; ?>
                    </div>
                </form>
            </div>
        </section>

        <section class="card content-card">
            <div class="card-header content-card-header table-card-header">
                <div><div class="eyebrow">RECORDS</div><h2>Registered Vehicles</h2></div>
                <form class="d-flex flex-wrap gap-2 search-form" method="get">
                    <input class="form-control" name="search" value="<?= e($search) ?>" placeholder="Search plate, owner, make..." aria-label="Search vehicles">
                    <button class="btn btn-dark" type="submit"><span class="btn-icon"><?= icon('search') ?></span>Search</button>
                    <?php if ($search !== ''): ?><a class="btn btn-outline-secondary" href="index.php">Clear</a><?php endif; ?>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>PLATE NUMBER</th><th>OWNER</th><th>VEHICLE</th><th>YEAR</th><th>EXPIRY DATE</th><th>STATUS</th><th class="text-end">ACTIONS</th></tr></thead>
                    <tbody>
                    <?php if ($vehicles->num_rows === 0): ?><tr><td colspan="7" class="text-center py-5 text-secondary">No vehicle records found. Add your first registration above.</td></tr><?php endif; ?>
                    <?php while ($v = $vehicles->fetch_assoc()): $displayStatus = ($v['expiry_date'] < $today) ? 'Expired' : $v['status']; ?>
                        <tr>
                            <td class="fw-semibold"><?= e($v['plate_number']) ?></td>
                            <td><?= e($v['owner_name']) ?></td>
                            <td><?= e($v['make_model']) ?><small class="d-block text-secondary"><?= e($v['vehicle_type']) ?> · <?= e($v['color']) ?></small></td>
                            <td><?= (int)$v['year'] ?></td>
                            <td><?= e(date('M d, Y', strtotime($v['expiry_date']))) ?></td>
                            <td><span class="badge status-badge status-<?= strtolower(e($displayStatus)) ?>"><?= e($displayStatus) ?></span></td>
                            <td><div class="d-flex justify-content-end gap-2">
                                <a class="btn btn-sm btn-outline-primary action-btn" href="?edit=<?= (int)$v['vehicle_id'] ?>#registration-form" title="Edit record"><?= icon('edit') ?><span>Edit</span></a>
                                <form method="post" onsubmit="return confirm('Delete this vehicle registration?');">
                                    <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="vehicle_id" value="<?= (int)$v['vehicle_id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger action-btn" type="submit" title="Delete record"><?= icon('trash') ?><span>Delete</span></button>
                                </form>
                            </div></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white text-secondary small">Showing matching vehicle registration records</div>
        </section>

    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
