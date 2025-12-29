<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';

$date = $_GET['date'] ?? '';

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo '<p>Invalid date format.</p>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Get all messages for this day across all servers, ordered by server, channel, then timestamp
$stmtMessages = $db->prepare('
    SELECT m.*, c.name as channel_name, c.id as channel_id, s.name as server_name, s.id as server_id
    FROM messages m
    LEFT JOIN channels c ON c.id = m.channel_id
    LEFT JOIN servers s ON s.id = c.server_id
    WHERE DATE(m.timestamp) = ?
    ORDER BY s.name ASC, c.name ASC, m.timestamp ASC
');
$stmtMessages->execute([$date]);
$messages = $stmtMessages->fetchAll(PDO::FETCH_ASSOC);

// Group messages by server and channel
$messagesByChannel = [];
foreach ($messages as $message) {
    $key = $message['server_id'] . '/' . $message['channel_id'];
    if (!isset($messagesByChannel[$key])) {
        $messagesByChannel[$key] = [
            'server_id' => $message['server_id'],
            'server_name' => $message['server_name'],
            'channel_id' => $message['channel_id'],
            'channel_name' => $message['channel_name'],
            'messages' => []
        ];
    }
    $messagesByChannel[$key]['messages'][] = $message;
}

$formattedDate = date('l, F j, Y', strtotime($date));

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

// Get previous and next days with messages
$stmtPrev = $db->prepare('
    SELECT DATE(timestamp) as date FROM messages
    WHERE DATE(timestamp) < ?
    GROUP BY DATE(timestamp)
    ORDER BY DATE(timestamp) DESC
    LIMIT 1
');
$stmtPrev->execute([$date]);
$prevDay = $stmtPrev->fetchColumn();

$stmtNext = $db->prepare('
    SELECT DATE(timestamp) as date FROM messages
    WHERE DATE(timestamp) > ?
    GROUP BY DATE(timestamp)
    ORDER BY DATE(timestamp) ASC
    LIMIT 1
');
$stmtNext->execute([$date]);
$nextDay = $stmtNext->fetchColumn();
?>

<div class="breadcrumb">
    <a href="index.php">Servers</a>
    <span> / </span>
    <a href="calendar.php">Calendar</a>
    <span> / </span>
    <strong><?= htmlspecialchars($formattedDate) ?></strong>
</div>

<div class="day-navigation">
    <?php if ($prevDay): ?>
        <a href="day.php?date=<?= $prevDay ?>" class="nav-prev">&larr; <?= date('M j, Y', strtotime($prevDay)) ?></a>
    <?php else: ?>
        <span class="nav-prev disabled"></span>
    <?php endif; ?>

    <h1><?= htmlspecialchars($formattedDate) ?></h1>

    <?php if ($nextDay): ?>
        <a href="day.php?date=<?= $nextDay ?>" class="nav-next"><?= date('M j, Y', strtotime($nextDay)) ?> &rarr;</a>
    <?php else: ?>
        <span class="nav-next disabled"></span>
    <?php endif; ?>
</div>

<p class="stats"><?= number_format(count($messages)) ?> messages across all servers</p>

<?php if (empty($messages)): ?>
    <div class="empty-state">
        <p>No messages on this day.</p>
    </div>
<?php else: ?>
    <?php foreach ($messagesByChannel as $channel): ?>
        <div class="channel-group">
            <div class="channel-group-header">
                <a href="channels.php?server=<?= urlencode($channel['server_id']) ?>"><?= htmlspecialchars($channel['server_name'] ?: 'Unknown Server') ?></a>
                <span class="meta-separator">/</span>
                <a href="messages.php?channel=<?= urlencode($channel['channel_id']) ?>">#<?= htmlspecialchars($channel['channel_name'] ?: 'unknown') ?></a>
                <span class="channel-message-count"><?= count($channel['messages']) ?> messages</span>
            </div>
            <div class="messages-list">
                <?php foreach ($channel['messages'] as $message): ?>
                    <div class="message">
                        <span class="message-time"><?= date('H:i:s', strtotime($message['timestamp'])) ?></span>
                        <span class="message-content"><?= nl2br(htmlspecialchars($message['contents'])) ?></span>
                        <?= formatAttachment($message['attachments']) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
