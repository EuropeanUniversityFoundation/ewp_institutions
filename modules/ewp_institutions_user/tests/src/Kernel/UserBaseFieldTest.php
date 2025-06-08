<?php

namespace Drupal\Tests\ewp_institutions_user\Kernel;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that the User entity has a certain base field.
 *
 * @group ewp_institutions_user
 */
class UserBaseFieldTest extends KernelTestBase {

  /**
   * Field to test.
   */
  const NEW_BASE_FIELD = 'user_institution';

  /**
   * Modules to enable.
   *
   * @var array<string>
   */
  protected static $modules = [
    'user',
    'ewp_institutions',
    'ewp_institutions_user',
  ];

  /**
   * Tests that the base field definitions exist.
   */
  public function testBaseFieldDefinitions() {
    $fields = \Drupal::service('entity_field.manager')
      ->getFieldDefinitions('user', 'user');

    // Test whether the field exists.
    $this->assertArrayHasKey(self::NEW_BASE_FIELD, $fields);
    // Test whether the field is a base field.
    $this->assertInstanceOf(BaseFieldDefinition::class, $fields[self::NEW_BASE_FIELD]);
  }

}
