# GuestLink – Cloud Deployment & Professor Presentation Guide
**Madison Park Hotel · Laravel 12 + SQLite WAL + Madi AI (Gemini)**

Your system has been hardened for **24/7 Cloud Production** (automatic HTTPS behind reverse proxies, automatic cloud QR domain detection, zero-data-loss SQLite startup, and production OPcache/Apache tuning) while keeping your existing presentation database intact (**34 rooms, 7 staff accounts, 20 menu items, 16 requests, 7 orders**).

---

## Option 1 (Recommended — Free 24/7 Cloud): Deploy on Render.com

Render gives you a permanent public HTTPS URL (`https://guestlink-madison-park.onrender.com`) that stays online even when your laptop is turned off.

### Step 1 — Put the Project on GitHub (No `git` CLI Required)
1. Sign in to [github.com](https://github.com) and click **New repository** (name it `guestlink-madison-park`, **Private** is fine).
2. Run `tools\pack-for-hosting.ps1` (already pre-built in `deploy-bundles\GuestLink-GitHub-Render.zip` — it strips `.env` secrets automatically!).
3. Extract `deploy-bundles\GuestLink-GitHub-Render.zip` and either:
   - Push it with **GitHub Desktop** (easiest for folders with images), **or**
   - Upload the contents (`render.yaml` + `guestlink/`) to your new GitHub repo.

### Step 2 — 1-Click Blueprint Deploy on Render
1. Go to [dashboard.render.com](https://dashboard.render.com) → **New +** → **Blueprint**.
2. Connect your `guestlink-madison-park` GitHub repository.
3. Render automatically reads `render.yaml` (`rootDir: guestlink`, `runtime: docker`).
4. Paste your **`GEMINI_API_KEY`** when prompted (copy it from your local `guestlink\.env`).
5. Click **Apply** and wait ~3–4 minutes for the Docker build to finish.
6. Once live, open `https://<your-app>.onrender.com/up` (returns HTTP 200) and `https://<your-app>.onrender.com/test-qr`.

> **Presentation Tip for Render Free Tier:** Render's free tier sleeps after 15 minutes of inactivity (~30–45s cold wake-up). Open your link **5 minutes before your professor arrives** so it is already awake and instant! *(Optional: set up a free [UptimeRobot](https://uptimerobot.com) 5-minute ping to `/up` during defense day so it never sleeps.)*

---

## Option 2 (Fastest Zero-Sleep Cloud): Deploy on Railway.app

1. Go to [railway.app](https://railway.app) → **New Project** → **Deploy from GitHub repo**.
2. In service **Settings**, set **Root Directory** = `guestlink` (Railway automatically detects `guestlink/Dockerfile`).
3. Under **Variables**, add:
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_KEY=` *(copy from your local `guestlink\.env`)*
   - `DB_CONNECTION=sqlite`
   - `SESSION_DRIVER=file`
   - `CACHE_STORE=file`
   - `GEMINI_API_KEY=` *(copy from your local `guestlink\.env`)*
   - `GEMINI_MODEL=gemini-3.5-flash-lite`
4. Click **Settings → Networking → Generate Domain** to get your permanent `https://...up.railway.app` URL.
5. *(Optional persistent storage)* Add a Railway Volume mounted at `/var/data` and set `DB_DATABASE=/var/data/database.sqlite` — `entrypoint.sh` automatically copies your bundled presentation DB into it on first boot!

---

## Option 3 (Shared PHP Hosting — Hostinger / cPanel)

We pre-packaged **`deploy-bundles\GuestLink-Hostinger-cPanel.zip`** with `vendor/`, your live SQLite DB, and a secure root `.htaccess`.

1. In Hostinger **hPanel** (or cPanel), ensure PHP version is **8.2 or 8.3** with extensions `pdo_sqlite`, `gd`, `mbstring`, `bcmath`, `fileinfo` enabled.
2. Upload **`GuestLink-Hostinger-cPanel.zip`** into `public_html/` and click **Extract**.
3. Copy `.env.example` → `.env` inside `public_html/`, then fill in:
   - `APP_URL=https://yourdomain.com`
   - `APP_KEY=` *(copy from your local `guestlink\.env`)*
   - `GEMINI_API_KEY=` *(copy from your local `guestlink\.env`)*
4. Ensure `storage/`, `bootstrap/cache/`, `database/`, and `public/uploads/` have `775` permissions.
5. Visit `https://yourdomain.com/login` and `https://yourdomain.com/test-qr` — Room QR codes automatically point to your live HTTPS domain!

---

## Option 4 (Instant Live Safety Net on Defense Day)

If school internet ever has issues or you need an immediate live backup, double-click **`start.bat`** on your laptop. It launches multi-threaded Apache 2.4 + a live `https://*.trycloudflare.com` tunnel automatically.

---

## Professor Presentation Demo Script & Credentials

### Live Demo URLs
- **Room QR Scanner & Portal Launcher:** `https://<your-domain>/test-qr`
- **Staff & Admin Login:** `https://<your-domain>/login`
- **Health Check Endpoint:** `https://<your-domain>/up`

### Staff & Admin Accounts (All Active in Live Database)

| Role | Username | Email | Password | What to Show the Professor |
| :--- | :--- | :--- | :--- | :--- |
| **System Admin** | `admin` | `admin@madisonpark.com` | `admin123` | `/admin` Overview, Live Hotel Controls (Notice Banner & Kitchen Pause), Room QR Reset, DB Backup |
| **Hotel Owner** | `owner` | `owner@madisonpark.com` | `owner123` | Executive Reports (`/admin/reports`), CSV export, AI acceptance rate & ratings |
| **Front Desk** | `front` | `frontdesk@madisonpark.com` | `front123` | Priority queue, Madi AI Triage Accept/Override card, Floating Live Guest Chat, Room Folio |
| **Kitchen / Times Sq.** | `kitch` | `restaurant@madisonpark.com` | `kitch123` | Kitchen Display (KDS), Room Delivery vs Dine-In badges, Forward-only order progression |
| **Maintenance** | `main` | `maintenance@madisonpark.com` | `main123` | Scoped engineering queue, AI photo triage tickets, field technician feedback |
| **Maint. Supervisor** | `maintsup` | `maintsup@madisonpark.com` | `password` | Maintenance supervisor dispatch & assignment |
| **Housekeeping** | `hksup` | `hksup@madisonpark.com` | `password` | Housekeeping queue, extra towels/pillows dispatch |

*(All 7 passwords above were verified directly against your live `database/database.sqlite`.)*

### Suggested 5-Minute Walkthrough Flow
1. **Scan Room QR (`/test-qr`):** Let the professor scan **Room 201** on their own phone — zero app install, works on 4G/5G or Wi-Fi, Light & Dark mode toggle in the top bar.
2. **Ask Madi AI & Submit Urgent Ticket:** Ask Madi a question or submit an urgent request (*"Aircon leaking water"*). Show instant AI priority/department routing (`urgent` → `Maintenance`).
3. **In-Room & Times Square Dining:** Place an order choosing **Room Delivery (Charge to Room)** vs **Times Square Dine-In (Pay at Cashier)** and show how the Room Folio prevents double-billing.
4. **Staff Live Operations (`/login` as `front` & `kitch`):** Show real-time alerts, 1-click **Madi AI Triage** Accept/Override, Floating **Live Guest Chat** (with auto-pause when a human replies), and Kitchen KDS status updates reflecting live on the guest's phone.
5. **Admin Governance (`/login` as `admin`):** Show `/admin` system health, publish a live **Hotel Notice** that immediately appears on the guest portal, and show **QR Token Regeneration** (clean next-guest stay isolation).
