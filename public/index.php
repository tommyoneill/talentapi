<?php

use DI\Container;
use Slim\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;
use PDOException;

require __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/../config.php';

// Create Container
$container = new Container();

// Set container to create App with on AppFactory
AppFactory::setContainer($container);

// Create App
$app = AppFactory::create();

// Add Authentication Middleware
$app->add(function (Request $request, $handler) {
    $authHeader = $request->getHeaderLine('Authorization');
    
    if (empty($authHeader)) {
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write(json_encode(['error' => 'Authorization header is required']));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }
    
    if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write(json_encode(['error' => 'Invalid authorization header format']));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }
    
    $token = $matches[1];

    // if the tokent doesn't match eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9 send the appropriate error
    if ($token !== 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9') {
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write(json_encode(['error' => 'Invalid token']));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }

    // Here you would typically validate the JWT token
    // For now, we'll just check if it's not empty
    if (empty($token)) {
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write(json_encode(['error' => 'Invalid token']));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }
    
    return $handler->handle($request);
});

// Add Error Middleware
$app->addErrorMiddleware(true, true, true);

// Add routes
$app->get('/front-office/v1/talent/{talentId}', function (Request $request, Response $response, array $args) use ($config) {
    $talentId = $args['talentId'];
    $includeResume = strtolower($request->getQueryParams()['includeResume'] ?? 'false');
    
    try {
        // Create database connection
        $db = new PDO(
            "mysql:host={$config['db']['host']};dbname={$config['db']['database']};charset={$config['db']['charset']}",
            $config['db']['username'],
            $config['db']['password']
        );
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Fetch talent data
        $stmt = $db->prepare("SELECT * FROM talents WHERE id = :id");
        $stmt->execute([':id' => $talentId]);
        $talent = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$talent) {
            $response->getBody()->write(json_encode(['error' => 'Talent not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        // Fetch addresses
        $stmt = $db->prepare("SELECT * FROM addresses WHERE talent_id = :talent_id");
        $stmt->execute([':talent_id' => $talentId]);
        $addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Organize addresses by type
        $residentAddress = null;
        $mailingAddress = null;
        $payrollAddress = null;
        foreach ($addresses as $address) {
            switch ($address['type']) {
                case 'resident':
                    $residentAddress = $address;
                    break;
                case 'mailing':
                    $mailingAddress = $address;
                    break;
                case 'payroll':
                    $payrollAddress = $address;
                    break;
            }
        }
        
        // Fetch skills
        $stmt = $db->prepare("SELECT * FROM skills WHERE talent_id = :talent_id");
        $stmt->execute([':talent_id' => $talentId]);
        $skills = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Fetch work history
        $stmt = $db->prepare("SELECT * FROM work_history WHERE talent_id = :talent_id ORDER BY from_date DESC");
        $stmt->execute([':talent_id' => $talentId]);
        $workHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Fetch resume if requested
        $talentResume = null;
        if ($includeResume === 'true') {
            $stmt = $db->prepare("SELECT * FROM talent_resumes WHERE talent_id = :talent_id ORDER BY created_date DESC LIMIT 1");
            $stmt->execute([':talent_id' => $talentId]);
            $talentResume = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // Format the response according to the API specification
        $responseData = [
            'id' => (int)$talent['id'],
            'firstName' => $talent['first_name'],
            'middleName' => $talent['middle_name'],
            'lastName' => $talent['last_name'],
            'homePhone' => $talent['home_phone'],
            'workPhone' => $talent['work_phone'],
            'mobilePhone' => $talent['mobile_phone'],
            'pageNumber' => $talent['page_number'],
            'emailAddress' => $talent['email_address'],
            'emailAddress2' => $talent['email_address2'],
            'taxIdNumber' => $talent['tax_id_number'],
            'birthday' => $talent['birthday'],
            'gender' => $talent['gender'],
            'hireDate' => $talent['hire_date'],
            'residentAddress' => $residentAddress ? [
                'street1' => $residentAddress['street1'],
                'street2' => $residentAddress['street2'],
                'city' => $residentAddress['city'],
                'state_Province' => $residentAddress['state_province'],
                'postalCode' => $residentAddress['postal_code'],
                'country' => $residentAddress['country'],
                'county' => $residentAddress['county'],
                'geoCode' => $residentAddress['geo_code'],
                'schoolDistrictCode' => $residentAddress['school_district_code']
            ] : null,
            'mailingAddress' => $mailingAddress ? [
                'street1' => $mailingAddress['street1'],
                'street2' => $mailingAddress['street2'],
                'city' => $mailingAddress['city'],
                'state_Province' => $mailingAddress['state_province'],
                'postalCode' => $mailingAddress['postal_code'],
                'country' => $mailingAddress['country'],
                'county' => $mailingAddress['county'],
                'geoCode' => $mailingAddress['geo_code'],
                'schoolDistrictCode' => $mailingAddress['school_district_code']
            ] : null,
            'payrollAddress' => $payrollAddress ? [
                'street1' => $payrollAddress['street1'],
                'street2' => $payrollAddress['street2'],
                'city' => $payrollAddress['city'],
                'state_Province' => $payrollAddress['state_province'],
                'postalCode' => $payrollAddress['postal_code'],
                'country' => $payrollAddress['country'],
                'county' => $payrollAddress['county'],
                'geoCode' => $payrollAddress['geo_code'],
                'schoolDistrictCode' => $payrollAddress['school_district_code']
            ] : null,
            'addresses' => array_map(function($address) {
                return [
                    'street1' => $address['street1'],
                    'street2' => $address['street2'],
                    'city' => $address['city'],
                    'state_Province' => $address['state_province'],
                    'postalCode' => $address['postal_code'],
                    'country' => $address['country'],
                    'county' => $address['county'],
                    'geoCode' => $address['geo_code'],
                    'schoolDistrictCode' => $address['school_district_code']
                ];
            }, $addresses),
            'status' => $talent['status'],
            'filingStatus' => $talent['filing_status'],
            'federalAllowances' => (int)$talent['federal_allowances'],
            'stateAllowances' => (int)$talent['state_allowances'],
            'additionalFederalWithholding' => (float)$talent['additional_federal_withholding'],
            'i9ValidatedDate' => $talent['i9_validated_date'],
            'frontOfficeId' => (int)$talent['front_office_id'],
            'latestActivityDate' => $talent['latest_activity_date'],
            'latestActivityName' => $talent['latest_activity_name'],
            'link' => $talent['link'],
            'race' => $talent['race'],
            'disability' => $talent['disability'],
            'veteranStatus' => $talent['veteran_status'],
            'emailOptOut' => (bool)$talent['email_opt_out'],
            'isArchived' => (bool)$talent['is_archived'],
            'placementStatus' => $talent['placement_status'],
            'representativeUser' => (int)$talent['representative_user'],
            'w2Consent' => (bool)$talent['w2_consent'],
            'electronic1095CConsent' => (bool)$talent['electronic_1095c_consent'],
            'referredBy' => $talent['referred_by'],
            'availabilityDate' => $talent['availability_date'],
            'statusId' => (int)$talent['status_id'],
            'officeName' => $talent['office_name'],
            'officeDivision' => $talent['office_division'],
            'enteredByUserId' => (int)$talent['entered_by_user_id'],
            'enteredByUser' => $talent['entered_by_user'],
            'representativeUserEmail' => $talent['representative_user_email'],
            'createdDate' => $talent['created_date'],
            'lastUpdatedDate' => $talent['last_updated_date'],
            'latestWork' => $talent['latest_work'],
            'lastContacted' => $talent['last_contacted'],
            'flag' => $talent['flag'],
            'origin' => $talent['origin'],
            'originRecordId' => $talent['origin_record_id'],
            'electronic1099Consent' => (bool)$talent['electronic_1099_consent'],
            'textConsent' => $talent['text_consent'],
            'rehireDate' => $talent['rehire_date'],
            'terminationDate' => $talent['termination_date'],
            'employmentTypeId' => (int)$talent['employment_type_id'],
            'employmentType' => $talent['employment_type'],
            'employmentTypeName' => $talent['employment_type_name']
        ];
        
        // Add resume if requested and available
        if ($includeResume === 'true' && $talentResume) {
            $responseData['talentResume'] = [
                'resumeId' => (int)$talentResume['resume_id'],
                'resumeFilename' => $talentResume['resume_filename'],
                'resumeText' => $talentResume['resume_text'],
                'resumeContents' => base64_encode($talentResume['resume_contents']),
                'createdDate' => $talentResume['created_date'],
                'lastUpdatedDate' => $talentResume['last_updated_date']
            ];
        }
        
        $response->getBody()->write(json_encode($responseData));
        return $response->withHeader('Content-Type', 'application/json');
            
    } catch (PDOException $e) {
        $response->getBody()->write(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

// Add the new endpoint for getting talent IDs
$app->get('/front-office/v1/talents/ids/{page}/{pageSize}', function (Request $request, Response $response, array $args) use ($config) {
    // Validate required headers
    $tenant = $request->getHeaderLine('Tenant');
    $frontOfficeTenantId = $request->getHeaderLine('FrontOfficeTenantId');
    
    if (empty($tenant) && empty($frontOfficeTenantId)) {
        $response->getBody()->write(json_encode(['error' => 'Either Tenant or FrontOfficeTenantId header is required']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }
    
    // Validate page and pageSize parameters
    $page = $args['page'];
    $pageSize = $args['pageSize'];
    
    if (!is_numeric($page) || !is_numeric($pageSize) || $page < 1 || $pageSize < 1) {
        $response->getBody()->write(json_encode(['error' => 'Invalid page or pageSize parameters']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }
    
    try {
        // Create database connection
        $db = new PDO(
            "mysql:host={$config['db']['host']};dbname={$config['db']['database']};charset={$config['db']['charset']}",
            $config['db']['username'],
            $config['db']['password']
        );
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Calculate offset
        $offset = ($page - 1) * $pageSize;
        
        // Fetch talent IDs with pagination
        $stmt = $db->prepare("SELECT id FROM talents ORDER BY id LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', (int)$pageSize, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $talentIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Convert IDs to integers
        $talentIds = array_map('intval', $talentIds);
        
        $response->getBody()->write(json_encode($talentIds));
        return $response->withHeader('Content-Type', 'application/json');
        
    } catch (PDOException $e) {
        $response->getBody()->write(json_encode(['error' => 'Database error occurred']));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

// Run app
$app->run(); 