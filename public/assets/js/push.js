import { initializeApp } from 'https://www.gstatic.com/firebasejs/13.0.0/firebase-app.js';
import {
  getMessaging,
  isSupported,
  onMessage,
  onRegistered,
  onUnregistered,
  register as registerMessaging,
  unregister as unregisterMessaging
} from 'https://www.gstatic.com/firebasejs/13.0.0/firebase-messaging.js';

const value = (name) => document.querySelector(`meta[name="${name}"]`)?.content || '';
const config = {
  apiKey: value('firebase-api-key'),
  projectId: value('firebase-project-id'),
  messagingSenderId: value('firebase-sender-id'),
  appId: value('firebase-app-id')
};
const vapidKey = value('firebase-vapid-key');
const csrfToken = value('csrf-token');
const manifestUrl = document.querySelector('link[rel="manifest"]')?.href;
const dialog = document.querySelector('[data-push-dialog]');
const state = document.querySelector('[data-push-state]');
const enableButton = document.querySelector('[data-push-enable]');
const disableButton = document.querySelector('[data-push-disable]');
const navStates = document.querySelectorAll('[data-push-nav-state]');
const destinationStorageKey = 'servicenotify-push-destination';

const setState = (message, enabled = false) => {
  if (state) state.textContent = message;
  navStates.forEach((item) => { item.textContent = enabled ? 'Enabled' : message; });
  if (enableButton) enableButton.hidden = enabled;
  if (disableButton) disableButton.hidden = !enabled;
};

const syncDestination = async (action, destination) => {
  const endpoint = new URL('push-subscription.php', manifestUrl);
  const response = await fetch(endpoint, {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-Token': csrfToken
    },
    body: JSON.stringify({ action, destination })
  });
  const payload = await response.json().catch(() => ({}));
  if (!response.ok) throw new Error(payload.error || 'Push settings could not be saved.');
};

const run = async () => {
  if (!dialog || !manifestUrl || !(await isSupported())) {
    setState('Push is unavailable in this browser.');
    enableButton?.setAttribute('disabled', 'disabled');
    return;
  }

  const registration = await navigator.serviceWorker.ready;
  const messaging = getMessaging(initializeApp(config));
  let currentDestination = localStorage.getItem(destinationStorageKey) || '';

  onRegistered(messaging, async (destination) => {
    try {
      await syncDestination('enroll', destination);
      currentDestination = destination;
      localStorage.setItem(destinationStorageKey, destination);
      setState('Push is enabled on this browser.', true);
    } catch (error) {
      setState(error.message || 'Push registration could not be saved.');
    }
  });

  onUnregistered(messaging, async (destination) => {
    const registeredDestination = destination || currentDestination;
    if (registeredDestination) {
      try { await syncDestination('revoke', registeredDestination); } catch (error) { /* Retry through the explicit disable flow. */ }
    }
    currentDestination = '';
    localStorage.removeItem(destinationStorageKey);
    setState('Push is disabled on this browser.');
  });

  onMessage(messaging, (payload) => {
    const notification = payload.notification || {};
    registration.showNotification(notification.title || 'Service renewal reminder', {
      body: notification.body || 'A tracked service needs your attention.',
      icon: new URL('assets/icons/icon-192.png', manifestUrl).href,
      badge: new URL('assets/icons/icon-192.png', manifestUrl).href,
      data: { url: new URL('dashboard.php', manifestUrl).href }
    });
  });

  enableButton?.addEventListener('click', async () => {
    enableButton.disabled = true;
    setState('Requesting browser permission…');
    try {
      const permission = await Notification.requestPermission();
      if (permission !== 'granted') {
        setState('Permission was not granted.');
        return;
      }
      setState('Registering this browser…');
      await registerMessaging(messaging, { vapidKey, serviceWorkerRegistration: registration });
    } catch (error) {
      setState(error.message || 'Push could not be enabled.');
    } finally {
      enableButton.disabled = false;
    }
  });

  disableButton?.addEventListener('click', async () => {
    disableButton.disabled = true;
    setState('Removing this browser…', true);
    try {
      const destination = currentDestination || localStorage.getItem(destinationStorageKey) || '';
      await unregisterMessaging(messaging);
      if (destination) await syncDestination('revoke', destination);
      currentDestination = '';
      localStorage.removeItem(destinationStorageKey);
      setState('Push is disabled on this browser.');
    } catch (error) {
      setState(error.message || 'Push could not be disabled.', true);
    } finally {
      disableButton.disabled = false;
    }
  });

  if (Notification.permission === 'granted') {
    setState('Confirming this browser…');
    await registerMessaging(messaging, { vapidKey, serviceWorkerRegistration: registration });
  } else if (Notification.permission === 'denied') {
    setState('Push is blocked in browser settings.');
    enableButton?.setAttribute('disabled', 'disabled');
  } else {
    setState('Not enabled');
  }
};

run().catch((error) => setState(error.message || 'Push setup is unavailable.'));
