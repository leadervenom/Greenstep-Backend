# 🌱 GreenStep API

A carbon footprint tracking REST API built with Slim Framework 4, MySQL, and JWT authentication.

## 📁 Project Structure

```text
backend_greenstep_api/
├── .agents/
├── certs/
├── public/
│   └── index.php
├── sql/
│   ├── greenstep.sql
│   ├── query3.sql
│   └── seed_data.sql
├── src/
│   ├── Auth/
│   │   └── JwtService.php
│   ├── Controllers/
│   │   ├── ActivityController.php
│   │   ├── AuthController.php
│   │   ├── EcoPhotoController.php
│   │   ├── FriendController.php
│   │   └── GoalController.php
│   ├── Data/
│   │   └── data.php
│   ├── Middleware/
│   │   ├── AuthMiddleware.php
│   │   ├── Cors.php
│   │   └── JsonBodyParser.php
│   ├── Repositories/
│   │   ├── ActivityRepository.php
│   │   ├── ChallengeRepository.php
│   │   ├── GoalRepository.php
│   │   ├── TipRepository.php
│   │   └── UserRepository.php
│   ├── Database.php
│   └── routes.php
├── var/
│   └── greenstep_data.json
├── vendor/
├── .env
├── .env.example
├── composer.json
├── composer.lock
├── debug.php
├── Dockerfile
├── kugaann.http
├── README.md
├── test_api.http
└── .gitignore
```

---

## 📋 Requirements

- [Laragon](https://laragon.org/) (Full version)
- PHP 8.1+
- Composer
- MySQL

---

## 🚀 Setup

**Step 1** — Start Laragon and click **Start All** (Apache + MySQL must be green)

**Step 2** — Clone the repo:
```bash
git clone https://github.com/you/greenstep-api
cd greenstep-api
```

**Step 3** — Install dependencies:
```bash
composer install
```

**Step 4** — Copy `.env.example` to `.env`:
```bash
cp .env.example .env
```

**Step 5** — Generate your own JWT secret:
```bash
php -r "echo bin2hex(random_bytes(32));"
```

**Step 6** — Open `.env` and fill in your values:
```env
DB_PASS=your_mysql_password
JWT_SECRET=your_generated_secret
```

**Step 7** — Create the database and run the schema:
```bash
mysql -u root greenstep_api < sql/greenstep.sql
```
This schema also loads demo data used by the frontend.

**Step 8** — Start the server:
```bash
php -S localhost:8000 -t public
```

API is now running at `http://localhost:8000`

---

## 🔐 Authentication

This API uses JWT Bearer token authentication.

Register and login to get a token:
```http
POST /auth/register
POST /auth/login
```

Seeded demo login:
```text
Email: you@greenstep.app
Password: password
```

Include the token in all protected requests:
```http
Authorization: Bearer <your_token>
```

---

## 📡 API Endpoints

### Public
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/auth/register` | Register a new user |
| POST | `/auth/login` | Login and get JWT token |
| GET | `/api/activities/types` | Get all activity types |
| GET | `/api/tips` | Get eco tips |

### Protected (JWT required)
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/auth/me` | Get current user |
| GET | `/api/dashboard` | Get dashboard summary |
| GET | `/api/activities/today` | Today's logs |
| GET | `/api/activities/history` | Activity history |
| POST | `/api/activities/log` | Log an activity |
| GET/POST | `/api/challenges` | Get or create challenges |
| POST | `/api/challenges/{id}/join` | Join a challenge |
| DELETE | `/api/challenges/{id}` | Delete your challenge |
| GET | `/api/friends` | Get friends list |
| GET | `/api/friends/requests` | Get pending requests |
| POST | `/api/friends/request` | Send friend request |
| POST | `/api/friends/accept/{id}` | Accept friend request |
| POST | `/api/friends/reject/{id}` | Reject friend request |
| GET | `/api/leaderboard` | Community leaderboard |
| GET/POST | `/api/goals` | Get or set goal |
| GET/POST | `/api/photos` | Get or upload eco photos |
| POST | `/api/reset` | Reset to baseline data |

---

## 🗄️ Database

Schema is located at `sql/greenstep.sql`. Run it to create all 10 tables with seed data.

---

## 🛠️ Built With

- [Slim Framework 4](https://www.slimframework.com/)
- [firebase/php-jwt](https://github.com/firebase/php-jwt)
- [vlucas/phpdotenv](https://github.com/vlucas/phpdotenv)
- MySQL 8
