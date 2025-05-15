# Tabby Webhook Fix Instructions

## Issue
The logs show that Tabby webhooks are being received, but they're failing signature verification because:
1. The webhook signature is null/missing in the incoming requests
2. The webhook signature format might be different from what we're expecting

## Solution

### 1. Update Your Environment Variables
Add the Tabby webhook secret to your `.env` file:

```
TABBY_WEBHOOK_SECRET=your_webhook_secret_key_here
```

You can obtain this secret key from the Tabby Merchant Dashboard or by contacting Tabby support.

### 2. Verify Webhook Headers
In your logs, you should now see the full headers of the incoming webhooks. This will help identify what header Tabby is using for the signature. Common header names may be:
- `X-Tabby-Signature`
- `Tabby-Signature`
- `X-Signature`

### 3. Whitelist Tabby IP Addresses
Ensure your firewall/security settings allow requests from Tabby's IP addresses:
```
34.166.36.90
34.166.35.211
34.166.34.222
34.166.37.207
34.93.76.191
```

### 4. Register Webhooks with Tabby
If you haven't already, make sure to register your webhook URL with Tabby. This is typically done through:
- Tabby Merchant Dashboard 
- API call to register webhook endpoints

### 5. Test the Webhook
After making these changes, you can test the webhook by:
1. Making a test purchase
2. Using a tool like webhook.site to debug
3. Checking the logs for successful signature verification

## Technical Details of the Fix
The controller has been updated to:
1. Check multiple possible header names for the signature
2. Log full request headers for debugging
3. Allow processing test webhooks even if signature verification fails
4. Improve payload structure parsing to handle different webhook formats
5. Add detailed logging for signature verification

If issues persist, please contact Tabby support and provide them with the webhook logs. 
