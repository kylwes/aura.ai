<?php

namespace App\Enums;

enum IntegrationType: string
{
    case Jira = 'jira';
    case Slack = 'slack';
    case Gmail = 'gmail';
    case Notion = 'notion';
    case GoogleCalendar = 'google_calendar';
    case GitHub = 'github';
    case Linear = 'linear';
    case Asana = 'asana';
    case Teams = 'teams';
    case Outlook = 'outlook';
    case Productive = 'productive';

    public function label(): string
    {
        return match ($this) {
            self::Jira => 'Jira',
            self::Slack => 'Slack',
            self::Gmail => 'Gmail',
            self::Notion => 'Notion',
            self::GoogleCalendar => 'Google Calendar',
            self::GitHub => 'GitHub',
            self::Linear => 'Linear',
            self::Asana => 'Asana',
            self::Teams => 'Microsoft Teams',
            self::Outlook => 'Outlook',
            self::Productive => 'Productive',
        };
    }

    public function iconComponent(): string
    {
        return match ($this) {
            self::Jira => 'icons.jira',
            self::Slack => 'icons.slack',
            self::Gmail => 'icons.gmail',
            self::Notion => 'icons.notion',
            self::GoogleCalendar => 'icons.google-calendar',
            self::GitHub => 'icons.github',
            self::Linear => 'icons.linear',
            self::Asana => 'icons.asana',
            self::Teams => 'icons.teams',
            self::Outlook => 'icons.outlook',
            self::Productive => 'icons.productive',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Jira => '#2684FF',
            self::Slack => '#4A154B',
            self::Gmail => '#EA4335',
            self::Notion => '#000000',
            self::GoogleCalendar => '#4285F4',
            self::GitHub => '#24292F',
            self::Linear => '#5E6AD2',
            self::Asana => '#F06A6A',
            self::Teams => '#5059C9',
            self::Outlook => '#0078D4',
            self::Productive => '#5046E5',
        };
    }
}
