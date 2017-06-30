<?php
/**
 * Task type.
 */
namespace Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class ContactsType.
 *
 * @package Form
 */
class TaskType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'task',
            TextType::class,
            [
                'label' => 'label.title_task',
                'required' => true,
                'attr' => [
                    'max_length' => 45,
                ],
                'constraints' => [
                    new Assert\NotBlank(
                        ['groups' => ['task-default']]
                    ),
                    new Assert\Length(
                        [
                            'groups' => ['task-default'],
                            'min' => 3,
                            'max' => 45,
                        ]
                    ),
                ],
            ]
        );
        $builder->add(
            'deadline',
            DateTimeType::class,
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
                'validation_groups' => 'task-default',
                'tag_repository' => null,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'task_type';
    }

    protected function prepareTagsForChoices($tagRepository)
    {
        $tags = $tagRepository->findAll();
        $choices = [];

        foreach ($tags as $tag) {
            $choices[$tag['name']] = $tag['id'];
        }

        return $choices;
    }
}