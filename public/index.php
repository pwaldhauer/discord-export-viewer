<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';

$stmt = $db->query('
    SELECT s.id, s.name, COUNT(DISTINCT c.id) as channel_count, COUNT(m.id) as message_count
    FROM servers s
    LEFT JOIN channels c ON c.server_id = s.id
    LEFT JOIN messages m ON m.channel_id = c.id
    GROUP BY s.id
    ORDER BY s.name
');
$servers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h1>Discord Message Archive</h1>

<div class="nav-links">
    <a href="calendar.php">View Calendar</a>
</div>

<div class="server-list">
    <?php foreach ($servers as $server): ?>
        <a href="channels.php?server=<?= urlencode($server['id']) ?>" class="server-card">
            <h2><?= htmlspecialchars($server['name']) ?></h2>
            <p>
                <?= number_format($server['channel_count']) ?> channels &middot;
                <?= number_format($server['message_count']) ?> messages
            </p>
        </a>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
