<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';

// Get the date range of all messages
$stmtRange = $db->query('
    SELECT MIN(DATE(timestamp)) as min_date, MAX(DATE(timestamp)) as max_date
    FROM messages
');
$range = $stmtRange->fetch(PDO::FETCH_ASSOC);

if (!$range['min_date'] || !$range['max_date']) {
    echo '<p>No messages found.</p>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Get all dates that have messages with their counts
$stmtDays = $db->query('
    SELECT DATE(timestamp) as date, COUNT(*) as message_count
    FROM messages
    GROUP BY DATE(timestamp)
');
$messageDays = [];
while ($row = $stmtDays->fetch(PDO::FETCH_ASSOC)) {
    $messageDays[$row['date']] = $row['message_count'];
}

$startDate = new DateTime($range['min_date']);
$endDate = new DateTime($range['max_date']);

// Get all years to display
$startYear = (int)$startDate->format('Y');
$endYear = (int)$endDate->format('Y');

$monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
               'July', 'August', 'September', 'October', 'November', 'December'];
$dayNames = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
?>

<div class="breadcrumb">
    <a href="index.php">Servers</a>
    <span> / </span>
    <strong>Calendar</strong>
</div>

<h1>Message Calendar</h1>
<p class="stats">
    <?= date('F j, Y', strtotime($range['min_date'])) ?> &ndash; <?= date('F j, Y', strtotime($range['max_date'])) ?>
    &middot; <?= number_format(array_sum($messageDays)) ?> total messages
</p>

<?php for ($year = $startYear; $year <= $endYear; $year++): ?>
    <div class="calendar-year">
        <h2 class="year-header"><?= $year ?></h2>
        <div class="months-grid">
            <?php for ($month = 1; $month <= 12; $month++):
                $firstDay = new DateTime("$year-$month-01");
                $daysInMonth = (int)$firstDay->format('t');
                // Monday = 1, Sunday = 7 (ISO-8601)
                $startDayOfWeek = (int)$firstDay->format('N');
            ?>
                <div class="calendar-month">
                    <div class="month-header"><?= $monthNames[$month - 1] ?></div>
                    <div class="calendar-grid">
                        <?php foreach ($dayNames as $dayName): ?>
                            <div class="day-name"><?= $dayName ?></div>
                        <?php endforeach; ?>

                        <?php
                        // Empty cells before first day
                        for ($i = 1; $i < $startDayOfWeek; $i++): ?>
                            <div class="day-cell empty"></div>
                        <?php endfor; ?>

                        <?php for ($day = 1; $day <= $daysInMonth; $day++):
                            $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);
                            $hasMessages = isset($messageDays[$dateStr]);
                            $messageCount = $hasMessages ? $messageDays[$dateStr] : 0;
                        ?>
                            <?php if ($hasMessages): ?>
                                <a href="day.php?date=<?= $dateStr ?>"
                                   class="day-cell has-messages"
                                   title="<?= number_format($messageCount) ?> messages">
                                    <?= $day ?>
                                </a>
                            <?php else: ?>
                                <div class="day-cell"><?= $day ?></div>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                </div>
            <?php endfor; ?>
        </div>
    </div>
<?php endfor; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
