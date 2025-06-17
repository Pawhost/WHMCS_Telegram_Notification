<?php

/**
 * WHMCS Telegram Notification Addon
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use Illuminate\Database\Capsule\Manager as Capsule;

function telegram_addon_config() {
    return [
        "name" => "Telegram Notification",
        "description" => "Erweiterte Telegram Benachrichtigungen pro Admin & Trigger-Gruppe.",
        "version" => "1.3",
        "author" => "PawHost.de",
    ];
}

function telegram_addon_activate() {
    try {
        Capsule::schema()->create('mod_telegram_admins', function ($table) {
            $table->increments('id');
            $table->integer('admin_id')->nullable();
            $table->string('chat_id');
            $table->text('groups'); // JSON
        });
    } catch (Exception $e) {}

    try {
        Capsule::schema()->create('mod_telegram_groups', function ($table) {
            $table->increments('id');
            $table->string('name')->unique();
        });
        // Default Gruppen
        Capsule::table('mod_telegram_groups')->insert([
            ['name' => 'Ticket'],
            ['name' => 'Invoice'],
            ['name' => 'Order'],
            ['name' => 'Service'],
            ['name' => 'Domain'],
        ]);
    } catch (Exception $e) {}

    return ["status" => "success", "description" => "Addon wurde erfolgreich aktiviert."];
}

function telegram_addon_deactivate() {
    Capsule::schema()->dropIfExists('mod_telegram_admins');
    Capsule::schema()->dropIfExists('mod_telegram_groups');
    return ["status" => "success", "description" => "Addon wurde deaktiviert."];
}

function telegram_addon_output($vars) {
    $admins = Capsule::table('tbladmins')->get();
    $availableGroups = Capsule::table('mod_telegram_groups')->pluck('name')->toArray();

    // Admin-Zuweisung löschen
    if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
        Capsule::table('mod_telegram_admins')->where('id', $_GET['delete'])->delete();
        echo '<div class="successbox">Eintrag wurde gelöscht.</div>';
    }

    // Gruppe löschen
    if (isset($_GET['delete_group']) && is_numeric($_GET['delete_group'])) {
        Capsule::table('mod_telegram_groups')->where('id', $_GET['delete_group'])->delete();
        echo '<div class="successbox">Gruppe wurde gelöscht.</div>';
    }

    // Admin-Zuweisung aktualisieren oder erstellen
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['chat_id'])) {
        $adminId = $_POST['admin_id'] !== '' ? (int) $_POST['admin_id'] : null;
        $chatId = trim($_POST['chat_id']);
        $groups = $_POST['groups'] ?? [];

        // Existiert schon eine mit gleicher chat_id?
        $existing = Capsule::table('mod_telegram_admins')->where('chat_id', $chatId)->first();

        if ($existing) {
            Capsule::table('mod_telegram_admins')->where('id', $existing->id)->update([
                'admin_id' => $adminId,
                'groups' => json_encode($groups)
            ]);
            echo '<div class="successbox">Eintrag aktualisiert.</div>';
        } else {
            Capsule::table('mod_telegram_admins')->insert([
                'admin_id' => $adminId,
                'chat_id' => $chatId,
                'groups' => json_encode($groups)
            ]);
            echo '<div class="successbox">Eintrag gespeichert.</div>';
        }
    }

    // Neue Gruppe hinzufügen
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_group'])) {
        $groupName = trim($_POST['new_group']);
        if ($groupName && !Capsule::table('mod_telegram_groups')->where('name', $groupName)->exists()) {
            Capsule::table('mod_telegram_groups')->insert(['name' => $groupName]);
            echo '<div class="successbox">Gruppe hinzugefügt.</div>';
        }
    }

    echo '<h2>Telegram Zuweisungen (Admins & Gruppen)</h2>';

    echo '<form method="post">
        <label>WHMCS Admin (optional für Einzelperson):</label>
        <select name="admin_id">
            <option value="">-- keiner (für Gruppenchat) --</option>';
    foreach ($admins as $admin) {
        echo "<option value=\"{$admin->id}\">{$admin->firstname} {$admin->lastname}</option>";
    }
    echo '</select><br><br>

        <label>Telegram Chat ID (auch Gruppenchat ID möglich):</label><br>
        <input type="text" name="chat_id" required style="width:auto; min-width: 300px;"><br><br>

        <label>Trigger Gruppen:</label><br>
        <select name="groups[]" multiple size="5" required class="form-control" style="width:auto; min-width: 300px;">';
    foreach ($availableGroups as $group) {
        echo "<option value=\"{$group}\">{$group}</option>";
    }
    echo '</select><br><small>Strg / Cmd + Klick für Mehrfachauswahl</small><br><br>

        <input type="submit" value="Speichern / Aktualisieren" class="btn btn-primary">
    </form>';

    // Tabelle mit allen Einträgen
    $entries = Capsule::table('mod_telegram_admins')->get();
    echo '<br><h3>Aktuelle Zuweisungen:</h3><table class="table table-striped">';
    echo '<thead><tr><th>Admin</th><th>Chat ID</th><th>Gruppen</th><th>Aktion</th></tr></thead><tbody>';
    foreach ($entries as $entry) {
        $adminName = $entry->admin_id ? Capsule::table('tbladmins')->where('id', $entry->admin_id)->value('firstname') . ' ' . Capsule::table('tbladmins')->where('id', $entry->admin_id)->value('lastname') : '<em>Gruppe</em>';
        $groupList = implode(', ', json_decode($entry->groups, true));
        echo "<tr>
            <td>{$adminName}</td>
            <td>{$entry->chat_id}</td>
            <td>{$groupList}</td>
            <td><a href=\"addonmodules.php?module=telegram_addon&delete={$entry->id}\" onclick=\"return confirm('Wirklich löschen?');\">Entfernen</a></td>
        </tr>";
    }
    echo '</tbody></table>';

    echo '<hr><h3>Trigger Gruppen verwalten</h3>
	<form method="post" style="margin-bottom:20px;">
		<label>Neue Gruppe hinzufügen:</label><br>
		<input type="text" name="new_group" required>
		<input type="submit" value="Gruppe speichern" class="btn btn-default">
	</form>';

	echo '<table class="table table-bordered table-striped">';
	echo '<thead><tr><th>Gruppenname</th><th>Aktion</th></tr></thead><tbody>';
	$groupEntries = Capsule::table('mod_telegram_groups')->get();
	foreach ($groupEntries as $group) {
		echo "<tr>
			<td>{$group->name}</td>
			<td><a href=\"addonmodules.php?module=telegram_addon&delete_group={$group->id}\" onclick=\"return confirm('Gruppe wirklich löschen?');\">Löschen</a></td>
		</tr>";
	}
	echo '</tbody></table>';
    echo '</ul>';
}