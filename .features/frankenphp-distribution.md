---
name: frankenphp-distribution
description: Build single binary distribution using FrankenPHP static builds
depends_on: real-time-updates
---

## Detailed Description

This feature implements the build process for distributing Sage as a single static binary using FrankenPHP. The binary will include the entire Laravel application, Caddy web server, and all necessary dependencies, requiring zero external setup from users.

### Key Capabilities
- Single binary distribution for macOS, Linux, and Windows
- No PHP installation required
- Embedded Caddy web server
- Automatic process management (web server, queue worker, scheduler, Reverb)
- Self-updating mechanism
- Zero-config startup with sensible defaults
- Portable (can run from any directory)
- Development and production modes

### Binary Commands
```bash
./sage serve              # Start all services (web, queue, reverb)
./sage version            # Show version info
./sage update             # Self-update to latest version
./sage optimize           # Run Laravel optimization commands
./sage migrate            # Run database migrations
```

### User Stories
1. As a user, I want to download and run Sage without installing PHP
2. As a user, I want Sage to just work with zero configuration
3. As a user, I want to easily update to the latest version
4. As a developer, I want to build binaries for all platforms

## Detailed Implementation Plan

### Step 1: Install FrankenPHP

Add FrankenPHP to project:
```bash
composer require dunglas/frankenphp
```

### Step 2: Create FrankenPHP Configuration

Create `frankenphp-config.php`:
```php
<?php

use function Frankenphp\run;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

run($app);
```

### Step 3: Create Build Script

Create `build/build.sh`:
```bash
#!/bin/bash

set -e

echo "Building Sage binary..."

# Platforms to build
PLATFORMS=("linux/amd64" "linux/arm64" "darwin/amd64" "darwin/arm64" "windows/amd64")

# Clean previous builds
rm -rf dist/
mkdir -p dist/

# Install dependencies
composer install --no-dev --optimize-autoloader
npm install
npm run build

# Optimize Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Build for each platform
for PLATFORM in "${PLATFORMS[@]}"; do
    echo "Building for $PLATFORM..."

    OS=$(echo $PLATFORM | cut -d'/' -f1)
    ARCH=$(echo $PLATFORM | cut -d'/' -f2)
    OUTPUT="dist/sage-${OS}-${ARCH}"

    if [ "$OS" = "windows" ]; then
        OUTPUT="${OUTPUT}.exe"
    fi

    # Build static binary with FrankenPHP
    frankenphp-builder build \
        --os "$OS" \
        --arch "$ARCH" \
        --output "$OUTPUT" \
        --embed-dir "." \
        --static

    echo "✓ Built $OUTPUT"
done

echo "Build complete! Binaries in dist/"
```

Make executable:
```bash
chmod +x build/build.sh
```

### Step 4: Create Artisan Commands for Binary

Create startup command:
```bash
php artisan make:command ServeCommand --no-interaction
```

**ServeCommand:**
```php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class ServeCommand extends Command
{
    protected $signature = 'sage:serve {--port=1984}';
    protected $description = 'Start Sage dashboard with all services';

    public function handle(): int
    {
        $this->info('Starting Sage...');

        // Start multiple processes
        $processes = [
            $this->startWebServer(),
            $this->startQueueWorker(),
            $this->startScheduler(),
            $this->startReverb(),
        ];

        $this->info('✓ All services started');
        $this->info('');
        $this->info('Dashboard: http://localhost:' . $this->option('port'));
        $this->info('Press Ctrl+C to stop');

        // Wait for processes
        $this->waitForProcesses($processes);

        return 0;
    }

    private function startWebServer(): Process
    {
        $process = new Process([
            PHP_BINARY,
            'artisan',
            'octane:start',
            '--port=' . $this->option('port'),
        ]);

        $process->setTimeout(null);
        $process->start();

        return $process;
    }

    private function startQueueWorker(): Process
    {
        $process = new Process([
            PHP_BINARY,
            'artisan',
            'queue:work',
            '--tries=3',
        ]);

        $process->setTimeout(null);
        $process->start();

        return $process;
    }

    private function startScheduler(): Process
    {
        $process = new Process([
            PHP_BINARY,
            'artisan',
            'schedule:work',
        ]);

        $process->setTimeout(null);
        $process->start();

        return $process;
    }

    private function startReverb(): Process
    {
        $process = new Process([
            PHP_BINARY,
            'artisan',
            'reverb:start',
            '--host=0.0.0.0',
            '--port=8080',
        ]);

        $process->setTimeout(null);
        $process->start();

        return $process;
    }

    private function waitForProcesses(array $processes): void
    {
        $running = true;

        pcntl_signal(SIGINT, function() use (&$running, $processes) {
            $this->info('Stopping services...');
            $running = false;

            foreach ($processes as $process) {
                $process->stop();
            }
        });

        while ($running) {
            pcntl_signal_dispatch();
            sleep(1);

            // Check if any process died
            foreach ($processes as $process) {
                if (!$process->isRunning()) {
                    $this->error('A service stopped unexpectedly');
                    $this->error($process->getErrorOutput());
                    $running = false;
                }
            }
        }
    }
}
```

