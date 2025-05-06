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

3. Configure the database connection:
Edit `config.php` with your database credentials.

4. Start the development server:
```bash
php -S localhost:8080 -t public
```

## API Endpoint

### Get Talent
```
GET /front-office/v1/talent/{talentId}
```

Query Parameters:
- `includeResume` (optional): Set to 'True' to include resume data

Headers:
- `FrontOfficeTenantId` (required): Front office ID
- `RequestId` (optional): Client-specified request correlation (GUID)
- `Tenant` (required): Tenant short code
- `Authorization` (required): Bearer token

## Response Format

The API returns a JSON object containing talent information including:
- Basic information (name, contact details)
- Addresses (resident, mailing, payroll)
- Employment details
- Status information
- And more as specified in the documentation

## Development

To add more mock data or modify the response format, edit the route handler in `public/index.php`. 