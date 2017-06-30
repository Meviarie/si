<?php
/**
 * Role type.
 */
namespace Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class RoleType.
 *
 * @package Form
 */
class RoleType extends AbstractType
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
            'name',
            ChoiceType::class,
            [
                'choices'   => array('Admin' => '1', 'User' => '2'),
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
        return 'role_type';
    }
}