# Lingo

Lingo is a real-time multiplayer word game built with Laravel, Vue, and Inertia.

## Run locally

### Prerequisites

- PHP 8.3 or newer, with the SQLite/PDO SQLite extension enabled
- Composer
- Node.js and npm
- Git

Check that the tools are available in your terminal:

```shell
php -v
composer --version
node --version
npm --version
```

### 1. Get the code

Clone the repository and move into its directory:

```shell
git clone <repository-url> lingo
cd lingo
```

Replace `<repository-url>` with the URL for this repository.

### 2. Create the local SQLite database file

The app uses SQLite by default. Create its database file before running setup.

**macOS** (Terminal):

```shell
touch database/database.sqlite
```

**Windows** (PowerShell):

```powershell
New-Item -ItemType File -Force database\database.sqlite
```

### 3. Install and configure

From the project directory, run:

```shell
composer run setup
```

This installs PHP and JavaScript dependencies, creates `.env` from `.env.example`,
generates the application key, runs database migrations, and builds the frontend.
The default configuration uses the local SQLite database and Reverb for
real-time updates; no separate database server is needed.

### 4. Start the app

Open two terminals in the project directory.

In the first terminal, start Reverb, the queue worker, and the Vite development
server:

```shell
composer run dev
```

In the second terminal, start Laravel's local web server:

```shell
php artisan serve
```

Open [http://localhost:8000](http://localhost:8000) in your browser. Keep both
terminals running while using the app. Press `Ctrl+C` in each terminal to stop
the processes.
