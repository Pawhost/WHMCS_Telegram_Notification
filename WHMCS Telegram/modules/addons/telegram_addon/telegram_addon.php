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
        "name" => "Telegram Admin Notification",
        "description" => "Advanced Telegram notifications per admin & trigger group.",
        "version" => "1.2",
        "author" => "PawHost",
    ];
}

function telegram_addon_activate() {
    try {
        Capsule::schema()->create('mod_telegram_admins', function ($table) {
            $table->increments('id');
            $table->integer('admin_id');  // 0 = Telegram group
            $table->string('chat_id');
            $table->text('groups'); // JSON array of trigger groups
        });
    } catch (Exception $e) {}

    try {
        Capsule::schema()->create('mod_telegram_groups', function ($table) {
            $table->increments('id');
            $table->string('name')->unique();
        });
        // Default trigger groups only on initial creation
        if (Capsule::table('mod_telegram_groups')->count() == 0) {
            Capsule::table('mod_telegram_groups')->insert([
                ['name' => 'Ticket'],
                ['name' => 'Order'],
                ['name' => 'Invoice'],
                ['name' => 'Service'],
                ['name' => 'Domain'],
            ]);
        }
    } catch (Exception $e) {}

    try {
        Capsule::schema()->create('mod_telegram_chatgroups', function ($table) {
            $table->increments('id');
            $table->string('name')->unique();
            $table->string('chat_id');
        });
    } catch (Exception $e) {}

    return ["status" => "success", "description" => "Addon activated successfully."];
}

function telegram_addon_deactivate() {
    // Do not delete to keep data persistent
    // Capsule::schema()->dropIfExists('mod_telegram_admins');
    // Capsule::schema()->dropIfExists('mod_telegram_groups');
    // Capsule::schema()->dropIfExists('mod_telegram_chatgroups');
    return ["status" => "success", "description" => "Addon has been deactivated."];
}

