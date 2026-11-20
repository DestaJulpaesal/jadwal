import './calendar';
import './habit-form';
import '@fontsource/figtree/400.css';
import '@fontsource/figtree/500.css';
import '@fontsource/figtree/600.css';
import '@fontsource/figtree/700.css';
import './forms';
import { initializeTaskBulkActions } from './task-bulk-actions';
import { initializeMotion } from './motion';
import { initializeNetworkUi } from './network';
import { initializeNavigation } from './nav';
import { initializePasswordUi } from './password';
import { initializePrivacyUi } from './privacy';
import { initializePwa } from './pwa';
import { initializeReceiptScan } from './receipt/receipt-scan-ui';
import { initializeToastQueue } from './toast';

import './components';

const initializeApplicationUi = () => {
    initializeNavigation();
    initializeTaskBulkActions();
    initializeMotion();
    initializeNetworkUi();
    initializeToastQueue();
    initializePasswordUi();
    initializePrivacyUi();
    initializePwa();
    initializeReceiptScan();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeApplicationUi, { once: true });
} else {
    initializeApplicationUi();
}
