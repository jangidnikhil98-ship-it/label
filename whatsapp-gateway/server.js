const express = require('express');
const cors = require('cors');
const QRCode = require('qrcode');
const axios = require('axios');
const path = require('path');
const fs = require('fs');
const pino = require('pino');
const {
    default: makeWASocket,
    useMultiFileAuthState,
    DisconnectReason,
    fetchLatestBaileysVersion,
    downloadMediaMessage
} = require('@whiskeysockets/baileys');

const app = express();
app.use(cors());
app.use(express.json({ limit: '50mb' }));

// Prevent unhandled network promise rejections (e.g. Baileys websocket timeouts) from crashing the gateway
process.on('unhandledRejection', (reason, promise) => {
    console.error('Handled unhandledRejection:', reason?.message || reason);
});

process.on('uncaughtException', (err) => {
    console.error('Handled uncaughtException:', err?.message || err);
});

const PORT = process.env.PORT || 3000;
const LARAVEL_WEBHOOK_URL = process.env.LARAVEL_WEBHOOK_URL || 'http://127.0.0.1:8000/api/v1/whatsapp/webhook';
const LARAVEL_BATCH_WEBHOOK_URL = LARAVEL_WEBHOOK_URL.replace(/\/webhook\/?$/, '/webhook/batch');
const AUTH_FOLDER = path.join(__dirname, '../storage/app/whatsapp_session');
const STORE_FILE = path.join(__dirname, '../storage/app/whatsapp_messages_store.json');

if (!fs.existsSync(AUTH_FOLDER)) {
    fs.mkdirSync(AUTH_FOLDER, { recursive: true });
}

let sock = null;
let qrCodeData = null;
let connectionStatus = 'DISCONNECTED'; // DISCONNECTED, SCAN_QR, CONNECTED
let connectedUser = null;
const messageStore = new Map();

// Helper to load persisted messages from disk
function loadStoreFromDisk() {
    try {
        if (fs.existsSync(STORE_FILE)) {
            const data = fs.readFileSync(STORE_FILE, 'utf8');
            const parsed = JSON.parse(data);
            if (Array.isArray(parsed)) {
                for (const item of parsed) {
                    if (item && item.key && item.key.id) {
                        messageStore.set(item.key.id, item);
                    }
                }
                console.log(`Loaded ${messageStore.size} messages from disk cache.`);
            }
        }
    } catch (err) {
        console.error('Failed to load message store from disk:', err.message);
    }
}

// Debounced save of store to disk
let saveTimeout = null;
function saveStoreToDisk() {
    if (saveTimeout) clearTimeout(saveTimeout);
    saveTimeout = setTimeout(() => {
        try {
            const arr = Array.from(messageStore.values()).slice(-2000); // keep up to recent 2000 messages
            fs.writeFileSync(STORE_FILE, JSON.stringify(arr), 'utf8');
        } catch (err) {
            console.error('Failed to save message store to disk:', err.message);
        }
    }, 1500);
}

loadStoreFromDisk();

function extractMessageText(msg) {
    if (!msg || !msg.message) return '';
    return (
        msg.message.conversation ||
        msg.message.extendedTextMessage?.text ||
        msg.message.imageMessage?.caption ||
        msg.message.documentMessage?.caption ||
        msg.message.videoMessage?.caption ||
        ''
    );
}

function getMessageTimestamp(msg) {
    if (!msg) return 0;
    let ts = msg.messageTimestamp;
    if (typeof ts === 'object' && ts !== null) {
        ts = ts.low !== undefined ? ts.low : Number(ts);
    }
    const num = Number(ts) || 0;
    return num > 1000000000000 ? Math.floor(num / 1000) : num;
}

async function downloadAndSaveImage(msg) {
    if (!msg || !msg.message) return null;
    const isImage = !!(msg.message.imageMessage || (msg.message.documentMessage && msg.message.documentMessage.mimetype?.startsWith('image/')));
    if (!isImage) return null;

    try {
        const buffer = await downloadMediaMessage(
            msg,
            'buffer',
            {},
            { logger: pino({ level: 'silent' }) }
        );
        if (!buffer) return null;

        const imagesDir = path.join(__dirname, '../storage/app/public/whatsapp_images');
        if (!fs.existsSync(imagesDir)) fs.mkdirSync(imagesDir, { recursive: true });

        const fileName = `wa_${msg.key.id || Date.now()}.jpg`;
        const fullImgPath = path.join(imagesDir, fileName);
        fs.writeFileSync(fullImgPath, buffer);
        console.log(`Downloaded WhatsApp image: ${fileName}`);
        return `whatsapp_images/${fileName}`;
    } catch (err) {
        console.error(`Failed to download image for message ${msg.key?.id}:`, err.message);
        return null;
    }
}

