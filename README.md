# MIKHMON V3 - Multi-User Mod

MikroTik Hotspot Monitor — Modified version with **multi-user database system**, **RouterOS 7.x date format fix**, and **Coolify/Docker deployment** support.

> Based on Mikhmon V3 by Laksamadi Guko (GPL v2)

---

## Features

- **Multi-User System** — Multiple users can register and login. Each user manages their own routers independently.
- **Admin Panel** — Admin can manage all users (add/delete) from User Management page.
- **SQLite Database** — No more flat file config. User accounts and router sessions stored in SQLite with bcrypt password hashing.
- **RouterOS 7.x Support** — On-login and scheduler scripts fixed for new `YYYY-MM-DD` date format.
- **Coolify Ready** — Dockerfile + docker-compose optimized for Coolify deployment. No Bad Gateway errors.
- All original Mikhmon V3 features: voucher management, hotspot user management, traffic monitor, reports, etc.

---

## Default Login

| Username | Password | Role |
|----------|----------|------|
| `mikhmon`  | `1234`     | Admin |

> First run e auto create hobe. Login korar por password change kore nin!

---

## Deploy on Coolify (Step by Step)

### Step 1: Git Repository e Push Korun

Apnar VPS/Git service e (Gitea, GitLab, or private Git) ei project ta push korun.

```bash
cd mikhmon-fixed
git init
git add .
git commit -m "Initial commit"
git remote add origin YOUR_GIT_REPO_URL
git push -u origin main
```

### Step 2: Coolify te New Resource Create Korun

1. Coolify dashboard e login korun
2. Apnar **Project** e jan (or create new project)
3. **"+ Add New Resource"** button e click korun
4. **"Private Repository (with Deploy Key)"** or **"Public Repository"** select korun
5. Apnar Git repo URL paste korun

### Step 3: Build Pack Select Korun

> **ETA IMPORTANT — Bad Gateway error ekhane hoy!**

1. Coolify automatically **Nixpacks** select korbe — **ETA CHANGE KORTE HOBE!**
2. Build Pack dropdown theke **"Docker Compose"** select korun
3. Docker Compose file location: `docker-compose.yml` (default thakbe, change na korle cholbe)

### Step 4: Port Configuration

1. **General** tab e jan
2. **"Ports Exposes"** field e likhen: `80`
3. Apnar **domain** set korun (e.g., `mikhmon.yourdomain.com`)
   - Domain provider e **A Record** add korun pointing to your VPS IP

### Step 5: Volume Persist Korun (Database Loss Rodhh)

1. **"Storages"** tab e jan
2. **"+ Add"** click korun
3. Volume name: `mikhmon-data`
4. Source path: `/var/www/html/data` (container path)
5. **"Preserve"** checkbox tick korun — na korle redeploy e data moche jabe!

### Step 6: Deploy Korun

1. **"Deploy"** button e click korun
2. Build logs check korun — successful hole green hobe
3. Apnar domain e browse korun — Mikhmon login page dekhte paben!

### Troubleshooting

| Problem | Solution |
|---------|----------|
| **Bad Gateway (502)** | Build Pack "Docker Compose" select korun, "Nixpacks" na. Port Exposes `80` set korun. |
| **502 after deploy** | Coolify Logs check korun. Container healthy kina check korun. |
| **Database reset on redeploy** | Storages tab e `/var/www/html/data` volume add korun ebong "Preserve" tick korun. |
| **Can't connect to MikroTik** | VPS theke MikroTik IP te port 8728 accessible kina check korun. API service enable korun MikroTik e. |
| **Login page ashche na** | Container logs e PHP error check korun. SQLite extension install ache kina verify korun. |

---

## Deploy Locally (Without Coolify)

Local VPS e or testing er jonno:

```bash
git clone YOUR_REPO_URL mikhmon
cd mikhmon

# Local version use korun (port mapping ache)
docker-compose -f docker-compose.local.yml up -d
```

Browser e open korun: `http://localhost:8080`

Stop korte:
```bash
docker-compose -f docker-compose.local.yml down
```

---

