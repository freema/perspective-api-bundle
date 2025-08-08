<?php

declare(strict_types=1);

namespace App\Form;

use Freema\PerspectiveApiBundle\Form\Type\PerspectiveTextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('author', TextType::class, [
                'required' => true,
                'attr' => [
                    'placeholder' => 'Your name',
                ],
            ])
            ->add('email', EmailType::class, [
                'required' => false,
                'attr' => [
                    'placeholder' => 'your.email@example.com (optional)',
                ],
            ])
            ->add('content', PerspectiveTextType::class, [
                'perspective_thresholds' => [
                    'TOXICITY' => 0.7,
                    'PROFANITY' => 0.5,
                    'THREAT' => 0.4,
                    'INSULT' => 0.6,
                ],
                'perspective_language' => 'en',
                'perspective_message' => 'Please keep your comments respectful and constructive.',
                'attr' => [
                    'rows' => 5,
                    'placeholder' => 'Write your comment here...',
                    'class' => 'form-control',
                ],
                'help' => 'Your comment will be automatically checked for inappropriate content.',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Post Comment',
                'attr' => [
                    'class' => 'btn btn-primary',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
        ]);
    }
}
