<?php

namespace Drupal\lesroidelareno\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\SortArray;
use Drupal\lesroidelareno\LesroidelarenoFormDonneeSite;
use Drupal\lesroidelareno\Services\FormDonneeSiteVar;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Url;

/**
 * Form controller for Donnee site internet des utilisateurs edit forms.
 *
 * @ingroup lesroidelareno
 */
class DonneeSiteInternetEntityForm extends ContentEntityForm {
  
  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;
  
  /**
   *
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    $instance = parent::create($container);
    $instance->account = $container->get('current_user');
    return $instance;
  }
  
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    return $form;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function buildForm0(array $form, FormStateInterface $form_state) {
    // vuejs_dev
    // $form['#attached']['library'][] = 'login_rx_vuejs/vuejs_dev';
    $formParents = [];
    /**
     * On verifie si l'entité existe deja.
     */
    if ($form_state->has(FormDonneeSiteVar::$entity)) {
      $entity = $form_state->get(FormDonneeSiteVar::$entity);
      /**
       *
       * @var EntityFormDisplay $form_display
       */
      $form_display = $form_state->get(FormDonneeSiteVar::$entity_display);
      $form_display->buildForm($entity, $formParents, $form_state);
    }
    else {
      $form_state->set(FormDonneeSiteVar::$entity, $this->entity);
      $formParents = parent::buildForm($form, $form_state);
      $form_display = EntityFormDisplay::collectRenderDisplay($this->entity, 'default');
      $form_state->set(FormDonneeSiteVar::$entity_display, $form_display);
    }
    // $fieldTem = $this->entity->getFieldDefinitions()['type_site'];
    // dump($fieldTem->getSetting('handler_settings'));
    /* @var \Drupal\lesroidelareno\Entity\DonneeSiteInternetEntity $entity */
    
    /**
     * On sauvegarde les champs qui vont etre utiliser durant les etapes.
     */
    // if (!$form_state->has(FormDonneeSiteVar::$key_dsi_form)) {
    $dsi_form = [];
    // on retire les elements qui ne correspondent pas à un champs.
    foreach ($formParents as $key => $field) {
      if (!empty($field['#type']) && !empty($field['widget'])) {
        $dsi_form[$key] = $field;
      }
    }
    // On reordonne les champs par ordre de poids.
    uasort($dsi_form, [
      SortArray::class,
      'sortByWeightProperty'
    ]);
    $form_state->set(FormDonneeSiteVar::$key_dsi_form, $dsi_form);
    // dump($this->entity->get('contenus_transferer')->getSettings());
    // }
    
    // dump($form_state->getStorage());
    $form['donnee-internet-entity'] = [
      '#type' => 'html_tag',
      '#tag' => 'section',
      "#attributes" => [
        'id' => 'donnee-internet-entity-next-field',
        'class' => [
          'step-donneesite',
          'mx-auto',
          'text-center'
        ]
      ],
      '#weight' => -10
    ];
    
    //
    if ($form_state->has(FormDonneeSiteVar::$key_steps)) {
      LesroidelarenoFormDonneeSite::getFieldForStep($form['donnee-internet-entity'], $form_state);
      if (array_key_last($form_state->get(FormDonneeSiteVar::$key_steps)) == 'login') {
        $form['donnee-internet-entity'][] = [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => [
            'class' => [
              'step-donneesite--header',
              'with-tablet',
              'mx-auto',
              'text-center'
            ]
          ],
          [
            '#type' => 'html_tag',
            '#tag' => 'h2',
            '#value' => 'Veillez vous connectez afin de sauvegarder vos données',
            '#attributes' => [
              'class' => [
                'step-donneesite--title'
              ]
            ]
          ],
          [
            '#type' => 'html_tag',
            '#tag' => 'div',
            '#attributes' => [
              'id' => 'appLoginRegister'
            ],
            '#value' => 'ff',
            '#weight' => 10
          ]
        ];
        // $form['donnee-internet-entity']['#attached']['library'][] = 'login_rx_vuejs/login_register';
        // $form['donnee-internet-entity']['#attached']['library'][] = 'login_rx_vuejs/login_register_small_components';
        $form['donnee-internet-entity']['#attached']['library'][] = "lesroidelareno/lesroidelareno_login";
      }
    }
    else
      LesroidelarenoFormDonneeSite::getHeader('ctm_description', $form['donnee-internet-entity']);
    
