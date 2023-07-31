<?php

namespace Drupal\lesroidelareno;

use Drupal\Component\Utility\Crypt;
use PhpParser\Error;

class lesroidelareno {
  
  /**
   * Propriettaire et gerant de site web.
   *
   * @var string
   */
  private static $managerWebSite = 'gerant_de_site_web';
  
  /**
   * Propriettaire et gerant de ecommerce web.
   *
   * @var string
   */
  private static $managerEcommerce = 'manage_ecommerce';
  /**
   *
   * @var boolean
   */
  private static $isOwnerSite = NULL;
  
  /**
   *
   * @var boolean
   */
  private static $isAdministrator = NULL;
  
  /**
   * True si l'utilisateur a un role administrateur de domaine.
   *
   * @var boolean
   */
  private static $userIsAdministratorSite = NULL;
  
  /**
   * "domain_id" si l'utilisateur est proprietaire du domaine et dispose des
   * droits, si non false;
   *
   * @var string
   */
  private static $AuthorOfDomaine = NULL;
  /**
   * Retoourne la valeur de l'uid.
   *
   * @var int
   */
  private static $uid = NULL;
  private static $cacheAPCu = [];
  
  /**
   * Pour ce dernier on utilise pas de cache.
   * On se fit au cache drupal, pour la premiere execution ensuite, on utilise
   * la cache statique.
   *
   * @return array
   */
  static public function getCurrentUserId() {
    if (self::$uid === NULL)
      self::$uid = \Drupal::currentUser()->id();
    return self::$uid;
  }
  
  /**
   * Permet de determiner si l'utilisateur connecter est author du domaine.
   *
   * @return string|false retourne le domaine si l'utilisateur encours est
   *         proprietaire ou false.
   */
  static public function FindUserAuthorDomain() {
    if (self::$AuthorOfDomaine === NULL) {
      // on commence par verifier dans le cache APCu.
      if (!self::$cacheAPCu)
        self::getCacheAPCu();
      // Recuperation à partir du cache.
      if (isset(self::$cacheAPCu['AuthorOfDomaine'])) {
        self::$AuthorOfDomaine = self::$cacheAPCu['AuthorOfDomaine'];
      }
      else {
        self::$AuthorOfDomaine = false;
        /**
         *
         * @var \Drupal\domain_source\HttpKernel\DomainSourcePathProcessor $domain_source
         */
        $domain_source = \Drupal::service('domain_source.path_processor');
        $domain = $domain_source->getActiveDomain();
        $domain_ovh_entities = \Drupal::entityTypeManager()->getStorage('domain_ovh_entity')->loadByProperties([
          'domain_id_drupal' => $domain->id()
        ]);
        if ($domain_ovh_entities) {
          /**
           *
           * @var \Drupal\ovh_api_rest\Entity\DomainOvhEntity $domain_ovh_entity
           */
          $domain_ovh_entity = reset($domain_ovh_entities);
          if ($domain_ovh_entity->getOwnerId() == self::getCurrentUserId())
            self::$AuthorOfDomaine = $domain->id();
        }
        // Dans la mesure ou le cache n'avais pas cette information on l'ajoute.
        self::setDataCache('AuthorOfDomaine', self::$AuthorOfDomaine);
      }
    }
    return self::$AuthorOfDomaine;
  }
  
  /**
   * Retoune les roles proprietaires de site web.
   *
   * @return string[]
   */
  static public function RolesHaveSites() {
    return [
      self::$managerWebSite => self::$managerWebSite,
      self::$managerEcommerce => self::$managerEcommerce
    ];
  }
  
  /**
   * Determine si un utilisateur a le role administrateur.
   *
   * @return boolean
   */
  static public function isAdministrator() {
    if (self::$isAdministrator == NULL) {
      // on commence par verifier dans le cache APCu.
      if (!self::$cacheAPCu)
        self::getCacheAPCu();
      // recuperation à partir du cache.
      if (isset(self::$cacheAPCu['isAdministrator'])) {
        self::$isAdministrator = self::$cacheAPCu['isAdministrator'];
      }
      else {
        self::$isAdministrator = false;
        $roles = \Drupal::currentUser()->getRoles();
        if (in_array('administrator', $roles)) {
          self::$isAdministrator = true;
        }
        // Dans la mesure ou le cache n'avais pas cette information on l'ajoute.
        self::setDataCache('isAdministrator', self::$isAdministrator);
      }
    }
    return self::$isAdministrator;
  }
  
