/**
 * Authentication JavaScript Module
 * Handles Google OAuth and session management
 */

class Auth {
    static googleClientId = '267507492904-hr7q0qi2655ne01tv2si5ienpi6el4cm.apps.googleusercontent.com';
    
    /**
     * Initialize Google OAuth
     */
    static initGoogleAuth() {
        return new Promise((resolve, reject) => {
            // Load Google Identity Services
            if (typeof google === 'undefined') {
                const script = document.createElement('script');
                script.src = 'https://accounts.google.com/gsi/client';
                script.onload = () => {
                    this.setupGoogleAuth();
                    resolve();
                };
                script.onerror = reject;
                document.head.appendChild(script);
            } else {
                this.setupGoogleAuth();
                resolve();
            }
        });
    }
    
    /**
     * Setup Google OAuth configuration
     */
    static setupGoogleAuth() {
        google.accounts.id.initialize({
            client_id: this.googleClientId,
            callback: this.handleGoogleCallback.bind(this),
            auto_select: false,
            cancel_on_tap_outside: true
        });
    }
    
    /**
     * Render Google Sign-In button
     */
    static renderGoogleButton(elementId) {
        google.accounts.id.renderButton(
            document.getElementById(elementId),
            {
                theme: 'outline',
                size: 'large',
                width: 300,
                text: 'continue_with'
            }
        );
    }
    
    /**
     * Handle Google OAuth callback
     */
    static async handleGoogleCallback(response) {
        try {
            const result = await fetch('/api/auth/google-callback.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    token: response.credential
                })
            });
            
            const data = await result.json();
            
            if (data.success) {
                // Redirect to dashboard
                window.location.href = data.redirect || '/app/dashboard.html';
            } else {
                throw new Error(data.error || 'Login failed');
            }
        } catch (error) {
            console.error('Google login error:', error);
            this.showError('Login failed. Please try again.');
        }
    }
    
    /**
     * Check current session status
     */
    static async checkSession() {
        try {
            const response = await fetch('/api/auth/session.php');
            const data = await response.json();
            
            if (data.authenticated) {
                return data.user;
            } else {
                return null;
            }
        } catch (error) {
            console.error('Session check error:', error);
            return null;
        }
    }
    
    /**
     * Logout user
     */
    static async logout() {
        try {
            const response = await fetch('/api/auth/logout.php', {
                method: 'POST'
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Sign out from Google
                if (typeof google !== 'undefined') {
                    google.accounts.id.disableAutoSelect();
                }
                return true;
            } else {
                throw new Error(data.error || 'Logout failed');
            }
        } catch (error) {
            console.error('Logout error:', error);
            return false;
        }
    }
    
    /**
     * Show error message
     */
    static showError(message) {
        // Create or update error element
        let errorElement = document.getElementById('auth-error');
        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.id = 'auth-error';
            errorElement.className = 'bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4';
            
            // Insert at the top of the main content
            const container = document.querySelector('.container') || document.body;
            container.insertBefore(errorElement, container.firstChild);
        }
        
        errorElement.innerHTML = `
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium">${message}</p>
                </div>
            </div>
        `;
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            if (errorElement.parentNode) {
                errorElement.parentNode.removeChild(errorElement);
            }
        }, 5000);
    }
    
    /**
     * Show success message
     */
    static showSuccess(message) {
        // Create or update success element
        let successElement = document.getElementById('auth-success');
        if (!successElement) {
            successElement = document.createElement('div');
            successElement.id = 'auth-success';
            successElement.className = 'bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-4';
            
            // Insert at the top of the main content
            const container = document.querySelector('.container') || document.body;
            container.insertBefore(successElement, container.firstChild);
        }
        
        successElement.innerHTML = `
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium">${message}</p>
                </div>
            </div>
        `;
        
        // Auto-hide after 3 seconds
        setTimeout(() => {
            if (successElement.parentNode) {
                successElement.parentNode.removeChild(successElement);
            }
        }, 3000);
    }
    
    /**
     * Require authentication (redirect if not logged in)
     */
    static async requireAuth() {
        const user = await this.checkSession();
        if (!user) {
            window.location.href = '/app/auth/login.html';
            return null;
        }
        return user;
    }
    
    /**
     * Check if user is premium
     */
    static async isPremium() {
        const user = await this.checkSession();
        return user && user.is_paid;
    }
}

// Export for use in other scripts
window.Auth = Auth;
