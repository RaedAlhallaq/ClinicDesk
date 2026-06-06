<?php
// ============================================================
// models/PrescriptionModel.php
// كل العمليات على جدول prescriptions
// ============================================================

require_once __DIR__ . '/BaseModel.php';

class PrescriptionModel extends BaseModel
{
    // --------------------------------------------------------
    // findByAppointmentId(): جلب وصفة بـ appointment_id
    //
    // تُستخدم في:
    // - Doctor: هل هذا الموعد له وصفة بالفعل؟
    // - Patient: عرض الوصفة في صفحة التفاصيل
    //
    // تُرجع: بيانات الوصفة أو null
    // --------------------------------------------------------
    public function findByAppointmentId(int $appointmentId): ?array
    {
        return $this->fetchOne(
            "SELECT  p.id,
                     p.appointment_id,
                     p.diagnosis,
                     p.medications,
                     p.notes,
                     p.file_path,
                     p.created_at
             FROM    prescriptions p
             WHERE   p.appointment_id = ?",
            "i",
            [$appointmentId]
        );
    }


    // --------------------------------------------------------
    // findById(): جلب وصفة بالـ ID مع بيانات الموعد
    //
    // تُستخدم في:
    // - صفحة تفاصيل الوصفة
    // - التحقق من الملكية قبل التحميل
    // --------------------------------------------------------
    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            "SELECT  p.id,
                     p.appointment_id,
                     p.diagnosis,
                     p.medications,
                     p.notes,
                     p.file_path,
                     p.created_at,
                     a.patient_id,
                     a.doctor_id,
                     a.appt_date,
                     a.appt_time,
                     u_p.name  AS patient_name,
                     u_d.name  AS doctor_name,
                     s.name    AS specialization_name
             FROM    prescriptions  p
             JOIN    appointments   a   ON p.appointment_id  = a.id
             JOIN    users          u_p ON a.patient_id      = u_p.id
             JOIN    doctors        d   ON a.doctor_id       = d.id
             JOIN    users          u_d ON d.user_id         = u_d.id
             JOIN    specializations s  ON d.specialization_id = s.id
             WHERE   p.id = ?",
            "i",
            [$id]
        );
    }


    // --------------------------------------------------------
    // create(): إنشاء وصفة جديدة
    //
    // تُستخدم في:
    // - Doctor يضيف وصفة بعد إكمال الموعد
    //
    // $data يجب أن يحتوي على:
    // appointment_id, diagnosis, medications
    // notes (اختياري), file_path (اختياري)
    //
    // تُرجع: ID الوصفة الجديدة أو 0 إذا فشل
    // --------------------------------------------------------
    public function create(array $data): int
    {
        $result = $this->execute(
            "INSERT INTO prescriptions
                (appointment_id, diagnosis, medications,
                 notes,          file_path)
             VALUES
                (?,              ?,         ?,
                 ?,              ?         )",
            "issss",
            [
                $data['appointment_id'],
                $data['diagnosis'],
                $data['medications'],
                $data['notes']     ?? null,
                $data['file_path'] ?? null
            ]
        );

        return $result ? $this->lastInsertId() : 0;
    }


    // --------------------------------------------------------
    // update(): تعديل وصفة موجودة
    //
    // تُستخدم في:
    // - Doctor يعدّل الوصفة
    // --------------------------------------------------------
    public function update(int $id, array $data): bool
    {
        return (bool) $this->execute(
            "UPDATE prescriptions
             SET    diagnosis   = ?,
                    medications = ?,
                    notes       = ?,
                    file_path   = ?
             WHERE  id          = ?",
            "ssssi",
            [
                $data['diagnosis'],
                $data['medications'],
                $data['notes']     ?? null,
                $data['file_path'] ?? null,
                $id
            ]
        );
    }


    // --------------------------------------------------------
    // updateFilePath(): تحديث مسار الملف فقط
    //
    // تُستخدم عند رفع ملف PDF جديد
    // دون تغيير باقي بيانات الوصفة
    // --------------------------------------------------------
    public function updateFilePath(int $id, string $filePath): bool
    {
        return (bool) $this->execute(
            "UPDATE prescriptions
             SET    file_path = ?
             WHERE  id        = ?",
            "si",
            [$filePath, $id]
        );
    }


    // --------------------------------------------------------
    // getByPatient(): كل وصفات مريض معين
    //
    // تُستخدم في:
    // - صفحة "وصفاتي" للمريض
    //
    // تُرجع: مصفوفة الوصفات مع بيانات الموعد
    // --------------------------------------------------------
    public function getByPatient(int $patientId): array
    {
        return $this->fetchAll(
            "SELECT  p.id,
                     p.appointment_id,
                     p.diagnosis,
                     p.medications,
                     p.notes,
                     p.file_path,
                     p.created_at,
                     a.appt_date,
                     a.appt_time,
                     u_d.name  AS doctor_name,
                     s.name    AS specialization_name
             FROM    prescriptions  p
             JOIN    appointments   a   ON p.appointment_id    = a.id
             JOIN    doctors        d   ON a.doctor_id         = d.id
             JOIN    users          u_d ON d.user_id           = u_d.id
             JOIN    specializations s  ON d.specialization_id = s.id
             WHERE   a.patient_id = ?
             ORDER BY p.created_at DESC",
            "i",
            [$patientId]
        );
    }


    // --------------------------------------------------------
    // getByDoctor(): كل وصفات طبيب معين
    //
    // تُستخدم في:
    // - صفحة وصفات الطبيب
    // --------------------------------------------------------
    public function getByDoctor(int $doctorId): array
    {
        return $this->fetchAll(
            "SELECT  p.id,
                     p.appointment_id,
                     p.diagnosis,
                     p.medications,
                     p.notes,
                     p.file_path,
                     p.created_at,
                     a.appt_date,
                     a.appt_time,
                     u_p.name  AS patient_name
             FROM    prescriptions  p
             JOIN    appointments   a   ON p.appointment_id = a.id
             JOIN    users          u_p ON a.patient_id     = u_p.id
             WHERE   a.doctor_id = ?
             ORDER BY p.created_at DESC",
            "i",
            [$doctorId]
        );
    }


    // --------------------------------------------------------
    // existsForAppointment(): هل الموعد له وصفة بالفعل؟
    //
    // تُستخدم قبل إنشاء وصفة جديدة
    // لأن كل موعد له وصفة واحدة فقط (UNIQUE في DB)
    //
    // تُرجع: true = توجد وصفة، false = لا توجد
    // --------------------------------------------------------
    public function existsForAppointment(int $appointmentId): bool
    {
        $count = $this->fetchColumn(
            "SELECT COUNT(*)
             FROM   prescriptions
             WHERE  appointment_id = ?",
            "i",
            [$appointmentId]
        );

        return (int) $count > 0;
    }


    // --------------------------------------------------------
    // isOwnedByPatient(): هل هذه الوصفة تخص هذا المريض؟
    //
    // تُستخدم قبل السماح بتحميل الملف
    // المريض يقدر يحمّل وصفاته فقط
    // --------------------------------------------------------
    public function isOwnedByPatient(
        int $prescriptionId,
        int $patientId
    ): bool {
        $count = $this->fetchColumn(
            "SELECT COUNT(*)
             FROM   prescriptions p
             JOIN   appointments  a ON p.appointment_id = a.id
             WHERE  p.id          = ?
             AND    a.patient_id  = ?",
            "ii",
            [$prescriptionId, $patientId]
        );

        return (int) $count > 0;
    }


    // --------------------------------------------------------
    // isOwnedByDoctor(): هل هذه الوصفة أنشأها هذا الطبيب؟
    //
    // تُستخدم قبل السماح للطبيب بتعديل الوصفة
    // --------------------------------------------------------
    public function isOwnedByDoctor(
        int $prescriptionId,
        int $doctorId
    ): bool {
        $count = $this->fetchColumn(
            "SELECT COUNT(*)
             FROM   prescriptions p
             JOIN   appointments  a ON p.appointment_id = a.id
             WHERE  p.id          = ?
             AND    a.doctor_id   = ?",
            "ii",
            [$prescriptionId, $doctorId]
        );

        return (int) $count > 0;
    }


    // --------------------------------------------------------
    // countByPatient(): عدد وصفات مريض
    //
    // تُستخدم في: Dashboard المريض (إحصائيات)
    // --------------------------------------------------------
    public function countByPatient(int $patientId): int
    {
        return (int) $this->fetchColumn(
            "SELECT COUNT(*)
             FROM   prescriptions p
             JOIN   appointments  a ON p.appointment_id = a.id
             WHERE  a.patient_id = ?",
            "i",
            [$patientId]
        );
    }
}