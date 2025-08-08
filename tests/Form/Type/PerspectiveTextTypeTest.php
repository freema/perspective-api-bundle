<?php

declare(strict_types=1);

namespace Freema\PerspectiveApiBundle\Tests\Form\Type;

use Freema\PerspectiveApiBundle\Form\Type\PerspectiveTextType;
use Freema\PerspectiveApiBundle\Validator\PerspectiveContent;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Test\TypeTestCase;

class PerspectiveTextTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $formData = 'This is a safe comment';

        $form = $this->factory->create(PerspectiveTextType::class);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($formData, $form->getData());
    }

    public function testDefaultOptions(): void
    {
        $form = $this->factory->create(PerspectiveTextType::class);
        $view = $form->createView();

        $this->assertTrue($view->vars['perspective_enabled']);
        $this->assertEquals([], $view->vars['perspective_thresholds']);
        $this->assertNull($view->vars['perspective_language']);
    }

    public function testCustomOptions(): void
    {
        $thresholds = ['TOXICITY' => 0.7, 'PROFANITY' => 0.5];
        $language = 'cs';

        $form = $this->factory->create(PerspectiveTextType::class, null, [
            'perspective_thresholds' => $thresholds,
            'perspective_language' => $language,
        ]);

        $view = $form->createView();

        $this->assertEquals($thresholds, $view->vars['perspective_thresholds']);
        $this->assertEquals($language, $view->vars['perspective_language']);
    }

    public function testDisabledValidation(): void
    {
        $form = $this->factory->create(PerspectiveTextType::class, null, [
            'perspective_validation' => false,
        ]);

        $view = $form->createView();

        $this->assertFalse($view->vars['perspective_enabled']);
        $this->assertEmpty($view->vars['constraints'] ?? []);
    }

    public function testConstraintIsAddedWhenValidationEnabled(): void
    {
        $form = $this->factory->create(PerspectiveTextType::class, null, [
            'perspective_validation' => true,
            'perspective_thresholds' => ['TOXICITY' => 0.8],
        ]);

        $view = $form->createView();

        $this->assertTrue($view->vars['perspective_enabled']);
        $this->assertNotEmpty($view->vars['constraints']);

        $constraint = $view->vars['constraints'][0];
        $this->assertInstanceOf(PerspectiveContent::class, $constraint);
        $this->assertEquals(['TOXICITY' => 0.8], $constraint->getThresholds());
    }

    public function testCustomMessage(): void
    {
        $customMessage = 'Your content violates our community standards.';

        $form = $this->factory->create(PerspectiveTextType::class, null, [
            'perspective_message' => $customMessage,
        ]);

        $view = $form->createView();

        $constraint = $view->vars['constraints'][0];
        $this->assertInstanceOf(PerspectiveContent::class, $constraint);
        $this->assertEquals($customMessage, $constraint->message);
    }

    public function testFormTypeParent(): void
    {
        $type = new PerspectiveTextType();
        $this->assertEquals(TextareaType::class, $type->getParent());
    }

    public function testBlockPrefix(): void
    {
        $type = new PerspectiveTextType();
        $this->assertEquals('perspective_text', $type->getBlockPrefix());
    }
}
