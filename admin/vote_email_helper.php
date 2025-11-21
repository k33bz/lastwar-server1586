<?php
/**
 * Vote Email Helper
 * Version: 1.0.0
 *
 * Sends vote notification emails with magic links for email voting
 *
 * Functions:
 * - send_vote_notification_email() - Send vote notification with voting link
 * - send_vote_result_notification_email() - Send vote results after finalization
 */

if (!defined('ADMIN_INIT')) {
    define('ADMIN_INIT', true);
}
if (!defined('ADMIN_BASE_PATH')) {
    define('ADMIN_BASE_PATH', __DIR__);
}

require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/includes/i18n.php';

/**
 * Send vote notification email with magic link
 *
 * @param string $to_email Recipient email address
 * @param string $vote_id Vote ID
 * @param string $vote_title Vote title
 * @param string $vote_description Vote description
 * @param string $alliance_tag Voter's alliance tag
 * @param string $voter_name Voter's name
 * @param string $magic_link Magic link for voting
 * @param string $end_time Vote deadline (ISO 8601 format)
 * @param string|null $language Language code (null = auto-detect)
 * @return bool Success status
 */
function send_vote_notification_email($to_email, $vote_id, $vote_title, $vote_description, $alliance_tag, $voter_name, $magic_link, $end_time, $language = null) {
    // Load translations
    email_load_i18n($language, $to_email);

    // Format deadline
    $deadline = new DateTime($end_time);
    $formatted_deadline = $deadline->format('F j, Y \a\t g:i A') . ' UTC';
    $time_remaining_hours = max(0, floor((time() - $deadline->getTimestamp()) / 3600));

    // Build email HTML
    $html_body = '
    <!DOCTYPE html>
    <html lang="' . htmlspecialchars($language ?? 'en') . '">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>' . __('emails.vote_notification.subject') . '</title>
    </head>
    <body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; background-color: #f5f5f5;">
        <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff;">
            <!-- Header -->
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 30px; text-align: center;">
                <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 700;">🗳️ ' . __('emails.vote_notification.title') . '</h1>
                <p style="margin: 10px 0 0 0; color: rgba(255,255,255,0.9); font-size: 14px;">' . __('emails.vote_notification.subtitle') . '</p>
            </div>

            <!-- Body -->
            <div style="padding: 40px 30px;">
                <!-- Greeting -->
                <p style="margin: 0 0 20px 0; font-size: 16px; color: #333333; line-height: 1.6;">
                    ' . __('emails.vote_notification.greeting', ['name' => htmlspecialchars($voter_name)]) . '
                </p>

                <!-- Vote Info Card -->
                <div style="background: #f8f9fa; border-left: 4px solid #667eea; border-radius: 8px; padding: 20px; margin: 30px 0;">
                    <h2 style="margin: 0 0 15px 0; font-size: 20px; color: #333333;">' . htmlspecialchars($vote_title) . '</h2>
                    <p style="margin: 0; font-size: 14px; color: #666666; line-height: 1.6;">' . nl2br(htmlspecialchars($vote_description)) . '</p>
                </div>

                <!-- Voter Info -->
                <div style="background: #e3f2fd; border-radius: 8px; padding: 15px; margin: 20px 0;">
                    <p style="margin: 0; font-size: 14px; color: #1565c0;">
                        <strong>' . __('emails.vote_notification.voting_as') . ':</strong> ' . htmlspecialchars($alliance_tag) . ' - ' . htmlspecialchars($voter_name) . '
                    </p>
                </div>

                <!-- Deadline Badge -->
                <div style="text-align: center; margin: 30px 0;">
                    <div style="display: inline-block; background: #fff3cd; border: 1px solid #ffc107; border-radius: 20px; padding: 10px 20px;">
                        <span style="font-size: 14px; color: #856404; font-weight: 600;">
                            ⏰ ' . __('emails.vote_notification.deadline') . ': ' . htmlspecialchars($formatted_deadline) . '
                        </span>
                    </div>
                </div>

                <!-- Vote Button -->
                <div style="text-align: center; margin: 30px 0;">
                    <a href="' . htmlspecialchars($magic_link) . '" style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; text-decoration: none; padding: 16px 40px; border-radius: 8px; font-size: 18px; font-weight: 600; box-shadow: 0 4px 15px rgba(102,126,234,0.4);">
                        ' . __('emails.vote_notification.vote_button') . ' →
                    </a>
                </div>

                <!-- Link Box (Alternative) -->
                <div style="background: #f8f9fa; border-radius: 8px; padding: 20px; margin: 30px 0;">
                    <p style="margin: 0 0 10px 0; font-size: 12px; color: #666666; text-transform: uppercase; font-weight: 600;">
                        ' . __('emails.vote_notification.alternative_method') . ':
                    </p>
                    <p style="margin: 0; font-size: 13px; color: #667eea; word-break: break-all; font-family: monospace;">
                        ' . htmlspecialchars($magic_link) . '
                    </p>
                </div>

                <!-- Voting Options Preview -->
                <div style="margin: 30px 0;">
                    <p style="margin: 0 0 15px 0; font-size: 14px; color: #666666; font-weight: 600;">
                        ' . __('emails.vote_notification.voting_options') . ':
                    </p>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <div style="flex: 1; min-width: 150px; background: #f0fff0; border: 1px solid #28a745; border-radius: 6px; padding: 12px; text-align: center;">
                            <span style="font-size: 14px; color: #28a745; font-weight: 600;">✅ ' . __('emails.vote_notification.option_yes') . '</span>
                        </div>
                        <div style="flex: 1; min-width: 150px; background: #fff5f5; border: 1px solid #dc3545; border-radius: 6px; padding: 12px; text-align: center;">
                            <span style="font-size: 14px; color: #dc3545; font-weight: 600;">❌ ' . __('emails.vote_notification.option_no') . '</span>
                        </div>
                        <div style="flex: 1; min-width: 150px; background: #fffef0; border: 1px solid #ffc107; border-radius: 6px; padding: 12px; text-align: center;">
                            <span style="font-size: 14px; color: #856404; font-weight: 600;">⚪ ' . __('emails.vote_notification.option_abstain') . '</span>
                        </div>
                    </div>
                </div>

                <!-- Discord Alternative -->
                <div style="background: #f8f9fa; border-radius: 8px; padding: 20px; margin: 30px 0;">
                    <p style="margin: 0; font-size: 13px; color: #666666; line-height: 1.6;">
                        💬 ' . __('emails.vote_notification.discord_alternative') . '
                    </p>
                </div>

                <!-- Security Notice -->
                <div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; padding: 15px; margin: 30px 0;">
                    <p style="margin: 0 0 10px 0; font-size: 13px; color: #856404; font-weight: 600;">
                        🔒 ' . __('emails.vote_notification.security_title') . '
                    </p>
                    <p style="margin: 0; font-size: 12px; color: #856404; line-height: 1.6;">
                        ' . __('emails.vote_notification.security_notice') . '
                    </p>
                </div>

                <!-- Vote ID -->
                <p style="margin: 30px 0 0 0; font-size: 12px; color: #999999; text-align: center; font-family: monospace;">
                    ' . __('emails.vote_notification.vote_id') . ': ' . htmlspecialchars($vote_id) . '
                </p>
            </div>

            <!-- Footer -->
            <div style="background: #f8f9fa; padding: 30px; text-align: center; border-top: 1px solid #e9ecef;">
                <p style="margin: 0 0 10px 0; font-size: 12px; color: #999999;">
                    ' . __('emails.no_reply_notice') . '
                </p>
                <p style="margin: 0; font-size: 12px; color: #999999;">
                    © ' . date('Y') . ' ' . __('emails.footer_copyright') . '
                </p>
            </div>
        </div>
    </body>
    </html>';

    // Send email
    $subject = __('emails.vote_notification.subject') . ': ' . $vote_title;

    return send_email($to_email, $subject, $html_body, true);
}

