# Development Guide

This guide covers development setup and testing for the Perspective API Bundle.

## 🚀 Quick Start

```bash
# Clone repository
git clone https://github.com/freema/perspective-api-bundle.git
cd perspective-api-bundle

# Initialize development environment
task init

# Configure API key for testing
cp dev/.env.example dev/.env
# Edit dev/.env and add your PERSPECTIVE_API_KEY

# Start development server
task dev:serve

# Access at http://localhost:8080
```

## 📋 Prerequisites

- Docker and Docker Compose
- [Task](https://taskfile.dev/) (optional, for easier command execution)
- Google Perspective API key ([Get one here](https://developers.perspectiveapi.com/s/docs-get-started))

## 🔧 Development Environment

### Environment Configuration

The dev environment supports `.env` configuration for easy testing:

```bash
# dev/.env
PERSPECTIVE_API_KEY=your_real_api_key_here

# Optional: Custom thresholds for testing
THRESHOLD_TOXICITY=0.3
THRESHOLD_PROFANITY=0.1

# Optional: Test specific attributes
ANALYZE_ATTRIBUTES=TOXICITY,PROFANITY,THREAT

# Optional: Proxy configuration (for corporate environments)
HTTP_PROXY=http://proxy.company.com:8080
HTTPS_PROXY=http://proxy.company.com:8080
NO_PROXY=localhost,127.0.0.1,.local

# Optional: SSL verification (ONLY for development/testing!)
# WARNING: Never use these in production!
HTTP_CLIENT_VERIFY_PEER=false
HTTP_CLIENT_VERIFY_HOST=false
```

**Important:** The `.env` file is ignored by git for security. Never commit real API keys!

### Available Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `PERSPECTIVE_API_KEY` | Your Google Perspective API key | `test_api_key_12345` |
| `THRESHOLD_TOXICITY` | Toxicity threshold (0.0-1.0) | `0.5` |
| `THRESHOLD_SEVERE_TOXICITY` | Severe toxicity threshold | `0.3` |
| `THRESHOLD_IDENTITY_ATTACK` | Identity attack threshold | `0.5` |
| `THRESHOLD_INSULT` | Insult threshold | `0.5` |
| `THRESHOLD_PROFANITY` | Profanity threshold | `0.5` |
| `THRESHOLD_THREAT` | Threat threshold | `0.5` |
| `ANALYZE_ATTRIBUTES` | Comma-separated list of attributes | All attributes |
| `DEFAULT_LANGUAGE` | Default language code | `en` |
| `ALLOW_RUNTIME_OVERRIDE` | Allow runtime threshold override | `true` |

## 🛠️ Available Commands

### Docker Management

```bash
task up         # Start containers
task down       # Stop containers
task restart    # Restart containers
task sh         # Open shell in PHP container
```

### Development

```bash
task init       # Initialize complete dev environment
task serve      # Start development server (port 8080)
task dev:setup  # Setup dev configuration (.env file)
```

### Testing

```bash
# Run all tests
task test:all

# Test specific Symfony version
task test:symfony54
task test:symfony64
task test:symfony71

# Run PHPUnit tests
task test

# Clean test environment
task test:cleanup
```

### Code Quality

```bash
task stan       # Run PHPStan analysis
task cs:fix     # Fix code style with PHP-CS-Fixer
```

### Composer

```bash
task install              # Install dependencies
task composer:dump-autoload  # Update autoloader
```

## 🧪 Testing Different Symfony Versions

The bundle is tested against multiple Symfony versions:

- Symfony 5.4 (LTS)
- Symfony 6.4 (LTS)
- Symfony 7.1

Each version has its own composer.json in the `test/` directory.

### Running Version-Specific Tests

```bash
# Test with Symfony 5.4
task test:symfony54

# Test with Symfony 6.4
task test:symfony64

# Test with Symfony 7.1
task test:symfony71

# Test all versions
task test:all
```

## 🐳 Docker Setup

The development environment uses Docker for consistency:

- **PHP 8.2** CLI container
- **Composer** pre-installed
- **Volume mounts** for code and composer cache

### Manual Docker Commands

If you prefer not to use Task:

```bash
# Start containers
docker compose up -d

# Install dependencies
docker exec -it perspective-api-bundle-php composer install

# Run tests
docker exec -it perspective-api-bundle-php vendor/bin/phpunit

# Start dev server
docker exec -it perspective-api-bundle-php php -S 0.0.0.0:8080 -t dev/
```

## 📁 Project Structure

```
perspective-api-bundle/
├── dev/                    # Development application
│   ├── Controller/         # Demo controllers
│   ├── config/            # Dev configuration
│   ├── .env.example       # Environment template
│   └── index.php          # Dev entry point
├── src/                   # Bundle source code
├── tests/                 # Unit tests
├── test/                  # Multi-version test configs
│   ├── symfony54/
│   ├── symfony64/
│   └── symfony71/
├── docker-compose.yml     # Docker configuration
├── Taskfile.yaml         # Task definitions
└── phpunit.xml.dist      # PHPUnit configuration
```

## 🔍 Demo Application

The `dev/` folder contains a minimal Symfony application for testing:

1. **Demo Controller** (`dev/Controller/DemoController.php`)
   - Web interface for testing the API
   - Available at http://localhost:8080

2. **API Testing**
   - Enter text in the web interface
   - See real-time analysis results
   - Test different thresholds via `.env`

## 🐛 Debugging

### Check Container Logs

```bash
docker logs perspective-api-bundle-php
```

### Clear Cache

```bash
rm -rf dev/cache/*
```

### Verify Configuration

```bash
docker exec -it perspective-api-bundle-php php dev/index.php
```

### Common Issues

1. **"Cannot autowire service"**
   - Make sure you've run `composer install`
   - Check that bundle is properly registered in `DevKernel.php`

2. **"API key not provided"**
   - Copy `dev/.env.example` to `dev/.env`
   - Add your actual API key

3. **Connection refused on port 8080**
   - Make sure Docker container is running: `task up`
   - Check if port is already in use

## 📊 Code Coverage

Generate code coverage report:

```bash
docker exec -it perspective-api-bundle-php vendor/bin/phpunit --coverage-html coverage/
```

View the report by opening `coverage/index.html` in your browser.

## 🔒 Security Notes

- Never commit `.env` files with real API keys
- Use different API keys for development and production
- The dev server is for local testing only - never expose it publicly
- SSL verification bypass (`HTTP_CLIENT_VERIFY_PEER=false`) should only be used in development

## 📝 Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for detailed contribution guidelines.