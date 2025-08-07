<?php
/**
 * PHASE 3C.4: UNSUBSCRIBE GUIDES SERVICE
 * Manage unsubscribe guides and cancellation instructions for popular services
 */

class UnsubscribeService {
    private $db;
    
    public function __construct() {
        $this->db = getDbConnection();
    }
    
    /**
     * Get unsubscribe guide for a specific service
     */
    public function getUnsubscribeGuide($serviceName) {
        try {
            // First try exact match
            $stmt = $this->db->prepare("
                SELECT * FROM unsubscribe_guides 
                WHERE LOWER(service_name) = LOWER(?) 
                OR LOWER(service_name) LIKE LOWER(?)
                ORDER BY service_name = ? DESC, popularity DESC
                LIMIT 1
            ");
            $likePattern = '%' . $serviceName . '%';
            $stmt->execute([$serviceName, $likePattern, $serviceName]);
            $guide = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($guide) {
                return $guide;
            }
            
            // If no specific guide found, return generic guide
            return $this->getGenericUnsubscribeGuide();
            
        } catch (Exception $e) {
            error_log("Error getting unsubscribe guide: " . $e->getMessage());
            return $this->getGenericUnsubscribeGuide();
        }
    }
    
    /**
     * Get all available unsubscribe guides
     */
    public function getAllGuides($limit = 50) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM unsubscribe_guides 
                ORDER BY popularity DESC, service_name ASC 
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error getting all guides: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Search unsubscribe guides
     */
    public function searchGuides($query, $limit = 20) {
        try {
            $searchPattern = '%' . $query . '%';
            $stmt = $this->db->prepare("
                SELECT * FROM unsubscribe_guides 
                WHERE LOWER(service_name) LIKE LOWER(?) 
                   OR LOWER(category) LIKE LOWER(?)
                   OR LOWER(description) LIKE LOWER(?)
                ORDER BY 
                    CASE WHEN LOWER(service_name) = LOWER(?) THEN 1 ELSE 2 END,
                    popularity DESC,
                    service_name ASC
                LIMIT ?
            ");
            $stmt->execute([$searchPattern, $searchPattern, $searchPattern, $query, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error searching guides: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get guides by category
     */
    public function getGuidesByCategory($category, $limit = 20) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM unsubscribe_guides 
                WHERE LOWER(category) = LOWER(?)
                ORDER BY popularity DESC, service_name ASC
                LIMIT ?
            ");
            $stmt->execute([$category, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error getting guides by category: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get popular/featured guides
     */
    public function getPopularGuides($limit = 12) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM unsubscribe_guides 
                WHERE is_featured = 1 OR popularity >= 8
                ORDER BY popularity DESC, service_name ASC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error getting popular guides: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Track guide usage
     */
    public function trackGuideUsage($guideId, $userId = null) {
        try {
            // Update guide usage count
            $stmt = $this->db->prepare("
                UPDATE unsubscribe_guides 
                SET usage_count = usage_count + 1,
                    last_used = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$guideId]);
            
            // Log usage if user provided
            if ($userId) {
                $stmt = $this->db->prepare("
                    INSERT INTO unsubscribe_guide_usage 
                    (user_id, guide_id, used_at) 
                    VALUES (?, ?, NOW())
                    ON DUPLICATE KEY UPDATE used_at = NOW()
                ");
                $stmt->execute([$userId, $guideId]);
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Error tracking guide usage: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user's recently used guides
     */
    public function getUserRecentGuides($userId, $limit = 10) {
        try {
            $stmt = $this->db->prepare("
                SELECT ug.*, ugu.used_at
                FROM unsubscribe_guides ug
                JOIN unsubscribe_guide_usage ugu ON ug.id = ugu.guide_id
                WHERE ugu.user_id = ?
                ORDER BY ugu.used_at DESC
                LIMIT ?
            ");
            $stmt->execute([$userId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error getting user recent guides: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get generic unsubscribe guide for unknown services
     */
    private function getGenericUnsubscribeGuide() {
        return [
            'id' => 0,
            'service_name' => 'Generic Service',
            'category' => 'General',
            'description' => 'General steps to cancel most subscription services',
            'difficulty' => 'Medium',
            'estimated_time' => '10-15 minutes',
            'steps' => json_encode([
                [
                    'step' => 1,
                    'title' => 'Check Your Email',
                    'description' => 'Look for the original signup confirmation email or recent billing emails',
                    'details' => 'Search your email for terms like "welcome", "subscription", "billing", or the service name'
                ],
                [
                    'step' => 2,
                    'title' => 'Log Into Your Account',
                    'description' => 'Visit the service website and log into your account',
                    'details' => 'Use the login credentials you created when signing up'
                ],
                [
                    'step' => 3,
                    'title' => 'Find Account Settings',
                    'description' => 'Look for "Account", "Settings", "Profile", or "Billing" sections',
                    'details' => 'These are usually found in the top menu, sidebar, or footer'
                ],
                [
                    'step' => 4,
                    'title' => 'Locate Subscription Management',
                    'description' => 'Find "Subscription", "Billing", "Plan", or "Cancel" options',
                    'details' => 'This might be under "Account Settings" or "Billing Information"'
                ],
                [
                    'step' => 5,
                    'title' => 'Cancel Subscription',
                    'description' => 'Click "Cancel", "End Subscription", or "Deactivate"',
                    'details' => 'You may need to confirm the cancellation or provide a reason'
                ],
                [
                    'step' => 6,
                    'title' => 'Confirm Cancellation',
                    'description' => 'Save confirmation email or screenshot as proof',
                    'details' => 'Keep this for your records in case of future billing disputes'
                ]
            ]),
            'tips' => json_encode([
                'Check if you can downgrade to a free plan instead of canceling completely',
                'Note your billing cycle end date - you may have access until then',
                'Some services require 24-48 hours to process cancellations',
                'If you can\'t find cancel options, try contacting customer support',
                'Consider pausing/freezing your subscription if available'
            ]),
            'common_issues' => json_encode([
                'Cancel button is hidden or hard to find',
                'Service requires phone call to cancel',
                'Multiple confirmation steps or retention offers',
                'Cancellation only takes effect at end of billing period'
            ]),
            'contact_info' => json_encode([
                'method' => 'general',
                'details' => 'Look for "Contact Us", "Support", or "Help" on the service website'
            ]),
            'is_featured' => 0,
            'popularity' => 5
        ];
    }
    
    /**
     * Initialize unsubscribe guides table with popular services
     */
    public function initializeGuidesTable() {
        try {
            // Create table if it doesn't exist
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS unsubscribe_guides (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    service_name VARCHAR(100) NOT NULL,
                    category VARCHAR(50) NOT NULL,
                    description TEXT,
                    difficulty ENUM('Easy', 'Medium', 'Hard') DEFAULT 'Medium',
                    estimated_time VARCHAR(50),
                    steps JSON,
                    tips JSON,
                    common_issues JSON,
                    contact_info JSON,
                    website_url VARCHAR(255),
                    is_featured BOOLEAN DEFAULT FALSE,
                    popularity INT DEFAULT 5,
                    usage_count INT DEFAULT 0,
                    last_used TIMESTAMP NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_service_name (service_name),
                    INDEX idx_category (category),
                    INDEX idx_popularity (popularity),
                    INDEX idx_featured (is_featured)
                )
            ");
            
            // Create usage tracking table
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS unsubscribe_guide_usage (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    guide_id INT NOT NULL,
                    used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_user_guide (user_id, guide_id),
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (guide_id) REFERENCES unsubscribe_guides(id) ON DELETE CASCADE
                )
            ");
            
            return true;
            
        } catch (Exception $e) {
            error_log("Error initializing unsubscribe guides table: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Seed popular unsubscribe guides
     */
    public function seedPopularGuides() {
        $guides = [
            [
                'service_name' => 'Netflix',
                'category' => 'Streaming',
                'description' => 'Cancel your Netflix subscription',
                'difficulty' => 'Easy',
                'estimated_time' => '2-3 minutes',
                'website_url' => 'https://netflix.com',
                'is_featured' => true,
                'popularity' => 10
            ],
            [
                'service_name' => 'Spotify',
                'category' => 'Music',
                'description' => 'Cancel Spotify Premium subscription',
                'difficulty' => 'Easy',
                'estimated_time' => '3-5 minutes',
                'website_url' => 'https://spotify.com',
                'is_featured' => true,
                'popularity' => 10
            ],
            [
                'service_name' => 'Amazon Prime',
                'category' => 'Shopping',
                'description' => 'Cancel Amazon Prime membership',
                'difficulty' => 'Medium',
                'estimated_time' => '5-10 minutes',
                'website_url' => 'https://amazon.com',
                'is_featured' => true,
                'popularity' => 9
            ],
            [
                'service_name' => 'Disney+',
                'category' => 'Streaming',
                'description' => 'Cancel Disney Plus subscription',
                'difficulty' => 'Easy',
                'estimated_time' => '3-5 minutes',
                'website_url' => 'https://disneyplus.com',
                'is_featured' => true,
                'popularity' => 9
            ],
            [
                'service_name' => 'Adobe Creative Cloud',
                'category' => 'Software',
                'description' => 'Cancel Adobe subscription',
                'difficulty' => 'Hard',
                'estimated_time' => '15-30 minutes',
                'website_url' => 'https://adobe.com',
                'is_featured' => true,
                'popularity' => 8
            ]
        ];
        
        foreach ($guides as $guide) {
            $this->insertGuide($guide);
        }
    }
    
    /**
     * Insert a new guide
     */
    private function insertGuide($guideData) {
        try {
            $stmt = $this->db->prepare("
                INSERT IGNORE INTO unsubscribe_guides 
                (service_name, category, description, difficulty, estimated_time, website_url, is_featured, popularity)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            return $stmt->execute([
                $guideData['service_name'],
                $guideData['category'],
                $guideData['description'],
                $guideData['difficulty'],
                $guideData['estimated_time'],
                $guideData['website_url'],
                $guideData['is_featured'],
                $guideData['popularity']
            ]);
            
        } catch (Exception $e) {
            error_log("Error inserting guide: " . $e->getMessage());
            return false;
        }
    }
}

// Helper function to get UnsubscribeService instance
function getUnsubscribeService() {
    static $instance = null;
    if ($instance === null) {
        $instance = new UnsubscribeService();
    }
    return $instance;
}
?>
