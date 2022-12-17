<?php
namespace BubbleORM\Enums;

enum DatabaseCreationMode{
    case None;
    case Create;
    case Override;
    case OverrideAndBackup;
}