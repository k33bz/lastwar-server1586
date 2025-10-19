<?php
/**
 * Alliance Tags API
 * Manages alliance tags, categories, and assignments
 *
 * Documentation:
 * - Alliance Management Guide: https://github.com/k33bz/lastwar-server1586/blob/mainline/admin/ALLIANCE_MANAGEMENT_GUIDE.md
 * - Alliance Data Schema: https://github.com/k33bz/lastwar-server1586/blob/mainline/data/ALLIANCE_SCHEMA.md
 *
 * GitHub Issues: https://github.com/k33bz/lastwar-server1586/issues
 *
 * @version 1.0.0
 * @date 2025-10-16
 */

// Require JWT authentication
require_once 'jwt.php';
$user = require_jwt_session();

// Include helper functions
require_once 'includes/alliance_helper.php';
require_once 'json_helpers.php';
require_once 'audit_logger.php';

// Set JSON header
header('Content-Type: application/json');

// File paths
$tags_file = __DIR__ . '/../data/alliance-tags.json';
$categories_file = __DIR__ . '/../data/tag-categories.json';
$assignments_file = __DIR__ . '/../data/alliance-tag-assignments.json';
$suggestions_file = __DIR__ . '/../data/tag-suggestions.json';

// Get action from query params
$action = $_GET['action'] ?? '';

// Create user token for role checking
$user_token = (object)[
    'sub' => $user->sub,
    'aud' => $user->aud,
    'alliances' => $user->alliances ?? []
];

$is_admin = ($user_token->aud === 'admin' && in_array('*', $user_token->alliances));

