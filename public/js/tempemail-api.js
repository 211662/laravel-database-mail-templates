/**
 * TempEmail API Client
 * JavaScript/TypeScript client for TempEmail API
 */

class TempEmailAPI {
    constructor(baseURL = 'http://localhost/api/v1') {
        this.baseURL = baseURL;
    }

    /**
     * Make an API request
     */
    async request(endpoint, options = {}) {
        const url = `${this.baseURL}${endpoint}`;
        const response = await fetch(url, {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                ...options.headers,
            },
            ...options,
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'API request failed');
        }

        return data;
    }

    /**
     * Generate a new temporary email
     */
    async generateEmail(domainId = null, lifetimeHours = 2) {
        return this.request('/email/generate', {
            method: 'POST',
            body: JSON.stringify({ 
                domain_id: domainId,
                lifetime_hours: lifetimeHours 
            }),
        });
    }

    /**
     * Get email details
     */
    async getEmail(email) {
        return this.request(`/email/${email}`);
    }

    /**
     * Get inbox messages
     */
    async getInbox(email, page = 1) {
        return this.request(`/email/${email}/inbox?page=${page}`);
    }

    /**
     * Check for new messages
     */
    async checkNewMessages(email) {
        return this.request(`/email/${email}/check`);
    }

    /**
     * Delete an email
     */
    async deleteEmail(email) {
        return this.request(`/email/${email}`, {
            method: 'DELETE',
        });
    }

    /**
     * Get a specific message
     */
    async getMessage(messageId) {
        return this.request(`/message/${messageId}`);
    }

    /**
     * Get message HTML
     */
    async getMessageHtml(messageId) {
        const url = `${this.baseURL}/message/${messageId}/html`;
        const response = await fetch(url);
        return response.text();
    }

    /**
     * Mark message as read
     */
    async markMessageAsRead(messageId) {
        return this.request(`/message/${messageId}/read`, {
            method: 'POST',
        });
    }

    /**
     * Delete a message
     */
    async deleteMessage(messageId) {
        return this.request(`/message/${messageId}`, {
            method: 'DELETE',
        });
    }

    /**
     * Get available domains
     */
    async getDomains() {
        return this.request('/domains');
    }

    /**
     * Get attachment download URL
     */
    getAttachmentUrl(attachmentId) {
        return `${this.baseURL}/attachment/${attachmentId}/download`;
    }
}

// Export for different module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = TempEmailAPI;
}
if (typeof window !== 'undefined') {
    window.TempEmailAPI = TempEmailAPI;
}
