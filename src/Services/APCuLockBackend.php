<?php

namespace Drupal\lesroidelareno\Services;

use Drupal\Core\Lock\LockBackendInterface;

class APCuLockBackend implements LockBackendInterface {
  private $locks = [];
  /**
   * Pause entre chaque vérification du verrou.
   * Periode de verification en milliseconde. Cette valeur doit etre
   * comprise entre [20 et 500]ms pour une meilleur efficacité.
   *
   * @var integer
   */
  private $ms_sleep = 250;
  
  /**
   * Permet de demarrer une requete ou de faire patienté l'utilisateur pendant
   * un certains temps.
   * Comportement par dezfaut:
   * Attendre maximum 150s, vérifier toutes les 250ms
   *
   * @param mixed $name
   *        nom de la clée.
   * @param number $timeout
   *        Durée de vie de la clée dans le cache.( auto suppression)
   * @param number $delay
   *        Attente maximale en seconde.
   * @param number $sleep
   *        Periode de verification en milliseconde.
   */
  public function waitBeforeStart($name, $timeout = 150, $delay = 150) {
    $exist = $this->acquire($name, $timeout);
    if (!$exist) {
      \Drupal::logger('lesroidelareno')->warning("Attente verrou : $name (timeout: $delay)");
      $wait = $this->wait($name, $delay);
      if (!$wait)
        \Drupal::logger('lesroidelareno')->error("Echec complet du verrou : $name (timeout: $delay)");
      return $wait;
    }
    return true;
  }
  
  /**
   * Par defaut une cle est automatiquement supprimé apres 3 minutes.
   *
   * {@inheritdoc}
   * @see \Drupal\Core\Lock\LockBackendInterface::acquire()
   */
  public function acquire($name, $timeout = 150) {
    $key = "wbhorizon_lock_$name";
    $success = apcu_add($key, microtime(TRUE), $timeout);
    if ($success) {
      $this->locks[$name] = $key;
      return TRUE;
    }
    return FALSE;
  }
  
  /**
   *
   * {@inheritdoc}
   * @see \Drupal\Core\Lock\LockBackendInterface::release()
   */
  public function release($name) {
    if (isset($this->locks[$name])) {
      apcu_delete($this->locks[$name]);
      unset($this->locks[$name]);
    }
  }
  
  /**
   *
   * {@inheritdoc}
   * @see \Drupal\Core\Lock\LockBackendInterface::lockMayBeAvailable()
   */
  public function lockMayBeAvailable($name) {
    return !apcu_exists("wbhorizon_lock_$name");
  }
  
  /**
   * $name : Identifiant du verrou (ex: "site_creation_123").
   * $delay (30s) : Délai maximal d'attente avant ab!andon.
   * - Example:
   * $this->ms_sleep = 50;
   * $lock->wait('resource_creation', 120);
   * Attendre maximum 2 minutes (120s), vérifier toutes les 50ms
   *
   * @param number $delay
   *        Attente maximale en seconde.
   * {@inheritdoc}
   * @see \Drupal\Core\Lock\LockBackendInterface::wait()
   */
  public function wait($name, $delay = 150) {
    $start = hrtime(true);
    $ms_sleep = $this->ms_sleep;
    $end = $start + ($delay * 1e9);
    $sleep_ns = $ms_sleep * 1_000_000;
    
    while (($now = hrtime(true)) < $end) {
      if ($this->lockMayBeAvailable($name)) {
        return true;
      }
      
      // Calcul dynamique du temps de sommeil restant
      $remaining_ns = $end - $now;
      $sleep_duration = min($sleep_ns, $remaining_ns);
      
      if ($sleep_duration > 0) {
        time_nanosleep(0, $sleep_duration);
      }
    }
    return false;
  }
  
  public function getLockId() {
    //
  }
  
  /**
   *
   * {@inheritdoc}
   * @see \Drupal\Core\Lock\LockBackendInterface::releaseAll()
   */
  public function releaseAll($lockId = NULL) {
    if ($lockId)
      $this->release($lockId);
    //
    foreach ($this->locks as $key) {
      apcu_delete($key);
    }
    $this->locks = [];
  }
}