/**
 * Send vote result notification email
 *
 * @param string $to_email Recipient email address
 * @param string $vote_id Vote ID
 * @param string $vote_title Vote title
 * @param string $alliance_tag Voter's alliance tag
 * @param string $voter_name Voter's name
 * @param string|null $voter_choice Voter's choice (null if didn't vote)
 * @param array $results Vote results (outcome, yes_count, no_count, abstain_count, absent_count)
 * @param string|null $language Language code (null = auto-detect)
 * @return bool Success status
 */
function send_vote_result_notification_email($to_email, $vote_id, $vote_title, $alliance_tag, $voter_name, $voter_choice, $results, $language = null) {
    // Load translations
    email_load_i18n($language, $to_email);

    // Determine outcome styling
    $outcome_emoji = $results['outcome'] === 'approved' ? '✅' : ($results['outcome'] === 'rejected' ? '❌' : '⚖️');
    $outcome_color = $results['outcome'] === 'approved' ? '#28a745' : ($results['outcome'] === 'rejected' ? '#dc3545' : '#ffc107');
    $outcome_bg = $results['outcome'] === 'approved' ? '#f0fff0' : ($results['outcome'] === 'rejected' ? '#fff5f5' : '#fffef0');

    // Voter's choice text
    if ($voter_choice) {
        $voter_choice_text = __('emails.vote_result.you_voted', ['choice' => strtoupper($voter_choice)]);
        $voter_choice_color = '#28a745';
    } else {
        $voter_choice_text = __('emails.vote_result.did_not_vote');
        $voter_choice_color = '#dc3545';
    }

    // Build email HTML
    $html_body = '
    <!DOCTYPE html>
    <html lang="' . htmlspecialchars($language ?? 'en') . '">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>' . __('emails.vote_result.subject') . '</title>
    </head>
    <body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; background-color: #f5f5f5;">
        <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff;">
            <!-- Header -->
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 30px; text-align: center;">
                <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 700;">' . $outcome_emoji . ' ' . __('emails.vote_result.title') . '</h1>
                <p style="margin: 10px 0 0 0; color: rgba(255,255,255,0.9); font-size: 14px;">' . htmlspecialchars($vote_title) . '</p>
            </div>

            <!-- Body -->
            <div style="padding: 40px 30px;">
                <!-- Greeting -->
                <p style="margin: 0 0 20px 0; font-size: 16px; color: #333333; line-height: 1.6;">
                    ' . __('emails.vote_result.greeting', ['name' => htmlspecialchars($voter_name)]) . '
                </p>

                <!-- Outcome Badge -->
                <div style="text-align: center; margin: 30px 0;">
                    <div style="display: inline-block; background: ' . $outcome_bg . '; border: 2px solid ' . $outcome_color . '; border-radius: 12px; padding: 20px 40px;">
                        <span style="font-size: 24px; color: ' . $outcome_color . '; font-weight: 700; text-transform: uppercase;">
                            ' . $outcome_emoji . ' ' . strtoupper($results['outcome']) . '
                        </span>
                    </div>
                </div>

                <!-- Your Vote -->
                <div style="background: #f8f9fa; border-radius: 8px; padding: 20px; margin: 30px 0;">
                    <p style="margin: 0; font-size: 14px; color: ' . $voter_choice_color . '; font-weight: 600;">
                        ' . $voter_choice_text . '
                    </p>
                </div>

                <!-- Results Table -->
                <div style="margin: 30px 0;">
                    <h3 style="margin: 0 0 15px 0; font-size: 16px; color: #333333; font-weight: 600;">
                        ' . __('emails.vote_result.results_breakdown') . ':
                    </h3>
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr style="background: #f0fff0;">
                            <td style="padding: 12px; border: 1px solid #e9ecef; font-size: 14px; color: #28a745;">✅ ' . __('emails.vote_result.yes') . '</td>
                            <td style="padding: 12px; border: 1px solid #e9ecef; font-size: 14px; font-weight: 600; text-align: center;">' . $results['yes_count'] . '</td>
                        </tr>
                        <tr style="background: #fff5f5;">
                            <td style="padding: 12px; border: 1px solid #e9ecef; font-size: 14px; color: #dc3545;">❌ ' . __('emails.vote_result.no') . '</td>
                            <td style="padding: 12px; border: 1px solid #e9ecef; font-size: 14px; font-weight: 600; text-align: center;">' . $results['no_count'] . '</td>
                        </tr>
                        <tr style="background: #fffef0;">
                            <td style="padding: 12px; border: 1px solid #e9ecef; font-size: 14px; color: #856404;">⚪ ' . __('emails.vote_result.abstain') . '</td>
                            <td style="padding: 12px; border: 1px solid #e9ecef; font-size: 14px; font-weight: 600; text-align: center;">' . $results['abstain_count'] . '</td>
                        </tr>
                        <tr style="background: #f8f9fa;">
                            <td style="padding: 12px; border: 1px solid #e9ecef; font-size: 14px; color: #6c757d;">⭕ ' . __('emails.vote_result.absent') . '</td>
                            <td style="padding: 12px; border: 1px solid #e9ecef; font-size: 14px; font-weight: 600; text-align: center;">' . $results['absent_count'] . '</td>
                        </tr>
                        <tr style="background: #e9ecef;">
                            <td style="padding: 12px; border: 1px solid #e9ecef; font-size: 14px; font-weight: 600; color: #333333;">' . __('emails.vote_result.total') . '</td>
                            <td style="padding: 12px; border: 1px solid #e9ecef; font-size: 14px; font-weight: 700; text-align: center;">' . $results['total_eligible'] . '</td>
                        </tr>
                    </table>
                </div>

                <!-- Vote ID -->
                <p style="margin: 30px 0 0 0; font-size: 12px; color: #999999; text-align: center; font-family: monospace;">
                    ' . __('emails.vote_result.vote_id') . ': ' . htmlspecialchars($vote_id) . '
                </p>
            </div>

            <!-- Footer -->
            <div style="background: #f8f9fa; padding: 30px; text-align: center; border-top: 1px solid #e9ecef;">
                <p style="margin: 0 0 10px 0; font-size: 12px; color: #999999;">
                    ' . __('emails.no_reply_notice') . '
                </p>
                <p style="margin: 0; font-size: 12px; color: #999999;">
                    © ' . date('Y') . ' ' . __('emails.footer_copyright') . '
                </p>
            </div>
        </div>
    </body>
    </html>';

    // Send email
    $subject = __('emails.vote_result.subject') . ': ' . $vote_title;

    return send_email($to_email, $subject, $html_body, true);
}
