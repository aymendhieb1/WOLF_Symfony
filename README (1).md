# TripToGo

## Description
TripToGo is a modern travel planning and management application developed as part of the coursework at Esprit School of Engineering. This project aims to simplify the travel planning process by providing an intuitive platform for users to organize their trips, manage itineraries, and discover new destinations.

### Key Features
- **Trip Planning**: Create, edit, and manage travel itineraries
- **User Management**: Secure authentication and personalized user profiles
- **Interactive Maps**: Visual trip planning with integrated maps
- **Real-time Updates**: Live notifications and trip status updates
- **Multi-language Support**: Available in multiple languages for global users
- **Responsive Design**: Seamless experience across all devices

## Table of Contents
- [Installation](#installation)
- [Usage](#usage)
- [Features](#features)
- [Tech Stack](#tech-stack)
- [Project Structure](#project-structure)
- [Development Workflow](#development-workflow)
- [Contributing](#contributing)
- [License](#license)

## Installation

### Prerequisites
- PHP 8.1 or higher
- Composer
- MySQL 8.0 or PostgreSQL 13
- Symfony CLI
- Git

### Step-by-Step Installation
1. Clone the repository:
   ```bash
   git clone https://github.com/yourusername/TripToGo.git
   cd TripToGo
   ```

2. Install PHP dependencies:
   ```bash
   composer install
   ```

3. Configure your environment:
   ```bash
   cp .env .env.local
   ```
   Then edit `.env.local` with your database credentials:
   ```yaml
   DATABASE_URL="mysql://user:password@127.0.0.1:3306/triptogo?serverVersion=8.0"
   APP_ENV=dev
   APP_SECRET=your_secret_here
   ```

4. Create the database:
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

5. Load fixtures (optional):
   ```bash
   php bin/console doctrine:fixtures:load
   ```

6. Start the Symfony server:
   ```bash
   symfony server:start
   ```

## Usage

### Running the Application
1. Start the Symfony development server:
   ```bash
   symfony server:start
   ```

2. Access the application at `http://localhost:8000`

### Development Commands
- Clear cache: `php bin/console cache:clear`
- Create migration: `php bin/console make:migration`
- Run migrations: `php bin/console doctrine:migrations:migrate`
- Run tests: `php bin/phpunit`
- Create entity: `php bin/console make:entity`
- Create controller: `php bin/console make:controller`
- Create form: `php bin/console make:form`

### Common Tasks
- **Database Updates**: After modifying entities, run:
  ```bash
  php bin/console make:migration
  php bin/console doctrine:migrations:migrate
  ```
- **Clear Cache**: If you encounter caching issues:
  ```bash
  php bin/console cache:clear
  ```
- **Run Tests**: To execute the test suite:
  ```bash
  php bin/phpunit
  ```

## Features

### User Management
- Secure authentication system
- User registration and profile management
- Role-based access control
- Password reset functionality
- Email verification

### Trip Planning
- Create and manage trip itineraries
- Add destinations and activities
- Set trip dates and budgets
- Share trips with other users
- Export trip details

### Interactive Maps
- Integration with mapping services
- Visual trip planning
- Location search and suggestions
- Route optimization
- Points of interest display

### Real-time Features
- Live trip updates
- Instant notifications
- Chat functionality
- Collaborative trip editing
- Status tracking

## Tech Stack

### Backend
- **PHP 8.1+**: Modern PHP features and performance
- **Symfony 6.4**: Robust framework for web applications
- **Doctrine ORM**: Advanced database abstraction
- **MySQL/PostgreSQL**: Reliable database systems
- **Twig**: Flexible templating engine
- **Symfony Security**: Comprehensive security system
- **Symfony Forms**: Form handling and validation
- **Symfony Mailer**: Email functionality

### Frontend
- **Bootstrap 5**: Responsive design framework
- **JavaScript**: Core frontend functionality
- **jQuery**: DOM manipulation and AJAX
- **CSS/SCSS**: Styling and theming
- **Font Awesome**: Icon library
- **Google Maps API**: Map integration

### Development Tools
- **Composer**: PHP dependency management
- **Git**: Version control
- **PHPUnit**: Unit testing
- **Symfony CLI**: Development server and tools
- **Docker**: Containerization (optional)
- **Xdebug**: Debugging
- **PHP CS Fixer**: Code style enforcement

## Project Structure
```
TripToGo/
├── bin/                # Executable files
├── config/            # Configuration files
├── migrations/        # Database migrations
├── public/            # Public directory
├── src/               # Source code
│   ├── Controller/    # Controllers
│   ├── Entity/        # Database entities
│   ├── Form/          # Form types
│   ├── Repository/    # Entity repositories
│   └── Service/       # Business logic
├── templates/         # Twig templates
├── tests/             # Test files
├── translations/      # Translation files
└── var/              # Cache and logs
```

## Development Workflow

### Coding Standards
- Follow PSR-12 coding standards
- Use type hints and return types
- Write PHPDoc blocks for classes and methods
- Keep methods small and focused
- Use meaningful variable and method names

### Git Workflow
1. Create a new branch for each feature
2. Write meaningful commit messages
3. Keep commits atomic and focused
4. Rebase before submitting PR
5. Update documentation as needed

### Testing
- Write unit tests for new features
- Maintain test coverage
- Run tests before committing
- Use PHPUnit for testing
- Follow TDD practices when possible

## Contributing
We welcome contributions to TripToGo! Please follow these steps:

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

Please make sure to:
- Follow PSR-12 coding standards
- Write unit tests for new features
- Update documentation as needed
- Run `composer test` before submitting PR
- Add comments for complex logic
- Update CHANGELOG.md for significant changes

## License
This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Acknowledgments
This project was developed as part of the coursework at Esprit School of Engineering. Special thanks to all contributors and mentors who provided guidance throughout the development process.

### Contact
For questions or support, please contact:
- Project Maintainer: [Your Name](mailto:your.email@example.com)
- Course Instructor: [Instructor Name](mailto:instructor@esprit.tn) 