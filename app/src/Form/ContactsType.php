<?php
/**
 * Contacts type.
 */
namespace Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class ContactsType.
 *
 * @package Form
 */
class ContactsType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'name',
            TextType::class,
            [
                'label' => 'label.name_contact',
                'required' => true,
                'attr' => [
                    'max_length' => 128,
                ],
                'constraints' => [
                    new Assert\NotBlank(
                        ['groups' => ['contact-default']]
                    ),
                    new Assert\Length(
                        [
                            'groups' => ['contact-default'],
                            'min' => 3,
                            'max' => 128,
                        ]
                    ),
                ],
            ]
        );
        $builder->add(
            'surname',
            TextType::class,
            [
                'label' => 'label.surname_contact',
                'required' => true,
                'attr' => [
                    'max_length' => 128,
                ],
                'constraints' => [
                    new Assert\NotBlank(
                        ['groups' => ['contact-default']]
                    ),
                    new Assert\Length(
                        [
                            'groups' => ['contact-default'],
                            'min' => 3,
                            'max' => 128,
                        ]
                    ),
                ],
            ]
        );
        $builder->add(
            'phone_number',
            NumberType::class,
            [
                'label' => 'label.number_contact',
                'required' => true,
                'attr' => [
                    'max_length' => 24,
                ],
                'constraints' => [
                    new Assert\NotBlank(
                        ['groups' => ['contact-default']]
                    ),
                    new Assert\Length(
                        [
                            'groups' => ['contact-default'],
                            'min' => 3,
                            'max' => 24,
                        ]
                    ),
                    /*
                    new Assert\Number(
                        ['groups' => ['contact-default']]
                    ),
                    */
                ],
            ]
        );
        $builder->add(
            'mail',
            EmailType::class,
            [
                'label' => 'label.mail_contact',
                'required' => true,
                'attr' => [
                    'max_length' => 45,
                ],
                'constraints' => [
                    new Assert\NotBlank(
                        ['groups' => ['contact-default']]
                    ),
                    new Assert\Length(
                        [
                            'groups' => ['contact-default'],
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
        $builder->add(
            'web_page',
            UrlType::class,
            [
                'label' => 'label.url_contact',
                'required' => true,
                'attr' => [
                    'max_length' => 128,
                ],
                'constraints' => [
                    new Assert\NotBlank(
                        ['groups' => ['contact-default']]
                    ),
                    new Assert\Length(
                        [
                            'groups' => ['contact-default'],
                            'min' => 3,
                            'max' => 128,
                        ]
                    ),
                    new Assert\Url(
                        ['groups' => ['contact-default']]
                    ),
                ],
            ]
        );
        $builder->add(
            'tags',
            TextType::class,
            [
                'label' => 'label.tags',
                'required' => true,
                'attr' => [
                    'max_length' => 128,
                ],
            ]
        );
        $builder->get('tags')->addModelTransformer(
            new TagsDataTransformer($options['tag_repository'])
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'validation_groups' => 'contact-default',
                'tag_repository' => null,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'contact_type';
    }

    protected function prepareTagsForChoices($tagRepository)
    {
        $tags = $tagRepository->findAll();
        $choices = [];

        foreach ($tags as $tag) {
            $choices[$tag['name']] = $tag['idtags'];
        }

        return $choices;
    }
}