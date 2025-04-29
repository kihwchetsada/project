/**
 * Enhanced Registration Form JavaScript
 * Features:
 * - Form validation with visual feedback
 * - File upload handling with size validation
 * - Modal dialog management
 * - Responsive interactions
 */

document.addEventListener('DOMContentLoaded', function() {
    // Cache DOM elements for better performance
    const form = document.getElementById('registerForm');
    const agreeCheckbox = document.getElementById('agree_terms');
    const modal = document.getElementById('termsModal');
    const modalOpenButtons = document.querySelectorAll('[data-target="#termsModal"]');
    const modalCloseButtons = document.querySelectorAll('[data-dismiss="modal"]');
    const acceptButton = document.querySelector('[data-accept="terms"]');
    
    // Initialize file input handlers
    initFileInputs();
    
    // Modal management
    initModal();
    
    // Form validation
    initFormValidation();
    
    // Handle form submission
    if (form) {
        form.addEventListener('submit', function(event) {
            if (!validateForm(this)) {
                event.preventDefault();
                event.stopPropagation();
                showFormErrors();
            }
        });
    }
    
    /**
     * Initialize file input handlers for each member
     */
    function initFileInputs() {
        for (let i = 1; i <= 8; i++) {
            const fileInput = document.getElementById(`member_id_card_${i}`);
            const fileNameSpan = document.getElementById(`file_name_${i}`);
            
            if (fileInput && fileNameSpan) {
                fileInput.addEventListener('change', function() {
                    handleFileSelect(this, fileNameSpan);
                });
            }
        }
    }
    
    /**
     * Handle file selection and validation
     */
    function handleFileSelect(fileInput, fileNameSpan) {
        if (fileInput.files.length > 0) {
            const file = fileInput.files[0];
            const fileName = file.name;
            const fileSize = Math.round(file.size / 1024); // KB
            
            // Validate file size (5MB max)
            if (file.size > 5 * 1024 * 1024) {
                showFileSizeError(fileInput, fileNameSpan);
                return;
            }
            
            // Validate file type
            const fileExt = fileName.split('.').pop().toLowerCase();
            if (!['jpg', 'jpeg', 'png', 'gif'].includes(fileExt)) {
                showFileTypeError(fileInput, fileNameSpan);
                return;
            }
            
            // Show file info
            fileNameSpan.textContent = `${fileName} (${fileSize} KB)`;
            fileNameSpan.classList.add('file-selected');
        } else {
            fileNameSpan.textContent = 'ยังไม่ได้เลือกไฟล์';
            fileNameSpan.classList.remove('file-selected');
        }
    }
    
    /**
     * Show file size error
     */
    function showFileSizeError(fileInput, fileNameSpan) {
        fileInput.value = ''; // Clear input
        fileNameSpan.textContent = 'ยังไม่ได้เลือกไฟล์';
        fileNameSpan.classList.remove('file-selected');
        showAlert('ไฟล์มีขนาดใหญ่เกินไป (เกิน 5MB)', 'error');
    }
    
    /**
     * Show file type error
     */
    function showFileTypeError(fileInput, fileNameSpan) {
        fileInput.value = ''; // Clear input
        fileNameSpan.textContent = 'ยังไม่ได้เลือกไฟล์';
        fileNameSpan.classList.remove('file-selected');
        showAlert('ประเภทไฟล์ไม่ถูกต้อง (รองรับเฉพาะ .jpg, .png, .gif)', 'error');
    }
    
    /**
     * Initialize modal dialog
     */
    function initModal() {
        if (!modal) return;
        
        // Open modal
        modalOpenButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden'; // Prevent background scrolling
            });
        });
        
        // Close modal
        modalCloseButtons.forEach(button => {
            button.addEventListener('click', closeModal);
        });
        
        // Accept terms
        if (acceptButton && agreeCheckbox) {
            acceptButton.addEventListener('click', function() {
                agreeCheckbox.checked = true;
                closeModal();
            });
        }
        
        // Close when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target == modal) {
                closeModal();
            }
        });
        
        // Close on Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && modal.style.display === 'flex') {
                closeModal();
            }
        });
    }
    
    /**
     * Close modal dialog
     */
    function closeModal() {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto'; // Restore scrolling
    }
    
    /**
     * Initialize form validation
     */
    function initFormValidation() {
        // Add visual feedback on input fields
        const inputs = document.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateInput(this);
            });
            
            // Clear visual error when user starts typing again
            input.addEventListener('input', function() {
                if (this.classList.contains('is-invalid')) {
                    this.classList.remove('is-invalid');
                    const feedback = this.parentNode.querySelector('.invalid-feedback');
                    if (feedback) {
                        feedback.style.display = 'none';
                    }
                }
            });
        });
    }
    
    /**
     * Validate a single input field
     */
    function validateInput(input) {
        if (input.hasAttribute('required') && !input.value.trim()) {
            markInvalid(input);
            return false;
        }
        
        if (input.type === 'tel' && input.value.trim()) {
            if (!validatePhone(input.value)) {
                markInvalid(input);
                return false;
            }
        }
        
        if (input.type === 'number' && input.value.trim()) {
            const val = Number(input.value);
            if (input.hasAttribute('min') && val < Number(input.getAttribute('min'))) {
                markInvalid(input);
                return false;
            }
            if (input.hasAttribute('max') && val > Number(input.getAttribute('max'))) {
                markInvalid(input);
                return false;
            }
        }
        
        input.classList.remove('is-invalid');
        return true;
    }
    
    /**
     * Mark an input as invalid with visual feedback
     */
    function markInvalid(input) {
        input.classList.add('is-invalid');
        const feedback = input.parentNode.querySelector('.invalid-feedback');
        if (feedback) {
            feedback.style.display = 'block';
        }
    }
    
    /**
     * Validate phone number format (10 digits)
     */
    function validatePhone(phone) {
        return /^[0-9]{10}$/.test(phone);
    }
    
    /**
     * Validate the entire form before submission
     */
    function validateForm(form) {
        let isValid = true;
        
        // Validate required fields
        const requiredInputs = form.querySelectorAll('[required]');
        requiredInputs.forEach(input => {
            if (!validateInput(input)) {
                isValid = false;
            }
        });
        
        // Check if enough team members are filled in
        const requiredMemberCount = 5;
        let validMembers = 0;
        
        for (let i = 1; i <= 5; i++) {
            const memberName = document.getElementById(`member_name_${i}`);
            const memberIdCard = document.getElementById(`member_id_card_${i}`);
            
            if (memberName && memberName.value.trim() !== '' && 
                memberIdCard && memberIdCard.files.length > 0) {
                validMembers++;
            }
        }
        
        if (validMembers < requiredMemberCount) {
            showAlert(`กรุณากรอกข้อมูลสมาชิกและอัปโหลดบัตรประชาชน/นักเรียนอย่างน้อย ${requiredMemberCount} คน`, 'error');
            isValid = false;
        }
        
        // Check terms agreement
        if (agreeCheckbox && !agreeCheckbox.checked) {
            markInvalid(agreeCheckbox);
            isValid = false;
        }
        
        return isValid;
    }
    
    /**
     * Show form validation errors with smooth scroll
     */
    function showFormErrors() {
        const firstError = document.querySelector('.is-invalid');
        if (firstError) {
            // Smooth scroll to first error
            window.scrollTo({
                top: firstError.getBoundingClientRect().top + window.pageYOffset - 100,
                behavior: 'smooth'
            });
            
            // Focus on the first error field
            setTimeout(() => {
                firstError.focus();
            }, 500);
        }
    }
    
    /**
     * Show alert message
     */
    function showAlert(message, type = 'error') {
        // Remove any existing alerts
        const existingAlerts = document.querySelectorAll('.alert-js');
        existingAlerts.forEach(alert => {
            alert.remove();
        });
        
        // Create new alert
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-js`;
        
        const icon = document.createElement('i');
        icon.className = `fas fa-${type === 'error' ? 'exclamation-circle' : 'check-circle'}`;
        alertDiv.appendChild(icon);
        
        const text = document.createTextNode(` ${message}`);
        alertDiv.appendChild(text);
        
        // Find insertion point
        const container = document.querySelector('.container');
        const formContainer = document.querySelector('.form-container');
        
        if (container && formContainer) {
            container.insertBefore(alertDiv, formContainer);
            
            // Scroll to alert
            window.scrollTo({
                top: alertDiv.getBoundingClientRect().top + window.pageYOffset - 100,
                behavior: 'smooth'
            });
            
            // Auto-dismiss after 5 seconds for success messages
            if (type === 'success') {
                setTimeout(() => {
                    alertDiv.style.opacity = '0';
                    setTimeout(() => {
                        alertDiv.remove();
                    }, 300);
                }, 5000);
            }
        }
    }
});