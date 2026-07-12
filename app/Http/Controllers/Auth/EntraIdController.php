<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Webkul\User\Models\User;

class EntraIdController
{
    public function redirect(): SymfonyRedirectResponse
    {
        return Socialite::driver('azure')->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            /** @var SocialiteUser $entraUser */
            $entraUser = Socialite::driver('azure')->user();
        } catch (InvalidStateException $e) {
            // Requisição duplicada/repetida do callback (comum com pré-carregamento de
            // link do navegador, F5 ou voltar depois que o login já foi concluído com
            // sucesso). Se já existe uma sessão autenticada, é exatamente esse caso —
            // manda direto pro painel em vez de mostrar um erro. Caso contrário, pede
            // pra tentar de novo.
            if (Auth::guard('user')->check()) {
                return redirect()->route('admin.dashboard.index');
            }

            return redirect()
                ->route('admin.session.create')
                ->with('error', 'A sessão de login expirou ou a página foi recarregada. Tente entrar com a Microsoft novamente.');
        }

        $user = User::firstWhere('entra_id', $entraUser->getId())
            ?? User::firstWhere('email', $entraUser->getEmail());

        if ($user) {
            $user->forceFill([
                'entra_id' => $entraUser->getId(),
            ])->save();
        } else {
            $user = User::create([
                'entra_id' => $entraUser->getId(),
                'name' => $entraUser->getName() ?? $entraUser->getNickname() ?? $entraUser->getEmail(),
                'email' => $entraUser->getEmail(),
                'password' => null,
                'status' => 1,
                // Único papel existente na instalação padrão do Krayin. Antes de ir para
                // produção, criar um papel com permissões mais restritas para novos usuários.
                'role_id' => 1,
            ]);
        }

        Auth::guard('user')->login($user, remember: true);

        return redirect()->intended(route('admin.dashboard.index'));
    }
}
