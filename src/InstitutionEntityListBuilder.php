<?php

namespace Drupal\ewp_institutions;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Institution entities.
 *
 * @ingroup ewp_institutions
 */
class InstitutionEntityListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Institution ID');
    $header['hei_id'] = $this->t('SCHAC code');
    $header['label'] = $this->t('Label');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\ewp_institutions\Entity\InstitutionEntity $entity */
    $row['id'] = $entity->id();
    $row['hei_id'] = $entity->get('hei_id')->value;
    // $row['hei_id'] = $entity->get('hei_id')->value;
    $row['label'] = Link::createFromRoute(
      $entity->label(),
      'entity.hei.canonical',
      ['hei' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
