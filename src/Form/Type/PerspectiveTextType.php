<?php

declare(strict_types=1);

namespace Freema\PerspectiveApiBundle\Form\Type;

use Freema\PerspectiveApiBundle\Validator\PerspectiveContent;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PerspectiveTextType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'perspective_validation' => true,
            'perspective_thresholds' => [],
            'perspective_language' => null,
            'perspective_message' => null,
        ]);

        $resolver->setAllowedTypes('perspective_validation', 'bool');
        $resolver->setAllowedTypes('perspective_thresholds', 'array');
        $resolver->setAllowedTypes('perspective_language', ['null', 'string']);
        $resolver->setAllowedTypes('perspective_message', ['null', 'string']);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['perspective_enabled'] = $options['perspective_validation'];
        $view->vars['perspective_thresholds'] = $options['perspective_thresholds'];
        $view->vars['perspective_language'] = $options['perspective_language'];
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        if (!$options['perspective_validation']) {
            return;
        }

        // Add perspective validation constraint
        $constraintOptions = [];

        if (!empty($options['perspective_thresholds'])) {
            $constraintOptions['thresholds'] = $options['perspective_thresholds'];
        }

        if ($options['perspective_language'] !== null) {
            $constraintOptions['language'] = $options['perspective_language'];
        }

        if ($options['perspective_message'] !== null) {
            $constraintOptions['message'] = $options['perspective_message'];
        }

        // Add constraint to form constraints
        if (!isset($view->vars['constraints'])) {
            $view->vars['constraints'] = [];
        }

        $view->vars['constraints'][] = new PerspectiveContent($constraintOptions);
    }

    public function getParent(): string
    {
        return TextareaType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'perspective_text';
    }
}
