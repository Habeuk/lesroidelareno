<?php

namespace Drupal\lesroidelareno;

use Drupal\Component\Utility\Crypt;
use PhpParser\Error;

class lesroidelareno {
  /**
   * L'adresse Ip du serveur.
   *
   * @var string
   */
  public const ip_serveur = '152.228.134.19';
  
  /**
   * L'adresse Ip du serveur.
   *
   * @var string
   */
  public const ipv6_serveur = '2001:41d0:404:200::7c3f';
  
  /**
   * webroot ( les fichiers du index est dans /home/wb-horizon/public/web/ ).
   *
   * @var string
   */
  public const webroot = '/home/wb-horizon';
  
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
   * Id du domaine encours.
   *
   * @var string
   */
  private static $currentDomainId = NULL;
  /**
   * Id du domaine encours.
   *
   * @var string
   */
  private static $currentPrefixDomain = NULL;
  
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
        $domain_ovh_entities = \Drupal::entityTypeManager()->getStorage('domain_ovh_entity')->loadByProperties([
          'domain_id_drupal' => self::getCurrentDomainId()
        ]);
        if ($domain_ovh_entities) {
          /**
           *
           * @var \Drupal\ovh_api_rest\Entity\DomainOvhEntity $domain_ovh_entity
           */
          $domain_ovh_entity = reset($domain_ovh_entities);
          if ($domain_ovh_entity->getOwnerId() == self::getCurrentUserId())
            self::$AuthorOfDomaine = self::getCurrentDomainId();
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
      // On commence par verifier dans le cache APCu.
      if (!self::$cacheAPCu)
        self::getCacheAPCu();
      // Recuperation à partir du cache.
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
          if (self::isOwnerSite()) {
            
            $uid = self::getCurrentUserId();
            $user = \Drupal\user\Entity\User::load($uid);
            $domaines = $user->get('field_domain_admin')->getValue();
            
            foreach ($domaines as $value) {
              if ($value['target_id'] == self::getCurrentDomainId()) {
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
   * Permet de determiner l'id du domaine encours.
   *
   * @return string // l'id du nom de domaine.
   */
  static public function getCurrentDomainId() {
    if (self::$currentDomainId === NULL) {
      if (isset(self::$cacheAPCu['currentDomainId'])) {
        // pour se cat specifique, on ne fait pas de getCahe ( car cela
        // provequera une boucle et fonctionnellement c'est un element qui doit
        // etre verifier aumoins une foix pour chaque initialisation du cache )
        self::$currentDomainId = self::$cacheAPCu['currentDomainId'];
      }
      else {
        /**
         *
         * @var \Drupal\domain_source\HttpKernel\DomainSourcePathProcessor $domain_source
         */
        $domain_source = \Drupal::service('domain_source.path_processor');
        $domain = $domain_source->getActiveDomain();
        self::$currentDomainId = $domain->id();
        /**
         * On ajoute cela en cache,
         */
        self::$cacheAPCu['currentDomainId'] = self::$currentDomainId;
      }
    }
    return self::$currentDomainId;
  }
  
  /**
   * Retourne le prefix du domaine encours.
   * Ce parametre est important car il peut etre utiliser comme #id pour les
   * types d'entites car sa valeur est bien en dessous des 32 max.
   */
  static public function getCurrentPrefixDomain($replace_by_undescore = true) {
    if (self::$currentPrefixDomain === NULL) {
      if (isset(self::$cacheAPCu['currentPrefixDomain'])) {
        self::$currentPrefixDomain = self::$cacheAPCu['currentPrefixDomain'];
      }
      else {
        $domain_ovh_entities = \Drupal::entityTypeManager()->getStorage('domain_ovh_entity')->loadByProperties([
          'domain_id_drupal' => self::getCurrentDomainId()
        ]);
        if ($domain_ovh_entities) {
          /**
           *
           * @var \Drupal\ovh_api_rest\Entity\DomainOvhEntity $domain_ovh_entity
           */
          $domain_ovh_entity = reset($domain_ovh_entities);
          
          self::$currentPrefixDomain = $domain_ovh_entity->getsubDomain();
          self::setDataCache('currentPrefixDomain', self::$currentPrefixDomain);
        }
      }
    }
    /**
     * cette chaine peut contenir "-" qui n'est pas valide pour
     * certains entitées.
     * ( du coup on remplace car la majorité des entitées exigent "_" ).
     */
    if ($replace_by_undescore)
      return str_replace("-", "_", self::$currentPrefixDomain);
    return self::$currentPrefixDomain;
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
    // aucune raison d'empecher les anonyme d'avoir leur cache.
    // if (self::getCurrentUserId()) {
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
    // On n'efface pas et ne modifie pas le contenu du cache encours.
    self::$cacheAPCu[$key] = $value;
    
    /**
     * On stocke les variables pour 2h.
     * (un temps tres eleve augmentera les risques de fails).
     * ( il faudra determiner la durée moyenne des sessions via google ou
     * autre ).
     */
    if (!apcu_store($key_APCu, self::$cacheAPCu, 7200))
      \Drupal::messenger()->addError('Error cache to save key : ' . $key);
    // }
  }
  
  /**
   * Permet de recuperer la valeur du cache.
   *
   * @return array|mixed
   */
  public static function getCacheAPCu() {
    /**
     *
     * @var \Symfony\Component\HttpFoundation\Session\Session $session
     */
    $session = \Drupal::service('session');
    if ($session->has(self::keySession())) {
      $key_APCu = self::keyACPu();
      $vals = apcu_fetch($key_APCu);
      if ($vals) {
        // le cache peut avoir des données ajouter de maniere statique, donc on
        // ne reset pas le cache on surcharge la valeur des clées.
        foreach ($vals as $k => $value) {
          // les données dans le $cacheAPCu sont prioritaire sur ceux dans la
          // memoire.
          if (!isset(self::$cacheAPCu[$k]))
            self::$cacheAPCu[$k] = $value;
        }
      }
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
  
  /**
   * On cree la clée de session en function du domaine encours et de l'id de
   * l'utilisateur.
   * ( Les anonymes, ils ont les memes access ).
   *
   * @return string
   */
  public static function keySession() {
    return self::getCurrentDomainId() . ".id_session." . self::getCurrentUserId();
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