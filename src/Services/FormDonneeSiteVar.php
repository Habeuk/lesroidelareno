<?php

namespace Drupal\lesroidelareno\Services;

/**
 *
 * @author stephane
 *        
 */
class FormDonneeSiteVar {
  
  /**
   * Permet de sauvegarder les etapes.
   * C'est un tableau avec les cles. La derniere valeur est la veleurs courantes.
   *
   * @var string
   */
  public static $key_steps = 'steps';
  
  /**
   * Contient les champs du formulaire à utiliser lors des etapes.
   *
   * @var string
   */
  public static $key_dsi_form = 'dsi_form';
  
  /**
   * Contient les valeurs.
   *
   * @var array
   * @deprecated Plus necesssaire.
   */
  public static $fields_value = 'fields_value';
  
  /**
   * Boolean permet de terminer, si c'est la derniere etape.
   *
   * @var string
   */
  public static $laststep = 'laststep';
  
  /**
   * Contient l'entité MAJ progressivement.
   *
   * @var string
   */
  public static $entity = 'entity';
  
  /**
   * Contient l'entité MAJ progressivement.
   *
   * @var string
   */
  public static $entity_display = 'entity_display';
  
}