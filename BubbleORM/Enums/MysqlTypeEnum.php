<?php
namespace BubbleORM\Enums;

enum MysqlTypeEnum{
    case TinyInt;
    case MediumInt;
    case BigInt;

    case TinyText;
    case MediumText;
    case Text;
    case LongText;

    case TinyBlob;
    case MediumBlob;
    case Blob;
    case LongBlob;

    case Date;
    case DateTime;
}