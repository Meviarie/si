<?php
/**
 * Note type.
 */
namespace Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class ContactsType.
 *
 * @package Form
 */
class NoteType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
       $builder->add(
            'content',
            TextType::class,
            [
                'label' => 'label.content_note',
                'required' => true,
                'attr' => [
                    'max_length' => 120,
                ],
                'constraints' => [
                    new Assert\NotBlank(
                        ['groups' => ['note-default']]
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
                'validation_groups' => 'note-default',
                'tag_repository' => null,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'note_type';
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