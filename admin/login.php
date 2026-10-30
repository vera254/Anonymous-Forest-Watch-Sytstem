<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Forest Watch</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #1b4332 0%, #2d6a4f 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }
        .btn-login {
            background: #2d6a4f;
            color: white;
            width: 100%;
            padding: 12px;
            font-weight: bold;
            border: none;
            border-radius: 8px;
        }
        .btn-login:hover { background: #1b4332; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="text-center mb-4">
            <i class="fas fa-tree fa-3x" style="color: #2d6a4f;"></i>
            <h2 class="mt-2">Forest Watch</h2>
            <p class="text-muted">Admin Portal</p>
        </div>
        
        <?php if(isset($_GET['error'])): ?>
            <div class="alert alert-danger text-center">
                <i class="fas fa-exclamation-triangle me-2"></i>Invalid username or password
            </div>
        <?php endif; ?>
        
        <form method="POST" action="authenticate.php">
            <div class="mb-3">
              <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required autocomplete="off">
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt me-2"></i>Login
            </button>
        </form>
        
        <hr class="my-4">
            </div>
</body>
</html>    