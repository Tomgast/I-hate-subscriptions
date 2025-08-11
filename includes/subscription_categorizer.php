<?php
/**
 * Subscription Categorizer
 * Automatically categorize subscriptions based on merchant names and patterns
 */

class SubscriptionCategorizer {
    
    /**
     * Category definitions with merchant patterns
     */
    private static $categoryPatterns = [
        'Entertainment' => [
            'keywords' => ['netflix', 'spotify', 'disney', 'hulu', 'hbo', 'amazon prime', 
                          'youtube premium', 'apple music', 'paramount', 'discovery', 
                          'crunchyroll', 'twitch', 'deezer'],
            'description' => 'Streaming services, music, movies, TV shows'
        ],
        
        'Gaming' => [
            'keywords' => ['steam', 'playstation', 'xbox', 'nintendo', 'epic games', 
                          'blizzard', 'ea games', 'ubisoft', 'jagex', 'games studio',
                          'riot games', 'valve', 'origin'],
            'description' => 'Gaming platforms, subscriptions, and services'
        ],
        
        'Software & Tools' => [
            'keywords' => ['adobe', 'microsoft', 'office 365', 'google workspace', 
                          'dropbox', 'onedrive', 'icloud', 'zoom', 'slack', 'notion',
                          'canva', 'figma', 'github', 'jetbrains', 'visual studio'],
            'description' => 'Software subscriptions, productivity tools, cloud storage'
        ],
        
        'News & Media' => [
            'keywords' => ['newspaper', 'magazine', 'news', 'times', 'guardian', 
                          'wall street', 'financial times', 'economist', 'medium',
                          'substack', 'patreon'],
            'description' => 'News subscriptions, magazines, media content'
        ],
        
        'Health & Fitness' => [
            'keywords' => ['gym', 'fitness', 'peloton', 'nike', 'adidas', 'strava',
                          'myfitnesspal', 'headspace', 'calm', 'meditation', 'yoga',
                          'health app', 'fitbit'],
            'description' => 'Gym memberships, fitness apps, health services'
        ],
        
        'Utilities' => [
            'keywords' => ['energy', 'gas', 'water', 'electricity', 'internet', 'phone',
                          'mobile', 'telecom', 'wifi', 'broadband', 'utilities'],
            'description' => 'Internet, phone, utilities, essential services'
        ],
        
        'Financial Services' => [
            'keywords' => ['bank', 'insurance', 'zorgverzekering', 'verzekering', 
                          'financial', 'investment', 'trading', 'paypal', 'stripe',
                          'wise', 'revolut', 'n26'],
            'description' => 'Banking, insurance, financial services'
        ],
        
        'Education' => [
            'keywords' => ['udemy', 'coursera', 'skillshare', 'masterclass', 'pluralsight',
                          'linkedin learning', 'education', 'course', 'university',
                          'school', 'training'],
            'description' => 'Online courses, education platforms, training'
        ],
        
        'Transportation' => [
            'keywords' => ['uber', 'lyft', 'taxi', 'ov-chipkaart', 'ns', 'public transport',
                          'car rental', 'lease', 'parking', 'fuel', 'charging'],
            'description' => 'Transportation, public transport, car services'
        ]
    ];
    
    /**
     * Automatically categorize a subscription based on merchant name
     */
    public static function categorizeSubscription($merchantName) {
        $merchantLower = strtolower($merchantName);
        
        foreach (self::$categoryPatterns as $category => $data) {
            foreach ($data['keywords'] as $keyword) {
                if (strpos($merchantLower, $keyword) !== false) {
                    return [
                        'category' => $category,
                        'confidence' => 'high',
                        'matched_keyword' => $keyword,
                        'description' => $data['description']
                    ];
                }
            }
        }
        
        return [
            'category' => 'Other',
            'confidence' => 'unknown',
            'matched_keyword' => null,
            'description' => 'Uncategorized subscription - user can manually assign'
        ];
    }
    
    /**
     * Get all available categories
     */
    public static function getAvailableCategories() {
        $categories = ['Other']; // Always include Other as default
        
        foreach (self::$categoryPatterns as $category => $data) {
            $categories[] = $category;
        }
        
        return $categories;
    }
    
    /**
     * Get category description
     */
    public static function getCategoryDescription($category) {
        if (isset(self::$categoryPatterns[$category])) {
            return self::$categoryPatterns[$category]['description'];
        }
        
        if ($category === 'Other') {
            return 'Uncategorized subscriptions that need manual sorting';
        }
        
        return 'Unknown category';
    }
    
    /**
     * Categorize multiple subscriptions at once
     */
    public static function categorizeMultiple($subscriptions) {
        $categorized = [];
        
        foreach ($subscriptions as $subscription) {
            $merchantName = $subscription['merchant_name'] ?? $subscription['name'] ?? '';
            $category = self::categorizeSubscription($merchantName);
            
            $categorized[] = array_merge($subscription, [
                'category' => $category['category'],
                'category_confidence' => $category['confidence'],
                'category_description' => $category['description']
            ]);
        }
        
        return $categorized;
    }
    
    /**
     * Get subscription statistics by category
     */
    public static function getCategoryStats($subscriptions) {
        $stats = [];
        $totalAmount = 0;
        
        foreach ($subscriptions as $subscription) {
            $category = $subscription['category'] ?? 'Other';
            $amount = abs($subscription['amount'] ?? 0);
            
            if (!isset($stats[$category])) {
                $stats[$category] = [
                    'count' => 0,
                    'total_amount' => 0,
                    'subscriptions' => []
                ];
            }
            
            $stats[$category]['count']++;
            $stats[$category]['total_amount'] += $amount;
            $stats[$category]['subscriptions'][] = $subscription;
            $totalAmount += $amount;
        }
        
        // Calculate percentages
        foreach ($stats as $category => &$data) {
            $data['percentage'] = $totalAmount > 0 ? round(($data['total_amount'] / $totalAmount) * 100, 1) : 0;
        }
        
        // Sort by total amount descending
        uasort($stats, function($a, $b) {
            return $b['total_amount'] <=> $a['total_amount'];
        });
        
        return $stats;
    }
}
?>
