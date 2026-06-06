<?php
// ============================================================
// controllers/PrescriptionController.php
// Manage medical prescriptions. Doctors can add them, while
// Patients and Doctors can view and download them.
// ============================================================

require_once __DIR__ . '/../models/BaseModel.php';
require_once __DIR__ . '/../models/DoctorModel.php';
require_once __DIR__ . '/../models/AppointmentModel.php';
require_once __DIR__ . '/../models/PrescriptionModel.php';

// Ensure the user is logged in before accessing any prescription action.
Auth::requireAuth();

$action = $action ?? 'list';

match($action) {
    'list'     => prescriptionList(),
    'add'      => prescriptionAdd(),
    'download' => prescriptionDownload(),
    default    => prescriptionList()
};


// ============================================================
// prescriptionList()
// Show a list of prescriptions filtered by the logged-in user's role.
// ============================================================
function prescriptionList(): void
{
    $prescriptionModel = new PrescriptionModel();

    if (Auth::isDoctor()) {

        // Fetch the doctor's profile record.
        $doctorModel = new DoctorModel();
        $doctor      = $doctorModel->findByUserId(Auth::id());

        if (!$doctor) {
            flashMessage('Doctor profile not found.', 'error');
            redirect('dashboard');
        }

        // Doctors see all prescriptions they have issued.
        $prescriptions = $prescriptionModel->getByDoctor($doctor['id']);

    } elseif (Auth::isPatient()) {

        // Patients only see their own prescriptions.
        $prescriptions = $prescriptionModel->getByPatient(Auth::id());

    } else {
        // Admins don't have a direct prescriptions list, redirect them to appointments.
        redirect('appointments');
    }

    $pageTitle       = 'Prescriptions';
    $currentPage_nav = 'prescriptions';

    require_once __DIR__ . '/../views/prescriptions/list.php';
}


// ============================================================
// prescriptionAdd()
// Allows a Doctor to add a prescription to a completed appointment.
// ============================================================
function prescriptionAdd(): void
{
    // Restrict access to Doctors only.
    Auth::requireRole('doctor');

    $appointmentModel  = new AppointmentModel();
    $prescriptionModel = new PrescriptionModel();
    $doctorModel       = new DoctorModel();

    // Read the appointment ID from the URL.
    $appointmentId = (int) ($_GET['appointment_id'] ?? 0);

    if (!$appointmentId) {
        flashMessage('Invalid appointment.', 'error');
        redirect('appointments');
    }

    // Always include the appointment_id in the redirect URL in case of errors.
    $addUrl = BASE_URL . '/index.php?page=prescriptions&action=add&appointment_id=' . $appointmentId;

    $appointment = $appointmentModel->findById($appointmentId);

    if (!$appointment) {
        flashMessage('Appointment not found.', 'error');
        redirect('appointments');
    }

    // Verify that this appointment belongs to the logged-in doctor.
    $doctor = $doctorModel->findByUserId(Auth::id());

    if (!$doctor ||
        !$appointmentModel->isOwnedByDoctor($appointmentId, $doctor['id'])) {
        flashMessage('Access denied.', 'error');
        redirect('appointments');
    }

    // Ensure the appointment is marked as 'completed' before prescribing.
    if ($appointment['status'] !== 'completed') {
        flashMessage(
            'Prescription can only be added to completed appointments.',
            'error'
        );
        redirect('appointments', 'detail', $appointmentId);
    }

    // Prevent duplicate prescriptions for the same appointment.
    if ($prescriptionModel->existsForAppointment($appointmentId)) {
        flashMessage(
            'A prescription already exists for this appointment.',
            'warning'
        );
        redirect('appointments', 'detail', $appointmentId);
    }

    if (isGet()) {
        $pageTitle       = 'Add Prescription';
        $currentPage_nav = 'prescriptions';
        require_once __DIR__ . '/../views/prescriptions/add.php';
        return;
    }

    if (isPost()) {

        // Validate CSRF token.
        if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
            flashMessage('Invalid request.', 'error');
            // Preserve the appointment ID in the URL on failure.
            redirectTo($addUrl);
        }

        $diagnosis   = sanitizeString($_POST['diagnosis']   ?? '');
        $medications = sanitizeString($_POST['medications'] ?? '');
        $notes       = sanitizeString($_POST['notes']       ?? '');

        // Validate required fields.
        if (empty($diagnosis)) {
            flashMessage('Diagnosis is required.', 'error');
            redirectTo($addUrl);
        }

        if (empty($medications)) {
            flashMessage('Medications are required.', 'error');
            redirectTo($addUrl);
        }

        // Handle an optional PDF file upload.
        $filePath = null;

        if (!empty($_FILES['prescription_file']['name'])) {
            // Securely process the upload (handlePrescriptionUpload is in core/helpers.php).
            $uploadResult = handlePrescriptionUpload($appointmentId);

            if ($uploadResult['error']) {
                flashMessage($uploadResult['error'], 'error');
                redirectTo($addUrl);
            }

            $filePath = $uploadResult['path'];
        }

        // Create the prescription record in the database.
        $prescId = $prescriptionModel->create([
            'appointment_id' => $appointmentId,
            'diagnosis'      => $diagnosis,
            'medications'    => $medications,
            'notes'          => $notes ?: null,
            'file_path'      => $filePath
        ]);

        if (!$prescId) {
            flashMessage('Failed to save prescription.', 'error');
            redirectTo($addUrl);
        }

        flashMessage('Prescription added successfully.', 'success');
        redirect('appointments', 'detail', $appointmentId);
    }
}


