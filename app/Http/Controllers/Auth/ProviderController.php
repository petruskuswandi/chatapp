<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;

class ProviderController extends Controller
{
    public function redirect($provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    public function callback($provider)
    {
        try {
            $SocialUser = Socialite::driver($provider)->user();
            if (User::where('email', $SocialUser->getEmail())->exists()) {
                return redirect('login')->withErrors(['email' => 'This email uses different method to login']);
            }
            $user = User::where([
                'provider' => $provider,
                'provider_id' => $SocialUser->id,
            ])->first();

            if (!$user) {
                $password = Str::random(12);
                $user = User::create([
                    'name' => $SocialUser->getName(),
                    'username' => User::generateUsername($SocialUser->getNickname()),
                    'email' => $SocialUser->getEmail(),
                    'provider' => $provider,
                    'provider_id' => $SocialUser->getId(),
                    'provider_token' => $SocialUser->token,
                    'password' => $password,
                ]);
                $user->sendEmailVerificationNotification();
                $user->update([
                    'password' => Hash::make($password)
                ]);
            }

            Auth::login($user);

            return redirect('/dashboard');
        } catch (\Exception $e) {
            return redirect('/login');
        }
    }
}