    if ($form_state->get(FormDonneeSiteVar::$laststep)) {
      LesroidelarenoFormDonneeSite::getFooter('ctm_footer', $form['donnee-internet-entity']);
    }
    
    //
    $form['donnee-internet-entity']['container_buttons'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => [
          'd-flex',
          'justify-content-around',
          'align-items-center',
          'step-donneesite--submit'
        ]
      ],
      '#weight' => 45
    ];
    //
    if ($form_state->has(FormDonneeSiteVar::$key_steps) && count($form_state->get(FormDonneeSiteVar::$key_steps)) > 1) {
      $form['donnee-internet-entity']['container_buttons']['previews'] = [
        '#type' => 'submit',
        '#value' => 'Precedent',
        '#button_type' => 'secondary',
        '#submit' => [
          [
            $this,
            'selectPreviewsFieldSubmit'
          ]
        ],
        '#ajax' => [
          'callback' => '::selectPreviewsFieldSCallback',
          'wrapper' => 'donnee-internet-entity-next-field',
          'effect' => 'fade'
        ],
        '#attributes' => [
          'class' => [
            'd-inline-block',
            'w-auto',
            'btn btn-secondary'
          ]
        ]
      ];
    }
    if (!$form_state->get(FormDonneeSiteVar::$laststep)) {
      $form['donnee-internet-entity']['container_buttons']['next'] = [
        '#type' => 'submit',
        '#value' => 'Suivant',
        '#button_type' => 'secondary',
        '#submit' => [
          [
            $this,
            'selectNextFieldSubmit'
          ]
        ],
        '#ajax' => [
          'callback' => '::selectNextFieldSCallback',
          'wrapper' => 'donnee-internet-entity-next-field',
          'effect' => 'fade'
        ],
        '#attributes' => [
          'class' => [
            'd-inline-block',
            'w-auto'
          ]
        ]
      ];
    }
    // save datas
    else {
      $form['donnee-internet-entity']['container_buttons']['submit'] = [
        '#type' => 'submit',
        '#value' => 'Enregistre les données',
        '#button_type' => 'secondary',
        '#submit' => [
          [
            $this,
            'saveSubmit'
          ]
        ],
        '#ajax' => [
          'callback' => '::selectNextFieldSCallback',
          'wrapper' => 'donnee-internet-entity-next-field',
          'effect' => 'fade'
        ],
        '#attributes' => [
          'class' => [
            'd-inline-block',
            'w-auto'
          ]
        ]
      ];
    }
    
    //
    // if (!$this->entity->isNew()) {
    // $form['new_revision'] = [
    // '#type' => 'checkbox',
    // '#title' => $this->t('Create new revision'),
    // '#default_value' => FALSE,
    // '#weight' => 10
    // ];
    // }
    // $this->messenger()->addMessage('buildForm ffff', 'status', true);
    return $form;
  }
  
  public function selectNextFieldSCallback(array $form, FormStateInterface $form_state) {
    return $form['donnee-internet-entity'];
  }
  
  public function selectPreviewsFieldSCallback(array $form, FormStateInterface $form_state) {
    return $form['donnee-internet-entity'];
  }
  
  /**
   *
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public function selectNextFieldSubmit($form, FormStateInterface $form_state) {
    //
    if ($form_state->has(FormDonneeSiteVar::$key_steps)) {
      if ($form_state->has(FormDonneeSiteVar::$entity)) {
        $entity = $form_state->get(FormDonneeSiteVar::$entity);
        /**
         *
         * @var EntityFormDisplay $form_display;
         */
        $form_display = $form_state->get(FormDonneeSiteVar::$entity_display);
        /**
         * On retire les arrays vide, cela semble etre un bug.
         *
         * @var array $files
         */
        $files = $entity->get('contenus_transferer')->getValue();
        $new_files = [];
        foreach ($files as $file) {
          if (!empty($file))
            $new_files[] = $file;
        }
        $entity->set('contenus_transferer', $new_files);
        $form_display->extractFormValues($entity, $form, $form_state);
        $form_state->set(FormDonneeSiteVar::$entity, $entity);
      }
    }
    else {
      $form_state->set(FormDonneeSiteVar::$key_steps, []);
    }
    $form_state->set('step_direction', '+');
    $form_state->setRebuild(true);
  }
  
  public function selectNextFieldSubmitOld($form, FormStateInterface $form_state) {
    // if (!empty($form['donnee-internet-entity']['name']))
    // $this->messenger()->addStatus('selectNextFieldSubmit :: ' . json_encode($form_state->getValue('name')), true);
    // on determine l'etape.
    $element = $form_state->getTriggeringElement();
    if (!empty($element['#name']) && $element['#name'] == 'op') {
      if ($form_state->has(FormDonneeSiteVar::$key_steps)) {
        $values = $form_state->getUserInput();
        $steps = $form_state->get(FormDonneeSiteVar::$key_steps);
        $fieldsValue = $form_state->get(FormDonneeSiteVar::$fields_value);
        $dsi_form = $form_state->get(FormDonneeSiteVar::$key_dsi_form);
        $steppers = LesroidelarenoFormDonneeSite::getStepper();
        $k_last = array_key_last($steps);
        if (!empty($steppers[$k_last]['keys']))
          foreach ($steppers[$k_last]['keys'] as $fieldName) {
            if (isset($values[$fieldName])) {
              // $this->messenger()->addStatus($fieldName . ' :: ' . json_encode($values));
              $fieldsValue[$fieldName] = $values[$fieldName];
              if (!empty($form['donnee-internet-entity'][$fieldName])) {
                // dump($form['donnee-internet-entity'][$fieldName]);
                // debugLog::$max_depth = 7;
                // debugLog::kintDebugDrupal($form['donnee-internet-entity'][$fieldName], $fieldName, true);
                $dsi_form[$fieldName] = $form['donnee-internet-entity'][$fieldName];
                /**
                 * Il faut nettoyer le #value, car si cette valeur est remplie on ne pourra plus mettre à jour.
                 * De plus, on doit mettre la valeur encours dans default value, pour mettre de voir la precedente si l'utilisateur revient en arriere.
                 */
                if (isset($dsi_form[$fieldName]['widget'][0]['value']['#value'])) {
                  $dsi_form[$fieldName]['widget'][0]['value']['#default_value'] = $dsi_form[$fieldName]['widget'][0]['value']['#value'];
                  unset($dsi_form[$fieldName]['widget'][0]['value']['#value']);
                }
                elseif (isset($dsi_form[$fieldName]['widget'][0]['color']['#value'])) {
                  $dsi_form[$fieldName]['widget'][0]['color']['#default_value'] = $dsi_form[$fieldName]['widget'][0]['color']['#value'];
                  unset($dsi_form[$fieldName]['widget'][0]['color']['#value']);
                }
                // elseif (isset($dsi_form[$fieldName]['widget'][0]['target_id']['#value'])) {
                // $dsi_form[$fieldName]['widget'][0]['target_id']['#default_value'] = $dsi_form[$fieldName]['widget'][0]['target_id']['#value'];
                // unset($dsi_form[$fieldName]['widget'][0]['target_id']['#value']);
                // }
                elseif (isset($dsi_form[$fieldName]['widget']['value']['#value'])) {
                  $dsi_form[$fieldName]['widget']['value']['#default_value'] = $dsi_form[$fieldName]['widget']['value']['#value'];
                  unset($dsi_form[$fieldName]['widget']['value']['#value']);
                }
                else {
                  // debugLog::$max_depth = 7;
                  // debugLog::kintDebugDrupal($form['donnee-internet-entity'][$fieldName], $fieldName, true);
                }
              }
              else {
                // dump('error :: ' . $fieldName);
              }
            }
          }
        /**
         * Permet de mettre à jour le champs.
         * ( Permet de concerver la valeur selectionner par l'utilisateur ).
         */
        $form_state->set(FormDonneeSiteVar::$key_dsi_form, $dsi_form);
        
        $form_state->set(FormDonneeSiteVar::$key_steps, $steps);
        
        $form_state->set(FormDonneeSiteVar::$fields_value, $fieldsValue);
      }
      else {
        $form_state->set(FormDonneeSiteVar::$key_steps, []);
      }
    }
    
    $form_state->set('step_direction', '+');
    $form_state->setRebuild(true);
  }
  
  public function selectPreviewsFieldSubmit($form, FormStateInterface $form_state) {
    $form_state->set('step_direction', '-');
    // $this->messenger()->addStatus('selectPreviewsFieldSubmit :: ' . json_encode($form_state->getValues()), true);
    $form_state->setRebuild(true);
  }
  
  public function saveSubmit0($form, FormStateInterface $form_state) {
    $entity = $form_state->get(FormDonneeSiteVar::$entity);
    $entity->save();
    
    $form_state->set(FormDonneeSiteVar::$entity, $entity);
    $this->messenger()->addStatus('Vos données ont été sauvegardées');
    // $form_state->setRebuild(true);
    $response = new AjaxResponse();
    if ($form_state->hasAnyErrors()) {
      // Do validation stuff here
      // ex: $response->addCommand(new ReplaceCommand... on error fields
    }
    else {
      // Do submit stuff here
      $this->messenger()->addStatus('rediction encours ... current');
      // $url = Url::fromRoute('multi_sitemap.render6');
      // $command = new RedirectCommand($url->toString());
      // $response->addCommand($command);
      
      $response = new AjaxResponse();
      $currentURL = Url::fromRoute('<current>');
      $response->addCommand(new RedirectCommand($currentURL->toString()));
      return $response;
    }
  }
  
  // public function file_managed_file_submit($form, FormStateInterface $form_state) {
  // // debugLog::kintDebugDrupal($form, 'file_managed_file_submit', true);
  // }
  
  /**
   * La validation se ferra à la fin.
   *
   * {@inheritdoc}
   * @see \Drupal\Core\Entity\ContentEntityForm::validateForm()
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $entity = parent::validateForm($form, $form_state);
    return $entity;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function save0(array $form, FormStateInterface $form_state) {
    $this->messenger()->addMessage('save entity', true);
    $entity = $this->entity;
    
    // Save as a new revision if requested to do so.
    if (!$form_state->isValueEmpty('new_revision') && $form_state->getValue('new_revision') != FALSE) {
      $entity->setNewRevision();
      
      // If a new revision is created, save the current user as revision author.
      $entity->setRevisionCreationTime($this->time->getRequestTime());
      $entity->setRevisionUserId($this->account->id());
    }
    else {
      $entity->setNewRevision(FALSE);
    }
    
    $status = parent::save($form, $form_state);
    
    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Donnee site internet des utilisateurs.', [
          '%label' => $entity->label()
        ]));
        break;
      
      default:
        $this->messenger()->addMessage($this->t('Saved the %label Donnee site internet des utilisateurs.', [
          '%label' => $entity->label()
        ]));
    }
    $form_state->setRedirect('entity.donnee_internet_entity.canonical', [
      'donnee_internet_entity' => $entity->id()
    ]);
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    
    // Save as a new revision if requested to do so.
    if (!$form_state->isValueEmpty('new_revision') && $form_state->getValue('new_revision') != FALSE) {
      $entity->setNewRevision();
      
      // If a new revision is created, save the current user as revision author.
      $entity->setRevisionCreationTime($this->time->getRequestTime());
      $entity->setRevisionUserId($this->account->id());
    }
    else {
      $entity->setNewRevision(FALSE);
    }
    
    $status = parent::save($form, $form_state);
    
    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Site internet entity.', [
          '%label' => $entity->label()
        ]));
        break;
      
      default:
        $this->messenger()->addMessage($this->t('Saved the %label Site internet entity.', [
          '%label' => $entity->label()
        ]));
    }
    $form_state->setRedirect('entity.donnee_internet_entity.canonical', [
      'donnee_internet_entity' => $entity->id()
    ]);
  }
  
}
