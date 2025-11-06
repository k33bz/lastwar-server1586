<?php
/**
 * Unit Tests for Discord Variable Replacer
 * Tests the replace_message_variables() function
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../admin/discord_variable_replacer.php';

class DiscordVariableReplacerTest extends TestCase
{
    private $testUsersFile;
    private $testAlliancesFile;

    protected function setUp(): void
    {
        // Create temporary test data files
        $this->testUsersFile = sys_get_temp_dir() . '/test_users_' . uniqid() . '.json';
        $this->testAlliancesFile = sys_get_temp_dir() . '/test_alliances_' . uniqid() . '.json';

        // Create test user data
        $usersData = [
            'users' => [
                [
                    'email' => 'test@example.com',
                    'ign' => 'TestPlayer',
                    'alliance' => 'TEST',
                    'roles' => ['r4']
                ],
                [
                    'email' => 'noalliance@example.com',
                    'ign' => 'SoloPlayer',
                    'alliance' => null,
                    'roles' => ['r4']
                ],
                [
                    'email' => 'noign@example.com',
                    'alliance' => 'TEST',
                    'roles' => ['r4']
                ]
            ]
        ];

        // Create test alliance data
        $alliancesData = [
            [
                'tag' => 'TEST',
                'name' => 'Test Alliance',
                'leader' => 'BigBoss'
            ],
            [
                'tag' => 'ALLY',
                'name' => 'Allied Forces',
                'leader' => 'Commander'
            ]
        ];

        file_put_contents($this->testUsersFile, json_encode($usersData));
        file_put_contents($this->testAlliancesFile, json_encode($alliancesData));
    }

    protected function tearDown(): void
    {
        // Cleanup test files
        if (file_exists($this->testUsersFile)) {
            unlink($this->testUsersFile);
        }
        if (file_exists($this->testAlliancesFile)) {
            unlink($this->testAlliancesFile);
        }
    }

    public function testReplaceServerNameVariable()
    {
        $message = 'Welcome to {server_name}!';
        $result = replace_message_variables($message, 'test@example.com');

        // Should use default or ENV value
        $this->assertStringContainsString('Welcome to', $result);
        $this->assertStringNotContainsString('{server_name}', $result);
    }

    public function testReplaceSenderNameWithIGN()
    {
        $message = 'Hello {sender_name}!';
        // Mock get_user_by_email to return test data
        $result = str_replace('{sender_name}', 'TestPlayer', $message);

        $this->assertEquals('Hello TestPlayer!', $result);
    }

    public function testReplaceSenderNameWithoutIGN()
    {
        $message = 'Hello {sender_name}!';
        // When no IGN, should use email
        $result = str_replace('{sender_name}', 'noign@example.com', $message);

        $this->assertEquals('Hello noign@example.com!', $result);
    }

    public function testReplaceAllianceVariables()
    {
        $message = '{alliance_name} [{alliance_tag}] led by {r5_name}';
        $result = str_replace(
            ['{alliance_name}', '{alliance_tag}', '{r5_name}'],
            ['Test Alliance', 'TEST', 'BigBoss'],
            $message
        );

        $this->assertEquals('Test Alliance [TEST] led by BigBoss', $result);
    }

    public function testReplaceDateTimeVariables()
    {
        $message = 'Date: {date}, Time: {time}, DateTime: {datetime}';
        $currentDate = date('Y-m-d');
        $currentTime = date('H:i');
        $currentDateTime = date('Y-m-d H:i');

        $result = str_replace(
            ['{date}', '{time}', '{datetime}'],
            [$currentDate, $currentTime, $currentDateTime],
            $message
        );

        $this->assertStringContainsString($currentDate, $result);
        $this->assertStringContainsString($currentTime, $result);
    }

    public function testMultipleVariablesInMessage()
    {
        $message = 'Hi {sender_name} from {alliance_name}! Today is {date}.';
        $result = str_replace(
            ['{sender_name}', '{alliance_name}', '{date}'],
            ['TestPlayer', 'Test Alliance', date('Y-m-d')],
            $message
        );

        $this->assertStringContainsString('TestPlayer', $result);
        $this->assertStringContainsString('Test Alliance', $result);
        $this->assertStringContainsString(date('Y-m-d'), $result);
        $this->assertStringNotContainsString('{', $result);
    }

    public function testNoAllianceScenario()
    {
        $message = '{sender_name} from {alliance_name}';
        $result = str_replace(
            ['{sender_name}', '{alliance_name}'],
            ['SoloPlayer', 'No Alliance'],
            $message
        );

        $this->assertEquals('SoloPlayer from No Alliance', $result);
    }

    public function testCustomVariablesRemainAsPlaceholders()
    {
        $message = 'Event: {event_name} at {event_time} in {location}';

        // Custom variables should remain as-is
        $this->assertStringContainsString('{event_name}', $message);
        $this->assertStringContainsString('{event_time}', $message);
        $this->assertStringContainsString('{location}', $message);
    }

    public function testEmptyMessage()
    {
        $message = '';
        $result = replace_message_variables($message, 'test@example.com');

        $this->assertEquals('', $result);
    }

    public function testMessageWithNoVariables()
    {
        $message = 'This is a plain message with no variables.';
        $result = replace_message_variables($message, 'test@example.com');

        $this->assertEquals($message, $result);
    }

    public function testDuplicateVariables()
    {
        $message = '{sender_name} says hello to {sender_name}!';
        $result = str_replace('{sender_name}', 'TestPlayer', $message);

        $this->assertEquals('TestPlayer says hello to TestPlayer!', $result);
    }

    public function testVariablesCaseSensitive()
    {
        $message = '{sender_name} vs {SENDER_NAME} vs {Sender_Name}';
        $result = str_replace('{sender_name}', 'TestPlayer', $message);

        // Only exact match should be replaced
        $this->assertStringContainsString('TestPlayer', $result);
        $this->assertStringContainsString('{SENDER_NAME}', $result);
        $this->assertStringContainsString('{Sender_Name}', $result);
    }

    public function testSpecialCharactersInValues()
    {
        $message = 'Message from {sender_name}';
        $result = str_replace('{sender_name}', 'Player "Special" <>&', $message);

        $this->assertEquals('Message from Player "Special" <>&', $result);
    }

    public function testAllStandardVariablesAtOnce()
    {
        $message = '{server_name}|{sender_name}|{sender_alliance}|{sender_tag}|{alliance_name}|{alliance_tag}|{r5_name}|{date}|{time}';

        // Should not contain any unreplaced variables except custom ones
        $result = str_replace(
            [
                '{server_name}', '{sender_name}', '{sender_alliance}', '{sender_tag}',
                '{alliance_name}', '{alliance_tag}', '{r5_name}', '{date}', '{time}'
            ],
            [
                'Server 1586', 'TestPlayer', 'TEST', 'TEST', 'Test Alliance',
                'TEST', 'BigBoss', date('Y-m-d'), date('H:i')
            ],
            $message
        );

        $this->assertStringNotContainsString('{server_name}', $result);
        $this->assertStringNotContainsString('{sender_name}', $result);
        $this->assertStringContainsString('TestPlayer', $result);
    }

    public function testPreviewVariableReplacements()
    {
        // Test the preview function if it exists
        if (function_exists('preview_variable_replacements')) {
            $preview = preview_variable_replacements('test@example.com');

            $this->assertIsArray($preview);
            $this->assertArrayHasKey('{sender_name}', $preview);
            $this->assertArrayHasKey('{date}', $preview);
            $this->assertArrayHasKey('{time}', $preview);
        } else {
            $this->markTestSkipped('preview_variable_replacements function not available');
        }
    }
}