async function forwardMessageToLaravel(msg) {
    try {
        if (!msg.message) return null;

        const senderPhone = msg.key.remoteJid ? msg.key.remoteJid.split('@')[0] : '';
        const pushName = msg.pushName || 'Customer';
        const textContent = extractMessageText(msg);

        // Download image if message contains an image attachment
        let imagePath = null;
        if (msg.message.imageMessage || (msg.message.documentMessage && msg.message.documentMessage.mimetype?.startsWith('image/'))) {
            imagePath = await downloadAndSaveImage(msg);
        }

        if (!textContent && !imagePath) return null;

        const ts = getMessageTimestamp(msg);

        const response = await axios.post(LARAVEL_WEBHOOK_URL, {
            event: 'message_received',
            message_id: msg.key.id,
            sender_phone: senderPhone,
            sender_name: pushName,
            message_text: textContent || '[Image Attachment]',
            image_path: imagePath,
            timestamp: ts,
            raw_payload: msg
        }, { timeout: 15000 });

        return response.data;
    } catch (err) {
        console.error('Error forwarding message to Laravel Webhook:', err.message);
        return null;
    }
}

async function forwardMessagesBatchToLaravel(messagesList) {
    if (!messagesList || messagesList.length === 0) {
        return { success: true, orders_created: 0, duplicates_skipped: 0 };
    }

    const CHUNK_SIZE = 50;
    let totalCreated = 0;
    let totalSkipped = 0;

    for (let i = 0; i < messagesList.length; i += CHUNK_SIZE) {
        const chunk = messagesList.slice(i, i + CHUNK_SIZE);
        const payload = {
            messages: chunk.map(msg => {
                const senderPhone = msg.key.remoteJid ? msg.key.remoteJid.split('@')[0] : '';
                const pushName = msg.pushName || 'Customer';
                const text = extractMessageText(msg);
                const ts = getMessageTimestamp(msg);
                return {
                    message_id: msg.key.id,
                    sender_phone: senderPhone,
                    sender_name: pushName,
                    message_text: text,
                    timestamp: ts,
                };
            }).filter(item => item.message_text.trim().length > 0)
        };

        if (payload.messages.length === 0) continue;

        try {
            const res = await axios.post(LARAVEL_BATCH_WEBHOOK_URL, payload, { timeout: 45000 });
            if (res.data?.summary) {
                totalCreated += res.data.summary.orders_created || 0;
                totalSkipped += res.data.summary.duplicates_skipped || 0;
            }
        } catch (err) {
            console.error('Batch webhook error, falling back to sequential forwarding:', err.message);
            for (const msg of chunk) {
                const res = await forwardMessageToLaravel(msg);
                if (res && res.order_detected) {
                    if (res.is_duplicate) totalSkipped++;
                    else totalCreated++;
                }
            }
        }
    }

    return { success: true, orders_created: totalCreated, duplicates_skipped: totalSkipped };
}

async function startWhatsAppGateway() {
    const { state, saveCreds } = await useMultiFileAuthState(AUTH_FOLDER);
    const { version } = await fetchLatestBaileysVersion();

    sock = makeWASocket({
        version,
        auth: state,
        logger: pino({ level: 'silent' }),
        printQRInTerminal: false,
        syncFullHistory: true, // Request full history from WhatsApp Web sync
        shouldSyncHistoryMessage: () => true,
    });

    sock.ev.on('creds.update', saveCreds);

    sock.ev.on('connection.update', async (update) => {
        const { connection, lastDisconnect, qr } = update;

        if (qr) {
            connectionStatus = 'SCAN_QR';
            try {
                qrCodeData = await QRCode.toDataURL(qr);
            } catch (err) {
                console.error('Failed to generate QR code data URL:', err);
            }
        }

        if (connection === 'close') {
            const statusCode = lastDisconnect?.error?.output?.statusCode;
            const shouldReconnect = statusCode !== DisconnectReason.loggedOut;

            connectionStatus = 'DISCONNECTED';
            qrCodeData = null;
            connectedUser = null;

            console.log(`Connection closed due to ${lastDisconnect?.error}, reconnecting: ${shouldReconnect}`);

            if (shouldReconnect) {
                setTimeout(startWhatsAppGateway, 3000);
            } else {
                if (fs.existsSync(AUTH_FOLDER)) {
                    fs.rmSync(AUTH_FOLDER, { recursive: true, force: true });
                }
            }
        } else if (connection === 'open') {
            connectionStatus = 'CONNECTED';
            qrCodeData = null;
            connectedUser = sock.user ? sock.user.id.split(':')[0] : 'Connected User';
            console.log('WhatsApp Web Gateway Connected successfully! User:', connectedUser);
        }
    });

    // History sync listener: triggered when WhatsApp syncs chat history upon connection
    sock.ev.on('messaging-history.set', async ({ chats, contacts, messages, isLatest, syncType }) => {
        if (messages && messages.length) {
            const nowSec = Math.floor(Date.now() / 1000);
            const threeDaysAgoSec = nowSec - (3 * 24 * 60 * 60);
            const recentMessagesToProcess = [];

            for (const msg of messages) {
                if (!msg || !msg.key || !msg.key.id) continue;
                messageStore.set(msg.key.id, msg);

                // Ignore messages sent by self or broadcast status
                if (msg.key.fromMe || msg.key.remoteJid?.includes('status@broadcast')) continue;

                const text = extractMessageText(msg);
                if (!text) continue;

                const ts = getMessageTimestamp(msg);
                // Check if message is within the last 3 days
                if (ts >= threeDaysAgoSec) {
                    recentMessagesToProcess.push(msg);
                }
            }

            saveStoreToDisk();

            console.log(`Received ${messages.length} historical messages from WhatsApp. Found ${recentMessagesToProcess.length} messages within last 3 days.`);

            if (recentMessagesToProcess.length > 0) {
                try {
                    const result = await forwardMessagesBatchToLaravel(recentMessagesToProcess);
                    console.log(`Extracted last 3 days orders on connect: Created ${result.orders_created} order(s), skipped ${result.duplicates_skipped} duplicate(s).`);
                } catch (e) {
                    console.error('Error forwarding 3-day history to Laravel:', e.message);
                }
            }
        }
    });

    // Real-time incoming new messages listener
    sock.ev.on('messages.upsert', async (m) => {
        try {
            if (m.type === 'notify' || m.type === 'append') {
                for (const msg of m.messages) {
                    if (!msg.message) continue;
                    if (msg.key && msg.key.id) {
                        messageStore.set(msg.key.id, msg);
                    }
                    if (!msg.key.fromMe && !msg.key.remoteJid?.includes('status@broadcast')) {
                        await forwardMessageToLaravel(msg);
                    }
                }
                saveStoreToDisk();
            }
        } catch (err) {
            console.error('Error processing messages.upsert:', err);
        }
    });
}

