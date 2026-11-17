<?php
declare(strict_types=1);

/** Generic list / form / save / delete / export / print for definition-driven resources */
final class CrudController
{
    private function crud(): Crud
    {
        return Crud::load((string)input('res', ''));
    }

    public function index(): void
    {
        $c = $this->crud();
        authorize($c->perm('view'));
        $p = $c->listData();
        view('crud/index', ['c' => $c, 'p' => $p, 'title' => t($c->d['title'])]);
    }

    public function form(): void
    {
        $c = $this->crud();
        $id = input_int('id');
        authorize($c->perm($id ? 'edit' : 'create'));
        if (!$c->allowed($id ? 'edit' : 'create')) {
            abort(403);
        }
        $row = $id ? $c->find($id) : null;
        if ($id && !$row) {
            abort(404);
        }
        if (!$id && isset($c->d['prefill'])) {
            $row = ($c->d['prefill'])();
        }
        echo render('crud/form', ['c' => $c, 'row' => $row, 'id' => $id], null);
    }

    public function save(): void
    {
        $c = $this->crud();
        $id = input_int('id');
        if (!$c->allowed($id ? 'edit' : 'create')) {
            abort(403);
        }
        $newId = $c->save($id ?: null);
        json_out(['ok' => true, 'id' => $newId, 'message' => t($c->d['singular']) . ' ' . t($id ? 'updated successfully.' : 'created successfully.')]);
    }

    public function delete(): void
    {
        $c = $this->crud();
        if (!$c->allowed('delete')) {
            abort(403);
        }
        $c->delete((int)input('id'));
        json_out(['ok' => true, 'message' => t('Deleted successfully')]);
    }

    public function export(): void
    {
        $c = $this->crud();
        authorize($c->perm('view'));
        $cols = array_filter($c->d['columns'], fn($col) => ($col['type'] ?? '') !== 'image' && empty($col['noexport']));
        $rows = [];
        foreach ($c->allRows() as $r) {
            $line = [];
            foreach ($cols as $k => $_) {
                $line[] = $c->cell($k, $r, true);
            }
            $rows[] = $line;
        }
        audit('export', $c->res, null, 'Exported ' . count($rows) . ' rows');
        export_table($c->res, array_map(fn($col) => $col[0], $cols), $rows, input('format') === 'xls' ? 'xls' : 'csv');
    }

    public function print(): void
    {
        $c = $this->crud();
        authorize($c->perm('view'));
        view('crud/print', ['c' => $c, 'rows' => $c->allRows(), 'title' => t($c->d['title']), 'landscape' => count($c->d['columns']) > 6], 'print');
    }
}
