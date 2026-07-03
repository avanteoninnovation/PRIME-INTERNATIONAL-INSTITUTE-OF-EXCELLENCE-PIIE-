@extends('layouts.signin_page')

@section('content')
@php $demo = config('demo'); @endphp

<style>
  body { margin: 0; background: #0f2642; min-height: 100vh; }

  .login-wrapper {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: linear-gradient(135deg, #0f2642 0%, #1a3c5e 50%, #0d1f35 100%);
  }

  .login-card {
    background: #fff;
    border-radius: 20px;
    padding: 48px 44px 40px;
    width: 100%;
    max-width: 440px;
    box-shadow: 0 30px 80px rgba(0,0,0,0.4);
  }

  .login-logo {
    text-align: center;
    margin-bottom: 6px;
  }

  .login-logo .logo-circle {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    background: #1a3c5e;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 14px;
    overflow: hidden;
  }

  .login-logo .logo-circle img {
    width: 100%;
    height: 100%;
    object-fit: contain;
  }

  .login-logo h4 {
    color: #1a3c5e;
    font-weight: 700;
    font-size: 1.25rem;
    margin: 0 0 4px;
    text-transform: uppercase;
    letter-spacing: 1px;
  }

  .login-logo p {
    color: #6c8caa;
    font-size: 0.8rem;
    margin: 0 0 28px;
  }

  .login-label {
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 1.2px;
    color: #1a3c5e;
    text-transform: uppercase;
    margin-bottom: 6px;
    display: block;
  }

  .login-input {
    border: 1.5px solid #dce4ef;
    border-radius: 8px;
    padding: 11px 14px;
    width: 100%;
    font-size: 0.92rem;
    color: #1a3c5e;
    background: #f5f8fc;
    box-sizing: border-box;
    transition: border-color .2s;
    outline: none;
  }

  .login-input:focus {
    border-color: #1a3c5e;
    background: #fff;
  }

  .quick-login-label {
    text-align: center;
    font-size: 0.75rem;
    color: #6c8caa;
    margin: 18px 0 10px;
  }

  .quick-login-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    justify-content: center;
    margin-bottom: 4px;
  }

  .ql-btn {
    border: 1.5px solid #1a3c5e;
    border-radius: 20px;
    padding: 5px 16px;
    font-size: 0.78rem;
    font-weight: 600;
    cursor: pointer;
    transition: all .18s;
    background: #fff;
    color: #1a3c5e;
    letter-spacing: 0.3px;
  }

  .ql-btn:hover,
  .ql-btn.active {
    background: #1a3c5e;
    color: #fff;
  }

  .btn-signin {
    background: #1a3c5e;
    color: #fff;
    border: none;
    border-radius: 10px;
    width: 100%;
    padding: 13px;
    font-size: 1rem;
    font-weight: 700;
    letter-spacing: 0.5px;
    cursor: pointer;
    margin-top: 22px;
    transition: background .2s;
  }

  .btn-signin:hover { background: #0f2642; }

  .login-footer-links {
    text-align: center;
    margin-top: 16px;
    font-size: 0.78rem;
    color: #8a9ab0;
  }

  .login-footer-links a { color: #1a3c5e; text-decoration: none; }
  .login-footer-links a:hover { text-decoration: underline; }

  .error-msg {
    background: #fff0f0;
    border: 1px solid #f5c6cb;
    border-radius: 8px;
    padding: 10px 14px;
    font-size: 0.82rem;
    color: #c0392b;
    margin-bottom: 16px;
  }
</style>

<div class="login-wrapper">
  <div class="login-card">

    {{-- Logo --}}
    <div class="login-logo">
      <div class="logo-circle">
        <img src="{{ asset('assets/uploads/logo/'.get_settings('dark_logo')) }}" alt="Logo">
      </div>
      <h4>{{ get_settings('system_title') ?: 'PIIE' }}</h4>
      <p>{{ get_settings('school_name') ?: 'Management System' }}</p>
    </div>

    {{-- Validation errors --}}
    @if ($errors->any())
      <div class="error-msg">
        <i class="bi bi-exclamation-circle me-1"></i>
        {{ $errors->first() }}
      </div>
    @endif

    <form method="POST" action="{{ route('login') }}" id="loginForm">
      @csrf

      <div class="mb-3">
        <label class="login-label">Email</label>
        <input type="email" name="email" id="emailInput" class="login-input"
               value="{{ old('email') }}" placeholder="Enter your email" required>
      </div>

      <div class="mb-1">
        <label class="login-label">Password</label>
        <input type="password" name="password" id="passwordInput" class="login-input"
               placeholder="Enter your password" required>
      </div>

      <div class="text-end mt-1">
        <a href="{{ route('password.request') }}" style="font-size:.78rem;color:#6c8caa;">Forgot password?</a>
      </div>

      {{-- Quick Login Buttons --}}
      @if(!empty($demo['enabled']) && !empty($demo['accounts']))
      <div class="quick-login-label">Quick login as:</div>
      <div class="quick-login-grid">
        @foreach($demo['accounts'] as $i => $acc)
          <button type="button" class="ql-btn"
                  data-email="{{ $acc['email'] }}"
                  data-password="{{ $acc['password'] }}"
                  onclick="quickLogin(this)">
            {{ $acc['label'] }}
          </button>
        @endforeach
      </div>
      @endif

      <button type="submit" class="btn-signin">
        <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
      </button>
    </form>

    <div class="login-footer-links">
      <a href="{{ get_settings('help_link') }}" target="_blank">Help</a>
    </div>
  </div>
</div>

<script>
function quickLogin(btn) {
  // Deactivate all buttons
  document.querySelectorAll('.ql-btn').forEach(function(b){ b.classList.remove('active'); });
  // Activate clicked
  btn.classList.add('active');
  // Fill form fields only — user clicks Sign In themselves
  document.getElementById('emailInput').value = btn.dataset.email;
  document.getElementById('passwordInput').value = btn.dataset.password;
}
</script>
@endsection
