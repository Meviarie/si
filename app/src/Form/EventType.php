<?php
/**
 * Events type.
 */
namespace Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class ContactsType.
 *
 * @package Form
 */
class EventType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'event',
            TextType::class,
            [
                'label' => 'label.name_event',
                'required' => true,
                'attr' => [
                    'max_length' => 45,
                ],
                'constraints' => [
                    new Assert\NotBlank(
                        ['groups' => ['event-default']]
                    ),
                    new Assert\Length(
                        [
                            'groups' => ['event-default'],
                            'min' => 3,
                            'max' => 45,
                        ]
                    ),
                ],
            ]
        );
        $builder->add(
            'date',
            DateType::class,
            [
                //'data' => new \DateTime(),
                'label' => 'label.date_event',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(
                        ['groups' => ['event-default']]
                    ),
                    new Assert\Date(
                        ['groups' => ['event-default']]
                    )
                ],
            ]
        );
        $builder->add(
            'time',
            TimeType::class,
            [
                //'data' => new \DateTime(),
                'label' => 'label.time_event',
                'required' => true,
                'attr' => [
                    'format' => 'Y-m-d H:i:s',
                ],
                'constraints' => [
                    new Assert\NotBlank(
                        ['groups' => ['event-default']]
                    ),
                    new Assert\Time(
                        ['groups' => ['event-default']]
                    )
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
                'validation_groups' => 'event-default',
                'tag_repository' => null,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'event_type';
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