// ============================================================
// prescriptionDownload()
// Securely stream the prescription PDF file to the user's browser.
// Includes strict ownership checks to prevent unauthorized access.
// ============================================================
function prescriptionDownload(): void
{
    $appointmentId     = (int) ($_GET['id'] ?? 0);
    $prescriptionModel = new PrescriptionModel();

    if (!$appointmentId) {
        flashMessage('Invalid appointment ID.', 'error');
        redirect('prescriptions');
    }

    // Fetch the prescription record by its appointment ID.
    $prescription = $prescriptionModel->findByAppointmentId($appointmentId);

    if (!$prescription) {
        flashMessage('Prescription not found.', 'error');
        redirect('prescriptions');
    }

    // Re-fetch by prescription ID to ensure all fields are loaded.
    $prescription = $prescriptionModel->findById((int) $prescription['id']);

    // Check ownership based on the user's role.
    if (Auth::isPatient()) {
        if (!$prescriptionModel->isOwnedByPatient((int) $prescription['id'], Auth::id())) {
            http_response_code(403);
            require_once __DIR__ . '/../views/errors/403.php';
            exit();
        }
    } elseif (Auth::isDoctor()) {
        $doctorModel = new DoctorModel();
        $doctor      = $doctorModel->findByUserId(Auth::id());
        if (!$doctor ||
            !$prescriptionModel->isOwnedByDoctor((int) $prescription['id'], $doctor['id'])) {
            http_response_code(403);
            require_once __DIR__ . '/../views/errors/403.php';
            exit();
        }
    }
    // Note: Admins are always allowed to download prescriptions.

    // Ensure a physical file was attached to this prescription.
    if (empty($prescription['file_path'])) {
        flashMessage('No file attached to this prescription.', 'error');
        redirect('prescriptions');
    }

    $filePath = PRESCRIPTION_PATH . $prescription['file_path'];

    if (!file_exists($filePath)) {
        flashMessage('File not found on server.', 'error');
        redirect('prescriptions');
    }

    // Safely stream the file to the browser with headers to force download.
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="prescription_' . $appointmentId . '.pdf"');
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Flush the output buffer before streaming to prevent file corruption.
    ob_clean();
    flush();

    readfile($filePath);
    exit();
}
