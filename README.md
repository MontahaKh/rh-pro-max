# RH Pro Max
## Authors
- Houssem Amor (initial project)
- Montaha Khedhiri (development & improvements)

A comprehensive Human Resources Management System built with Symfony 7.

## Features

### For HR Managers & Recruiters
- **Job Offer Management**: Create, edit, and manage job postings
- **Candidate Tracking**: Review applications, update candidate status, and manage the hiring pipeline
- **Employee Management**: Add and manage employee profiles
- **Skills Management**: Define skill categories and track employee/candidate skills

### For Candidates
- **Job Search**: Browse available job openings
- **Easy Application**: Apply to jobs with one click
- **Application Dashboard**: Track all your applications and their status
- **CV Management**: Upload and manage CV documents for each application

### For Employees
- **Profile Management**: View and update personal information
- **Internal Applications**: Apply for internal job postings

## Tech Stack

- **Framework**: Symfony 7
- **Database**: MySQL/MariaDB (via Doctrine ORM)
- **Frontend**: Twig templates, Bootstrap 5, Font Awesome
- **Authentication**: Symfony Security with separate providers for employees and candidates

## Requirements

- PHP 8.2 or higher
- Composer
- MySQL/MariaDB
- Node.js (for asset management)

## Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/MontahaKh/RH_Pro_Max.git
   cd RH_Pro_Max
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Configure the database**
   
   Copy `.env` to `.env.local` and update the database connection:
   ```
   DATABASE_URL="mysql://user:password@127.0.0.1:3306/rh_pro_max?serverVersion=8.0"
   ```

4. **Create the database and run migrations**
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

5. **Start the development server**
   ```bash
   symfony server:start
   ```
   
   Or with PHP's built-in server:
   ```bash
   php -S localhost:8000 -t public
   ```

6. **Access the application**
   
   Open your browser and navigate to `http://localhost:8000`

## User Roles

| Role | Description |
|------|-------------|
| `ROLE_ADMIN` | Full system access, can delete job offers |
| `ROLE_HR_MANAGER` | Manage employees, job offers, and candidates |
| `ROLE_RECRUITER` | View and manage candidates |
| `ROLE_TEAM_MANAGER` | Team management capabilities |
| `ROLE_EMPLOYEE` | Basic employee access, can view colleagues |
| `ROLE_CANDIDATE` | External candidates, can apply to jobs |

## Project Structure

```
RH_Pro_Max/
├── assets/              # Frontend assets (JS, CSS)
├── config/              # Symfony configuration
├── migrations/          # Database migrations
├── public/              # Web root
│   ├── uploads/cvs/     # Uploaded CV files
│   ├── css/             # Stylesheets
│   └── js/              # JavaScript files
├── src/
│   ├── Controller/      # Application controllers
│   ├── Entity/          # Doctrine entities
│   ├── Enum/            # PHP enums (status types, roles)
│   ├── Form/            # Symfony form types
│   └── Repository/      # Doctrine repositories
├── templates/           # Twig templates
└── tests/               # PHPUnit tests
```

## Key Entities

- **User**: Internal employees with various roles
- **Candidate**: External job applicants
- **CandidateProfile**: Application record linking a candidate to a job offer
- **JobOffer**: Job postings with status and closing dates
- **CV**: Uploaded CV documents linked to applications
- **Skill/SkillCategory**: Skills tracking for candidates and employees

## Candidate Application Flow

1. Candidate registers an account
2. Browses available job offers
3. Applies to a job (creates a CandidateProfile)
4. Uploads CV through their dashboard
5. Tracks application status (NEW → SCREENING → INTERVIEW → OFFER → HIRED)

## Running Tests

```bash
php bin/phpunit
```

## License

This project is proprietary software.

## Contributing

This is a private project. Please contact the repository owner for contribution guidelines.
