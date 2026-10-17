# TradeLens — tutor / marker copy

This zip is the full source **without live API keys**.

## 1. Copy placeholder configs

In `config/`:

- `ai.example.php` → copy to `ai.php`
- `oauth.example.php` → copy to `oauth.php`
- `brokers.example.php` → copy to `brokers.php`
- `db.example.php` → copy to `db.php`

The zip already includes those files as `ai.php`, `oauth.php`, `brokers.php`, and `db.php` filled with **YOUR_… placeholders**. Core pages still run. Google, MetaTrader sync, and AI chat stay off until real keys are added.

## 2. Database

Import `setup.sql` in phpMyAdmin (database name `tradelens`). Default XAMPP: user `root`, empty password. Edit `config/db.php` if your MySQL is different.

## 3. Run

```bash
php -S localhost:8080
```

Open http://localhost:8080

## Keys not included (on purpose)

- OpenRouter (`OPENROUTER_API_KEY`)
- Google OAuth client id / secret
- MetaApi JWT
- Broker cron secret

Replace `YOUR_…` in the config files if you need those integrations for marking.
