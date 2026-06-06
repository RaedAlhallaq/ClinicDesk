<?php
Auth::requireRole('admin');
require_once __DIR__ . '/../../views/partials/header.php';
require_once __DIR__ . '/../../views/partials/navbar.php';
require_once __DIR__ . '/../../views/partials/sidebar.php';
?>

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6"><h1>Appointment Reports</h1></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item">
                        <a href="<?= BASE_URL ?>/index.php?page=dashboard">
                            Dashboard
                        </a>
                    </li>
                    <li class="breadcrumb-item active">Reports</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">

        <?php require_once __DIR__ . '/../../views/partials/alerts.php'; ?>

        <!-- Filter Form -->
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-filter mr-2"></i>
                    Report Filters
                </h3>
            </div>

            <form method="GET"
                  action="<?= BASE_URL ?>/index.php">
                <input type="hidden" name="page"   value="reports">
                <input type="hidden" name="action" value="index">

                <div class="card-body">
                    <div class="row">

                        <!-- Date From -->
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-calendar mr-1"></i>
                                    Start Date *
                                </label>
                                <input type="date"
                                       name="date_from"
                                       class="form-control"
                                       value="<?= e($_GET['date_from'] ?? '') ?>"
                                       required>
                            </div>
                        </div>

                        <!-- Date To -->
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-calendar mr-1"></i>
                                    End Date *
                                </label>
                                <input type="date"
                                       name="date_to"
                                       class="form-control"
                                       value="<?= e($_GET['date_to'] ?? '') ?>"
                                       required>
                            </div>
                        </div>

                        <!-- Doctor Filter -->
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-user-md mr-1"></i>
                                    Doctor
                                </label>
                                <select name="doctor_id"
                                        class="form-control">
                                    <option value="">All Doctors</option>
                                    <?php foreach ($doctors as $doc): ?>
                                        <option value="<?= $doc['id'] ?>"
                                            <?= ($_GET['doctor_id'] ?? '') == $doc['id'] ? 'selected' : '' ?>>
                                            Dr. <?= e($doc['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Status Filter -->
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-tag mr-1"></i>
                                    Status
                                </label>
                                <select name="status" class="form-control">
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
                        </div>

                    </div>
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search mr-1"></i>
                        Generate Report
                    </button>
                    <a href="<?= BASE_URL ?>/index.php?page=reports"
                       class="btn btn-secondary ml-2">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Errors -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <p class="mb-0">
                        <i class="fas fa-exclamation-circle mr-1"></i>
                        <?= e($error) ?>
                    </p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Results -->
        <?php if ($hasFilters && empty($errors)): ?>

            <!-- Summary Cards -->
            <div class="row">
                <div class="col-md-3 col-6">
                    <div class="small-box bg-primary">
                        <div class="inner">
                            <h3><?= $totalCount ?></h3>
                            <p>Total Results</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3><?= $statusSummary['pending'] ?></h3>
                            <p>Pending</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-hourglass-half"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3><?= $statusSummary['completed'] ?></h3>
                            <p>Completed</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3><?= $statusSummary['cancelled'] ?></h3>
                            <p>Cancelled</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-times-circle"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Results Table -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-table mr-2"></i>
                        Report Results
                        <span class="badge badge-primary ml-2">
                            <?= $totalCount ?> records
                        </span>
                    </h3>
                    <div class="card-tools">
                        <!-- Export CSV Button -->
                        <?php if ($totalCount > 0): ?>
                            <a href="<?= BASE_URL ?>/index.php?page=reports&export=csv&<?= http_build_query(array_filter([
                                'date_from' => $_GET['date_from'] ?? '',
                                'date_to'   => $_GET['date_to']   ?? '',
                                'doctor_id' => $_GET['doctor_id'] ?? '',
                                'status'    => $_GET['status']    ?? '',
                            ])) ?>"
                               class="btn btn-sm btn-success">
                                <i class="fas fa-file-csv mr-1"></i>
                                Export CSV
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card-body p-0">
                    <?php if (empty($appointments)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-calendar-times fa-3x mb-3 d-block"></i>
                            No appointments found for the selected filters.
                        </div>
                    <?php else: ?>
                        <table class="table table-striped table-hover table-sm">
                            <thead class="thead-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Patient</th>
                                    <th>Doctor</th>
                                    <th>Specialization</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                    <th>Reason</th>
                                    <th>Fee</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($appointments as $appt): ?>
                                    <tr>
                                        <td><?= $appt['id'] ?></td>
                                        <td><?= e($appt['patient_name']) ?></td>
                                        <td>
                                            Dr. <?= e($appt['doctor_name']) ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-info">
                                                <?= e($appt['specialization_name']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?= formatDate($appt['appt_date']) ?>
                                        </td>
                                        <td>
                                            <?= formatTime($appt['appt_time']) ?>
                                        </td>
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
                                            $<?= number_format($appt['consultation_fee'], 2) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>

                            <!-- Summary Footer -->
                            <tfoot class="thead-dark">
                                <tr>
                                    <td colspan="6"
                                        class="text-right font-weight-bold">
                                        Summary:
                                    </td>
                                    <td colspan="3">
                                        <span class="badge badge-warning mr-1">
                                            Pending: <?= $statusSummary['pending'] ?>
                                        </span>
                                        <span class="badge badge-info mr-1">
                                            Confirmed: <?= $statusSummary['confirmed'] ?>
                                        </span>
                                        <span class="badge badge-success mr-1">
                                            Completed: <?= $statusSummary['completed'] ?>
                                        </span>
                                        <span class="badge badge-danger">
                                            Cancelled: <?= $statusSummary['cancelled'] ?>
                                        </span>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif (!$hasFilters): ?>

            <!-- Initial State -->
            <div class="callout callout-info">
                <h5>
                    <i class="fas fa-info-circle mr-2"></i>
                    How to use Reports
                </h5>
                <p class="mb-0">
                    Select a <strong>Start Date</strong> and
                    <strong>End Date</strong> to generate a report.
                    Optionally filter by doctor or status.
                    Use <strong>Export CSV</strong> to download
                    the results for Excel.
                </p>
            </div>

        <?php endif; ?>

    </div>
</section>

<?php require_once __DIR__ . '/../../views/partials/footer.php'; ?>
