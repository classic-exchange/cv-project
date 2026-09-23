<?php

namespace App\Enum;

enum AttributeDataType: string
{
    case STRING = 'string';
    case TEXT = 'text';
    case IMAGE = 'image';
    case NUMERIC = 'numeric';
    case DATE = 'date';
    case PERIOD = 'period';
    case BOOLEAN = 'boolean';
    case ONE_OF_MANY = 'one_of_many';

    public function label(): string
    {
        return match ($this) {
            self::STRING => 'String',
            self::TEXT => 'Text',
            self::IMAGE => 'Image',
            self::NUMERIC => 'Numeric',
            self::DATE => 'Date',
            self::PERIOD => 'Period',
            self::BOOLEAN => 'Boolean',
            self::ONE_OF_MANY => 'One of many'
        };
    }
}
