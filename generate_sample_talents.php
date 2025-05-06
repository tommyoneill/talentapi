<?php

require_once 'vendor/autoload.php';
require_once 'config.php';

use OpenAI;
use Dotenv\Dotenv;

// Parse command line arguments
$options = getopt('', ['count::', 'profile::', 'profiles_file::', 'verbose::', 'quiet::']);
$count = isset($options['count']) ? (int)$options['count'] : 1;
$profile = isset($options['profile']) ? $options['profile'] : '';
$profilesFile = isset($options['profiles_file']) ? $options['profiles_file'] : '';
$verbose = isset($options['verbose']) || isset($options['v']);
$quiet = isset($options['quiet']) || isset($options['q']);

if ($count < 1) {
    die("Error: Count must be a positive number\n");
}

// Load profiles from file if specified
$profiles = [];
if ($profilesFile) {
    if (!file_exists($profilesFile)) {
        die("Error: Profiles file '$profilesFile' not found\n");
    }
    
    $profilesContent = file_get_contents($profilesFile);
    if ($profilesContent === false) {
        die("Error: Could not read profiles file '$profilesFile'\n");
    }
    
    $profiles = json_decode($profilesContent, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        die("Error: Invalid JSON in profiles file: " . json_last_error_msg() . "\n");
    }
    
    if (!is_array($profiles) || empty($profiles)) {
        die("Error: Profiles file must contain a non-empty array of profile descriptions\n");
    }
    
    if (!$quiet) {
        echo "Loaded " . count($profiles) . " profiles from file\n";
    }
}

if (!$quiet) {
    echo "Will generate $count talent(s)" . ($profile ? " with profile: $profile" : ($profilesFile ? " using random profiles from file" : "")) . "...\n";
}

// Load environment variables from .env file
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Debug: Check if API key is loaded
$apiKey = $_ENV['OPENAI_API_KEY'] ?? null;
if (empty($apiKey)) {
    die("Error: OPENAI_API_KEY not found in environment variables. Please check your .env file.\n");
}
if (!$quiet) {
    echo "API Key loaded successfully (first 4 chars: " . substr($apiKey, 0, 4) . "...)\n";
}

// Database connection
$config = require 'config.php';
$db = new PDO(
    "mysql:host={$config['db']['host']};dbname={$config['db']['database']};charset={$config['db']['charset']}",
    $config['db']['username'],
    $config['db']['password']
);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// OpenAI API setup
$openai = OpenAI::client($apiKey);

