/**
 * MCC Railway Dashboard - Shared JavaScript
 * Common functions and utilities for all pages
 */

// ==========================================
// Document Ready
// ==========================================

document.addEventListener('DOMContentLoaded', function() {
    initializeNavigation();
    initializeTooltips();
    initializePopovers();
});

// ==========================================
// Navigation & Sidebar
// ==========================================

/**
 * Initialize navigation by setting active links based on current page
 */
function initializeNavigation() {
    const currentPage = window.location.pathname.split('/').pop() || 'index.html';
    const navLinks = document.querySelectorAll('.sidebar .nav-link');
    
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href === currentPage || (currentPage === '' && href === 'index.html')) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });
}

// ==========================================
// Bootstrap Tooltips & Popovers
// ==========================================

/**
 * Initialize Bootstrap tooltips
 */
function initializeTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

/**
 * Initialize Bootstrap popovers
 */
function initializePopovers() {
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function(popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
}

// ==========================================
// Form Validation
// ==========================================

/**
 * Validate email format
 * @param {string} email - Email address to validate
 * @returns {boolean} - True if valid email
 */
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

/**
 * Validate password strength
 * @param {string} password - Password to validate
 * @returns {object} - Contains isValid and strength properties
 */
function validatePassword(password) {
    const strength = {
        valid: true,
        score: 0,
        issues: []
    };

    if (password.length < 8) {
        strength.valid = false;
        strength.issues.push('Minimum 8 characters required');
    } else {
        strength.score += 1;
    }

    if (!/[A-Z]/.test(password)) {
        strength.valid = false;
        strength.issues.push('At least one uppercase letter required');
    } else {
        strength.score += 1;
    }

    if (!/[a-z]/.test(password)) {
        strength.valid = false;
        strength.issues.push('At least one lowercase letter required');
    } else {
        strength.score += 1;
    }

    if (!/[0-9]/.test(password)) {
        strength.valid = false;
        strength.issues.push('At least one number required');
    } else {
        strength.score += 1;
    }

    if (!/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) {
        strength.issues.push('Special character recommended');
    } else {
        strength.score += 1;
    }

    return strength;
}

/**
 * Validate form fields
 * @param {HTMLFormElement} form - Form to validate
 * @returns {boolean} - True if form is valid
 */
function validateForm(form) {
    let isValid = true;

    // Check HTML5 validation
    if (!form.checkValidity()) {
        isValid = false;
    }

    // Check required fields
    form.querySelectorAll('[required]').forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });

    // Check email fields
    form.querySelectorAll('input[type="email"]').forEach(field => {
        if (field.value && !isValidEmail(field.value)) {
            field.classList.add('is-invalid');
            isValid = false;
        } else if (field.value) {
            field.classList.remove('is-invalid');
        }
    });

    return isValid;
}

// ==========================================
// Date & Time Utilities
// ==========================================

/**
 * Format date to YYYY-MM-DD format
 * @param {Date} date - Date to format
 * @returns {string} - Formatted date
 */
function formatDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

/**
 * Format time to HH:MM format
 * @param {string} timeStr - Time string in HH:MM format
 * @returns {string} - Formatted time
 */
function formatTime(timeStr) {
    if (!timeStr || timeStr.length !== 5) return '';
    const [hours, minutes] = timeStr.split(':');
    return `${hours}:${minutes}`;
}

/**
 * Calculate time difference between two times
 * @param {string} startTime - Start time in HH:MM format
 * @param {string} endTime - End time in HH:MM format
 * @returns {string} - Duration in "Xh Ym" format
 */
function calculateTimeDifference(startTime, endTime) {
    if (!startTime || !endTime) return '';

    const [startHour, startMin] = startTime.split(':').map(Number);
    const [endHour, endMin] = endTime.split(':').map(Number);

    let startTotalMin = startHour * 60 + startMin;
    let endTotalMin = endHour * 60 + endMin;

    let duration = endTotalMin - startTotalMin;
    if (duration < 0) duration += 24 * 60;

    const hours = Math.floor(duration / 60);
    const minutes = duration % 60;

    return `${hours}h ${minutes}m`;
}

/**
 * Get today's date in YYYY-MM-DD format
 * @returns {string} - Today's date
 */
function getTodayDate() {
    return formatDate(new Date());
}

