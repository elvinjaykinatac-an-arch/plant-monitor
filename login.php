<?php
require 'config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, username, password FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plant Monitor — Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'DM Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            background: #0d1f0f;
            overflow: hidden;
        }
        .bg-art {
            position: fixed;
            inset: 0;
            background: 
                radial-gradient(ellipse 80% 60% at 20% 50%, rgba(34,197,94,0.08) 0%, transparent 60%),
                radial-gradient(ellipse 60% 80% at 80% 80%, rgba(16,185,129,0.06) 0%, transparent 60%),
                #0d1f0f;
            z-index: 0;
        }
        .leaf-pattern {
            position: fixed;
            inset: 0;
            background-image: radial-gradient(circle at 1px 1px, rgba(34,197,94,0.06) 1px, transparent 0);
            background-size: 40px 40px;
            z-index: 0;
        }
        .container {
            position: relative;
            z-index: 1;
            display: flex;
            width: 100%;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .card {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(34,197,94,0.2);
            border-radius: 20px;
            padding: 3rem 2.5rem;
            width: 100%;
            max-width: 420px;
            backdrop-filter: blur(20px);
            box-shadow: 0 40px 80px rgba(0,0,0,0.4);
        }
        .logo { text-align: center; margin-bottom: 2.5rem; }
        .logo-icon {
            width: 64px; height: 64px;
            background: linear-gradient(135deg, #16a34a, #4ade80);
            border-radius: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin-bottom: 1rem;
            box-shadow: 0 8px 32px rgba(74,222,128,0.3);
        }
        .logo h1 {
            font-family: 'Playfair Display', serif;
            font-size: 1.6rem;
            color: #f0fdf4;
        }
        .logo p {
            color: rgba(255,255,255,0.4);
            font-size: 0.8rem;
            margin-top: 0.3rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }
        .form-group { margin-bottom: 1.2rem; }
        label {
            display: block;
            font-size: 0.8rem;
            font-weight: 500;
            color: rgba(255,255,255,0.5);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 0.5rem;
        }
        input {
            width: 100%;
            padding: 0.85rem 1rem;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            color: #f0fdf4;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.95rem;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }
        input:focus {
            border-color: rgba(74,222,128,0.5);
            box-shadow: 0 0 0 3px rgba(74,222,128,0.1);
        }
        input::placeholder { color: rgba(255,255,255,0.2); }
        .error {
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.3);
            color: #fca5a5;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 1.2rem;
        }
        button {
            width: 100%;
            padding: 0.9rem;
            background: linear-gradient(135deg, #16a34a, #4ade80);
            border: none;
            border-radius: 10px;
            color: #052e16;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.2s, transform 0.1s;
            margin-top: 0.5rem;
        }
        button:hover { opacity: 0.9; transform: translateY(-1px); }
        button:active { transform: translateY(0); }
    </style>
</head>
<body>
    <div class="bg-art"></div>
    <div class="leaf-pattern"></div>
    <div class="container">
        <div class="card">
            <div class="logo">
                <div class="logo-icon">🌿</div>
                <h1>Plant Monitor</h1>
                <p>Automated Watering System</p>
            </div>

            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" placeholder="Enter username"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Enter password" required>
                </div>
                <button type="submit">Sign In →</button>
            </form>
        </div>
    </div>
</body>
</html>