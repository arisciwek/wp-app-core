<?php
/**
 * General Settings Tab
 *
 * @package     WP_App_Core
 * @subpackage  Views/Templates/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Views/templates/settings/tab-general.php
 *
 * Description: General/Company settings tab template
 *              Company information, contact, branding, regional settings
 *
 * Changelog:
 * 1.0.0 - 2025-10-19
 * - Initial creation for platform settings
 */

if (!defined('ABSPATH')) {
    die;
}

// $settings is passed from controller
?>

<div class="platform-settings-general">
    <form method="post" action="options.php" id="platform-general-settings-form">
        <?php settings_fields('wpapp_platform_settings'); ?>

        <!-- Company Information Section -->
        <div class="settings-section">
            <h2><?php _e('Company Information', 'wp-app-core'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="company_name"><?php _e('Company Name', 'wp-app-core'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="wpapp_platform_settings[company_name]"
                               id="company_name" value="<?php echo esc_attr($settings['company_name'] ?? ''); ?>"
                               class="regular-text">
                        <p class="description"><?php _e('Your company or platform name', 'wp-app-core'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="company_tagline"><?php _e('Tagline', 'wp-app-core'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="wpapp_platform_settings[company_tagline]"
                               id="company_tagline" value="<?php echo esc_attr($settings['company_tagline'] ?? ''); ?>"
                               class="regular-text">
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="company_address"><?php _e('Address', 'wp-app-core'); ?></label>
                    </th>
                    <td>
                        <textarea name="wpapp_platform_settings[company_address]" id="company_address"
                                  rows="3" class="large-text"><?php echo esc_textarea($settings['company_address'] ?? ''); ?></textarea>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="company_city"><?php _e('City', 'wp-app-core'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="wpapp_platform_settings[company_city]"
                               id="company_city" value="<?php echo esc_attr($settings['company_city'] ?? ''); ?>"
                               class="regular-text">
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="company_country"><?php _e('Country', 'wp-app-core'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="wpapp_platform_settings[company_country]"
                               id="company_country" value="<?php echo esc_attr($settings['company_country'] ?? 'Indonesia'); ?>"
                               class="regular-text">
                    </td>
                </tr>
            </table>
        </div>

        <!-- Contact Information Section -->
        <div class="settings-section">
            <h2><?php _e('Contact Information', 'wp-app-core'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="company_phone"><?php _e('Phone', 'wp-app-core'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="wpapp_platform_settings[company_phone]"
                               id="company_phone" value="<?php echo esc_attr($settings['company_phone'] ?? ''); ?>"
                               class="regular-text">
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="company_email"><?php _e('Company Email', 'wp-app-core'); ?></label>
                    </th>
                    <td>
                        <input type="email" name="wpapp_platform_settings[company_email]"
                               id="company_email" value="<?php echo esc_attr($settings['company_email'] ?? ''); ?>"
                               class="regular-text">
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="company_website"><?php _e('Website', 'wp-app-core'); ?></label>
                    </th>
                    <td>
                        <input type="url" name="wpapp_platform_settings[company_website]"
                               id="company_website" value="<?php echo esc_attr($settings['company_website'] ?? ''); ?>"
                               class="regular-text">
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="support_email"><?php _e('Support Email', 'wp-app-core'); ?></label>
                    </th>
                    <td>
                        <input type="email" name="wpapp_platform_settings[support_email]"
                               id="support_email" value="<?php echo esc_attr($settings['support_email'] ?? ''); ?>"
                               class="regular-text">
                    </td>
                </tr>
            </table>
        </div>

        <!-- Regional Settings Section -->
        <div class="settings-section">
            <h2><?php _e('Regional Settings', 'wp-app-core'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="timezone"><?php _e('Timezone', 'wp-app-core'); ?></label>
                    </th>
                    <td>
                        <select name="wpapp_platform_settings[timezone]" id="timezone">
                            <?php
                            $timezones = timezone_identifiers_list();
                            foreach ($timezones as $tz) {
                                printf(
                                    '<option value="%s" %s>%s</option>',
                                    esc_attr($tz),
                                    selected($settings['timezone'] ?? 'Asia/Jakarta', $tz, false),
                                    esc_html($tz)
                                );
                            }
                            ?>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="date_format"><?php _e('Date Format', 'wp-app-core'); ?></label>
                    </th>
                    <td>
                        <select name="wpapp_platform_settings[date_format]" id="date_format">
                            <option value="d/m/Y" <?php selected($settings['date_format'] ?? 'd/m/Y', 'd/m/Y'); ?>>DD/MM/YYYY</option>
                            <option value="m/d/Y" <?php selected($settings['date_format'] ?? 'd/m/Y', 'm/d/Y'); ?>>MM/DD/YYYY</option>
                            <option value="Y-m-d" <?php selected($settings['date_format'] ?? 'd/m/Y', 'Y-m-d'); ?>>YYYY-MM-DD</option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="default_language"><?php _e('Default Language', 'wp-app-core'); ?></label>
                    </th>
                    <td>
                        <select name="wpapp_platform_settings[default_language]" id="default_language">
                            <option value="id_ID" <?php selected($settings['default_language'] ?? 'id_ID', 'id_ID'); ?>>Bahasa Indonesia</option>
                            <option value="en_US" <?php selected($settings['default_language'] ?? 'id_ID', 'en_US'); ?>>English (US)</option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>
    </form>

    <!-- Sticky Footer with Action Buttons -->
    <div class="settings-footer">
        <p class="submit">
            <?php submit_button(__('Save General Settings', 'wp-app-core'), 'primary', 'submit', false, ['form' => 'platform-general-settings-form']); ?>
            <button type="button" id="reset-general-settings" class="button button-secondary">
                <?php _e('Reset to Default', 'wp-app-core'); ?>
            </button>
        </p>
    </div>
</div>

<style>
.settings-section {
    background: #fff;
    padding: 20px;
    margin-bottom: 20px;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.settings-section h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #e5e5e5;
}
</style>