### Step 5: Create Update Command

```bash
php artisan make:command UpdateCommand --no-interaction
```

**UpdateCommand:**
```php
class UpdateCommand extends Command
{
    protected $signature = 'sage:update';
    protected $description = 'Update Sage to the latest version';

    public function handle(): int
    {
        $this->info('Checking for updates...');

        $currentVersion = $this->getCurrentVersion();
        $latestVersion = $this->getLatestVersion();

        if (version_compare($currentVersion, $latestVersion, '>=')) {
            $this->info('Already up to date!');
            return 0;
        }

        $this->info("New version available: $latestVersion");

        if (!$this->confirm('Download and install?')) {
            return 0;
        }

        $this->downloadAndReplace($latestVersion);

        $this->info('✓ Updated to ' . $latestVersion);
        $this->info('Restart Sage to apply changes');

        return 0;
    }

    private function getCurrentVersion(): string
    {
        return config('app.version', '0.0.0');
    }

    private function getLatestVersion(): string
    {
        $response = Http::get('https://api.github.com/repos/prvious/sage/releases/latest');
        return $response->json('tag_name');
    }

    private function downloadAndReplace(string $version): void
    {
        // Detect OS and architecture
        $os = PHP_OS_FAMILY;
        $arch = php_uname('m');

        // Download binary
        $url = "https://github.com/prvious/sage/releases/download/{$version}/sage-{$os}-{$arch}";

        $binary = Http::get($url)->body();

        // Replace current binary
        $currentBinary = $_SERVER['SCRIPT_FILENAME'];
        $backup = $currentBinary . '.backup';

        copy($currentBinary, $backup);
        file_put_contents($currentBinary, $binary);
        chmod($currentBinary, 0755);
    }
}
```

### Step 6: Configure Build Files

Create `.dockerignore` (if using Docker for builds):
```
node_modules/
vendor/
.git/
.env
storage/logs/
storage/framework/cache/
dist/
```

Create `build/frankenphp.yaml`:
```yaml
name: sage
version: 1.0.0
description: AI Agent Orchestrator for Laravel

binary:
  name: sage
  entry: public/index.php

embed:
  - public/
  - app/
  - bootstrap/
  - config/
  - database/
  - resources/
  - routes/
  - storage/
  - vendor/
  - artisan
  - composer.json

exclude:
  - storage/logs/*
  - storage/framework/cache/*
  - node_modules/
  - .git/

environment:
  APP_ENV: production
  APP_DEBUG: false
```

### Step 7: Setup GitHub Actions for Builds

Create `.github/workflows/build.yml`:
```yaml
name: Build Binaries

on:
  push:
    tags:
      - 'v*'

jobs:
  build:
    strategy:
      matrix:
        os: [ubuntu-latest, macos-latest, windows-latest]
        include:
          - os: ubuntu-latest
            platform: linux
          - os: macos-latest
            platform: darwin
          - os: windows-latest
            platform: windows

    runs-on: ${{ matrix.os }}

    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.4
          extensions: sqlite, pdo, mbstring

      - name: Install Composer dependencies
        run: composer install --no-dev --optimize-autoloader

      - name: Install NPM dependencies
        run: npm install

      - name: Build frontend
        run: npm run build

      - name: Optimize Laravel
        run: |
          php artisan config:cache
          php artisan route:cache
          php artisan view:cache

      - name: Build binary
        run: ./build/build.sh

      - name: Upload artifacts
        uses: actions/upload-artifact@v3
        with:
          name: sage-${{ matrix.platform }}
          path: dist/

  release:
    needs: build
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v3

      - name: Download artifacts
        uses: actions/download-artifact@v3

      - name: Create Release
        uses: softprops/action-gh-release@v1
        with:
          files: |
            sage-linux/*
            sage-darwin/*
            sage-windows/*
        env:
          GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}
```

### Step 8: Create Installation Script

