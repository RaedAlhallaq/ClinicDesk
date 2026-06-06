<?php
// ============================================================
// controllers/ReportController.php
// Manages the generation and CSV export of appointment reports.
// Access is strictly limited to Admins.
// ============================================================

require_once __DIR__ . '/../models/BaseModel.php';
require_once __DIR__ . '/../models/AppointmentModel.php';
require_once __DIR__ . '/../models/DoctorModel.php';

// Ensure only admins can access these reports.
Auth::requireRole('admin');

$action = $action ?? 'index';

match($action) {
    'index'  => reportIndex(),
    'export' => reportExport(),
    default  => reportIndex()
};


// ============================================================
// reportIndex()
// Displays the report filters and the generated report table.
// ============================================================
function reportIndex(): void
{
    // If the export button was clicked, redirect to the export function.
    if (($_GET['export'] ?? '') === 'csv') {
        reportExport();
        return;
    }

    $doctorModel      = new DoctorModel();
    $appointmentModel = new AppointmentModel();

    // Fetch doctors to populate the filter dropdown.
    $doctors = $doctorModel->getAll();

    // Read filter values from the URL.
    $filters    = getReportFilters();
    $hasFilters = !empty($filters['date_from']) && !empty($filters['date_to']);

    $appointments   = [];
    $statusSummary  = [];
    $totalCount     = 0;
    $errors         = [];

    if ($hasFilters) {

        // Validate date range.
        if ($filters['date_from'] > $filters['date_to']) {
            $errors[] = 'Start date must be before or equal to end date.';
        } else {
            // Fetch filtered report data.
            $totalCount   = $appointmentModel->countAll($filters);
            $appointments = $appointmentModel->getAll(0, 1000, $filters);

            // Calculate totals for each status.
            $statusSummary = buildStatusSummary($appointments);
        }
    }

    $pageTitle       = 'Reports';
    $currentPage_nav = 'reports';

    require_once __DIR__ . '/../views/reports/index.php';
}


// ============================================================
// reportExport()
// Generates and downloads a CSV file containing the filtered report data.
// ============================================================
function reportExport(): void
{
    $appointmentModel = new AppointmentModel();
    $filters          = getReportFilters();

    // Ensure a date range is selected before exporting.
    if (empty($filters['date_from']) || empty($filters['date_to'])) {
        flashMessage('Please select a date range first.', 'error');
        redirect('reports');
    }

    // Validate date range.
    if ($filters['date_from'] > $filters['date_to']) {
        flashMessage('Start date must be before or equal to end date.', 'error');
        redirect('reports');
    }

    // Fetch up to 10,000 records for the export.
    $appointments = $appointmentModel->getAll(0, 10000, $filters);

    // Prepare the CSV filename based on the date range.
    $filename = 'report_'
        . $filters['date_from']
        . '_to_'
        . $filters['date_to']
        . '.csv';

    // Set headers to force file download in the browser securely.
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Open PHP's output stream.
    $output = fopen('php://output', 'w');

    // Add Byte Order Mark (BOM) for Excel UTF-8 compatibility.
    fputs($output, "\xEF\xBB\xBF");

    // Write the CSV header row.
    fputcsv($output, [
        'ID',
        'Patient Name',
        'Doctor Name',
        'Specialization',
        'Date',
        'Time',
        'Status',
        'Reason',
        'Fee'
    ]);

    // Write each appointment as a data row.
    foreach ($appointments as $appt) {
        fputcsv($output, [
            $appt['id'],
            $appt['patient_name'],
            $appt['doctor_name'],
            $appt['specialization_name'],
            $appt['appt_date'],
            $appt['appt_time'],
            $appt['status'],
            $appt['reason'] ?? '',
            $appt['consultation_fee']
        ]);
    }

    // Write the summary section at the bottom of the CSV.
    fputcsv($output, []);
    fputcsv($output, ['--- SUMMARY ---']);
    fputcsv($output, ['Total Appointments', count($appointments)]);

    $summary = buildStatusSummary($appointments);
    foreach ($summary as $status => $count) {
        fputcsv($output, [ucfirst($status), $count]);
    }

    fclose($output);
    exit();
}


// ============================================================
// getReportFilters()
// Helper to read and sanitize filter inputs from the URL.
// ============================================================
function getReportFilters(): array
{
    $filters = [
        'date_from' => sanitizeString($_GET['date_from'] ?? $_GET['start_date'] ?? ''),
        'date_to'   => sanitizeString($_GET['date_to']   ?? $_GET['end_date'] ?? ''),
        'doctor_id' => (int) ($_GET['doctor_id']          ?? 0),
        'status'    => sanitizeString($_GET['status']    ?? ''),
    ];

    // Remove empty filters.
    return array_filter($filters);
}


// ============================================================
// buildStatusSummary()
// Helper to count the total number of appointments per status.
// ============================================================
function buildStatusSummary(array $appointments): array
{
    $summary = [
        'pending'   => 0,
        'confirmed' => 0,
        'completed' => 0,
        'cancelled' => 0
    ];

    foreach ($appointments as $appt) {
        if (isset($summary[$appt['status']])) {
            $summary[$appt['status']]++;
        }
    }

    return $summary;
}
