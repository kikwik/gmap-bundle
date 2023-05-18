<?php

namespace Kikwik\GmapBundle\Form\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddressAutocompleteType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $allowedAutocompleteFields = ['street','streetNumber','zipCode','city','province','region','country','latitude','longitude'];

        $resolver->setDefault('autocomplete_fields', $allowedAutocompleteFields);
        $resolver->setAllowedTypes('autocomplete_fields', ['array']);
        $resolver->setAllowedValues('autocomplete_fields',function($value) use ($allowedAutocompleteFields){
            foreach($value as $fieldName)
            {
                if(!in_array($fieldName, $allowedAutocompleteFields))
                    return false;
            }
            return true;
        });
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('autocomplete',TextType::class, [
            'label'=>false,
            'attr' => [
                'class'=> 'js-autocomplete',
            ]
        ]);

        foreach($options['autocomplete_fields'] as $autocompleteFieldName)
        {
            $builder->add($autocompleteFieldName,HiddenType::class,[
                'attr' => [
                    'class'=> 'js-'.$autocompleteFieldName,
                ]
            ]);
        }
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        // set the required input class
        $class = $view->vars['attr']['class'] ?? '';
        $class .= ' js-kw-gmap-autocomplete';
        $view->vars['attr']['class'] = trim($class);
    }
}