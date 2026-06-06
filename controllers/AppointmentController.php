<?php
// ============================================================
// controllers/AppointmentController.php
// Manages appointments for all user roles (Admin, Doctor, Patient).
// Handles listing, booking, viewing details, and status updates.
// ============================================================

require_once __DIR__ . '/../models/BaseModel.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/DoctorModel.php';
require_once __DIR__ . '/../models/AppointmentModel.php';
require_once __DIR__ . '/../models/PrescriptionModel.php';

// Ensure the user is logged in before accessing any appointment action.
Auth::requireAuth();

$action = $action ?? 'list';

// Route the request to the appropriate function based on the action parameter.
match($action) {
    'list'   => appointmentList(),
    'book'   => appointmentBook(),
    'detail' => appointmentDetail(),
    'status' => appointmentStatus(),
    'cancel' => appointmentCancel(),
    default  => appointmentList()
};


// ============================================================
// appointmentList()
// Displays a paginated list of appointments filtered by the logged-in user's role.
// ============================================================
function appointmentList(): void
{
    $appointmentModel = new AppointmentModel();

    // Retrieve and sanitize search/filter parameters from the URL.
    $filters = [
        'status'    => sanitizeString($_GET['status']    ?? ''),
        'date_from' => sanitizeString($_GET['date_from'] ?? ''),
        'date_to'   => sanitizeString($_GET['date_to']   ?? ''),
        'doctor_id' => (int) ($_GET['doctor_id']         ?? 0),
        'search'    => sanitizeString($_GET['search']    ?? ''),
    ];

    // Remove empty filter values to keep the query clean.
    $filters = array_filter($filters);

    $currentPageNum = max(1, (int) ($_GET['page_num'] ?? 1));

    // Route the logic based on the user's role.
    if (Auth::isAdmin()) {
        
        // Admins can see all appointments.
        $totalItems = $appointmentModel->countAll($filters);
        $paginator  = new Paginator($totalItems, ITEMS_PER_PAGE, $currentPageNum);
        
        // Preserve filters in pagination links.
        $paginator->setExtraParams($filters);
        
        $appointments = $appointmentModel->getAll(
            $paginator->offset(),
            $paginator->perPage(),
            $filters
        );

        // Fetch all doctors for the filter dropdown.
        $doctorModel = new DoctorModel();
        $doctors     = $doctorModel->getAll();

    } elseif (Auth::isDoctor()) {
        
        // Doctors can only see their own appointments.
        $doctorModel = new DoctorModel();
        $doctor      = $doctorModel->findByUserId(Auth::id());

        if (!$doctor) {
            flashMessage('Doctor profile not found.', 'error');
            redirect('dashboard');
        }

        $doctorId   = $doctor['id'];
        $totalItems = $appointmentModel->countByDoctor($doctorId, $filters);
        $paginator  = new Paginator($totalItems, ITEMS_PER_PAGE, $currentPageNum);
        
        // Preserve filters in pagination links.
        $paginator->setExtraParams($filters);
        
        $appointments = $appointmentModel->getByDoctor(
            $doctorId,
            $paginator->offset(),
            $paginator->perPage(),
            $filters
        );

        $doctors = [];

    } else {
        
        // Patients can only see their own appointments.
        $patientId  = Auth::id();
        $totalItems = $appointmentModel->countByPatient($patientId, $filters);
        $paginator  = new Paginator($totalItems, ITEMS_PER_PAGE, $currentPageNum);
        
        // Preserve filters in pagination links.
        $paginator->setExtraParams($filters);
        
        $appointments = $appointmentModel->getByPatient(
            $patientId,
            $paginator->offset(),
            $paginator->perPage(),
            $filters
        );

        $doctors = [];
    }

    $pageTitle       = 'Appointments';
    $currentPage_nav = 'appointments';

    // Render the view.
    require_once __DIR__ . '/../views/appointments/list.php';
}


