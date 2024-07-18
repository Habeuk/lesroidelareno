<?php

namespace Drupal\lesroidelareno\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\export_import_entities\Services\CleanConfigsTheme;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityFieldManager;

/**
 * Permet de supprimer les entites et les donfiguration en rapport avec un
 * theme, et de supprimer le theme par la meme occasion.
 * De plus: ce formulaire peut egalement servir si le formulaire definit au
 * niveau de Drupal\export_import_entities\Form\CleanConfigsThemeForm met assez
 * de temps pour traiter les données.
 *
 * @author Stephane
 *        
 */
class CleanConfigThemesAndEntitiesForm extends FormBase {
  protected static $field_domain_access = 'field_domain_access';
  /**
   *
   * @var \Drupal\export_import_entities\Services\CleanConfigsTheme
   */
  protected $CleanConfigsTheme;
  
  /**
   *
   * @var array
   */
  protected $ListEntities = [];
  
  function __construct(CleanConfigsTheme $CleanConfigsTheme, EntityFieldManager $EntityFieldManager) {
    $this->CleanConfigsTheme = $CleanConfigsTheme;
    $this->entityFieldManger = $EntityFieldManager;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('export_import_entities.clean_configs_theme'), $container->get('entity_field.manager'));
  }
  
  /**
   *
   * {@inheritdoc}
   * @see \Drupal\Core\Form\FormInterface::getFormId()
   */
  public function getFormId() {
    return 'lesroidelareno_cleanconfigthemesentitiesform';
  }
  
  /**
   * On recupere la dependances de configuration du theme,
   * Ensuite, on verifie si le theme a une entrée au niveau de l'entite
   * "config_theme_entity", SIOUI on cree un lien qui renvoit vers la page de
   * suppression ( car l'entité à un mecanisme de suppresion ).
   * SINON, on charge toutes le entites de content en relation avec le domaine
   * et on les supprimes.
   *
   * {@inheritdoc}
   * @see \Drupal\Core\Form\FormInterface::buildForm()
   */
  public function buildForm(array $form, FormStateInterface $form_state, $theme_name = null) {
    $configsDependencies = $this->CleanConfigsTheme->getConfigsDepenceForTheme($theme_name);
    $form['header'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => ' Theme to delete '
    ];
    $form['theme'] = [
      '#type' => 'textfield',
      '#default_value' => $theme_name,
      '#attributes' => [
        // 'read-only' => true,
        'readonly' => true,
        'maxlength' => 255
      ]
    ];
    //
    if (!empty($configsDependencies) && (count($configsDependencies) > 1 || $configsDependencies[0]['name'] != 'core.extension')) {
      $contents = $this->getListContentTodelete($theme_name);
      $nbre = 0;
      $form['contents'] = [
        '#type' => 'details',
        '#title' => 'Content du delete : ',
        '#tree' => true,
        '#open' => false
      ];
      foreach ($contents as $entity_type => $nodes) {
        $nbre += count($nodes);
        $form['contents'][$entity_type] = [
          '#type' => 'details',
          '#title' => $entity_type . ' : ' . count($nodes) . ' to delete.',
          '#tree' => true,
          '#open' => false
        ];
        foreach ($nodes as $key => $node) {
          /**
           *
           * @var \Drupal\Core\Entity\EditorialContentEntityBase $node
           */
          $form['contents'][$entity_type][$key] = [
            '#type' => 'textfield',
            '#title' => $node->id(),
            '#default_value' => $node->label(),
            '#attributes' => [
              'readonly' => true
            ],
            '#maxlength' => 255
          ];
        }
      }
      $form['contents']['#title'] .= ' (' . $nbre . ')';
      //
      $nbre = 0;
      $configs = $this->getConfigsTodelete($theme_name);
      $form['configs'] = [
        '#type' => 'details',
        '#title' => 'Configs du delete : ',
        '#tree' => true,
        '#open' => false
      ];
      foreach ($configs as $entity_type => $blocks) {
        $nbre += count($blocks);
        $form['configs'][$entity_type] = [
          '#type' => 'details',
          '#title' => $entity_type . ' : ' . count($blocks) . ' to delete ',
          '#tree' => true,
          '#open' => false
        ];
        foreach ($blocks as $key => $block) {
          /**
           *
           * @var \Drupal\block\Entity\Block $block
           */
          $form['configs'][$entity_type][$key] = [
            '#type' => 'textfield',
            '#title' => $block->id(),
            '#default_value' => $block->label(),
            '#attributes' => [
              'readonly' => true
            ],
            '#maxlength' => 255
          ];
        }
      }
      $form['configs']['#title'] .= ' (' . $nbre . ')';
      
      //
      $configs = $this->getListBasicConfigDependancies($theme_name);
      $form['basic_configs'] = [
        '#type' => 'details',
        '#title' => ' Basic configuration ',
        '#tree' => true,
        '#open' => false
      ];
      foreach ($configs as $confs) {
        $form['basic_configs'][$confs['name']] = [
          '#type' => 'textfield',
          '#default_value' => $confs['name'],
          '#attributes' => [
            'readonly' => true
          ],
          '#maxlength' => 255
        ];
      }
      //
      if ($this->CleanConfigsTheme->checkIfThemeIsCompatibleWithLogic($theme_name)) {
        /**
         * La suppression de 55 contenus en local prend assez de temps plus 4mn.
         * On ferra une suppresion par blocs
         */
        $key = 'DeleteEntities';
        if (empty($contents)) {
          $key = 'DeleteConfigs';
        }
        $form['actions'] = [
          '#type' => 'submit',
          '#value' => $this->t('Delete all : ' . $key),
          '#button_type' => 'primary',
          '#submit' => [
            '::' . $key
          ]
        ];
      }
      else {
        $this->messenger()->addWarning(" Ce theme ne peut etre supprimer via cette logique, car les fichiers existent ");
      }
    }
    else {
      // On retourne un bouton pour la suppression.
      $form['actions'] = [
        '#type' => 'submit',
        '#value' => $this->t('Delete this theme'),
        '#button_type' => 'primary',
        '#submit' => [
          '::DeleteTheme'
        ]
      ];
    }
    return $form;
  }
  
  /**
   * Liste des entites content installé sur le site drupal.
   * ( On regardera les entites de configuration plus tard ).
   */
  protected function getListentities() {
    if (empty($this->ListEntities)) {
      $entities = \Drupal::entityTypeManager()->getDefinitions();
      foreach ($entities as $key => $entity) {
        $table = $entity->getBaseTable();
        if ($table)
          $this->ListEntities[$key] = $key;
      }
    }
    return $this->ListEntities;
  }
  
  protected function getListContentTodelete($domaineId) {
    $this->getListentities();
    $contents = [];
    foreach ($this->ListEntities as $entity_type) {
      $storage = \Drupal::entityTypeManager()->getStorage($entity_type);
      $contents[$entity_type] = [];
      if ($entity_type == 'config_theme_entity') {
        $contents[$entity_type] = $storage->loadByProperties([
          'hostname' => $domaineId
        ]);
      }
      else {
        $fields = $this->entityFieldManger->getFieldStorageDefinitions($entity_type);
        if (!empty($fields[self::$field_domain_access])) {
          $contents[$entity_type] = $storage->loadByProperties([
            self::$field_domain_access => $domaineId
          ]);
        }
      }
    }
    return $contents;
  }
  
  /**
   *
   * @param string $domaineId
   * @return array[]|NULL[]
   */
  protected function getConfigsTodelete(string $domaineId) {
    $configsEntities = [
      'block',
      'webform'
    ];
    $contents = [];
    foreach ($configsEntities as $entity_type) {
      $storage = \Drupal::entityTypeManager()->getStorage($entity_type);
      $contents[$entity_type] = [];
      if ($entity_type == 'block') {
        $contents[$entity_type] = $storage->loadByProperties([
          'theme' => $domaineId
        ]);
      }
      elseif ($entity_type == 'webform') {
        /**
         *
         * @var \Drupal\Core\Entity\Query\QueryInterface $query
         */
        $query = $storage->getQuery();
        $query->condition('third_party_settings.webform_domain_access.field_domain_access', $domaineId);
        $result = $query->execute();
        if (!empty($result))
          $contents[$entity_type] = $storage->loadMultiple($result);
      }
    }
    return $contents;
  }
  
  /**
   *
   * @param string $domaineId
   */
  protected function getListBasicConfigDependancies(string $domaineId) {
    return $this->CleanConfigsTheme->getConfigsDepenceForTheme($domaineId);
    // dump($configs);
  }
  
  /**
   *
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public function DeleteEntities(array &$form, FormStateInterface $form_state) {
    $theme_name = $form_state->getValue('theme');
    $this->messenger()->addStatus(' Prepare delete theme : ' . $theme_name);
    //
    $contents = $this->getListContentTodelete($theme_name);
    foreach ($contents as $nodes) {
      foreach ($nodes as $node) {
        $node->delete();
      }
    }
  }
  
  /**
   * Dans la pratique on n'a rarement besoin de supprimer via la seconde
   * approche , la premiere (DeleteEntities) la fait deja via des dependance.
   *
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public function DeleteConfigs(array &$form, FormStateInterface $form_state) {
    $theme_name = $form_state->getValue('theme');
    //
    $configs = $this->getConfigsTodelete($theme_name);
    foreach ($configs as $blocks) {
      foreach ($blocks as $block) {
        /**
         *
         * @var \Drupal\block\Entity\Block $block
         */
        $block->delete();
      }
    }
    //
    /**
     * Pour cette suppresion il est judicieux de supprimer uniquement, les
     * configurations de base, pas les dependances.
     */
    $configs = $this->getListBasicConfigDependancies($theme_name);
    foreach ($configs as $config) {
      \Drupal::config($config['name'])->delete();
    }
  }
  
  /**
   *
   * {@inheritdoc}
   * @see \Drupal\Core\Form\FormInterface::submitForm()
   */
  public function DeleteTheme(array &$form, FormStateInterface $form_state) {
    $theme = $form_state->getValue('theme');
    if (!empty($theme)) {
      $theme_list[$theme] = $theme;
      try {
        $this->CleanConfigsTheme->DeleteThemes($theme_list);
        $this->messenger()->addStatus('Theme supprimé : ' . $theme);
      }
      catch (\Exception $e) {
        $this->messenger()->addError($e->getMessage());
      }
    }
  }
  
  /**
   *
   * {@inheritdoc}
   * @see \Drupal\Core\Form\FormInterface::submitForm()
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->messenger()->addStatus('submit end from');
  }
  
}