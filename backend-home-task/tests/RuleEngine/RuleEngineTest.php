<?php

namespace App\Tests\RuleEngine;

use App\Entity\UploadedDependencyFile;
use App\RuleEngine\RuleEngine;
use App\RuleEngine\RuleEvaluationResult;
use App\Service\NotificationService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class RuleEngineTest extends TestCase
{
    /** @var NotificationService&MockObject */
    private $notificationService;

    /** @var LoggerInterface&MockObject */
    private $logger;

    protected function setUp(): void
    {
        $this->notificationService = $this->createMock(NotificationService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testHighVulnerabilityRuleTriggersNotifications(): void
    {
        $file = $this->createBaseFile();
        $file->setStatus('done');
        $file->setVulnerabilityCount(8);

        $this->notificationService
            ->expects($this->once())
            ->method('sendEmail')
            ->willReturn(true);

        $this->notificationService
            ->expects($this->once())
            ->method('sendSlack')
            ->willReturn(false);

        $engine = new RuleEngine($this->notificationService, $this->logger, 5, 15);

        $result = $engine->evaluate($file, null);

        $this->assertInstanceOf(RuleEvaluationResult::class, $result);
        $this->assertTrue($result->hasTriggered());

        $payload = $file->getScanResultPayload();
        $this->assertArrayHasKey('ruleState', $payload);
        $this->assertArrayHasKey(RuleEngine::RULE_HIGH_VULNERABILITY, $payload['ruleState']['notifiedRules']);
    }

    public function testStuckUploadRuleOnlyFiresOnce(): void
    {
        $file = $this->createBaseFile();
        $file->setStatus('processing');
        $file->setUpdatedAt(new \DateTimeImmutable('-30 minutes'));

        $this->notificationService
            ->expects($this->once())
            ->method('sendEmail')
            ->willReturn(true);

        $this->notificationService
            ->expects($this->once())
            ->method('sendSlack')
            ->willReturn(false);

        $engine = new RuleEngine($this->notificationService, $this->logger, 5, 10);

        $first = $engine->evaluate($file, null);
        $this->assertTrue($first->hasTriggered());

        $second = $engine->evaluate($file, null);
        $this->assertFalse($second->hasTriggered());
    }

    private function createBaseFile(): UploadedDependencyFile
    {
        $file = new UploadedDependencyFile();
        $file->setOriginalFilename('composer.lock');
        $file->setStoredFilename('example.lock');
        $file->setFilePath('example.lock');
        $file->setCreatedAt(new \DateTimeImmutable('-1 hour'));
        $file->setUpdatedAt(new \DateTimeImmutable('-1 hour'));
        $file->setScanResultPayload([
            'metadata' => [
                'email' => 'dev@example.com',
                'slackWebhook' => null,
            ],
            'ruleState' => [
                'notifiedRules' => [],
            ],
        ]);

        return $file;
    }
}
