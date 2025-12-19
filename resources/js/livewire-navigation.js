/**
 * Livewire Navigation Helper
 * 
 * Handles Livewire wire:navigate events and provides a smooth SPA-like experience
 * with a progress bar and proper JavaScript initialization.
 */

// Create and inject progress bar
const createProgressBar = () => {
    const bar = document.createElement('div');
    bar.id = 'livewire-progress-bar';
    bar.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        height: 3px;
        background: linear-gradient(to right, #6366f1, #8b5cf6, #d946ef);
        z-index: 99999;
        transition: width 300ms ease-out, opacity 300ms ease-in;
        width: 0;
        opacity: 0;
        box-shadow: 0 0 10px rgba(139, 92, 246, 0.6);
    `;
    document.body.appendChild(bar);
    return bar;
};

let progressBar = null;
let progressInterval = null;

// Initialize progress bar
const initProgressBar = () => {
    if (!progressBar) {
        progressBar = createProgressBar();
    }
};

// Show progress bar with smooth animation
const showProgress = () => {
    initProgressBar();
    progressBar.style.opacity = '1';
    progressBar.style.width = '0%';
    
    let progress = 0;
    progressInterval = setInterval(() => {
        progress += Math.random() * 10;
        if (progress > 90) {
            progress = 90;
            clearInterval(progressInterval);
        }
        progressBar.style.width = progress + '%';
    }, 200);
};

// Complete progress bar animation
const completeProgress = () => {
    if (progressBar) {
        clearInterval(progressInterval);
        progressBar.style.width = '100%';
        setTimeout(() => {
            progressBar.style.opacity = '0';
            setTimeout(() => {
                progressBar.style.width = '0%';
            }, 300);
        }, 100);
    }
};

// Initialize app-wide JavaScript
const initializeApp = () => {
    console.log('🚀 Avinash-EYE: App initialized');
    
    // Example: Re-initialize any third-party libraries here
    // initTooltips();
    // initModals();
    
    // Dispatch custom event for other scripts
    window.dispatchEvent(new CustomEvent('app:initialized'));
};

// Livewire Navigation Events
document.addEventListener('livewire:navigating', () => {
    console.log('🔄 Livewire: Navigation started');
    showProgress();
    
    // Close any open dropdowns/modals before navigation
    window.dispatchEvent(new CustomEvent('app:before-navigate'));
});

document.addEventListener('livewire:navigated', () => {
    console.log('✅ Livewire: Navigation completed');
    completeProgress();
    
    // Re-initialize app after navigation
    initializeApp();
    
    // Scroll to top smoothly
    window.scrollTo({ top: 0, behavior: 'smooth' });
});

// Handle navigation failures
document.addEventListener('livewire:navigate-error', (event) => {
    console.error('❌ Livewire: Navigation error', event.detail);
    completeProgress();
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    initializeApp();
});

// Handle Livewire component lifecycle
document.addEventListener('livewire:init', () => {
    console.log('⚡ Livewire: Framework initialized');
    
    // Hook into Livewire component lifecycle
    Livewire.hook('commit', ({ component, commit, respond, succeed, fail }) => {
        // Show loading indicator for long-running operations
        succeed(({ snapshot, effect }) => {
            // Component updated successfully
        });
        
        fail(() => {
            console.error('Component update failed');
        });
    });
});

// Global helper for custom scripts
window.AvinashEYE = {
    // Re-initialize function for custom scripts
    onNavigate: (callback) => {
        document.addEventListener('livewire:navigated', callback);
        // Run immediately if already loaded
        if (document.readyState === 'complete') {
            callback();
        }
    },
    
    // Execute code on every page load (including navigations)
    ready: (callback) => {
        // Run on initial load
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
        } else {
            callback();
        }
        
        // Run on Livewire navigations
        document.addEventListener('livewire:navigated', callback);
    }
};

// Export for use in other modules
export { initializeApp, showProgress, completeProgress };
