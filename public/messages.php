<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';

$channelId = $_GET['channel'] ?? '';

$stmtChannel = $db->prepare('
    SELECT c.*, s.name as server_name, s.id as server_id
    FROM channels c
    LEFT JOIN servers s ON s.id = c.server_id
    WHERE c.id = ?
');
$stmtChannel->execute([$channelId]);
$channel = $stmtChannel->fetch(PDO::FETCH_ASSOC);

if (!$channel) {
    echo '<p>Channel not found.</p>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$stmtMessages = $db->prepare('
    SELECT * FROM messages
    WHERE channel_id = ?
    ORDER BY timestamp ASC
');
$stmtMessages->execute([$channelId]);
$messages = $stmtMessages->fetchAll(PDO::FETCH_ASSOC);

// Group messages by day
$messagesByDay = [];
foreach ($messages as $message) {
    $day = date('Y-m-d', strtotime($message['timestamp']));
    $messagesByDay[$day][] = $message;
}

function isImage($url) {
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $parsed = parse_url($url, PHP_URL_PATH);
    $extension = strtolower(pathinfo($parsed, PATHINFO_EXTENSION));
    return in_array($extension, $imageExtensions);
}

function formatAttachment($url) {
    if (empty($url)) {
        return '';
    }

    $html = '<div class="message-attachment">';
    if (isImage($url)) {
        $html .= '<a href="' . htmlspecialchars($url) . '" target="_blank">';
        $html .= '<img src="' . htmlspecialchars($url) . '" alt="Attachment" loading="lazy">';
        $html .= '</a>';
    } else {
        $html .= '<a href="' . htmlspecialchars($url) . '" target="_blank">Download Attachment</a>';
    }
    $html .= '</div>';

    return $html;
}
?>

<div class="breadcrumb">
    <a href="index.php">Servers</a>
    <span> / </span>
    <a href="channels.php?server=<?= urlencode($channel['server_id']) ?>"><?= htmlspecialchars($channel['server_name']) ?></a>
    <span> / </span>
    <strong><?= htmlspecialchars($channel['name'] ?: 'Unknown') ?></strong>
</div>

<h1><?= htmlspecialchars($channel['name'] ?: 'Unknown') ?></h1>
<p class="stats">
    <?= number_format(count($messages)) ?> messages
    <span class="channel-type"><?= htmlspecialchars($channel['type']) ?></span>
</p>

<?php if (empty($messages)): ?>
    <div class="empty-state">
        <p>No messages in this channel.</p>
    </div>
<?php else: ?>
    <?php foreach ($messagesByDay as $day => $dayMessages): ?>
        <div class="day-group">
            <div class="day-header"><?= date('l, F j, Y', strtotime($day)) ?></div>
            <?php foreach ($dayMessages as $message): ?>
                <div class="message">
                    <span class="message-time"><?= date('H:i', strtotime($message['timestamp'])) ?></span>
                    <span class="message-content"><?= nl2br(htmlspecialchars($message['contents'])) ?></span>
                    <?= formatAttachment($message['attachments']) ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
