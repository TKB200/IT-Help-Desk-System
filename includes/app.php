<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

const DATA_DIR = __DIR__ . '/../data';

function seed_data(): void
{
    if (!is_dir(DATA_DIR)) {
        mkdir(DATA_DIR, 0777, true);
    }

    $usersFile = DATA_DIR . '/users.json';
    if (!file_exists($usersFile)) {
        $users = [
            [
                'id' => 'USR-1001',
                'name' => 'System Admin',
                'username' => 'admin',
                'password' => password_hash('admin123', PASSWORD_DEFAULT),
                'role' => 'admin',
                'location' => 'Head Office',
                'created_at' => date(DATE_ATOM),
            ],
            [
                'id' => 'USR-1002',
                'name' => 'Nimal Perera',
                'username' => 'user1',
                'password' => password_hash('user123', PASSWORD_DEFAULT),
                'role' => 'user',
                'location' => 'Colombo',
                'created_at' => date(DATE_ATOM),
            ],
            [
                'id' => 'USR-1003',
                'name' => 'Ayesha Silva',
                'username' => 'user2',
                'password' => password_hash('user123', PASSWORD_DEFAULT),
                'role' => 'user',
                'location' => 'Kandy',
                'created_at' => date(DATE_ATOM),
            ],
            [
                'id' => 'USR-1004',
                'name' => 'Kasun Fernando',
                'username' => 'support1',
                'password' => password_hash('support123', PASSWORD_DEFAULT),
                'role' => 'support',
                'location' => 'Head Office',
                'created_at' => date(DATE_ATOM),
            ],
            [
                'id' => 'USR-1005',
                'name' => 'Dinithi Jayasinghe',
                'username' => 'support2',
                'password' => password_hash('support123', PASSWORD_DEFAULT),
                'role' => 'support',
                'location' => 'Galle',
                'created_at' => date(DATE_ATOM),
            ],
        ];

        write_json($usersFile, $users);
    }

    $locationsFile = DATA_DIR . '/locations.json';
    if (!file_exists($locationsFile)) {
        write_json($locationsFile, ['Head Office', 'Colombo', 'Kandy', 'Galle']);
    }

    foreach (['tickets.json' => [], 'logs.json' => []] as $file => $payload) {
        $fullPath = DATA_DIR . '/' . $file;
        if (!file_exists($fullPath)) {
            write_json($fullPath, $payload);
        }
    }
}

function read_json(string $file): array
{
    if (!file_exists($file)) {
        return [];
    }

    $content = file_get_contents($file);
    if ($content === false || trim($content) === '') {
        return [];
    }

    $decoded = json_decode($content, true);
    return is_array($decoded) ? $decoded : [];
}

function write_json(string $file, array $data): void
{
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function all_users(): array
{
    return read_json(DATA_DIR . '/users.json');
}

function save_users(array $users): void
{
    write_json(DATA_DIR . '/users.json', array_values($users));
}

function all_locations(): array
{
    return read_json(DATA_DIR . '/locations.json');
}

function save_locations(array $locations): void
{
    $clean = array_values(array_unique(array_filter(array_map('trim', $locations))));
    sort($clean);
    write_json(DATA_DIR . '/locations.json', $clean);
}

function all_tickets(): array
{
    return read_json(DATA_DIR . '/tickets.json');
}

function save_tickets(array $tickets): void
{
    write_json(DATA_DIR . '/tickets.json', array_values($tickets));
}

function all_logs(): array
{
    return read_json(DATA_DIR . '/logs.json');
}

function add_log(string $message): void
{
    $logs = all_logs();
    array_unshift($logs, [
        'id' => uniqid('LOG-', true),
        'message' => $message,
        'created_at' => date(DATE_ATOM),
    ]);
    write_json(DATA_DIR . '/logs.json', array_slice($logs, 0, 50));
}

function find_user(string $id): ?array
{
    foreach (all_users() as $user) {
        if ($user['id'] === $id) {
            return $user;
        }
    }

    return null;
}

function find_user_by_username(string $username): ?array
{
    foreach (all_users() as $user) {
        if (strcasecmp($user['username'], $username) === 0) {
            return $user;
        }
    }

    return null;
}

function current_user(): ?array
{
    $id = $_SESSION['user_id'] ?? null;
    return $id ? find_user($id) : null;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        flash('error', 'Please sign in first.');
        header('Location: index.php');
        exit;
    }

    return $user;
}