// API Routes
app.get('/status', (req, res) => {
    res.json({
        success: true,
        status: connectionStatus,
        qr: qrCodeData,
        user: connectedUser,
        stored_messages: messageStore.size,
        timestamp: new Date().toISOString()
    });
});

app.get('/qr', (req, res) => {
    res.json({
        success: true,
        status: connectionStatus,
        qr: qrCodeData
    });
});

app.post('/disconnect', async (req, res) => {
    try {
        if (sock) {
            await sock.logout().catch(() => {});
        }
        if (fs.existsSync(AUTH_FOLDER)) {
            fs.rmSync(AUTH_FOLDER, { recursive: true, force: true });
        }
        connectionStatus = 'DISCONNECTED';
        qrCodeData = null;
        connectedUser = null;
        messageStore.clear();
        setTimeout(startWhatsAppGateway, 1000);
        res.json({ success: true, message: 'WhatsApp Gateway session disconnected.' });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

app.post('/sync', async (req, res) => {
    try {
        const days = parseInt(req.body.days || 3, 10);
        const nowSec = Math.floor(Date.now() / 1000);
        const cutoffSec = nowSec - (days * 24 * 60 * 60);

        console.log(`Starting manual WhatsApp sync for past ${days} day(s) (cutoff: ${cutoffSec})...`);

        const messagesToSync = [];

        for (const [id, msg] of messageStore.entries()) {
            if (!msg || !msg.message) continue;
            if (msg.key?.fromMe || msg.key?.remoteJid?.includes('status@broadcast')) continue;

            const ts = getMessageTimestamp(msg);
            // If ts is within requested days or zero
            if (ts === 0 || ts >= cutoffSec) {
                const text = extractMessageText(msg);
                const hasImage = !!(msg.message.imageMessage || (msg.message.documentMessage && msg.message.documentMessage.mimetype?.startsWith('image/')));
                if ((text && text.trim().length > 0) || hasImage) {
                    messagesToSync.push(msg);
                }
            }
        }

        console.log(`Found ${messagesToSync.length} eligible messages to sync.`);

        const formattedMessages = [];
        for (const msg of messagesToSync) {
            const senderPhone = msg.key.remoteJid ? msg.key.remoteJid.split('@')[0] : '';
            const pushName = msg.pushName || 'Customer';
            const text = extractMessageText(msg);
            const ts = getMessageTimestamp(msg);

            let imagePath = null;
            if (msg.message.imageMessage || (msg.message.documentMessage && msg.message.documentMessage.mimetype?.startsWith('image/'))) {
                imagePath = await downloadAndSaveImage(msg);
            }

            if (text.trim().length > 0 || imagePath) {
                formattedMessages.push({
                    message_id: msg.key.id,
                    sender_phone: senderPhone,
                    sender_name: pushName,
                    message_text: text || '[Image Attachment]',
                    image_path: imagePath,
                    timestamp: ts,
                });
            }
        }

        res.json({
            success: true,
            days: days,
            processed_messages: formattedMessages.length,
            messages: formattedMessages
        });
    } catch (err) {
        console.error('Error during message sync:', err);
        res.status(500).json({ success: false, message: err.message });
    }
});

app.listen(PORT, () => {
    console.log(`WhatsApp Web Gateway listening on http://127.0.0.1:${PORT}`);
    startWhatsAppGateway();
});
