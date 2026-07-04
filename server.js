const express = require('express');
const axios = require('axios');
const cors = require('cors');

const app = express();
app.use(express.json());
app.use(cors());

// 🔴 REPLACE THESE WITH YOUR CASHFREE API KEYS FROM YOUR DASHBOARD
const CASHFREE_APP_ID = "667352df14424500e82f8c2307253766";
const CASHFREE_SECRET_KEY = "cfsk_ma_prod_fcce7fef36b367558df61a0bc68d7fca_3e106266";
const CASHFREE_URL = "https://api.cashfree.com/pg/orders"; 

app.post('/api/create-order', async (req, res) => {
    try {
        const { name, phone, amount } = req.body;
        const orderId = "TICKET_" + Date.now();

        const orderPayload = {
            order_id: orderId,
            order_amount: parseFloat(amount),
            order_currency: "INR",
            customer_details: {
                customer_id: "CUST_" + phone,
                customer_name: name,
                customer_phone: phone,
                customer_email: "gamer@example.com"
            }
        };

        const response = await axios.post(CASHFREE_URL, orderPayload, {
            headers: {
                'x-client-id': CASHFREE_APP_ID,
                'x-client-secret': CASHFREE_SECRET_KEY,
                'x-api-version': '2023-08-01',
                'Content-Type': 'application/json'
            }
        });

        res.status(200).json({
            order_id: response.data.order_id,
            payment_session_id: response.data.payment_session_id
        });

    } catch (error) {
        console.error("Cashfree Error:", error.response ? error.response.data : error.message);
        res.status(500).json({ error: "Failed to create order" });
    }
});

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
    console.log(`Server running on port ${PORT}`);
});
