<?php
declare(strict_types=1);

/** Small JSON endpoints for dependent dropdowns and autocomplete */
final class AjaxController
{
    public function sections(): void
    {
        $classId = input_int('class_id');
        $items = [];
        foreach (section_options($classId) as $id => $name) {
            $items[] = ['id' => $id, 'name' => $name];
        }
        json_out(['ok' => true, 'items' => $items]);
    }

    public function subjects(): void
    {
        $items = [];
        foreach (subject_options(input_int('class_id')) as $id => $name) {
            $items[] = ['id' => $id, 'name' => $name];
        }
        json_out(['ok' => true, 'items' => $items]);
    }

    public function students(): void
    {
        authorize('students.view', 'fees.collect', 'library.issue', 'fees.view');
        $q = trim(preg_replace('/\s+/', ' ', (string)input('q', '')));
        if (mb_strlen($q) < 1) {
            json_out(['ok' => true, 'items' => []]);
        }
        $name = "CONCAT(s.first_name,' ',IFNULL(s.last_name,''))";
        $digits = preg_replace('/\D/', '', $q);
        // Every word must match one of: name, reg no, father name, father phone, B-Form / CNIC
        $where = [];
        $params = [];
        foreach (array_slice(explode(' ', $q), 0, 5) as $w) {
            $like = '%' . $w . '%';
            $where[] = "($name LIKE ? OR s.reg_no LIKE ? OR p.father_name LIKE ? OR REPLACE(p.father_phone,'-','') LIKE ? OR REPLACE(IFNULL(s.b_form,''),'-','') LIKE ? OR REPLACE(IFNULL(p.father_cnic,''),'-','') LIKE ?)";
            $wd = preg_replace('/\D/', '', $w);
            array_push($params, $like, $like, $like, $wd !== '' ? "%$wd%" : $like, $wd !== '' ? "%$wd%" : $like, $wd !== '' ? "%$wd%" : $like);
        }
        $sql = "SELECT s.id, $name AS name, s.reg_no, s.roll_no, p.father_name, CONCAT(c.name, IFNULL(CONCAT(' - ', sec.name),'')) AS class, s.status,
                (SELECT COALESCE(SUM(i.net_amount-i.paid_amount),0) FROM fee_invoices i WHERE i.student_id=s.id AND i.status IN ('unpaid','partial')) AS due,
                CASE
                    WHEN s.reg_no = ? OR (? <> '' AND s.reg_no LIKE CONCAT('%', ?)) THEN 0
                    WHEN $name = ? THEN 1
                    WHEN CONCAT(' ', $name, ' ') LIKE ? THEN 2
                    WHEN $name LIKE ? THEN 3
                    WHEN p.father_name LIKE ? THEN 4
                    ELSE 5 END AS score
                FROM students s JOIN classes c ON c.id=s.class_id LEFT JOIN sections sec ON sec.id=s.section_id LEFT JOIN parents p ON p.id=s.parent_id
                WHERE " . implode(' AND ', $where);
        array_unshift($params, $q, $digits, $digits, $q, '% ' . $q . ' %', $q . '%', '%' . $q . '%');
        if (input('all') !== '1') {
            $sql .= " AND s.status='active'";
        }
        if (Auth::scoped()) {
            $ids = Auth::teacherSectionIds();
            $sql .= ' AND s.section_id IN ' . DB::in($ids);
            $params = array_merge($params, $ids);
        }
        $rows = DB::all($sql . ' ORDER BY score, c.numeric_order, s.first_name LIMIT 25', $params);
        foreach ($rows as &$r) {
            $r['due'] = (float)$r['due'];
            $r['due_text'] = $r['due'] > 0 ? money($r['due']) : '';
            unset($r['score']);
        }
        json_out(['ok' => true, 'items' => $rows]);
    }

    public function parents(): void
    {
        authorize('students.create', 'students.edit');
        $q = '%' . trim((string)input('q', '')) . '%';
        json_out(['ok' => true, 'items' => DB::all('SELECT id, father_name, father_cnic, father_phone, mother_name FROM parents WHERE father_name LIKE ? OR father_cnic LIKE ? OR father_phone LIKE ? ORDER BY father_name LIMIT 10', [$q, $q, $q])]);
    }
}
