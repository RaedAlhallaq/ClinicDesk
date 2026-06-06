<?php
Auth::requireAuth();
require_once __DIR__ . '/../../views/partials/header.php';
require_once __DIR__ . '/../../views/partials/navbar.php';
require_once __DIR__ . '/../../views/partials/sidebar.php';
?>

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6"><h1>Appointments</h1></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item">
                        <a href="<?= BASE_URL ?>/index.php?page=dashboard">
                            Dashboard
                        </a>
                    </li>
                    <li class="breadcrumb-item active">Appointments</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">

        <?php require_once __DIR__ . '/../../views/partials/alerts.php'; ?>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-calendar-check mr-2"></i>
                    <?php if (Auth::isPatient()): ?>
                        My Appointments
                    <?php elseif (Auth::isDoctor()): ?>
                        My Schedule
                    <?php else: ?>
                        All Appointments
                    <?php endif; ?>
                </h3>
                <div class="card-tools">
                    <?php if (Auth::isPatient()): ?>
                        <a href="<?= BASE_URL ?>/index.php?page=appointments&action=book"
                           class="btn btn-sm btn-success">
                            <i class="fas fa-plus mr-1"></i>
                            Book New
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Filters -->
            <div class="card-body border-bottom">
                <form method="GET" action="<?= BASE_URL ?>/index.php">
                    <input type="hidden" name="page"   value="appointments">
                    <input type="hidden" name="action" value="list">
                    <div class="row">

                        <!-- Status Filter -->
                        <div class="col-md-2">
                            <select name="status" class="form-control form-control-sm">
                                <option value="">All Status</option>
                                <?php
                                $statuses = ['pending','confirmed','completed','cancelled'];
                                foreach ($statuses as $s):
                                ?>
                                    <option value="<?= $s ?>"
                                        <?= ($_GET['status'] ?? '') === $s ? 'selected' : '' ?>>
                                        <?= ucfirst($s) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Date From -->
                        <div class="col-md-2">
                            <input type="date"
                                   name="date_from"
                                   class="form-control form-control-sm"
                                   value="<?= e($_GET['date_from'] ?? '') ?>"
                                   placeholder="From Date">
                        </div>

                        <!-- Date To -->
                        <div class="col-md-2">
                            <input type="date"
                                   name="date_to"
                                   class="form-control form-control-sm"
                                   value="<?= e($_GET['date_to'] ?? '') ?>"
                                   placeholder="To Date">
                        </div>

                        <!-- Doctor Filter (Admin only) -->
                        <?php if (Auth::isAdmin() && !empty($doctors)): ?>
                            <div class="col-md-2">
                                <select name="doctor_id"
                                        class="form-control form-control-sm">
                                    <option value="">All Doctors</option>
                                    <?php foreach ($doctors as $doc): ?>
                                        <option value="<?= $doc['id'] ?>"
                                            <?= ($_GET['doctor_id'] ?? '') == $doc['id'] ? 'selected' : '' ?>>
                                            <?= e($doc['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <!-- Search (Admin only) -->
                        <?php if (Auth::isAdmin()): ?>
                            <div class="col-md-2">
                                <input type="text"
                                       name="search"
                                       class="form-control form-control-sm"
                                       placeholder="Search patient..."
                                       value="<?= e($_GET['search'] ?? '') ?>">
                            </div>
                        <?php endif; ?>

                        <div class="col-md-2">
                            <button type="submit"
                                    class="btn btn-sm btn-primary">
                                <i class="fas fa-filter mr-1"></i>
                                Filter
                            </button>
                            <a href="<?= BASE_URL ?>/index.php?page=appointments"
                               class="btn btn-sm btn-secondary ml-1">
                                Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Table -->
            <div class="card-body p-0">
                <table class="table table-striped table-hover">
                    <thead class="thead-dark">
                        <tr>
                            <th>#</th>
                            <?php if (!Auth::isPatient()): ?>
                                <th>Patient</th>
                            <?php endif; ?>
                            <?php if (!Auth::isDoctor()): ?>
                                <th>Doctor</th>
                            <?php endif; ?>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Reason</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($appointments)): ?>
                            <tr>
                                <td colspan="8"
                                    class="text-center text-muted py-4">
                                    No appointments found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($appointments as $appt): ?>
                                <tr>
                                    <td><?= $appt['id'] ?></td>

                                    <?php if (!Auth::isPatient()): ?>
                                        <td><?= e($appt['patient_name']) ?></td>
                                    <?php endif; ?>

                                    <?php if (!Auth::isDoctor()): ?>
                                        <td>
                                            <?= e($appt['doctor_name']) ?>
                                            <br>
                                            <small class="text-muted">
                                                <?= e($appt['specialization_name']) ?>
                                            </small>
                                        </td>
                                    <?php endif; ?>

                                    <td><?= formatDate($appt['appt_date']) ?></td>
                                    <td><?= formatTime($appt['appt_time']) ?></td>

                                    <td>
                                        <?php
                                        $badgeClass = match($appt['status']) {
                                            'pending'   => 'badge-warning',
                                            'confirmed' => 'badge-info',
                                            'completed' => 'badge-success',
                                            'cancelled' => 'badge-danger',
                                            default     => 'badge-secondary'
                                        };
                                        ?>
                                        <span class="badge <?= $badgeClass ?>">
                                            <?= ucfirst($appt['status']) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= e(truncate($appt['reason'] ?? '—', 25)) ?>
                                    </td>

                                    <td>
                                        <!-- View Detail -->
                                        <a href="<?= BASE_URL ?>/index.php?page=appointments&action=detail&id=<?= $appt['id'] ?>"
                                           class="btn btn-xs btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        <!-- Cancel (Patient, pending only) -->
                                        <?php if (Auth::isPatient() && $appt['status'] === 'pending'): ?>
                                            <form method="POST"
                                                  action="<?= BASE_URL ?>/index.php?page=appointments&action=cancel"
                                                  style="display:inline;">
                                                <?= CSRF::tokenInput() ?>
                                                <input type="hidden"
                                                       name="id"
                                                       value="<?= $appt['id'] ?>">
                                                <button type="submit"
                                                        class="btn btn-xs btn-danger"
                                                        onclick="return confirm('Cancel this appointment?')">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if (isset($paginator) && $paginator->totalPages() > 1): ?>
                <div class="card-footer">
                    <ul class="pagination pagination-sm float-right mb-0">
                        <?php if ($paginator->hasPrev()): ?>
                            <li class="page-item">
                                <a class="page-link"
                                   href="<?= $paginator->pageUrl($paginator->prevPage(), 'appointments') ?>">
                                    &laquo;
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php foreach ($paginator->pages() as $num): ?>
                            <li class="page-item <?= $num === $paginator->currentPage() ? 'active' : '' ?>">
                                <a class="page-link"
                                   href="<?= $paginator->pageUrl($num, 'appointments') ?>">
                                    <?= $num ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                        <?php if ($paginator->hasNext()): ?>
                            <li class="page-item">
                                <a class="page-link"
                                   href="<?= $paginator->pageUrl($paginator->nextPage(), 'appointments') ?>">
                                    &raquo;
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            <?php endif; ?>

        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../../views/partials/footer.php'; ?>