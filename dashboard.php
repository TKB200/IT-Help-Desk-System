<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/app.php';

$user = require_login();
$flash = get_flash();
$tickets = tickets_for_user($user);
$allUsers = all_users();
$locations = all_locations();
$logs = all_logs();
$supportMembers = array_values(array_filter($allUsers, static fn(array $member): bool => $member['role'] === 'support'));
$openCount = count(array_filter($tickets, static fn(array $ticket): bool => $ticket['status'] === 'Open'));
$progressCount = count(array_filter($tickets, static fn(array $ticket): bool => $ticket['status'] === 'In Progress'));
$resolvedCount = count(array_filter($tickets, static fn(array $ticket): bool => in_array($ticket['status'], ['Resolved', 'Closed'], true)));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | IT Help Desk</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar">
            <div>
                <p class="eyebrow">IT Help Desk</p>
                <h1>Welcome, <?= safe($user['name']) ?></h1>
                <p class="sidebar-copy"><?= safe(ucfirst($user['role'])) ?> panel for ticket tracking, support updates, and team management.</p>
            </div>

            <div class="profile-card">
                <span><?= safe($user['id']) ?></span>
                <strong><?= safe($user['location']) ?></strong>
                <small><?= safe($user['username']) ?></small>
            </div>

            <nav class="tab-nav">
                <button type="button" class="tab-link active" data-tab-target="overview">Overview</button>
                <?php if ($user['role'] === 'admin' || $user['role'] === 'user'): ?>
                    <button type="button" class="tab-link" data-tab-target="create-ticket">Create Ticket</button>
                <?php endif; ?>
                <button type="button" class="tab-link" data-tab-target="tickets">Tickets</button>
                <?php if ($user['role'] === 'admin'): ?>
                    <button type="button" class="tab-link" data-tab-target="members">Members</button>
                    <button type="button" class="tab-link" data-tab-target="locations">Locations</button>
                <?php endif; ?>
            </nav>

            <form action="actions.php" method="post">
                <input type="hidden" name="action" value="logout">
                <button type="submit" class="ghost-btn full-width">Sign Out</button>
            </form>
        </aside>

        <main class="content">
            <?php if ($flash): ?>
                <div class="flash <?= safe($flash['type']) ?>"><?= safe($flash['message']) ?></div>
            <?php endif; ?>

            <section class="tab-panel active" data-tab-panel="overview">
                <div class="panel-header">
                    <div>
                        <p class="eyebrow">Overview</p>
                        <h2>Ticket Summary</h2>
                    </div>
                </div>

                <div class="stats-grid">
                    <article class="stat-card">
                        <span>Total Tickets</span>
                        <strong><?= count($tickets) ?></strong>
                    </article>
                    <article class="stat-card">
                        <span>Open</span>
                        <strong><?= $openCount ?></strong>
                    </article>
                    <article class="stat-card">
                        <span>In Progress</span>
                        <strong><?= $progressCount ?></strong>
                    </article>
                    <article class="stat-card">
                        <span>Resolved / Closed</span>
                        <strong><?= $resolvedCount ?></strong>
                    </article>
                </div>

                <div class="two-column">
                    <section class="card">
                        <div class="section-title">
                            <h3>Recent Activity</h3>
                        </div>
                        <div class="activity-list">
                            <?php if (!$logs): ?>
                                <p class="muted">No activity yet.</p>
                            <?php else: ?>
                                <?php foreach (array_slice($logs, 0, 8) as $log): ?>
                                    <article class="activity-item">
                                        <strong><?= safe($log['message']) ?></strong>
                                        <small><?= safe(format_time($log['created_at'])) ?></small>
                                    </article>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </section>

                    <section class="card">
                        <div class="section-title">
                            <h3>Team Snapshot</h3>
                        </div>
                        <div class="mini-list">
                            <?php foreach ($allUsers as $member): ?>
                                <article class="mini-user">
                                    <div>
                                        <strong><?= safe($member['name']) ?></strong>
                                        <small><?= safe($member['location']) ?></small>
                                    </div>
                                    <span class="badge <?= strtolower($member['role']) === 'none' ? 'closed' : 'progress' ?>">
                                        <?= safe(strtoupper($member['role'])) ?>
                                    </span>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>
                </div>
            </section>

            <?php if ($user['role'] === 'admin' || $user['role'] === 'user'): ?>
                <section class="tab-panel" data-tab-panel="create-ticket">
                    <div class="panel-header">
                        <div>
                            <p class="eyebrow">New Request</p>
                            <h2>Create IT Support Ticket</h2>
                        </div>
                    </div>

                    <form action="actions.php" method="post" class="card form-grid">
                        <input type="hidden" name="action" value="create_ticket">

                        <label>
                            <span>Issue Title</span>
                            <input type="text" name="title" required placeholder="Printer offline in finance room">
                        </label>

                        <label>
                            <span>Location</span>
                            <select name="location" required>
                                <?php foreach ($locations as $location): ?>
                                    <option value="<?= safe($location) ?>" <?= $location === $user['location'] ? 'selected' : '' ?>><?= safe($location) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <label class="full-span">
                            <span>Description</span>
                            <textarea name="description" rows="5" required placeholder="Describe the issue, impact, and anything already tried."></textarea>
                        </label>

                        <label>
                            <span>Priority</span>
                            <select name="priority">
                                <?php foreach (ticket_priorities() as $priority): ?>
                                    <option value="<?= safe($priority) ?>"><?= safe($priority) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <div class="actions full-span">
                            <button type="submit" class="primary-btn">Submit Ticket</button>
                        </div>
                    </form>
                </section>
            <?php endif; ?>

            <section class="tab-panel" data-tab-panel="tickets">
                <div class="panel-header split">
                    <div>
                        <p class="eyebrow">Ticket Queue</p>
                        <h2><?= $user['role'] === 'user' ? 'My Tickets' : 'Active Support Tickets' ?></h2>
                    </div>
                    <input type="search" class="search-input" placeholder="Filter by title, ID, or status" data-ticket-search>
                </div>

                <div class="ticket-list">
                    <?php if (!$tickets): ?>
                        <article class="card">
                            <p class="muted">No tickets available yet.</p>
                        </article>
                    <?php else: ?>
                        <?php foreach ($tickets as $ticket): ?>
                            <?php
                            $creator = find_user($ticket['created_by']);
                            $assignee = $ticket['assigned_to'] ? find_user($ticket['assigned_to']) : null;
                            ?>
                            <article class="ticket-card card" data-ticket-card data-ticket-text="<?= safe(strtolower($ticket['id'] . ' ' . $ticket['title'] . ' ' . $ticket['status'] . ' ' . $ticket['priority'])) ?>">
                                <div class="ticket-head">
                                    <div>
                                        <div class="ticket-title-row">
                                            <h3><?= safe($ticket['title']) ?></h3>
                                            <span class="badge"><?= safe($ticket['id']) ?></span>
                                        </div>
                                        <p class="muted"><?= safe($ticket['description']) ?></p>
                                    </div>
                                    <div class="badge-row">
                                        <span class="badge <?= $ticket['status'] === 'Open' ? 'open' : ($ticket['status'] === 'In Progress' ? 'progress' : 'closed') ?>">
                                            <?= safe($ticket['status']) ?>
                                        </span>
                                        <span class="badge priority"><?= safe($ticket['priority']) ?></span>
                                    </div>
                                </div>

                                <div class="meta-grid">
                                    <div><strong>Requested by:</strong> <?= safe($creator['name'] ?? 'Unknown') ?></div>
                                    <div><strong>Assigned to:</strong> <?= safe($assignee['name'] ?? 'Unassigned') ?></div>
                                    <div><strong>Location:</strong> <?= safe($ticket['location']) ?></div>
                                    <div><strong>Updated:</strong> <?= safe(format_time($ticket['updated_at'])) ?></div>
                                </div>

                                <?php if ($user['role'] === 'admin' || $user['role'] === 'support'): ?>
                                    <form action="actions.php" method="post" class="form-grid compact-form">
                                        <input type="hidden" name="action" value="update_ticket">
                                        <input type="hidden" name="ticket_id" value="<?= safe($ticket['id']) ?>">

                                        <label>
                                            <span>Status</span>
                                            <select name="status">
                                                <?php foreach (ticket_statuses() as $status): ?>
                                                    <option value="<?= safe($status) ?>" <?= $ticket['status'] === $status ? 'selected' : '' ?>><?= safe($status) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>

                                        <label>
                                            <span>Priority</span>
                                            <select name="priority">
                                                <?php foreach (ticket_priorities() as $priority): ?>
                                                    <option value="<?= safe($priority) ?>" <?= $ticket['priority'] === $priority ? 'selected' : '' ?>><?= safe($priority) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>

                                        <label>
                                            <span>Assign To</span>
                                            <select name="assigned_to">
                                                <option value="">Unassigned</option>
                                                <?php foreach ($supportMembers as $member): ?>
                                                    <option value="<?= safe($member['id']) ?>" <?= $ticket['assigned_to'] === $member['id'] ? 'selected' : '' ?>><?= safe($member['name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>

                                        <label class="full-span">
                                            <span>Support Note</span>
                                            <textarea name="note" rows="3" placeholder="Add a troubleshooting update or resolution note."></textarea>
                                        </label>

                                        <div class="actions full-span">
                                            <button type="submit" class="primary-btn">Save Ticket</button>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <form action="actions.php" method="post" class="stack-form">
                                        <input type="hidden" name="action" value="add_comment">
                                        <input type="hidden" name="ticket_id" value="<?= safe($ticket['id']) ?>">
                                        <label>
                                            <span>Add More Details</span>
                                            <textarea name="comment" rows="3" placeholder="Share an update for the support team."></textarea>
                                        </label>
                                        <div class="actions">
                                            <button type="submit" class="ghost-btn">Add Note</button>
                                        </div>
                                    </form>
                                <?php endif; ?>

                                <div class="comment-list">
                                    <?php if (!$ticket['comments']): ?>
                                        <p class="muted">No notes on this ticket yet.</p>
                                    <?php else: ?>
                                        <?php foreach (array_reverse($ticket['comments']) as $comment): ?>
                                            <article class="comment-item">
                                                <div class="comment-head">
                                                    <strong><?= safe($comment['author_name']) ?></strong>
                                                    <span><?= safe(strtoupper($comment['author_role'])) ?></span>
                                                </div>
                                                <p><?= safe($comment['message']) ?></p>
                                                <small><?= safe(format_time($comment['created_at'])) ?></small>
                                            </article>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <?php if ($user['role'] === 'admin'): ?>
                <section class="tab-panel" data-tab-panel="members">
                    <div class="panel-header">
                        <div>
                            <p class="eyebrow">Admin Panel</p>
                            <h2>Members & Roles</h2>
                        </div>
                    </div>

                    <div class="two-column">
                        <form action="actions.php" method="post" class="card form-grid">
                            <input type="hidden" name="action" value="add_member">
                            <div class="section-title full-span">
                                <h3>Add Member</h3>
                            </div>

                            <label>
                                <span>Full Name</span>
                                <input type="text" name="name" required>
                            </label>

                            <label>
                                <span>Username</span>
                                <input type="text" name="username" required>
                            </label>

                            <label>
                                <span>Password</span>
                                <input type="text" name="password" required>
                            </label>

                            <label>
                                <span>Role</span>
                                <select name="role">
                                    <option value="user">User</option>
                                    <option value="support">IT Support</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </label>

                            <label class="full-span">
                                <span>Location</span>
                                <select name="location">
                                    <?php foreach ($locations as $location): ?>
                                        <option value="<?= safe($location) ?>"><?= safe($location) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>

                            <div class="actions full-span">
                                <button type="submit" class="primary-btn">Add Member</button>
                            </div>
                        </form>

                        <section class="card">
                            <div class="section-title">
                                <h3>Manage Existing Members</h3>
                            </div>

                            <div class="member-list">
                                <?php foreach ($allUsers as $member): ?>
                                    <article class="member-item">
                                        <div class="member-head">
                                            <div>
                                                <strong><?= safe($member['name']) ?></strong>
                                                <small><?= safe($member['username']) ?> • <?= safe($member['location']) ?></small>
                                            </div>
                                            <span class="badge <?= $member['role'] === 'none' ? 'closed' : 'progress' ?>"><?= safe(strtoupper($member['role'])) ?></span>
                                        </div>

                                        <form action="actions.php" method="post" class="inline-form">
                                            <input type="hidden" name="action" value="change_role">
                                            <input type="hidden" name="user_id" value="<?= safe($member['id']) ?>">
                                            <select name="role">
                                                <option value="user" <?= $member['role'] === 'user' ? 'selected' : '' ?>>User</option>
                                                <option value="support" <?= $member['role'] === 'support' ? 'selected' : '' ?>>IT Support</option>
                                                <option value="admin" <?= $member['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                                <option value="none" <?= $member['role'] === 'none' ? 'selected' : '' ?>>Remove Role</option>
                                            </select>
                                            <button type="submit" class="ghost-btn">Update Role</button>
                                        </form>

                                        <form action="actions.php" method="post" class="inline-form">
                                            <input type="hidden" name="action" value="change_password">
                                            <input type="hidden" name="user_id" value="<?= safe($member['id']) ?>">
                                            <input type="text" name="new_password" placeholder="New password" required>
                                            <button type="submit" class="ghost-btn">Change Password</button>
                                        </form>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    </div>
                </section>

                <section class="tab-panel" data-tab-panel="locations">
                    <div class="panel-header">
                        <div>
                            <p class="eyebrow">Admin Panel</p>
                            <h2>Locations</h2>
                        </div>
                    </div>

                    <div class="two-column">
                        <form action="actions.php" method="post" class="card stack-form">
                            <input type="hidden" name="action" value="add_location">
                            <div class="section-title">
                                <h3>Add Location</h3>
                            </div>
                            <label>
                                <span>Location Name</span>
                                <input type="text" name="location" placeholder="Jaffna Branch" required>
                            </label>
                            <button type="submit" class="primary-btn">Add Location</button>
                        </form>

                        <section class="card">
                            <div class="section-title">
                                <h3>Current Locations</h3>
                            </div>
                            <div class="pill-list">
                                <?php foreach ($locations as $location): ?>
                                    <span class="pill"><?= safe($location) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    </div>
                </section>
            <?php endif; ?>
        </main>
    </div>

    <script src="assets/app.js"></script>
</body>
</html>
