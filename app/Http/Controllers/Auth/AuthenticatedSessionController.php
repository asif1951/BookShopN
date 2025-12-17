<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(Request $request): Response
    {
        // Store the previous URL in session if not coming from auth pages
        $previousUrl = url()->previous();
        $currentUrl = url()->current();
        
        // Only store if it's not a login/register page
        if (!str_contains($previousUrl, '/login') && 
            !str_contains($previousUrl, '/register') &&
            $previousUrl !== $currentUrl) {
            session(['previous_url' => $previousUrl]);
        }
        
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
            'previousUrl' => session('previous_url', '/')
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        // Get redirect URL from request or session
        $redirectTo = $request->input('redirect_to');
        
        if (!$redirectTo) {
            $redirectTo = session('previous_url', RouteServiceProvider::HOME);
        }
        
        // Clear the previous URL from session
        session()->forget('previous_url');
        
        // Validate the redirect URL
        if ($redirectTo && $redirectTo !== url()->current() && 
            !str_contains($redirectTo, '/login') && 
            !str_contains($redirectTo, '/register') &&
            !str_contains($redirectTo, '/password-reset')) {
            return redirect()->to($redirectTo);
        }

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Store current URL before logout for potential redirect back
        $currentUrl = url()->current();
        
        // Check if current page is dashboard or admin page
        $isDashboard = str_contains($currentUrl, '/dashboard') || 
                       str_contains($currentUrl, '/admin');
        
        // Store the URL to redirect after logout (not dashboard)
        if (!$isDashboard && 
            !str_contains($currentUrl, '/login') && 
            !str_contains($currentUrl, '/register')) {
            session(['after_logout_url' => $currentUrl]);
        } else {
            // If on dashboard, redirect to home or previous page
            $previousUrl = url()->previous();
            if ($previousUrl && $previousUrl !== $currentUrl &&
                !str_contains($previousUrl, '/dashboard') &&
                !str_contains($previousUrl, '/admin')) {
                session(['after_logout_url' => $previousUrl]);
            } else {
                session(['after_logout_url' => '/']);
            }
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Redirect to stored URL or home
        $redirectUrl = session('after_logout_url', '/');
        session()->forget('after_logout_url');
        
        return redirect()->to($redirectUrl);
    }
}