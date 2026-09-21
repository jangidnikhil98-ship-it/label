<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - WhatsApp Order System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #128C7E 0%, #075E54 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem 0;
        }
        .login-card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 420px;
        }
        .toggle-password {
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="card login-card p-4">
        <div class="text-center mb-4">
            <i class="bi bi-whatsapp text-success display-3"></i>
            <h4 class="fw-bold mt-2">Order & Label Manager</h4>
            <p class="text-muted fs-7">Log in to manage WhatsApp orders & shipping labels</p>
        </div>

        @if($errors->any())
            <div class="alert alert-danger p-2 fs-7">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('login') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label class="form-label fw-semibold">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" id="emailInput" name="email" class="form-control" value="{{ old('email') }}" required autofocus placeholder="Enter your email">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" id="passwordInput" name="password" class="form-control" required placeholder="Enter password">
                    <button class="btn btn-outline-secondary toggle-password" type="button" id="togglePasswordBtn" title="Toggle password visibility">
                        <i class="bi bi-eye" id="toggleEyeIcon"></i>
                    </button>
                </div>
            </div>

            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="remember" id="remember" checked>
                <label class="form-check-label fs-7" for="remember">Remember Me</label>
            </div>

            <button type="submit" class="btn btn-success w-100 py-2 fw-semibold mb-3"><i class="bi bi-box-arrow-in-right me-1"></i> Sign In</button>

            <div class="text-center fs-7 text-muted">
                Don't have an account? <a href="{{ route('register') }}" class="text-success fw-bold text-decoration-none">Sign Up / Register</a>
            </div>
        </form>
    </div>

    <script>
        document.getElementById('togglePasswordBtn').addEventListener('click', function() {
            const passwordInput = document.getElementById('passwordInput');
            const eyeIcon = document.getElementById('toggleEyeIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('bi-eye');
                eyeIcon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('bi-eye-slash');
                eyeIcon.classList.add('bi-eye');
            }
        });
    </script>
</body>
</html>
