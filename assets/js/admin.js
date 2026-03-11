/**
 * Admin JavaScript
 * Menu Scanner System - Admin Dashboard
 */

// Confirm delete actions
document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
    
    // Form validation enhancement
    const forms = document.querySelectorAll('form[method="POST"]');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
    
    // Image preview for file uploads
    const imageInputs = document.querySelectorAll('input[type="file"][accept="image/*"]');
    imageInputs.forEach(function(input) {
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.createElement('img');
                    preview.src = e.target.result;
                    preview.className = 'img-thumbnail mt-2';
                    preview.style.maxHeight = '150px';
                    
                    const existingPreview = input.parentElement.querySelector('.img-thumbnail');
                    if (existingPreview) {
                        existingPreview.remove();
                    }
                    
                    input.parentElement.appendChild(preview);
                };
                reader.readAsDataURL(file);
            }
        });
    });
    
    // Confirm before leaving page with unsaved changes
    const unsavedForms = document.querySelectorAll('form[data-unsaved-warning]');
    let formChanged = false;
    
    unsavedForms.forEach(function(form) {
        form.querySelectorAll('input, select, textarea').forEach(function(field) {
            field.addEventListener('change', function() {
                formChanged = true;
            });
        });
    });
    
    window.addEventListener('beforeunload', function(e) {
        if (formChanged) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
});

// Utility functions
function confirmDelete(message) {
    return confirm(message || 'Are you sure you want to delete this item?');
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} position-fixed top-0 end-0 m-3`;
    notification.style.zIndex = '9999';
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(function() {
        notification.classList.add('fade');
        setTimeout(function() {
            notification.remove();
        }, 300);
    }, 3000);
}

// Service calls auto-refresh indicator
if (window.location.href.includes('service-calls.php')) {
    // Add visual indicator for auto-refresh
    const refreshIndicator = document.createElement('div');
    refreshIndicator.className = 'position-fixed bottom-0 end-0 m-3 live-indicator';
    refreshIndicator.innerHTML = `
        <span class="badge bg-success">
            <i class="bi bi-wifi"></i> Live
        </span>
    `;
    document.body.appendChild(refreshIndicator);
}

// Table QR code modal handling
document.addEventListener('shown.bs.modal', function(event) {
    // Focus first input in modal
    const modal = event.target;
    const firstInput = modal.querySelector('input:not([type="hidden"]), select, textarea');
    if (firstInput) {
        firstInput.focus();
    }
});

// Price formatting helper
function formatPrice(price) {
    return '$' + parseFloat(price).toFixed(2);
}

// AJAX form submission helper
function submitFormAjax(formElement, successCallback, errorCallback) {
    const formData = new FormData(formElement);
    
    fetch(formElement.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            successCallback(data);
        } else {
            errorCallback(data);
        }
    })
    .catch(error => {
        errorCallback({ error: error.message });
    });
}
