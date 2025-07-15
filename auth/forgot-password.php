<?php
/**
 * Forgot Password
 * Student Management System
 */

session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: ../dashboard.php');
    exit();
}

$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = clean_input($_POST['email']);
    
    if (empty($email)) {
        $error_message = 'Please enter your email address.';
    } elseif (!validate_email($email)) {
        $error_message = 'Please enter a valid email address.';
    } else {
        try {
            // Check if admin exists
            $stmt = $pdo->prepare("SELECT admin_id, username, full_name FROM admins WHERE email = ? AND status = 'active'");
            $stmt->execute([$email]);
            $admin = $stmt->fetch();
            
            if ($admin) {
                // Generate reset token
                $reset_token = bin2hex(random_bytes(32));
                $reset_expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Store reset token in database (you'd need to add a password_reset_tokens table)
                // For demo purposes, we'll just show a success message
                $success_message = 'If an account with that email exists, we have sent password reset instructions.';
                
                // Log activity
                log_activity('Password Reset Request', "Password reset requested for email: $email");
                
            } else {
                // Don't reveal if email exists or not for security
                $success_message = 'If an account with that email exists, we have sent password reset instructions.';
            }
            
        } catch (Exception $e) {
            $error_message = 'An error occurred. Please try again later.';
            error_log("Password reset error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary: #0f172a;
            --secondary: #1e293b;
            --accent: #3b82f6;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --background: #f8fafc;
            --card: #ffffff;
            --border: #e2e8f0;
            --text: #0f172a;
            --muted: #64748b;
        }

        body {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .forgot-container {
            background: var(--card);
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            width: 100%;
            max-width: 400px;
        }

        .forgot-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .forgot-header h1 {
            color: var(--primary);
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .forgot-header p {
            color: var(--muted);
            font-size: 0.9rem;
        }

        .form-label {
            color: var(--text);
            font-weight: 500;
        }

        .form-control {
            border: 2px solid var(--border);
            border-radius: 8px;
            padding: 0.75rem;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .btn-primary {
            background: var(--accent);
            border: none;
            border-radius: 8px;
            padding: 0.75rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }

        .alert {
            border: none;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        .input-group-text {
            background: var(--background);
            border: 2px solid var(--border);
            border-right: none;
            color: var(--muted);
        }

        .input-group .form-control {
            border-left: none;
        }

        .back-link {
            text-align: center;
            margin-top: 2rem;
        }

        .back-link a {
            color: var(--accent);
            text-decoration: none;
            font-size: 0.9rem;
        }

        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="forgot-container">
        <div class="forgot-header">
            <h1><i class="bi bi-key-fill"></i> Reset Password</h1>
            <p>Enter your email to receive reset instructions</p>
        </div>

        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill"></i>
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (!$success_message): ?>
        <form method="POST" action="">
            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-envelope-fill"></i>
                    </span>
                    <input type="email" class="form-control" id="email" name="email" 
                           placeholder="Enter your email address" required 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-send-fill"></i> Send Reset Instructions
                </button>
            </div>
        </form>
        <?php endif; ?>

        <div class="back-link">
            <a href="login.php">
                <i class="bi bi-arrow-left"></i> Back to Login
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>