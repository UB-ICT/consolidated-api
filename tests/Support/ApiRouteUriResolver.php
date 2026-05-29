<?php

namespace Tests\Support;

use Illuminate\Support\Str;

class ApiRouteUriResolver
{
    /**
     * Replace route parameters with deterministic sample values for smoke tests.
     */
    public static function resolve(string $uri): string
    {
        return preg_replace_callback(
            '/\{([^}]+)\}/',
            static function (array $matches): string {
                $name = $matches[1];

                if (Str::contains($name, 'email')) {
                    return 'test.user@ub.edu.bz';
                }

                if (Str::contains($name, 'documentPath')) {
                    return 'documents/sample.pdf';
                }

                if (Str::contains($name, 'fileName')) {
                    return 'sample-file.pdf';
                }

                if (Str::contains($name, 'fileType')) {
                    return 'photo';
                }

                if (Str::contains($name, 'reportTypes')) {
                    return 'staff';
                }

                if (Str::contains($name, 'buildingName')) {
                    return 'Main-Building';
                }

                if (Str::contains($name, 'courseCode')) {
                    return 'CS101';
                }

                if (Str::contains($name, 'courseSection')) {
                    return '01';
                }

                if (Str::contains($name, 'programCode')) {
                    return 'BSCS';
                }

                if (Str::contains($name, 'academicYear')) {
                    return '2024-2025';
                }

                if (Str::contains($name, 'semester')) {
                    return 'Fall';
                }

                if (Str::contains($name, 'name')) {
                    return 'Test-User';
                }

                if (Str::contains($name, 'permission')) {
                    return 'manage-users';
                }

                if (Str::contains($name, 'menu')) {
                    return '00000000-0000-4000-8000-000000000001';
                }

                if (Str::contains($name, 'conversationId')) {
                    return '00000000-0000-4000-8000-000000000002';
                }

                if (Str::contains($name, 'messageId')) {
                    return '00000000-0000-4000-8000-000000000003';
                }

                if (Str::contains($name, 'reportId')) {
                    return '00000000-0000-4000-8000-000000000004';
                }

                if (Str::contains($name, 'eventId')) {
                    return '1';
                }

                if (Str::contains($name, 'meetingId')) {
                    return '1';
                }

                if (Str::endsWith($name, 'ID') || Str::endsWith($name, 'Id') || $name === 'id' || $name === 'ID') {
                    return '00000000-0000-4000-8000-000000000099';
                }

                if (Str::contains($name, 'user')) {
                    return '00000000-0000-4000-8000-000000000010';
                }

                if (Str::contains($name, 'role')) {
                    return '00000000-0000-4000-8000-000000000011';
                }

                return 'test-value';
            },
            $uri
        ) ?? $uri;
    }
}
