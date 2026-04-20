import makeWASocket, { DisconnectReason, useMultiFileAuthState } from '@whiskeysockets/baileys'
import { Boom } from '@hapi/boom'
import pino from 'pino'

const sessions = new Map()

export async function createSession(clubId) {
    if (sessions.has(clubId)) {
        return sessions.get(clubId)
    }

    const { state, saveCreds } = await useMultiFileAuthState(`./storage/sessions/club_${clubId}`)

    const sock = makeWASocket({
        auth: state,
        printQRInTerminal: false,
        logger: pino({ level: 'silent' }),
    })

    let qrCode = null

    sock.ev.on('creds.update', saveCreds)

    sock.ev.on('connection.update', (update) => {
        const { connection, lastDisconnect, qr } = update

        if (qr) {
            qrCode = qr
            // Update the stored session with the new QR
            if (sessions.has(clubId)) {
                sessions.get(clubId).qrCode = qr
            }
        }

        if (connection === 'close') {
            const shouldReconnect =
                (lastDisconnect?.error instanceof Boom) &&
                lastDisconnect.error.output.statusCode !== DisconnectReason.loggedOut

            if (shouldReconnect) {
                sessions.delete(clubId)
                createSession(clubId)
            } else {
                sessions.delete(clubId)
            }
        } else if (connection === 'open') {
            console.log(`WhatsApp connected for club ${clubId}`)
            if (sessions.has(clubId)) {
                sessions.get(clubId).connected = true
                sessions.get(clubId).qrCode = null
            }
        }
    })

    const session = { sock, qrCode, connected: false, getQR: () => qrCode }
    sessions.set(clubId, session)

    return session
}

export function getSession(clubId) {
    return sessions.get(clubId) ?? null
}

export async function sendMessage(clubId, phoneNumber, message) {
    const session = getSession(clubId)

    if (!session || !session.connected) {
        throw new Error(`No active WhatsApp session for club ${clubId}`)
    }

    // +963944123456 → 963944123456@s.whatsapp.net
    const formattedNumber = phoneNumber.replace(/[^0-9]/g, '') + '@s.whatsapp.net'

    await session.sock.sendMessage(formattedNumber, { text: message })

    return { success: true }
}

export function disconnectSession(clubId) {
    const session = sessions.get(clubId)
    if (session?.sock) {
        session.sock.end()
    }
    sessions.delete(clubId)
}
