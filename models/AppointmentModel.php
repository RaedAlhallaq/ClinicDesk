<?php
// ============================================================
// models/AppointmentModel.php
// كل العمليات على جدول appointments
// ============================================================

require_once __DIR__ . '/BaseModel.php';

class AppointmentModel extends BaseModel
{
    // --------------------------------------------------------
    // الـ SELECT الأساسي المشترك بين كل الـ queries
    // نعرّفه مرة واحدة لتجنب التكرار
    // --------------------------------------------------------
    private function baseSelect(): string
    {
        return "SELECT
                    a.id,
                    a.patient_id,
                    a.doctor_id,
                    a.appt_date,
                    a.appt_time,
                    a.status,
                    a.reason,
                    a.doctor_notes,
                    a.created_at,
                    p.name          AS patient_name,
                    p.phone         AS patient_phone,
                    du.name         AS doctor_name,
                    s.name          AS specialization_name,
                    d.consultation_fee
                FROM  appointments  a
                JOIN  users         p  ON a.patient_id = p.id
                JOIN  doctors       d  ON a.doctor_id  = d.id
                JOIN  users         du ON d.user_id    = du.id
                JOIN  specializations s ON d.specialization_id = s.id";
    }


    // --------------------------------------------------------
    // hasConflict(): هل يوجد تعارض في الحجز؟
    //
    // يتحقق: هل هذا الطبيب محجوز في نفس التاريخ والوقت؟
    //
    // تُستدعى قبل book() لعرض رسالة خطأ واضحة
    // (DB أيضًا تمنعه لكن رسالتها غير مفهومة)
    // --------------------------------------------------------
    public function hasConflict(
        int    $doctorId,
        string $date,
        string $time
    ): bool {
        $count = $this->fetchColumn(
            "SELECT COUNT(*)
             FROM   appointments
             WHERE  doctor_id  = ?
             AND    appt_date  = ?
             AND    appt_time  = ?
             AND    status    != 'cancelled'",
            "iss",
            [$doctorId, $date, $time]
        );

        return (int) $count > 0;
    }


    public function countToday(): int
    {
        return (int) $this->fetchColumn(
            "SELECT COUNT(*)
             FROM   appointments
             WHERE  appt_date = CURDATE()"
        );
    }


    public function countThisWeekByStatus(): array
    {
        $rows = $this->fetchAll(
            "SELECT   status, COUNT(*) as total
             FROM     appointments
             WHERE    YEARWEEK(appt_date, 1) = YEARWEEK(CURDATE(), 1)
             GROUP BY status"
        );

        return $this->normaliseStatusCounts($rows);
    }


    public function countThisMonthByDoctor(int $doctorId): array
    {
        $rows = $this->fetchAll(
            "SELECT   status, COUNT(*) as total
             FROM     appointments
             WHERE    doctor_id = ?
             AND      appt_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
             AND      appt_date <  DATE_ADD(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 1 MONTH)
             GROUP BY status",
            "i",
            [$doctorId]
        );

        return $this->normaliseStatusCounts($rows);
    }


    public function getNextUpcomingByPatient(int $patientId): ?array
    {
        return $this->fetchOne(
            $this->baseSelect()
            . " WHERE  a.patient_id = ?
                AND    a.appt_date >= CURDATE()
                AND    a.status    IN ('pending','confirmed')
                ORDER BY a.appt_date ASC, a.appt_time ASC
                LIMIT  1",
            "i",
            [$patientId]
        );
    }


    // --------------------------------------------------------
    // book(): حجز موعد جديد
    //
    // تُرجع: ID الموعد الجديد أو 0 إذا فشل
    // --------------------------------------------------------
    public function book(array $data): int
    {
        $result = $this->execute(
            "INSERT INTO appointments
                (patient_id, doctor_id, appt_date,
                 appt_time,  status,    reason)
             VALUES
                (?,          ?,         ?,
                 ?,           'pending', ?)",
            "iisss",
            [
                $data['patient_id'],
                $data['doctor_id'],
                $data['appt_date'],
                $data['appt_time'],
                $data['reason'] ?? null
            ]
        );

        return $result ? $this->lastInsertId() : 0;
    }


