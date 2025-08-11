# Perspective API Bundle for Symfony

[![Latest Stable Version](https://img.shields.io/packagist/v/freema/perspective-api-bundle.svg)](https://packagist.org/packages/freema/perspective-api-bundle)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP Version Require](https://img.shields.io/packagist/php-v/freema/perspective-api-bundle.svg)](https://packagist.org/packages/freema/perspective-api-bundle)
[![Symfony Version](https://img.shields.io/badge/symfony-%5E5.4%7C%5E6.0%7C%5E7.0-brightgreen)](https://symfony.com)

A powerful Symfony bundle that integrates Google's Perspective API for content moderation and toxicity detection in your applications. Built with flexibility in mind, it supports multiple threshold management strategies, caching, and can be used as a standalone service or Symfony validator.

## 🎯 Key Features

- **🔧 Flexible Threshold Management** - Static config, database-driven, or runtime dynamic thresholds
- **💾 Built-in Caching Support** - Reduce API calls and improve performance
- **🎨 Multiple Usage Patterns** - Use as a service, validator constraint, or command-line tool
- **🌍 Multi-language Support** - Analyze content in multiple languages with automatic detection
- **📊 Rich Analysis Results** - Detailed scoring across multiple toxicity dimensions
- **⚡ Production Ready** - Battle-tested with proper error handling and logging
- **🔄 Easy Migration** - Simple migration path from deprecated libraries like `bredmor/comment-analyzer`

## 🚀 Why This Bundle?

This bundle was created as a modern, maintained alternative to abandoned Perspective API wrappers. It provides a clean, Symfony-native integration that follows best practices and offers the flexibility needed for real-world applications.

Unlike simple API wrappers, this bundle understands that different parts of your application might need different moderation thresholds - a children's section needs stricter controls than a general discussion forum. It allows you to implement context-aware content moderation without sacrificing simplicity.

## ⚠️ Important Note About Google's Gemini LLM Integration

Google has integrated their Gemini LLM into the Perspective API, which represents a shift from traditional deterministic models to AI-powered analysis. While the API remains fully functional and is actively supported by Google, please be aware that:

- **Consistency**: The same text might receive slightly different scores when analyzed at different times
- **Predictability**: Results may be less deterministic compared to traditional machine learning models  
- **Recommendation**: Consider implementing caching to ensure consistent moderation decisions for the same content

This change reflects Google's commitment to improving the API with cutting-edge AI technology, but it's important to understand these characteristics when implementing content moderation.

## 📋 Requirements

- PHP 8.1 or higher
- Symfony 5.4, 6.x, or 7.x
- Google Perspective API key ([Get one here](https://developers.perspectiveapi.com/s/docs-get-started))

## 🔧 Advanced Configuration

### HTTP Client Options

The bundle supports custom HTTP client configuration for environments that require proxy servers or specific timeouts:

```yaml
# config/packages/perspective_api.yaml
perspective_api:
    api_key: '%env(PERSPECTIVE_API_KEY)%'
    
    # Configure HTTP client options
    http_client_options:
        # Use proxy server
        proxy: '%env(HTTP_PROXY)%'
        
        # Request timeout in seconds
        timeout: 30
        
        # Maximum number of redirects
        max_redirects: 5
        
        # Custom headers
        headers:
            'User-Agent': 'MyApp/1.0'
        
        # Any other Symfony HTTP client option
        verify_peer: true
        verify_host: true
```

This is particularly useful for:
- Corporate environments with proxy requirements
- Applications with specific network configurations
- Custom timeout requirements for slow networks
- Adding custom headers for monitoring or tracking

## 🛠️ Use Cases

This bundle is perfect for:

- **Comment Systems** - Automatically moderate user comments before publication
- **Forums & Communities** - Maintain healthy discussion environments
- **Social Platforms** - Filter toxic content in real-time
- **Content Management** - Review and flag potentially problematic content
- **Customer Support** - Filter inappropriate messages in support tickets
- **Educational Platforms** - Ensure safe learning environments

## 📦 Installation

Install the bundle using Composer:

```bash
composer require freema/perspective-api-bundle
```

## ⚙️ Configuration

### Basic Configuration

```yaml
# config/packages/perspective_api.yaml
perspective_api:
    api_key: '%env(PERSPECTIVE_API_KEY)%'
    
    # Default thresholds for all attributes
    thresholds:
        TOXICITY: 0.5
        SEVERE_TOXICITY: 0.3
        IDENTITY_ATTACK: 0.5
        INSULT: 0.5
        PROFANITY: 0.5
        THREAT: 0.5
    
    # Which attributes to analyze
    analyze_attributes:
        - TOXICITY
        - SEVERE_TOXICITY
        - IDENTITY_ATTACK
        - INSULT
        - PROFANITY
        - THREAT
    
    # Default language for analysis
    default_language: 'en'
    
    # Allow runtime threshold override
    allow_runtime_override: true
    
    # Optional: Custom threshold provider service
    # threshold_provider: 'my.custom.threshold.provider'
    
    # Optional: HTTP client options (proxy, timeout, etc.)
    # http_client_options:
    #     proxy: 'http://proxy.example.com:8080'
    #     timeout: 30
    #     max_redirects: 5
```

### Environment Variables

```bash
# .env.local
PERSPECTIVE_API_KEY=your_api_key_here

# Optional: HTTP proxy configuration
HTTP_PROXY=http://proxy.example.com:8080
HTTPS_PROXY=http://proxy.example.com:8080
```

## 📖 Usage

### Basic Service Usage

```php
use Freema\PerspectiveApiBundle\Service\PerspectiveApiService;

class CommentController
{
    public function __construct(
        private readonly PerspectiveApiService $perspectiveApi
    ) {}

    public function submitComment(Request $request): Response
    {
        $comment = $request->get('comment');
        
        // Analyze the comment
        $result = $this->perspectiveApi->analyzeText($comment);
        
        // Check if content is safe
        if (!$result->isSafe()) {
            // Get specific violations
            $violations = $result->getViolations();
            
            return $this->json([
                'error' => 'Your comment contains inappropriate content',
                'violations' => $violations
            ], 400);
        }
        
        // Save the comment...
    }
}
```

### Using as a Validator

```php
use Freema\PerspectiveApiBundle\Validator\PerspectiveContent;
use Symfony\Component\Validator\Constraints as Assert;

class Comment
{
    #[Assert\NotBlank]
    #[PerspectiveContent(
        thresholds: [
            'TOXICITY' => 0.5,
            'PROFANITY' => 0.3
        ],
        message: 'Your comment contains inappropriate content.'
    )]
    private string $content;
}
```

### Using in Symfony Forms

The bundle provides a `PerspectiveTextType` for easy integration into Symfony forms:

```php
use Freema\PerspectiveApiBundle\Form\Type\PerspectiveTextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class CommentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('author', TextType::class)
            ->add('content', PerspectiveTextType::class, [
                'perspective_thresholds' => [
                    'TOXICITY' => 0.7,
                    'PROFANITY' => 0.5,
                ],
                'perspective_language' => 'en',
                'perspective_message' => 'Please keep your comments respectful.',
                'attr' => [
                    'rows' => 5,
                    'placeholder' => 'Write your comment...'
                ]
            ])
            ->add('submit', SubmitType::class);
    }
}
```

#### Form Type Options

- `perspective_validation` (bool, default: `true`) - Enable/disable Perspective validation
- `perspective_thresholds` (array, default: `[]`) - Custom thresholds for validation  
- `perspective_language` (string|null, default: `null`) - Language for analysis
- `perspective_message` (string|null, default: `null`) - Custom validation error message

```php
// Disable validation for specific form
$builder->add('content', PerspectiveTextType::class, [
    'perspective_validation' => false
]);

// Use form with custom validation message
$builder->add('message', PerspectiveTextType::class, [
    'perspective_thresholds' => ['TOXICITY' => 0.8],
    'perspective_message' => 'This content violates our community guidelines.'
]);
```

### Custom Threshold Provider

Implement dynamic thresholds based on context:

```php
use Freema\PerspectiveApiBundle\Contract\ThresholdProviderInterface;

class ContextualThresholdProvider implements ThresholdProviderInterface
{
    public function getThresholds(): array
    {
        // Return different thresholds based on current context
        if ($this->isChildrenSection()) {
            return [
                'TOXICITY' => 0.3,
                'PROFANITY' => 0.1,
                'THREAT' => 0.1,
            ];
        }
        
        return [
            'TOXICITY' => 0.7,
            'PROFANITY' => 0.5,
            'THREAT' => 0.5,
        ];
    }
}
```

### Using Threshold Resolver

```php
// Set a custom resolver function
$perspectiveApi->setThresholdResolver(function (string $attribute, array $context) {
    // Custom logic based on attribute and context
    if ($attribute === 'PROFANITY' && $context['strict_mode']) {
        return 0.1;
    }
    return null; // Use default
});

// Use with context
$result = $perspectiveApi->analyzeText(
    $text,
    null,
    null,
    ['strict_mode' => true]
);
```

### Batch Analysis

```php
$texts = [
    'This is a normal comment',
    'This might be problematic',
    'Another text to analyze'
];

$results = $this->perspectiveApi->analyzeBatch($texts);

foreach ($results as $index => $result) {
    echo "Text {$index}: " . ($result->isSafe() ? 'Safe' : 'Unsafe') . PHP_EOL;
}
```

### Analyzing with Specific Attributes

```php
// Analyze only specific attributes
$result = $this->perspectiveApi->analyzeWithAttributes(
    $text,
    ['TOXICITY', 'THREAT'],
    'en'
);
```


## 🧪 Development

For development setup, testing, and contribution guidelines, see:

- [DEVELOP.md](DEVELOP.md) - Development environment setup and testing
- [CONTRIBUTING.md](CONTRIBUTING.md) - Contribution guidelines

## 🔄 Migration from bredmor/comment-analyzer

If you're migrating from the deprecated `bredmor/comment-analyzer` library:

```php
// Old way with bredmor/comment-analyzer
$analyzer = new CommentAnalyzer($apiKey);
$response = $analyzer->analyze($text);
$toxicity = $response->toxicity();

// New way with this bundle
$result = $perspectiveApi->analyzeText($text);
$toxicity = $result->getScore('TOXICITY');
$isSafe = $result->isSafe();
```

## 📊 Available Attributes

- **TOXICITY** - A rude, disrespectful, or unreasonable comment
- **SEVERE_TOXICITY** - A very hateful, aggressive, disrespectful comment
- **IDENTITY_ATTACK** - Negative or hateful comments targeting identity
- **INSULT** - Insulting, inflammatory, or negative comment
- **PROFANITY** - Swear words, curse words, or profane language
- **THREAT** - Describes an intention to inflict pain or violence

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📄 License

This bundle is released under the MIT License. See the bundled LICENSE file for details.

## 🙏 Credits

- Created and maintained by [Freema](https://github.com/freema)
- Inspired by the need for a modern, maintained Perspective API integration
- Thanks to all contributors

## 📚 Resources

- [Google Perspective API Documentation](https://developers.perspectiveapi.com/s/docs)
- [Symfony Documentation](https://symfony.com/doc)
- [Bundle Issues & Support](https://github.com/freema/perspective-api-bundle/issues)