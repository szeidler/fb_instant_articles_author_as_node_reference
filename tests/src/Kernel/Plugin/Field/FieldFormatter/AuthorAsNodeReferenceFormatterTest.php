<?php

namespace Drupal\Tests\fb_instant_articles\Kernel\Plugin\Field\FieldFormatter;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\fb_instant_articles\Regions;
use Facebook\InstantArticles\Elements\Author;
use Facebook\InstantArticles\Elements\InstantArticle;

/**
 * Test the instant article author reference field formatter.
 *
 * @group fb_instant_articles
 */
class AuthorAsNodeReferenceFormatterTest extends FormatterTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', 'sequences');
    $this->installConfig(['system', 'field']);

    $this->installEntitySchema('node');

    // Create the node bundles required for testing.
    $this->container->get('entity_type.manager')
      ->getStorage('node_type')
      ->create([
        'name' => 'Profile',
        'type' => 'profile',
      ])->save();

    // Setup entity view display with default settings.
    $this->display->setComponent($this->fieldName, [
      'type' => 'fbia_author_as_node_reference',
      'settings' => [],
    ]);
    $this->display->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function getFieldType() {
    return 'entity_reference';
  }

  /**
   * Test the instant article author as node reference formatter.
   */
  public function testAuthorAsNodeReferenceFormatter() {
    $value_alpha = 'Joe Mayo';
    $value_beta = 'J. Peterman';

    // Referenced entity.
    $referenced_entity_alpha = $this->container->get('entity_type.manager')
      ->getStorage('node')
      ->create(['type' => 'profile', 'title' => $value_alpha]);
    $referenced_entity_alpha->save();
    $referenced_entity_beta = $this->container->get('entity_type.manager')
      ->getStorage('node')
      ->create(['type' => 'profile', 'title' => $value_beta]);
    $referenced_entity_beta->save();

    $entity = EntityTest::create([]);
    $entity->{$this->fieldName}[] = ['entity' => $referenced_entity_alpha];
    $entity->{$this->fieldName}[] = ['entity' => $referenced_entity_beta];

    /** @var \Drupal\fb_instant_articles\Plugin\Field\InstantArticleFormatterInterface $formatter */
    $formatter = $this->display->getRenderer($this->fieldName);
    $article = InstantArticle::create();
    $formatter->viewInstantArticle($entity->{$this->fieldName}, $article, Regions::REGION_CONTENT);

    $authors = $article->getHeader()->getAuthors();
    $this->assertEquals(2, count($authors));
    $this->assertTrue($authors[0] instanceof Author);
    $this->assertEquals($value_alpha, $authors[0]->getName());
    $this->assertEquals('http://localhost/user/1', $authors[0]->getUrl());
    $this->assertEquals($value_beta, $authors[1]->getName());
    $this->assertEquals('http://localhost/user/2', $authors[1]->getUrl());

    // Test an un-linked configuration.
    $this->display->setComponent($this->fieldName, [
      'type' => 'fbia_author_as_node_reference',
      'settings' => [
        'link' => FALSE,
      ],
    ]);
    $this->display->save();
    /** @var \Drupal\fb_instant_articles\Plugin\Field\InstantArticleFormatterInterface $formatter */
    $formatter = $this->display->getRenderer($this->fieldName);
    $article = InstantArticle::create();
    $formatter->viewInstantArticle($entity->{$this->fieldName}, $article, Regions::REGION_CONTENT);

    $authors = $article->getHeader()->getAuthors();
    $this->assertNull($authors[0]->getUrl());
  }

}
