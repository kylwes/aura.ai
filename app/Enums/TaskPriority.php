<?php

namespace App\Enums;

enum TaskPriority: string
{
    case Urgent = 'urgent';
    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';

    public function label(): string
    {
        return match ($this) {
            self::Urgent => 'Urgent',
            self::High => 'High',
            self::Medium => 'Mid',
            self::Low => 'Low',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Urgent => 'text-priority-urgent',
            self::High => 'text-priority-high',
            self::Medium => 'text-priority-medium',
            self::Low => 'text-priority-low',
        };
    }

    public function bgColor(): string
    {
        return match ($this) {
            self::Urgent => 'bg-priority-urgent',
            self::High => 'bg-priority-high',
            self::Medium => 'bg-priority-medium',
            self::Low => 'bg-priority-low',
        };
    }

    public function borderColor(): string
    {
        return match ($this) {
            self::Urgent => 'border-priority-urgent',
            self::High => 'border-priority-high',
            self::Medium => 'border-priority-medium',
            self::Low => 'border-priority-low',
        };
    }
}