// ============================================================
// appointmentBook()
// Allows a Patient to book a new appointment.
// ============================================================
function appointmentBook(): void
{
    // Ensure only patients can access this action.
    Auth::requireRole('patient');

    $doctorModel      = new DoctorModel();
    $appointmentModel = new AppointmentModel();
    $doctors          = $doctorModel->getAll();

    // Define the allowed appointment time slots.
    $timeSlots = [
        '09:00:00', '09:30:00', '10:00:00', '10:30:00',
        '11:00:00', '11:30:00', '12:00:00', '12:30:00',
        '13:00:00', '13:30:00', '14:00:00', '14:30:00',
        '15:00:00', '15:30:00', '16:00:00'
    ];

    if (isGet()) {
        $pageTitle       = 'Book Appointment';
        $currentPage_nav = 'book';
        require_once __DIR__ . '/../views/appointments/book.php';
        return;
    }

    if (isPost()) {

        // Validate CSRF token before processing the form.
        if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
            flashMessage('Invalid request.', 'error');
            redirect('appointments', 'book');
        }

        $doctorId = (int)    ($_POST['doctor_id']  ?? 0);
        $date     = sanitizeString($_POST['appt_date'] ?? '');
        $time     = sanitizeString($_POST['appt_time'] ?? '');
        $reason   = sanitizeString($_POST['reason']    ?? '');

        // Validate user inputs.
        $errors = [];

        if (!$doctorId)    $errors[] = 'Please select a doctor.';
        if (empty($date))  $errors[] = 'Please select a date.';
        if (empty($time))  $errors[] = 'Please select a time.';

        // Verify the selected time slot is valid.
        if (!empty($time) && !in_array($time, $timeSlots, true)) {
            $errors[] = 'Invalid appointment time slot.';
        }

        // Verify the date format.
        $dateObj = DateTime::createFromFormat('Y-m-d', $date);
        if (!empty($date) && (!$dateObj || $dateObj->format('Y-m-d') !== $date)) {
            $errors[] = 'Invalid appointment date.';
        }

        // Ensure the date is not in the past.
        if (!empty($date) && $dateObj && $date < date('Y-m-d')) {
            $errors[] = 'Appointment date cannot be in the past.';
        }

        // Verify the doctor works on the selected day of the week.
        if (!empty($date) && $dateObj && $doctorId) {
            $dayName = date('D', strtotime($date));
            if (!$doctorModel->isAvailableOnDay($doctorId, $dayName)) {
                $errors[] = "Doctor is not available on {$dayName}.";
            }
        }

        if (!empty($errors)) {
            flashMessage(implode(' ', $errors), 'error');
            redirect('appointments', 'book');
        }

        // Check for double bookings.
        if ($appointmentModel->hasConflict($doctorId, $date, $time)) {
            flashMessage(
                'This time slot is already booked. Please choose another.',
                'error'
            );
            redirect('appointments', 'book');
        }

        // Create the appointment record in the database.
        $apptId = $appointmentModel->book([
            'patient_id' => Auth::id(),
            'doctor_id'  => $doctorId,
            'appt_date'  => $date,
            'appt_time'  => $time,
            'reason'     => $reason ?: null
        ]);

        if (!$apptId) {
            flashMessage('Booking failed. Please try again.', 'error');
            redirect('appointments', 'book');
        }

        flashMessage('Appointment booked successfully!', 'success');
        redirect('appointments');
    }
}


// ============================================================
// appointmentDetail()
// Displays the full details of a specific appointment.
// ============================================================
function appointmentDetail(): void
{
    Auth::requireAuth();

    $id                = (int) ($_GET['id'] ?? 0);
    $appointmentModel  = new AppointmentModel();
    $prescriptionModel = new PrescriptionModel();

    if (!$id) {
        flashMessage('Invalid appointment ID.', 'error');
        redirect('appointments');
    }

    $appointment = $appointmentModel->findById($id);

    if (!$appointment) {
        flashMessage('Appointment not found.', 'error');
        redirect('appointments');
    }

    // Check appointment ownership before allowing access.
    if (Auth::isPatient() &&
        !$appointmentModel->isOwnedByPatient($id, Auth::id())) {
        http_response_code(403);
        require_once __DIR__ . '/../views/errors/403.php';
        exit();
    }

    if (Auth::isDoctor()) {
        $doctorModel = new DoctorModel();
        $doctor      = $doctorModel->findByUserId(Auth::id());
        if (!$doctor ||
            !$appointmentModel->isOwnedByDoctor($id, $doctor['id'])) {
            http_response_code(403);
            require_once __DIR__ . '/../views/errors/403.php';
            exit();
        }
    }

    // Fetch associated prescription if one exists.
    $prescription = $prescriptionModel->findByAppointmentId($id);

    $pageTitle       = 'Appointment Details';
    $currentPage_nav = 'appointments';

    require_once __DIR__ . '/../views/appointments/detail.php';
}


