<?php

namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Forms\Persistence;

use WPDesk\FlexibleSubscriptions\Vendor\Psr\Container\NotFoundExceptionInterface;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Forms\FieldProvider;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Persistence\PersistentContainer;
/**
 * Can save/load provided fields to/from PersistentContainer.
 *
 * @package WPDesk\Forms
 */
class FieldPersistenceStrategy
{
    /** @var PersistentContainer */
    private $persistence;
    public function __construct(PersistentContainer $persistence)
    {
        $this->persistence = $persistence;
    }
    /** @return void */
    public function persist_fields(FieldProvider $fields_provider, array $data)
    {
        foreach ($fields_provider->get_fields() as $field) {
            $field_key = $field->get_name();
            if ($field->has_serializer()) {
                $this->persistence->set($field_key, $field->get_serializer()->serialize($data[$field_key]));
            } else {
                $this->persistence->set($field_key, $data[$field_key]);
            }
        }
    }
    /** @return void */
    public function load_fields(FieldProvider $fields_provider): array
    {
        $data = [];
        foreach ($fields_provider->get_fields() as $field) {
            $field_key = $field->get_name();
            try {
                if ($field->has_serializer()) {
                    $data[$field_key] = $field->get_serializer()->unserialize($this->persistence->get($field_key));
                } else {
                    $data[$field_key] = $this->persistence->get($field_key);
                }
            } catch (NotFoundExceptionInterface $not_found) {
                //phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
            }
        }
        return $data;
    }
}
