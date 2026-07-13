<?php

namespace Webkul\Admin\Http\Controllers\Settings;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\User\Repositories\UserRepository;

/**
 * Permite que um usuário com acesso total (permission_type = 'all', o
 * perfil Super Admin/TI) entre temporariamente como outro usuário, para dar
 * suporte ou verificar o que aquele perfil enxerga no sistema.
 *
 * Toda a sessão de simulação é registrada em audit_logs (ação
 * 'impersonate_start' / 'impersonate_stop'), com o usuário real e o
 * usuário simulado.
 */
class ImpersonateController extends Controller
{
    public function __construct(protected UserRepository $userRepository) {}

    /**
     * Lista enxuta de usuários (id, nome, status) para o modal "Simular
     * Usuário" — não usa o DataGrid completo, só o essencial pra escolher
     * quem simular. Só acessível pra quem já tem acesso total.
     */
    public function list(): JsonResponse
    {
        $actingUser = auth()->guard('user')->user();

        if (! $actingUser->role || $actingUser->role->permission_type !== 'all') {
            abort(401, 'This action is unauthorized');
        }

        $users = $this->userRepository
            ->all(['id', 'name', 'status'])
            ->reject(fn ($user) => $user->id == $actingUser->id)
            ->values();

        return new JsonResponse(['data' => $users]);
    }

    /**
     * Começa a simular o usuário informado.
     */
    public function start(int $id): RedirectResponse
    {
        $actingUser = auth()->guard('user')->user();

        if (! $actingUser->role || $actingUser->role->permission_type !== 'all') {
            abort(401, 'This action is unauthorized');
        }

        if (session()->has('impersonator_id')) {
            session()->flash('error', 'Você já está simulando um usuário. Volte ao seu usuário original antes de simular outro.');

            return redirect()->back();
        }

        if ($actingUser->id == $id) {
            session()->flash('error', 'Você não pode simular a si mesmo.');

            return redirect()->back();
        }

        $target = $this->userRepository->findOrFail($id);

        /**
         * Se a simulação começou pela tela de Usuários (Configurações),
         * ao encerrar volta pra lá. Se começou pelo atalho da navbar
         * (modal rápido, disponível em qualquer tela), ao encerrar
         * permanece na mesma tela onde estava.
         */
        $referer = request()->headers->get('referer', '');

        $origin = str_contains($referer, '/admin/settings/users') ? 'users_page' : 'navbar';

        session([
            'impersonator_id' => $actingUser->id,
            'impersonator_name' => $actingUser->name,
            'impersonator_origin' => $origin,
            'impersonator_return_url' => $origin === 'navbar' ? $referer : null,
        ]);

        $this->logImpersonation('impersonate_start', $actingUser, $target);

        auth()->guard('user')->loginUsingId($target->id);

        session()->flash('success', 'Agora você está navegando como '.$target->name.'.');

        return redirect()->route('admin.dashboard.index');
    }

    /**
     * Encerra a simulação e volta para o usuário original.
     */
    public function stop(): RedirectResponse
    {
        $impersonatorId = session('impersonator_id');

        if (! $impersonatorId) {
            return redirect()->route('admin.dashboard.index');
        }

        $target = auth()->guard('user')->user();

        $original = $this->userRepository->findOrFail($impersonatorId);

        $this->logImpersonation('impersonate_stop', $original, $target);

        $origin = session('impersonator_origin', 'users_page');

        $returnUrl = session('impersonator_return_url');

        session()->forget(['impersonator_id', 'impersonator_name', 'impersonator_origin', 'impersonator_return_url']);

        auth()->guard('user')->loginUsingId($original->id);

        session()->flash('success', 'Você voltou para o seu usuário.');

        if ($origin === 'navbar' && $returnUrl) {
            return redirect()->to($returnUrl);
        }

        return redirect()->route('admin.settings.users.index');
    }

    /**
     * A tabela audit_logs tem `action` como ENUM('insert','update','delete')
     * e não tem coluna `updated_at` (só `created_at`), então a simulação é
     * registrada como 'update', com a direção (início/fim) descrita em
     * `field_changes`.
     */
    private function logImpersonation(string $direction, $actingUser, $targetUser): void
    {
        \DB::table('audit_logs')->insert([
            'model_type' => 'Impersonation',
            'model_id' => $targetUser->id,
            'model_label' => $targetUser->name.' ('.$targetUser->email.')',
            'action' => 'update',
            'user_id' => $actingUser->id,
            'user_name' => $actingUser->name,
            'field_changes' => json_encode(['direction' => $direction]),
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }
}
