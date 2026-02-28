# 🎮 EyeTwin — E-Sports Management Platform

**EyeTwin** is a comprehensive e-sports event management platform featuring live streaming, team management, tournament organization, training scheduling, and much more. Built on **Symfony 6.4**, it delivers a rich, feature-packed experience for players, coaches, and administrators alike.

---

## 📋 Table of Contents

- [Project Overview](#project-overview)
- [Key Features](#key-features)
- [Tech Stack](#tech-stack)
- [System Requirements](#system-requirements)
- [Installation](#installation)
- [Architecture](#architecture)
- [Available Commands](#available-commands)
- [External Services](#external-services)
- [Main Routes](#main-routes)
- [Database](#database)
- [Authentication](#authentication)
- [File Uploads](#file-uploads)
- [Deployment](#deployment)
- [Troubleshooting](#troubleshooting)

---

## 🎯 Project Overview

EyeTwin is a multi-functional platform for the e-sports ecosystem, offering:

- 📺 **Live Streaming** with granular access control
- 👥 **Team & Match Management** with roles and statistics
- 🏆 **Full Tournament Organization** from brackets to results
- 📅 **Training Sessions & Scheduling** for coaches and players
- 💬 **Messaging & Community Channels** with real-time notifications
- 🎥 **Video Management** with adaptive streaming
- 🔐 **Advanced Authentication** — 2FA, Face Auth, and OAuth
- 💳 **Virtual Currency & Payments** via Stripe
- 📊 **Comprehensive Admin Dashboards** for full oversight


---

## ✨ Key Features

### 👤 User Management
- ✅ Registration and authentication
- ✅ Customizable user profiles
- ✅ Two-factor authentication (TOTP 2FA)
- ✅ Facial recognition authentication
- ✅ Password recovery flow
- ✅ Avatar management via Cloudinary

### 🎮 Teams & Matches
- ✅ Team creation and management
- ✅ Member roles and tiered permissions
- ✅ Team invitations system
- ✅ Full match lifecycle management
- ✅ Player statistics tracking
- ✅ Reviews and ratings

### 🏆 Tournaments
- ✅ Tournament creation and management
- ✅ Multiple tournament types and formats
- ✅ Difficulty levels configuration
- ✅ Support for team-based and solo play
- ✅ Tournament scheduling and planning

### 📺 Live Streaming
- ✅ Create and manage live streaming sessions
- ✅ Access control (public / private)
- ✅ Real-time chat via WebSocket (Mercure)
- ✅ Screen sharing and HD audio/video
- ✅ Stream history and archives

### 🎥 Video Management
- ✅ Video uploads via Cloudinary
- ✅ Full HD resolution support
- ✅ Automatic video conversion
- ✅ Adaptive streaming
- ✅ Metadata and tagging
- ✅ View count and analytics

### 💬 Communities & Messaging
- ✅ Community channel creation
- ✅ Access request system
- ✅ Messaging with file attachments
- ✅ Media file support
- ✅ Real-time notifications

### 💰 Virtual Currency System
- ✅ In-platform currency (coins)
- ✅ Secure payment processing via Stripe
- ✅ Purchase history
- ✅ Fully guided checkout experience

### 📋 Coaching & Planning
- ✅ Coach application workflow
- ✅ Admin coach approval process
- ✅ Training session planning
- ✅ Skill levels and session types
- ✅ Review and rating system

### 🛡️ Security & Administration
- ✅ Advanced 2FA and facial authentication
- ✅ Backup codes for account recovery
- ✅ hCaptcha on sensitive forms
- ✅ Full audit logs
- ✅ EasyAdmin-powered dashboard
- ✅ Complaint and moderation management

---

## 🛠️ Tech Stack

### Backend
| Technology                  | Version | Purpose       |
|---                          |---      |---            |
| **Symfony**                 | 6.4     | PHP framework |
| **Doctrine ORM**            | 3.6     | Database mapping |
| **Symfony Security Bundle** | —       | Authentication authorization |
| **EasyAdmin**               | 4.28    | Admin interface |
| **MySQL**                   | 8.0+    | Relational database |

### Frontend
| Technology | Version | Purpose |
|---|---|---|
| **Twig** | — | Templating engine |
| **Bootstrap** | 5.3 | CSS framework |
| **Webpack Encore** | 5.1 | Asset bundling |
| **Stimulus.js** | — | JavaScript framework |
| **Hotwired Turbo** | — | AJAX navigation |

### External Services
| Service | Version | Purpose |
|---|---|---|
| **Cloudinary** | 3.1 | Image & video hosting |
| **Stripe** | 19.3 | Payment processing |
| **Twilio** | 8.11 | SMS & communications |
| **Mercure** | 0.4 | Real-time push notifications |
| **hCaptcha** | 4.5 | Bot protection |

### Development & Tooling
| Tool | Purpose |
|---|---|
| **PHPUnit** | Unit and integration testing |
| **PHPStan** | Static code analysis |
| **Docker Compose** | Containerization |

---

## 📦 System Requirements

### Environment
- **PHP**: 8.1 or higher
- **MySQL**: 8.0+
- **Node.js**: 16+ (for asset bundling)
- **Composer**: 2.0+
- **Docker**: Optional but strongly recommended

### Required PHP Extensions
```
ext-ctype
ext-iconv
ext-pdo_mysql
```

---

## 🚀 Installation

### Step 1 — Clone the Repository
```bash
git clone https://github.com/your-repo/eyetwin.git
cd eyetwin
```

### Step 2 — Install PHP Dependencies
```bash
composer install
```

### Step 3 — Install JavaScript Dependencies
```bash
npm install
```

### Step 4 — Configure Environment Variables
```bash
cp .env .env.local
```

Edit `.env.local` and fill in the required values:

```dotenv
# Database
DATABASE_URL=mysql://root:password@127.0.0.1:3306/eyetwin_platform

# Application
APP_ENV=dev
APP_DEBUG=1
APP_SECRET=your_secret_key

# Cloudinary
CLOUDINARY_URL=cloudinary://api_key:api_secret@cloud_name
CLOUDINARY_CLOUD_NAME=your_cloud_name
CLOUDINARY_API_KEY=your_api_key
CLOUDINARY_API_SECRET=your_api_secret

# Mailer
MAILER_DSN=smtp://user:password@smtp.example.com:587?encryption=tls
MAILER_FROM_EMAIL=noreply@eyetwin.com
MAILER_FROM_NAME="EyeTwin Platform"

# Stripe (optional)
STRIPE_PUBLIC_KEY=pk_test_...
STRIPE_SECRET_KEY=sk_test_...

# Twilio (optional)
TWILIO_ACCOUNT_SID=your_account_sid
TWILIO_AUTH_TOKEN=your_auth_token
TWILIO_PHONE_NUMBER=+1234567890

# 2FA
TOTP_SERVER_NAME="EyeTwin Platform"
TOTP_ISSUER="EyeTwin"

# Mercure (WebSocket / Real-Time)
MERCURE_URL=http://127.0.0.1:3000/.well-known/mercure
MERCURE_PUBLIC_URL=http://127.0.0.1:3000/.well-known/mercure
MERCURE_JWT_SECRET=your_jwt_secret

# CORS
CORS_ALLOW_ORIGIN='^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$'
```

### Step 5 — Set Up the Database
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### Step 6 — Load Fixtures (Test Data)
```bash
php bin/console doctrine:fixtures:load
```

### Step 7 — Build Assets
```bash
# Production build
npm run build

# Development mode with hot reload
npm run dev-server
```

### Step 8 — Create an Admin User
```bash
php bin/console app:make-user-admin your_user_id
```

### Step 9 — Start the Application

**With Docker Compose:**
```bash
docker-compose up -d
```

**Without Docker (PHP built-in server):**
```bash
cd public
php -S localhost:8000
```

The application will be available at: **http://localhost:8000**

---

## 🏗️ Architecture

### Directory Structure

```
eyetwin/
├── src/
│   ├── Kernel.php                 # Symfony Kernel
│   ├── Command/                   # CLI Commands
│   ├── Controller/                # Controllers
│   │   ├── Admin/                 # Admin panel controllers
│   │   ├── Api/                   # REST API endpoints
│   │   └── Community/             # Community controllers
│   ├── Entity/                    # Doctrine entities
│   ├── Repository/                # Data repositories
│   ├── Service/                   # Business logic services
│   ├── Form/                      # Form types
│   ├── Security/                  # Auth & security
│   ├── EventListener/             # Event listeners
│   └── EventSubscriber/           # Event subscribers
│
├── config/
│   ├── packages/                  # Symfony package configs
│   ├── routes.yaml                # Main route definitions
│   ├── services.yaml              # Dependency injection
│   └── routes/                    # Additional route files
│
├── templates/                     # Twig templates
│   ├── admin/                     # Admin views
│   ├── home/                      # Landing page
│   ├── live/                      # Live streaming views
│   ├── video/                     # Video management views
│   ├── coach/                     # Coaching views
│   ├── team/                      # Team management views
│   ├── tournoi/                   # Tournament views
│   └── ...
│
├── public/
│   ├── index.php                  # Application entry point
│   ├── assets/                    # Static assets
│   ├── builds/                    # Compiled assets
│   └── uploads/                   # Local uploads
│
├── assets/
│   ├── app.js                     # Main JavaScript
│   ├── admin.js                   # Admin JavaScript
│   ├── bootstrap.js               # Bootstrap config
│   ├── controllers/               # Stimulus controllers
│   └── styles/                    # SCSS stylesheets
│
├── migrations/                    # Database migrations
├── tests/                         # PHPUnit tests
├── var/
│   ├── cache/                     # Symfony cache
│   └── log/                       # Application logs
│
├── composer.json                  # PHP dependencies
├── package.json                   # Node dependencies
├── webpack.config.js              # Webpack configuration
├── phpunit.dist.xml               # PHPUnit configuration
├── phpstan.neon                   # PHPStan configuration
├── docker-compose.yaml            # Docker Compose setup
└── .env                           # Environment variables
```

### Core Entity Relationships

```
User
├── Video
├── LiveStream
├── Team (via TeamMembership)
├── Channel (via ChannelMember, ChannelJoinRequest)
├── Planning (via TrainingSession)
├── Tournoi
├── Message
├── Complaint
├── Review
├── CoachApplication
├── CoinPurchase
└── NotificationLog
```

---

## 🔧 Available Commands

### User Management
```bash
# Promote a user to admin
php bin/console app:make-user-admin <user_id>

# List all users
php bin/console app:list-users
```

### Database
```bash
# Create the database
php bin/console doctrine:database:create

# Run all pending migrations
php bin/console doctrine:migrations:migrate

# Generate a new blank migration
php bin/console doctrine:migrations:generate

# Show schema diff (pending changes)
php bin/console doctrine:migrations:diff
```

### Assets
```bash
# Build for production
npm run build

# Watch mode for development
npm run watch

# Dev server with hot module replacement
npm run dev-server
```

### Testing & Analysis
```bash
# Run the test suite
php bin/phpunit

# Run PHPStan static analysis
php bin/phpstan analyse src/
```

### Utilities
```bash
# List all registered routes
php bin/console debug:router

# Debug package configuration
php bin/console debug:config <package_name>

# Display session information
php bin/console app:session-info <session_id>

# Test Twilio connection
php bin/console app:test-twilio
```

---

## 🔗 External Services

### Cloudinary — Media Hosting
Handles all image and video storage, processing, and delivery.
- Automatic video transcoding
- Adaptive bitrate streaming
- On-the-fly image optimization
- Global CDN delivery

**Config keys:** `CLOUDINARY_*`

---

### Stripe — Payments
Manages all payment flows and virtual currency purchases.
- Coin pack purchases
- Secure PCI-compliant checkout
- Subscription management (roadmap)

**Config keys:** `STRIPE_*`

---

### Twilio — Communications
Handles SMS and multi-channel notifications.
- SMS-based authentication
- Urgent alerts and notifications
- Multi-channel message delivery

**Config keys:** `TWILIO_*`

---

### Mercure — Real-Time Push
Powers real-time updates across the platform.
- Live chat during streams
- Instant user notifications
- Live score and match updates

**Config keys:** `MERCURE_*`

---

### hCaptcha — Bot Protection
Prevents automated abuse on sensitive endpoints.
- Login and registration forms
- Password reset
- File uploads

**Config file:** `config/packages/meteo_concept_hcaptcha.yaml`

---

## 🛣️ Main Routes

### Public (No Authentication Required)
```
GET  /                           # Landing page
GET  /register                   # Registration form
POST /register                   # Submit registration
GET  /login                      # Login form
POST /login                      # Submit login
GET  /password-reset             # Password reset request
```

### Authenticated Users
```
GET  /home/dashboard             # User dashboard

# Profile
GET  /profile                    # View profile
PUT  /profile/edit               # Edit profile

# Videos
GET  /video/upload               # Video upload form
POST /video/upload               # Submit video
GET  /video/{id}                 # View video

# Live Streaming
GET  /live                       # Browse streams
POST /live/create                # Start a new stream
GET  /live/{id}                  # Watch a stream
POST /live/{id}/join             # Join a stream

# Teams
GET  /team                       # My teams
POST /team/create                # Create a team
GET  /team/{id}                  # View team
GET  /team/{id}/edit             # Edit team

# Tournaments
GET  /tournoi                    # Browse tournaments
POST /tournoi/create             # Create tournament
GET  /tournoi/{id}               # View tournament

# Training & Planning
GET  /planning                   # Planning sessions
POST /planning/create            # Create session
GET  /planning/{id}/join         # Join a session

# Community
GET  /community/channel          # Browse channels
POST /community/channel/create   # Create a channel
GET  /community/channel/{id}     # View channel messages

# Messaging
GET  /message                    # Inbox
POST /message/send               # Send a message

# Coaching
GET  /coach/apply                # Apply as a coach
GET  /coach/applications         # My applications

# Complaints
GET  /complaints                 # My complaints
POST /complaints/new             # Submit a complaint
```

### Admin Panel
```
GET  /admin                      # Admin dashboard
GET  /admin/users                # User management
GET  /admin/videos               # Video moderation
GET  /admin/complaints           # Complaint management
GET  /admin/audit-logs           # Audit logs
GET  /admin/channels             # Channel moderation
GET  /admin/coach-applications   # Coach applications
GET  /admin/teams                # Team management
GET  /admin/tournaments          # Tournament management
GET  /admin/planning             # Planning sessions
```

### REST API
```
GET    /api/users                # List users
GET    /api/users/{id}           # User details
POST   /api/videos               # Create video record
GET    /api/videos/{id}          # Video details
POST   /api/live/stream          # Start a stream
POST   /api/team/create          # Create a team
```

---

## 💾 Database

### Core Schema

**Users**

| Field | Type | Description |
|---|---|---|
| `id` | UUID | Primary key |
| `email` | String | Unique email address |
| `password` | String | Hashed password |
| `username` | String | Unique display name |
| `avatar_url` | String | Cloudinary avatar URL |
| `is_2fa_enabled` | Boolean | Whether 2FA is active |
| `created_at` / `updated_at` | Datetime | Timestamps |

**Videos**

| Field | Type | Description |
|---|---|---|
| `id` | UUID | Primary key |
| `user_id` | FK | Owning user |
| `cloudinary_public_id` | String | Cloudinary reference |
| `duration` | Integer | Duration in seconds |
| `view_count` | Integer | Total views |
| `visibility` | Enum | `PUBLIC` or `PRIVATE` |

**Live Streams**

| Field | Type | Description |
|---|---|---|
| `id` | UUID | Primary key |
| `user_id` | FK | Stream creator |
| `stream_key` | String | Unique stream key |
| `is_live` | Boolean | Currently broadcasting? |
| `viewer_count` | Integer | Current viewers |
| `status` | Enum | `PENDING`, `LIVE`, `COMPLETED` |

**Teams** — Relations: `TeamMembership`, `TeamInvite`

**Channels** — Relations: `ChannelMember`, `ChannelJoinRequest`

**Messages** — Supports attachments and edits

**Planning / Training Sessions** — Linked to coaches and participants

**Tournaments** — Supports team and solo formats with scheduling

> See the `migrations/` directory for the full schema history.

---

## 🔐 Authentication

### Supported Methods

#### 1. Traditional Login / Password
```
POST /login
Body: { email, password }
```

#### 2. Two-Factor Authentication (TOTP)
Generated using `endroid/qr-code`:
- Activate from user settings
- Scan QR code with Google Authenticator or Authy
- Backup codes auto-generated for account recovery

```
GET  /2fa/enable                 # Enable 2FA
POST /2fa/enable                 # Confirm with TOTP code
GET  /2fa/backup-codes           # View backup codes
```

#### 3. Facial Recognition Authentication
Powered by `khanamiryan/qrcode-detector-decoder`:
- Live video face capture
- Real-time identity verification
- Face registered at signup

```
POST /api/face-auth/register     # Register face
POST /api/face-auth/verify       # Verify face
```

#### 4. hCaptcha Bot Protection
Active on:
- Login form
- Registration form
- Password reset
- File upload endpoints

---

## 📤 File Uploads

### Images (Avatars & Profile Pictures)
- **Provider**: Cloudinary
- **Accepted formats**: JPG, PNG, WEBP
- **Max size**: 5 MB
- **Flow**: Client uploads → Cloudinary returns public URL → URL saved to database

### Videos
- **Provider**: Cloudinary (recommended) or local storage
- **Accepted formats**: MP4, WebM, MOV
- **Max resolution**: 4K (Cloudinary handles conversion automatically)
- **Reference**: See [VIDEO_UPLOAD_FIX.md](VIDEO_UPLOAD_FIX.md)

**Entry point — `VideoUploadType.php`:**
```php
->add('video', FileType::class, [
    'label'    => 'Video File',
    'mapped'   => false,
    'required' => true,
    'accept'   => 'video/mp4,video/webm',
])
```

### Message Attachments
- **Types**: Images and documents
- **Max size**: 10 MB
- **Storage**: Cloudinary or local fallback

---

## 🚢 Deployment

### Production Preparation
```bash
# 1. Clone and enter the repository
git clone ... && cd eyetwin

# 2. Install dependencies without dev packages
composer install --no-dev -o --classmap-authoritative

# 3. Build production assets
npm install --production
npm run build

# 4. Warm up and clear cache
php bin/console cache:warmup --env=prod
php bin/console cache:clear --env=prod

# 5. Set production environment variables
APP_ENV=prod
APP_DEBUG=0

# 6. Run database migrations
php bin/console doctrine:migrations:migrate --env=prod --no-interaction

# 7. Set correct file permissions
chmod -R 755 public/
chmod -R 755 var/
```

### Docker Compose Setup
```yaml
services:
  app:
    build: .
    ports:
      - "80:80"
    environment:
      - DATABASE_URL=mysql://root:password@db:3306/eyetwin
    depends_on:
      - db
    volumes:
      - ./public:/app/public

  db:
    image: mysql:8.0
    environment:
      - MYSQL_ROOT_PASSWORD=password
      - MYSQL_DATABASE=eyetwin
    volumes:
      - db_data:/var/lib/mysql

volumes:
  db_data:
```

```bash
docker-compose -f compose.yaml -f compose.override.yaml up -d
```

### Nginx Configuration
```nginx
server {
    listen 80;
    server_name eyetwin.com;
    root /app/public;
    index index.php;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass app:9000;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }
}
```

---

## 🔍 Troubleshooting

### PDOException — SQLSTATE[HY000]: Connection Refused
**Cause**: Cannot connect to the database.

```bash
# Verify DATABASE_URL and try creating the DB
php bin/console doctrine:database:create

# Test connection manually
mysql -u root -p -h 127.0.0.1 -D eyetwin_platform
```

---

### 400 Bad Request on Video Upload
See [VIDEO_UPLOAD_FIX.md](VIDEO_UPLOAD_FIX.md) for full details.

```bash
# Verify the form has the correct enctype
# <form enctype="multipart/form-data">

# Verify Cloudinary credentials
CLOUDINARY_URL=cloudinary://key:secret@cloud_name

# Increase PHP upload limits
post_max_size = 100M
upload_max_filesize = 100M
```

---

### Invalid Mercure Token Error
**Cause**: The Mercure JWT secret is missing or expired.

```bash
# Regenerate a new JWT secret
MERCURE_JWT_SECRET=$(php -r 'echo bin2hex(random_bytes(32));')

# Update .env
MERCURE_JWT_SECRET=your_new_secret
```

---

### Cloudinary — Permission Denied
**Cause**: Invalid API credentials.

```bash
# Check environment values
echo $CLOUDINARY_URL
echo $CLOUDINARY_API_KEY
echo $CLOUDINARY_API_SECRET

# Debug from Symfony
php bin/console debug:config cloudinary
```

---

### Missing Cache or Assets
```bash
# Clear Symfony cache
php bin/console cache:clear --env=dev

# Rebuild JavaScript/CSS assets
npm run build
php bin/console assets:install public/

# Clear browser cache
Ctrl + Shift + Delete (Chrome / Edge)
```

---

### Slow Page Load Times
```bash
# Profile routes and queries
php bin/console debug:router

# Run static analysis
php bin/phpstan analyse src/

# Review database indexes via admin audit logs

# Enable APCu caching
APCu_ENABLED=1
```

---

### 2FA Not Working
```bash
# Check 2FA configuration
php bin/console debug:config scheb_two_factor

# Verify system clock is accurate (TOTP is time-sensitive)
date

# Regenerate backup codes for a user
php bin/console app:regenerate-backup-codes <user_id>
```

---

## 📞 Support & Contributing

- **Bug Reports**: GitHub Issues
- **Discussions**: GitHub Discussions
- **Security Vulnerabilities**: security@eyetwin.com

> Please search existing issues before opening a new one.

---

## 📄 License

Proprietary — All rights reserved © 2026 EyeTwin

---

## 👥 Team

- **Development Lead**: EyeTwin Team
- **DevOps & Infrastructure**: Infrastructure Team

---

## 🗺️ Roadmap

| Feature | Status |
|---|---|
| Native mobile apps (iOS & Android) | Planned |
| AI-powered match & content recommendations | Planned |
| Badge and achievement system | Planned |
| Twitch integration | Planned |
| Discord bot | Planned |
| Advanced analytics & reporting | Planned |
| Machine learning coaching insights | Planned |

---

**Last Updated**: February 28, 2026 — **Version**: 1.0.0