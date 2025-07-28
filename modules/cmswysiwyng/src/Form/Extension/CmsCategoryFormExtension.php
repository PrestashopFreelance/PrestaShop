<?php
declare(strict_types=1);

namespace YourVendor\Cmswysiwyng\Form\Extension;

use PrestaShopBundle\Form\Admin\Improve\Design\Pages\CmsPageCategoryType;
use PrestaShopBundle\Form\Admin\Type\TranslatableType;
use PrestaShopBundle\Form\Admin\Type\FormattedTextareaType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Db;
use Tools;

class CmsCategoryFormExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [CmsPageCategoryType::class];
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($builder->has('description')) {
            $orig = $builder->get('description')->getFormConfig()->getOptions();
            $builder->remove('description');


            $builder->add('description', TranslatableType::class, array_merge($orig, [
                'required' => false,
                'row_attr' => array_merge($orig['row_attr'] ?? [], ['class' => 'd-none']),
            ]));
        }

        $builder->add('my_description', TranslatableType::class, [
            'label'    => 'Extra description',
            'type'     => FormattedTextareaType::class,
            'required' => false,

        ]);

            
        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {

            $form = $event->getForm();
            $idCategory = (int) Tools::getValue('id_cms_category');
            $rows = Db::getInstance()->executeS(
                'SELECT id_lang, my_description
                 FROM `' . _DB_PREFIX_ . 'cms_category_lang`
                 WHERE id_cms_category = ' . $idCategory
            );

            $translations = [];
            foreach ($rows as $row) {
                $translations[(int)$row['id_lang']] = $row['my_description']; 
            }
            $form->get('my_description')->setData($translations);
        });
    }

}