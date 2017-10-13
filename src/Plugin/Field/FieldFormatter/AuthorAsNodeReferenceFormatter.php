<?php

namespace Drupal\fb_instant_articles\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\fb_instant_articles\Plugin\Field\InstantArticleFormatterInterface;
use Drupal\fb_instant_articles\Plugin\Field\FieldFormatter\AuthorReferenceFormatter;
use Facebook\InstantArticles\Elements\Author;
use Facebook\InstantArticles\Elements\Header;
use Facebook\InstantArticles\Elements\InstantArticle;

/**
 * Plugin implementation of the 'fbia_author_as_node_reference' formatter.
 *
 * In some use-cases you might store authors of content as a entity_reference
 * to a node type, like 'profile'. This formatter makes it possible to use the
 * node's label to add it as a FBIA Author.
 *
 * @FieldFormatter(
 *   id = "fbia_author_as_node_reference",
 *   label = @Translation("FBIA Author"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class AuthorAsNodeReferenceFormatter extends AuthorReferenceFormatter implements InstantArticleFormatterInterface {

  /**
   * {@inheritdoc}
   */
  public function viewInstantArticle(FieldItemListInterface $items, InstantArticle $article, $region, $langcode = NULL) {
    // Need to call parent::prepareView() to populate the entities since it's
    // not otherwise getting called.
    $this->prepareView([$items->getEntity()->id() => $items]);

    /* @var \Drupal\node\NodeInterface $entity */
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      $author = Author::create()
        ->withName($entity->label());
      if ($this->getSetting('link')) {
        $author->withURL($entity->toUrl('canonical', ['absolute' => TRUE])->toString());
      }
      // Author's are added to the header of an instant article regardless of
      // the given $region.
      $header = $article->getHeader();
      if (!$header) {
        $header = Header::create();
      }
      $header->addAuthor($author);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return $field_definition->getFieldStorageDefinition()->getSetting('target_type') == 'node';
  }

}
