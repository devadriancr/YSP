import FingerprintJS from '@fingerprintjs/fingerprintjs';
import { detectIncognito } from 'detectincognitojs';

const GREASE_BRAND = /not.?a.?brand/i;

function setField(name, value) {
    document.querySelectorAll(`input[name="${name}"]`).forEach((input) => {
        input.value = value;
    });
}

async function collectClientHints() {
    const uaData = navigator.userAgentData;

    if (!uaData) {
        setField('device_client_platform', navigator.platform || '');
        return;
    }

    let browser = '';
    let platform = uaData.platform || '';

    try {
        const hints = await uaData.getHighEntropyValues(['platformVersion', 'fullVersionList']);
        const brands = (hints.fullVersionList || []).filter((b) => !GREASE_BRAND.test(b.brand));
        const main = brands.find((b) => b.brand !== 'Chromium') || brands[0];

        if (main) {
            browser = `${main.brand} ${main.version}`;
        }

        const major = parseInt(hints.platformVersion, 10);
        if (platform === 'Windows' && !Number.isNaN(major)) {
            platform = major >= 13 ? 'Windows 11' : major >= 1 ? 'Windows 10' : 'Windows 7/8';
        } else if (hints.platformVersion) {
            platform = `${platform} ${hints.platformVersion}`;
        }
    } catch {
        // Fall back to the low-entropy values.
    }

    setField('device_client_browser', browser);
    setField('device_client_platform', platform);
}

async function collectIncognito() {
    try {
        const result = await detectIncognito();
        setField('device_incognito', result.isPrivate ? 'yes' : 'no');
    } catch {
        setField('device_incognito', 'unknown');
    }
}

async function collectFingerprint() {
    try {
        const agent = await FingerprintJS.load();
        const { visitorId } = await agent.get();
        setField('device_id', visitorId);
    } catch {
        // The device id is optional; login continues without it.
    }
}

export async function initDeviceInfo() {
    setField('device_timezone', Intl.DateTimeFormat().resolvedOptions().timeZone || '');
    setField('device_screen', `${window.screen.width}x${window.screen.height}`);
    setField('device_touch', String(navigator.maxTouchPoints || 0));

    await Promise.allSettled([collectClientHints(), collectIncognito(), collectFingerprint()]);
}