// ==========================================
// Data Table Utilities
// ==========================================

/**
 * Filter table rows based on search term
 * @param {HTMLTableElement} table - Table element
 * @param {string} searchTerm - Search term
 */
function filterTable(table, searchTerm) {
    const rows = table.querySelectorAll('tbody tr');
    const term = searchTerm.toLowerCase();

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(term)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

/**
 * Sort table by column
 * @param {HTMLTableElement} table - Table element
 * @param {number} columnIndex - Column index to sort by
 * @param {boolean} ascending - Sort direction
 */
function sortTable(table, columnIndex, ascending = true) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));

    rows.sort((a, b) => {
        const aValue = a.cells[columnIndex].textContent.trim();
        const bValue = b.cells[columnIndex].textContent.trim();

        // Try to parse as numbers
        const aNum = parseFloat(aValue);
        const bNum = parseFloat(bValue);

        if (!isNaN(aNum) && !isNaN(bNum)) {
            return ascending ? aNum - bNum : bNum - aNum;
        }

        // Otherwise compare as strings
        return ascending
            ? aValue.localeCompare(bValue)
            : bValue.localeCompare(aValue);
    });

    rows.forEach(row => tbody.appendChild(row));
}

/**
 * Export table data to CSV
 * @param {HTMLTableElement} table - Table element
 * @param {string} filename - Output filename
 */
