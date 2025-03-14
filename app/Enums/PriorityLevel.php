<?php

namespace App\Enums;

enum PriorityLevel: string
{
    case LOW = "LOW";
    case MEDIUM = "MEDIUM";
    case HIGH = "HIGH";
    case CRITICAL = "CRITICAL";
    case NONE = "NONE";
    case UNASSIGNED = "UNASSIGNED";
    case PENDING = "PENDING";

    public static function getValues()
    {
        return [
            self::LOW,
            self::MEDIUM,
            self::HIGH,
            self::CRITICAL,
            self::NONE,
            self::UNASSIGNED,
            self::PENDING,
        ];
    }
    public function label(): string
    {
        return match ($this) {
            self::LOW        => 'Low',
            self::MEDIUM     => 'Medium',
            self::HIGH       => 'High',
            self::CRITICAL   => 'Critical',
            self::NONE       => 'None',
            self::UNASSIGNED => 'Unassigned',
            self::PENDING    => 'Pending',
        };
    }
}