// Function to generate a single talent using OpenAI
function generateTalent($openai, $profile = '', $profiles = [], $verbose = false) {
    // If no specific profile is provided but we have profiles array, randomly select one
    if (empty($profile) && !empty($profiles)) {
        $profile = $profiles[array_rand($profiles)];
        if ($verbose) {
            echo "Using profile: $profile\n";
        }
    }
    
    $basePrompt = "Generate a realistic talent profile with the following fields in JSON format. Return ONLY the JSON object, no other text:
    {
        \"first_name\": \"string\",
        \"middle_name\": \"string or null\",
        \"last_name\": \"string\",
        \"home_phone\": \"XXX-XXX-XXXX\",
        \"work_phone\": \"XXX-XXX-XXXX\",
        \"mobile_phone\": \"XXX-XXX-XXXX\",
        \"email_address\": \"email@example.com\",
        \"tax_id_number\": \"XXX-XX-XXXX\",
        \"birthday\": \"YYYY-MM-DD\",
        \"gender\": \"male/female/other\",
        \"hire_date\": \"YYYY-MM-DD\",
        \"status\": \"active/inactive\",
        \"filing_status\": \"single/married/head of household\",
        \"federal_allowances\": number,
        \"state_allowances\": number,
        \"race\": \"white/black/asian/hispanic/other\",
        \"disability\": \"yes/no\",
        \"veteran_status\": \"yes/no\",
        \"placement_status\": \"available/placed/not_available\",
        \"office_name\": \"string\",
        \"employment_type\": \"full-time/part-time/contract\",
        \"addresses\": [
            {
                \"type\": \"resident\",
                \"street1\": \"string\",
                \"street2\": \"string or null\",
                \"city\": \"string\",
                \"state_province\": \"string\",
                \"postal_code\": \"string\",
                \"country\": \"USA\",
                \"county\": \"string\"
            },
            {
                \"type\": \"mailing\",
                \"street1\": \"string\",
                \"street2\": \"string or null\",
                \"city\": \"string\",
                \"state_province\": \"string\",
                \"postal_code\": \"string\",
                \"country\": \"USA\",
                \"county\": \"string\"
            }
        ],
        \"skills\": [
            {
                \"position_id\": number,
                \"description_id\": number,
                \"skill_position\": \"string\",
                \"skill_description\": \"string\"
            }
        ],
        \"work_history\": [
            {
                \"company\": \"string\",
                \"title\": \"string\",
                \"from_date\": \"YYYY-MM-DD\",
                \"to_date\": \"YYYY-MM-DD or null for current position\",
                \"city\": \"string\",
                \"state\": \"string\",
                \"country\": \"USA\",
                \"duties\": \"string\",
                \"reason_for_leaving\": \"string\",
                \"notes\": \"string or null\"
            }
        ]
    }";

    $profilePrompt = $profile ? "Generate a talent profile that matches this description: \"$profile\". " : "";
    $prompt = $profilePrompt . $basePrompt . " Make it realistic and diverse. For addresses, use real US cities and states. For skills, generate 3-5 realistic professional skills that match the talent's profile. Each skill should have a position (like 'Software Developer', 'Project Manager', etc.) and a detailed description of their expertise in that area. For work history, generate 2-4 previous positions that show career progression and are relevant to the talent's profile. Include detailed duties and realistic reasons for leaving each position.";

    try {
        $response = $openai->chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a helpful assistant that generates realistic talent profiles. Return only valid JSON, no other text.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.7
        ]);

        $content = trim($response->choices[0]->message->content);
        $talent = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON response: " . json_last_error_msg() . "\nResponse: " . $content);
        }

        // Validate required fields
        $requiredFields = ['first_name', 'last_name', 'email_address', 'gender', 'status', 'addresses', 'skills', 'work_history'];
        foreach ($requiredFields as $field) {
            if (empty($talent[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        // Generate detailed resume based on the talent profile
        $resumePrompt = "Generate a detailed professional resume (5000-6000 characters) for the following talent profile. The resume should be formatted as plain text and include all the information provided. Make sure to:
        1. Use the exact contact information provided
        2. Include a detailed professional summary
        3. List all skills with detailed descriptions
        4. Include comprehensive work history with detailed achievements and responsibilities
        5. Add relevant education and certifications
        6. Include any relevant professional affiliations or memberships
        7. Add a section for notable achievements or awards
        8. Include any relevant volunteer work or community involvement
        
        Here is the talent profile to use:
        " . json_encode($talent, JSON_PRETTY_PRINT);

        $resumeResponse = $openai->chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a professional resume writer. Generate a detailed, well-formatted resume based on the provided talent profile.'],
                ['role' => 'user', 'content' => $resumePrompt]
            ],
            'temperature' => 0.7
        ]);

        $resumeText = trim($resumeResponse->choices[0]->message->content);
        
        // Add the resume to the talent data
        $talent['resume'] = [
            'resume_filename' => strtolower($talent['first_name'] . '_' . $talent['last_name'] . '_resume.txt'),
            'resume_text' => $resumeText,
            'resume_contents' => base64_encode($resumeText)
        ];

        if ($verbose) {
            echo "Generated talent data: " . print_r($talent, true) . "\n";
        }
        return $talent;
    } catch (Exception $e) {
        echo "Error generating talent: " . $e->getMessage() . "\n";
        throw $e;
    }
}

