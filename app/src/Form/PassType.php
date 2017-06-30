<?php
/**
 * Password type.
 */
namespace Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\FormEvents;

/**
 * Class PasswordType.
 *
 * @package Form
 */
class PassType extends AbstractType
{
    /**
     * Build form.
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
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
                            'min' => 4,
                            'max' => 32,
                        ]
                    ),
                ],
            ]
        );
    }


    /**
     * Get prefix
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'password_type';
    }
}