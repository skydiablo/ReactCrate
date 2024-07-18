<?php

namespace SkyDiablo\ReactCrate\DBAL\Table\Enums;

/**
 * @see https://cratedb.com/docs/crate/reference/en/latest/general/ddl/data-types.html
 */
enum DataType : string
{
    case BOOLEAN = 'BOOLEAN'; // 1 byte
    case VARCHAR = 'VARCHAR';
    case TEXT = 'TEXT';
    case CHARACTER = 'CHARACTER';
    case CHAR = 'CHAR';
    case SMALLINT = 'SMALLINT'; // 2 byte
    case INTEGER = 'INTEGER'; // 4 byte
    case BIGINT = 'BIGINT'; // 8 byte
    case NUMERIC = 'NUMERIC';
    case REAL = 'REAL'; // 6 byte
    case DOUBLE_PRECISION = 'DOUBLE PRECISION'; // 8 byte
    case TIMESTAMP_WITH_TIME_ZONE = 'TIMESTAMP WITH TIME ZONE'; // 8 byte
    case TIMESTAMP_WITHOUT_TIME_ZONE = 'TIMESTAMP WITHOUT TIME ZONE'; // 8 byte
    case DATE = 'DATE'; // 8 byte
    case TIME_WITH_TIME_ZONE = 'TIME WITH TIME ZONE'; // 12 byte
    case BIT = 'BIT';
    case IP = 'IP'; //8 byte
    case OBJECT = 'OBJECT';
    case ARRAY = 'ARRAY';
    case GEO_POINT = 'GEO_POINT'; // 16 byte
    case GEO_SHAPE = 'GEO_SHAPE';
    case FLOAT_VECTOR = 'float_vector';
}
