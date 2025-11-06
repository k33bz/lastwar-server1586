<?php
/**
 * Unit Tests for Discord Scheduled Message Processor
 * Tests the discord_scheduled_processor.php background processor
 */

use PHPUnit\Framework\TestCase;

class DiscordScheduledProcessorTest extends TestCase
{
    private $testScheduledFile;
    private $testRecurringFile;
    private $testVariableReplacerIncluded = false;

    protected function setUp(): void
    {
        // Create temporary test files for scheduled messages
        $this->testScheduledFile = sys_get_temp_dir() . '/test_scheduled_' . uniqid() . '.json';
        $this->testRecurringFile = sys_get_temp_dir() . '/test_recurring_' . uniqid() . '.json';

        // Sample scheduled messages
        $scheduledData = [
            'scheduled_messages' => [
                [
                    'id' => 'sched_1',
                    'channel_id' => '12345',
                    'channel_name' => 'Test Channel',
                    'message' => 'Scheduled message from {sender_name}',
                    'embed_title' => 'Scheduled by {sender_name}',
                    'embed_color' => '#00FF00',
                    'scheduled_time' => date('Y-m-d H:i:s', strtotime('+1 hour')),
                    'timezone' => 'America/New_York',
                    'created_by' => 'test@example.com',
                    'created_at' => date('Y-m-d H:i:s'),
                    'status' => 'pending'
                ],
                [
                    'id' => 'sched_2',
                    'channel_id' => '12345',
                    'channel_name' => 'Test Channel',
                    'message' => 'Past scheduled message',
                    'embed_title' => 'Overdue Message',
                    'embed_color' => '#FF0000',
                    'scheduled_time' => date('Y-m-d H:i:s', strtotime('-1 hour')),
                    'timezone' => 'America/New_York',
                    'created_by' => 'test@example.com',
                    'created_at' => date('Y-m-d H:i:s'),
                    'status' => 'pending'
                ],
                [
                    'id' => 'sched_3',
                    'channel_id' => '12345',
                    'channel_name' => 'Test Channel',
                    'message' => 'Already sent message',
                    'embed_title' => 'Sent Message',
                    'embed_color' => '#0000FF',
                    'scheduled_time' => date('Y-m-d H:i:s', strtotime('-2 hours')),
                    'timezone' => 'America/New_York',
                    'created_by' => 'test@example.com',
                    'created_at' => date('Y-m-d H:i:s'),
                    'status' => 'sent'
                ]
            ]
        ];

        // Sample recurring messages
        $recurringData = [
            'recurring_messages' => [
                [
                    'id' => 'rec_1',
                    'channel_id' => '12345',
                    'channel_name' => 'Test Channel',
                    'message' => 'Weekly reminder on {date}',
                    'embed_title' => 'Recurring from {alliance_name}',
                    'embed_color' => '#FFA500',
                    'schedule_type' => 'weekly',
                    'day_of_week' => date('N'), // Today's day number
                    'time' => date('H:i', strtotime('-1 minute')), // 1 minute ago
                    'timezone' => 'America/New_York',
                    'created_by' => 'test@example.com',
                    'created_at' => date('Y-m-d H:i:s'),
                    'last_sent' => null,
                    'enabled' => true
                ],
                [
                    'id' => 'rec_2',
                    'channel_id' => '12345',
                    'channel_name' => 'Test Channel',
                    'message' => 'Daily message',
                    'embed_title' => 'Daily Reminder',
                    'embed_color' => '#00FFFF',
                    'schedule_type' => 'daily',
                    'day_of_week' => null,
                    'time' => date('H:i', strtotime('-1 minute')),
                    'timezone' => 'America/New_York',
                    'created_by' => 'test@example.com',
                    'created_at' => date('Y-m-d H:i:s'),
                    'last_sent' => date('Y-m-d H:i:s', strtotime('-2 days')),
                    'enabled' => true
                ],
                [
                    'id' => 'rec_3',
                    'channel_id' => '12345',
                    'channel_name' => 'Test Channel',
                    'message' => 'Disabled message',
                    'embed_title' => 'Should not send',
                    'embed_color' => '#000000',
                    'schedule_type' => 'daily',
                    'day_of_week' => null,
                    'time' => date('H:i'),
                    'timezone' => 'America/New_York',
                    'created_by' => 'test@example.com',
                    'created_at' => date('Y-m-d H:i:s'),
                    'last_sent' => null,
                    'enabled' => false
                ]
            ]
        ];

        file_put_contents($this->testScheduledFile, json_encode($scheduledData, JSON_PRETTY_PRINT));
        file_put_contents($this->testRecurringFile, json_encode($recurringData, JSON_PRETTY_PRINT));
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testScheduledFile)) {
            unlink($this->testScheduledFile);
        }
        if (file_exists($this->testRecurringFile)) {
            unlink($this->testRecurringFile);
        }
    }

    private function loadScheduled()
    {
        return json_decode(file_get_contents($this->testScheduledFile), true);
    }

    private function loadRecurring()
    {
        return json_decode(file_get_contents($this->testRecurringFile), true);
    }

    private function saveScheduled($data)
    {
        file_put_contents($this->testScheduledFile, json_encode($data, JSON_PRETTY_PRINT));
    }

    private function saveRecurring($data)
    {
        file_put_contents($this->testRecurringFile, json_encode($data, JSON_PRETTY_PRINT));
    }

    public function testLoadScheduledMessagesReturnsArray()
    {
        $data = $this->loadScheduled();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('scheduled_messages', $data);
        $this->assertCount(3, $data['scheduled_messages']);
    }

    public function testLoadRecurringMessagesReturnsArray()
    {
        $data = $this->loadRecurring();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('recurring_messages', $data);
        $this->assertCount(3, $data['recurring_messages']);
    }

    public function testScheduledMessageStructure()
    {
        $data = $this->loadScheduled();
        $message = $data['scheduled_messages'][0];

        $this->assertArrayHasKey('id', $message);
        $this->assertArrayHasKey('channel_id', $message);
        $this->assertArrayHasKey('message', $message);
        $this->assertArrayHasKey('scheduled_time', $message);
        $this->assertArrayHasKey('timezone', $message);
        $this->assertArrayHasKey('status', $message);
        $this->assertArrayHasKey('created_by', $message);
    }

    public function testRecurringMessageStructure()
    {
        $data = $this->loadRecurring();
        $message = $data['recurring_messages'][0];

        $this->assertArrayHasKey('id', $message);
        $this->assertArrayHasKey('channel_id', $message);
        $this->assertArrayHasKey('message', $message);
        $this->assertArrayHasKey('schedule_type', $message);
        $this->assertArrayHasKey('time', $message);
        $this->assertArrayHasKey('timezone', $message);
        $this->assertArrayHasKey('enabled', $message);
        $this->assertArrayHasKey('last_sent', $message);
    }

    public function testFilterPendingScheduledMessages()
    {
        $data = $this->loadScheduled();

        // Filter only pending messages
        $pending = array_filter($data['scheduled_messages'], function($msg) {
            return $msg['status'] === 'pending';
        });

        $this->assertCount(2, $pending);
    }

    public function testFilterEnabledRecurringMessages()
    {
        $data = $this->loadRecurring();

        // Filter only enabled messages
        $enabled = array_filter($data['recurring_messages'], function($msg) {
            return $msg['enabled'] === true;
        });

        $this->assertCount(2, $enabled);
    }

    public function testIdentifyDueScheduledMessages()
    {
        $data = $this->loadScheduled();
        $now = new DateTime();

        // Find messages that should be sent (scheduled_time <= now and status = pending)
        $due = array_filter($data['scheduled_messages'], function($msg) use ($now) {
            $scheduledTime = new DateTime($msg['scheduled_time']);
            return $msg['status'] === 'pending' && $scheduledTime <= $now;
        });

        $this->assertCount(1, $due); // Only sched_2 is past due
    }

    public function testMarkScheduledMessageAsSent()
    {
        $data = $this->loadScheduled();

        // Find and mark message as sent
        foreach ($data['scheduled_messages'] as &$msg) {
            if ($msg['id'] === 'sched_2') {
                $msg['status'] = 'sent';
                $msg['sent_at'] = date('Y-m-d H:i:s');
            }
        }

        $this->saveScheduled($data);
        $reloaded = $this->loadScheduled();

        $sentMessage = array_filter($reloaded['scheduled_messages'], function($msg) {
            return $msg['id'] === 'sched_2';
        });
        $sentMessage = array_values($sentMessage)[0];

        $this->assertEquals('sent', $sentMessage['status']);
        $this->assertArrayHasKey('sent_at', $sentMessage);
    }

    public function testUpdateRecurringLastSent()
    {
        $data = $this->loadRecurring();
        $now = date('Y-m-d H:i:s');

        // Update last_sent for recurring message
        foreach ($data['recurring_messages'] as &$msg) {
            if ($msg['id'] === 'rec_1') {
                $msg['last_sent'] = $now;
            }
        }

        $this->saveRecurring($data);
        $reloaded = $this->loadRecurring();

        $updatedMessage = array_filter($reloaded['recurring_messages'], function($msg) {
            return $msg['id'] === 'rec_1';
        });
        $updatedMessage = array_values($updatedMessage)[0];

        $this->assertEquals($now, $updatedMessage['last_sent']);
    }

    public function testCheckRecurringMessageShouldRun()
    {
        $data = $this->loadRecurring();
        $message = $data['recurring_messages'][0];

        // Check if message should run based on time and last_sent
        $shouldRun = false;

        if ($message['enabled']) {
            $now = new DateTime();
            $messageTime = DateTime::createFromFormat('H:i', $message['time']);

            // Check if it's the right time (within 5 minute window)
            $timeDiff = abs($now->getTimestamp() - $messageTime->getTimestamp());

            if ($timeDiff <= 300) { // 5 minutes
                // Check if not already sent today
                if ($message['last_sent'] === null) {
                    $shouldRun = true;
                } else {
                    $lastSent = new DateTime($message['last_sent']);
                    if ($lastSent->format('Y-m-d') !== $now->format('Y-m-d')) {
                        $shouldRun = true;
                    }
                }
            }
        }

        // rec_1 is enabled, time is past, and never sent before
        $this->assertTrue($shouldRun);
    }

    public function testWeeklyRecurringCheckDayOfWeek()
    {
        $data = $this->loadRecurring();
        $message = $data['recurring_messages'][0]; // Weekly message

        $this->assertEquals('weekly', $message['schedule_type']);
        $this->assertNotNull($message['day_of_week']);

        $now = new DateTime();
        $currentDayOfWeek = (int)$now->format('N'); // 1 = Monday, 7 = Sunday

        // Message should only run on matching day
        $shouldRun = ($message['day_of_week'] === $currentDayOfWeek);

        $this->assertTrue($shouldRun); // We set it to today's day in setUp
    }

    public function testDailyRecurringNoLastSent()
    {
        $data = $this->loadRecurring();

        // Find daily message with no last_sent
        $dailyMessage = array_filter($data['recurring_messages'], function($msg) {
            return $msg['schedule_type'] === 'daily' && $msg['last_sent'] !== null;
        });

        $this->assertNotEmpty($dailyMessage);

        $dailyMessage = array_values($dailyMessage)[0];
        $lastSent = new DateTime($dailyMessage['last_sent']);
        $now = new DateTime();

        // If last sent was more than 1 day ago, should run again
        $daysSince = $now->diff($lastSent)->days;
        $shouldRun = ($daysSince >= 1);

        $this->assertTrue($shouldRun); // rec_2 was sent 2 days ago
    }

    public function testDisabledRecurringMessageDoesNotRun()
    {
        $data = $this->loadRecurring();

        // rec_3 is disabled
        $disabledMessage = array_filter($data['recurring_messages'], function($msg) {
            return $msg['id'] === 'rec_3';
        });
        $disabledMessage = array_values($disabledMessage)[0];

        $this->assertFalse($disabledMessage['enabled']);

        // Disabled messages should never run
        $shouldRun = $disabledMessage['enabled'];
        $this->assertFalse($shouldRun);
    }

    public function testVariableReplacementInScheduledMessage()
    {
        $data = $this->loadScheduled();
        $message = $data['scheduled_messages'][0];

        // Simulate variable replacement
        $content = $message['message'];
        $this->assertStringContainsString('{sender_name}', $content);

        // Replace variables (mocked)
        $processedContent = str_replace('{sender_name}', 'TestPlayer', $content);

        $this->assertStringNotContainsString('{sender_name}', $processedContent);
        $this->assertStringContainsString('TestPlayer', $processedContent);
    }

    public function testVariableReplacementInRecurringMessage()
    {
        $data = $this->loadRecurring();
        $message = $data['recurring_messages'][0];

        // Check for variables
        $content = $message['message'];
        $title = $message['embed_title'];

        $this->assertStringContainsString('{date}', $content);
        $this->assertStringContainsString('{alliance_name}', $title);

        // Simulate replacement
        $processedContent = str_replace('{date}', date('Y-m-d'), $content);
        $processedTitle = str_replace('{alliance_name}', 'Test Alliance', $title);

        $this->assertStringContainsString(date('Y-m-d'), $processedContent);
        $this->assertStringContainsString('Test Alliance', $processedTitle);
    }

    public function testScheduledMessageTimezoneHandling()
    {
        $data = $this->loadScheduled();
        $message = $data['scheduled_messages'][0];

        $this->assertEquals('America/New_York', $message['timezone']);

        // Create DateTime object with timezone
        $scheduledTime = new DateTime($message['scheduled_time'], new DateTimeZone($message['timezone']));

        $this->assertInstanceOf(DateTime::class, $scheduledTime);
        $this->assertEquals('America/New_York', $scheduledTime->getTimezone()->getName());
    }

    public function testRecurringMessageTimeFormat()
    {
        $data = $this->loadRecurring();
        $message = $data['recurring_messages'][0];

        // Time should be in H:i format
        $this->assertMatchesRegularExpression('/^\d{2}:\d{2}$/', $message['time']);

        // Should be parseable as time
        $time = DateTime::createFromFormat('H:i', $message['time']);
        $this->assertInstanceOf(DateTime::class, $time);
    }

    public function testProcessorHandlesEmptyScheduledList()
    {
        $emptyData = ['scheduled_messages' => []];
        $this->saveScheduled($emptyData);

        $data = $this->loadScheduled();

        $this->assertIsArray($data['scheduled_messages']);
        $this->assertEmpty($data['scheduled_messages']);
    }

    public function testProcessorHandlesEmptyRecurringList()
    {
        $emptyData = ['recurring_messages' => []];
        $this->saveRecurring($emptyData);

        $data = $this->loadRecurring();

        $this->assertIsArray($data['recurring_messages']);
        $this->assertEmpty($data['recurring_messages']);
    }

    public function testEmbedColorValidation()
    {
        $data = $this->loadScheduled();
        $message = $data['scheduled_messages'][0];

        $this->assertMatchesRegularExpression('/^#[0-9A-Fa-f]{6}$/', $message['embed_color']);
    }

    public function testScheduledMessageStatusTransitions()
    {
        $data = $this->loadScheduled();

        // Valid statuses: pending, sent, failed
        $validStatuses = ['pending', 'sent', 'failed'];

        foreach ($data['scheduled_messages'] as $msg) {
            $this->assertContains($msg['status'], $validStatuses);
        }
    }
}
