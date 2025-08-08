<?php
/**
 * Setup Unsubscribe Guides Database and Content
 */

require_once __DIR__ . '/config/db_config.php';

echo "=== Setting up Unsubscribe Guides ===\n\n";

try {
    $pdo = getDBConnection();
    
    // Create unsubscribe_guides table
    echo "1. Creating unsubscribe_guides table...\n";
    
    $createTable = "
        CREATE TABLE IF NOT EXISTS unsubscribe_guides (
            id INT AUTO_INCREMENT PRIMARY KEY,
            service_name VARCHAR(255) NOT NULL,
            service_slug VARCHAR(255) NOT NULL UNIQUE,
            category VARCHAR(100) NOT NULL,
            difficulty ENUM('Easy', 'Medium', 'Hard') DEFAULT 'Easy',
            estimated_time VARCHAR(50) DEFAULT '2-5 minutes',
            description TEXT,
            steps JSON NOT NULL,
            tips TEXT,
            warnings TEXT,
            contact_info JSON,
            website_url VARCHAR(500),
            logo_url VARCHAR(500),
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_service_slug (service_slug),
            INDEX idx_category (category),
            INDEX idx_active (is_active)
        )
    ";
    
    $pdo->exec($createTable);
    echo "   ✅ Table created successfully\n\n";
    
    // Insert comprehensive guide data
    echo "2. Inserting unsubscribe guides...\n";
    
    $guides = [
        [
            'service_name' => 'Netflix',
            'service_slug' => 'netflix',
            'category' => 'Streaming',
            'difficulty' => 'Easy',
            'estimated_time' => '2-3 minutes',
            'description' => 'Cancel your Netflix subscription and stop automatic renewals.',
            'steps' => json_encode([
                'Go to netflix.com and sign in to your account',
                'Click on your profile icon in the top right corner',
                'Select "Account" from the dropdown menu',
                'Under "Membership & Billing", click "Cancel Membership"',
                'Confirm cancellation by clicking "Finish Cancellation"',
                'You\'ll receive a confirmation email'
            ]),
            'tips' => 'You can continue watching until your current billing period ends. You can reactivate anytime.',
            'warnings' => 'Downloaded content will be removed when your subscription ends.',
            'contact_info' => json_encode([
                'phone' => '1-866-579-7172',
                'chat' => 'Available through Netflix Help Center',
                'email' => 'Not available'
            ]),
            'website_url' => 'https://netflix.com',
            'logo_url' => '/assets/images/services/netflix.png'
        ],
        [
            'service_name' => 'Spotify',
            'service_slug' => 'spotify',
            'category' => 'Music',
            'difficulty' => 'Easy',
            'estimated_time' => '2-3 minutes',
            'description' => 'Cancel your Spotify Premium subscription.',
            'steps' => json_encode([
                'Go to spotify.com and log in to your account',
                'Click on your profile name in the top right',
                'Select "Account" from the dropdown',
                'Scroll down to "Your plan" section',
                'Click "Change or cancel your subscription"',
                'Click "Cancel Premium"',
                'Follow the prompts to confirm cancellation'
            ]),
            'tips' => 'Your Premium features will continue until the end of your billing cycle.',
            'warnings' => 'You\'ll lose offline downloads and ad-free listening.',
            'contact_info' => json_encode([
                'phone' => 'Not available',
                'chat' => 'Available through Spotify Support',
                'email' => 'Through support form only'
            ]),
            'website_url' => 'https://spotify.com',
            'logo_url' => '/assets/images/services/spotify.png'
        ],
        [
            'service_name' => 'Amazon Prime',
            'service_slug' => 'amazon-prime',
            'category' => 'Shopping & Streaming',
            'difficulty' => 'Medium',
            'estimated_time' => '3-5 minutes',
            'description' => 'Cancel your Amazon Prime membership and stop automatic renewals.',
            'steps' => json_encode([
                'Go to amazon.com and sign in to your account',
                'Hover over "Account & Lists" and click "Your Account"',
                'Click "Prime Membership" under "Ordering and shopping preferences"',
                'Click "Update, cancel and more" on the left side',
                'Click "End membership"',
                'Choose to end immediately or at renewal date',
                'Confirm your cancellation'
            ]),
            'tips' => 'You can choose to end immediately with a refund or continue until renewal date.',
            'warnings' => 'You\'ll lose free shipping, Prime Video, and other Prime benefits.',
            'contact_info' => json_encode([
                'phone' => '1-888-280-4331',
                'chat' => 'Available through Amazon Customer Service',
                'email' => 'Through Your Account contact form'
            ]),
            'website_url' => 'https://amazon.com',
            'logo_url' => '/assets/images/services/amazon.png'
        ],
        [
            'service_name' => 'Disney+',
            'service_slug' => 'disney-plus',
            'category' => 'Streaming',
            'difficulty' => 'Easy',
            'estimated_time' => '2-3 minutes',
            'description' => 'Cancel your Disney+ subscription.',
            'steps' => json_encode([
                'Go to disneyplus.com and sign in',
                'Click on your profile icon',
                'Select "Account" from the menu',
                'Click "Billing Details"',
                'Click "Cancel Subscription"',
                'Confirm your cancellation',
                'You\'ll receive a confirmation email'
            ]),
            'tips' => 'You can continue watching until your subscription expires.',
            'warnings' => 'Downloaded content will be removed when subscription ends.',
            'contact_info' => json_encode([
                'phone' => '1-888-905-7888',
                'chat' => 'Available through Disney+ Help Center',
                'email' => 'Not available'
            ]),
            'website_url' => 'https://disneyplus.com',
            'logo_url' => '/assets/images/services/disney.png'
        ],
        [
            'service_name' => 'Microsoft 365',
            'service_slug' => 'microsoft-365',
            'category' => 'Productivity',
            'difficulty' => 'Medium',
            'estimated_time' => '3-5 minutes',
            'description' => 'Cancel your Microsoft 365 subscription.',
            'steps' => json_encode([
                'Go to account.microsoft.com and sign in',
                'Click "Services & subscriptions"',
                'Find your Microsoft 365 subscription',
                'Click "Manage"',
                'Click "Cancel subscription"',
                'Choose a reason for canceling',
                'Confirm cancellation'
            ]),
            'tips' => 'Your subscription will remain active until the end of the billing period.',
            'warnings' => 'You\'ll lose access to Office apps and OneDrive storage beyond 5GB.',
            'contact_info' => json_encode([
                'phone' => '1-800-642-7676',
                'chat' => 'Available through Microsoft Support',
                'email' => 'Through Microsoft Support portal'
            ]),
            'website_url' => 'https://microsoft.com',
            'logo_url' => '/assets/images/services/microsoft.png'
        ],
        [
            'service_name' => 'Adobe Creative Cloud',
            'service_slug' => 'adobe-creative-cloud',
            'category' => 'Design & Creative',
            'difficulty' => 'Hard',
            'estimated_time' => '5-10 minutes',
            'description' => 'Cancel your Adobe Creative Cloud subscription.',
            'steps' => json_encode([
                'Go to account.adobe.com and sign in',
                'Click "Plans" in the left sidebar',
                'Find your Creative Cloud plan',
                'Click "Manage plan"',
                'Click "Cancel plan"',
                'Choose a reason for canceling',
                'Review cancellation terms and fees',
                'Confirm cancellation if you agree to terms'
            ]),
            'tips' => 'Check for early termination fees before canceling.',
            'warnings' => 'You may be charged a cancellation fee if canceling before your annual commitment ends.',
            'contact_info' => json_encode([
                'phone' => '1-800-833-6687',
                'chat' => 'Available through Adobe Support',
                'email' => 'Through Adobe Support portal'
            ]),
            'website_url' => 'https://adobe.com',
            'logo_url' => '/assets/images/services/adobe.png'
        ],
        [
            'service_name' => 'YouTube Premium',
            'service_slug' => 'youtube-premium',
            'category' => 'Streaming',
            'difficulty' => 'Easy',
            'estimated_time' => '2-3 minutes',
            'description' => 'Cancel your YouTube Premium subscription.',
            'steps' => json_encode([
                'Go to youtube.com and sign in',
                'Click your profile picture in the top right',
                'Click "Purchases and memberships"',
                'Find YouTube Premium',
                'Click "Manage"',
                'Click "Cancel membership"',
                'Confirm cancellation'
            ]),
            'tips' => 'You\'ll keep Premium benefits until the end of your billing cycle.',
            'warnings' => 'You\'ll see ads again and lose background play and downloads.',
            'contact_info' => json_encode([
                'phone' => 'Not available',
                'chat' => 'Available through YouTube Help',
                'email' => 'Through YouTube Help form'
            ]),
            'website_url' => 'https://youtube.com',
            'logo_url' => '/assets/images/services/youtube.png'
        ],
        [
            'service_name' => 'Dropbox',
            'service_slug' => 'dropbox',
            'category' => 'Cloud Storage',
            'difficulty' => 'Easy',
            'estimated_time' => '2-3 minutes',
            'description' => 'Cancel your Dropbox subscription and downgrade to free.',
            'steps' => json_encode([
                'Go to dropbox.com and sign in',
                'Click your avatar in the top right',
                'Select "Settings"',
                'Click "Plan" tab',
                'Click "Cancel plan"',
                'Choose to downgrade to Basic (free)',
                'Confirm cancellation'
            ]),
            'tips' => 'Your files will remain but storage will be limited to 2GB on free plan.',
            'warnings' => 'Files exceeding free storage limit may become inaccessible.',
            'contact_info' => json_encode([
                'phone' => 'Not available for Basic users',
                'chat' => 'Available for paid users',
                'email' => 'Through Dropbox Help Center'
            ]),
            'website_url' => 'https://dropbox.com',
            'logo_url' => '/assets/images/services/dropbox.png'
        ]
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO unsubscribe_guides (
            service_name, service_slug, category, difficulty, estimated_time,
            description, steps, tips, warnings, contact_info, website_url, logo_url
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($guides as $guide) {
        $stmt->execute([
            $guide['service_name'],
            $guide['service_slug'],
            $guide['category'],
            $guide['difficulty'],
            $guide['estimated_time'],
            $guide['description'],
            $guide['steps'],
            $guide['tips'],
            $guide['warnings'],
            $guide['contact_info'],
            $guide['website_url'],
            $guide['logo_url']
        ]);
        echo "   ✅ Added guide for " . $guide['service_name'] . "\n";
    }
    
    echo "\n3. Database setup complete!\n";
    echo "   - Created unsubscribe_guides table\n";
    echo "   - Added " . count($guides) . " comprehensive guides\n";
    echo "   - Ready for unsubscribe guide functionality\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
