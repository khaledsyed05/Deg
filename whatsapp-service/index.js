import express from 'express'
import QRCode from 'qrcode'
import { createSession, getSession, sendMessage, disconnectSession } from './services/baileys.js'

const app = express()
app.use(express.json())

// Simple API key middleware
app.use((req, res, next) => {
    const key = req.headers['x-api-key']
    const expected = process.env.WHATSAPP_API_KEY

    if (expected && key !== expected) {
        return res.status(401).json({ error: 'Unauthorized' })
    }

    next()
})

// Initiate or return existing session
app.post('/sessions/:clubId/init', async (req, res) => {
    try {
        const clubId = Number(req.params.clubId)
        const session = await createSession(clubId)

        if (session.connected) {
            return res.json({ status: 'connected' })
        }

        if (session.qrCode) {
            const qrImage = await QRCode.toDataURL(session.qrCode)
            return res.json({ qr_code: qrImage, status: 'pending' })
        }

        res.json({ status: 'initializing' })
    } catch (error) {
        console.error(error)
        res.status(500).json({ error: error.message })
    }
})

// Get session status + optional QR if still pending
app.get('/sessions/:clubId/status', async (req, res) => {
    const clubId = Number(req.params.clubId)
    const session = getSession(clubId)

    if (!session) {
        return res.json({ status: 'disconnected' })
    }

    if (session.connected) {
        return res.json({ status: 'connected' })
    }

    if (session.qrCode) {
        const qrImage = await QRCode.toDataURL(session.qrCode)
        return res.json({ status: 'pending', qr_code: qrImage })
    }

    res.json({ status: 'initializing' })
})

// Send a WhatsApp message
app.post('/messages/send', async (req, res) => {
    try {
        const { club_id, phone_number, message } = req.body

        if (!club_id || !phone_number || !message) {
            return res.status(422).json({ error: 'club_id, phone_number, and message are required' })
        }

        await sendMessage(Number(club_id), phone_number, message)
        res.json({ success: true })
    } catch (error) {
        console.error(error)
        res.status(500).json({ error: error.message })
    }
})

// Disconnect and remove session
app.delete('/sessions/:clubId', (req, res) => {
    const clubId = Number(req.params.clubId)
    disconnectSession(clubId)
    res.json({ success: true })
})

const PORT = process.env.PORT || 3000
app.listen(PORT, () => {
    console.log(`WhatsApp service running on port ${PORT}`)
})