function require_role(array $roles): array
{
    $user = require_login();
    if (!in_array($user['role'], $roles, true)) {
        flash('error', 'You do not have access to that action.');
        header('Location: dashboard.php');
        exit;
    }

    return $user;
}

function authenticate(string $username, string $password): ?array
{
    $user = find_user_by_username($username);
    if (!$user || $user['role'] === 'none') {
        return null;
    }

    return password_verify($password, $user['password']) ? $user : null;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function next_id(string $prefix, array $records): string
{
    $max = 1000;
    foreach ($records as $record) {
        if (!isset($record['id'])) {
            continue;
        }

        if (preg_match('/(\d+)$/', (string) $record['id'], $matches)) {
            $max = max($max, (int) $matches[1]);
        }
    }

    return sprintf('%s-%04d', $prefix, $max + 1);
}

function ticket_statuses(): array
{
    return ['Open', 'In Progress', 'Resolved', 'Closed'];
}

function ticket_priorities(): array
{
    return ['Low', 'Medium', 'High', 'Critical'];
}

function create_ticket(array $user, array $input): void
{
    $tickets = all_tickets();
    $ticket = [
        'id' => next_id('TKT', $tickets),
        'title' => trim($input['title']),
        'description' => trim($input['description']),
        'location' => trim($input['location']),
        'priority' => trim($input['priority']),
        'status' => 'Open',
        'created_by' => $user['id'],
        'assigned_to' => null,
        'comments' => [],
        'created_at' => date(DATE_ATOM),
        'updated_at' => date(DATE_ATOM),
    ];
    $tickets[] = $ticket;
    save_tickets($tickets);
    add_log($user['name'] . ' created ticket ' . $ticket['id']);
}

function add_ticket_comment(string $ticketId, array $author, string $comment): bool
{
    $tickets = all_tickets();
    $updated = false;

    foreach ($tickets as &$ticket) {
        if ($ticket['id'] !== $ticketId) {
            continue;
        }

        $ticket['comments'][] = [
            'author_id' => $author['id'],
            'author_name' => $author['name'],
            'author_role' => $author['role'],
            'message' => trim($comment),
            'created_at' => date(DATE_ATOM),
        ];
        $ticket['updated_at'] = date(DATE_ATOM);
        $updated = true;
        break;
    }
    unset($ticket);

    if ($updated) {
        save_tickets($tickets);
        add_log($author['name'] . ' added a note to ' . $ticketId);
    }

    return $updated;
}

function update_ticket_record(string $ticketId, array $changes, array $actor): bool
{
    $tickets = all_tickets();
    $updated = false;

    foreach ($tickets as &$ticket) {
        if ($ticket['id'] !== $ticketId) {
            continue;
        }

        foreach (['status', 'assigned_to', 'priority'] as $field) {
            if (array_key_exists($field, $changes)) {
                $ticket[$field] = $changes[$field];
            }
        }

        $ticket['updated_at'] = date(DATE_ATOM);
        $updated = true;
        break;
    }
    unset($ticket);

    if ($updated) {
        save_tickets($tickets);
        add_log($actor['name'] . ' updated ' . $ticketId);
    }

    return $updated;
}

function tickets_for_user(array $user): array
{
    $tickets = all_tickets();
    usort($tickets, static fn(array $a, array $b): int => strcmp($b['updated_at'], $a['updated_at']));

    if ($user['role'] === 'admin' || $user['role'] === 'support') {
        return $tickets;
    }

    return array_values(array_filter(
        $tickets,
        static fn(array $ticket): bool => $ticket['created_by'] === $user['id']
    ));
}

function safe(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function format_time(string $value): string
{
    $timestamp = strtotime($value);
    return $timestamp ? date('M d, Y h:i A', $timestamp) : $value;
}

seed_data();
