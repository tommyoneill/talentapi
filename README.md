# Talent API Mock

This is a mock API implementation of the Avionté Front Office Talent API endpoint. It's designed to help with testing and development when you don't have access to the actual API.

## Setup

1. Install dependencies:
```bash
composer install
```

2. Create the database:
```bash
mysql -u root -p < database.sql
```

3. Configure the application:
   - Copy `config.php.example` to `config.php`
   - Copy `.env.example` to `.env`
   - Update the database credentials and other settings in both files:
     - In `config.php`: Set your database connection details and API settings
     - In `.env`: Set your environment-specific variables
   - Make sure to set appropriate values for security settings (JWT secret, etc.)

4. Start the development server:
```bash
php -S localhost:8080 -t public
```

## Configuration

The application uses two configuration files:

### config.php
The main configuration file that includes:
- Database connection settings
- API configuration
- Security settings (JWT)
- Logging configuration

### .env
Environment-specific configuration that includes:
- Database credentials
- API settings
- Security keys
- Logging preferences

Make sure to:
1. Never commit the actual `config.php` or `.env` files to version control
2. Keep sensitive information like passwords and secrets secure
3. Use appropriate values for your environment
4. Keep the example files (`config.php.example` and `.env.example`) up to date with any new configuration options

## API Endpoints

### Get Talent IDs
```
GET /front-office/v1/talents/ids/{page}/{pageSize}
```

Returns a paged list of talent IDs registered in the system.

Path Parameters:
- `page` (required): Page number for pagination
- `pageSize` (required): Number of items per page

Headers:
- `FrontOfficeTenantId` (optional): Front office ID
- `RequestId` (optional): Client-specified request correlation (GUID)
- `Tenant` (optional): Tenant short code
- `Authorization` (required): Bearer token

### Get Talent Details
```
GET /front-office/v1/talent/{talentId}
```

Returns detailed information about a specific talent record.

Path Parameters:
- `talentId` (required): ID of the talent to retrieve

Query Parameters:
- `includeResume` (optional): Set to 'True' to include resume data

Headers:
- `FrontOfficeTenantId` (required): Front office ID
- `RequestId` (optional): Client-specified request correlation (GUID)
- `Tenant` (required): Tenant short code
- `Authorization` (required): Bearer token

## Response Format

The API returns JSON objects containing the following information:

### Talent Details Response
- Basic information (name, contact details)
- Addresses (resident, mailing, payroll)
- Skills and qualifications
- Work history
- Employment details
- Status information
- Tax and compliance information
- Resume data (when requested)

### Talent IDs Response
- Array of talent IDs for the requested page

## Database Schema

The API uses the following database tables:
- `talents`: Core talent information
- `addresses`: Address records (resident, mailing, payroll)
- `skills`: Talent skills and qualifications
- `work_history`: Employment history
- `talent_resumes`: Resume documents and metadata

## Development

To add more mock data or modify the response format:
1. Edit the route handlers in `public/index.php`
2. Add or modify data in the database tables
3. Update the OpenAPI specification in `public/talentapi.yaml` if adding new endpoints