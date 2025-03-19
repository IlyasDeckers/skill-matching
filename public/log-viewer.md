# InfluxDB Logger for ODY Framework

This integration allows you to store logs from your ODY framework-based microservices in InfluxDB and view them in real-time.

## Features

- Log storage in InfluxDB with efficient time series format
- Support for multiple microservices sending logs to a central database
- Asynchronous logging with Swoole coroutines for high performance
- Real-time log monitoring through a JavaScript client
- Customizable log levels, tags, and retention policies
- Query APIs for filtering logs by service, level, or time range

## Installation

1. Install required dependencies:

```bash
composer require influxdb/influxdb-php
```

2. Add the InfluxDB configuration file to your config directory:

```bash
cp influxdb.php /path/to/your/app/config/
```

3. Add the InfluxDB service provider to your `app.php` configuration:

```php
'providers' => [
    // Other providers
    Ody\Foundation\Providers\InfluxDBServiceProvider::class,
],
```

4. Add the log viewer controller routes to your routes file:

```php
Route::get('/api/logs/recent', 'App\Controllers\LogViewerController@recent')
    ->middleware('auth:api');
    
Route::get('/api/logs/services', 'App\Controllers\LogViewerController@services')
    ->middleware('auth:api');
    
Route::get('/api/logs/levels', 'App\Controllers\LogViewerController@levels')
    ->middleware('auth:api');
```

5. Add LogViewerController.php to your controllers directory.

## Configuration

Configure the InfluxDB connection in your `.env` file or directly in `config/influxdb.php`:

```
INFLUXDB_HOST=localhost
INFLUXDB_PORT=8086
INFLUXDB_USERNAME=your_username
INFLUXDB_PASSWORD=your_password
INFLUXDB_DATABASE=logs
INFLUXDB_USE_COROUTINES=true
INFLUXDB_BATCH_SIZE=10
```

### Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `INFLUXDB_HOST` | InfluxDB server hostname | `localhost` |
| `INFLUXDB_PORT` | InfluxDB server port | `8086` |
| `INFLUXDB_USERNAME` | InfluxDB username | ` ` |
| `INFLUXDB_PASSWORD` | InfluxDB password | ` ` |
| `INFLUXDB_DATABASE` | InfluxDB database name | `logs` |
| `INFLUXDB_LOG_LEVEL` | Minimum log level to store | `debug` |
| `INFLUXDB_USE_COROUTINES` | Use Swoole coroutines for async logging | `true` |
| `INFLUXDB_BATCH_SIZE` | Number of logs to batch before writing | `10` |
| `INFLUXDB_RETENTION_DURATION` | Log retention period | `30d` |

## Usage

### Using the InfluxDBLogger Directly

```php
use Ody\Logger\InfluxDBLogger;

$logger = new InfluxDBLogger(
    'localhost',       // host
    'logs',           // database
    'username',       // username
    'password',       // password
    8086,             // port
    'debug',          // level
    null,             // formatter
    ['service' => 'user-service'], // default tags
    10,               // batch size
    true              // use coroutines
);

// Log messages
$logger->info('User logged in', ['user_id' => 123, 'tags' => ['module' => 'auth']]);
$logger->error('Database connection failed', ['connection' => 'mysql', 'error' => $exception]);
```

### Using with the Framework's LogManager

```php
// Log to InfluxDB channel
logger('User registered', ['user_id' => 123], 'influxdb');

// Or configure influxdb as your default channel in config/logging.php
// and use logger normally
logger('System started');
```

### Using Tags for Better Querying

Tags in InfluxDB are indexed, making filtering by tags very efficient:

```php
// Log with custom tags
logger('API request processed', [
    'duration' => 120,
    'endpoint' => '/users',
    'tags' => [
        'module' => 'api',
        'method' => 'GET',
        'status' => 200
    ]
], 'influxdb');
```

### Using the Log Viewer

1. Create the log viewer HTML page (copy log-viewer.html and log-viewer.js to your public directory)
2. Access the log viewer at http://your-app/log-viewer.html

## Multi-Service Configuration

For a multi-service architecture, set a unique service name in each service's configuration:

```
# Service 1 .env
APP_NAME=auth-service
INFLUXDB_HOST=influxdb.example.com
INFLUXDB_DATABASE=logs

# Service 2 .env
APP_NAME=user-service
INFLUXDB_HOST=influxdb.example.com
INFLUXDB_DATABASE=logs
```

All services will log to the same InfluxDB database but can be filtered by their service name.

## Considerations

- For high-load production environments, consider setting up an InfluxDB cluster
- Adjust retention policies based on your compliance and storage requirements
- Use Grafana alongside this integration for more advanced visualization capabilities