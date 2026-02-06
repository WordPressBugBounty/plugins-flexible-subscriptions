<?php
/**
 * @var \WPDesk\View\Renderer\Renderer $this
 * @var \WPDesk\Forms\Field $field
 * @var \WPDesk\View\Renderer\Renderer $renderer
 * @var string $name_prefix
 * @var string $value
 * @var string $template_name Real field template.
 */

defined( 'ABSPATH' ) || exit;
?>

<tr valign="top">
	<?php if ( $field->has_label() ) : ?>
		<?php
		$this->output_render(
			'form-label',
			[
				'field'       => $field,
				'name_prefix' => $name_prefix,
			]
		);
		?>
	<?php endif; ?>

	<td class="forminp">
		<?php
			$this->output_render(
				$template_name,
				[
					'field'       => $field,
					'renderer'    => $renderer,
					'name_prefix' => $name_prefix,
					'value'       => $value,
				]
			);
			?>

		<?php if ( $field->has_description() ) : ?>
			<p class="description"><?php echo wp_kses_post( $field->get_description() ); ?></p>
		<?php endif; ?>
	</td>
</tr>