try {
    switch ($action) {
        // ===== PUBLIC ENDPOINTS (all authenticated users) =====

        case 'list_categories':
            $categories = read_json_file($categories_file);
            // Filter to active categories only for non-admins
            if (!$is_admin) {
                $categories = array_filter($categories, fn($c) => $c['active'] ?? false);
            }
            usort($categories, fn($a, $b) => ($a['order'] ?? 999) - ($b['order'] ?? 999));
            echo json_encode(['success' => true, 'categories' => $categories]);
            break;

        case 'list_tags':
            $category = $_GET['category'] ?? null;
            $tags = read_json_file($tags_file);

            // Filter to active tags only for non-admins
            if (!$is_admin) {
                $tags = array_filter($tags, fn($t) => $t['active'] ?? false);
            }

            // Filter by category if specified
            if ($category) {
                $tags = array_filter($tags, fn($t) => $t['category'] === $category);
            }

            // Sort by votes descending, then name
            usort($tags, function($a, $b) {
                $vote_diff = ($b['votes'] ?? 0) - ($a['votes'] ?? 0);
                return $vote_diff !== 0 ? $vote_diff : strcmp($a['name'], $b['name']);
            });

            echo json_encode(['success' => true, 'tags' => array_values($tags)]);
            break;

        case 'get_alliance_tags':
            $tag = $_GET['tag'] ?? '';
            if (empty($tag)) {
                throw new Exception('Alliance tag is required');
            }

            $assignments = read_json_file($assignments_file);
            $alliance_tags = $assignments[$tag] ?? ['tags' => [], 'lastUpdated' => null, 'updatedBy' => null];

            echo json_encode(['success' => true, 'data' => $alliance_tags]);
            break;

        case 'suggest_tag':
            // Any authenticated user can suggest a tag
            $tag_name = trim($_POST['name'] ?? '');
            $category = $_POST['category'] ?? '';
            $reason = trim($_POST['reason'] ?? '');

            if (empty($tag_name) || empty($category)) {
                throw new Exception('Tag name and category are required');
            }

            // Validate category exists
            $categories = read_json_file($categories_file);
            $category_exists = false;
            foreach ($categories as $cat) {
                if ($cat['id'] === $category) {
                    $category_exists = true;
                    break;
                }
            }
            if (!$category_exists) {
                throw new Exception('Invalid category');
            }

            $suggestions = read_json_file($suggestions_file);

            // Check for duplicate suggestions
            foreach ($suggestions as $suggestion) {
                if (strtolower($suggestion['name']) === strtolower($tag_name) &&
                    $suggestion['status'] === 'pending') {
                    throw new Exception('This tag has already been suggested and is pending review');
                }
            }

            // Add suggestion
            $suggestion_id = 'suggestion_' . time() . '_' . bin2hex(random_bytes(4));
            $suggestions[] = [
                'id' => $suggestion_id,
                'name' => $tag_name,
                'category' => $category,
                'reason' => $reason,
                'status' => 'pending',
                'submittedBy' => $user->sub,
                'submittedAt' => gmdate('Y-m-d\TH:i:s\Z'),
                'reviewedBy' => null,
                'reviewedAt' => null,
                'reviewNotes' => null
            ];

            if (!write_json_file($suggestions_file, $suggestions)) {
                throw new Exception('Failed to save tag suggestion');
            }

            // Log audit event
            log_audit_event('tag_suggestion_submitted', $user->sub, [
                'suggestion_id' => $suggestion_id,
                'tag_name' => $tag_name,
                'category' => $category,
                'reason' => $reason
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Tag suggestion submitted successfully. Admins will review it soon.',
                'suggestionId' => $suggestion_id
            ]);
            break;

        case 'update_alliance_tags':
            // Users can update tags for their alliances
            $alliance_tag = $_POST['tag'] ?? '';
            $selected_tags = json_decode($_POST['tags'] ?? '[]', true);

            if (empty($alliance_tag)) {
                throw new Exception('Alliance tag is required');
            }

            // Check permission for this alliance
            if (!has_alliance_access($user_token, $alliance_tag)) {
                http_response_code(403);
                throw new Exception('You do not have permission to edit this alliance');
            }

            // Validate all selected tags exist
            $all_tags = read_json_file($tags_file);
            $valid_tag_ids = array_column($all_tags, 'id');
            foreach ($selected_tags as $tag_id) {
                if (!in_array($tag_id, $valid_tag_ids)) {
                    throw new Exception('Invalid tag ID: ' . $tag_id);
                }
            }

            $assignments = read_json_file($assignments_file);
            $old_tags = $assignments[$alliance_tag]['tags'] ?? [];

            $assignments[$alliance_tag] = [
                'tags' => $selected_tags,
                'lastUpdated' => gmdate('Y-m-d\TH:i:s\Z'),
                'updatedBy' => $user->sub
            ];

            if (!write_json_file($assignments_file, $assignments)) {
                throw new Exception('Failed to update alliance tags');
            }

            // Log audit event
            log_audit_event('alliance_tags_updated', $user->sub, [
                'alliance_tag' => $alliance_tag,
                'old_tags' => $old_tags,
                'new_tags' => $selected_tags,
                'tags_added' => array_diff($selected_tags, $old_tags),
                'tags_removed' => array_diff($old_tags, $selected_tags)
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Alliance tags updated successfully'
            ]);
            break;

        // ===== ADMIN-ONLY ENDPOINTS =====

        case 'create_tag':
            if (!$is_admin) {
                http_response_code(403);
                throw new Exception('Admin access required');
            }

            $tag_name = trim($_POST['name'] ?? '');
            $category = $_POST['category'] ?? '';
            $active = isset($_POST['active']) ? (bool)$_POST['active'] : true;

            if (empty($tag_name) || empty($category)) {
                throw new Exception('Tag name and category are required');
            }

            $tags = read_json_file($tags_file);

            // Check for duplicate
            foreach ($tags as $tag) {
                if (strtolower($tag['name']) === strtolower($tag_name)) {
                    throw new Exception('A tag with this name already exists');
                }
            }

            // Generate tag ID
            $tag_id = strtolower(preg_replace('/[^a-z0-9]+/', '_', $tag_name));

            // Ensure unique ID
            $counter = 1;
            $original_id = $tag_id;
            while (array_search($tag_id, array_column($tags, 'id')) !== false) {
                $tag_id = $original_id . '_' . $counter++;
            }

            $tags[] = [
                'id' => $tag_id,
                'name' => $tag_name,
                'category' => $category,
                'votes' => 0,
                'active' => $active,
                'verified' => true,
                'createdAt' => gmdate('Y-m-d\TH:i:s\Z'),
                'createdBy' => $user->sub
            ];

            if (!write_json_file($tags_file, $tags)) {
                throw new Exception('Failed to create tag');
            }

            // Log audit event
            log_audit_event('tag_created', $user->sub, [
                'tag_id' => $tag_id,
                'tag_name' => $tag_name,
                'category' => $category,
                'active' => $active
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Tag created successfully',
                'tagId' => $tag_id
            ]);
            break;

        case 'update_tag':
            if (!$is_admin) {
                http_response_code(403);
                throw new Exception('Admin access required');
            }

            $tag_id = $_POST['id'] ?? '';
            $tag_name = trim($_POST['name'] ?? '');
            $category = $_POST['category'] ?? '';
            $active = isset($_POST['active']) ? (bool)$_POST['active'] : true;

            if (empty($tag_id) || empty($tag_name) || empty($category)) {
                throw new Exception('Tag ID, name, and category are required');
            }

            $tags = read_json_file($tags_file);
            $found = false;

            foreach ($tags as &$tag) {
                if ($tag['id'] === $tag_id) {
                    $tag['name'] = $tag_name;
                    $tag['category'] = $category;
                    $tag['active'] = $active;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                throw new Exception('Tag not found');
            }

            if (!write_json_file($tags_file, $tags)) {
                throw new Exception('Failed to update tag');
            }

            // Log audit event
            log_audit_event('tag_updated', $user->sub, [
                'tag_id' => $tag_id,
                'tag_name' => $tag_name,
                'category' => $category,
                'active' => $active
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Tag updated successfully'
            ]);
            break;

        case 'delete_tag':
            if (!$is_admin) {
                http_response_code(403);
                throw new Exception('Admin access required');
            }

            $tag_id = $_POST['id'] ?? '';

            if (empty($tag_id)) {
                throw new Exception('Tag ID is required');
            }

            $tags = read_json_file($tags_file);
            $tags = array_filter($tags, fn($t) => $t['id'] !== $tag_id);

            if (!write_json_file($tags_file, array_values($tags))) {
                throw new Exception('Failed to delete tag');
            }

            // Remove tag from all assignments
            $assignments = read_json_file($assignments_file);
            foreach ($assignments as $alliance_tag => &$data) {
                $data['tags'] = array_filter($data['tags'], fn($t) => $t !== $tag_id);
                $data['tags'] = array_values($data['tags']);
            }
            write_json_file($assignments_file, $assignments);

            // Log audit event
            log_audit_event('tag_deleted', $user->sub, [
                'tag_id' => $tag_id
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Tag deleted successfully'
            ]);
            break;

        case 'create_category':
            if (!$is_admin) {
                http_response_code(403);
                throw new Exception('Admin access required');
            }

            $name = trim($_POST['name'] ?? '');
            $icon = trim($_POST['icon'] ?? '🏷️');
            $description = trim($_POST['description'] ?? '');
            $order = (int)($_POST['order'] ?? 999);
            $active = isset($_POST['active']) ? (bool)$_POST['active'] : true;

            if (empty($name)) {
                throw new Exception('Category name is required');
            }

            $categories = read_json_file($categories_file);

            // Generate category ID
            $category_id = strtolower(preg_replace('/[^a-z0-9]+/', '_', $name));

            // Check for duplicate
            foreach ($categories as $cat) {
                if ($cat['id'] === $category_id) {
                    throw new Exception('A category with this ID already exists');
                }
            }

            $categories[] = [
                'id' => $category_id,
                'name' => $name,
                'icon' => $icon,
                'description' => $description,
                'order' => $order,
                'active' => $active
            ];

            if (!write_json_file($categories_file, $categories)) {
                throw new Exception('Failed to create category');
            }

            // Log audit event
            log_audit_event('tag_category_created', $user->sub, [
                'category_id' => $category_id,
                'name' => $name,
                'icon' => $icon,
                'order' => $order
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Category created successfully',
                'categoryId' => $category_id
            ]);
            break;

        case 'update_category':
            if (!$is_admin) {
                http_response_code(403);
                throw new Exception('Admin access required');
            }

            $category_id = $_POST['id'] ?? '';
            $name = trim($_POST['name'] ?? '');
            $icon = trim($_POST['icon'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $order = (int)($_POST['order'] ?? 999);
            $active = isset($_POST['active']) ? (bool)$_POST['active'] : true;

            if (empty($category_id) || empty($name)) {
                throw new Exception('Category ID and name are required');
            }

            $categories = read_json_file($categories_file);
            $found = false;

            foreach ($categories as &$cat) {
                if ($cat['id'] === $category_id) {
                    $cat['name'] = $name;
                    $cat['icon'] = $icon;
                    $cat['description'] = $description;
                    $cat['order'] = $order;
                    $cat['active'] = $active;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                throw new Exception('Category not found');
            }

            if (!write_json_file($categories_file, $categories)) {
                throw new Exception('Failed to update category');
            }

            // Log audit event
            log_audit_event('tag_category_updated', $user->sub, [
                'category_id' => $category_id,
                'name' => $name,
                'icon' => $icon,
                'order' => $order,
                'active' => $active
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Category updated successfully'
            ]);
            break;

        case 'delete_category':
            if (!$is_admin) {
                http_response_code(403);
                throw new Exception('Admin access required');
            }

            $category_id = $_POST['id'] ?? '';

            if (empty($category_id)) {
                throw new Exception('Category ID is required');
            }

            // Check if any tags use this category
            $tags = read_json_file($tags_file);
            $tags_in_category = array_filter($tags, fn($t) => $t['category'] === $category_id);

            if (!empty($tags_in_category)) {
                throw new Exception('Cannot delete category: ' . count($tags_in_category) . ' tags are using it');
            }

            $categories = read_json_file($categories_file);
            $categories = array_filter($categories, fn($c) => $c['id'] !== $category_id);

            if (!write_json_file($categories_file, array_values($categories))) {
                throw new Exception('Failed to delete category');
            }

            // Log audit event
            log_audit_event('tag_category_deleted', $user->sub, [
                'category_id' => $category_id
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Category deleted successfully'
            ]);
            break;

        case 'list_suggestions':
            if (!$is_admin) {
                http_response_code(403);
                throw new Exception('Admin access required');
            }

            $suggestions = read_json_file($suggestions_file);

            // Sort by status (pending first) then by date
            usort($suggestions, function($a, $b) {
                if ($a['status'] === 'pending' && $b['status'] !== 'pending') return -1;
                if ($a['status'] !== 'pending' && $b['status'] === 'pending') return 1;
                return strcmp($b['submittedAt'], $a['submittedAt']);
            });

            echo json_encode(['success' => true, 'suggestions' => $suggestions]);
            break;

        case 'review_suggestion':
            if (!$is_admin) {
                http_response_code(403);
                throw new Exception('Admin access required');
            }

            $suggestion_id = $_POST['id'] ?? '';
            $status = $_POST['status'] ?? ''; // approved, rejected
            $notes = trim($_POST['notes'] ?? '');

            if (empty($suggestion_id) || empty($status)) {
                throw new Exception('Suggestion ID and status are required');
            }

            if (!in_array($status, ['approved', 'rejected'])) {
                throw new Exception('Invalid status');
            }

            $suggestions = read_json_file($suggestions_file);
            $found = false;
            $approved_suggestion = null;

            foreach ($suggestions as &$suggestion) {
                if ($suggestion['id'] === $suggestion_id) {
                    $suggestion['status'] = $status;
                    $suggestion['reviewedBy'] = $user->sub;
                    $suggestion['reviewedAt'] = gmdate('Y-m-d\TH:i:s\Z');
                    $suggestion['reviewNotes'] = $notes;
                    $found = true;

                    if ($status === 'approved') {
                        $approved_suggestion = $suggestion;
                    }
                    break;
                }
            }

            if (!$found) {
                throw new Exception('Suggestion not found');
            }

            if (!write_json_file($suggestions_file, $suggestions)) {
                throw new Exception('Failed to update suggestion');
            }

            // If approved, create the tag
            if ($approved_suggestion) {
                $tags = read_json_file($tags_file);

                // Check if tag already exists
                $tag_exists = false;
                foreach ($tags as $tag) {
                    if (strtolower($tag['name']) === strtolower($approved_suggestion['name'])) {
                        $tag_exists = true;
                        break;
                    }
                }

                if (!$tag_exists) {
                    // Generate tag ID
                    $tag_id = strtolower(preg_replace('/[^a-z0-9]+/', '_', $approved_suggestion['name']));

                    // Ensure unique ID
                    $counter = 1;
                    $original_id = $tag_id;
                    while (array_search($tag_id, array_column($tags, 'id')) !== false) {
                        $tag_id = $original_id . '_' . $counter++;
                    }

                    $tags[] = [
                        'id' => $tag_id,
                        'name' => $approved_suggestion['name'],
                        'category' => $approved_suggestion['category'],
                        'votes' => 0,
                        'active' => true,
                        'verified' => true,
                        'createdAt' => gmdate('Y-m-d\TH:i:s\Z'),
                        'createdBy' => $user->sub,
                        'suggestedBy' => $approved_suggestion['submittedBy']
                    ];

                    write_json_file($tags_file, $tags);
                }
            }

            // Log audit event
            log_audit_event('tag_suggestion_reviewed', $user->sub, [
                'suggestion_id' => $suggestion_id,
                'status' => $status,
                'tag_name' => $approved_suggestion ? $approved_suggestion['name'] : '',
                'notes' => $notes
            ]);

            echo json_encode([
                'success' => true,
                'message' => $status === 'approved' ? 'Tag suggestion approved and created' : 'Tag suggestion rejected'
            ]);
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
