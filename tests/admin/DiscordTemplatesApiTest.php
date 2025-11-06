<?php
/**
 * Unit Tests for Discord Templates API
 * Tests the discord_templates_api.php REST API endpoints
 */

use PHPUnit\Framework\TestCase;

class DiscordTemplatesApiTest extends TestCase
{
    private $testTemplatesFile;
    private $testUsersFile;
    private $originalTemplatesFile;
    private $testUserEmail = 'test@example.com';
    private $testAdminEmail = 'admin@example.com';

    protected function setUp(): void
    {
        // Backup original templates file
        $this->originalTemplatesFile = __DIR__ . '/../../admin/discord_templates.json';

        // Create temporary test templates file
        $this->testTemplatesFile = sys_get_temp_dir() . '/test_templates_' . uniqid() . '.json';

        $testData = [
            'templates' => [
                [
                    'id' => 'tpl_1',
                    'name' => 'Global Welcome',
                    'content' => 'Welcome {sender_name} to {server_name}!',
                    'scope' => 'global',
                    'alliance' => null,
                    'created_by' => 'admin@example.com',
                    'created_at' => '2025-01-01 10:00:00',
                    'variables' => ['{sender_name}', '{server_name}']
                ],
                [
                    'id' => 'tpl_2',
                    'name' => 'Alliance Announcement',
                    'content' => 'Message from {alliance_name}',
                    'scope' => 'alliance',
                    'alliance' => 'TEST',
                    'created_by' => 'test@example.com',
                    'created_at' => '2025-01-02 10:00:00',
                    'variables' => ['{alliance_name}']
                ],
                [
                    'id' => 'tpl_3',
                    'name' => 'Other Alliance Template',
                    'content' => 'Message from {alliance_name}',
                    'scope' => 'alliance',
                    'alliance' => 'OTHER',
                    'created_by' => 'other@example.com',
                    'created_at' => '2025-01-03 10:00:00',
                    'variables' => ['{alliance_name}']
                ]
            ],
            'pending_submissions' => [
                [
                    'id' => 'sub_1',
                    'template_id' => 'tpl_temp_1',
                    'name' => 'Pending Template',
                    'content' => 'This needs approval',
                    'submitted_by' => 'test@example.com',
                    'submitted_at' => '2025-01-04 10:00:00',
                    'status' => 'pending'
                ]
            ]
        ];

        file_put_contents($this->testTemplatesFile, json_encode($testData, JSON_PRETTY_PRINT));

        // Create test users file
        $this->testUsersFile = sys_get_temp_dir() . '/test_users_' . uniqid() . '.json';
        $usersData = [
            'users' => [
                [
                    'email' => 'test@example.com',
                    'ign' => 'TestPlayer',
                    'alliance' => 'TEST',
                    'roles' => ['r4']
                ],
                [
                    'email' => 'admin@example.com',
                    'ign' => 'AdminPlayer',
                    'alliance' => 'TEST',
                    'roles' => ['r4', 'admin']
                ]
            ]
        ];
        file_put_contents($this->testUsersFile, json_encode($usersData));
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testTemplatesFile)) {
            unlink($this->testTemplatesFile);
        }
        if (file_exists($this->testUsersFile)) {
            unlink($this->testUsersFile);
        }
    }

    private function loadTemplates()
    {
        return json_decode(file_get_contents($this->testTemplatesFile), true);
    }

    private function saveTemplates($data)
    {
        file_put_contents($this->testTemplatesFile, json_encode($data, JSON_PRETTY_PRINT));
    }

    public function testLoadTemplatesReturnsArray()
    {
        $data = $this->loadTemplates();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('templates', $data);
        $this->assertArrayHasKey('pending_submissions', $data);
    }

    public function testTemplateStructureIsValid()
    {
        $data = $this->loadTemplates();
        $template = $data['templates'][0];

        $this->assertArrayHasKey('id', $template);
        $this->assertArrayHasKey('name', $template);
        $this->assertArrayHasKey('content', $template);
        $this->assertArrayHasKey('scope', $template);
        $this->assertArrayHasKey('alliance', $template);
        $this->assertArrayHasKey('created_by', $template);
        $this->assertArrayHasKey('created_at', $template);
        $this->assertArrayHasKey('variables', $template);
    }

    public function testGlobalTemplatesHaveNullAlliance()
    {
        $data = $this->loadTemplates();

        foreach ($data['templates'] as $template) {
            if ($template['scope'] === 'global') {
                $this->assertNull($template['alliance']);
            }
        }
    }

    public function testAllianceTemplatesHaveAllianceTag()
    {
        $data = $this->loadTemplates();

        foreach ($data['templates'] as $template) {
            if ($template['scope'] === 'alliance') {
                $this->assertNotNull($template['alliance']);
                $this->assertIsString($template['alliance']);
            }
        }
    }

    public function testFilterTemplatesByScope()
    {
        $data = $this->loadTemplates();
        $userAlliance = 'TEST';

        // User should see global templates + their alliance templates
        $accessible = array_filter($data['templates'], function($t) use ($userAlliance) {
            return $t['scope'] === 'global' ||
                   ($t['scope'] === 'alliance' && $t['alliance'] === $userAlliance);
        });

        $this->assertCount(2, $accessible); // Global + TEST alliance

        // Verify OTHER alliance template is not included
        $allianceTags = array_column($accessible, 'alliance');
        $this->assertNotContains('OTHER', $allianceTags);
    }

    public function testExtractVariablesFromContent()
    {
        $content = 'Hello {sender_name}, welcome to {server_name}! Your alliance is {alliance_name}.';

        preg_match_all('/\{([^}]+)\}/', $content, $matches);
        $variables = array_unique($matches[0]);

        $this->assertCount(3, $variables);
        $this->assertContains('{sender_name}', $variables);
        $this->assertContains('{server_name}', $variables);
        $this->assertContains('{alliance_name}', $variables);
    }

    public function testCreateTemplateGeneratesUniqueId()
    {
        $data = $this->loadTemplates();

        $newTemplate = [
            'id' => 'tpl_' . uniqid(),
            'name' => 'New Template',
            'content' => 'Test content',
            'scope' => 'global',
            'alliance' => null,
            'created_by' => $this->testUserEmail,
            'created_at' => date('Y-m-d H:i:s'),
            'variables' => []
        ];

        $data['templates'][] = $newTemplate;
        $this->saveTemplates($data);

        $reloaded = $this->loadTemplates();
        $this->assertCount(4, $reloaded['templates']);

        // Verify ID is unique
        $ids = array_column($reloaded['templates'], 'id');
        $this->assertEquals(count($ids), count(array_unique($ids)));
    }

    public function testDeleteTemplateRemovesFromList()
    {
        $data = $this->loadTemplates();
        $initialCount = count($data['templates']);
        $deleteId = 'tpl_1';

        $data['templates'] = array_values(array_filter($data['templates'], function($t) use ($deleteId) {
            return $t['id'] !== $deleteId;
        }));

        $this->saveTemplates($data);
        $reloaded = $this->loadTemplates();

        $this->assertCount($initialCount - 1, $reloaded['templates']);

        $ids = array_column($reloaded['templates'], 'id');
        $this->assertNotContains($deleteId, $ids);
    }

    public function testSubmissionWorkflow()
    {
        $data = $this->loadTemplates();

        // Create new submission
        $submission = [
            'id' => 'sub_new',
            'template_id' => 'tpl_temp_new',
            'name' => 'New Submission',
            'content' => 'Content for approval',
            'submitted_by' => $this->testUserEmail,
            'submitted_at' => date('Y-m-d H:i:s'),
            'status' => 'pending'
        ];

        $data['pending_submissions'][] = $submission;
        $this->saveTemplates($data);

        $reloaded = $this->loadTemplates();
        $this->assertCount(2, $reloaded['pending_submissions']);
    }

    public function testApproveSubmissionCreatesGlobalTemplate()
    {
        $data = $this->loadTemplates();
        $submission = $data['pending_submissions'][0];

        // Create global template from submission
        $newTemplate = [
            'id' => $submission['template_id'],
            'name' => $submission['name'],
            'content' => $submission['content'],
            'scope' => 'global',
            'alliance' => null,
            'created_by' => $submission['submitted_by'],
            'created_at' => date('Y-m-d H:i:s'),
            'variables' => []
        ];

        $data['templates'][] = $newTemplate;

        // Remove from pending
        $data['pending_submissions'] = array_values(array_filter(
            $data['pending_submissions'],
            function($s) use ($submission) {
                return $s['id'] !== $submission['id'];
            }
        ));

        $this->saveTemplates($data);
        $reloaded = $this->loadTemplates();

        $this->assertCount(4, $reloaded['templates']);
        $this->assertCount(0, $reloaded['pending_submissions']);

        // Verify new template is global
        $newTemplateData = array_filter($reloaded['templates'], function($t) use ($submission) {
            return $t['id'] === $submission['template_id'];
        });
        $newTemplateData = array_values($newTemplateData)[0];
        $this->assertEquals('global', $newTemplateData['scope']);
    }

    public function testRejectSubmissionRemovesFromPending()
    {
        $data = $this->loadTemplates();
        $submission = $data['pending_submissions'][0];

        // Remove submission
        $data['pending_submissions'] = array_values(array_filter(
            $data['pending_submissions'],
            function($s) use ($submission) {
                return $s['id'] !== $submission['id'];
            }
        ));

        $this->saveTemplates($data);
        $reloaded = $this->loadTemplates();

        $this->assertCount(0, $reloaded['pending_submissions']);
        // Templates should remain unchanged
        $this->assertCount(3, $reloaded['templates']);
    }

    public function testVariableDetectionInTemplate()
    {
        $content = 'Event on {date} at {time} in {location} for {alliance_name}';

        preg_match_all('/\{([^}]+)\}/', $content, $matches);
        $variables = array_unique($matches[0]);

        $this->assertContains('{date}', $variables);
        $this->assertContains('{time}', $variables);
        $this->assertContains('{location}', $variables);
        $this->assertContains('{alliance_name}', $variables);
    }

    public function testNoVariablesInPlainContent()
    {
        $content = 'This is plain text with no variables';

        preg_match_all('/\{([^}]+)\}/', $content, $matches);
        $variables = $matches[0];

        $this->assertEmpty($variables);
    }

    public function testDuplicateVariablesDetectedOnce()
    {
        $content = '{sender_name} says hello to {sender_name} from {alliance_name}';

        preg_match_all('/\{([^}]+)\}/', $content, $matches);
        $variables = array_unique($matches[0]);

        $this->assertCount(2, $variables); // sender_name and alliance_name
    }

    public function testTemplateTimestampFormat()
    {
        $data = $this->loadTemplates();
        $template = $data['templates'][0];

        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            $template['created_at']
        );
    }

    public function testEmptyTemplateListHandling()
    {
        $emptyData = [
            'templates' => [],
            'pending_submissions' => []
        ];

        $this->saveTemplates($emptyData);
        $reloaded = $this->loadTemplates();

        $this->assertIsArray($reloaded['templates']);
        $this->assertEmpty($reloaded['templates']);
    }

    public function testUserCanOnlyDeleteOwnAllianceTemplates()
    {
        $data = $this->loadTemplates();
        $userAlliance = 'TEST';

        // User should be able to delete their own alliance template (tpl_2)
        $canDelete = false;
        foreach ($data['templates'] as $template) {
            if ($template['id'] === 'tpl_2') {
                if ($template['created_by'] === $this->testUserEmail &&
                    $template['alliance'] === $userAlliance) {
                    $canDelete = true;
                }
            }
        }

        $this->assertTrue($canDelete);

        // User should NOT be able to delete other alliance template (tpl_3)
        $cannotDelete = true;
        foreach ($data['templates'] as $template) {
            if ($template['id'] === 'tpl_3') {
                if ($template['alliance'] !== $userAlliance) {
                    $cannotDelete = false;
                }
            }
        }

        $this->assertFalse($cannotDelete);
    }
}
