<?php

namespace App\Form\Type;

use App\Entity\Review;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

class ReviewType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $fieldNameSuffix = $options['edit_mode'] ? '1' : '';

        $builder
            ->add('film' . $fieldNameSuffix, TextType::class)
            ->add('rating' . $fieldNameSuffix, IntegerType::class)
            ->add('comments' . $fieldNameSuffix, TextType::class)
            ->add('created_at' . $fieldNameSuffix, DateTimeType::class)
            ->add('save' . $fieldNameSuffix, SubmitType::class);
    }
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Review::class,
            'edit_mode' => false,
        ]);
    }
}