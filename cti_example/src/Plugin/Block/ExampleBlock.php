<?php

namespace Drupal\cti_example\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an 'ExampleBlock' block.
 *
 * @Block(
 *  id = "example_block",
 *  admin_label = @Translation("Example block")
 * )
 */
class ExampleBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs a new ExampleBlock instance.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    // Load only published nodes with type article.
    $articles = $this->entityTypeManager
      ->getStorage('node')
      ->loadByProperties(['type' => 'article', 'status' => 1]);

    /** @var \Drupal\node\NodeInterface $article */
    foreach ($articles ?? [] as $article) {
      if (
        $article->hasField('field_show_in_list')
        && !$article->get('field_show_in_list')->isEmpty()
      ) {
        // Build link to the article and add to render array depending on
        // field_show_in_list value.
        $field_show_in_list = (bool) $article->get('field_show_in_list')->value;

        if ($field_show_in_list) {
          $items[] = [
            '#type' => 'link',
            '#title' => $article->label(),
            '#url' => $article->toUrl(),
            '#attributes' => [
              'class' => 'item-link',
            ],
          ];

          $cacheTags[] = 'node:' . $article->id();
        }
      }
    }

    $cacheTags[] = 'node_list';

    $build[] = [
      '#theme' => 'block_example',
      '#items' => $items ?? [],
      '#articles_count' => $this->t('There are @count articles.', [
        '@count' => empty($items) ? 0 : count($items)
      ]),
      '#cache' => [
        'tags' => $cacheTags,
      ],
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags(): array {
    return Cache::mergeTags(parent::getCacheTags(), ['node_list']);
  }

}
