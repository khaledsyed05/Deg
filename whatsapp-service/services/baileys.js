import makeWASocket, { DisconnectReason, useMultiFileAuthState, fetchLatestBaileysVersion } from '@whiskeysockets/baileys'
import { Boom } from '@hapi/boom'
import pino from 'pino'

const sessions = new Map()

export async function createSession(key) {
    const sessionKey = String(key)
    if (sessions.has(sessionKey)) {
        return sessions.get(sessionKey)
    }

    const { state, saveCreds } = await useMultiFileAuthState(`./storage/sessions/session_${sessionKey}`)
    const { version, isLatest } = await fetchLatestBaileysVersion()
    console.log(`[${sessionKey}] using WA v${version.join('.')} (latest: ${isLatest})`)

    const sock = makeWASocket({
        version,
        auth: state,
        printQRInTerminal: false,
        logger: pino({ level: 'warn' }),
        browser: ['Daq Ehjezly', 'Chrome', '1.0.0'],
    })

    console.log(`[${sessionKey}] socket created`)

    let qrCode = null

    sock.ev.on('creds.update', saveCreds)

    sock.ev.on('connection.update', (update) => {
        const { connection, lastDisconnect, qr } = update
        console.log(`[${sessionKey}] connection.update`, { connection, hasQR: !!qr, err: lastDisconnect?.error?.message })

        if (qr) {
            qrCode = qr
            if (sessions.has(sessionKey)) {
                sessions.get(sessionKey).qrCode = qr
            }
        }

        if (connection === 'close') {
            const shouldReconnect =
                (lastDisconnect?.error instanceof Boom) &&
                lastDisconnect.error.output.statusCode !== DisconnectReason.loggedOut

            if (shouldReconnect) {
                sessions.delete(sessionKey)
                createSession(sessionKey)
            } else {
                sessions.delete(sessionKey)
            }
        } else if (connection === 'open') {
            console.log(`WhatsApp connected for ${sessionKey}`)
            if (sessions.has(sessionKey)) {
                sessions.get(sessionKey).connected = true
                sessions.get(sessionKey).qrCode = null
            }
        }
    })

    const session = { sock, qrCode, connected: false, getQR: () => qrCode }
    sessions.set(sessionKey, session)

    return session
}

export function getSession(key) {
    return sessions.get(String(key)) ?? null
}

export async function sendMessage(key, phoneNumber, message) {
    const session = getSession(key)

    if (!session || !session.connected) {
        throw new Error(`No active WhatsApp session for ${key}`)
    }

    const formattedNumber = phoneNumber.replace(/[^0-9]/g, '') + '@s.whatsapp.net'

    await session.sock.sendMessage(formattedNumber, { text: message })

    return { success: true }
}

export async function checkNumber(key, phoneNumber) {
    const session = getSession(key)

    if (!session || !session.connected) {
        throw new Error(`No active WhatsApp session for ${key}`)
    }

    const jid = phoneNumber.replace(/[^0-9]/g, '') + '@s.whatsapp.net'
    const [result] = await session.sock.onWhatsApp(jid)

    return { has_whatsapp: !!result?.exists, jid: result?.jid ?? null }
}

export function disconnectSession(key) {
    const sessionKey = String(key)
    const session = sessions.get(sessionKey)
    if (session?.sock) {
        session.sock.end()
    }
    sessions.delete(sessionKey)
}