// Function to insert talent into database
function insertTalent($db, $talent) {
    $db->beginTransaction();
    
    try {
        // Insert talent
        $sql = "INSERT INTO talents (
            first_name, middle_name, last_name, home_phone, work_phone, mobile_phone,
            email_address, tax_id_number, birthday, gender, hire_date, status,
            filing_status, federal_allowances, state_allowances, race, disability,
            veteran_status, placement_status, office_name, employment_type,
            created_date, last_updated_date
        ) VALUES (
            :first_name, :middle_name, :last_name, :home_phone, :work_phone, :mobile_phone,
            :email_address, :tax_id_number, :birthday, :gender, :hire_date, :status,
            :filing_status, :federal_allowances, :state_allowances, :race, :disability,
            :veteran_status, :placement_status, :office_name, :employment_type,
            NOW(), NOW()
        )";

        $stmt = $db->prepare($sql);
        
        // Bind each parameter explicitly
        $params = [
            ':first_name' => $talent['first_name'] ?? null,
            ':middle_name' => $talent['middle_name'] ?? null,
            ':last_name' => $talent['last_name'] ?? null,
            ':home_phone' => $talent['home_phone'] ?? null,
            ':work_phone' => $talent['work_phone'] ?? null,
            ':mobile_phone' => $talent['mobile_phone'] ?? null,
            ':email_address' => $talent['email_address'] ?? null,
            ':tax_id_number' => $talent['tax_id_number'] ?? null,
            ':birthday' => $talent['birthday'] ?? null,
            ':gender' => $talent['gender'] ?? null,
            ':hire_date' => $talent['hire_date'] ?? null,
            ':status' => $talent['status'] ?? null,
            ':filing_status' => $talent['filing_status'] ?? null,
            ':federal_allowances' => $talent['federal_allowances'] ?? null,
            ':state_allowances' => $talent['state_allowances'] ?? null,
            ':race' => $talent['race'] ?? null,
            ':disability' => $talent['disability'] ?? null,
            ':veteran_status' => $talent['veteran_status'] ?? null,
            ':placement_status' => $talent['placement_status'] ?? null,
            ':office_name' => $talent['office_name'] ?? null,
            ':employment_type' => $talent['employment_type'] ?? null
        ];

        $stmt->execute($params);
        $talentId = $db->lastInsertId();

        // Insert addresses
        if (!empty($talent['addresses'])) {
            $addressSql = "INSERT INTO addresses (
                talent_id, type, street1, street2, city, state_province,
                postal_code, country, county
            ) VALUES (
                :talent_id, :type, :street1, :street2, :city, :state_province,
                :postal_code, :country, :county
            )";

            $addressStmt = $db->prepare($addressSql);

            foreach ($talent['addresses'] as $address) {
                $addressParams = [
                    ':talent_id' => $talentId,
                    ':type' => $address['type'],
                    ':street1' => $address['street1'],
                    ':street2' => $address['street2'] ?? null,
                    ':city' => $address['city'],
                    ':state_province' => $address['state_province'],
                    ':postal_code' => $address['postal_code'],
                    ':country' => $address['country'],
                    ':county' => $address['county'] ?? null
                ];
                $addressStmt->execute($addressParams);
            }
        }

        // Insert skills
        if (!empty($talent['skills'])) {
            $skillSql = "INSERT INTO skills (
                talent_id, position_id, description_id,
                skill_position, skill_description
            ) VALUES (
                :talent_id, :position_id, :description_id,
                :skill_position, :skill_description
            )";

            $skillStmt = $db->prepare($skillSql);

            foreach ($talent['skills'] as $skill) {
                $skillParams = [
                    ':talent_id' => $talentId,
                    ':position_id' => $skill['position_id'] ?? null,
                    ':description_id' => $skill['description_id'] ?? null,
                    ':skill_position' => $skill['skill_position'],
                    ':skill_description' => $skill['skill_description']
                ];
                $skillStmt->execute($skillParams);
            }
        }

        // Insert work history
        if (!empty($talent['work_history'])) {
            $workHistorySql = "INSERT INTO work_history (
                talent_id, company, title, from_date, to_date,
                city, state, country, duties, reason_for_leaving, notes
            ) VALUES (
                :talent_id, :company, :title, :from_date, :to_date,
                :city, :state, :country, :duties, :reason_for_leaving, :notes
            )";

            $workHistoryStmt = $db->prepare($workHistorySql);

            foreach ($talent['work_history'] as $work) {
                $workParams = [
                    ':talent_id' => $talentId,
                    ':company' => $work['company'],
                    ':title' => $work['title'],
                    ':from_date' => $work['from_date'],
                    ':to_date' => $work['to_date'] ?? null,
                    ':city' => $work['city'],
                    ':state' => $work['state'],
                    ':country' => $work['country'] ?? 'USA',
                    ':duties' => $work['duties'],
                    ':reason_for_leaving' => $work['reason_for_leaving'],
                    ':notes' => $work['notes'] ?? null
                ];
                $workHistoryStmt->execute($workParams);
            }
        }

        // Insert resume
        if (!empty($talent['resume'])) {
            $resumeSql = "INSERT INTO talent_resumes (
                talent_id, resume_filename, resume_text, resume_contents
            ) VALUES (
                :talent_id, :resume_filename, :resume_text, :resume_contents
            )";

            $resumeStmt = $db->prepare($resumeSql);
            $resumeParams = [
                ':talent_id' => $talentId,
                ':resume_filename' => $talent['resume']['resume_filename'],
                ':resume_text' => $talent['resume']['resume_text'],
                ':resume_contents' => $talent['resume']['resume_contents'] ?? null
            ];
            $resumeStmt->execute($resumeParams);
        }

        $db->commit();
        return $talentId;
    } catch (Exception $e) {
        $db->rollBack();
        echo "Database Error: " . $e->getMessage() . "\n";
        throw $e;
    }
}

// Generate and insert talents
if (!$quiet) {
    echo "Starting to generate $count sample talent(s)...\n";
}
$generated = 0;

try {
    for ($i = 0; $i < $count; $i++) {
        $talent = generateTalent($openai, $profile, $profiles, $verbose);
        $talentId = insertTalent($db, $talent);
        $generated++;
        
        // Show concise output by default
        $location = $talent['addresses'][0]['city'] . ', ' . $talent['addresses'][0]['state_province'];
        echo sprintf("Generated talent #%d (ID: %d) - %s %s - %s\n", 
            $generated, 
            $talentId, 
            $talent['first_name'], 
            $talent['last_name'],
            $location
        );
        
        // Add a small delay to avoid rate limiting
        usleep(100000); // 100ms delay
    }
    
    if (!$quiet) {
        echo "Successfully generated and inserted $generated talent(s)!\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 