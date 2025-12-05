<?php
/**
 * Ical Export
 * @package Studiofy\Export
 * @version 2.0.4
 */

declare(strict_types=1);

namespace Studiofy\Export;

class IcalService {
    public function generate_event(string $summary, string $description, string $date_start, string $uid): string {
        $dtstart = date('Ymd', strtotime($date_start));
        $dtend = date('Ymd', strtotime($date_start . ' + 1 day'));
        $now = date('Ymd\THis\Z');

        $ics = [
            "BEGIN:VCALENDAR",
            "VERSION:2.0",
            "PRODID:-//Studiofy CRM//NONSGML v1.0//EN",
            "CALSCALE:GREGORIAN",
            "BEGIN:VEVENT",
            "UID:studiofy-{$uid}",
            "DTSTAMP:{$now}",
            "DTSTART;VALUE=DATE:{$dtstart}",
            "DTEND;VALUE=DATE:{$dtend}",
            "SUMMARY:{$summary}",
            "DESCRIPTION:{$description}",
            "STATUS:CONFIRMED",
            "END:VEVENT",
            "END:VCALENDAR"
        ];

        return implode("\r\n", $ics);
    }

    public function download(string $filename, string $content): void {
        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.ics"');
        echo $content;
        exit;
    }
}
