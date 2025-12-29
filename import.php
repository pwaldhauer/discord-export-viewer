<?php
/**
 * Discord Export Importer
 * Imports Discord JSON exports into SQLite database
 * Downloads attachments locally
 */

$packageDir = __DIR__ . '/package';
$dbFile = __DIR__ . '/discord.db';
$attachmentsDir = __DIR__ . '/public/attachments';

// Create attachments directory if it doesn't exist
if (!is_dir($attachmentsDir)) {
    mkdir($attachmentsDir, 0755, true);
    echo "Created attachments directory.\n";
}

/**
 * Download an attachment from Discord CDN and return local path
 */
function downloadAttachment($url, $attachmentsDir, $channelId, $messageId) {
    if (empty($url)) {
        return '';
    }

    // Parse URL to get filename
    $parsedUrl = parse_url($url);
    $pathParts = explode('/', $parsedUrl['path']);
    $filename = end($pathParts);

    // Create a subdirectory structure: channel_id/message_id/
    $subDir = $attachmentsDir . '/' . $channelId;
    if (!is_dir($subDir)) {
        mkdir($subDir, 0755, true);
    }

    // Local file path
    $localPath = $subDir . '/' . $messageId . '_' . $filename;
    $relativePath = 'attachments/' . $channelId . '/' . $messageId . '_' . $filename;

    // Skip if already downloaded
    if (file_exists($localPath)) {
        return $relativePath;
    }

    // Download the file
    $context = stream_context_create([
        'http' => [
            'timeout' => 30,
            'user_agent' => 'Mozilla/5.0 (compatible; DiscordArchiver/1.0)'
        ]
    ]);

    $content = @file_get_contents($url, false, $context);

    if ($content === false) {
        echo "  Failed to download: $url\n";
        return $url; // Return original URL if download fails
    }

    file_put_contents($localPath, $content);
    return $relativePath;
}

// Remove existing database
if (file_exists($dbFile)) {
    unlink($dbFile);
    echo "Removed existing database.\n";
}

// Create database and tables
$db = new PDO('sqlite:' . $dbFile);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$db->exec('
    CREATE TABLE servers (
        id TEXT PRIMARY KEY,
        name TEXT NOT NULL
    );

    CREATE TABLE channels (
        id TEXT PRIMARY KEY,
        server_id TEXT,
        type TEXT NOT NULL,
        name TEXT,
        recipients TEXT,
        FOREIGN KEY (server_id) REFERENCES servers(id)
    );

    CREATE TABLE messages (
        id TEXT PRIMARY KEY,
        channel_id TEXT NOT NULL,
        timestamp DATETIME NOT NULL,
        contents TEXT,
        attachments TEXT,
        FOREIGN KEY (channel_id) REFERENCES channels(id)
    );

    CREATE INDEX idx_messages_channel ON messages(channel_id);
    CREATE INDEX idx_messages_timestamp ON messages(timestamp);
    CREATE INDEX idx_channels_server ON channels(server_id);
');

echo "Database schema created.\n";

// Import servers from index.json
$serversIndex = json_decode(file_get_contents($packageDir . '/Servers/index.json'), true);

// Add a virtual "Direct Messages" server for DMs
$db->exec("INSERT INTO servers (id, name) VALUES ('DM', 'Direct Messages')");
echo "Added Direct Messages server.\n";

$stmtServer = $db->prepare('INSERT OR IGNORE INTO servers (id, name) VALUES (?, ?)');
foreach ($serversIndex as $serverId => $serverName) {
    $stmtServer->execute([$serverId, $serverName]);
    echo "Imported server: $serverName\n";
}

// Import channels and messages
$messagesDir = $packageDir . '/Messages';
$channelDirs = glob($messagesDir . '/c*', GLOB_ONLYDIR);

$stmtChannel = $db->prepare('INSERT OR IGNORE INTO channels (id, server_id, type, name, recipients) VALUES (?, ?, ?, ?, ?)');
$stmtMessage = $db->prepare('INSERT OR IGNORE INTO messages (id, channel_id, timestamp, contents, attachments) VALUES (?, ?, ?, ?, ?)');

$totalMessages = 0;
$totalChannels = 0;
$totalAttachments = 0;

foreach ($channelDirs as $channelDir) {
    $channelFile = $channelDir . '/channel.json';
    $messagesFile = $channelDir . '/messages.json';

    if (!file_exists($channelFile)) {
        continue;
    }

    $channel = json_decode(file_get_contents($channelFile), true);
    $channelId = $channel['id'];
    $channelType = $channel['type'];

    // Determine server ID and channel name
    if ($channelType === 'DM') {
        $serverId = 'DM';
        $channelName = 'DM with ' . implode(', ', $channel['recipients'] ?? []);
        $recipients = json_encode($channel['recipients'] ?? []);
    } else {
        $serverId = $channel['guild']['id'] ?? null;
        $channelName = $channel['name'] ?? 'Unknown';
        $recipients = null;
    }

    $stmtChannel->execute([$channelId, $serverId, $channelType, $channelName, $recipients]);
    $totalChannels++;

    // Import messages
    if (file_exists($messagesFile)) {
        $messages = json_decode(file_get_contents($messagesFile), true);

        if (is_array($messages)) {
            $channelAttachments = 0;
            foreach ($messages as $message) {
                $messageId = (string)$message['ID'];
                $attachmentUrl = $message['Attachments'];

                // Download attachment if present
                $localAttachment = '';
                if (!empty($attachmentUrl)) {
                    $localAttachment = downloadAttachment($attachmentUrl, $attachmentsDir, $channelId, $messageId);
                    if ($localAttachment !== $attachmentUrl) {
                        $channelAttachments++;
                    }
                }

                $stmtMessage->execute([
                    $messageId,
                    $channelId,
                    $message['Timestamp'],
                    $message['Contents'],
                    $localAttachment
                ]);
                $totalMessages++;
            }
            if ($channelAttachments > 0) {
                echo "  Downloaded $channelAttachments attachments\n";
                $totalAttachments += $channelAttachments;
            }
        }
    }

    echo "Imported channel: $channelName ($channelType)\n";
}

echo "\n";
echo "Import complete!\n";
echo "Servers: " . (count($serversIndex) + 1) . "\n";
echo "Channels: $totalChannels\n";
echo "Messages: $totalMessages\n";
echo "Attachments downloaded: $totalAttachments\n";
