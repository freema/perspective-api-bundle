# Contributing to Perspective API Bundle

Thank you for considering contributing to the Perspective API Bundle! We welcome contributions of all kinds.

## Development Environment

### Prerequisites

- Docker and Docker Compose
- [Task](https://taskfile.dev/) (optional, for easier command execution)
- Git

### Setup

1. Clone the repository:
```bash
git clone https://github.com/freema/perspective-api-bundle.git
cd perspective-api-bundle
```

2. Initialize development environment:
```bash
task init
# or manually:
docker compose up -d
docker exec -it perspective-api-bundle-php composer install
```

3. Run tests to ensure everything works:
```bash
task test:all
```

### Available Commands

```bash
# Development
task init          # Initialize development environment
task up            # Start containers
task down          # Stop containers
task serve         # Start development server

# Testing
task test          # Run PHPUnit tests
task test:all      # Test all Symfony versions
task test:symfony54 # Test Symfony 5.4
task test:symfony64 # Test Symfony 6.4  
task test:symfony71 # Test Symfony 7.1

# Code Quality
task stan          # Run PHPStan analysis
task cs:fix        # Fix code style with PHP-CS-Fixer
task php:shell     # Open shell in container
```

## Code Standards

### Code Style
- Follow PSR-12 coding standards
- Use PHP-CS-Fixer to maintain consistent style
- Run `task cs:fix` before committing

### Static Analysis
- Code must pass PHPStan at maximum level
- Run `task stan` to check

### Testing
- Write unit tests for all new functionality
- Maintain high code coverage
- Test against multiple Symfony versions (5.4, 6.4, 7.1)
- Run `task test:all` before submitting PR

## Pull Request Process

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/your-feature`
3. Make your changes
4. Add tests for new functionality
5. Ensure all tests pass: `task test:all`
6. Ensure code style is correct: `task cs:fix`
7. Ensure static analysis passes: `task stan`
8. Commit with descriptive messages
9. Push to your fork
10. Open a Pull Request

### Commit Messages

Use conventional commits format:
```
feat: add batch analysis functionality
fix: handle API timeout errors properly
docs: update configuration examples
test: add integration tests for validator
```

## Reporting Issues

When reporting issues, please include:
- PHP version
- Symfony version
- Bundle version
- Complete error message
- Minimal code example to reproduce
- Expected vs actual behavior

## Feature Requests

Before requesting features:
- Check existing issues and PRs
- Consider if it fits the bundle's scope
- Provide use cases and examples
- Be willing to contribute implementation

## Questions

- Check the README.md first
- Search existing issues
- Open a new issue with the "question" label

## License

By contributing, you agree that your contributions will be licensed under the MIT License.

## Code of Conduct

- Be respectful and inclusive
- Focus on constructive feedback
- Help newcomers and answer questions
- Follow community guidelines

Thank you for contributing! 🎉