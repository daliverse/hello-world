<?php

namespace Uie;

/************************************************************************
 *
 *    Description:  Base class for "Entities" that are typically backended
 *      by a database record
 *
 ************************************************************************/
abstract class EntityBase Implements Attribution, CRUD {


  /**
   * interface Uie\CRUD
   */

  protected $is_test = false;
  public function isTest() {
    return $this->is_test;
  }
  public function setTest($value) {
    $this->is_test = $value;
  }

  protected $ID;
  public function getID() {
    return $this->ID;
  }
  public function setID($value) {
    $this->ID = $value;
  }

  abstract public function save($connObj = null);
  abstract public function loadFromDatabase($connObj = null, $id = null);
  abstract public function loadFromArray($params);
  abstract public function loadFromJson($json);
  abstract public function getJson();

  abstract public function delete($hard_delete = false);

  protected function loadAttributionFromArray($params) {
    if (isset($params['date_created'])) {$this->setDateCreated($params['date_created']);}
    if (isset($params['created_by_actor_id'])) {$this->setCreatedByActorID($params['created_by_actor_id']);}
    if (isset($params['date_modified'])) {$this->setDateModified($params['date_modified']);}
    if (isset($params['modified_by_actor_id'])) {$this->setModifiedByActorID($params['modified_by_actor_id']);}
  }


  /**
   * interface Uie\Attribution
   */

  protected $attribution = array();

  public function setCreate($actor_id = null) {
    $this->setDateCreated(now());
    $this->setCreatedByActorID($actor_id);
  }

  public function setModified($actor_id = null) {
    $this->setDateModified(now());
    $this->setModifiedByActorID($actor_id);
  }


  public function getDateCreated() {
    return @$this->attribution['date_created'];
  }
  public function setDateCreated($date) {
    $this->attribution['date_created'] = $date;
    return $this;
  }

  public function getDateModified(){
    return @$this->attribution['date_modified'];
  }
  public function setDateModified($date){
    $this->attribution['date_modified'] = $date;
    return $this;
  }

  public function getCreatedByActorID() {
    return @$this->attribution['created_by_actor_id'];
  }
  public function setCreatedByActorID($actor_id) {
    $this->attribution['created_by_actor_id'] = $actor_id;
    return $this;
  }

  public function getModifiedByActorID() {
    return @$this->attribution['modified_by_actor_id'];
  }
  public function setModifiedByActorID($actor_id)  {
    $this->attribution['modified_by_actor_id'] = $actor_id;
    return $this;
  }

}
?>
