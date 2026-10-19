document.querySelectorAll('[data-password-toggle]').forEach((button) => {
  button.addEventListener('click', () => {
    const input = document.getElementById(button.dataset.passwordToggle);

    if (!input) return;

    const shouldShow = input.type === 'password';
    input.type = shouldShow ? 'text' : 'password';
    button.setAttribute('aria-pressed', shouldShow ? 'true' : 'false');
    button.setAttribute('aria-label', shouldShow ? 'Hide password' : 'Show password');
  });
});

document.querySelectorAll('[data-reminder-card]').forEach((card) => {
  const enable = card.querySelector('[data-reminder-enable]');
  const channels = card.querySelectorAll('[data-reminder-channel]');

  if (!enable) return;

  const sync = () => {
    card.classList.toggle('reminder-option--enabled', enable.checked);
    channels.forEach((channel) => {
      channel.disabled = !enable.checked;
    });
  };

  enable.addEventListener('change', sync);
  sync();
});

const manifestLink = document.querySelector('link[rel="manifest"]');
const pwaMode = document.querySelector('meta[name="service-notify-pwa"]')?.content;

if ('serviceWorker' in navigator && manifestLink) {
  const serviceWorkerUrl = new URL('sw.js', manifestLink.href);
  window.addEventListener('load', async () => {
    try {
      const registration = await navigator.serviceWorker.register(serviceWorkerUrl, {
        scope: new URL('.', manifestLink.href).pathname
      });

      if (pwaMode === 'clear-private') {
        const worker = registration.active || registration.waiting || registration.installing;
        worker?.postMessage({ type: 'CLEAR_PRIVATE_CACHES' });
      }
    } catch (error) {
      // The application remains fully usable when service-worker registration is unavailable.
    }
  });
}

document.querySelectorAll('form[action$="logout.php"]').forEach((form) => {
  form.addEventListener('submit', () => {
    navigator.serviceWorker?.controller?.postMessage({ type: 'CLEAR_PRIVATE_CACHES' });
  });
});

const connectionBanner = document.querySelector('[data-connection-banner]');
const syncConnectionState = () => {
  if (!connectionBanner) return;
  connectionBanner.hidden = navigator.onLine;
};
window.addEventListener('online', syncConnectionState);
window.addEventListener('offline', syncConnectionState);
syncConnectionState();

let pendingInstallPrompt = null;
const installPanel = document.querySelector('[data-pwa-install]');
const installAction = document.querySelector('[data-pwa-install-action]');
const installDismiss = document.querySelector('[data-pwa-install-dismiss]');

window.addEventListener('beforeinstallprompt', (event) => {
  event.preventDefault();
  pendingInstallPrompt = event;
  if (installPanel && sessionStorage.getItem('servicenotify-install-dismissed') !== 'yes') {
    installPanel.hidden = false;
  }
});

installAction?.addEventListener('click', async () => {
  if (!pendingInstallPrompt) return;
  await pendingInstallPrompt.prompt();
  pendingInstallPrompt = null;
  if (installPanel) installPanel.hidden = true;
});

installDismiss?.addEventListener('click', () => {
  sessionStorage.setItem('servicenotify-install-dismissed', 'yes');
  if (installPanel) installPanel.hidden = true;
});

window.addEventListener('appinstalled', () => {
  pendingInstallPrompt = null;
  if (installPanel) installPanel.hidden = true;
});

const pushDialog = document.querySelector('[data-push-dialog]');
document.querySelectorAll('[data-push-open]').forEach((button) => {
  button.addEventListener('click', () => pushDialog?.showModal());
});