// ============================================================
// appointmentStatus()
// Allows Doctors and Admins to update the status of an appointment.
// ============================================================
function appointmentStatus(): void
{
    // Restrict to Admin and Doctor roles.
    Auth::requireRole('admin', 'doctor');

    if (!isPost()) {
        redirect('appointments');
    }

    // Validate CSRF token.
    if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
        flashMessage('Invalid request.', 'error');
        redirect('appointments');
    }

    $id               = (int) ($_POST['id']     ?? 0);
    $status           = sanitizeString($_POST['status'] ?? '');
    $notes            = sanitizeString($_POST['notes']  ?? '');
    $appointmentModel = new AppointmentModel();

    // Verify the requested status is valid.
    $validStatuses = ['confirmed', 'completed', 'cancelled'];
    if (!in_array($status, $validStatuses, true)) {
        flashMessage('Invalid status.', 'error');
        redirect('appointments');
    }

    $appointment = $appointmentModel->findById($id);

    if (!$appointment) {
        flashMessage('Appointment not found.', 'error');
        redirect('appointments');
    }

    // If a doctor is making the change, verify they own the appointment.
    if (Auth::isDoctor()) {
        $doctorModel = new DoctorModel();
        $doctor      = $doctorModel->findByUserId(Auth::id());
        if (!$doctor ||
            !$appointmentModel->isOwnedByDoctor($id, $doctor['id'])) {
            flashMessage('Access denied.', 'error');
            redirect('appointments');
        }
    }

    // Define allowed status transitions.
    $allowedTransitions = [
        'pending'   => ['confirmed', 'cancelled'],
        'confirmed' => ['completed', 'cancelled'],
        'completed' => [],
        'cancelled' => [],
    ];

    // Ensure the state transition is allowed based on the current status.
    $currentStatus = $appointment['status'];
    if (!in_array($status, $allowedTransitions[$currentStatus] ?? [], true)) {
        flashMessage(
            "Invalid status change from {$currentStatus} to {$status}.",
            'error'
        );
        redirect('appointments', 'detail', $id);
    }

    $appointmentModel->updateStatus($id, $status, $notes);

    flashMessage("Appointment marked as {$status}.", 'success');
    redirect('appointments', 'detail', $id);
}


// ============================================================
// appointmentCancel()
// Allows a Patient to cancel their own pending appointment.
// ============================================================
function appointmentCancel(): void
{
    // Restrict access to patients only.
    Auth::requireRole('patient');

    if (!isPost()) {
        redirect('appointments');
    }

    // Validate CSRF token.
    if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
        flashMessage('Invalid request.', 'error');
        redirect('appointments');
    }

    $id               = (int) ($_POST['id'] ?? 0);
    $appointmentModel = new AppointmentModel();
    $appointment      = $appointmentModel->findById($id);

    if (!$appointment) {
        flashMessage('Appointment not found.', 'error');
        redirect('appointments');
    }

    // Ensure the patient owns the appointment.
    if (!$appointmentModel->isOwnedByPatient($id, Auth::id())) {
        flashMessage('Access denied.', 'error');
        redirect('appointments');
    }

    // Only allow cancellation if the status is still pending.
    if ($appointment['status'] !== 'pending') {
        flashMessage(
            'Only pending appointments can be cancelled.',
            'error'
        );
        redirect('appointments');
    }

    $appointmentModel->updateStatus($id, 'cancelled');
    flashMessage('Appointment cancelled successfully.', 'success');
    redirect('appointments');
}
