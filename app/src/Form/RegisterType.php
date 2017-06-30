<?php
/**
 * Register form.
 */

namespace Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class LoginType
 *
 * @package Form
 */
class RegisterType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'login',
            TextType::class,
            [
                'label' => 'label.login',
                'required' => true,
                'attr' => [
                    'max_length' => 20,

                ],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(
                        [
                            'min' => 8,
                            'max' => 20,
                        ]
                    ),
                ],
            ]
        );
        $builder->add(
            'password',
            PasswordType::class,
            [
                'label' => 'label.password',
                'required' => true,
                'attr' => [
                    'max_length' => 32,

                ],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(
                        [
                            'min' => 8,
                            'max' => 32,
                        ]
                    ),
                ],
            ]
        );
        $builder->add(
            'repeat_password',
            PasswordType::class,
            [
                'label' => 'label.repeat_password',
                'required' => true,
                'attr' => [
                    'max_length' => 32,

                ],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(
                        [
                            'min' => 8,
                            'max' => 32,
                        ]
                    ),
                ],
            ]
        );
        $builder->add(
            'mail',
            EmailType::class,
            [
                'label' => 'label.mail',
                'required' => true,
                'attr' => [
                    'max_length' => 45,
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(
                        [
                            'min' => 3,
                            'max' => 45,
                        ]
                    ),
                    /*
                    new Assert\mail(
                        ['groups' => ['contact-default']]
                    ),
                    */
                ],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'register_type';
    }
}