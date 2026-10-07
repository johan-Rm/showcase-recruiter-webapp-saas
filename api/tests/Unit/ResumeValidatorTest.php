<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Candidate\InvalidResumeException;
use App\Domain\Candidate\ResumeValidator;
use PHPUnit\Framework\TestCase;

/**
 * Garde-fous du CV d'inscription (tâche 091) : PDF réel accepté, non-PDF et fichier trop lourd
 * refusés avant tout envoi à Jarvi.
 */
final class ResumeValidatorTest extends TestCase
{
    public function testNoCvReturnsNull(): void
    {
        self::assertNull(ResumeValidator::validate(null, null));
        self::assertNull(ResumeValidator::validate('cv.pdf', null));
    }

    public function testValidPdfIsAccepted(): void
    {
        $content = base64_encode("%PDF-1.7\n...contenu...");

        $resume = ResumeValidator::validate('Mon CV', $content);

        self::assertNotNull($resume);
        self::assertSame($content, $resume['content']);
        // Nom sûr et suffixé .pdf.
        self::assertSame('Mon CV.pdf', $resume['fileName']);
    }

    public function testNonPdfIsRejected(): void
    {
        $this->expectException(InvalidResumeException::class);
        ResumeValidator::validate('cv.pdf', base64_encode('PK'."\x03\x04".'zip content'));
    }

    public function testInvalidBase64IsRejected(): void
    {
        $this->expectException(InvalidResumeException::class);
        ResumeValidator::validate('cv.pdf', 'not-valid-base64!!!');
    }

    public function testOversizedFileIsRejected(): void
    {
        $content = base64_encode('%PDF-'.str_repeat('a', ResumeValidator::MAX_BYTES + 1));

        $this->expectException(InvalidResumeException::class);
        ResumeValidator::validate('cv.pdf', $content);
    }
}
