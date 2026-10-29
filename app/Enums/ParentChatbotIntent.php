<?php

namespace App\Enums;

enum ParentChatbotIntent: string
{
    case Link = 'link';
    case Unlinked = 'unlinked';
    case Stop = 'stop';
    case Stopped = 'stopped';
    case Start = 'start';
    case Revoked = 'revoked';
    case Help = 'help';
    case ChildSelection = 'child_selection';
    case PublishedGrades = 'published_grades';
    case Absences = 'absences';
    case AttendanceRate = 'attendance_rate';
    case PublishedReportCard = 'published_report_card';
    case SchoolAdminFirst = 'school_admin_first';
}
