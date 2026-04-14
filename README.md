## RONIN-SUBSCRIPTIONS-SF
**RONIN-SUBSCRIPTIONS-SF** is a professional **Subscription Management** application built with **Symfony 6.4**, **PHP 8.2**, **TypeScript**, **React**, and **PostgreSQL**. This project showcases full-stack web development skills, including backend logic, database integration, templating with Twig, and responsive frontend design.

The application allows users to:

- Create and manage user accounts
- Subscribe to and manage subscription plans
- Handle subscription status, billing, and payments
- Access detailed subscription history and invoices
- Administer subscription plans and monitor analytics through an admin dashboard

## Table of Contents
- [About the Project](#about-the-project)
- [Features](#features)
- [Technologies Used](#technologies-used)
- [Installation](#installation)
- [Usage](#usage)
- [Project Structure](#project-structure)
- [Contributing](#contributing)
- [License](#license)

## About the Project
**RONIN-SUBSCRIPTIONS-SF** is a professional **Subscription Management** application built with **Symfony 6.4**, **PHP 8.2**, **TypeScript**, **React**, and **PostgreSQL**.
This project showcases a full-featured subscription management platform designed to handle subscription plans, user accounts, billing, and advanced analytics. It demonstrates how to build a scalable and robust solution for managing recurring subscriptions.

Key features include:

- User account management (registration, login, profile editing, and role management)
- Subscription plan management (create, edit, delete plans, and pricing)
- Subscription handling (subscribe, change plans, cancel, track status, pause/resume)
- Billing and payments (record payments, generate invoices, payment history)
- Admin dashboard for monitoring subscriptions, analytics, and user activity
- Optional features: subscription history, email notifications, advanced charts, and analytics

This project demonstrates **full-stack web development skills**, including backend logic with **Symfony** and **PHP**, frontend development with **React** and **TypeScript**, and database management using **PostgreSQL**. It also leverages responsive design principles to ensure a seamless user experience.

## Features
The Subscription Management application implements the core and high-impact features using Symfony 6.4.

### User Management
- [x] Create account
- [x] Login / Logout
- [x] Edit profile
- [x] Role management (admin)
- [x] View subscription history (high-impact, optional)

### Subscription Plan Management
- [x] Create / Edit / Delete subscription plan
- [x] Define pricing and plan features
- [x] Enable / disable plans (optional)

### Subscription Management
- [x] Subscribe to a plan
- [x] Change subscription plan
- [x] Cancel subscription
- [x] Track subscription status (active, cancelled, expired)
- [x] Display next billing date
- [x] Pause / Resume subscription (optional, high-impact)

### Billing and Payments
- [x] Simulate payment processing
- [x] Record payments
- [x] Generate invoices (simulated)
- [x] View payment history (optional, high-impact)
- [x] Download invoices (PDF, optional, high-impact)

### Admin Dashboard
- [x] Dashboard overview
- [x] Display summary widgets with total subscriptions, active vs cancelled, and MRR
- [x] Recent activity feed
- [x] Charts and analytics (optional, high-impact)

### Notifications
- [x] Flash messages
- [x] Subscription confirmation
- [x] Upcoming renewal reminder (optional)
- [x] Payment failure notification (optional, high-impact)

### Permissions & Security
- [x] Admin-only sections
- [x] User roles & permissions
- [x] Route protection using Symfony Security
- [x] Activity logs / subscription tracking (optional)

### UX/UI
- [x] Responsive layout
- [x] Subscription plans comparison table
- [x] Status indicators for subscriptions
- [x] Success/error alerts
- [x] Consistent navigation

## Technologies Used
This project uses the following technologies and tools:

- **PHP 8.2** – Server-side scripting language powering the application.
- **Symfony 6.4** – Framework used for building the MVC architecture and managing routes, controllers, and templates.
- **TypeScript** – Superset of JavaScript, providing type safety and better tooling for frontend development.
- **React** – Frontend JavaScript library for building interactive user interfaces.
- **PostgreSQL** – Relational database used for storing subscription data, user accounts, and payment information.
- **Doctrine ORM** – Object-Relational Mapper for managing database entities and interactions.
- **Twig** – Template engine for rendering dynamic HTML views.
- **Bootstrap 5** – Frontend framework for creating responsive and modern designs.
- **HTML5 & CSS3** – Markup and styling technologies for structuring and designing web pages.
- **JavaScript (Vanilla)** – Client-side interactions and dynamic content (Vue.js is optional for advanced features).
- **Composer** – Dependency management tool for PHP packages.
- **Git & GitHub** – Version control and collaboration for managing code and contributing to the project.

## Installation

1. **Clone the repository**
```bash
git clone https://github.com/username/ronin-subscriptions-sf.git
cd ronin-subscriptions-sf
```
2. **Install PHP dependencies**
```bash
composer install
```
3. **Set up environment variables**
```bash
cp .env .env.local
# Edit .env.local with your database credentials (PostgreSQL recommended)
```
4. **Install Node.js dependencies** (for React and TypeScript)
```bash
npm install
```
5. **Create and migrate the database**
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```
6. **Start the Symfony server**
```bash
symfony server:start
```
7. **Build and run the frontend assets**
- For React and TypeScript, run:
```bash
npm run dev
```
This will start the development server and compile the assets for the frontend.

## Usage
Once the Symfony server is running, open your browser at: `http://localhost:8000`.

You can now:

- Create and manage user accounts (registration, login, profile editing)
- Browse and subscribe to available subscription plans
- Manage your subscriptions (change plans, cancel, pause/resume)
- Track billing history and download invoices
- Explore the admin panel to monitor subscriptions, user activity, and analytics
- View subscription history and payment details (optional)
- Receive email notifications for important updates (if enabled)

Make sure to explore all the features, including the subscription management, billing system, and the powerful admin dashboard for detailed insights.

## Project Structure
```text
ronin-subscriptions-sf/
├── assets/               # Directory for frontend assets (CSS, JavaScript, images)
├── bin/                  # Symfony console commands
├── config/               # Application configuration (routes, services, packages)
├── migrations/           # Doctrine database migrations
├── public/               # Public web directory (document root)
│   └── index.php         # Front controller
├── src/                  # PHP source code (Controllers, Entities, Services)
│   ├── Command/          # Console commands
│   ├── Controller/       # Controllers for handling HTTP requests
│   ├── Entity/           # Doctrine entities (database models)
│   ├── Enum/             # Enum classes for fixed values used across the application
│   ├── Event/            # Event classes to handle domain events
│   ├── EventListener/    # Listeners for handling events (e.g., after saving entities)
│   ├── Form/             # Form classes for handling user inputs
│   ├── Repository/       # Repository classes for data queries
│   ├── Security/         # Security-related classes, such as authentication and authorization
│   └── Service/          # Business logic and services
├── templates/            # Twig templates for rendering views
├── tests/                # Unit and functional tests
├── translations/         # Translation files for internationalization
├── fixtures/             # Optional: Database fixtures for testing
├── var/                  # Cache, logs, sessions
├── vendor/               # Composer dependencies
├── frontend/             # React and TypeScript frontend assets (npm package)
├── composer.json         # PHP dependencies and project configuration
├── package.json          # Node.js dependencies and frontend configuration
├── tsconfig.json         # TypeScript configuration for frontend development
└── webpack.config.js     # Webpack configuration for bundling frontend assets (React, TypeScript)
└── README.md             # Project documentation
...
```

## Contributing
Contributions are welcome! To contribute to this project:

1. **Fork the repository**:
- Click the **Fork** button in the top-right corner of the repository page on GitHub to create a copy of the repository under your own account.
2. **Create a new branch** for your feature or fix:
```bash
git checkout -b feature/new-feature
```
Replace `new-feature` with a descriptive name for the branch you're working on.
3. **Commit your changes** with a descriptive message:
```bash
git commit -m "Add new feature"
```
Write a clear and concise message describing what you have done in the commit.
4. **Push your branch** to your fork:
```bash
git push origin feature/new-feature
```
This uploads your branch to your GitHub fork.
5. **Open a Pull Request**:
- Go to the original repository where you want to contribute.
- Click on the **Pull Requests** tab.
- Click the **New Pull Request** button.
- Describe your changes and submit the pull request.

## License
This project is currently unlicensed. You may view or fork it for demo purposes.
A proper license (e.g., MIT, GPL, Apache 2.0) may be added in the future.