function exportTableToCSV(table, filename = 'export.csv') {
    const csv = [];

    // Get headers
    const headers = [];
    table.querySelectorAll('thead th').forEach(th => {
        headers.push('"' + th.textContent.trim().replace(/"/g, '""') + '"');
    });
    csv.push(headers.join(','));

    // Get rows
    table.querySelectorAll('tbody tr').forEach(tr => {
        const row = [];
        tr.querySelectorAll('td').forEach(td => {
            row.push('"' + td.textContent.trim().replace(/"/g, '""') + '"');
        });
        csv.push(row.join(','));
    });

    // Download
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    window.URL.revokeObjectURL(url);
}

// ==========================================
// Notification & Alert Utilities
// ==========================================

/**
 * Show success notification
 * @param {string} message - Message to display
 * @param {number} duration - Duration in milliseconds
 */
function showSuccessNotification(message, duration = 3000) {
    showNotification(message, 'success', duration);
}

/**
 * Show error notification
 * @param {string} message - Message to display
 * @param {number} duration - Duration in milliseconds
 */
function showErrorNotification(message, duration = 3000) {
    showNotification(message, 'danger', duration);
}

/**
 * Show info notification
 * @param {string} message - Message to display
 * @param {number} duration - Duration in milliseconds
 */
function showInfoNotification(message, duration = 3000) {
    showNotification(message, 'info', duration);
}

/**
 * Show notification
 * @param {string} message - Message to display
 * @param {string} type - Type (success, danger, warning, info)
 * @param {number} duration - Duration in milliseconds
 */
function showNotification(message, type = 'info', duration = 3000) {
    const alertId = 'alert-' + Date.now();
    const alertHTML = `
        <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;

    // Create container if doesn't exist
    let container = document.getElementById('notification-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'notification-container';
        container.style.position = 'fixed';
        container.style.top = '100px';
        container.style.right = '20px';
        container.style.zIndex = '9999';
        container.style.maxWidth = '500px';
        document.body.appendChild(container);
    }

    container.insertAdjacentHTML('beforeend', alertHTML);

    // Auto-close after duration
    if (duration > 0) {
        setTimeout(() => {
            const alert = document.getElementById(alertId);
            if (alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, duration);
    }
}

// ==========================================
// Modal & Dialog Utilities
// ==========================================

/**
 * Show confirmation dialog
 * @param {string} title - Dialog title
 * @param {string} message - Dialog message
 * @param {Function} onConfirm - Callback on confirm
 * @param {Function} onCancel - Callback on cancel
 */
function showConfirmDialog(title, message, onConfirm, onCancel) {
    if (confirm(`${title}\n\n${message}`)) {
        onConfirm();
    } else {
        if (onCancel) onCancel();
    }
}

/**
 * Trigger Bootstrap modal
 * @param {string} modalId - Modal id
 */
function showModal(modalId) {
    const modalElement = document.getElementById(modalId);
    if (modalElement) {
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    }
}

/**
 * Hide Bootstrap modal
 * @param {string} modalId - Modal id
 */
function hideModal(modalId) {
    const modalElement = document.getElementById(modalId);
    if (modalElement) {
        const modal = bootstrap.Modal.getInstance(modalElement);
        if (modal) {
            modal.hide();
        }
    }
}

// ==========================================
// Local Storage Utilities
// ==========================================

/**
 * Save data to localStorage
 * @param {string} key - Storage key
 * @param {any} value - Value to store
 */
function saveToStorage(key, value) {
    try {
        localStorage.setItem(key, JSON.stringify(value));
    } catch (error) {
        console.error('Error saving to localStorage:', error);
    }
}

/**
 * Get data from localStorage
 * @param {string} key - Storage key
 * @returns {any} - Stored value or null
 */
function getFromStorage(key) {
    try {
        const item = localStorage.getItem(key);
        return item ? JSON.parse(item) : null;
    } catch (error) {
        console.error('Error reading from localStorage:', error);
        return null;
    }
}

/**
 * Remove data from localStorage
 * @param {string} key - Storage key
 */
function removeFromStorage(key) {
    try {
        localStorage.removeItem(key);
    } catch (error) {
        console.error('Error removing from localStorage:', error);
    }
}

// ==========================================
// API & Ajax Utilities
// ==========================================

/**
 * Make GET request
 * @param {string} url - URL to fetch
 * @returns {Promise} - Promise resolving to response
 */
async function getRequest(url) {
    try {
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        });
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        return await response.json();
    } catch (error) {
        console.error('GET request error:', error);
        throw error;
    }
}

/**
 * Make POST request
 * @param {string} url - URL to fetch
 * @param {object} data - Data to send
 * @returns {Promise} - Promise resolving to response
 */
async function postRequest(url, data) {
    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        return await response.json();
    } catch (error) {
        console.error('POST request error:', error);
        throw error;
    }
}

// ==========================================
// Utility Functions
// ==========================================

/**
 * Debounce function for performance optimization
 * @param {Function} func - Function to debounce
 * @param {number} wait - Wait time in milliseconds
 * @returns {Function} - Debounced function
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Throttle function for performance optimization
 * @param {Function} func - Function to throttle
 * @param {number} limit - Time limit in milliseconds
 * @returns {Function} - Throttled function
 */
function throttle(func, limit) {
    let inThrottle;
    return function(...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

/**
 * Format currency value
 * @param {number} value - Value to format
 * @param {string} currency - Currency code (default: USD)
 * @returns {string} - Formatted currency
 */
function formatCurrency(value, currency = 'USD') {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency
    }).format(value);
}

/**
 * Format number with thousand separator
 * @param {number} value - Value to format
 * @param {number} decimals - Decimal places
 * @returns {string} - Formatted number
 */
function formatNumber(value, decimals = 0) {
    return Number(value).toLocaleString('en-US', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    });
}

// ==========================================
// Console Utilities (Development Only)
// ==========================================

/**
 * Log debug message
 * @param {string} message - Message to log
 * @param {any} data - Additional data to log
 */
function logDebug(message, data = null) {
    if (data) {
        console.log('%c' + message, 'color: blue; font-weight: bold;', data);
    } else {
        console.log('%c' + message, 'color: blue; font-weight: bold;');
    }
}

/**
 * Log info message
 * @param {string} message - Message to log
 * @param {any} data - Additional data to log
 */
function logInfo(message, data = null) {
    if (data) {
        console.log('%c' + message, 'color: green; font-weight: bold;', data);
    } else {
        console.log('%c' + message, 'color: green; font-weight: bold;');
    }
}

/**
 * Log warning message
 * @param {string} message - Message to log
 * @param {any} data - Additional data to log
 */
function logWarning(message, data = null) {
    if (data) {
        console.warn('%c' + message, 'color: orange; font-weight: bold;', data);
    } else {
        console.warn('%c' + message, 'color: orange; font-weight: bold;');
    }
}

/**
 * Log error message
 * @param {string} message - Message to log
 * @param {any} data - Additional data to log
 */
function logError(message, data = null) {
    if (data) {
        console.error('%c' + message, 'color: red; font-weight: bold;', data);
    } else {
        console.error('%c' + message, 'color: red; font-weight: bold;');
    }
}