    // --------------------------------------------------------
    // findById(): جلب موعد واحد بالـ ID مع كل التفاصيل
    //
    // تُستخدم في:
    // - صفحة تفاصيل الموعد
    // - التحقق من ملكية الموعد
    // --------------------------------------------------------
    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            $this->baseSelect() . " WHERE a.id = ?",
            "i",
            [$id]
        );
    }


    // --------------------------------------------------------
    // buildWhereClause(): بناء WHERE ديناميكي
    //
    // private helper تُستخدم داخليًا فقط
    //
    // $filters مصفوفة تحتوي على:
    // 'status'     → فلترة بالحالة
    // 'date_from'  → من تاريخ
    // 'date_to'    → إلى تاريخ
    // 'doctor_id'  → فلترة بطبيب معين
    // 'search'     → بحث باسم المريض
    //
    // تُرجع: ['where' => '...', 'types' => '...', 'params' => [...]]
    // --------------------------------------------------------
    private function buildWhereClause(
        array  $filters,
        array  $baseConditions = []
    ): array {
        $conditions = $baseConditions;
        $params     = [];
        $types      = '';

        // فلترة بالحالة
        if (!empty($filters['status'])) {
            $conditions[] = "a.status = ?";
            $params[]     = $filters['status'];
            $types       .= 's';
        }

        // فلترة من تاريخ
        if (!empty($filters['date_from'])) {
            $conditions[] = "a.appt_date >= ?";
            $params[]     = $filters['date_from'];
            $types       .= 's';
        }

        // فلترة إلى تاريخ
        if (!empty($filters['date_to'])) {
            $conditions[] = "a.appt_date <= ?";
            $params[]     = $filters['date_to'];
            $types       .= 's';
        }

        // فلترة بطبيب معين (للـ Admin)
        if (!empty($filters['doctor_id'])) {
            $conditions[] = "a.doctor_id = ?";
            $params[]     = (int) $filters['doctor_id'];
            $types       .= 'i';
        }

        // بحث باسم المريض (للـ Admin)
        if (!empty($filters['search'])) {
            $conditions[] = "p.name LIKE ?";
            $params[]     = '%' . $filters['search'] . '%';
            $types       .= 's';
        }

        // بناء جملة WHERE النهائية
        $where = '';
        if (!empty($conditions)) {
            $where = "WHERE " . implode(" AND ", $conditions);
        }

        return [
            'where'  => $where,
            'types'  => $types,
            'params' => $params
        ];
    }


    // --------------------------------------------------------
    // getByPatient(): مواعيد مريض معين مع Pagination وفلترة
    //
    // تُستخدم في: صفحة "مواعيدي" للمريض
    // --------------------------------------------------------
    public function getByPatient(
        int   $patientId,
        int   $offset,
        int   $limit,
        array $filters = []
    ): array {
        // الشرط الأساسي: هذا المريض فقط
        $baseConditions = ["a.patient_id = ?"];
        $baseParams     = [$patientId];
        $baseTypes      = 'i';

        $clause = $this->buildWhereClause($filters, $baseConditions);

        // دمج الـ params: الأساسية أولًا ثم الفلاتر ثم LIMIT/OFFSET
        $allParams = array_merge(
            $baseParams,
            $clause['params'],
            [$limit, $offset]
        );
        $allTypes = $baseTypes . $clause['types'] . 'ii';

        return $this->fetchAll(
            $this->baseSelect()
            . " " . $clause['where']
            . " ORDER BY a.appt_date DESC, a.appt_time DESC"
            . " LIMIT ? OFFSET ?",
            $allTypes,
            $allParams
        );
    }


    // --------------------------------------------------------
    // getByDoctor(): مواعيد طبيب معين مع Pagination وفلترة
    //
    // تُستخدم في: جدول الطبيب اليومي والأسبوعي
    // --------------------------------------------------------
    public function getByDoctor(
        int   $doctorId,
        int   $offset,
        int   $limit,
        array $filters = []
    ): array {
        $baseConditions = ["a.doctor_id = ?"];
        $baseParams     = [$doctorId];
        $baseTypes      = 'i';

        $clause = $this->buildWhereClause($filters, $baseConditions);

        $allParams = array_merge(
            $baseParams,
            $clause['params'],
            [$limit, $offset]
        );
        $allTypes = $baseTypes . $clause['types'] . 'ii';

        return $this->fetchAll(
            $this->baseSelect()
            . " " . $clause['where']
            . " ORDER BY a.appt_date ASC, a.appt_time ASC"
            . " LIMIT ? OFFSET ?",
            $allTypes,
            $allParams
        );
    }


    // --------------------------------------------------------
    // getAll(): كل المواعيد للـ Admin مع Pagination وفلترة
    // --------------------------------------------------------
    public function getAll(
        int   $offset,
        int   $limit,
        array $filters = []
    ): array {
        $clause = $this->buildWhereClause($filters);

        $allParams = array_merge(
            $clause['params'],
            [$limit, $offset]
        );
        $allTypes = $clause['types'] . 'ii';

        return $this->fetchAll(
            $this->baseSelect()
            . " " . $clause['where']
            . " ORDER BY a.created_at DESC"
            . " LIMIT ? OFFSET ?",
            $allTypes,
            $allParams
        );
    }


    // --------------------------------------------------------
    // countByPatient(): عدد مواعيد مريض (للـ Paginator)
    // --------------------------------------------------------
    public function countByPatient(
        int   $patientId,
        array $filters = []
    ): int {
        $baseConditions = ["a.patient_id = ?"];
        $baseParams     = [$patientId];
        $baseTypes      = 'i';

        $clause = $this->buildWhereClause($filters, $baseConditions);

        $allParams = array_merge($baseParams, $clause['params']);
        $allTypes  = $baseTypes . $clause['types'];

        return (int) $this->fetchColumn(
            "SELECT COUNT(*)
             FROM   appointments a
             JOIN   doctors      d  ON a.doctor_id  = d.id
             JOIN   users        p  ON a.patient_id = p.id
             " . $clause['where'],
            $allTypes,
            $allParams
        );
    }


    // --------------------------------------------------------
    // countByDoctor(): عدد مواعيد طبيب (للـ Paginator)
    // --------------------------------------------------------
    public function countByDoctor(
        int   $doctorId,
        array $filters = []
    ): int {
        $baseConditions = ["a.doctor_id = ?"];
        $baseParams     = [$doctorId];
        $baseTypes      = 'i';

        $clause = $this->buildWhereClause($filters, $baseConditions);

        $allParams = array_merge($baseParams, $clause['params']);
        $allTypes  = $baseTypes . $clause['types'];

        return (int) $this->fetchColumn(
            "SELECT COUNT(*)
             FROM   appointments a
             " . $clause['where'],
            $allTypes,
            $allParams
        );
    }


    // --------------------------------------------------------
    // countAll(): كل المواعيد (للـ Admin Paginator)
    // --------------------------------------------------------
    public function countAll(array $filters = []): int
    {
        $clause = $this->buildWhereClause($filters);

        return (int) $this->fetchColumn(
            "SELECT COUNT(*)
             FROM   appointments a
             JOIN   users        p ON a.patient_id = p.id
             " . $clause['where'],
            $clause['types'],
            $clause['params']
        );
    }


    // --------------------------------------------------------
    // updateStatus(): تحديث حالة الموعد
    //
    // تُستخدم في:
    // - Doctor: confirm / complete / cancel
    // - Admin:  تغيير أي حالة
    // - Patient: cancel (pending فقط)
    //
    // $notes → ملاحظات الطبيب (اختياري)
    // --------------------------------------------------------
    public function updateStatus(
        int    $id,
        string $status,
        string $notes = ''
    ): bool {
        // إذا تم تمرير notes → حدّثها أيضًا
        if (!empty($notes)) {
            return (bool) $this->execute(
                "UPDATE appointments
                 SET    status       = ?,
                        doctor_notes = ?
                 WHERE  id           = ?",
                "ssi",
                [$status, $notes, $id]
            );
        }

        // بدون notes → حدّث الحالة فقط
        return (bool) $this->execute(
            "UPDATE appointments
             SET    status = ?
             WHERE  id     = ?",
            "si",
            [$status, $id]
        );
    }


    // --------------------------------------------------------
    // getTodayByDoctor(): مواعيد اليوم لطبيب معين
    //
    // تُستخدم في: أعلى Dashboard الطبيب
    // --------------------------------------------------------
    public function getTodayByDoctor(int $doctorId): array
    {
        return $this->fetchAll(
            $this->baseSelect()
            . " WHERE  a.doctor_id = ?
                AND    a.appt_date = CURDATE()
                AND    a.status   != 'cancelled'
                ORDER BY a.appt_time ASC",
            "i",
            [$doctorId]
        );
    }


    // --------------------------------------------------------
    // getUpcomingByPatient(): المواعيد القادمة لمريض
    //
    // تُستخدم في: Dashboard المريض
    // --------------------------------------------------------
    public function getUpcomingByPatient(
        int $patientId,
        int $limit = 5
    ): array {
        return $this->fetchAll(
            $this->baseSelect()
            . " WHERE  a.patient_id = ?
                AND    a.appt_date >= CURDATE()
                AND    a.status    IN ('pending','confirmed')
                ORDER BY a.appt_date ASC, a.appt_time ASC
                LIMIT  ?",
            "ii",
            [$patientId, $limit]
        );
    }


    // --------------------------------------------------------
    // getRecentForAdmin(): آخر N موعد للـ Admin Dashboard
    // --------------------------------------------------------
    public function getRecentForAdmin(int $limit = 5): array
    {
        return $this->fetchAll(
            $this->baseSelect()
            . " ORDER BY a.created_at DESC
                LIMIT ?",
            "i",
            [$limit]
        );
    }


    // --------------------------------------------------------
    // countByStatus(): إحصائيات حسب الحالة
    //
    // تُستخدم في: Dashboard الإحصائيات
    //
    // تُرجع:
    // ['pending'=>5, 'confirmed'=>3, 'completed'=>20, 'cancelled'=>2]
    // --------------------------------------------------------
    public function countByStatus(
        string $scope   = 'all',
        int    $scopeId = 0
    ): array {
        // scope = 'all' | 'patient' | 'doctor'
        $where  = '';
        $types  = '';
        $params = [];

        if ($scope === 'patient') {
            $where  = "WHERE patient_id = ?";
            $types  = 'i';
            $params = [$scopeId];
        } elseif ($scope === 'doctor') {
            $where  = "WHERE doctor_id = ?";
            $types  = 'i';
            $params = [$scopeId];
        }

        $rows = $this->fetchAll(
            "SELECT   status, COUNT(*) as total
             FROM     appointments
             {$where}
             GROUP BY status",
            $types,
            $params
        );

        // قيم افتراضية
        $counts = [
            'pending'   => 0,
            'confirmed' => 0,
            'completed' => 0,
            'cancelled' => 0
        ];

        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['total'];
        }

        return $counts;
    }


    private function normaliseStatusCounts(array $rows): array
    {
        $counts = [
            'pending'   => 0,
            'confirmed' => 0,
            'completed' => 0,
            'cancelled' => 0
        ];

        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['total'];
        }

        return $counts;
    }


    // --------------------------------------------------------
    // isOwnedByPatient(): هل هذا الموعد يخص هذا المريض؟
    //
    // تُستخدم للتحقق من الملكية قبل أي عملية
    // مثل: إلغاء الموعد
    // --------------------------------------------------------
    public function isOwnedByPatient(
        int $appointmentId,
        int $patientId
    ): bool {
        $count = $this->fetchColumn(
            "SELECT COUNT(*)
             FROM   appointments
             WHERE  id         = ?
             AND    patient_id = ?",
            "ii",
            [$appointmentId, $patientId]
        );

        return (int) $count > 0;
    }


    // --------------------------------------------------------
    // isOwnedByDoctor(): هل هذا الموعد يخص هذا الطبيب؟
    // --------------------------------------------------------
    public function isOwnedByDoctor(
        int $appointmentId,
        int $doctorId
    ): bool {
        $count = $this->fetchColumn(
            "SELECT COUNT(*)
             FROM   appointments
             WHERE  id        = ?
             AND    doctor_id = ?",
            "ii",
            [$appointmentId, $doctorId]
        );

        return (int) $count > 0;
    }
}
