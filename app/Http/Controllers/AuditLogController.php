<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::query();

        if ($request->filled('model')) {
            $query->where('model_type', $request->get('model'));
        }
        if ($request->filled('audit_action') && in_array($request->get('audit_action'), ['insert', 'update', 'delete'], true)) {
            $query->where('action', $request->get('audit_action'));
        }
        if ($request->filled('user')) {
            $query->where('user_name', 'like', '%' . $request->get('user') . '%');
        }

        $total = $query->count();

        $perPage = 50;
        $page = max(1, (int) $request->get('page', 1));

        $rows = $query->orderByDesc('id')
            ->forPage($page, $perPage)
            ->get();

        $modelList = AuditLog::query()->select('model_type')->distinct()->orderBy('model_type')->pluck('model_type');

        $actionLabels = ['insert' => 'Criação', 'update' => 'Atualização', 'delete' => 'Exclusão'];
        $actionColors = ['insert' => '#2e7d32', 'update' => '#ef6c00', 'delete' => '#c62828'];

        $html = $this->render($rows, $modelList, $actionLabels, $actionColors, $total, $page, $perPage, $request);

        return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    private function render($rows, $modelList, $actionLabels, $actionColors, $total, $page, $perPage, Request $request): string
    {
        $h = fn ($s) => htmlspecialchars(is_scalar($s) || $s === null ? (string) $s : json_encode($s), ENT_QUOTES, 'UTF-8');
        $module = $h($request->get('model', ''));
        $action = $h($request->get('audit_action', ''));
        $userName = $h($request->get('user', ''));

        $moduleOptions = '<option value="">Todos</option>';
        foreach ($modelList as $m) {
            $short = class_basename($m);
            $sel = $m === $request->get('model') ? 'selected' : '';
            $moduleOptions .= "<option value=\"{$h($m)}\" {$sel}>{$h($short)}</option>";
        }

        $actionOptions = '<option value="">Todas</option>';
        foreach ($actionLabels as $key => $label) {
            $sel = $key === $request->get('audit_action') ? 'selected' : '';
            $actionOptions .= "<option value=\"{$h($key)}\" {$sel}>{$h($label)}</option>";
        }

        $rowsHtml = '';
        foreach ($rows as $row) {
            $changesHtml = '&mdash;';
            if (!empty($row->field_changes)) {
                $decoded = json_decode($row->field_changes, true);
                $pre = '';
                if (is_array($decoded)) {
                    foreach ($decoded as $field => $vals) {
                        $pre .= $h($field) . ': ' . $h($vals['old'] ?? '') . ' -> ' . $h($vals['new'] ?? '') . "\n";
                    }
                } else {
                    $pre = $h($row->field_changes);
                }
                $changesHtml = "<details><summary>ver alterações</summary><pre>{$pre}</pre></details>";
            }

            $color = $actionColors[$row->action] ?? '#999';
            $label = $actionLabels[$row->action] ?? $row->action;
            $shortModel = class_basename($row->model_type);

            $rowsHtml .= '<tr>'
                . '<td>' . $h($row->created_at) . '</td>'
                . '<td>' . $h($row->user_name) . '</td>'
                . "<td><span class=\"badge\" style=\"background:{$color}\">" . $h($label) . '</span></td>'
                . '<td>' . $h($shortModel) . '</td>'
                . '<td>' . $h($row->model_label) . '<br><small style="color:#999;">' . $h($row->model_id) . '</small></td>'
                . '<td>' . $changesHtml . '</td>'
                . '<td>' . $h($row->ip_address) . '</td>'
                . '</tr>';
        }

        if ($rows->isEmpty()) {
            $rowsHtml = '<tr><td colspan="7">Nenhum registro encontrado.</td></tr>';
        }

        $totalPages = max(1, (int) ceil($total / $perPage));
        $qs = "model={$module}&audit_action={$action}&user={$userName}";
        $prevLink = $page > 1 ? "<a href=\"?{$qs}&page=" . ($page - 1) . '">&laquo; Anterior</a>' : '';
        $nextLink = ($page * $perPage) < $total ? "<a href=\"?{$qs}&page=" . ($page + 1) . '">Próxima &raquo;</a>' : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Log de Auditoria - Krayin</title>
<style>
    body { font-family: Arial, Helvetica, sans-serif; margin: 20px; background: #f5f5f5; color: #333; }
    h1 { font-size: 20px; }
    form.filters { background: #fff; padding: 12px; border-radius: 4px; margin-bottom: 16px; display: flex; gap: 10px; flex-wrap: wrap; align-items: end; }
    form.filters label { display: flex; flex-direction: column; font-size: 12px; color: #666; }
    form.filters input, form.filters select { padding: 6px; margin-top: 4px; }
    form.filters button { padding: 7px 16px; background: #443dff; color: #fff; border: none; border-radius: 3px; cursor: pointer; }
    table { width: 100%; border-collapse: collapse; background: #fff; }
    th, td { padding: 8px 10px; border-bottom: 1px solid #eee; font-size: 13px; text-align: left; vertical-align: top; }
    th { background: #1a1c26; color: #fff; }
    .badge { padding: 2px 8px; border-radius: 3px; color: #fff; font-size: 11px; font-weight: bold; }
    details summary { cursor: pointer; color: #1565c0; }
    pre { white-space: pre-wrap; word-break: break-all; font-size: 11px; margin: 4px 0 0 0; }
    .pagination { margin-top: 14px; }
    .pagination a { margin-right: 8px; text-decoration: none; color: #1565c0; }
    .total { color: #666; font-size: 13px; margin-bottom: 8px; }
    a.back { display:inline-block; margin-bottom: 12px; color: #443dff; text-decoration: none; }
</style>
</head>
<body>
<a class="back" href="/admin/dashboard">&laquo; Voltar ao painel</a>
<h1>Log de Auditoria — quem criou, alterou ou excluiu registros</h1>

<form class="filters" method="get">
    <label>Módulo
        <select name="model">{$moduleOptions}</select>
    </label>
    <label>Ação
        <select name="audit_action">{$actionOptions}</select>
    </label>
    <label>Usuário
        <input type="text" name="user" value="{$userName}" placeholder="nome do usuário">
    </label>
    <button type="submit">Filtrar</button>
</form>

<div class="total">{$total} registro(s) encontrado(s)</div>

<table>
<thead>
<tr>
    <th>Data/Hora</th>
    <th>Usuário</th>
    <th>Ação</th>
    <th>Módulo</th>
    <th>Registro</th>
    <th>Alterações</th>
    <th>IP</th>
</tr>
</thead>
<tbody>
{$rowsHtml}
</tbody>
</table>

<div class="pagination">
    {$prevLink}
    <span>Página {$page} de {$totalPages}</span>
    {$nextLink}
</div>

</body>
</html>
HTML;
    }
}
