<?php

namespace WHMCS\Module\Notification\Telegram;

use WHMCS\Config\Setting;
use WHMCS\Exception;
use WHMCS\Module\Notification\DescriptionTrait;
use WHMCS\Module\Contracts\NotificationModuleInterface;
use WHMCS\Notification\Contracts\NotificationInterface;
use Illuminate\Database\Capsule\Manager as Capsule;

class Telegram implements NotificationModuleInterface
{
    use DescriptionTrait;

    public function __construct()
    {
        $this->setDisplayName('Telegram')
             ->setLogoFileName('logo.png');
    }

    public function settings()
    {
        return [
            'botToken' => [
                'FriendlyName' => 'Telegram Bot Token',
                'Type' => 'text',
                'Description' => 'Token from the Telegram-Bot.',
                'Placeholder' => '',
            ],
        ];
    }

    public function notificationSettings()
    {
        // Load available trigger groups from the database
        $groups = Capsule::table('mod_telegram_groups')->pluck('name')->toArray();
        $options = [];
        foreach ($groups as $group) {
            $options[$group] = $group;
        }

        return [
            'trigger' => [
                'FriendlyName' => 'Trigger Group',
                'Type' => 'dropdown',
                'Options' => $options,
                'Description' => 'Only admins assigned to this group will receive this notification.',
            ],
        ];
    }

    public function getDynamicField($fieldName, $settings)
    {
        return [];
    }

    public function testConnection($settings)
    {
        $botToken = $settings['botToken'] ?? '';
        if (!$botToken) {
            throw new Exception("Telegram Bot Token is not set.");
        }

        // Get a test chat ID from the admin assignments
        $testChatID = Capsule::table('mod_telegram_admins')->value('chat_id');
        if (!$testChatID) {
            throw new Exception("No test chat ID found.");
        }

        $message = urlencode("✅ Connection to Telegram successful.");
        $url = "https://api.telegram.org/bot{$botToken}/sendMessage?chat_id={$testChatID}&text={$message}";

        $this->sendTelegramMessage($url);
    }

    public function sendNotification(NotificationInterface $notification, $moduleSettings, $notificationSettings)
    {
        $triggerGroup = $notificationSettings['trigger'] ?? '';
        if (!$triggerGroup) {
            // No trigger group selected, do nothing
            return;
        }

        $admins = Capsule::table('mod_telegram_admins')->get();

        foreach ($admins as $admin) {
            $adminGroups = json_decode($admin->groups, true) ?: [];

            if (!in_array($triggerGroup, $adminGroups)) {
                // Admin is not in the selected trigger group
                continue;
            }

            $chatID = $admin->chat_id;
            $botToken = $moduleSettings['botToken'] ?? '';
            if (!$botToken) {
                continue; // skip if bot token missing
            }

            $title = $notification->getTitle();
            $message = $notification->getMessage();
            $urlLink = $notification->getUrl();

            $text = "*" . $title . "*\n\n" . $message . "\n\n[Open](" . $urlLink . ")";
            $telegramUrl = "https://api.telegram.org/bot{$botToken}/sendMessage?parse_mode=Markdown&chat_id={$chatID}&text=" . urlencode($text);

            $this->sendTelegramMessage($telegramUrl);
        }
    }

    private function sendTelegramMessage($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false || $httpCode !== 200) {
            $error = curl_error($ch) ?: "HTTP $httpCode";
            curl_close($ch);
            throw new Exception("Telegram API error: $error");
        }

        curl_close($ch);
    }
}
