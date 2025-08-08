<?php

declare(strict_types=1);

namespace Freema\PerspectiveApiBundle\Dev\Controller;

use Freema\PerspectiveApiBundle\Service\PerspectiveApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DemoController extends AbstractController
{
    public function __construct(
        #[Autowire(service: 'perspective_api')]
        private readonly PerspectiveApiService $perspectiveApi,
    ) {
    }

    public function index(): Response
    {
        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>Perspective API Bundle Demo</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        h1 { color: #333; }
        textarea { width: 100%; padding: 10px; margin: 10px 0; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; cursor: pointer; }
        button:hover { background: #0056b3; }
        #result { margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px; }
        .score { margin: 5px 0; }
        .high { color: red; }
        .medium { color: orange; }
        .low { color: green; }
        .error { color: red; background: #ffe0e0; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Perspective API Bundle Demo</h1>
    <p>Enter text to analyze for toxicity and other attributes:</p>

    <textarea id="text" rows="5" placeholder="Enter text to analyze...">This is a test message.</textarea>
    <br>
    <button onclick="analyzeText()">Analyze Text</button>

    <div id="result"></div>

    <hr style="margin: 40px 0;">

    <h2>Symfony Form Integration Demo</h2>
    <p><a href="/form-demo" style="color: #007bff; text-decoration: none;">Try the Form Demo →</a></p>
    <p>See how PerspectiveTextType works in a real Symfony form with validation.</p>

    <script>
    async function analyzeText() {
        const text = document.getElementById('text').value;
        const resultDiv = document.getElementById('result');

        if (!text.trim()) {
            resultDiv.innerHTML = '<div class="error">Please enter some text to analyze.</div>';
            return;
        }

        resultDiv.innerHTML = 'Analyzing...';

        try {
            const response = await fetch('/analyze', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ text: text })
            });

            const data = await response.json();

            if (data.error) {
                resultDiv.innerHTML = '<div class="error">Error: ' + data.error + '</div>';
                return;
            }

            let html = '<h3>Analysis Results:</h3>';
            html += '<div><strong>Overall Safe: ' + (data.is_safe ? 'Yes ✓' : 'No ✗') + '</strong></div>';
            html += '<div><strong>Severity Level: ' + data.severity_level + '</strong></div><br>';
            html += '<h4>Scores:</h4>';

            for (const [attribute, score] of Object.entries(data.scores)) {
                const percentage = (score * 100).toFixed(1);
                let cssClass = 'low';
                if (score > 0.7) cssClass = 'high';
                else if (score > 0.4) cssClass = 'medium';

                html += '<div class="score"><strong>' + attribute + ':</strong> ';
                html += '<span class="' + cssClass + '">' + percentage + '%</span>';

                if (data.violations && data.violations.includes(attribute)) {
                    html += ' <span style="color: red;">⚠️ Threshold exceeded</span>';
                }
                html += '</div>';
            }

            if (data.exceeded_thresholds && Object.keys(data.exceeded_thresholds).length > 0) {
                html += '<br><h4>Exceeded Thresholds:</h4>';
                for (const [attribute, details] of Object.entries(data.exceeded_thresholds)) {
                    html += '<div style="color: red;">' + attribute + ': ';
                    html += 'Score: ' + (details.score * 100).toFixed(1) + '% ';
                    html += '(Threshold: ' + (details.threshold * 100).toFixed(0) + '%)</div>';
                }
            }

            resultDiv.innerHTML = html;
        } catch (error) {
            resultDiv.innerHTML = '<div class="error">Network error: ' + error.message + '</div>';
        }
    }
    </script>
</body>
</html>
HTML;

        return new Response($html);
    }

    public function analyze(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $text = $data['text'] ?? '';

            if (empty($text)) {
                return new JsonResponse(['error' => 'Text is required'], 400);
            }

            $result = $this->perspectiveApi->analyzeText($text);

            return new JsonResponse([
                'scores' => $result->getScores(),
                'is_safe' => $result->isSafe(),
                'violations' => array_keys($result->getExceededThresholds()),
                'exceeded_thresholds' => $result->getExceededThresholds(),
                'severity_level' => $result->getSeverityLevel(),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    public function formDemo(Request $request): Response
    {
        $result = null;
        $errors = [];

        if ($request->isMethod('POST')) {
            $author = $request->request->get('author', '');
            $email = $request->request->get('email', '');
            $content = $request->request->get('content', '');

            // Validate required fields
            if (empty($author)) {
                $errors['author'] = 'Author is required';
            }
            if (empty($content)) {
                $errors['content'] = 'Content is required';
            }

            // Validate with Perspective API
            if (!empty($content) && empty($errors)) {
                try {
                    $apiResult = $this->perspectiveApi->analyzeText($content, null, [
                        'TOXICITY' => 0.7,
                        'PROFANITY' => 0.5,
                        'THREAT' => 0.4,
                        'INSULT' => 0.6,
                    ]);

                    if (!$apiResult->isSafe()) {
                        $violations = implode(', ', $apiResult->getViolations());
                        $errors['content'] = 'Please keep your comments respectful and constructive. Violations: '.$violations;
                    }
                } catch (\Exception $e) {
                    // API error - don't block submission
                }
            }

            if (empty($errors)) {
                $result = 'Comment submitted successfully!<br>';
                $result .= 'Author: '.htmlspecialchars($author).'<br>';
                $result .= 'Content: '.htmlspecialchars($content).'<br>';
                if (!empty($email)) {
                    $result .= 'Email: '.htmlspecialchars($email).'<br>';
                }
            }
        }

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>Form Demo - Perspective API Bundle</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        h1 { color: #333; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; cursor: pointer; border-radius: 4px; }
        button:hover { background: #0056b3; }
        .error { color: red; margin-top: 5px; }
        .success { color: green; background: #e0ffe0; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
        .help { font-size: 0.9em; color: #666; margin-top: 5px; }
        .back-link { margin-bottom: 20px; }
        .back-link a { color: #007bff; text-decoration: none; }
    </style>
</head>
<body>
    <div class="back-link">
        <a href="/">← Back to API Demo</a>
    </div>

    <h1>Form Demo - Perspective API Validation</h1>
    <p>This form demonstrates server-side validation using Perspective API with custom thresholds.</p>

HTML;

        if ($result) {
            $html .= '<div class="success">'.$result.'</div>';
        }

        $authorValue = htmlspecialchars($request->request->get('author', ''));
        $emailValue = htmlspecialchars($request->request->get('email', ''));
        $contentValue = htmlspecialchars($request->request->get('content', ''));

        $html .= '<form method="post">';
        $html .= '<div class="form-group">';
        $html .= '<label>Author *</label>';
        $html .= '<input type="text" name="author" value="'.$authorValue.'" placeholder="Your name" required>';
        if (isset($errors['author'])) {
            $html .= '<div class="error">'.$errors['author'].'</div>';
        }
        $html .= '</div>';

        $html .= '<div class="form-group">';
        $html .= '<label>Email</label>';
        $html .= '<input type="email" name="email" value="'.$emailValue.'" placeholder="your.email@example.com (optional)">';
        $html .= '</div>';

        $html .= '<div class="form-group">';
        $html .= '<label>Content *</label>';
        $html .= '<textarea name="content" rows="5" placeholder="Write your comment here..." class="form-control" required>'.$contentValue.'</textarea>';
        $html .= '<div class="help">Your comment will be automatically checked for inappropriate content.</div>';
        if (isset($errors['content'])) {
            $html .= '<div class="error">'.$errors['content'].'</div>';
        }
        $html .= '</div>';

        $html .= '<div class="form-group">';
        $html .= '<button type="submit" class="btn btn-primary">Post Comment</button>';
        $html .= '</div>';
        $html .= '</form>';

        $html .= <<<HTML

    <h3>Configuration</h3>
    <p>This form validates content using Perspective API with these thresholds:</p>
    <ul>
        <li><strong>TOXICITY:</strong> 70%</li>
        <li><strong>PROFANITY:</strong> 50%</li>
        <li><strong>THREAT:</strong> 40%</li>
        <li><strong>INSULT:</strong> 60%</li>
    </ul>
    <p>Try entering inappropriate content to see the validation in action!</p>

    <h3>PerspectiveTextType Integration</h3>
    <p>In a real Symfony application with forms installed, you would use the <code>PerspectiveTextType</code>:</p>
    <pre style="background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto;">
\$builder->add('content', PerspectiveTextType::class, [
    'perspective_thresholds' => [
        'TOXICITY' => 0.7,
        'PROFANITY' => 0.5,
        'THREAT' => 0.4,
        'INSULT' => 0.6,
    ],
    'perspective_message' => 'Please keep your comments respectful.',
]);
    </pre>
</body>
</html>
HTML;

        return new Response($html);
    }
}
