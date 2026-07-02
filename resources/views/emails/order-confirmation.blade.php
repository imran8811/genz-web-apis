<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="color-scheme" content="light">
  <meta name="supported-color-schemes" content="light">
  <title>Order Confirmation</title>
  <!--[if mso]><noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript><![endif]-->
</head>
@php($fmt = fn ($n) => $symbol.' '.number_format((float) $n))
<body style="margin:0; padding:0; background-color:#f2f3f5; -webkit-font-smoothing:antialiased; font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
  <!-- preheader -->
  <div style="display:none; max-height:0; overflow:hidden; opacity:0;">
    Order {{ $order->order_number }} confirmed — {{ $fmt($order->total_amount) }}. We're on it!
  </div>

  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f2f3f5;">
    <tr>
      <td align="center" style="padding:24px 12px;">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:600px; max-width:600px; background:#ffffff; border-radius:14px; overflow:hidden; border:1px solid #e6e7ea;">

          <!-- Header -->
          <tr>
            <td style="background:#111114; padding:26px 32px;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="font-family:Roboto,Arial,sans-serif; font-weight:900; font-size:26px; letter-spacing:0.5px; color:#ffffff;">
                    GEN Z <span style="color:#ff1f2d;">FOODS</span>
                  </td>
                  <td align="right" style="font-size:12px; color:#9b9ba4;">
                    Order&nbsp;confirmation
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Hero -->
          <tr>
            <td style="background:linear-gradient(120deg,#ff1f2d,#c30f1a); padding:30px 32px;">
              <div style="font-size:15px; color:#ffe0e2; margin-bottom:6px;">Thanks{{ $order->user ? ', '.e($order->user->name) : '' }}! 🎉</div>
              <div style="font-family:Roboto,Arial,sans-serif; font-weight:900; font-size:24px; color:#ffffff; line-height:1.2;">
                Your order is confirmed
              </div>
              <div style="margin-top:12px; display:inline-block; background:#ffe000; color:#111114; font-weight:700; font-size:14px; padding:7px 14px; border-radius:999px;">
                Order #{{ $order->order_number }}
              </div>
            </td>
          </tr>

          <!-- Body copy -->
          <tr>
            <td style="padding:26px 32px 6px;">
              <p style="margin:0 0 18px; font-size:15px; line-height:1.6; color:#3a3a42;">
                We've received your order and the kitchen is getting started. Here's a summary of what's on the way.
              </p>
            </td>
          </tr>

          <!-- Items -->
          <tr>
            <td style="padding:0 32px;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                @foreach ($order->items as $item)
                  <tr>
                    <td style="padding:12px 0; border-bottom:1px solid #eeeef1; vertical-align:top;">
                      <div style="font-size:15px; font-weight:600; color:#1c1c22;">
                        {{ $item->name }}
                        @if ($item->variant_label)
                          <span style="color:#8a8a93; font-weight:500;">· {{ $item->variant_label }}</span>
                        @endif
                      </div>
                      @if (!empty($item->selections))
                        <div style="font-size:12px; color:#8a8a93; margin-top:3px;">
                          {{ is_array($item->selections) ? implode(', ', $item->selections) : $item->selections }}
                        </div>
                      @endif
                      <div style="font-size:12px; color:#8a8a93; margin-top:3px;">Qty {{ $item->quantity }} × {{ $fmt($item->unit_price) }}</div>
                    </td>
                    <td align="right" style="padding:12px 0; border-bottom:1px solid #eeeef1; vertical-align:top; font-size:15px; font-weight:600; color:#1c1c22; white-space:nowrap;">
                      {{ $fmt($item->line_total) }}
                    </td>
                  </tr>
                @endforeach
              </table>
            </td>
          </tr>

          <!-- Totals -->
          <tr>
            <td style="padding:14px 32px 0;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="font-size:14px; color:#6b6b73; padding:4px 0;">Subtotal</td>
                  <td align="right" style="font-size:14px; color:#3a3a42; padding:4px 0;">{{ $fmt($order->subtotal) }}</td>
                </tr>
                <tr>
                  <td style="font-size:14px; color:#6b6b73; padding:4px 0;">Delivery</td>
                  <td align="right" style="font-size:14px; color:#3a3a42; padding:4px 0;">
                    {{ (float) $order->delivery_fee > 0 ? $fmt($order->delivery_fee) : 'Free' }}
                  </td>
                </tr>
                <tr>
                  <td style="font-family:Roboto,Arial,sans-serif; font-weight:900; font-size:17px; color:#111114; padding:12px 0 4px; border-top:2px solid #111114;">Total</td>
                  <td align="right" style="font-family:Roboto,Arial,sans-serif; font-weight:900; font-size:17px; color:#ff1f2d; padding:12px 0 4px; border-top:2px solid #111114;">{{ $fmt($order->total_amount) }}</td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- CTA -->
          <tr>
            <td align="center" style="padding:26px 32px 6px;">
              <table role="presentation" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center" style="border-radius:10px; background:#ff1f2d;">
                    <a href="{{ $viewUrl }}" target="_blank"
                       style="display:inline-block; padding:14px 34px; font-size:15px; font-weight:700; color:#ffffff; text-decoration:none; border-radius:10px;">
                      View your order →
                    </a>
                  </td>
                </tr>
              </table>
              <div style="font-size:12px; color:#9b9ba4; margin-top:10px;">or copy: {{ $viewUrl }}</div>
            </td>
          </tr>

          <!-- Delivery + payment -->
          <tr>
            <td style="padding:22px 32px 6px;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td width="50%" valign="top" style="padding-right:10px;">
                    <div style="font-size:11px; text-transform:uppercase; letter-spacing:0.6px; color:#9b9ba4; margin-bottom:6px;">Deliver to</div>
                    <div style="font-size:14px; color:#3a3a42; line-height:1.55;">
                      <strong>{{ $order->shipping_name }}</strong><br>
                      {{ $order->shipping_phone }}<br>
                      {{ $order->shipping_address_line_1 }}@if($order->shipping_area), {{ $order->shipping_area }}@endif<br>
                      {{ $order->shipping_city }}@if($order->shipping_landmark) · {{ $order->shipping_landmark }}@endif
                    </div>
                  </td>
                  <td width="50%" valign="top" style="padding-left:10px;">
                    <div style="font-size:11px; text-transform:uppercase; letter-spacing:0.6px; color:#9b9ba4; margin-bottom:6px;">Payment</div>
                    <div style="font-size:14px; color:#3a3a42; line-height:1.55;">
                      {{ $order->payment_method === 'cod' ? 'Cash on Delivery' : 'Online Payment' }}<br>
                      <span style="color:#8a8a93;">Status: {{ ucfirst($order->payment_status ?? 'pending') }}</span>
                    </div>
                    @if ($order->notes)
                      <div style="font-size:12px; color:#8a8a93; margin-top:10px;"><em>Note:</em> {{ $order->notes }}</div>
                    @endif
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="padding:26px 32px; background:#fafafb; border-top:1px solid #eeeef1;">
              <div style="font-size:13px; color:#6b6b73; line-height:1.6;">
                Need help with your order? Reply to this email or contact us at
                <a href="mailto:{{ $supportEmail }}" style="color:#ff1f2d; text-decoration:none;">{{ $supportEmail }}</a>@if(!empty($restaurant['phone'])) or call {{ $restaurant['phone'] }}@endif.
              </div>
              <div style="font-size:12px; color:#9b9ba4; margin-top:16px; line-height:1.6;">
                <strong style="color:#6b6b73;">{{ $restaurant['name'] ?? 'GEN Z Foods' }}</strong><br>
                @if(!empty($restaurant['address'])){{ $restaurant['address'] }}<br>@endif
                @if(!empty($restaurant['timing'])){{ $restaurant['timing'] }}<br>@endif
              </div>
              <div style="font-size:11px; color:#b9b9c0; margin-top:16px;">
                You're receiving this email because you placed an order at {{ $restaurant['name'] ?? 'GEN Z Foods' }}.
                This is a transactional message about your purchase.
              </div>
            </td>
          </tr>

        </table>
        <div style="font-size:11px; color:#b9b9c0; margin-top:16px;">© {{ date('Y') }} {{ $restaurant['name'] ?? 'GEN Z Foods' }}. All rights reserved.</div>
      </td>
    </tr>
  </table>
</body>
</html>
