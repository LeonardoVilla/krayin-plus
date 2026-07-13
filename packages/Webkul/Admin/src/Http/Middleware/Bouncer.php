<?php

namespace Webkul\Admin\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class Bouncer
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, \Closure $next, $guard = 'user')
    {
        if (! auth()->guard($guard)->check()) {
            return redirect()->route('admin.session.create');
        }

        /**
         * Durante uma simulação de usuário (impersonation), a sessão é
         * só-leitura — não importa o que o papel do usuário simulado
         * normalmente permitiria. Bloqueia qualquer requisição que não
         * seja GET/HEAD, com a única exceção da própria rota de encerrar
         * a simulação (senão ninguém consegue voltar pro usuário real).
         */
        if (
            session()->has('impersonator_id')
            && ! in_array($request->method(), ['GET', 'HEAD'])
            && Route::currentRouteName() !== 'admin.settings.users.impersonate.stop'
        ) {
            session()->flash('error', 'Durante uma simulação de usuário, o acesso é somente leitura — não é possível criar, editar ou excluir nada.');

            return redirect()->back();
        }

        /**
         * If user status is changed by admin. Then session should be
         * logged out.
         */
        if (! (bool) auth()->guard($guard)->user()->status) {
            auth()->guard($guard)->logout();

            session()->flash('error', trans('admin::app.errors.401'));

            return redirect()->route('admin.session.create');
        }

        /**
         * If somehow the user deleted all permissions, then it should be
         * auto logged out and need to contact the administrator again.
         */
        if ($this->isPermissionsEmpty()) {
            auth()->guard($guard)->logout();

            session()->flash('error', trans('admin::app.errors.401'));

            return redirect()->route('admin.session.create');
        }

        return $next($request);
    }

    /**
     * Check for user, if they have empty permissions or not except admin.
     *
     * @return bool
     */
    public function isPermissionsEmpty()
    {
        if (! $role = auth()->guard('user')->user()->role) {
            abort(401, 'This action is unauthorized.');
        }

        if ($role->permission_type === 'all') {
            return false;
        }

        if ($role->permission_type !== 'all' && empty($role->permissions)) {
            return true;
        }

        $this->checkIfAuthorized();

        return false;
    }

    /**
     * Check authorization.
     *
     * @return null
     */
    public function checkIfAuthorized()
    {
        $roles = acl()->getRoles();

        if (isset($roles[Route::currentRouteName()])) {
            bouncer()->allow($roles[Route::currentRouteName()]);
        }
    }
}