function telegram_addon_output($vars) {
    $admins = Capsule::table('tbladmins')->get();
    $entries = Capsule::table('mod_telegram_admins')->get();
    $availableGroups = Capsule::table('mod_telegram_groups')->pluck('name')->toArray();
    $chatGroups = Capsule::table('mod_telegram_chatgroups')->get();

    // Delete admin assignment
    if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
        Capsule::table('mod_telegram_admins')->where('id', $_GET['delete'])->delete();
        echo '<div class="successbox">Admin assignment has been deleted.</div>';
    }

    // Delete trigger group
    if (isset($_GET['delete_group']) && is_numeric($_GET['delete_group'])) {
        $group = Capsule::table('mod_telegram_groups')->find($_GET['delete_group']);
        if ($group) {
            Capsule::table('mod_telegram_groups')->where('id', $_GET['delete_group'])->delete();
            echo '<div class="successbox">Trigger group has been deleted.</div>';
        }
    }

    // Delete Telegram chat group
    if (isset($_GET['delete_chatgroup']) && is_numeric($_GET['delete_chatgroup'])) {
        $cg = Capsule::table('mod_telegram_chatgroups')->find($_GET['delete_chatgroup']);
        if ($cg) {
            Capsule::table('mod_telegram_chatgroups')->where('id', $_GET['delete_chatgroup'])->delete();
            echo '<div class="successbox">Telegram group has been deleted.</div>';
        }
    }

    // Save new admin or chat group assignment with update check
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_id_or_chatgroup'])) {
        $selection = $_POST['admin_id_or_chatgroup'];
        $groups = $_POST['groups'] ?? [];

        if (strpos($selection, 'admin_') === 0) {
            // Normal admin assignment
            $adminId = (int) substr($selection, 6);
            $chatId = trim($_POST['chat_id']);
            if (!$chatId) {
                echo '<div class="errorbox">Please enter a Telegram chat ID.</div>';
            } else {
                // Check if entry exists
                $existing = Capsule::table('mod_telegram_admins')
                    ->where('admin_id', $adminId)
                    ->where('chat_id', $chatId)
                    ->first();

                if ($existing) {
                    // Update
                    Capsule::table('mod_telegram_admins')
                        ->where('id', $existing->id)
                        ->update([
                            'groups' => json_encode($groups)
                        ]);
                    echo '<div class="successbox">Admin assignment updated.</div>';
                } else {
                    // Insert new
                    Capsule::table('mod_telegram_admins')->insert([
                        'admin_id' => $adminId,
                        'chat_id' => $chatId,
                        'groups' => json_encode($groups)
                    ]);
                    echo '<div class="successbox">Admin assignment saved.</div>';
                }
                echo '<script>setTimeout(function(){window.location.href="addonmodules.php?module=telegram_addon";}, 1000);</script>';
            }
        } elseif (strpos($selection, 'chatgroup_') === 0) {
            // Telegram chat group as "admin"
            $chatGroupId = (int) substr($selection, 10);
            $chatGroup = Capsule::table('mod_telegram_chatgroups')->find($chatGroupId);
            if ($chatGroup) {
                $existing = Capsule::table('mod_telegram_admins')
                    ->where('admin_id', 0)
                    ->where('chat_id', $chatGroup->chat_id)
                    ->first();

                if ($existing) {
                    Capsule::table('mod_telegram_admins')
                        ->where('id', $existing->id)
                        ->update([
                            'groups' => json_encode($groups)
                        ]);
                    echo '<div class="successbox">Telegram group assignment updated.</div>';
                } else {
                    Capsule::table('mod_telegram_admins')->insert([
                        'admin_id' => 0,
                        'chat_id' => $chatGroup->chat_id,
                        'groups' => json_encode($groups)
                    ]);
                    echo '<div class="successbox">Telegram group assignment saved.</div>';
                }
                echo '<script>setTimeout(function(){window.location.href="addonmodules.php?module=telegram_addon";}, 1000);</script>';
            } else {
                echo '<div class="errorbox">Invalid Telegram group selected.</div>';
            }
        }
    }

    // Add new trigger group
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_group'])) {
        $groupName = trim($_POST['new_group']);
        if ($groupName && !Capsule::table('mod_telegram_groups')->where('name', $groupName)->exists()) {
            Capsule::table('mod_telegram_groups')->insert(['name' => $groupName]);
            echo '<div class="successbox">Trigger group added.</div>';
            echo '<script>setTimeout(function(){window.location.href="addonmodules.php?module=telegram_addon";}, 1000);</script>';
        }
    }

    // Add new Telegram chat group
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_chatgroup_name'], $_POST['new_chatgroup_chatid'])) {
        $cgName = trim($_POST['new_chatgroup_name']);
        $cgChatId = trim($_POST['new_chatgroup_chatid']);
        if ($cgName && $cgChatId && !Capsule::table('mod_telegram_chatgroups')->where('name', $cgName)->exists()) {
            Capsule::table('mod_telegram_chatgroups')->insert([
                'name' => $cgName,
                'chat_id' => $cgChatId
            ]);
            echo '<div class="successbox">Telegram group added.</div>';
            echo '<script>setTimeout(function(){window.location.href="addonmodules.php?module=telegram_addon";}, 1000);</script>';
        }
    }

    // Output admin/chat group assignments
    echo '<h2>Telegram Admin & Group Assignments</h2>';

    echo '<form method="post" id="assignForm">
        <label>WHMCS Admin or Telegram Group:</label><br>
        <select name="admin_id_or_chatgroup" id="admin_select" style="width: 300px;" required>
            <optgroup label="WHMCS Admins">';
    foreach ($admins as $admin) {
        echo "<option value=\"admin_{$admin->id}\">" . htmlspecialchars($admin->firstname . ' ' . $admin->lastname) . "</option>";
    }
    echo '</optgroup><optgroup label="Telegram Groups">';
    foreach ($chatGroups as $cg) {
        echo "<option value=\"chatgroup_{$cg->id}\">" . htmlspecialchars($cg->name) . "</option>";
    }
    echo '</optgroup></select><br><br>

        <label>Telegram Chat ID (only for WHMCS Admins):</label><br>
        <input type="text" name="chat_id" id="chat_id_input" style="width: 300px;"><br><br>

        <label>Trigger Groups:</label><br>
        <select name="groups[]" multiple size="5" required style="width: 300px;">';
    foreach ($availableGroups as $group) {
        echo "<option value=\"" . htmlspecialchars($group) . "\">" . htmlspecialchars($group) . "</option>";
    }
    echo '</select><br><small>Ctrl / Cmd + click for multiple selection</small><br><br>

        <input type="submit" value="Save" class="btn btn-primary">
    </form>';

    // Display existing assignments
    echo '<h2><br>Current Assignments:</h2><table class="table table-striped" style="min-width: 300px;">';
    echo '<thead><tr><th>Admin / Group</th><th>Chat ID</th><th>Groups</th><th>Action</th></tr></thead><tbody>';
    foreach ($entries as $entry) {
        if ($entry->admin_id === 0) {
            $group = Capsule::table('mod_telegram_chatgroups')->where('chat_id', $entry->chat_id)->first();
            $name = $group ? $group->name : 'Unknown Telegram Group';
        } else {
            $admin = Capsule::table('tbladmins')->where('id', $entry->admin_id)->first();
            $name = $admin ? "{$admin->firstname} {$admin->lastname}" : 'Unknown Admin';
        }
        $groupList = implode(', ', json_decode($entry->groups, true));
        echo "<tr>
            <td>" . htmlspecialchars($name) . "</td>
            <td>" . htmlspecialchars($entry->chat_id) . "</td>
            <td>" . htmlspecialchars($groupList) . "</td>
            <td><a href=\"addonmodules.php?module=telegram_addon&delete={$entry->id}\" onclick=\"return confirm('Really delete?');\">Remove</a></td>
        </tr>";
    }
    echo '</tbody></table>';

    // Manage trigger groups
    echo '<hr><h3>Manage Trigger Groups</h3>
    <form method="post" style="max-width: 400px;">
        <label>Add new trigger group:</label><br>
        <input type="text" name="new_group" required style="width: 300px;">
        <input type="submit" value="Add" class="btn btn-secondary">
    </form>';

    echo '<table class="table table-striped" style="min-width: 300px; max-width: 30%; margin-top: 10px;"><thead><tr><th>Name</th><th>Action</th></tr></thead><tbody>';
    foreach (Capsule::table('mod_telegram_groups')->get() as $group) {
        echo '<tr><td>' . htmlspecialchars($group->name) . '</td><td><a href="addonmodules.php?module=telegram_addon&delete_group=' . $group->id . '" onclick="return confirm(\'Really delete?\');">Delete</a></td></tr>';
    }
    echo '</tbody></table>';

    // Manage Telegram chat groups
    echo '<hr><h3>Manage Telegram Groups</h3>
    <form method="post" style="max-width: 400px;">
        <label>Name of the Telegram group:</label><br>
        <input type="text" name="new_chatgroup_name" required style="width: 300px;"><br><br>
        <label>Telegram Chat ID:</label><br>
        <input type="text" name="new_chatgroup_chatid" required style="width: 300px;"><br><br>
        <input type="submit" value="Add" class="btn btn-secondary">
    </form>';

    echo '<table class="table table-striped" style="max-width: 50%; margin-top: 10px;"><thead><tr><th>Name</th><th>Chat ID</th><th>Action</th></tr></thead><tbody>';
    foreach ($chatGroups as $cg) {
        echo '<tr><td>' . htmlspecialchars($cg->name) . '</td><td>' . htmlspecialchars($cg->chat_id) . '</td><td><a href="addonmodules.php?module=telegram_addon&delete_chatgroup=' . $cg->id . '" onclick="return confirm(\'Really delete?\');">Delete</a></td></tr>';
    }
    echo '</tbody></table>';
}
