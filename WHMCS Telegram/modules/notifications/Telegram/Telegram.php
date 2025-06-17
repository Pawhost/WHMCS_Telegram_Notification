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
                'Description' => 'Token des Telegram-Bots',
                'Placeholder' => '',
            ],
        ];
    }

    public function notificationSettings()
    {
        $groups = Capsule::table('mod_telegram_groups')->pluck('name')->toArray();
        $options = [];
        foreach ($groups as $group) {
            $options[$group] = $group;
        }

        return [
            'trigger' => [
                'FriendlyName' => 'Trigger Gruppe',
                'Type' => 'dropdown',
                'Options' => $options,
                'Description' => 'Nur Admins mit dieser Gruppen-Zuordnung erhalten diese Nachricht.',
            ],
        ];
    }

    public function getDynamicField($fieldName, $settings)
    {
        return [];
    }

    public function testConnection($settings)
    {
        $botToken = $settings['botToken'];
        $testChatID = Capsule::table('mod_telegram_admins')->value('chat_id');

        if (!$testChatID) {
            throw new Exception("Keine Test-Chat-ID gefunden.");
        }

        $message = urlencode("✅ Verbindung zu Telegram erfolgreich.");
        $url = "https://api.telegram.org/bot{$botToken}/sendMessage?chat_id={$testChatID}&text={$message}";
        $this->sendTelegramMessage($url);
    }

    public function sendNotification(NotificationInterface $notification, $moduleSettings, $notificationSettings)
    {
        $triggerGroup = $notificationSettings['trigger'] ?? '';
        if (!$triggerGroup) {
            return;
        }

        $admins = Capsule::table('mod_telegram_admins')->get();
        foreach ($admins as $admin) {
            $adminGroups = json_decode($admin->groups, true);
            if (!in_array($triggerGroup, $adminGroups)) {
                continue;
            }

            $chatID = $admin->chat_id;
            $botToken = $moduleSettings['botToken'];

            $text = "*" . $notification->getTitle() . "*\n\n" . $notification->getMessage() . "\n\n[Zum Vorgang](" . $notification->getUrl() . ")";
            $url = "https://api.telegram.org/bot{$botToken}/sendMessage?parse_mode=Markdown&chat_id={$chatID}&text=" . urlencode($text);

            $this->sendTelegramMessage($url);
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
            throw new Exception("Telegram API Fehler: $error");
        }

        curl_close($ch);
    }
}
