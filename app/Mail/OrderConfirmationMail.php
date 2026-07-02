<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Customer-facing order confirmation email. Rendered from the
 * emails.order-confirmation Blade view (a self-contained, table-based
 * responsive HTML email).
 */
class OrderConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your GEN Z Foods order '.$this->order->order_number.' is confirmed 🎉',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-confirmation',
            with: [
                'order' => $this->order,
                'restaurant' => config('genz.restaurant'),
                'symbol' => config('genz.currency.symbol', 'Rs'),
                'viewUrl' => config('genz.web_app_url').'/order/'.$this->order->order_number,
                'supportEmail' => config('genz.support_email'),
            ],
        );
    }
}
