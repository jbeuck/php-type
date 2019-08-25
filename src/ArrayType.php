<?php

declare(strict_types=1);

namespace Dgame\Type;

/**
 * Class ArrayType
 * @package Dgame\Type
 */
final class ArrayType extends Type
{
    private const GENERIC_ARRAY_PATTERN = '/^\s*array\s*<\s*(?<index>.+?)(?:\s*,\s*(?<value>.+?(?:\[\s*\w*\s*\])*))?\s*>\s*$/S';

    /**
     * @var Type|null
     */
    private $valueType;
    /**
     * @var int
     */
    private $dimension;
    /**
     * @var Type|null
     */
    private $indexType;

    /**
     * ArrayType constructor.
     *
     * @param Type|null $valueType
     * @param int       $dimension
     * @param Type|null $indexType
     */
    public function __construct(Type $valueType = null, int $dimension = 1, Type $indexType = null)
    {
        $this->valueType = $valueType;
        $this->dimension = $dimension;
        $this->indexType = $indexType;
    }

    /**
     * @return bool
     */
    public function hasIndexType(): bool
    {
        return $this->indexType !== null;
    }

    /**
     * @return bool
     */
    public function hasValueType(): bool
    {
        return $this->valueType !== null;
    }

    /**
     * @param string $typeName
     *
     * @return ArrayType|null
     */
    public static function parseGeneric(string $typeName): ?self
    {
        if (preg_match(self::GENERIC_ARRAY_PATTERN, $typeName, $matches) === 1) {
            $hasIndexType = !empty($matches['value']);
            $indexType    = $hasIndexType ? $matches['index'] : null;
            $valueType    = $hasIndexType ? $matches['value'] : $matches['index'];

            return new self(
                self::parseGeneric($valueType) ?? Type::parse($valueType),
                1,
                is_string($indexType) ? Type::parse($indexType) : null
            );
        }

        return null;
    }

    /**
     * @return array
     */
    public function getDefaultValue(): array
    {
        return [];
    }

    /**
     * @param TypeVisitorInterface $visitor
     */
    public function accept(TypeVisitorInterface $visitor): void
    {
        $visitor->visitArray($this);
    }

    /**
     * @param mixed $value
     * @param bool  $strict
     *
     * @return bool
     */
    public function acceptValue($value, bool $strict): bool
    {
        if ($this->valueType === null) {
            return is_array($value);
        }

        if (!is_array($value)) {
            $value = [$value];
        }

        foreach ($value as $key => $val) {
            if (!$this->valueType->acceptValue($val, $strict)) {
                return false;
            }

            if ($this->indexType !== null && !$this->indexType->acceptValue($key, $strict)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        if ($this->valueType !== null && $this->indexType !== null) {
            return sprintf('array<%s, %s>', $this->indexType->getDescription(), $this->valueType->getDescription());
        }

        if ($this->valueType !== null) {
            $resolver = new TypeResolver($this->valueType);
            $array    = $resolver->getArrayType();
            $desc     = $this->valueType->getDescription();

            return $array !== null && $array->hasIndexType() ? $desc : $desc . str_repeat('[]', $this->dimension);
        }

        return 'array';
    }

    /**
     * @return Type
     */
    public function getBasicType(): Type
    {
        $type = $this->valueType;
        while ($type !== null) {
            $resolver = new TypeResolver($type);
            $array    = $resolver->getArrayType();
            if ($array === null) {
                break;
            }
            $type = $array->getValueType();
        }

        return $type ?? new MixedType();
    }

    /**
     * @return Type
     */
    public function getIndexType(): Type
    {
        return $this->indexType ?? new IntType();
    }

    /**
     * @return Type|null
     */
    public function getValueType(): ?Type
    {
        return $this->valueType;
    }

    /**
     * @return int
     */
    public function getDimension(): int
    {
        return $this->dimension;
    }
}