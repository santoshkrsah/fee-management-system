<!-- PWA Meta Tags for Mobile App Support -->
<meta name="application-name" content="Fee Management System">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="FeeMS">
<meta name="mobile-web-app-capable" content="yes">
<meta name="theme-color" content="#6366f1">
<meta name="msapplication-TileColor" content="#6366f1">
<meta name="msapplication-navbutton-color" content="#6366f1">

<!-- PWA Manifest -->
<link rel="manifest" href="/manifest.json">

<!-- App Icons for Different Platforms -->
<link rel="icon" type="image/png" sizes="72x72" href="/assets/images/icon-72x72.png">
<link rel="icon" type="image/png" sizes="96x96" href="/assets/images/icon-96x96.png">
<link rel="icon" type="image/png" sizes="128x128" href="/assets/images/icon-128x128.png">
<link rel="icon" type="image/png" sizes="192x192" href="/assets/images/icon-192x192.png">
<link rel="icon" type="image/png" sizes="512x512" href="/assets/images/icon-512x512.png">

<!-- Apple Touch Icons -->
<link rel="apple-touch-icon" sizes="152x152" href="/assets/images/icon-152x152.png">
<link rel="apple-touch-icon" sizes="180x180" href="/assets/images/icon-192x192.png">

<!-- Microsoft Tile Icons -->
<meta name="msapplication-TileImage" content="/assets/images/icon-144x144.png">

<!-- Splash Screens for iOS (optional) -->
<link rel="apple-touch-startup-image" href="/assets/images/icon-512x512.png">

<!-- Additional Mobile Optimization -->
<meta name="format-detection" content="telephone=no">
<meta name="HandheldFriendly" content="true">
<meta name="MobileOptimized" content="width">

<!-- Service Worker Registration Script -->
<script>
if ('serviceWorker' in navigator) {
  window.addEventListener('load', function() {
    navigator.serviceWorker.register('/sw.js')
      .then(function(registration) {
        console.log('✅ Service Worker registered successfully:', registration.scope);

        // Check for updates every 60 seconds
        setInterval(function() {
          registration.update();
        }, 60000);
      })
      .catch(function(error) {
        console.log('❌ Service Worker registration failed:', error);
      });

    // Listen for service worker updates
    navigator.serviceWorker.addEventListener('controllerchange', function() {
      console.log('🔄 Service Worker updated, reloading page...');
      window.location.reload();
    });
  });

  // Handle install prompt for Android
  let deferredPrompt;
  window.addEventListener('beforeinstallprompt', (e) => {
    // Prevent Chrome 67 and earlier from automatically showing the prompt
    e.preventDefault();
    // Stash the event so it can be triggered later
    deferredPrompt = e;
    console.log('💡 Install prompt available');

    // Show install button/banner if you have one
    // You can customize this part
    showInstallPromotion();
  });

  // Function to show install promotion (customize as needed)
  function showInstallPromotion() {
    // You can create a banner or button here to prompt users to install
    // Example: Show a notification or button
    const installButton = document.getElementById('installButton');
    if (installButton) {
      installButton.style.display = 'block';
      installButton.addEventListener('click', () => {
        deferredPrompt.prompt();
        deferredPrompt.userChoice.then((choiceResult) => {
          if (choiceResult.outcome === 'accepted') {
            console.log('✅ User accepted the install prompt');
          } else {
            console.log('❌ User dismissed the install prompt');
          }
          deferredPrompt = null;
        });
      });
    }
  }

  // Detect if app is running in standalone mode
  if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true) {
    console.log('📱 App running in standalone mode');
    document.body.classList.add('standalone-mode');
  }
}

// Online/Offline detection
window.addEventListener('online', function() {
  console.log('🌐 Back online');
  document.body.classList.remove('offline');
  // You can show a message to user
});

window.addEventListener('offline', function() {
  console.log('📵 Offline');
  document.body.classList.add('offline');
  // You can show a message to user
});
</script>

<!-- Optional: Add offline indicator styles -->
<style>
  .offline-indicator {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    background: #ef4444;
    color: white;
    padding: 0.5rem;
    text-align: center;
    z-index: 10000;
    display: none;
    font-size: 0.875rem;
    font-weight: 600;
  }

  body.offline .offline-indicator {
    display: block;
  }

  .install-prompt {
    position: fixed;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    background: #6366f1;
    color: white;
    padding: 1rem 2rem;
    border-radius: 0.75rem;
    box-shadow: 0 10px 25px rgba(0,0,0,0.3);
    z-index: 9999;
    display: none;
    animation: slideUp 0.3s ease-out;
  }

  @keyframes slideUp {
    from {
      transform: translateX(-50%) translateY(100px);
      opacity: 0;
    }
    to {
      transform: translateX(-50%) translateY(0);
      opacity: 1;
    }
  }

  .install-prompt button {
    margin-left: 1rem;
    padding: 0.5rem 1rem;
    background: white;
    color: #6366f1;
    border: none;
    border-radius: 0.5rem;
    font-weight: 600;
    cursor: pointer;
  }
</style>

<!-- Optional: Add offline indicator in body -->
<div class="offline-indicator">
  <i class="fas fa-wifi-slash"></i> You're offline. Some features may not be available.
</div>
