<?php

namespace Modules\Xenegrade\Services;

use Illuminate\Support\Facades\DB;

/**
 * Final grade frequency for one course section from SQL Server (CLIENTASSIGNMENT + schedule).
 */
class GradeDistributionService
{
    /**
     * @return list<object>
     */
    public static function queryRows(string $sesName, string $couCourseId, string $schSectionId): array
    {
        $sql = <<<'SQL'
WITH GradeMap AS (
    SELECT *
    FROM (VALUES
        ('F',  'Failure',      '0 - 59.4',    0.0),
        ('D',  'Passing',      '59.5 - 64.4', 1.0),
        ('D+', 'Passing',      '64.5 - 69.9', 1.5),
        ('C',  'Satisfactory', '70 - 74.4',   2.0),
        ('C+', 'Satisfactory', '74.5 - 79.4', 2.5),
        ('B',  'Good',         '79.5 - 84.4', 3.0),
        ('B+', 'Good',         '84.5 - 89.4', 3.5),
        ('A-', 'Excellent',    '89.5 - 94.4', 3.8),
        ('A',  'Excellent',    '94.5 - 100',  4.0)
    ) AS G(assGPA, [Grade Description], [Range], [Quality])
),
FilteredAssignments AS (
    SELECT
        CA.assGPA
    FROM CLIENTASSIGNMENT CA
        INNER JOIN COURSESCHEDULE cs
            ON cs.schID = CA.schID
        INNER JOIN SESSIONS s
            ON s.sesID = cs.sesID
        INNER JOIN STAFF st
            ON st.staID = cs.staID
        INNER JOIN COURSE co
            ON co.couID = cs.couID
        INNER JOIN ROOM ro
            ON ro.rooID = cs.rooID
        INNER JOIN FACILITY f
            ON f.facID = ro.facID
        INNER JOIN DEPARTMENT d
            ON d.depID = cs.depID
        INNER JOIN GRADINGCODE gc
            ON gc.graGradeVersion = 2
            AND CA.assGradeVersion = 2
            AND gc.graLetter = CA.assGPA
    WHERE
        s.sesName = ?
        AND co.couCourseID = ?
        AND cs.schSectionID = ?
        AND LOWER(LTRIM(RTRIM(cs.schCourseStatus))) = 'offered'
        AND co.couCourseID NOT IN
            ('serv0001', 'advs0001', 'plas0014', 'plam0014', 'plae0014')
        AND cs.schSectionID NOT LIKE '%ex%'
)
SELECT
    GM.assGPA,
    GM.[Grade Description],
    GM.[Range],
    GM.[Quality],
    COUNT(FA.assGPA) AS [Frequency]
FROM GradeMap GM
    LEFT JOIN FilteredAssignments FA
        ON FA.assGPA = GM.assGPA
GROUP BY
    GM.assGPA,
    GM.[Grade Description],
    GM.[Range],
    GM.[Quality]
ORDER BY
    GM.[Quality]
SQL;

        return DB::connection('sqlsrv')->select($sql, [
            $sesName,
            $couCourseId,
            $schSectionId,
        ]);
    }
}
