# Discord Export Viewer

A PHP web application for browsing Discord data exports. Imports Discord's JSON export format into a SQLite database and provides a web interface to browse servers, channels, messages, and a calendar view.


## Vibe Warning

The code in this repo was mainly coded by Claude AI. It's not beautiful but does what it should do.	

## Features

- **Server & Channel Browser**: Navigate through all your Discord servers and channels
- **Message Viewer**: View messages grouped by day with timestamps and attachments
- **Calendar View**: Yearly calendar showing all days with messages, clickable to view daily activity
- **Daily Overview**: See all messages from a specific day grouped by channel across all servers
- **Attachment Downloads**: Automatically downloads attachments from Discord's CDN for local storage

## Requirements

- PHP 7.4+
- SQLite3 extension
- Web server (Apache, nginx, or PHP's built-in server)

## Installation

1. Clone or download this repository

2. Place your Discord data export in the `package/` directory. The expected structure is:
   ```
   package/
   ├── Servers/
   │   └── index.json
   └── Messages/
       ├── c1234567890/
       │   ├── channel.json
       │   └── messages.json
       └── c0987654321/
           ├── channel.json
           └── messages.json
   ```

3. Run the importer:
   ```bash
   php import.php
   ```
   This will:
   - Create `discord.db` SQLite database
   - Import all servers, channels, and messages
   - Download all attachments to `public/attachments/`

4. Start a web server in the `public/` directory:
   ```bash
   cd public
   php -S localhost:8000
   ```

5. Open http://localhost:8000 in your browser

## Project Structure

```
discord-export/
├── import.php              # Data importer script
├── discord.db              # SQLite database (created by import)
├── package/                # Discord export data (place your export here)
├── public/                 # Web root
│   ├── index.php          # Server list (home page)
│   ├── channels.php       # Channel list for a server
│   ├── messages.php       # Messages for a channel
│   ├── calendar.php       # Yearly calendar view
│   ├── day.php            # Daily messages across all servers
│   ├── attachments/       # Downloaded attachments (created by import)
│   └── includes/
│       ├── db.php         # Database connection
│       ├── header.php     # HTML header and styles
│       └── footer.php     # HTML footer
└── README.md
```

## Database Schema

```sql
-- Servers (Discord guilds + DMs)
CREATE TABLE servers (
    id TEXT PRIMARY KEY,
    name TEXT NOT NULL
);

-- Channels (text channels, DMs, etc.)
CREATE TABLE channels (
    id TEXT PRIMARY KEY,
    server_id TEXT,
    type TEXT NOT NULL,
    name TEXT,
    recipients TEXT
);

-- Messages
CREATE TABLE messages (
    id TEXT PRIMARY KEY,
    channel_id TEXT NOT NULL,
    timestamp DATETIME NOT NULL,
    contents TEXT,
    attachments TEXT
);
```

## Re-importing Data

To re-import your data (e.g., after a new export):

```bash
php import.php
```

This will delete and recreate the database. Already downloaded attachments are skipped, so only new attachments will be downloaded.

## License

MIT