  /**
   * L'utilisateur connecté est proprietaire d'un site ou a les roles pour gerer
   * un site ? true:false;
   * On doit mettre le resultat en cache pour l'utilisateur et le domaine.
   * // on doit utiliser les caches pour cette information ?
   */
  static public function isOwnerSite() {
    if (self::$isOwnerSite === NULL) {
      self::$isOwnerSite = false;
      $roles = \Drupal::currentUser()->getRoles();
      foreach ($roles as $role) {
        if (!empty(self::RolesHaveSites()[$role])) {
          self::$isOwnerSite = true;
          break;
        }
      }
    }
    return self::$isOwnerSite;
  }
  
  /**
   * Permet de determiner si l'utilisateur connecter est administrateur du site.
   * Ce droit peut etre ajouter par un administrateur ou meme le proprietaire du
   * siteweb.
   */
  static public function userIsAdministratorSite() {
    // \Drupal::messenger()->addStatus('check userIsAdministratorSite');
    if (self::$userIsAdministratorSite === NULL) {
      // on commence par verifier dans le cache APCu.
      if (!self::$cacheAPCu)
        self::getCacheAPCu();
      // recuperation à partir du cache.
      if (isset(self::$cacheAPCu['userIsAdministratorSite'])) {
        self::$userIsAdministratorSite = self::$cacheAPCu['userIsAdministratorSite'];
        // \Drupal::messenger()->addWarning('Get from cache :
        // userIsAdministratorSite');
      }
      else {
        if (self::isAdministrator()) {
          self::$userIsAdministratorSite = true;
        }
        else {
          self::$userIsAdministratorSite = false;
          /**
           *
           * @var \Drupal\domain_source\HttpKernel\DomainSourcePathProcessor $domain_source
           */
          $domain_source = \Drupal::service('domain_source.path_processor');
          $domain = $domain_source->getActiveDomain();
          if ($domain && self::isOwnerSite()) {
            $uid = self::getCurrentUserId();
            $user = \Drupal\user\Entity\User::load($uid);
            $domaines = $user->get('field_domain_admin')->getValue();
            foreach ($domaines as $value) {
              if ($value['target_id'] == $domain->id()) {
                self::$userIsAdministratorSite = true;
                break;
              }
            }
          }
        }
        // dans la mesure ou le cache n'avais pas cette information on l'ajoute.
        self::setDataCache('userIsAdministratorSite', self::$userIsAdministratorSite);
        // \Drupal::messenger()->addError('cache not work :
        // userIsAdministratorSite');
      }
    }
    return self::$userIsAdministratorSite;
  }
  
  /**
   * * Cette logique est definit pour les utilisateurs connectés.
   * Logique du caches:
   * On va sauvegarder les données en fonction de l'uid et de la session
   * generer.
   * ( si l'utilisateur modifier sa clée il passera par les tests de
   * verification ).
   * - La session permet d'eviter de verifier le domaine à chaque fois.
   * Drupal pour identifier un utilisateur via une session, demande de creer un
   * clée via cryp:
   * https://www.drupal.org/node/3006306
   * ///
   * 1- On cree une session unique.
   * 2- On utilise cette clée pour sauvegarder les données dans le cache APCu.
   * NB: On ne stocke pas des avec la valeur null.
   *
   * @param string $key
   * @param mixed $value
   *        //except NULL.
   * @param array $cacheAPCu
   */
  public static function setDataCache($key, $value) {
    if (self::getCurrentUserId()) {
      if ($value === NULL)
        throw new Error("La valeur de stockage ne doit pas etre NULL " . $key);
      /**
       *
       * @var \Symfony\Component\HttpFoundation\Session\Session $session
       */
      $session = \Drupal::service('session');
      if (!$session->has(self::keySession())) {
        $session->set(self::keySession(), Crypt::randomBytesBase64());
      }
      $key_APCu = self::keyACPu();
      // $oldCacheStatic = self::$cacheAPCu;
      $oldCacheFromAPCu = self::getCacheAPCu();
      self::$cacheAPCu = $oldCacheFromAPCu;
      self::$cacheAPCu[$key] = $value;
      
      /**
       * On stocke les variables pour 1h.
       * (un temps tres eleve augmentera les risques de fails).
       * ( il faudra determiner la durée moyenne des sessions via google ou
       * autre ).
       */
      if (!apcu_store($key_APCu, self::$cacheAPCu, 3600))
        \Drupal::messenger()->addError('Error cache to save key : ' . $key);
    }
  }
  
