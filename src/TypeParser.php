<?php

declare(strict_types=1);

namespace Dgame\Type;

use Dgame\Type\Tokenizer\Token;
use Dgame\Type\Tokenizer\Tokenizer;
use Dgame\Type\Tokenizer\TokenStream;
use RuntimeException;

/**
 * Class TypeParser
 * @package Dgame\Type
 */
final class TypeParser
{
    private const  TYPE_CALLBACKS = [
        'is_int'      => 'int',
        'is_float'    => 'float',
        'is_numeric'  => null,
        'is_string'   => 'string',
        'is_bool'     => 'bool',
        'is_callable' => 'callable',
        'is_object'   => 'object',
        'is_resource' => 'resource',
        'is_array'    => 'array',
        'is_iterable' => 'iterable',
        'is_null'     => 'null'
    ];

    /**
     * @param mixed $value
     *
     * @return Type
     */
    public static function fromValue($value): Type
    {
        /**
         * @var callable  $callback
         * @var  string[] $types
         */
        foreach (self::TYPE_CALLBACKS as $callback => $type) {
            if ($callback($value)) {
                return $type !== null ? self::parse($type) : self::fromValue(self::interpretValue($value));
            }
        }

        return new MixedType();
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    private static function interpretValue($value)
    {
        $value = json_decode($value, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(json_last_error_msg());
        }

        return $value;
    }

    /**
     * @param string $typeName
     *
     * @return Type
     */
    public static function parse(string $typeName): Type
    {
        $tokenizer = new Tokenizer($typeName);
        $stream    = $tokenizer->getTokenStream();

        $type = self::parseStream($stream);
        //        var_dump($stream->getNextToken());
        //        var_dump($type);
        //        exit;

        $stream->expectOneOf(Token::EOF);

        return $type;
    }

    /**
     * @param TokenStream $stream
     *
     * @return Type
     */
    public static function parseStream(TokenStream $stream): Type
    {
        $type = self::parseType($stream);
        $type = self::parseGeneric($stream, $type);
        $type = self::parseBrackets($stream, $type);
        $type = self::parseUnion($stream, $type);

        return $type;
    }

    /**
     * @param TokenStream $stream
     *
     * @return Type
     */
    public static function parseType(TokenStream $stream): Type
    {
        $nullable = $stream->mayOneOf(Token::NULLABLE);
        $type     = self::parseBasicType($stream);

        return $nullable === null ? $type : $type->asNullable();
    }

    /**
     * @param TokenStream $stream
     *
     * @return Type
     */
    public static function parseBasicType(TokenStream $stream): Type
    {
        $token = $stream->expectOneOf(Token::BUILTIN_TYPE, Token::IDENTIFIER);
        switch ($token->getValue()) {
            case 'callable':
                $type = new CallableType();
                break;
            case 'iterable':
                $type = new IterableType();
                break;
            case 'null':
                $type = new NullType();
                break;
            case 'object':
            case 'static':
            case 'self':
            case 'parent':
                $type = new ObjectType($token->getValue());
                break;
            case 'void':
                $type = new VoidType();
                break;
            case 'array':
                $type = new ArrayType();
                break;
            case 'bool':
            case 'boolean':
                $type = new BoolType();
                break;
            case 'float':
            case 'double':
            case 'real':
                $type = new FloatType();
                break;
            case 'int':
            case 'integer':
                $type = new IntType();
                break;
            case 'resource':
                $type = new ResourceType();
                break;
            case 'string':
                $type = new StringType();
                break;
            case 'mixed':
                $type = new MixedType();
                break;
            default:
                $type = new UserDefinedType($token->getValue());
                break;
        }

        return $type;
    }

    /**
     * @param TokenStream $stream
     * @param Type        $type
     *
     * @return Type
     */
    public static function parseGeneric(TokenStream $stream, Type $type): Type
    {
        $peek = $stream->peekNextToken();
        if ($peek->isOpenAngleBracket()) {
            $stream->skipNextToken();

            $genericTypes = [];
            do {
                $genericTypes[] = self::parseStream($stream);
            } while ($stream->mayOneOf(TOKEN::COMMA) !== null);

            $stream->expectOneOf(Token::CLOSE_ANGLE_BRACKET);

            $resolver = new TypeResolver($type);
            if ($resolver->isArrayType() && count($genericTypes) <= 2) {
                $valueType = array_pop($genericTypes);
                $indexType = array_pop($genericTypes);

                return new ArrayType($valueType, $indexType);
            }

            return new GenericType($type, ...$genericTypes);
        }

        return $type;
    }

    /**
     * @param TokenStream $stream
     * @param Type        $type
     *
     * @return Type
     */
    public static function parseBrackets(TokenStream $stream, Type $type): Type
    {
        while ($stream->mayOneOf(Token::OPEN_SQUARE_BRACKET) !== null) {
            $indexType = null;

            $token = $stream->mayOneOf(Token::BUILTIN_TYPE, Token::IDENTIFIER);
            if ($token !== null) {
                $indexType = self::parse($token->getValue());
            }
            $stream->expectOneOf(Token::CLOSE_SQUARE_BRACKET);

            $type = new ArrayType($type, $indexType);
        }

        return $type;
    }

    /**
     * @param TokenStream $stream
     * @param Type        $type
     *
     * @return Type
     */
    public static function parseUnion(TokenStream $stream, Type $type): Type
    {
        $type = new UnionType($type);
        while ($stream->mayOneOf(Token::UNION) !== null) {
            $token = $stream->expectOneOf(Token::BUILTIN_TYPE, Token::IDENTIFIER);
            $type->appendType(self::parse($token->getValue()));
        }

        return $type->unwrap();
    }
}
