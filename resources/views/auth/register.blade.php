<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - WhatsApp Order System</title>
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
        .register-card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 450px;
        }
    </style>
</head>
<body>
    <div class="card register-card p-4">
        <div class="text-center mb-3">
            <i class="bi bi-person-plus text-success display-4"></i>
            <h4 class="fw-bold mt-2">Create an Account</h4>
            <p class="text-muted fs-7">Start managing your own WhatsApp orders & shipping labels</p>
        </div>

        @if($errors->any())
            <div class="alert alert-danger p-2 fs-7">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('register') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label class="form-label fw-semibold">Full Name</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" required autofocus placeholder="e.g. Anuj Choudhary">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email" class="form-control" value="{{ old('email') }}" required placeholder="e.g. user@example.com">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" id="regPasswordInput" name="password" class="form-control" required placeholder="Minimum 6 characters">
                    <button class="btn btn-outline-secondary" type="button" id="toggleRegPasswordBtn">
                        <i class="bi bi-eye" id="regEyeIcon"></i>
                    </button>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Confirm Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                    <input type="password" id="regPasswordConfirmInput" name="password_confirmation" class="form-control" required placeholder="Re-enter password">
                </div>
            </div>

            <button type="submit" class="btn btn-success w-100 py-2 fw-semibold mb-3">
                <i class="bi bi-person-check me-1"></i> Create Account
            </button>

            <div class="text-center fs-7 text-muted">
                Already have an account? <a href="{{ route('login') }}" class="text-success fw-bold text-decoration-none">Sign In</a>
            </div>
        </form>
    </div>

    <script>
        document.getElementById('toggleRegPasswordBtn').addEventListener('click', function() {
            const p1 = document.getElementById('regPasswordInput');
            const p2 = document.getElementById('regPasswordConfirmInput');
            const eye = document.getElementById('regEyeIcon');
            if (p1.type === 'password') {
                p1.type = 'text';
                p2.type = 'text';
                eye.classList.remove('bi-eye');
                eye.classList.add('bi-eye-slash');
            } else {
                p1.type = 'password';
                p2.type = 'password';
                eye.classList.remove('bi-eye-slash');
                eye.classList.add('bi-eye');
            }
        });
    </script>
</body>
</html>
