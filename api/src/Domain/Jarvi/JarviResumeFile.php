<?php

declare(strict_types=1);

namespace App\Domain\Jarvi;

/**
 * Un CV joint à un applicant Jarvi. `data` est la chaîne data-URI attendue par Jarvi
 * (`data:@file/pdf;base64,<...>`). Le binaire transite vers Jarvi sans être copié en base showcase-recruiter-webapp-saas.
 */
final readonly class JarviResumeFile
{
    public function __construct(
        public string $fileName,
        public string $data,
    ) {
    }
}
