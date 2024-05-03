<?php

namespace SkyDiablo\ReactCrate\DataObject\Enums;

/**
 * @see https://cratedb.com/docs/crate/reference/en/latest/general/ddl/data-types.html
 */
enum DataType
{
    case BOOLEAN; // 1 byte
    case VARCHAR;
    case TEXT;
    case CHARACTER;
    case CHAR;
    case SMALLINT; // 2 byte
    case INTEGER; // 4 byte
    case BIGINT; // 8 byte
    case NUMERIC;
    case REAL; // 6 byte
    case DOUBLE_PRECISION; // 8 byte
    case TIMESTAMP_WITH_TIME_ZONE; // 8 byte
    case TIMESTAMP_WITHOUT_TIME_ZONE; // 8 byte
    case DATE; // 8 byte
    case TIME_WITH_TIME_ZONE; // 12 byte
    case BIT;
    case IP; //8 byte
    case OBJECT;
    case ARRAY;
    case GEO_POINT; // 16 byte
    case GEO_SHAPE;
    case FLOAT_VECTOR;
}
