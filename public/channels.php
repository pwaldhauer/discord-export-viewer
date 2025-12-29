<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';

$serverId = $_GET['server'] ?? '';

$stmtServer = $db->prepare('SELECT * FROM servers WHERE id = ?');
$stmtServer->execute([$serverId]);
$server = $stmtServer->fetch(PDO::FETCH_ASSOC);

if (!$server) {
    echo '<p>Server not found.</p>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$stmt = $db->prepare('
    SELECT c.id, c.name, c.type, COUNT(m.id) as message_count,
           MIN(m.timestamp) as first_message,
           MAX(m.timestamp) as last_message
    FROM channels c
    LEFT JOIN messages m ON m.channel_id = c.id
    WHERE c.server_id = ?
    GROUP BY c.id
    ORDER BY c.name
');
$stmt->execute([$serverId]);
$channels = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="breadcrumb">
    <a href="index.php">Servers</a>
    <span> / </span>
    <strong><?= htmlspecialchars($server['name']) ?></strong>
</div>

<h1><?= htmlspecialchars($server['name']) ?></h1>
<p class="stats"><?= count($channels) ?> channels</p>

<div class="channel-list">
    <?php foreach ($channels as $channel): ?>
        <a href="messages.php?channel=<?= urlencode($channel['id']) ?>" class="channel-card">
            <h3>
                <?= htmlspecialchars($channel['name'] ?: 'Unknown') ?>
                <span class="channel-type"><?= htmlspecialchars($channel['type']) ?></span>
            </h3>
            <p>
                <?= number_format($channel['message_count']) ?> messages
                <?php if ($channel['first_message'] && $channel['last_message']): ?>
                    &middot; <?= date('M j, Y', strtotime($channel['first_message'])) ?>
                    &ndash; <?= date('M j, Y', strtotime($channel['last_message'])) ?>
                <?php endif; ?>
            </p>
        </a>
    <?php endforeach; ?>
</div>

<?php if (empty($channels)): ?>
    <div class="empty-state">
        <p>No channels found.</p>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