Create `install.sh` for easy installation:
```bash
#!/bin/bash

set -e

echo "Installing Sage..."

# Detect OS and architecture
OS=$(uname -s | tr '[:upper:]' '[:lower:]')
ARCH=$(uname -m)

# Map architecture names
case $ARCH in
    x86_64) ARCH="amd64" ;;
    aarch64) ARCH="arm64" ;;
    arm64) ARCH="arm64" ;;
esac

# Download latest release
echo "Downloading Sage for $OS/$ARCH..."
URL="https://github.com/prvious/sage/releases/latest/download/sage-${OS}-${ARCH}"

if command -v curl &> /dev/null; then
    curl -L "$URL" -o sage
elif command -v wget &> /dev/null; then
    wget "$URL" -O sage
else
    echo "Error: curl or wget required"
    exit 1
fi

# Make executable
chmod +x sage

# Move to /usr/local/bin (optional)
if [ -w "/usr/local/bin" ]; then
    mv sage /usr/local/bin/sage
    echo "✓ Sage installed to /usr/local/bin/sage"
else
    echo "✓ Sage downloaded to ./sage"
    echo "Run with: ./sage serve"
fi

echo ""
echo "Get started:"
echo "  sage serve"
```

### Step 9: Create README for Binary Distribution

Create `build/README-binary.md`:
```markdown
# Sage - AI Agent Orchestrator

## Quick Start

1. Download Sage for your platform
2. Run: `./sage serve`
3. Open: http://localhost:1984

## Commands

- `./sage serve` - Start dashboard
- `./sage version` - Show version
- `./sage update` - Update to latest version
- `./sage migrate` - Run database migrations
- `./sage optimize` - Optimize for production

## System Requirements

- No PHP required (included in binary)
- 100MB disk space
- Port 1984 available (or specify with --port)

## Support

https://github.com/prvious/sage
```

### Step 10: Configure Default Environment

Create `config/sage.php` with sensible defaults:
```php
return [
    'data_path' => env('SAGE_DATA_PATH', storage_path('sage')),

    'server' => [
        'port' => env('SAGE_PORT', 1984),
    ],

    'database' => [
        'path' => env('SAGE_DB_PATH', storage_path('sage/database.sqlite')),
    ],

    // Binary mode optimizations
    'binary_mode' => env('SAGE_BINARY_MODE', false),
];
```

### Step 11: Initialize Data Directory on First Run

Create initialization logic:
```php
// In service provider
public function boot(): void
{
    if (config('sage.binary_mode')) {
        $this->ensureDataDirectory();
        $this->ensureDatabase();
    }
}

private function ensureDataDirectory(): void
{
    $dataPath = config('sage.data_path');

    if (!file_exists($dataPath)) {
        mkdir($dataPath, 0755, true);
        mkdir($dataPath . '/database', 0755, true);
        mkdir($dataPath . '/logs', 0755, true);
    }
}

private function ensureDatabase(): void
{
    $dbPath = config('sage.database.path');

    if (!file_exists($dbPath)) {
        touch($dbPath);
        Artisan::call('migrate', ['--force' => true]);
    }
}
```

### Step 12: Create Version Command

```bash
php artisan make:command VersionCommand --no-interaction
```

```php
class VersionCommand extends Command
{
    protected $signature = 'sage:version';

    public function handle(): int
    {
        $this->info('Sage ' . config('app.version'));
        $this->info('PHP ' . PHP_VERSION);
        $this->info('Laravel ' . app()->version());
        return 0;
    }
}
```

### Step 13: Test Binary Build Locally

Build and test:
```bash
./build/build.sh
./dist/sage-$(uname -s | tr '[:upper:]' '[:lower:]')-$(uname -m) serve
```

Verify all services start correctly.

### Step 14: Create Distribution Package

Package binary with README:
```bash
tar -czf sage-linux-amd64.tar.gz \
    dist/sage-linux-amd64 \
    build/README-binary.md
```

### Step 15: Setup Auto-update Mechanism

Add version check on startup:
```php
// Check for updates on startup (non-blocking)
dispatch(function () {
    $updater = new UpdateChecker();
    if ($updater->hasUpdate()) {
        Log::info('New version available: ' . $updater->getLatestVersion());
        // Could show in UI
    }
});
```

### Step 16: Add Telemetry (Optional, Opt-in)

Track usage for improvements:
- Binary version
- OS/platform
- PHP version
- Anonymous usage stats

Completely opt-in with clear privacy policy.

### Step 17: Create Feature Tests

Test coverage:
- `it('serve command starts all services')`
- `it('version command shows correct info')`
- `it('update command checks for updates')`
- `it('initializes data directory on first run')`

### Step 18: Create Distribution Documentation

Document:
- Build process
- Release workflow
- Platform-specific considerations
- Troubleshooting binary issues

### Step 19: Setup Code Signing (macOS/Windows)

For macOS:
```bash
codesign --sign "Developer ID" dist/sage-darwin-amd64
```

For Windows:
```bash
signtool sign /f certificate.pfx dist/sage-windows-amd64.exe
```

### Step 20: Create First Release

```bash
git tag -a v1.0.0 -m "Initial release"
git push origin v1.0.0
```

GitHub Actions will build and publish binaries automatically.

### Step 21: Format Code
```bash
vendor/bin/pint --dirty
```
