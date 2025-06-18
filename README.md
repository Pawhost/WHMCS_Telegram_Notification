# WHMCS Telegram Notification Module

![Telegram Logo](https://telegram.org/img/t_logo.png)

Send automated admin notifications from WHMCS to Telegram based on trigger groups. Supports multiple admins, chat groups, and configurable alert scopes.

## 📦 Features

- 🔔 Send WHMCS admin notifications directly to Telegram
- 👥 Assign multiple Telegram chat IDs or groups per admin
- 🧠 Filter notifications using **Trigger Groups**
- ✅ Easy bot setup with test connection support
- 🛠 Built for WHMCS's native notification system

---

## 📸 Screenshots

### Admin & Group Assignment
> _Example of linking WHMCS Admins and Telegram groups_

![Admin Assignment Screenshot](screenshots/admin-assignment.png)

### Trigger Group Management
> _Add, remove, and manage custom trigger groups_

![Trigger Groups Screenshot](screenshots/trigger-groups.png)

### Telegram Group Management
> _Add, remove, and manage custom Telegram groups_

![Trigger Groups Screenshot](screenshots/telegram-groups.png)

### Notification Trigger
> _How to set up the trigger to a group_

![Telegram Message Screenshot](screenshots/notification-trigger.png)

### Notification in Telegram
> _How notifications appear in your Telegram app_

![Telegram Message Screenshot](screenshots/telegram-notification.png)

---

## 🚀 Installation

1. **Upload the module**
   - Place the files under:  
     `modules/notifications/telegram/`

2. **Create the required database tables**
   - You can use the activation SQL or use the legacy activation module to auto-create:
     - `mod_telegram_admins`
     - `mod_telegram_groups`
     - `mod_telegram_chatgroups`

3. **Configure the module in WHMCS**
   - Go to: `Setup` > `Notifications`
   - Add new notification rule with "Telegram" as the provider
   - Enter your **Telegram Bot Token**

4. **Set up Trigger Groups**
   - Assign WHMCS admins or Telegram chat groups to specific trigger groups
   - Only these will receive the corresponding notifications
  
5. **Set up Notification**
   - Assign WHMCS admins or Telegram chat groups to specific trigger groups
   - Only these will receive the corresponding notifications

---

## 🤖 How to Get a Telegram Bot Token

1. Open Telegram and start a chat with [@BotFather](https://t.me/BotFather)
2. Run `/newbot` and follow the instructions
3. Copy the **Bot Token** provided

---

## 🧪 Testing

To verify your Telegram bot configuration:
- Use the **Test Connection** button in the module settings
- A test message will be sent to the first available chat ID in your configuration

---

## 🛠 Customization

- To define custom trigger groups, insert them into `mod_telegram_groups` table
- Extend filtering logic by modifying the `sendNotification()` method

---

## 🧾 Database Tables

| Table | Purpose |
|-------|---------|
| `mod_telegram_admins` | Stores admin/chat ID and group mappings |
| `mod_telegram_groups` | Defines logical trigger categories |
| `mod_telegram_chatgroups` | Named Telegram chat group entries |

---

## ❓ FAQ

### Can I send messages to group chats?

Yes. Use the group’s chat ID (e.g. `-1001234567890`) when assigning.

### Are Markdown messages supported?

Yes. Telegram messages use Markdown parse mode by default.

### Can I assign multiple groups to one chat ID?

Yes. The admin/group entry stores a JSON array of trigger group names.

---

## 📄 License

MIT © PawHost.de

---

## 🙌 Contributions

Feel free to open issues or PRs to improve this module!

