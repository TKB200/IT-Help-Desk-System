<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/app.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'login':
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $user = authenticate($username, $password);

        if (!$user) {
            flash('error', 'Invalid username, password, or role access.');
            header('Location: index.php');
            exit;
        }

        $_SESSION['user_id'] = $user['id'];
        flash('success', 'Welcome back, ' . $user['name'] . '.');
        header('Location: dashboard.php');
        exit;

    case 'logout':
        session_unset();
        session_destroy();
        session_start();
        flash('success', 'You have signed out.');
        header('Location: index.php');
        exit;

    case 'create_ticket':
        $user = require_role(['user', 'admin']);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $priority = trim($_POST['priority'] ?? 'Medium');

        if ($title === '' || $description === '' || $location === '') {
            flash('error', 'Please complete all ticket fields.');
            header('Location: dashboard.php');
            exit;
        }

        create_ticket($user, [
            'title' => $title,
            'description' => $description,
            'location' => $location,
            'priority' => $priority,
        ]);

        flash('success', 'Ticket created successfully.');
        header('Location: dashboard.php');
        exit;

    case 'update_ticket':
        $user = require_role(['admin', 'support']);
        $ticketId = $_POST['ticket_id'] ?? '';
        $status = trim($_POST['status'] ?? 'Open');
        $priority = trim($_POST['priority'] ?? 'Medium');
        $assignedTo = trim($_POST['assigned_to'] ?? '');
        $note = trim($_POST['note'] ?? '');

        $changes = [
            'status' => in_array($status, ticket_statuses(), true) ? $status : 'Open',
            'priority' => in_array($priority, ticket_priorities(), true) ? $priority : 'Medium',
            'assigned_to' => $assignedTo !== '' ? $assignedTo : null,
        ];

        if (!update_ticket_record($ticketId, $changes, $user)) {
            flash('error', 'Ticket update failed.');
            header('Location: dashboard.php');
            exit;
        }

        if ($note !== '') {
            add_ticket_comment($ticketId, $user, $note);
        }

        flash('success', 'Ticket updated.');
        header('Location: dashboard.php');
        exit;

    case 'add_comment':
        $user = require_login();
        $ticketId = $_POST['ticket_id'] ?? '';
        $comment = trim($_POST['comment'] ?? '');

        if ($comment === '' || !add_ticket_comment($ticketId, $user, $comment)) {
            flash('error', 'Unable to add the note.');
            header('Location: dashboard.php');
            exit;
        }

        flash('success', 'Note added to ticket.');
        header('Location: dashboard.php');
        exit;

    case 'add_member':
        $admin = require_role(['admin']);
        $users = all_users();
        $name = trim($_POST['name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $role = trim($_POST['role'] ?? 'user');
        $location = trim($_POST['location'] ?? '');

        if ($name === '' || $username === '' || $password === '' || $location === '') {
            flash('error', 'Fill in all member details.');
            header('Location: dashboard.php');
            exit;
        }

        if (find_user_by_username($username)) {
            flash('error', 'That username is already in use.');
            header('Location: dashboard.php');
            exit;
        }

        if (!in_array($role, ['admin', 'support', 'user'], true)) {
            $role = 'user';
        }

        $users[] = [
            'id' => next_id('USR', $users),
            'name' => $name,
            'username' => $username,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
            'location' => $location,
            'created_at' => date(DATE_ATOM),
        ];

        save_users($users);
        add_log($admin['name'] . ' added member ' . $name);
        flash('success', 'New member added.');
        header('Location: dashboard.php');
        exit;

    case 'change_role':
        $admin = require_role(['admin']);
        $userId = $_POST['user_id'] ?? '';
        $role = trim($_POST['role'] ?? 'none');
        $users = all_users();
        $updated = false;

        foreach ($users as &$member) {
            if ($member['id'] !== $userId) {
                continue;
            }

            if ($member['id'] === $admin['id'] && $role === 'none') {
                flash('error', 'The active admin account cannot remove its own role.');
                header('Location: dashboard.php');
                exit;
            }

            $member['role'] = in_array($role, ['admin', 'support', 'user', 'none'], true) ? $role : 'user';
            $updated = true;
            break;
        }
        unset($member);

        if ($updated) {
            save_users($users);
            add_log($admin['name'] . ' changed a member role to ' . $role);
            flash('success', 'Member role updated.');
        } else {
            flash('error', 'Member not found.');
        }

        header('Location: dashboard.php');
        exit;

    case 'change_password':
        $admin = require_role(['admin']);
        $userId = $_POST['user_id'] ?? '';
        $newPassword = trim($_POST['new_password'] ?? '');
        $users = all_users();
        $updated = false;

        if (strlen($newPassword) < 6) {
            flash('error', 'Passwords should be at least 6 characters.');
            header('Location: dashboard.php');
            exit;
        }

        foreach ($users as &$member) {
            if ($member['id'] !== $userId) {
                continue;
            }

            $member['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
            $updated = true;
            break;
        }
        unset($member);

        if ($updated) {
            save_users($users);
            add_log($admin['name'] . ' changed a member password');
            flash('success', 'Password updated successfully.');
        } else {
            flash('error', 'Member not found.');
        }

        header('Location: dashboard.php');
        exit;

    case 'add_location':
        $admin = require_role(['admin']);
        $location = trim($_POST['location'] ?? '');

        if ($location === '') {
            flash('error', 'Enter a location name.');
            header('Location: dashboard.php');
            exit;
        }

        $locations = all_locations();
        $locations[] = $location;
        save_locations($locations);
        add_log($admin['name'] . ' added location ' . $location);
        flash('success', 'Location added.');
        header('Location: dashboard.php');
        exit;
}

flash('error', 'Unsupported action.');
header('Location: index.php');
exit;
