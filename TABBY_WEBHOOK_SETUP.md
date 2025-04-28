# Setting Up Tabby Webhooks

This document outlines the steps required to set up Tabby webhooks for payment status updates.

## Prerequisites

- A Tabby merchant account
- Access to the Tabby merchant dashboard
- Your application deployed to a publicly accessible URL

## Environment Configuration

Add the following environment variable to your `.env` file:

```
TABBY_WEBHOOK_SECRET=your_webhook_secret_here
```

This secret will be used to verify the authenticity of incoming webhook requests from Tabby.

## Webhook URL

The webhook endpoint in your application is:

```
https://your-domain.com/api/webhooks/tabby
```

Replace `your-domain.com` with your actual domain.

## Setting up in Tabby Dashboard

1. Login to your Tabby merchant dashboard
2. Navigate to Settings > Webhooks
3. Click on "Add webhook"
4. Enter your webhook URL: `https://your-domain.com/api/webhooks/tabby`
5. Select the following events to receive notifications for:
   - Payment authorized
   - Payment captured
   - Payment expired
   - Payment canceled
   - Payment rejected
6. Generate a webhook secret and copy it
7. Paste this secret into your `.env` file as `TABBY_WEBHOOK_SECRET`
8. Save the webhook configuration

## Testing the Webhook

To test if your webhook is set up correctly:

1. In the Tabby dashboard, go to the webhook configuration
2. Click on "Test webhook"
3. Select an event type to test
4. Check your application logs to confirm the webhook was received and processed correctly

## Webhook Events

The implementation handles the following payment status changes:

- **AUTHORIZED/CAPTURED/CLOSED/COMPLETED**: Payment is successful
- **REJECTED/EXPIRED/CANCELED**: Payment has failed
- **CREATED/PENDING**: Payment is still pending

For each status change, the application will:

1. Update the order or booking status in the database
2. Send notifications to the customer and admin
3. Record the payment details

## Troubleshooting

If webhooks are not being received:

1. Verify your application is accessible from the internet
2. Check that the webhook URL is correct in the Tabby dashboard
3. Ensure the webhook secret in your application matches the one in Tabby
4. Check your application logs for any errors related to webhook processing
5. Verify that your server allows incoming POST requests to the webhook endpoint

## Security Considerations

- Always use HTTPS for webhook endpoints
- The webhook signature verification ensures that only authentic requests from Tabby are processed
- Store the webhook secret securely and never expose it in client-side code 
