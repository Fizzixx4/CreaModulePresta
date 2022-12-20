<?php

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class Zztop extends Module implements WidgetInterface{

    private $templateFile;//fichier de template

    public function __construct(){
        $this-> name = 'zztop';// nom du module
        $this->tab; //catégorie du module, si rien va dans Autres
        $this->version = '1.0.0';//version
        $this->author = 'GK';//auteur
        $this->need_instance = 1;//est-ce que le module a besoin d'une instance de la classe
        $this->bootstrap = true;//bootstrap activé ou non
        parent::__construct();

        $this->displayName = $this->trans('My first module', [],'Module.Zztop.Admin');//affichage du non et on traduit
        $this->description = $this->trans('My first module description', [],'Module.Zztop.Admin');//on récupère la descritpion et on la passe dans trans pour la traduction
        $this->confirmUninstall = 'Vous désinstallez mon super module. Êtes-vous sûr?';//on peut ne pas traduire

        $this->templateFile = 'module:zztop/template/views/front.tpl';//chemin pour aller chercher le template via le chemin
    }

    public function install(){
        return parent::install()
        && $this->registerHook('displayHome')
        && $this->registerHook('header')//permet de rajouter des fichiers js ou css
        && Configuration::updateValue('ZZTOP_TITLE', 'Titre')//check si dans la table configuration un  ZZTOP_TITLE existe. Si il existe il le met à jour sinon il le crée
        && Configuration::updateValue('ZZTOP_SUBTITLE', 'Sous-titre')
        && Configuration::updateValue('ZZTOP_DESCRIPTION', 'Description');
    }

    public function uninstall(){
        return parent::uninstall()
        && $this->unregisterHook('displayHome');
    }

    //Dans notre cas, pas nécessaire car on est sur un module d'affichage
    // public function hookDisplayHome($params){//lancé quand le hook sera déclenché
    //     $this->smarty->assign([
    //         'title' => Configuration::get('ZZTOP_TITLE'),//ce qui sera envoyé dans le template
    //         'description' => '<h2>Une description</h2>'
    //     ]);
    //     return $this->fetch($this->templateFile);
    // }

    public function hookHeader(){
        $this->context->controller->registerStylesheet(
            'module-zztop-style',
            'modules/'.$this->name.'/template/views/assets/main.css',
            [
                'media' => 'all',
                'priority' => 200
            ]
        );
    }

    public function getWidgetVariables($hookName, array $configuration){//Récupère les variables sur la BDD pour les utiliser dans renderWidget
        // $sql = "select * from  "._DB_PREFIX_."product";
        // $products = Db::getInstance()->executeS($sql);//retourne un tableau du résultat de la reqûete
        // foreach ($products as $product){
        //     $product = new Product($product['id_product']);//on les crée en objet par rapport à ce que la requête nous a donné
        //     $product->description_short[1] = 'vide'; //le 1 est l'id de la langue
        //     $product->save();
        // }
        // $product = new Product();
        // $product->reference = 'RefNew';
        // $product->id_category_default = 2;
        // $product->description_short[1] = 'Nouveau produit';
        // $product->price = 15;
        // $product->active = 1;
        // $product->save();
        return [
            'title' => Configuration::get('ZZTOP_TITLE'),
            'subtitle' => Configuration::get('ZZTOP_SUBTITLE'),
            'description' => Configuration::get('ZZTOP_DESCRIPTION'),
            //'products' => $products
            'link' => Context::getContext()->link->getModuleLink('zztop','pagezz')
        ];
    }

    public function renderWidget($hookName, array $configuration){//Envoie les variables récupérées par getWidgetVariables dans le template
        $templateVars = $this->getWidgetVariables($hookName, $configuration);
        $this->smarty->assign($templateVars);
        return $this->fetch($this->templateFile);
    } 

    public function getContent(){ //son rôle est de rajouter un bouton configurer pour accéder à un formulaire pour paramétrer le module
        $output = $this->post_validate();
        return $output.$this->renderForm();
    }

    private function post_validate(){//Check de validation pour le formulaire
        $output = '';
        $errors = [];
        if(Tools::isSubmit('submitZZ')){//Check si le formulaire a été submit
            $title = Tools::getValue('zztitle');//récupère la valeur du champ du formulaire, de manière générale la fonction va chercher la valeur par rapport à la clé en paramètre dans le $_GET ou $_POST
            $subtitle = Tools::getValue('zzsubtitle');
            $description = Tools::getValue('zzdescription');
            if($title === ''){
                $errors[] = 'Le champs title est obligatoire';
            }
            if($subtitle === ''){
                $errors[] = 'Le champs subtitle est obligatoire';
            }
            if($description === ''){
                $errors[] = 'Le champs description est obligatoire';
            }
            if(count($errors) > 0){
                $output = $this->displayError(implode('<br>',$errors));//Affiche un message d'erreur
            }
            else{
                Configuration::updateValue('ZZTOP_TITLE',$title);//l'envoie en BDD
                Configuration::updateValue('ZZTOP_SUBTITLE',$subtitle);
                Configuration::updateValue('ZZTOP_DESCRIPTION',$description,true);
                $output = $this->displayConfirmation('Le formulaire est enregistré');//Affiche un message de confirmation
            }
        }
        return $output;
    }

    private function renderForm(){//Construction du formulaire de configuration
        $fields_form = [
            'form' =>[
                'legend' => [
                    'title' => $this->trans('Settings', [], 'Admin.Global'),
                    'icon' => 'icon.org'
                ],
                'description' => $this->trans('Display a title in front page',[], 'Modules.Zztop.Admin'),
                'input' => [
                    [
                        'type' => 'text',
                        'name' => 'zztitle',
                        'label' => $this->trans('Title', [], 'Modules.Zztop.Admin'),
                        'required' => 1
                    ],
                    [
                        'type' => 'text',
                        'name' =>'zzsubtitle',
                        'label' => $this->trans('Sub Title', [], 'Modules.Zztop.Admin'),
                        'required' => 1
                    ],
                    [
                        'type' => 'textarea',
                        'label' => $this->trans('Text block', [], 'Modules.Customtext.Admin'),
                        'name' => 'zzdescription',
                        'cols' => 40,
                        'rows' => 10,
                        'class' => 'rte',
                        'autoload_rte' => true,
                        'required' => 1
                    ]
                ],
                'submit' => [
                    'title' => $this->trans('Save',[],'Admin.Actions'),
                ]
            ]
        ];

        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitZZ';//Important => donner un nom au submit_action
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValue(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$fields_form]);
    }

    private function getConfigFieldsValue(){//Va chercher les valeurs de config sur la BDD
        return [
            'zztitle' => Tools::getValue('zztitle', Configuration::get('ZZTOP_TITLE')),
            'zzsubtitle' => Tools::getValue('zzsubtitle',Configuration ::get('ZZTOP_SUBTITLE')),
            'zzdescription' => Tools::getValue('zzdescription',Configuration ::get('ZZTOP_DESCRIPTION'))
        ];
    }
}