  /**
   * Permet de recuperer la valeur du cache.
   *
   * @return array|mixed
   */
  public static function getCacheAPCu() {
    // self::$cacheAPCu = [];
    /**
     *
     * @var \Symfony\Component\HttpFoundation\Session\Session $session
     */
    $session = \Drupal::service('session');
    if ($session->has(self::keySession())) {
      $key_APCu = self::keyACPu();
      $vals = apcu_fetch($key_APCu);
      // $db = [
      // 'cacheFromAPCu' => $vals,
      // 'cacheStatic-Begin' => self::$cacheAPCu,
      // 'key_APCu' => $key_APCu
      // ];
      if ($vals)
        self::$cacheAPCu = $vals;
      
      // $db['cacheStatic-End'] = self::$cacheAPCu;
      // \Stephane888\Debug\debugLog::SaveLogsDrupal($db, 'getCacheAPCu');
    }
    
    return self::$cacheAPCu;
  }
  
  /**
   * * Permet de recupere une donnée à travers sa clée.
   * NB: On ne stocke pas des avec la valeur null.
   *
   * @param string $key
   * @return mixed|NULL Null if key not exit
   */
  public static function retriveDataByKey($key) {
    if (!self::$cacheAPCu)
      self::getCacheAPCu();
    if (isset(self::$cacheAPCu[$key])) {
      return self::$cacheAPCu[$key];
    }
    return NULL;
  }
  
  public static function keySession() {
    return "lesroidelareno.id_session." . self::getCurrentUserId();
  }
  
  public static function keyACPu() {
    /**
     *
     * @var \Symfony\Component\HttpFoundation\Session\Session $session
     */
    $session = \Drupal::service('session');
    if (!$session->has(self::keySession())) {
      $session->set(self::keySession(), Crypt::randomBytesBase64());
    }
    return 'wb-horizon-' . $session->get(self::keySession());
  }
  
  /**
   * On genere une session unique qu'on transmet à l'utilisateur.
   * si l'utilisateur nous renvoit cet id, on se sert de cela pour recuperer les
   * données en cache si elle existe.
   */
  public static function testSession() {
    $key = 'test_wb_horizon_uid' . self::getCurrentUserId();
    /**
     *
     * @var \Symfony\Component\HttpFoundation\Session\Session $session
     */
    $session = \Drupal::service('session');
    if (!$session->has($key)) {
      $uniqueIdentifier = Crypt::randomBytesBase64();
      $session->set($key, $uniqueIdentifier);
      \Drupal::messenger()->addWarning('initialisation de la session');
      apcu_store($key, $uniqueIdentifier, 3600);
    }
    else {
      $id_from_session = $session->get($key);
      $id_from_cache = apcu_fetch($key);
      if ($id_from_session === $id_from_cache) {
        \Drupal::messenger()->addMessage('Bienvenue user identifier by : ' . $session->get($key));
      }
      else {
        \Drupal::messenger()->addWarning(" Impossible d'identifier l'utilisateur : " . $session->get($key));
      }
      
      // $session->remove($key);
      ;
    }
    // on verifie que la session est effectivement celle stocker en memoire pour
    // l'utilisateur.
  }
  
}