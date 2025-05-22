# Tracking.my API Integration

This project integrates with the tracking.my API for package tracking and delivery management.

## Features

- **Track Packages**: Register and monitor package tracking numbers
- **Webhook Support**: Receive real-time updates when package status changes
- **Multiple Couriers**: Support for various shipping providers (J&T, PosLaju, etc.)
- **Event Handling**: Process tracking events like delivery updates, checkpoint changes
- **Dual API Support**: Compatible with both v1 and v2 API versions

## Prerequisites

- PHP 7.3+ 
- cURL extension enabled
- Valid tracking.my API key
- Public URL for webhook endpoint (ngrok for development)

## Installation

1. **Clone or download the project**
   ```bash
   git clone <your-repo-url>
   cd tracking-my-integration
   ```

2. **Create the directory structure**
   ```
   project/
   ├── config/
   │   └── config.php
   ├── src/
   │   └── TrackingClient.php
   ├── scripts/
   │   ├── register_tracking.php
   │   ├── get_tracking.php
   │   ├── register_webhook.php
   │   └── test_webhook.php
   ├── public/
   │   └── webhook.php
   ├── logs/
   └── README.md
   ```

3. **Configure your settings**
   
   Copy and edit the configuration file:
   ```bash
   cp config/config.example.php config/config.php
   ```
   
   Update `config/config.php` with your details:
   ```php
   return [
       'api_key' => 'YOUR_TRACKING_MY_API_KEY',
       'webhook_url' => 'https://your-domain.com/webhook.php',
       'webhook_secret' => 'your-random-secret-key'
   ];
   ```

## Configuration

### API Key
Get your API key from the tracking.my seller dashboard.

### Webhook URL
- **Production**: Use your actual domain
- **Development**: Use ngrok to expose your local server
  ```bash
  ngrok http 80
  # Use the https URL provided by ngrok
  ```

### Webhook Secret
Generate a random string for webhook security:
```bash
openssl rand -base64 32
```

## Usage

### 1. Register a Tracking Number

Register a package for tracking:

```bash
php scripts/register_tracking.php <tracking_number> [courier] [order_number]
```

**Examples:**
```bash
# Register J&T tracking
php scripts/register_tracking.php 600527447944 jt

# Register with custom order number
php scripts/register_tracking.php 600527447944 jt ORDER123

# Register PosLaju tracking
php scripts/register_tracking.php EH123456789MY pos
```

### 2. Get Tracking Information

Retrieve tracking details:

```bash
php scripts/get_tracking.php <tracking_number> [courier]
```

**Examples:**
```bash
php scripts/get_tracking.php 600527447944 jt
php scripts/get_tracking.php EH123456789MY pos
```

### 3. Register Webhook

Set up webhook to receive real-time updates:

```bash
php scripts/register_webhook.php
```

This will register your webhook URL to receive events for:
- Tracking creation/updates
- Delivery status changes
- Shipment events

### 4. Test Webhook

Test your webhook endpoint:

```bash
php scripts/test_webhook.php
```

## Webhook Events

Your webhook will receive different types of events:

### Tracking Events
- `trackings/create` - New tracking registered
- `trackings/update` - Tracking information updated
- `trackings/checkpoint_update` - Package status changed
- `trackings/delete` - Tracking removed

### Shipment Events
- `shipments/create` - New shipment created
- `shipments/update` - Shipment updated
- `shipments/generated` - Shipping label generated
- `shipments/cancel` - Shipment cancelled
- `shipments/delete` - Shipment deleted

### Example Webhook Data

```json
{
  "events": [
    {
      "time": 1744876112,
      "event": "trackings/update",
      "tracking": {
        "tracking_number": "600527447944",
        "courier": "jt",
        "status": "delivered",
        "latest_checkpoint": {
          "time": "2023-03-08T14:42:08+08:00",
          "status": "delivered",
          "content": "Delivered",
          "location": "Drop Point PDP AJIL 211"
        }
      }
    }
  ]
}
```

## API Client Usage

You can also use the TrackingClient class directly in your code:

```php
require_once 'src/TrackingClient.php';

$client = new TrackingClient('your-api-key');

// Create tracking
$result = $client->createTracking([
    'tracking_number' => '600527447944',
    'courier' => 'jt',
    'customer_name' => 'John Doe',
    'customer_email' => 'john@example.com'
]);

// Get tracking info
$result = $client->getTracking('jt', '600527447944');

// Register webhook
$result = $client->registerWebhook(
    'https://your-domain.com/webhook.php',
    ['trackings/create', 'trackings/update'],
    'your-secret-key'
);
```

## Logging

The application logs all activities to the `logs/` directory:

- `webhook_YYYY-MM-DD.log` - All webhook requests
- `events_YYYY-MM-DD.log` - Processed events
- `signature_check_YYYY-MM-DD.log` - Webhook signature verification

## Troubleshooting

### Common Issues

**1. SSL Certificate Errors**
```bash
# For development only - disable SSL verification
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
```

**2. Webhook Not Receiving Events**
- Check webhook URL is publicly accessible
- Verify webhook is registered correctly
- Check logs for incoming requests
- Ensure webhook returns HTTP 200 status

**3. API Authentication Errors**
- Verify API key is correct
- Check API key has proper permissions
- Ensure headers are set correctly

**4. Tracking Registration Fails**
- Use correct courier code
- Ensure tracking number format is valid
- Check API version compatibility (use v1 for tracking operations)

### Debug Mode

Enable verbose logging by checking the log files:

```bash
# View webhook logs
tail -f logs/webhook_$(date +%Y-%m-%d).log

# View event logs  
tail -f logs/events_$(date +%Y-%m-%d).log
```

## API Versions

This integration supports both API versions:

- **API v1**: Used for tracking operations (create, read, delete)
- **API v2**: Used for webhook management

The client automatically uses the appropriate version for each operation.

## Security

### Webhook Security

- Always use HTTPS for webhook URLs
- Implement HMAC signature verification
- Use a strong random secret key
- Validate incoming webhook data

### API Security

- Keep your API key secure
- Don't commit API keys to version control
- Use environment variables in production
- Rotate API keys regularly

## Development

### Setting Up Development Environment

1. **Install ngrok** for local webhook testing:
   ```bash
   # Download from https://ngrok.com/
   ngrok http 80
   ```

2. **Start local server**:
   ```bash
   php -S localhost:80 -t public/
   ```

3. **Update webhook URL** with ngrok URL in config

### Testing

Run the test scripts to verify everything works:

```bash
# Test tracking registration
php scripts/register_tracking.php TEST123456789 jt

# Test tracking retrieval  
php scripts/get_tracking.php TEST123456789 jt

# Test webhook
php scripts/test_webhook.php
```

## Production Deployment

1. **Upload files** to your web server
2. **Set proper permissions**:
   ```bash
   chmod 755 public/
   chmod 644 public/webhook.php
   chmod 755 logs/
   ```
3. **Configure web server** to point to `public/` directory
4. **Update config** with production URLs and credentials
5. **Enable SSL/HTTPS** for webhook endpoint
6. **Set up log rotation** for log files

## Support

For issues with this integration:
1. Check the troubleshooting section above
2. Review log files for error details
3. Verify API documentation at tracking.my

For tracking.my API support:
- Visit tracking.my documentation
- Contact tracking.my support team

## License

This project is open source. Feel free to modify and use as needed.

---

**Note**: Please refer to the official tracking.my documentation for the most up-to-date API information.