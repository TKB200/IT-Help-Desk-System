<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/app.php';

if (current_user()) {
    header('Location: dashboard.php');
    exit;
}

$flash = get_flash();
$users = all_users();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Help Desk</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="login-page">
    <main class="login-shell">
        <section class="hero-card">
            <p class="eyebrow">IT Support Portal</p>
            <h1>Simple IT Help Desk & Ticketing System</h1>
            <p class="lead">Give users a clean way to submit incidents, let the IT support team manage the queue, and keep admin tools for members, roles, passwords, and locations in one place.</p>

            <div class="demo-card">
                <h2>Demo Accounts</h2>
                <div class="demo-grid">
                    <?php foreach ($users as $user): ?>
                        <?php
                        $passwordHint = $user['role'] === 'admin' ? 'admin123' : ($user['role'] === 'support' ? 'support123' : 'user123');
                        ?>
                        <div class="demo-user">
                            <strong><?= safe($user['name']) ?></strong>
                            <span><?= safe(strtoupper($user['role'])) ?></span>
                            <small><?= safe($user['username']) ?> / <?= safe($passwordHint) ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="panel-card">
            <h2>Sign In</h2>
            <?php if ($flash): ?>
                <div class="flash <?= safe($flash['type']) ?>"><?= safe($flash['message']) ?></div>
            <?php endif; ?>

            <form action="actions.php" method="post" class="stack-form">
                <input type="hidden" name="action" value="login">

                <label>
                    <span>Username</span>
                    <input type="text" name="username" placeholder="Enter the username" required>
                </label>

                <label>
                    <span>Password</span>
                    <input type="password" name="password" placeholder="Please Enter your password" required>
                </label>

                <button type="submit" class="primary-btn">Open Dashboard</button>
            </form>
        </section>
    </main>
</body>
</html>
