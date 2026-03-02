**eye2win-metamind** is a Symfony-based web application designed to manage a community platform for gaming enthusiasts. It includes features such as user registration, video uploads, Valorant tracker integration, live streams, teams, tournaments, and more. The application leverages modern PHP practices and numerous third-party libraries to provide a rich user experience.

---

## 📦 Prerequisites

Before you begin, ensure you have the following installed:

- PHP 8.1 or higher
- Composer
- Node.js & npm (for frontend assets)
- MySQL (or another supported SQL database)
- Docker & Docker Compose (optional, for development)

---

## 🚀 Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/your-org/eyetwin-metamind.git
   cd eyetwin-metamind
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install JavaScript dependencies & build assets**
   ```bash
   npm install
   npm run dev
   ```

4. **Configure environment variables**
   Copy `.env` to `.env.local` and edit database credentials, mailer settings, and other secrets.
   ```bash
   cp .env .env.local
   ```

5. **Setup the database**
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

6. **Run tests**
   ```bash
   php bin/phpunit
   ```

7. **Start the development server**
   ```bash
   symfony server:start
   ```

   Or using Docker:
   ```bash
   docker-compose up -d --build
   ```

---

## 🔍 Features

- User registration, login, and two-factor authentication
- Profile management and avatar uploads
- Video upload and administration tools
- Valorant statistics and match tracking
- Live streaming support and access control
- Team and tournament management
- Complaint system with categories, priorities and statuses
- Chat and messaging infrastructure
- Admin dashboard and command line utilities

---

## 🗂️ Project Structure

```
src/
├── Controller/          # HTTP controllers
├── Entity/              # Doctrine entities
├── Repository/          # Doctrine repositories
├── Service/             # Domain services
├── Form/                # Symfony form types
├── Command/             # CLI commands
├── EventListener/       # Symfony event listeners
├── EventSubscriber/     # Event subscribers
└── Twig/                # Twig extensions and templates helpers

templates/              # Twig templates
public/                 # Public assets (JS/CSS) & entry point
config/                 # Symfony configuration files
tests/                  # PHPUnit tests
```

---

## 🛠 Development Tips

- Use `php bin/console doctrine:migrations:diff` to generate migration files after updating entities.
- Assets are managed with Webpack Encore; run `npm run watch` for automatic rebuilding.
- Custom CLI tools are available under `src/Command` (e.g. `DebugVideoUploadCommand`, `ListUsersCommand`).

---

## 📚 Documentation & Resources

- Symfony: https://symfony.com/doc/current/index.html
- Doctrine ORM: https://www.doctrine-project.org/projects/orm.html
- Webpack Encore: https://symfony.com/doc/current/frontend.html

---

## 🤝 Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/XYZ`)
3. Commit your changes (`git commit -m 'Add some feature'`)
4. Push to the branch (`git push origin feature/XYZ`)
5. Open a Pull Request

Please ensure tests are updated and passing, and adhere to project coding standards.

---

## 📄 License

Specify your license here. For example, MIT License. See [LICENSE](LICENSE).

---

## ⚠️ Notes

- This README is a starting point; adapt paths and commands based on your environment.
- Secrets and credentials should never be committed to version control.

---

*Thank you for using eye2win-metamind!*