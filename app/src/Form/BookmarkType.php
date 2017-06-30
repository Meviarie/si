<?php
/**
 * Bookmark type.
 */
namespace Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class BookmarkType.
 *
 * @package Form
 */
class BookmarkType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'bookmark',
            TextType::class,
            [
                'label' => 'label.title_bookmark',
                'required' => true,
                'attr' => [
                    'max_length' => 128,
                ],
                'constraints' => [
                    new Assert\NotBlank(
                        ['groups' => ['bookmark-default']]
                    ),
                    new Assert\Length(
                        [
                            'groups' => ['bookmark-default'],
                            'min' => 3,
                            'max' => 128,
                        ]
                    ),
                ],
            ]
        );
        $builder->add(
            'url',
            UrlType::class,
            [
                'label' => 'label.url_bookmark',
                'required' => true,
                'attr' => [
                    'max_length' => 128,
                ],
                'constraints' => [
                    new Assert\NotBlank(
                        ['groups' => ['bookmark-default']]
                    ),
                    new Assert\Length(
                        [
                            'groups' => ['bookmark-default'],
                            'min' => 3,
                            'max' => 128,
                        ]
                    ),
                    new Assert\Url(
                        ['groups' => ['bookmark-default']]
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
                'validation_groups' => 'bookmark-default',
                'tag_repository' => null,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'bookmark_type';
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