<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Utils;

class KsesAllowList {
	public static function allowed_html(): array {
		return array_merge(
			wp_kses_allowed_html( 'post' ),
			[
				'input' => [
					'type'             => [],
					'name'             => [],
					'value'            => [],
					'id'               => [],
					'placeholder'      => [],
					'class'            => [],
					'checked'          => [],
					'required'         => [],
					'disabled'         => [],
					'min'              => [],
					'max'              => [],
					'pattern'          => [],
					'autocomplete'     => [],
					'maxlength'        => [],
					'size'             => [],
					'readonly'         => [],
					'step'             => [],
					'aria-label'       => [],
					'aria-describedby' => [],
					'data-*'           => true,
				],
				'select' => [
					'id'               => [],
					'name'             => [],
					'class'            => [],
					'required'         => [],
					'disabled'         => [],
					'multiple'         => [],
					'size'             => [],
					'aria-label'       => [],
					'aria-describedby' => [],
					'data-*'           => true,
				],
				'option' => [
					'value'    => [],
					'selected' => [],
					'disabled' => [],
					'label'    => [],
				],
				'form' => [
					'class'        => [],
					'method'       => [],
					'action'       => [],
					'id'           => [],
					'novalidate'   => [],
					'target'       => [],
					'enctype'      => [],
					'autocomplete' => [],
					'data-*'       => true,
				],
				'textarea' => [
					'name'             => [],
					'id'               => [],
					'class'            => [],
					'rows'             => [],
					'cols'             => [],
					'required'         => [],
					'disabled'         => [],
					'readonly'         => [],
					'maxlength'        => [],
					'placeholder'      => [],
					'aria-label'       => [],
					'aria-describedby' => [],
					'data-*'           => true,
				],
				'button' => [
					'type'       => [],
					'name'       => [],
					'class'      => [],
					'id'         => [],
					'disabled'   => [],
					'value'      => [],
					'aria-label' => [],
					'data-*'     => true,
				],
				'label' => [
					'for'   => [],
					'class' => [],
					'id'    => [],
				],
				'fieldset' => [
					'class'    => [],
					'id'       => [],
					'disabled' => [],
				],
				'legend' => [
					'class' => [],
					'id'    => [],
				],
			]
		);
	}
}
