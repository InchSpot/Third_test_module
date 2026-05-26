<?php

namespace Drupal\third_test_module\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

/**
 * Блок с тремя последними проектами.
 *
 * @Block(
 *   id = "latest_third_test_block",
 *   admin_label = @Translation("Последние проекты"),
 *   category = @Translation("Third test module")
 * )
 */
class LatestThirdTestBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'label_display' => 'visible',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $nids = \Drupal::entityQuery('node')
      ->condition('type', 'project')
      ->condition('status', 1)
      ->sort('created', 'DESC')
      ->range(0, 3)
      ->accessCheck(TRUE)
      ->execute();

    $projects = [];

    if (is_array($nids) && $nids !== []) {
      $nodes = Node::loadMultiple($nids);

      foreach ($nodes as $node) {
        $text = '';
        if ($node->hasField('body') && !$node->get('body')->isEmpty()) {
          $body = $node->get('body')->first();
          $raw = $body ? ($body->summary ?: $body->value) : '';
          $text = trim(strip_tags((string) $raw));
        }

        $image_url = NULL;
        if ($node->hasField('field_image') && !$node->get('field_image')->isEmpty()) {
          $file = $node->get('field_image')->entity;
          if ($file) {
            $image_url = \Drupal::service('file_url_generator')
              ->generateAbsoluteString($file->getFileUri());
          }
        }

        $end_date = '';
        if ($node->hasField('field_end_date') && !$node->get('field_end_date')->isEmpty()) {
          $date_value = $node->get('field_end_date')->value;
          if ($date_value) {
            $end_date = date('d.m.Y', strtotime($date_value));
          }
        }

        $projects[] = [
          'url' => $node->toUrl()->toString(),
          'title' => $node->label(),
          'text' => $text,
          'image_url' => $image_url,
          'end_date' => $end_date,
        ];
      }
    }

    return [
      '#theme' => 'third_test_projects',
      '#projects' => $projects,
      '#title' => $this->t('Последние проекты'),
      '#empty_message' => $this->t('Пока нет проектов.'),
      '#attached' => [
        'library' => ['third_test_module/third_test_module_css'],
      ],
      '#cache' => [
        'tags' => ['node_list:project'],
        'max-age' => 0,
      ],
    ];
  }

}
