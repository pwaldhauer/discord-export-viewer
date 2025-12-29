<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discord Archive</title>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
            background: #36393f;
            color: #dcddde;
            line-height: 1.5;
        }
        a {
            color: #00aff4;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        h1, h2, h3 {
            color: #fff;
            margin-top: 0;
        }
        .breadcrumb {
            margin-bottom: 20px;
            padding: 10px 15px;
            background: #2f3136;
            border-radius: 5px;
        }
        .breadcrumb a {
            color: #b9bbbe;
        }
        .breadcrumb span {
            color: #72767d;
        }
        .server-list, .channel-list {
            display: grid;
            gap: 15px;
        }
        .server-card, .channel-card {
            display: block;
            padding: 20px;
            background: #2f3136;
            border-radius: 8px;
            transition: background 0.2s;
        }
        .server-card:hover, .channel-card:hover {
            background: #40444b;
            text-decoration: none;
        }
        .server-card h2, .channel-card h3 {
            margin: 0 0 8px 0;
        }
        .server-card p, .channel-card p {
            margin: 0;
            color: #72767d;
            font-size: 14px;
        }
        .channel-type {
            font-size: 12px;
            padding: 2px 6px;
            background: #5865f2;
            border-radius: 3px;
            color: #fff;
            margin-left: 8px;
        }
        .day-group {
            margin-bottom: 30px;
        }
        .day-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            color: #72767d;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .day-header::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #40444b;
            margin-left: 10px;
        }
        .message {
            padding: 8px 15px;
            margin: 2px 0;
            border-radius: 4px;
        }
        .message:hover {
            background: #32353b;
        }
        .message-time {
            color: #72767d;
            font-size: 12px;
            margin-right: 10px;
        }
        .message-content {
            word-break: break-word;
        }
        .message-attachment {
            margin-top: 8px;
        }
        .message-attachment img {
            max-width: 400px;
            max-height: 300px;
            border-radius: 4px;
        }
        .message-attachment a {
            display: inline-block;
            padding: 8px 12px;
            background: #40444b;
            border-radius: 4px;
            font-size: 14px;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #72767d;
        }
        .stats {
            color: #72767d;
            font-size: 14px;
            margin-bottom: 20px;
        }
        /* Calendar styles */
        .calendar-year {
            margin-bottom: 40px;
        }
        .year-header {
            font-size: 28px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #40444b;
        }
        .months-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }
        .calendar-month {
            background: #2f3136;
            border-radius: 8px;
            padding: 15px;
        }
        .month-header {
            font-weight: 600;
            color: #fff;
            margin-bottom: 10px;
            text-align: center;
        }
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 2px;
        }
        .day-name {
            font-size: 10px;
            color: #72767d;
            text-align: center;
            padding: 4px 0;
        }
        .day-cell {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: #72767d;
            border-radius: 4px;
        }
        .day-cell.empty {
            background: transparent;
        }
        .day-cell.has-messages {
            background: #5865f2;
            color: #fff;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .day-cell.has-messages:hover {
            background: #4752c4;
            text-decoration: none;
        }
        /* Day view styles */
        .day-navigation {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .day-navigation h1 {
            margin: 0;
            font-size: 24px;
        }
        .nav-prev, .nav-next {
            min-width: 120px;
            padding: 8px 12px;
            background: #2f3136;
            border-radius: 4px;
            font-size: 14px;
        }
        .nav-prev {
            text-align: left;
        }
        .nav-next {
            text-align: right;
        }
        .nav-prev.disabled, .nav-next.disabled {
            visibility: hidden;
        }
        .day-message {
            background: #2f3136;
            padding: 12px 15px;
            margin-bottom: 8px;
            border-radius: 6px;
        }
        .day-message .message-time {
            font-family: monospace;
        }
        .message-meta {
            font-size: 12px;
            margin-left: 10px;
        }
        .message-meta a {
            color: #b9bbbe;
        }
        .message-meta a:hover {
            color: #00aff4;
        }
        .meta-separator {
            color: #72767d;
            margin: 0 4px;
        }
        .day-message .message-content {
            display: block;
            margin-top: 6px;
            padding-left: 70px;
        }
        .nav-links {
            margin-bottom: 20px;
        }
        .nav-links a {
            display: inline-block;
            padding: 10px 20px;
            background: #5865f2;
            color: #fff;
            border-radius: 4px;
            margin-right: 10px;
        }
        .nav-links a:hover {
            background: #4752c4;
            text-decoration: none;
        }
        /* Channel group styles for day view */
        .channel-group {
            margin-bottom: 30px;
            background: #2f3136;
            border-radius: 8px;
            overflow: hidden;
        }
        .channel-group-header {
            padding: 12px 15px;
            background: #202225;
            font-weight: 600;
            border-bottom: 1px solid #40444b;
        }
        .channel-group-header a {
            color: #fff;
        }
        .channel-group-header a:hover {
            color: #00aff4;
        }
        .channel-message-count {
            float: right;
            font-weight: normal;
            font-size: 12px;
            color: #72767d;
        }
        .channel-group .messages-list {
            padding: 10px 0;
        }
        .channel-group .message {
            padding: 6px 15px;
        }
    </style>
</head>
<body>
