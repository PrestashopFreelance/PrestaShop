<?php
if (!defined('_PS_VERSION_')) {
    exit;
}
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
class Cmswysiwyng extends Module
{
    public function __construct()
    {
        $this->name = 'cmswysiwyng';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Your Name';
        $this->bootstrap = true;
        $this->ps_versions_compliancy = ['min' => '9.0.0', 'max' => '9.99.99'];

        parent::__construct();
        $this->displayName = $this->l('CMS WYSIWYG Extra Field');
        $this->description = $this->l('Adds an extra WYSIWYG description to CMS category edit form.');
    }

    /**
     * Install the module and add the custom column to the database.
     *
     * This method checks if the `my_description` column exists in the `cms_category_lang` table
     * and adds it during the installation process if it does not already exist.
     *
     * @return bool Success status of the installation process.
     */
    public function install()
    {
        // Check if the column already exists
        $columnExists = Db::getInstance()->executeS("
            SELECT COLUMN_NAME 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = '" . _DB_NAME_ . "' 
            AND TABLE_NAME = '" . _DB_PREFIX_ . "cms_category_lang' 
            AND COLUMN_NAME = 'my_description'
        ");

        $sql = '';
        if (empty($columnExists)) {
            $sql = "ALTER TABLE `" . _DB_PREFIX_ . "cms_category_lang` 
                    ADD COLUMN `my_description` TEXT NULL AFTER `description`";
        }

        return parent::install() 
            && $this->registerHook('displayOverrideTemplate')
            && $this->registerHook('actionAdminControllerSetMedia')
            && $this->registerHook('actionAfterCreateCmsPageCategoryFormHandler')
            && $this->registerHook('actionAfterUpdateCmsPageCategoryFormHandler')
            && ($sql === '' || Db::getInstance()->execute($sql));
    }

    /**
     * Uninstall the module and remove the custom column from the database.
     *
     * This method checks if the `my_description` column exists in the `cms_category_lang` table
     * and removes it during the uninstallation process.
     *
     * @return bool Success status of the uninstallation process.
     */
    public function uninstall()
    {
        // Check if the column exists before attempting to remove it
        $columnExists = Db::getInstance()->executeS("
            SELECT COLUMN_NAME 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = '" . _DB_NAME_ . "' 
            AND TABLE_NAME = '" . _DB_PREFIX_ . "cms_category_lang' 
            AND COLUMN_NAME = 'my_description'
        ");

        $sql = '';
        if (!empty($columnExists)) {
            $sql = "ALTER TABLE `" . _DB_PREFIX_ . "cms_category_lang` 
                    DROP COLUMN `my_description`";
        }

        return parent::uninstall() && ($sql === '' || Db::getInstance()->execute($sql));
    }

    /**
     * Hook to include custom JavaScript files in the admin controller.
     *
     * This method is used to add the TinyMCE JavaScript file to the admin controller,
     * enabling the WYSIWYG editor functionality for the custom description field.
     *
     * @return void
     */
    public function hookActionAdminControllerSetMedia()
    {
        // Add the TinyMCE JavaScript file to the admin controller
        $this->context->controller->addJS($this->_path . 'views/js/cmsedit.js');
    }

       public function hookActionAfterCreateCmsPageCategoryFormHandler(array $params)
    {
    
        if (isset($params['form_data']['my_description'])) {
            $id_cms_category = (int)$params['id'];
            $my_description = $params['form_data']['my_description'];
            $this->saveMyDescription($id_cms_category, $my_description);
        }
    }

    /**
     * Save changes after updating a category.
     *
     * @param array $params Parameters from the form handler
     * @return void
     */
    public function hookActionAfterUpdateCmsPageCategoryFormHandler(array $params)
    {

        if (isset($params['form_data']['my_description'])) {
            $id_cms_category = (int)$params['id'];
            $my_description = $params['form_data']['my_description'];
            $this->saveMyDescription($id_cms_category, $my_description);
        }
    }

    /**
     * Save my description for CMS category
     *
     * @param int $id_cms_category CMS category ID
     * @param array $my_description The description content for each language
     * @return bool Success status
     */
    private function saveMyDescription(int $id_cms_category, array $my_description)
    {
        $success = true;
        foreach ($my_description as $id_lang => $description) {
            $sql = 'UPDATE `' . _DB_PREFIX_ . 'cms_category_lang` 
                    SET `my_description` = "' . pSQL($description, true) . '"
                    WHERE `id_cms_category` = ' . (int)$id_cms_category . ' 
                    AND `id_lang` = ' . (int)$id_lang;
            
            $success = $success && Db::getInstance()->execute($sql);
        }
        return $success;
    }

    /**
     * Hook to display additional content for a CMS category.
     *
     * This method retrieves a custom description (`my_description`) for a CMS category
     * from the database and renders it using a Twig template if the description exists.
     *
     * @param array $params An associative array of parameters passed to the hook:
     * - 'id_cms_category' (int): The ID of the CMS category.
     *
     * @return string The rendered HTML content if `my_description` exists, or an empty string otherwise.
     */

    public function hookDisplayOverrideTemplate(array $params)
    {
       
        if ($params['template_file'] !== 'cms/category') {
            return '';         
        }

      
        $idCategory = (int) Tools::getValue('id_cms_category');
        if (!$idCategory) {
            return '';
        }


        $idLang = (int) $this->context->language->id;
        $myDescription = (string) Db::getInstance()->getValue(
            (new DbQuery())
                ->select('ccl.`my_description`')
                ->from('cms_category_lang', 'ccl')
                ->where('ccl.`id_cms_category` = '.$idCategory)
                ->where('ccl.`id_lang` = '.$idLang)
        );

        $this->context->smarty->assign('my_description', $myDescription);

        return 'module:'.$this->name.'/views/templates/override/cms/category.tpl';
    }
    
}