## Deploy Without Docker (Manual)

1. PHP 7.4 install korun `sqlite3` ebong `sockets` extension soho
2. Nginx or Apache configure korun
3. Project files web root e copy korun
4. Permission set korun:
```bash
chmod -R 777 data/
chmod -R 777 img/
```
5. Browser e open korun

---

## Multi-User System

### How It Works

- **Register** — Login page e "Register New Account" link theke new account create korun
- **Login** — Each user apnar own username/password diye login korben
- **Router Isolation** — Each user sudhu nijer routers dekhte parbe, onner ta na
- **Admin** — Admin role users "User Management" page theke sob users manage korte parbe

### User Roles

| Role | Permissions |
|------|------------|
| **Admin** | Manage all users, add/delete users, manage own routers |
| **User** | Register, login, manage only own routers |

### Admin User Management

1. Login korun admin account diye
2. **Admin Settings** page e jan
3. **"Manage Users"** link e click korun
4. User add/delete korte parben

---

## RouterOS 7.x Date Format Fix

### Problem

RouterOS 7.x changed system date format:
- **Old format**: `jan/01/2024`
- **New format**: `2024-01-01`

This broke Mikhmon's on-login expiry calculation — users expired immediately or never expired.

### Fix Applied

**On-Login Script** — Date format auto-detect kora hoyeche. System date `YYYY-MM-DD` format e thakle automatically `jan/01/2024` format e convert kore. Scheduler `next-run` result o same bhabe convert hoy.

**Scheduler Monitor Script** — Same fix. Background service je expired users check kore, seo new date format handle kore.

### After Deploy

Mikhmon e login kore **prottek Hotspot User Profile** open korun ebong **Save** click korun. Eta on-login ebong scheduler scripts re-generate korbe new date-compatible code diye.

---

## Migration from Old Mikhmon

Jodi apni old Mikhmon V3 theke upgrade korchen:

1. System automatically old `include/config.php` flat file detect kore
2. First run e admin user ebong sob router sessions SQLite e migrate kore
3. `data/.migrated` file create hoy jate duplicate migration na hoy
4. Old config format ar use hoy na

---

## Project Structure

```
mikhmon/
├── Dockerfile                  # Docker image (PHP 7.4 + Nginx + Supervisor)
├── docker-compose.yml          # Coolify deployment
├── docker-compose.local.yml    # Local deployment (with port mapping)
├── nginx.conf                  # Nginx config
├── admin.php                   # Admin panel
├── index.php                   # Main dashboard
├── data/                       # SQLite database (persistent)
│   └── mikhmon.db              # Auto-created on first run
├── include/
│   ├── database.php            # Multi-user database system
│   ├── config.php              # Config loader (from database)
│   ├── readcfg.php             # Config parser
│   ├── login.php               # Login page
│   ├── register.php            # Registration page
│   ├── usermanage.php          # User management (admin)
│   └── menu.php                # Navigation
├── hotspot/
│   ├── adduserprofile.php      # Add profile (scripts fixed)
│   ├── userprofilebyname.php   # Edit profile (scripts fixed)
│   └── ...
├── settings/
│   ├── sessions.php            # Router list (per-user)
│   ├── settings.php            # Router config (database)
│   └── ...
├── lib/
│   └── routeros_api.class.php  # RouterOS API
├── report/                     # Sales reports
├── voucher/                    # Voucher templates
└── ...
```

---

## Changelog

### Multi-User Mod (2025)
- Multi-user database system (SQLite + bcrypt)
- User registration page
- Per-user router isolation
- Admin user management panel
- RouterOS 7.x date format fix (on-login + scheduler)
- Coolify-optimized Dockerfile + docker-compose
- Auto-migration from old config.php

### V3.20 (06-30-2021)
- Perbaikan typo script profile on-login

### V3.19 (09-08-2020)
- Penambahan jumlah sisa voucher

### V3.18 (08-16-2019)
- Penambahan harga jual (selling price)

---

## License

GNU General Public License v2.0 — see [LICENSE](LICENSE) file.

Original Mikhmon V3 by Laksamadi Guko.
